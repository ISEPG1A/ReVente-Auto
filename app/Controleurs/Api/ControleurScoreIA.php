<?php
/**
 * ControleurScoreIA - API pour le calcul et la récupération du score IA
 */

require_once __DIR__ . '/../../Services/Utilitaires.php';
require_once __DIR__ . '/../../Modeles/ModeleScoreIA.php';

class ControleurScoreIA {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleScoreIA();
    }

    /**
     * Récupérer le score d'un véhicule
     * GET /api/score-ia?vehicule_id=X
     * Ne recalcule PAS le score - utilise uniquement celui en BDD
     */
    public function getScore() {
        try {
            $vehiculeId = $_GET['vehicule_id'] ?? null;
            
            if (!$vehiculeId) {
                Utilitaires::envoyerJSON(['erreur' => 'ID du véhicule requis'], 400);
                return;
            }
            
            $score = $this->modele->getScore($vehiculeId);
            
            if ($score) {
                Utilitaires::envoyerJSON(['success' => true, 'data' => $score], 200);
            } else {
                // Pas de score en BDD = analyse non disponible
                Utilitaires::envoyerJSON([
                    'success' => true, 
                    'data' => [
                        'score' => null,
                        'label' => 'Non évalué',
                        'conseil' => 'Le score IA n\'a pas encore été calculé pour ce véhicule.'
                    ]
                ], 200);
            }
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['erreur' => $e->getMessage()], 500);
        }
    }

    /**
     * Calculer/recalculer le score d'un véhicule
     * POST /api/score-ia/calculer
     */
    public function calculer() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $vehiculeId = $data['vehicule_id'] ?? null;
            
            if (!$vehiculeId) {
                Utilitaires::envoyerJSON(['erreur' => 'ID du véhicule requis'], 400);
                return;
            }
            
            $score = $this->modele->calculerEtSauvegarder($vehiculeId);
            Utilitaires::envoyerJSON(['success' => true, 'data' => $score], 200);
            
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['erreur' => $e->getMessage()], 500);
        }
    }
}

// Routage
$controleur = new ControleurScoreIA();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    $controleur->getScore();
} elseif ($method === 'POST' && $action === 'calculer') {
    $controleur->calculer();
} else {
    Utilitaires::envoyerJSON(['erreur' => 'Méthode non autorisée'], 405);
}
