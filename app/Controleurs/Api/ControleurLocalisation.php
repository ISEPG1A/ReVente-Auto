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

require_once __DIR__ . '/../../Services/Securite.php';
require_once __DIR__ . '/../../Modeles/ModeleLocalisation.php';

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
            
            // Récupération et validation de la ville + code postal (optionnel)
            $ville = isset($_GET['ville']) ? trim($_GET['ville']) : '';
            $codePostal = isset($_GET['code_postal']) ? trim($_GET['code_postal']) : '';
            
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
            
            // Validation du code postal si fourni
            if (!empty($codePostal)) {
                if (!preg_match('/^\d{5}$/', $codePostal)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Le code postal doit contenir exactement 5 chiffres'
                    ]);
                    return;
                }
            }
            
            // Récupération des coordonnées (avec code postal pour améliorer la précision)
            $coordonnees = $this->modele->obtenirCoordonnees($ville, $codePostal);
            
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
    
    /**
     * Récupère les villes correspondant à un code postal français
     * Utilise l'API gouvernementale : geo.api.gouv.fr
     * 
     * @return void Renvoie du JSON
     */
    public function obtenirVillesParCodePostal() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // Validation de la requête
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Méthode non autorisée'], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            // Validation du code postal
            $codePostal = $_GET['code_postal'] ?? '';
            
            if (empty($codePostal)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Code postal manquant'], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            // Validation format (5 chiffres)
            if (!preg_match('/^\d{5}$/', $codePostal)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Code postal invalide (5 chiffres requis)'], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            // Appel à l'API gouvernementale française
            $url = "https://geo.api.gouv.fr/communes?codePostal=" . urlencode($codePostal) . "&fields=nom,code,codesPostaux,centre&format=json&geometry=centre";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'ReVente-Auto/1.0');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erreur de connexion à l\'API gouvernementale'], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            if ($httpCode !== 200) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des données'], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            $communes = json_decode($response, true);
            
            if (!is_array($communes) || empty($communes)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Aucune ville trouvée pour ce code postal'], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            // Formater la réponse
            $villes = [];
            foreach ($communes as $commune) {
                $villes[] = [
                    'nom' => $commune['nom'],
                    'code' => $commune['code'],
                    'code_postal' => $codePostal,
                    'latitude' => $commune['centre']['coordinates'][1] ?? null,
                    'longitude' => $commune['centre']['coordinates'][0] ?? null
                ];
            }
            
            // Trier par nom
            usort($villes, function($a, $b) {
                return strcmp($a['nom'], $b['nom']);
            });
            
            echo json_encode([
                'success' => true,
                'code_postal' => $codePostal,
                'villes' => $villes,
                'count' => count($villes)
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération des villes'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// INSTANCIATION ET ROUTAGE
// ═══════════════════════════════════════════════════════════════════════════

$controleur = new ControleurLocalisation();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'coordonnees';

if ($method === 'GET') {
    if ($action === 'villes') {
        $controleur->obtenirVillesParCodePostal();
    } else {
        $controleur->obtenirCoordonnees();
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
}
