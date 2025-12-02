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
                   v.marque, v.modele, v.image_path,
                   ub.first_name as buyer_name, ub.last_name as buyer_lastname, ub.avatar_path as buyer_avatar,
                   us.first_name as seller_name, us.last_name as seller_lastname, us.avatar_path as seller_avatar
            FROM conversations c
            LEFT JOIN vehicles v ON c.vehicle_id = v.id
            JOIN users ub ON c.buyer_id = ub.id
            JOIN users us ON c.seller_id = us.id
            WHERE c.buyer_id = ? OR c.seller_id = ?
            ORDER BY c.updated_at DESC
        ");
        $stmt->execute([$idUtilisateur, $idUtilisateur]);
        return $stmt->fetchAll();
    }

    public function verifierAppartenanceConversation($idConv, $idUtilisateur) {
        $stmt = $this->bdd->prepare("SELECT * FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?)");
        $stmt->execute([$idConv, $idUtilisateur, $idUtilisateur]);
        return $stmt->fetch();
    }

    public function marquerMessagesCommeLus($idConv, $idUtilisateur) {
        $stmt = $this->bdd->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?");
        return $stmt->execute([$idConv, $idUtilisateur]);
    }

    public function obtenirMessages($idConv) {
        $stmt = $this->bdd->prepare("
            SELECT m.*, u.first_name, u.last_name 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
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
        $cleDechiffree = CryptoService::dechiffrerDonnee($cleStockee);
        
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
}
