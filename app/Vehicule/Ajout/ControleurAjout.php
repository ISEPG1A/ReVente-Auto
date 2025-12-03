<?php

if (session_status() === PHP_SESSION_NONE) session_start();

class ControleurAjout {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleVehicule();
    }

    public function traiterRequete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->gererPost();
        } else {
            Utils::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
        }
    }

    private function gererPost() {
        // Vérifier l'authentification via le GestionnaireSession
        if (!GestionnaireSession::estConnecte()) {
            Utils::envoyerJSON(['error' => 'Authentification requise'], 401);
        }

        $donnees = $_POST;
        // Récupérer les images (tableau) ou l'image unique (rétrocompatibilité)
        $fichiersImages = $_FILES['images'] ?? ($_FILES['image'] ?? null);
        $userId = (int)$_SESSION['user']['id'];

        // Validation du nombre d'images (Max 10)
        if ($fichiersImages && isset($fichiersImages['name']) && is_array($fichiersImages['name'])) {
            if (count($fichiersImages['name']) > 10) {
                Utils::envoyerJSON(['error' => 'Vous ne pouvez télécharger que 10 images maximum.'], 422);
            }
        }

        // Validation basique
        $erreurs = [];
        if (!Utils::chaineValide($donnees['marque'] ?? '', 50)) $erreurs[] = 'Marque invalide.';
        if (!Utils::chaineValide($donnees['modele'] ?? '', 50)) $erreurs[] = 'Modèle invalide.';
        if (!Utils::entierEntre($donnees['annee'] ?? null, 1900, (int)date('Y') + 1)) $erreurs[] = 'Année invalide.';
        if (!Utils::nombreMinimum($donnees['prix'] ?? null, 0)) $erreurs[] = 'Prix invalide.';

        if (!empty($erreurs)) {
            Utils::envoyerJSON(['error' => implode(' ', $erreurs)], 422);
        }

        try {
            $nouveauVehicule = $this->modele->ajouter($donnees, $fichiersImages, $userId);
            Utils::envoyerJSON(['ok' => true, 'vehicle' => $nouveauVehicule], 201);
        } catch (Throwable $e) {
            Utils::envoyerJSON(['error' => $e->getMessage()], 500);
        }
    }
}

$controleur = new ControleurAjout();
$controleur->traiterRequete();
