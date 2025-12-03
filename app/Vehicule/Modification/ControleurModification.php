<?php

if (session_status() === PHP_SESSION_NONE) session_start();

class ControleurModification {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleVehicule();
    }

    public function traiterRequete() {
        $methode = $_SERVER['REQUEST_METHOD'];

        switch ($methode) {
            case 'GET':
                $this->gererGet();
                break;
            case 'POST':
                $this->gererPost();
                break;
            default:
                Utils::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
        }
    }

    /**
     * GET : Récupérer les données d'un véhicule pour pré-remplir le formulaire
     */
    private function gererGet() {
        // Vérifier l'authentification
        if (!GestionnaireSession::estConnecte()) {
            Utils::envoyerJSON(['error' => 'Authentification requise'], 401);
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            Utils::envoyerJSON(['error' => 'ID véhicule manquant'], 400);
        }

        $vehicule = $this->modele->obtenirParId($id);
        if (!$vehicule) {
            Utils::envoyerJSON(['error' => 'Véhicule introuvable'], 404);
        }

        // Vérifier que l'utilisateur est bien le propriétaire (ou admin)
        $userId = (int)$_SESSION['user']['id'];
        $isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';
        $estProprietaire = (int)$vehicule['user_id'] === $userId;

        if (!$estProprietaire && !$isAdmin) {
            Utils::envoyerJSON(['error' => 'Vous n\'êtes pas autorisé à modifier ce véhicule'], 403);
        }

        Utils::envoyerJSON($vehicule);
    }

    /**
     * POST : Mettre à jour les données d'un véhicule
     */
    private function gererPost() {
        // Vérifier l'authentification
        if (!GestionnaireSession::estConnecte()) {
            Utils::envoyerJSON(['error' => 'Authentification requise'], 401);
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            Utils::envoyerJSON(['error' => 'ID véhicule manquant'], 400);
        }

        $vehicule = $this->modele->obtenirParId($id);
        if (!$vehicule) {
            Utils::envoyerJSON(['error' => 'Véhicule introuvable'], 404);
        }

        // Vérifier les droits
        $userId = (int)$_SESSION['user']['id'];
        $isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';
        $estProprietaire = (int)$vehicule['user_id'] === $userId;

        if (!$estProprietaire && !$isAdmin) {
            Utils::envoyerJSON(['error' => 'Vous n\'êtes pas autorisé à modifier ce véhicule'], 403);
        }

        // Validation basique
        $erreurs = [];
        if (!Utils::chaineValide($_POST['marque'] ?? '', 50)) $erreurs[] = 'Marque invalide.';
        if (!Utils::chaineValide($_POST['modele'] ?? '', 50)) $erreurs[] = 'Modèle invalide.';
        if (!Utils::entierEntre($_POST['annee'] ?? null, 1900, (int)date('Y') + 1)) $erreurs[] = 'Année invalide.';
        if (!Utils::nombreMinimum($_POST['prix'] ?? null, 0)) $erreurs[] = 'Prix invalide.';

        if (!empty($erreurs)) {
            Utils::envoyerJSON(['error' => implode(' ', $erreurs)], 422);
        }

        // Préparer les données
        $donnees = [
            'marque' => $_POST['marque'],
            'modele' => $_POST['modele'],
            'annee' => (int)$_POST['annee'],
            'prix' => (float)$_POST['prix'],
            'km' => (int)($_POST['km'] ?? 0),
            'carburant' => $_POST['carburant'] ?? '',
            'boite' => $_POST['boite'] ?? '',
            'description' => $_POST['description'] ?? '',
            'ville' => $_POST['ville'] ?? ''
        ];

        // Récupérer les nouvelles images si présentes
        $nouvellesImages = $_FILES['images'] ?? null;
        
        // Récupérer les images existantes à conserver (tableau d'URLs)
        $imagesAConserver = isset($_POST['images_existantes']) ? json_decode($_POST['images_existantes'], true) : [];
        
        // Récupérer l'index de la nouvelle image qui doit être couverture (-1 si aucune)
        $couvertureNouvelleIndex = isset($_POST['couverture_nouvelle_index']) ? (int)$_POST['couverture_nouvelle_index'] : -1;

        try {
            $vehiculeMisAJour = $this->modele->modifier($id, $donnees, $nouvellesImages, $imagesAConserver, $couvertureNouvelleIndex);
            Utils::envoyerJSON(['ok' => true, 'vehicle' => $vehiculeMisAJour], 200);
        } catch (Exception $e) {
            Utils::envoyerJSON(['error' => $e->getMessage()], 500);
        }
    }
}

$controleur = new ControleurModification();
$controleur->traiterRequete();
