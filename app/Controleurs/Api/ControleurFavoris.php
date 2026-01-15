<?php

/**
 * Contrôleur gérant les actions liées aux favoris.
 */
class ControleurFavoris {

    /**
     * Point d'entrée principal du contrôleur.
     */
    public function traiterRequete() {
        // Vérification de l'authentification
        if (empty($_SESSION['user'])) {
            Utilitaires::envoyerJSON(['erreur' => 'Authentification requise'], 401);
            return;
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
                    Utilitaires::envoyerJSON(['erreur' => 'Méthode non autorisée'], 405);
            }
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['erreur' => $e->getMessage()], 500);
        }
    }

    /**
     * Gère la récupération des favoris (liste complète ou IDs seulement).
     */
    private function gererLecture($modele, $idUtilisateur) {
        if (isset($_GET['ids_only'])) {
            $ids = $modele->obtenirIdsFavoris($idUtilisateur);
            Utilitaires::envoyerJSON($ids);
        } else {
            $favoris = $modele->obtenirFavoris($idUtilisateur);
            Utilitaires::envoyerJSON($favoris);
        }
    }

    /**
     * Gère l'ajout d'un favori.
     */
    private function gererAjout($modele, $idUtilisateur) {
        $donnees = Utilitaires::lireCorpsJSON();
        $idVehicule = $donnees['vehicle_id'] ?? null;

        if (!$idVehicule) {
            Utilitaires::envoyerJSON(['erreur' => 'ID véhicule requis'], 422);
            return;
        }

        // Vérifier que l'utilisateur n'est pas le propriétaire du véhicule
        $modeleVehicule = new ModeleVehicule();
        $vehicule = $modeleVehicule->obtenirParId($idVehicule);
        
        if (!$vehicule) {
            Utilitaires::envoyerJSON(['erreur' => 'Véhicule introuvable'], 404);
            return;
        }
        
        if ((int)$vehicule['user_id'] === $idUtilisateur) {
            Utilitaires::envoyerJSON(['erreur' => 'Vous ne pouvez pas mettre votre propre annonce en favoris'], 403);
            return;
        }

        $ajoute = $modele->ajouterFavori($idUtilisateur, $idVehicule);
        
        if ($ajoute) {
            // Mettre à jour le compteur de favoris du véhicule
            $modeleVehicule->mettreAJourFavoris($idVehicule);
            
            // Log ajout favori
            try {
                $modeleAdmin = new ModeleAdmin();
                $modeleAdmin->ajouterLog('favori', 'Ajout aux favoris', [
                    'marque' => $vehicule['brand'] ?? '',
                    'modele' => $vehicule['model'] ?? '',
                    'annee' => $vehicule['year'] ?? ''
                ], $idUtilisateur, $idVehicule, null);
            } catch (Exception $logError) {
                error_log('Erreur log favori: ' . $logError->getMessage());
            }
            
            Utilitaires::envoyerJSON(['succes' => true, 'message' => 'Ajouté aux favoris'], 201);
        } else {
            Utilitaires::envoyerJSON(['succes' => true, 'message' => 'Déjà en favoris']);
        }
    }

    /**
     * Gère la suppression d'un favori.
     */
    private function gererSuppression($modele, $idUtilisateur) {
        $idVehicule = $_GET['id'] ?? null;

        if (!$idVehicule) {
            Utilitaires::envoyerJSON(['erreur' => 'ID véhicule requis'], 422);
            return;
        }

        // Récupérer info véhicule pour le log avant suppression
        $modeleVehicule = new ModeleVehicule();
        $vehicule = $modeleVehicule->obtenirParId($idVehicule);
        
        $modele->supprimerFavori($idUtilisateur, $idVehicule);
        
        // Mettre à jour le compteur de favoris du véhicule
        $modeleVehicule->mettreAJourFavoris($idVehicule);
        
        // Log suppression favori
        try {
            $modeleAdmin = new ModeleAdmin();
            $modeleAdmin->ajouterLog('favori', 'Retiré des favoris', [
                'marque' => $vehicule['brand'] ?? '',
                'modele' => $vehicule['model'] ?? '',
                'annee' => $vehicule['year'] ?? ''
            ], $idUtilisateur, (int)$idVehicule, null);
        } catch (Exception $logError) {
            error_log('Erreur log favori: ' . $logError->getMessage());
        }
        
        Utilitaires::envoyerJSON(['succes' => true, 'message' => 'Retiré des favoris']);
    }
}

// Instanciation et exécution
$controleur = new ControleurFavoris();
$controleur->traiterRequete();
