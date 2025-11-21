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
    // GET : Récupérer la liste des véhicules ou un véhicule spécifique
    // ============================================
    if ($methodeHTTP === 'GET') {
        $connexionBDD = obtenirConnexionBDD();

        // Cas 1 : Récupération d'un véhicule spécifique par ID
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $requeteSQL = "SELECT v.*, 
                           u.first_name as seller_first_name, u.last_name as seller_last_name,
                           u.email as seller_email, u.phone as seller_phone, u.avatar_path as seller_avatar
                    FROM vehicles v
                    LEFT JOIN users u ON u.id = v.user_id
                    WHERE v.id = ?";
            $requetePreparee = $connexionBDD->prepare($requeteSQL);
            $requetePreparee->execute([$id]);
            $vehicule = $requetePreparee->fetch();

            if ($vehicule) {
                envoyerJSON($vehicule);
            } else {
                envoyerJSON(['error' => 'Véhicule introuvable'], 404);
            }
            return; // Arrêter ici pour ne pas renvoyer la liste
        }

        // Cas 2 : Liste des véhicules (avec recherche optionnelle)
        $termRecherche = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        
        // Si une recherche est demandée, filtrer par marque ou modèle
        if ($termRecherche !== '') {
            $requeteSQL = "SELECT v.*,
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
            $requetePreparee = $connexionBDD->query("SELECT v.*,
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
        
        // Déterminer si c'est du JSON ou du Form Data (pour l'upload de fichiers)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $donnees = lireCorpsJSON();
        } else {
            $donnees = $_POST;
        }
        
        // Récupérer les données du véhicule
        $marque = $donnees['marque'] ?? '';
        $modele = $donnees['modele'] ?? '';
        $annee = $donnees['annee'] ?? null;
        $prix = $donnees['prix'] ?? null;
        $km = $donnees['km'] ?? null;
        $carburant = $donnees['carburant'] ?? '';
        $boite = $donnees['boite'] ?? '';
        $description = $donnees['description'] ?? '';
        $ville = $donnees['ville'] ?? '';

        // Gestion de l'image (si envoyée via multipart/form-data)
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../public/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            // Sécuriser l'extension
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array(strtolower($extension), $allowedExtensions)) {
                $filename = uniqid('v_') . '.' . $extension;
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = 'uploads/' . $filename;
                }
            }
        }

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
        // Note: Assurez-vous que votre table 'vehicles' a bien les colonnes ajoutées (km, carburant, etc.)
        // Si elles n'existent pas encore, la requête échouera.
        // Pour la compatibilité immédiate, on vérifie si on peut insérer ces champs ou on fait un fallback
        
        try {
            $requetePreparee = $connexionBDD->prepare("INSERT INTO vehicles (marque, modele, annee, prix, km, carburant, boite, description, ville, image_path, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $requetePreparee->execute([$marque, $modele, (int)$annee, (float)$prix, (int)$km, $carburant, $boite, $description, $ville, $imagePath, $idUtilisateur]);
        } catch (PDOException $e) {
            // Fallback si les colonnes n'existent pas encore (pour éviter de casser l'app si la migration n'est pas faite)
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                $requetePreparee = $connexionBDD->prepare("INSERT INTO vehicles (marque, modele, annee, prix, user_id) VALUES (?, ?, ?, ?, ?)");
                $requetePreparee->execute([$marque, $modele, (int)$annee, (float)$prix, $idUtilisateur]);
            } else {
                throw $e;
            }
        }

        // Récupérer le véhicule créé avec toutes ses informations
        $idVehicule = (int)$connexionBDD->lastInsertId();
        $ligneResultat = $connexionBDD->query("SELECT v.*,
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
