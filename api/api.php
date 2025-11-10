<?php
/**
 * API de gestion des véhicules (CRUD)
 * 
 * Points d'accès :
 * - GET  : Récupérer la liste des véhicules (avec recherche optionnelle)
 * - POST : Créer un nouveau véhicule (authentification requise)
 * - DELETE : Supprimer un véhicule (propriétaire ou admin uniquement)
 */

require __DIR__ . '/config.php';

// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) session_start();

// Récupérer la méthode HTTP utilisée
$methodeHTTP = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    // ============================================
    // GET : Récupérer la liste des véhicules
    // ============================================
    if ($methodeHTTP === 'GET') {
        // Récupérer le terme de recherche (optionnel)
        $termRecherche = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $connexionBDD = obtenirConnexionBDD();
        
        // Si une recherche est demandée, filtrer par marque ou modèle
        if ($termRecherche !== '') {
            $requeteSQL = "SELECT v.id, v.marque, v.modele, v.annee, v.prix, v.created_at,
                           v.user_id as seller_id,
                           u.first_name as seller_first_name, u.last_name as seller_last_name,
                           u.email as seller_email, u.phone as seller_phone
                    FROM vehicles v
                    LEFT JOIN users u ON u.id = v.user_id
                    WHERE v.marque LIKE :q OR v.modele LIKE :q
                    ORDER BY v.created_at DESC";
            $requetePreparee = $connexionBDD->prepare($requeteSQL);
            $motifRecherche = "%$termRecherche%";
            $requetePreparee->bindParam(':q', $motifRecherche, PDO::PARAM_STR);
            $requetePreparee->execute();
        } else {
            // Sinon, récupérer tous les véhicules
            $requetePreparee = $connexionBDD->query("SELECT v.id, v.marque, v.modele, v.annee, v.prix, v.created_at,
                                         v.user_id as seller_id,
                                         u.first_name as seller_first_name, u.last_name as seller_last_name,
                                         u.email as seller_email, u.phone as seller_phone
                                  FROM vehicles v
                                  LEFT JOIN users u ON u.id = v.user_id
                                  ORDER BY v.created_at DESC");
        }
        
        // Envoyer la liste en JSON
        envoyerJSON($requetePreparee->fetchAll());
    }

    // ============================================
    // POST : Créer un nouveau véhicule
    // ============================================
    if ($methodeHTTP === 'POST') {
        // Vérifier que l'utilisateur est connecté
        if (empty($_SESSION['user'])) envoyerJSON(['error' => 'Authentification requise'], 401);
        
        $idUtilisateur = (int)$_SESSION['user']['id'];
        $donnees = lireCorpsJSON();
        
        // Récupérer les données du véhicule
        $marque = $donnees['marque'] ?? '';
        $modele = $donnees['modele'] ?? '';
        $annee = $donnees['annee'] ?? null;
        $prix = $donnees['prix'] ?? null;

        // Valider les données
        $anneeActuelle = (int)date('Y') + 1;
        $erreursValidation = [];
        
        if (!chaineValide($marque, 50)) $erreursValidation[] = 'Marque invalide.';
        if (!chaineValide($modele, 50)) $erreursValidation[] = 'Modèle invalide.';
        if (!entierEntre($annee, 1900, $anneeActuelle)) $erreursValidation[] = 'Année invalide.';
        if (!nombreMinimum($prix, 0)) $erreursValidation[] = 'Prix invalide.';
        
        if ($erreursValidation) envoyerJSON(['error' => implode(' ', $erreursValidation)], 422);

        // Insérer le véhicule dans la base de données
        $connexionBDD = obtenirConnexionBDD();
        $requetePreparee = $connexionBDD->prepare("INSERT INTO vehicles (marque, modele, annee, prix, user_id) VALUES (?, ?, ?, ?, ?)");
        $requetePreparee->execute([$marque, $modele, (int)$annee, (float)$prix, $idUtilisateur]);

        // Récupérer le véhicule créé avec toutes ses informations
        $idVehicule = (int)$connexionBDD->lastInsertId();
        $ligneResultat = $connexionBDD->query("SELECT v.id, v.marque, v.modele, v.annee, v.prix, v.created_at,
                                   v.user_id as seller_id,
                                   u.first_name as seller_first_name, u.last_name as seller_last_name,
                                   u.email as seller_email, u.phone as seller_phone
                            FROM vehicles v LEFT JOIN users u ON u.id = v.user_id WHERE v.id = " . $idVehicule)->fetch();
        
        envoyerJSON(['ok' => true, 'vehicle' => $ligneResultat], 201);
    }

    // ============================================
    // DELETE : Supprimer un véhicule
    // ============================================
    if ($methodeHTTP === 'DELETE') {
        // Vérifier que l'utilisateur est connecté
        if (empty($_SESSION['user'])) envoyerJSON(['error' => 'Authentification requise'], 401);
        
        // Récupérer l'ID du véhicule à supprimer
        $idVehicule = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($idVehicule <= 0) envoyerJSON(['error' => 'ID invalide'], 422);
        
        // Vérifier que le véhicule existe
        $connexionBDD = obtenirConnexionBDD();
        $requetePreparee = $connexionBDD->prepare("SELECT id, user_id FROM vehicles WHERE id = ?");
        $requetePreparee->execute([$idVehicule]);
        $vehicule = $requetePreparee->fetch();
        
        if (!$vehicule) envoyerJSON(['error' => 'Véhicule introuvable'], 404);
        
        // Vérifier les permissions : propriétaire ou administrateur
        $estProprietaire = (int)$vehicule['user_id'] === (int)$_SESSION['user']['id'];
        
        $requeteRole = $connexionBDD->prepare("SELECT role FROM users WHERE id = ?");
        $requeteRole->execute([(int)$_SESSION['user']['id']]);
        $estAdministrateur = ($requeteRole->fetchColumn() === 'admin');
        
        if (!$estProprietaire && !$estAdministrateur) {
            envoyerJSON(['error' => 'Non autorisé'], 403);
        }
        
        // Supprimer le véhicule
        $connexionBDD->prepare("DELETE FROM vehicles WHERE id = ?")->execute([$idVehicule]);
        envoyerJSON(['ok' => true]);
    }

    // Méthode HTTP non supportée
    envoyerJSON(['error' => 'Méthode non autorisée'], 405);
    
} catch (Throwable $erreur) {
    envoyerJSON(['error' => 'Erreur serveur: ' . $erreur->getMessage()], 500);
}
