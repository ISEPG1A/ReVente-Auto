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
            Utilitaires::envoyerJSON(['error' => 'Authentification requise'], 401);
            return;
        }

        $userId = $_SESSION['user']['id'];
        
        // Déterminer l'action à partir de l'URI
        $uri = $_SERVER['REQUEST_URI'];
        $action = 'liste';
        
        if (strpos($uri, '/statut') !== false) {
            $action = 'toggle-status';
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
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
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
                Utilitaires::envoyerJSON(['error' => 'Action inconnue'], 400);
        }
    }

    private function traiterPut($userId, $action) {
        // Valider le token CSRF
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!GestionnaireSession::validerTokenCSRF($csrfToken)) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
            return;
        }

        switch ($action) {
            case 'toggle-status':
                $this->basculerStatut($userId);
                break;
            default:
                Utilitaires::envoyerJSON(['error' => 'Action inconnue'], 400);
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
            Utilitaires::envoyerJSON(['error' => 'Erreur lors de la récupération des annonces'], 500);
        }
    }

    private function obtenirStatistiques($userId, $vehicleId) {
        if ($vehicleId <= 0) {
            Utilitaires::envoyerJSON(['error' => 'ID véhicule invalide'], 400);
            return;
        }

        try {
            $stats = $this->modele->obtenirStatistiquesVehicule($vehicleId, $userId);
            if (!$stats) {
                Utilitaires::envoyerJSON(['error' => 'Véhicule non trouvé ou non autorisé'], 404);
                return;
            }
            Utilitaires::envoyerJSON([
                'ok' => true,
                'statistiques' => $stats
            ]);
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['error' => 'Erreur lors de la récupération des statistiques'], 500);
        }
    }

    private function basculerStatut($userId) {
        $donnees = Utilitaires::lireCorpsJSON();
        $vehicleId = isset($donnees['vehicule_id']) ? (int)$donnees['vehicule_id'] : 0;
        $nouveauStatut = $donnees['status'] ?? '';

        if ($vehicleId <= 0) {
            Utilitaires::envoyerJSON(['error' => 'ID véhicule invalide'], 400);
            return;
        }

        if (!in_array($nouveauStatut, ['public', 'prive'])) {
            Utilitaires::envoyerJSON(['error' => 'Statut invalide'], 400);
            return;
        }

        try {
            $result = $this->modele->changerStatut($vehicleId, $userId, $nouveauStatut);
            if ($result) {
                Utilitaires::envoyerJSON([
                    'ok' => true,
                    'message' => 'Statut modifié avec succès',
                    'status' => $nouveauStatut
                ]);
            } else {
                Utilitaires::envoyerJSON(['error' => 'Véhicule non trouvé ou non autorisé'], 404);
            }
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['error' => 'Erreur lors de la modification du statut'], 500);
        }
    }
}

// Exécution
$controleur = new ControleurMesAnnonces();
$controleur->traiterRequete();
