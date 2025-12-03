<?php

// Démarrage de la session si nécessaire
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Contrôleur gérant les actions liées aux favoris.
 */
class ControleurFavoris {

    /**
     * Point d'entrée principal du contrôleur.
     */
    public function gererRequete() {
        // Vérification de l'authentification
        if (empty($_SESSION['user'])) {
            Utils::envoyerJSON(['erreur' => 'Authentification requise'], 401);
        }

        $idUtilisateur = (int)$_SESSION['user']['id'];
        $modele = new ModeleFavoris();
        $methode = $_SERVER['REQUEST_METHOD'];

        try {
            switch ($methode) {
                case 'GET':
                    $this->gererLecture($modele, $idUtilisateur);
                    break;
                case 'POST':
                    $this->gererAjout($modele, $idUtilisateur);
                    break;
                case 'DELETE':
                    $this->gererSuppression($modele, $idUtilisateur);
                    break;
                default:
                    Utils::envoyerJSON(['erreur' => 'Méthode non autorisée'], 405);
            }
        } catch (Exception $e) {
            Utils::envoyerJSON(['erreur' => $e->getMessage()], 500);
        }
    }

    /**
     * Gère la récupération des favoris (liste complète ou IDs seulement).
     */
    private function gererLecture($modele, $idUtilisateur) {
        if (isset($_GET['ids_only'])) {
            $ids = $modele->obtenirIdsFavoris($idUtilisateur);
            Utils::envoyerJSON($ids);
        } else {
            $favoris = $modele->obtenirFavoris($idUtilisateur);
            Utils::envoyerJSON($favoris);
        }
    }

    /**
     * Gère l'ajout d'un favori.
     */
    private function gererAjout($modele, $idUtilisateur) {
        $donnees = Utils::lireCorpsJSON();
        $idVehicule = $donnees['vehicle_id'] ?? null;

        if (!$idVehicule) {
            Utils::envoyerJSON(['erreur' => 'ID véhicule requis'], 422);
        }

        $ajoute = $modele->ajouterFavori($idUtilisateur, $idVehicule);
        
        if ($ajoute) {
            Utils::envoyerJSON(['succes' => true, 'message' => 'Ajouté aux favoris'], 201);
        } else {
            Utils::envoyerJSON(['succes' => true, 'message' => 'Déjà en favoris']);
        }
    }

    /**
     * Gère la suppression d'un favori.
     */
    private function gererSuppression($modele, $idUtilisateur) {
        $idVehicule = $_GET['id'] ?? null;

        if (!$idVehicule) {
            Utils::envoyerJSON(['erreur' => 'ID véhicule requis'], 422);
        }

        $modele->supprimerFavori($idUtilisateur, $idVehicule);
        Utils::envoyerJSON(['succes' => true, 'message' => 'Retiré des favoris']);
    }
}

// Instanciation et exécution
$controleur = new ControleurFavoris();
$controleur->gererRequete();
