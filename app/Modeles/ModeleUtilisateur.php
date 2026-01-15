<?php

class ModeleUtilisateur {
    private $connexion;

    public function __construct() {
        $this->connexion = BaseDeDonnees::obtenirConnexion();
    }

    public function trouverParEmail($email) {
        $stmt = $this->connexion->prepare('SELECT id, first_name, email, avatar_path, password_hash, email_verified_at, role, banned_at, ban_reason FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function trouverParId($id) {
        $stmt = $this->connexion->prepare('SELECT id, first_name, last_name, email, phone, avatar_path, email_verified_at, role, password_hash, banned_at, ban_reason FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function creer($prenom, $nom, $email, $telephone, $motDePasse, $cheminAvatar = null) {
        $motDePasseHache = ServiceChiffrement::hacherMotDePasse($motDePasse);
        $cles = ServiceChiffrement::genererPaireCles();
        
        // Chiffrement de la clé privée avant stockage
        $clePriveeChiffree = ServiceChiffrement::chiffrerDonnee($cles['private']);
        
        $stmt = $this->connexion->prepare('INSERT INTO users (first_name,last_name,email,phone,password_hash,avatar_path,public_key,private_key,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())');
        
        try {
            $stmt->execute([$prenom, $nom, $email, $telephone, $motDePasseHache, $cheminAvatar, $cles['public'], $clePriveeChiffree]);
            return $this->connexion->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new Exception('Email déjà utilisé.');
            }
            throw $e;
        }
    }

    public function creerTokenReset($userId, $token) {
        // Invalider tous les anciens tokens non utilisés pour cet utilisateur
        $this->connexion->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$userId]);
        
        // Créer le nouveau token
        $stmt = $this->connexion->prepare('INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())');
        return $stmt->execute([$userId, $token]);
    }

    public function verifierTokenReset($token) {
        // 🔒 SÉCURITÉ : Récupérer tous les tokens actifs pour comparaison timing-safe
        $stmt = $this->connexion->prepare('SELECT pr.id, pr.user_id, pr.token FROM password_resets pr WHERE pr.used_at IS NULL AND pr.expires_at > NOW()');
        $stmt->execute();
        $tokens = $stmt->fetchAll();
        
        // Comparer avec hash_equals pour éviter les attaques timing
        foreach ($tokens as $row) {
            if (hash_equals($row['token'], $token)) {
                return ['id' => $row['id'], 'user_id' => $row['user_id']];
            }
        }
        return false;
    }

    public function mettreAJourMotDePasse($userId, $nouveauMotDePasse, $resetId = null) {
        $motDePasseHache = ServiceChiffrement::hacherMotDePasse($nouveauMotDePasse);
        
        $this->connexion->beginTransaction();
        try {
            $this->connexion->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')->execute([$motDePasseHache, $userId]);
            if ($resetId) {
                $this->connexion->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([$resetId]);
            }
            $this->connexion->commit();
            return true;
        } catch (Exception $e) {
            $this->connexion->rollBack();
            throw $e;
        }
    }

    public function mettreAJourProfil($id, $prenom, $nom, $telephone, $cheminAvatar = null) {
        if ($cheminAvatar) {
            $stmt = $this->connexion->prepare('UPDATE users SET first_name=?, last_name=?, phone=?, avatar_path=?, updated_at=NOW() WHERE id=?');
            return $stmt->execute([$prenom, $nom, $telephone, $cheminAvatar, $id]);
        } else {
            $stmt = $this->connexion->prepare('UPDATE users SET first_name=?, last_name=?, phone=?, updated_at=NOW() WHERE id=?');
            return $stmt->execute([$prenom, $nom, $telephone, $id]);
        }
    }

    public function supprimerCompte($id) {
        $this->connexion->beginTransaction();
        try {
            // 0. Annuler toutes les offres en cours de cet utilisateur (en tant qu'expéditeur ou receveur)
            // Annuler les offres envoyées par l'utilisateur
            $this->connexion->prepare("UPDATE offers SET status = 'cancelled' WHERE sender_id = ? AND status IN ('pending', 'accepted')")->execute([$id]);
            // Annuler les offres reçues par l'utilisateur (où il est l'autre partie de la conversation)
            $this->connexion->prepare("
                UPDATE offers o
                JOIN conversations c ON o.conversation_id = c.id
                SET o.status = 'cancelled'
                WHERE (c.buyer_id = ? OR c.seller_id = ?)
                AND o.sender_id != ?
                AND o.status IN ('pending', 'accepted')
            ")->execute([$id, $id, $id]);
            
            // 1. Récupérer tous les véhicules de l'utilisateur
            $stmt = $this->connexion->prepare('SELECT id, image_path FROM vehicles WHERE user_id = ?');
            $stmt->execute([$id]);
            $vehicules = $stmt->fetchAll();
            
            // 2. Supprimer les images des véhicules
            foreach ($vehicules as $vehicule) {
                if (!empty($vehicule['image_path'])) {
                    $cheminComplet = __DIR__ . '/../../public/' . $vehicule['image_path'];
                    if (file_exists($cheminComplet)) {
                        @unlink($cheminComplet);
                    }
                    // Supprimer le dossier du véhicule s'il est vide
                    $dossierVehicule = dirname($cheminComplet);
                    if (is_dir($dossierVehicule) && count(scandir($dossierVehicule)) <= 2) {
                        @rmdir($dossierVehicule);
                    }
                }
            }
            
            // 3. Supprimer les favoris liés aux véhicules de l'utilisateur
            $this->connexion->prepare('DELETE FROM favorites WHERE vehicle_id IN (SELECT id FROM vehicles WHERE user_id = ?)')-> execute([$id]);
            
            // 4. Supprimer les favoris de l'utilisateur
            $this->connexion->prepare('DELETE FROM favorites WHERE user_id = ?')->execute([$id]);
            
            // 5. Marquer les conversations comme supprimées du côté de cet utilisateur
            // Les conversations ne seront réellement supprimées que quand les 2 utilisateurs auront supprimé leur compte
            $this->connexion->prepare('UPDATE conversations SET buyer_deleted_at = NOW() WHERE buyer_id = ?')->execute([$id]);
            $this->connexion->prepare('UPDATE conversations SET seller_deleted_at = NOW() WHERE seller_id = ?')->execute([$id]);
            
            // Supprimer les conversations où les 2 utilisateurs ont supprimé leur compte
            $this->connexion->prepare('DELETE FROM conversations WHERE buyer_deleted_at IS NOT NULL AND seller_deleted_at IS NOT NULL')->execute();
            
            // 7. Supprimer les véhicules de l'utilisateur
            $this->connexion->prepare('DELETE FROM vehicles WHERE user_id = ?')->execute([$id]);
            
            // 8. Supprimer l'avatar de l'utilisateur
            $user = $this->trouverParId($id);
            if ($user && !empty($user['avatar_path'])) {
                $cheminAvatar = __DIR__ . '/../../public/' . $user['avatar_path'];
                if (file_exists($cheminAvatar)) {
                    @unlink($cheminAvatar);
                }
            }
            
            // 9. Supprimer les tokens de vérification email
            $this->connexion->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$id]);
            
            // 10. Supprimer les demandes de changement d'email
            $this->connexion->prepare('DELETE FROM changements_email WHERE id_utilisateur = ?')->execute([$id]);
            
            // 11. Supprimer les tokens de réinitialisation de mot de passe
            $this->connexion->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$id]);
            
            // 12. Supprimer l'utilisateur
            $result = $this->connexion->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            
            $this->connexion->commit();
            return $result;
        } catch (Exception $e) {
            $this->connexion->rollBack();
            throw $e;
        }
    }

    public function creerTokenVerificationEmail($userId, $token) {
        // Invalider tous les anciens tokens non utilisés pour cet utilisateur
        $this->connexion->prepare('UPDATE email_verifications SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$userId]);
        
        // Créer le nouveau token
        return $this->connexion->prepare('INSERT INTO email_verifications (user_id, token, expires_at, created_at) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())')->execute([$userId, $token]);
    }

    public function verifierTokenEmail($token) {
        // 🔒 SÉCURITÉ : Récupérer tous les tokens actifs pour comparaison timing-safe
        $stmt = $this->connexion->prepare('SELECT id, user_id, token FROM email_verifications WHERE used_at IS NULL AND expires_at > NOW()');
        $stmt->execute();
        $tokens = $stmt->fetchAll();
        
        // Comparer avec hash_equals pour éviter les attaques timing
        foreach ($tokens as $row) {
            if (hash_equals($row['token'], $token)) {
                return ['id' => $row['id'], 'user_id' => $row['user_id']];
            }
        }
        return false;
    }

    public function validerEmail($userId, $verificationId) {
        $this->connexion->beginTransaction();
        try {
            $this->connexion->prepare('UPDATE users SET email_verified_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([$userId]);
            $this->connexion->prepare('UPDATE email_verifications SET used_at = NOW() WHERE id = ?')->execute([$verificationId]);
            $this->connexion->commit();
            return true;
        } catch (Exception $e) {
            $this->connexion->rollBack();
            throw $e;
        }
    }
    
    public function obtenirDernierTokenEmail($userId) {
        $stmt = $this->connexion->prepare('SELECT created_at FROM email_verifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function obtenirDernierTokenReset($userId) {
        $stmt = $this->connexion->prepare('SELECT created_at FROM password_resets WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    // =============================================
    // GESTION DU CHANGEMENT D'EMAIL
    // =============================================

    /**
     * Crée un token pour le changement d'email
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $nouvelEmail Nouvelle adresse email
     * @param string $token Token de validation
     * @return bool
     */
    public function creerTokenChangementEmail($userId, $nouvelEmail, $token) {
        // Invalider tous les anciens tokens non utilisés pour cet utilisateur
        $this->connexion->prepare('UPDATE changements_email SET utilise_le = NOW() WHERE id_utilisateur = ? AND utilise_le IS NULL')->execute([$userId]);
        
        // Créer le nouveau token (expire dans 24h)
        $stmt = $this->connexion->prepare('INSERT INTO changements_email (id_utilisateur, nouvel_email, jeton, expire_le, cree_le) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())');
        return $stmt->execute([$userId, $nouvelEmail, $token]);
    }

    /**
     * Vérifie la validité d'un token de changement d'email
     * 🔒 SÉCURITÉ : Utilise hash_equals pour comparaison timing-safe
     * 
     * @param string $token Token à vérifier
     * @return array|false Données de la demande ou false
     */
    public function verifierTokenChangementEmail($token) {
        // Récupérer tous les tokens actifs pour comparaison timing-safe
        $stmt = $this->connexion->prepare('SELECT id, id_utilisateur, nouvel_email, jeton FROM changements_email WHERE utilise_le IS NULL AND expire_le > NOW()');
        $stmt->execute();
        $tokens = $stmt->fetchAll();
        
        // Comparer avec hash_equals pour éviter les attaques timing
        foreach ($tokens as $row) {
            if (hash_equals($row['jeton'], $token)) {
                return ['id' => $row['id'], 'id_utilisateur' => $row['id_utilisateur'], 'nouvel_email' => $row['nouvel_email']];
            }
        }
        return false;
    }

    /**
     * Applique le changement d'email
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $nouvelEmail Nouvelle adresse email
     * @param int $changeId ID de la demande de changement
     * @return bool
     */
    public function appliquerChangementEmail($userId, $nouvelEmail, $changeId) {
        $this->connexion->beginTransaction();
        try {
            // Mettre à jour l'email de l'utilisateur
            $this->connexion->prepare('UPDATE users SET email = ?, email_verified_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([$nouvelEmail, $userId]);
            
            // Marquer le token comme utilisé
            $this->connexion->prepare('UPDATE changements_email SET utilise_le = NOW() WHERE id = ?')->execute([$changeId]);
            
            $this->connexion->commit();
            return true;
        } catch (Exception $e) {
            $this->connexion->rollBack();
            throw $e;
        }
    }

    /**
     * Vérifie si un email est déjà utilisé par un autre compte
     * 
     * @param string $email Email à vérifier
     * @param int $excludeUserId ID de l'utilisateur à exclure
     * @return bool True si déjà utilisé
     */
    public function emailDejaUtilise($email, $excludeUserId = null) {
        if ($excludeUserId) {
            $stmt = $this->connexion->prepare('SELECT 1 FROM users WHERE email = ? AND id != ? LIMIT 1');
            $stmt->execute([$email, $excludeUserId]);
        } else {
            $stmt = $this->connexion->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
        }
        return (bool)$stmt->fetch();
    }

    /**
     * Obtient le dernier token de changement d'email (pour cooldown)
     * 
     * @param int $userId ID de l'utilisateur
     * @return array|false
     */
    public function obtenirDernierTokenChangementEmail($userId) {
        $stmt = $this->connexion->prepare('SELECT cree_le FROM changements_email WHERE id_utilisateur = ? ORDER BY cree_le DESC LIMIT 1');
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // MASQUAGE DU NUMÉRO DE TÉLÉPHONE
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Met à jour le paramètre de masquage du numéro de téléphone
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $hidePhone 1 pour masquer, 0 pour afficher
     * @return bool
     */
    public function mettreAJourMasquageTelephone($userId, $hidePhone) {
        $stmt = $this->connexion->prepare('UPDATE users SET hide_phone = ? WHERE id = ?');
        return $stmt->execute([$hidePhone, $userId]);
    }
}
