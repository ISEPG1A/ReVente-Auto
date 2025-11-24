<?php
/**
 * API de Messagerie Sécurisée
 */

require __DIR__ . '/config.php';
require __DIR__ . '/CryptoService.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Vérification auth
if (empty($_SESSION['user'])) {
    envoyerJSON(['error' => 'Authentification requise'], 401);
}

$userId = (int)$_SESSION['user']['id'];
$conn = obtenirConnexionBDD();
$method = $_SERVER['REQUEST_METHOD'];

try {
    // ============================================================
    // GET: Lister les conversations ou les messages d'une conv
    // ============================================================
    if ($method === 'GET') {
        
        // Cas 0: Compter les messages non lus (Badge)
        if (isset($_GET['action']) && $_GET['action'] === 'count_unread') {
            // Compter le nombre d'utilisateurs distincts qui m'ont envoyé des messages non lus
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT sender_id) as count 
                FROM messages 
                WHERE conversation_id IN (
                    SELECT id FROM conversations WHERE buyer_id = ? OR seller_id = ?
                )
                AND sender_id != ? 
                AND is_read = 0
            ");
            // Note: La sous-requête conversation_id est une sécurité supplémentaire, 
            // mais techniquement si je suis destinataire (implicite via sender_id != me), c'est bon.
            // Simplifions : On compte les messages où je suis le destinataire implicite dans mes conversations.
            // Mais la table messages n'a pas recipient_id. On doit passer par les conversations.
            
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT m.sender_id) 
                FROM messages m
                JOIN conversations c ON m.conversation_id = c.id
                WHERE (c.buyer_id = ? OR c.seller_id = ?)
                AND m.sender_id != ?
                AND m.is_read = 0
            ");
            $stmt->execute([$userId, $userId, $userId]);
            $count = $stmt->fetchColumn();
            
            envoyerJSON(['count' => (int)$count]);
        }

        // Cas 1: Récupérer les messages d'une conversation spécifique
        elseif (isset($_GET['conversation_id'])) {
            $convId = (int)$_GET['conversation_id'];
            
            // Vérifier l'appartenance
            $check = $conn->prepare("SELECT * FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?)");
            $check->execute([$convId, $userId, $userId]);
            $conv = $check->fetch();
            
            if (!$conv) envoyerJSON(['error' => 'Conversation introuvable ou accès refusé'], 403);

            // MARQUER COMME LU (Messages des autres)
            $markRead = $conn->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?");
            $markRead->execute([$convId, $userId]);

            // Récupérer les messages
            $stmt = $conn->prepare("
                SELECT m.*, u.first_name, u.last_name 
                FROM messages m 
                JOIN users u ON m.sender_id = u.id 
                WHERE conversation_id = ? 
                ORDER BY created_at ASC
            ");
            $stmt->execute([$convId]);
            $messages = $stmt->fetchAll();

            // Récupérer ma clé privée pour déchiffrer
            // NOTE: Dans un vrai système, la clé privée serait chiffrée par le mot de passe utilisateur
            // Ici on la récupère brute de la BDD (simulé)
            $stmtKey = $conn->prepare("SELECT private_key FROM users WHERE id = ?");
            $stmtKey->execute([$userId]);
            $myPrivateKey = $stmtKey->fetchColumn();

            // Déchiffrer les messages
            foreach ($messages as &$msg) {
                // Choix de la clé chiffrée à utiliser :
                // - Si je suis l'expéditeur, j'utilise encrypted_key_sender
                // - Si je suis le destinataire, j'utilise encrypted_key
                $isSender = ($msg['sender_id'] == $userId);
                
                // Compatibilité avec les anciens messages (avant la mise à jour double clé)
                // Si je suis l'expéditeur et qu'il n'y a pas de clé sender, je ne peux pas lire.
                $keyToUse = $isSender ? ($msg['encrypted_key_sender'] ?? null) : $msg['encrypted_key'];

                if (!$keyToUse) {
                    $msg['content_clear'] = "[Message ancien chiffré uniquement pour le destinataire]";
                } else {
                    try {
                        $msg['content_clear'] = CryptoService::dechiffrerMessage(
                            $msg['content'], 
                            $msg['iv'], 
                            $keyToUse, 
                            $myPrivateKey
                        );
                    } catch (Exception $e) {
                        $msg['content_clear'] = "[Erreur de déchiffrement]";
                    }
                }
                
                // On retire les données brutes pour la sécurité
                unset($msg['content']);
                unset($msg['iv']);
                unset($msg['encrypted_key']);
                unset($msg['encrypted_key_sender']);
            }

            envoyerJSON(['conversation' => $conv, 'messages' => $messages]);
        } 
        
        // Cas 2: Lister toutes les conversations
        else {
            $stmt = $conn->prepare("
                SELECT c.*, 
                       v.marque, v.modele, v.image_path,
                       ub.first_name as buyer_name, ub.last_name as buyer_lastname,
                       us.first_name as seller_name, us.last_name as seller_lastname
                FROM conversations c
                LEFT JOIN vehicles v ON c.vehicle_id = v.id
                JOIN users ub ON c.buyer_id = ub.id
                JOIN users us ON c.seller_id = us.id
                WHERE c.buyer_id = ? OR c.seller_id = ?
                ORDER BY c.updated_at DESC
            ");
            $stmt->execute([$userId, $userId]);
            $conversations = $stmt->fetchAll();
            
            // Formater pour le front
            foreach ($conversations as &$c) {
                $isBuyer = ($c['buyer_id'] == $userId);
                $c['other_user_name'] = $isBuyer ? ($c['seller_name'] . ' ' . $c['seller_lastname']) : ($c['buyer_name'] . ' ' . $c['buyer_lastname']);
                $c['other_user_id'] = $isBuyer ? $c['seller_id'] : $c['buyer_id'];
            }
            
            envoyerJSON($conversations);
        }
    }

    // ============================================================
    // POST: Créer une conversation ou envoyer un message
    // ============================================================
    elseif ($method === 'POST') {
        $data = lireCorpsJSON();
        
        // Action : Créer conversation (depuis bouton "Contacter")
        if (isset($data['action']) && $data['action'] === 'create_conv') {
            $vehicleId = (int)$data['vehicle_id'];
            $sellerId = (int)$data['seller_id'];
            
            if ($sellerId === $userId) envoyerJSON(['error' => 'Vous ne pouvez pas vous contacter vous-même'], 400);

            // Vérifier si existe déjà
            $check = $conn->prepare("SELECT id FROM conversations WHERE vehicle_id = ? AND buyer_id = ? AND seller_id = ?");
            $check->execute([$vehicleId, $userId, $sellerId]);
            $existing = $check->fetch();
            
            if ($existing) {
                envoyerJSON(['id' => $existing['id']]);
            } else {
                $stmt = $conn->prepare("INSERT INTO conversations (vehicle_id, buyer_id, seller_id) VALUES (?, ?, ?)");
                $stmt->execute([$vehicleId, $userId, $sellerId]);
                envoyerJSON(['id' => $conn->lastInsertId()], 201);
            }
        }
        
        // Action : Envoyer message
        else {
            $convId = (int)$data['conversation_id'];
            $content = trim($data['content']);
            
            if (empty($content)) envoyerJSON(['error' => 'Message vide'], 400);

            // Récupérer infos conversation pour savoir qui est le destinataire
            $stmt = $conn->prepare("SELECT buyer_id, seller_id FROM conversations WHERE id = ?");
            $stmt->execute([$convId]);
            $conv = $stmt->fetch();
            
            if (!$conv) envoyerJSON(['error' => 'Conversation introuvable'], 404);
            
            // Déterminer destinataire
            $destinataireId = ($conv['buyer_id'] == $userId) ? $conv['seller_id'] : $conv['buyer_id'];
            
            // Récupérer clé publique du destinataire ET de l'expéditeur
            $stmtKey = $conn->prepare("SELECT id, public_key FROM users WHERE id IN (?, ?)");
            $stmtKey->execute([$destinataireId, $userId]);
            $keys = $stmtKey->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $destPubKey = $keys[$destinataireId] ?? null;
            $senderPubKey = $keys[$userId] ?? null;
            
            if (!$destPubKey) envoyerJSON(['error' => 'Le destinataire n\'a pas de clé de chiffrement'], 500);
            if (!$senderPubKey) envoyerJSON(['error' => 'Vous n\'avez pas de clé de chiffrement'], 500);

            // CHIFFREMENT
            try {
                $encryptedData = CryptoService::chiffrerMessagePourDeux($content, $destPubKey, $senderPubKey);
                
                // Sauvegarder
                $insert = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, content, iv, encrypted_key, encrypted_key_sender) VALUES (?, ?, ?, ?, ?, ?)");
                $insert->execute([
                    $convId, 
                    $userId, 
                    $encryptedData['content'], 
                    $encryptedData['iv'], 
                    $encryptedData['encrypted_key_recipient'],
                    $encryptedData['encrypted_key_sender']
                ]);
                
                // Mettre à jour timestamp conversation
                $conn->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$convId]);
                
                envoyerJSON(['ok' => true]);
                
            } catch (Exception $e) {
                envoyerJSON(['error' => 'Erreur chiffrement: ' . $e->getMessage()], 500);
            }
        }
    }

} catch (Exception $e) {
    envoyerJSON(['error' => $e->getMessage()], 500);
}
