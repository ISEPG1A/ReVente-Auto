<?php
// Ultra App — API minimaliste (GET list, POST create)
// Aucune présentation ici; juste JSON + logique serveur

require __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        // Liste + recherche simple côté DB
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $pdo = db();
        if ($q !== '') {
            $sql = "SELECT v.id, v.marque, v.modele, v.annee, v.prix, v.created_at,
                           v.user_id as seller_id,
                           u.first_name as seller_first_name, u.last_name as seller_last_name,
                           u.email as seller_email, u.phone as seller_phone
                    FROM vehicles v
                    LEFT JOIN users u ON u.id = v.user_id
                    WHERE v.marque LIKE :q OR v.modele LIKE :q
                    ORDER BY v.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $like = "%$q%";
            $stmt->bindParam(':q', $like, PDO::PARAM_STR);
            $stmt->execute();
        } else {
            $stmt = $pdo->query("SELECT v.id, v.marque, v.modele, v.annee, v.prix, v.created_at,
                                         v.user_id as seller_id,
                                         u.first_name as seller_first_name, u.last_name as seller_last_name,
                                         u.email as seller_email, u.phone as seller_phone
                                  FROM vehicles v
                                  LEFT JOIN users u ON u.id = v.user_id
                                  ORDER BY v.created_at DESC");
        }
        $rows = $stmt->fetchAll();
        json($rows);
    }

    if ($method === 'POST') {
        // Création d'un véhicule — nécessite une session utilisateur
        if (empty($_SESSION['user'])) { json(['error' => 'Authentification requise'], 401); }
        $userId = (int)$_SESSION['user']['id'];
        $data = read_json_body();
        $marque = $data['marque'] ?? '';
        $modele = $data['modele'] ?? '';
        $annee = $data['annee'] ?? null;
        $prix   = $data['prix'] ?? null;

        $currentYear = (int)date('Y') + 1;
        $errors = [];
        if (!str_ok($marque, 50)) $errors[] = 'Marque invalide.';
        if (!str_ok($modele, 50)) $errors[] = 'Modèle invalide.';
        if (!int_between($annee, 1900, $currentYear)) $errors[] = 'Année invalide.';
        if (!num_min($prix, 0)) $errors[] = 'Prix invalide.';
        if ($errors) json(['error' => implode(' ', $errors)], 422);

        $pdo = db();
        $sql = "INSERT INTO vehicles (marque, modele, annee, prix, user_id) VALUES (:marque, :modele, :annee, :prix, :uid)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':marque' => $marque,
            ':modele' => $modele,
            ':annee' => (int)$annee,
            ':prix'   => (float)$prix,
            ':uid'    => $userId,
        ]);

        $id = (int)$pdo->lastInsertId();
        $row = $pdo->query("SELECT v.id, v.marque, v.modele, v.annee, v.prix, v.created_at,
                                   v.user_id as seller_id,
                                   u.first_name as seller_first_name, u.last_name as seller_last_name,
                                   u.email as seller_email, u.phone as seller_phone
                            FROM vehicles v LEFT JOIN users u ON u.id = v.user_id WHERE v.id = " . $id)->fetch();
        json(['ok' => true, 'vehicle' => $row], 201);
    }

    if ($method === 'DELETE') {
        // Suppression d'un véhicule — propriétaire ou admin
        if (empty($_SESSION['user'])) { json(['error' => 'Authentification requise'], 401); }
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) json(['error' => 'ID invalide'], 422);
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id, user_id FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);
        $v = $stmt->fetch();
        if (!$v) json(['error' => 'Véhicule introuvable'], 404);
        $isOwner = (int)$v['user_id'] === (int)$_SESSION['user']['id'];
        // Rafraîchir le rôle depuis la base au cas où il a changé après la connexion
        $roleRow = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $roleRow->execute([(int)$_SESSION['user']['id']]);
        $dbRole = $roleRow->fetchColumn();
        $isAdmin = ($dbRole === 'admin');
        if (!$isOwner && !$isAdmin) json(['error' => 'Non autorisé'], 403);
        $pdo->prepare("DELETE FROM vehicles WHERE id = ?")->execute([$id]);
        json(['ok' => true]);
    }

    // Méthode non supportée
    json(['error' => 'Méthode non autorisée'], 405);
} catch (Throwable $e) {
    json(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
}
