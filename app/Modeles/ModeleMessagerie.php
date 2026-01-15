<?php

class ModeleMessagerie {
    private $bdd;

    public function __construct() {
        $this->bdd = BaseDeDonnees::obtenirConnexion();
    }

    public function obtenirInfosUtilisateur($idUtilisateur) {
        $stmt = $this->bdd->prepare('SELECT id, first_name, last_name, email, phone, avatar_path, role FROM users WHERE id = ?');
        $stmt->execute([$idUtilisateur]);
        return $stmt->fetch();
    }

    public function compterMessagesNonLus($idUtilisateur) {
        $stmt = $this->bdd->prepare("
            SELECT COUNT(DISTINCT m.sender_id) 
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            WHERE (c.buyer_id = ? OR c.seller_id = ?)
            AND m.sender_id != ?
            AND m.is_read = 0
        ");
        $stmt->execute([$idUtilisateur, $idUtilisateur, $idUtilisateur]);
        return (int)$stmt->fetchColumn();
    }

    public function obtenirConversations($idUtilisateur) {
        $stmt = $this->bdd->prepare("
            SELECT c.*, 
                   v.marque, v.modele, v.image_path, v.prix, v.annee,
                   COALESCE(ub.first_name, 'Utilisateur') as buyer_name, 
                   COALESCE(ub.last_name, 'supprimé') as buyer_lastname, 
                   ub.avatar_path as buyer_avatar,
                   COALESCE(us.first_name, 'Utilisateur') as seller_name, 
                   COALESCE(us.last_name, 'supprimé') as seller_lastname, 
                   us.avatar_path as seller_avatar,
                   (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.is_read = 0) as messages_non_lus
            FROM conversations c
            LEFT JOIN vehicles v ON c.vehicle_id = v.id
            LEFT JOIN users ub ON c.buyer_id = ub.id
            LEFT JOIN users us ON c.seller_id = us.id
            WHERE (c.buyer_id = ? OR c.seller_id = ?)
            AND (
                (c.buyer_id = ? AND c.buyer_deleted_at IS NULL) OR
                (c.seller_id = ? AND c.seller_deleted_at IS NULL)
            )
            ORDER BY c.updated_at DESC
        ");
        $stmt->execute([$idUtilisateur, $idUtilisateur, $idUtilisateur, $idUtilisateur, $idUtilisateur]);
        return $stmt->fetchAll();
    }

    public function verifierAppartenanceConversation($idConv, $idUtilisateur) {
        $stmt = $this->bdd->prepare("
            SELECT * FROM conversations 
            WHERE id = ? 
            AND (
                (buyer_id = ? AND buyer_deleted_at IS NULL) OR 
                (seller_id = ? AND seller_deleted_at IS NULL)
            )
        ");
        $stmt->execute([$idConv, $idUtilisateur, $idUtilisateur]);
        return $stmt->fetch();
    }

    public function marquerMessagesCommeLus($idConv, $idUtilisateur) {
        $stmt = $this->bdd->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?");
        return $stmt->execute([$idConv, $idUtilisateur]);
    }

    public function obtenirMessages($idConv) {
        $stmt = $this->bdd->prepare("
            SELECT m.*, 
                   COALESCE(u.first_name, 'Utilisateur') as first_name, 
                   COALESCE(u.last_name, 'supprimé') as last_name
            FROM messages m 
            LEFT JOIN users u ON m.sender_id = u.id 
            WHERE conversation_id = ? 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$idConv]);
        return $stmt->fetchAll();
    }

    public function obtenirClePrivee($idUtilisateur) {
        $stmt = $this->bdd->prepare("SELECT private_key FROM users WHERE id = ?");
        $stmt->execute([$idUtilisateur]);
        $cleStockee = $stmt->fetchColumn();
        
        if (!$cleStockee) {
            return null;
        }

        // Tenter de déchiffrer
        $cleDechiffree = ServiceChiffrement::dechiffrerDonnee($cleStockee);
        
        if ($cleDechiffree !== false) {
            return $cleDechiffree;
        }

        // Si échec du déchiffrement, vérifier si c'est une clé en clair (Legacy)
        // Cela permet de supporter les anciens comptes non migrés
        if (strpos($cleStockee, '-----BEGIN') !== false && strpos($cleStockee, 'PRIVATE KEY-----') !== false) {
            return $cleStockee;
        }

        return null;
    }

    public function trouverConversation($idVehicule, $idAcheteur, $idVendeur) {
        $stmt = $this->bdd->prepare("SELECT id FROM conversations WHERE vehicle_id = ? AND buyer_id = ? AND seller_id = ?");
        $stmt->execute([$idVehicule, $idAcheteur, $idVendeur]);
        return $stmt->fetch();
    }

    public function creerConversation($idVehicule, $idAcheteur, $idVendeur) {
        $stmt = $this->bdd->prepare("INSERT INTO conversations (vehicle_id, buyer_id, seller_id) VALUES (?, ?, ?)");
        $stmt->execute([$idVehicule, $idAcheteur, $idVendeur]);
        return $this->bdd->lastInsertId();
    }

    public function obtenirParticipantsConversation($idConv) {
        $stmt = $this->bdd->prepare("SELECT buyer_id, seller_id FROM conversations WHERE id = ?");
        $stmt->execute([$idConv]);
        return $stmt->fetch();
    }

    /**
     * Obtient les informations détaillées des participants d'une conversation
     * Utilisé notamment pour les notifications par email
     */
    public function obtenirInfosParticipantsConversation($idConv) {
        $stmt = $this->bdd->prepare("
            SELECT c.buyer_id, c.seller_id, c.vehicle_id,
                   COALESCE(ub.first_name, 'Utilisateur') as buyer_first_name, 
                   COALESCE(ub.last_name, 'supprimé') as buyer_last_name, 
                   ub.email as buyer_email,
                   ub.email_verified_at as buyer_email_verified,
                   COALESCE(us.first_name, 'Utilisateur') as seller_first_name, 
                   COALESCE(us.last_name, 'supprimé') as seller_last_name, 
                   us.email as seller_email,
                   us.email_verified_at as seller_email_verified,
                   CONCAT(v.marque, ' ', v.modele) as vehicle_title,
                   v.prix as vehicle_price
            FROM conversations c
            LEFT JOIN users ub ON c.buyer_id = ub.id
            LEFT JOIN users us ON c.seller_id = us.id
            LEFT JOIN vehicles v ON c.vehicle_id = v.id
            WHERE c.id = ?
        ");
        $stmt->execute([$idConv]);
        return $stmt->fetch();
    }

    public function obtenirClesPubliques($idsUtilisateurs) {
        // $idsUtilisateurs doit être un tableau d'IDs
        if (empty($idsUtilisateurs)) return [];
        
        $placeholders = implode(',', array_fill(0, count($idsUtilisateurs), '?'));
        $stmt = $this->bdd->prepare("SELECT id, public_key FROM users WHERE id IN ($placeholders)");
        $stmt->execute($idsUtilisateurs);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function enregistrerMessage($idConv, $idExpediteur, $donneesChiffrees) {
        $stmt = $this->bdd->prepare("INSERT INTO messages (conversation_id, sender_id, content, iv, encrypted_key, encrypted_key_sender) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $idConv, 
            $idExpediteur, 
            $donneesChiffrees['content'], 
            $donneesChiffrees['iv'], 
            $donneesChiffrees['encrypted_key_recipient'],
            $donneesChiffrees['encrypted_key_sender']
        ]);
        
        // Mettre à jour le timestamp de la conversation
        $this->bdd->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$idConv]);
        
        return true;
    }

    // =============================================
    // GESTION DES PROPOSITIONS DE PRIX (OFFRES)
    // =============================================

    /**
     * Crée une nouvelle proposition de prix
     */
    public function creerProposition($idConversation, $idExpediteur, $montant) {
        // Vérifier qu'il n'y a pas déjà une offre en attente pour cette conversation
        $stmt = $this->bdd->prepare("SELECT id FROM offers WHERE conversation_id = ? AND status = 'pending'");
        $stmt->execute([$idConversation]);
        if ($stmt->fetch()) {
            throw new Exception("Une proposition est déjà en attente pour cette conversation.");
        }

        // Créer l'offre avec expiration dans 48h
        $expiration = date('Y-m-d H:i:s', strtotime('+48 hours'));
        $stmt = $this->bdd->prepare("INSERT INTO offers (conversation_id, sender_id, amount, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$idConversation, $idExpediteur, $montant, $expiration]);
        
        // Mettre à jour le timestamp de la conversation
        $this->bdd->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$idConversation]);
        
        return $this->bdd->lastInsertId();
    }

    /**
     * Récupère la proposition active d'une conversation
     */
    public function obtenirPropositionActive($idConversation) {
        // D'abord, mettre à jour les offres expirées
        $this->mettreAJourOffresExpirees();
        
        $stmt = $this->bdd->prepare("
            SELECT o.*, 
                   COALESCE(u.first_name, 'Utilisateur') as first_name, 
                   COALESCE(u.last_name, 'supprimé') as last_name
            FROM offers o
            LEFT JOIN users u ON o.sender_id = u.id
            WHERE o.conversation_id = ? 
            AND o.status IN ('pending', 'accepted')
            ORDER BY o.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$idConversation]);
        return $stmt->fetch();
    }

    /**
     * Récupère toutes les propositions d'une conversation
     */
    public function obtenirPropositions($idConversation) {
        $this->mettreAJourOffresExpirees();
        
        $stmt = $this->bdd->prepare("
            SELECT o.*, 
                   COALESCE(u.first_name, 'Utilisateur') as first_name, 
                   COALESCE(u.last_name, 'supprimé') as last_name
            FROM offers o
            LEFT JOIN users u ON o.sender_id = u.id
            WHERE o.conversation_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$idConversation]);
        return $stmt->fetchAll();
    }

    /**
     * Accepte une proposition
     */
    public function accepterProposition($idOffre, $idUtilisateur) {
        // Vérifier que l'utilisateur peut accepter (n'est pas l'expéditeur)
        $stmt = $this->bdd->prepare("SELECT * FROM offers WHERE id = ? AND status = 'pending'");
        $stmt->execute([$idOffre]);
        $offre = $stmt->fetch();
        
        if (!$offre) {
            throw new Exception("Proposition introuvable ou déjà traitée.");
        }
        
        if ($offre['sender_id'] == $idUtilisateur) {
            throw new Exception("Vous ne pouvez pas accepter votre propre proposition.");
        }

        // Vérifier que l'offre n'a pas expiré
        if (strtotime($offre['expires_at']) < time()) {
            $this->bdd->prepare("UPDATE offers SET status = 'expired' WHERE id = ?")->execute([$idOffre]);
            throw new Exception("Cette proposition a expiré.");
        }

        // Accepter l'offre - l'acheteur a 48h pour payer
        $paymentExpires = date('Y-m-d H:i:s', strtotime('+48 hours'));
        $stmt = $this->bdd->prepare("UPDATE offers SET status = 'accepted', accepted_at = NOW(), payment_expires_at = ? WHERE id = ?");
        $stmt->execute([$paymentExpires, $idOffre]);
        
        return true;
    }

    /**
     * Refuse une proposition
     */
    public function refuserProposition($idOffre, $idUtilisateur) {
        $stmt = $this->bdd->prepare("SELECT * FROM offers WHERE id = ? AND status = 'pending'");
        $stmt->execute([$idOffre]);
        $offre = $stmt->fetch();
        
        if (!$offre) {
            throw new Exception("Proposition introuvable ou déjà traitée.");
        }
        
        if ($offre['sender_id'] == $idUtilisateur) {
            throw new Exception("Vous ne pouvez pas refuser votre propre proposition.");
        }

        $stmt = $this->bdd->prepare("UPDATE offers SET status = 'declined' WHERE id = ?");
        $stmt->execute([$idOffre]);
        
        return true;
    }

    /**
     * Annule une proposition (par l'expéditeur ou après acceptation par l'acheteur)
     */
    public function annulerProposition($idOffre, $idUtilisateur) {
        $stmt = $this->bdd->prepare("SELECT * FROM offers WHERE id = ? AND status IN ('pending', 'accepted')");
        $stmt->execute([$idOffre]);
        $offre = $stmt->fetch();
        
        if (!$offre) {
            throw new Exception("Proposition introuvable ou déjà traitée.");
        }

        // Vérifier l'appartenance à la conversation
        $conv = $this->verifierAppartenanceConversation($offre['conversation_id'], $idUtilisateur);
        if (!$conv) {
            throw new Exception("Accès non autorisé.");
        }

        // Si en attente, seul l'expéditeur peut annuler
        // Si acceptée, seul l'acheteur (non-expéditeur) peut annuler
        if ($offre['status'] === 'pending' && $offre['sender_id'] != $idUtilisateur) {
            throw new Exception("Seul l'auteur peut annuler une proposition en attente.");
        }
        
        if ($offre['status'] === 'accepted') {
            // L'acheteur est celui qui n'a pas fait la proposition ou le vendeur
            // Dans tous les cas, les deux peuvent annuler après acceptation
        }

        $stmt = $this->bdd->prepare("UPDATE offers SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$idOffre]);
        
        return true;
    }

    /**
     * Marque une proposition comme payée (simulation)
     */
    public function marquerCommePaye($idOffre, $idUtilisateur) {
        $stmt = $this->bdd->prepare("SELECT o.*, c.buyer_id, c.seller_id FROM offers o JOIN conversations c ON o.conversation_id = c.id WHERE o.id = ? AND o.status = 'accepted'");
        $stmt->execute([$idOffre]);
        $offre = $stmt->fetch();
        
        if (!$offre) {
            throw new Exception("Proposition introuvable ou non acceptée.");
        }

        // Vérifier que le délai de paiement n'est pas dépassé
        if ($offre['payment_expires_at'] && strtotime($offre['payment_expires_at']) < time()) {
            $this->bdd->prepare("UPDATE offers SET status = 'expired' WHERE id = ?")->execute([$idOffre]);
            throw new Exception("Le délai de paiement a expiré.");
        }

        // Déterminer qui est l'acheteur (celui qui doit payer)
        $idAcheteur = $offre['buyer_id'];
        
        if ($idUtilisateur != $idAcheteur) {
            throw new Exception("Seul l'acheteur peut effectuer le paiement.");
        }

        $stmt = $this->bdd->prepare("UPDATE offers SET status = 'paid' WHERE id = ?");
        $stmt->execute([$idOffre]);
        
        return true;
    }

    /**
     * Met à jour les offres expirées
     */
    private function mettreAJourOffresExpirees() {
        // Expirer les offres en attente dont la date est dépassée
        $this->bdd->prepare("UPDATE offers SET status = 'expired' WHERE status = 'pending' AND expires_at < NOW()")->execute();
        
        // Expirer les offres acceptées dont le délai de paiement est dépassé
        $this->bdd->prepare("UPDATE offers SET status = 'expired' WHERE status = 'accepted' AND payment_expires_at < NOW()")->execute();
    }
}
