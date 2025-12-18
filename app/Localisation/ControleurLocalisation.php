<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONTRÔLEUR LOCALISATION - GÉOLOCALISATION DES VÉHICULES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce contrôleur gère la géolocalisation des véhicules via OpenStreetMap.
 * Il permet de récupérer les coordonnées GPS d'une ville pour afficher
 * la position approximative du véhicule sur une carte interactive.
 * 
 * Fonctionnalités :
 * - Récupération des coordonnées GPS via l'API Nominatim
 * - Mise en cache des résultats pour optimiser les performances
 * - Gestion des erreurs et des cas limites
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * @see     ModeleLocalisation Pour l'accès aux données
 * @see     https://nominatim.openstreetmap.org/ API de géolocalisation
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../Commun/Securite.php';
require_once __DIR__ . '/ModeleLocalisation.php';

class ControleurLocalisation {
    
    private $modele;
    
    /**
     * Constructeur - Initialise le modèle
     */
    public function __construct() {
        $this->modele = new ModeleLocalisation();
    }
    
    /**
     * Récupère les coordonnées GPS d'une ville
     * 
     * @return void Renvoie du JSON
     */
    public function obtenirCoordonnees() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // Validation de la requête
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode([
                    'success' => false,
                    'message' => 'Méthode non autorisée'
                ]);
                return;
            }
            
            // Récupération et validation de la ville
            $ville = isset($_GET['ville']) ? trim($_GET['ville']) : '';
            
            if (empty($ville)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Le paramètre "ville" est requis'
                ]);
                return;
            }
            
            // Sécurisation de l'entrée - nettoyer et échapper
            $ville = trim(strip_tags($ville));
            $ville = htmlspecialchars($ville, ENT_QUOTES, 'UTF-8');
            
            if (strlen($ville) < 2 || strlen($ville) > 100) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Le nom de la ville doit contenir entre 2 et 100 caractères'
                ]);
                return;
            }
            
            // Récupération des coordonnées
            $coordonnees = $this->modele->obtenirCoordonnees($ville);
            
            if ($coordonnees) {
                echo json_encode([
                    'success' => true,
                    'data' => $coordonnees
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Localisation non trouvée'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Erreur Localisation: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur serveur lors de la géolocalisation'
            ]);
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// INSTANCIATION ET ROUTAGE
// ═══════════════════════════════════════════════════════════════════════════

$controleur = new ControleurLocalisation();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $controleur->obtenirCoordonnees();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
}
