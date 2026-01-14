<?php
/**
 * Contrôleur Mes Annonces - Gestion des annonces de l'utilisateur
 */

if (session_status() === PHP_SESSION_NONE) session_start();

class ControleurMesAnnonces {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleVehicule();
    }

    public function traiterRequete() {
        // Vérifier l'authentification
        if (!GestionnaireSession::estConnecte()) {
            Utilitaires::envoyerJSON(['erreur' => 'Authentification requise'], 401);
            return;
        }

        $userId = $_SESSION['user']['id'];
        
        // Déterminer l'action à partir de l'URI
        $uri = $_SERVER['REQUEST_URI'];
        $action = 'liste';
        
        if (strpos($uri, '/statut') !== false) {
            $action = 'toggle-status';
        } elseif (strpos($uri, '/resoumettre') !== false) {
            $action = 'resoumettre';
        } elseif (isset($_GET['action'])) {
            $action = $_GET['action'];
        }

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $this->traiterGet($userId, $action);
                break;
            case 'PUT':
                $this->traiterPut($userId, $action);
                break;
            default:
                Utilitaires::envoyerJSON(['erreur' => 'Méthode non autorisée'], 405);
        }
    }

    private function traiterGet($userId, $action) {
        switch ($action) {
            case 'liste':
                $this->obtenirAnnonces($userId);
                break;
            case 'statistiques':
                $vehicleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                $this->obtenirStatistiques($userId, $vehicleId);
                break;
            default:
                Utilitaires::envoyerJSON(['erreur' => 'Action inconnue'], 400);
        }
    }

    private function traiterPut($userId, $action) {
        // Valider le token CSRF
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!GestionnaireSession::validerTokenCSRF($csrfToken)) {
            Utilitaires::envoyerJSON(['erreur' => 'Token CSRF invalide'], 403);
            return;
        }

        switch ($action) {
            case 'toggle-status':
                $this->basculerStatut($userId);
                break;
            case 'resoumettre':
                $this->resoumettrePourVerification($userId);
                break;
            default:
                Utilitaires::envoyerJSON(['erreur' => 'Action inconnue'], 400);
        }
    }

    private function obtenirAnnonces($userId) {
        try {
            $annonces = $this->modele->obtenirParUtilisateurAvecStats($userId);
            Utilitaires::envoyerJSON([
                'ok' => true,
                'annonces' => $annonces
            ]);
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['erreur' => 'Erreur lors de la récupération des annonces'], 500);
        }
    }

    private function obtenirStatistiques($userId, $vehicleId) {
        if ($vehicleId <= 0) {
            Utilitaires::envoyerJSON(['erreur' => 'ID véhicule invalide'], 400);
            return;
        }

        try {
            $stats = $this->modele->obtenirStatistiquesVehicule($vehicleId, $userId);
            if (!$stats) {
                Utilitaires::envoyerJSON(['erreur' => 'Véhicule non trouvé ou non autorisé'], 404);
                return;
            }
            Utilitaires::envoyerJSON([
                'ok' => true,
                'statistiques' => $stats
            ]);
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['erreur' => 'Erreur lors de la récupération des statistiques'], 500);
        }
    }

    private function basculerStatut($userId) {
        $donnees = Utilitaires::lireCorpsJSON();
        $vehicleId = isset($donnees['vehicule_id']) ? (int)$donnees['vehicule_id'] : 0;
        $nouveauStatut = $donnees['status'] ?? '';

        if ($vehicleId <= 0) {
            Utilitaires::envoyerJSON(['erreur' => 'ID véhicule invalide'], 400);
            return;
        }

        if (!in_array($nouveauStatut, ['public', 'prive'])) {
            Utilitaires::envoyerJSON(['erreur' => 'Statut invalide'], 400);
            return;
        }
        
        // Vérifier que l'annonce n'est pas en attente ou refusée
        $vehicule = $this->modele->obtenirParId($vehicleId);
        if (!$vehicule || $vehicule['user_id'] != $userId) {
            Utilitaires::envoyerJSON(['erreur' => 'Véhicule non trouvé ou non autorisé'], 404);
            return;
        }
        
        if ($vehicule['status'] === 'en_attente') {
            Utilitaires::envoyerJSON(['erreur' => 'Impossible de modifier le statut d\'une annonce en cours de vérification'], 403);
            return;
        }
        
        if ($vehicule['status'] === 'refuse') {
            Utilitaires::envoyerJSON(['erreur' => 'Cette annonce a été refusée. Modifiez-la et re-soumettez pour vérification.'], 403);
            return;
        }

        try {
            $result = $this->modele->changerStatut($vehicleId, $userId, $nouveauStatut);
            if ($result) {
                // Log changement statut annonce
                try {
                    $modeleAdmin = new ModeleAdmin();
                    $modeleAdmin->ajouterLog('annonce_statut', 'Statut annonce modifié', [
                        'marque' => $vehicule['marque'] ?? '',
                        'modele' => $vehicule['modele'] ?? '',
                        'annee' => $vehicule['annee'] ?? '',
                        'ancien_statut' => $vehicule['status'],
                        'nouveau_statut' => $nouveauStatut
                    ], $userId, $vehicleId, null);
                } catch (Exception $logError) {
                    error_log('Erreur log statut annonce: ' . $logError->getMessage());
                }
                
                Utilitaires::envoyerJSON([
                    'ok' => true,
                    'message' => 'Statut modifié avec succès',
                    'status' => $nouveauStatut
                ]);
            } else {
                Utilitaires::envoyerJSON(['erreur' => 'Véhicule non trouvé ou non autorisé'], 404);
            }
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['erreur' => 'Erreur lors de la modification du statut'], 500);
        }
    }
    
    /**
     * Re-soumettre une annonce refusée pour vérification
     */
    private function resoumettrePourVerification($userId) {
        $donnees = Utilitaires::lireCorpsJSON();
        $vehicleId = isset($donnees['vehicule_id']) ? (int)$donnees['vehicule_id'] : 0;

        if ($vehicleId <= 0) {
            Utilitaires::envoyerJSON(['erreur' => 'ID véhicule invalide'], 400);
            return;
        }
        
        // Vérifier que l'annonce appartient à l'utilisateur et est refusée
        $vehicule = $this->modele->obtenirParId($vehicleId);
        if (!$vehicule || $vehicule['user_id'] != $userId) {
            Utilitaires::envoyerJSON(['erreur' => 'Véhicule non trouvé ou non autorisé'], 404);
            return;
        }
        
        if ($vehicule['status'] !== 'refuse') {
            Utilitaires::envoyerJSON(['erreur' => 'Seules les annonces refusées peuvent être re-soumises'], 400);
            return;
        }

        try {
            // Passer en statut en_attente et effacer la raison du refus
            $result = $this->modele->resoumettrePourVerification($vehicleId, $userId);
            if (is_array($result) && !empty($result['success'])) {
                // Log resoumission annonce
                try {
                    $modeleAdmin = new ModeleAdmin();
                    $modeleAdmin->ajouterLog('annonce_statut', 'Annonce resoumise pour vérification', [
                        'marque' => $vehicule['marque'] ?? '',
                        'modele' => $vehicule['modele'] ?? '',
                        'annee' => $vehicule['annee'] ?? '',
                        'ancien_statut' => 'refuse',
                        'nouveau_statut' => 'en_attente'
                    ], $userId, $vehicleId, null);
                } catch (Exception $logError) {
                    error_log('Erreur log resoumission: ' . $logError->getMessage());
                }
                
                Utilitaires::envoyerJSON([
                    'ok' => true,
                    'message' => 'Annonce soumise pour vérification'
                ]);
            } else {
                $message = is_array($result) ? ($result['message'] ?? 'Erreur') : 'Erreur lors de la soumission';
                Utilitaires::envoyerJSON(['erreur' => $message], 500);
            }
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['erreur' => 'Erreur lors de la soumission'], 500);
        }
    }
}

// Instanciation et exécution du contrôleur
$controleur = new ControleurMesAnnonces();
$controleur->traiterRequete();
