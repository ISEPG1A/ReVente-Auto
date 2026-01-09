<?php

class ModeleUtilisateur {
    private $connexion;

    public function __construct() {
        $this->connexion = BaseDeDonnees::obtenirConnexion();
    }

    public function trouverParEmail($email) {
        $stmt = $this->connexion->prepare('SELECT id, first_name, email, avatar_path, password_hash, email_verified_at, phone_verified_at, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function trouverParId($id) {
        $stmt = $this->connexion->prepare('SELECT id, first_name, last_name, email, phone, avatar_path, email_verified_at, phone_verified_at, role, password_hash FROM users WHERE id = ?');
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
        $stmt = $this->connexion->prepare('SELECT pr.id, pr.user_id FROM password_resets pr WHERE pr.token = ? AND pr.used_at IS NULL AND pr.expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch();
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
            
            // 5. Supprimer les messages où l'utilisateur est impliqué
            $this->connexion->prepare('DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?')->execute([$id, $id]);
            
            // 6. Supprimer les conversations où l'utilisateur est impliqué
            $this->connexion->prepare('DELETE FROM conversations WHERE user1_id = ? OR user2_id = ?')->execute([$id, $id]);
            
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
            
            // 9. Supprimer l'utilisateur
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
        $stmt = $this->connexion->prepare('SELECT id, user_id FROM email_verifications WHERE token = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch();
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

    public function creerCodeTelephone($userId, $code) {
        return $this->connexion->prepare('UPDATE users SET phone_code = ?, phone_code_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE), updated_at = NOW() WHERE id = ?')->execute([$code, $userId]);
    }

    public function verifierCodeTelephone($userId) {
        $stmt = $this->connexion->prepare('SELECT phone_code, phone_code_expires_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function validerTelephone($userId) {
        return $this->connexion->prepare('UPDATE users SET phone_verified_at = NOW(), phone_code = NULL, phone_code_expires_at = NULL, updated_at = NOW() WHERE id = ?')->execute([$userId]);
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
}
