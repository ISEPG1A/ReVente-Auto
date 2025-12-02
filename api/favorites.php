<?php
/**
 * API de gestion des favoris
 * 
 * Points d'accès :
 * - GET  : Récupérer la liste des favoris de l'utilisateur connecté
 * - POST : Ajouter un véhicule aux favoris
 * - DELETE : Retirer un véhicule des favoris
 */

require __DIR__ . '/config.php';

// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) session_start();

$methodeHTTP = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Vérifier l'authentification pour toutes les opérations
if (empty($_SESSION['user'])) {
    envoyerJSON(['error' => 'Authentification requise'], 401);
}

$idUtilisateur = (int)$_SESSION['user']['id'];
$connexionBDD = obtenirConnexionBDD();

try {
    // ============================================
    // GET : Récupérer les favoris
    // ============================================
    if ($methodeHTTP === 'GET') {
        // Si on demande seulement les IDs (pour cocher les coeurs dans la galerie)
        if (isset($_GET['ids_only'])) {
            $requete = $connexionBDD->prepare("SELECT vehicle_id FROM favorites WHERE user_id = ?");
            $requete->execute([$idUtilisateur]);
            envoyerJSON($requete->fetchAll(PDO::FETCH_COLUMN));
        } else {
            // Récupérer les détails complets des véhicules favoris
            $requete = $connexionBDD->prepare("
                SELECT v.*, 
                       u.first_name as seller_first_name, u.last_name as seller_last_name,
                       u.email as seller_email, u.phone as seller_phone
                FROM favorites f
                JOIN vehicles v ON f.vehicle_id = v.id
                LEFT JOIN users u ON u.id = v.user_id
                WHERE f.user_id = ?
                ORDER BY f.created_at DESC
            ");
            $requete->execute([$idUtilisateur]);
            envoyerJSON($requete->fetchAll());
        }
    }

    // ============================================
    // POST : Ajouter un favori
    // ============================================
    elseif ($methodeHTTP === 'POST') {
        $donnees = lireCorpsJSON();
        $idVehicule = $donnees['vehicle_id'] ?? null;
        
        if (!$idVehicule) envoyerJSON(['error' => 'ID véhicule requis'], 422);
        
        // Vérifier si déjà en favori
        $check = $connexionBDD->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND vehicle_id = ?");
        $check->execute([$idUtilisateur, $idVehicule]);
        
        if (!$check->fetch()) {
            $insert = $connexionBDD->prepare("INSERT INTO favorites (user_id, vehicle_id) VALUES (?, ?)");
            $insert->execute([$idUtilisateur, $idVehicule]);
            envoyerJSON(['ok' => true, 'message' => 'Ajouté aux favoris'], 201);
        } else {
            envoyerJSON(['ok' => true, 'message' => 'Déjà en favoris']);
        }
    }

    // ============================================
    // DELETE : Retirer un favori
    // ============================================
    elseif ($methodeHTTP === 'DELETE') {
        $idVehicule = $_GET['id'] ?? null;
        
        if (!$idVehicule) envoyerJSON(['error' => 'ID véhicule requis'], 422);
        
        $delete = $connexionBDD->prepare("DELETE FROM favorites WHERE user_id = ? AND vehicle_id = ?");
        $delete->execute([$idUtilisateur, $idVehicule]);
        
        envoyerJSON(['ok' => true, 'message' => 'Retiré des favoris']);
    }
    else {
        envoyerJSON(['error' => 'Méthode non autorisée'], 405);
    }

} catch (PDOException $e) {
    // Si la table n'existe pas, on renvoie une erreur spécifique ou vide
    if ($e->getCode() === '42S02') { // Table not found
        envoyerJSON(['error' => 'Table favoris inexistante', 'code' => 'NO_TABLE'], 500);
    }
    envoyerJSON(['error' => 'Erreur base de données: ' . $e->getMessage()], 500);
} catch (Throwable $e) {
    envoyerJSON(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
}
