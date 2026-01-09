<?php
/**
 * GestionnaireVues
 * Gère le comptage intelligent des vues de véhicules
 * Évite les vues multiples et abusives
 */

class GestionnaireVues {
    
    /**
     * Durée de cooldown entre deux vues (en secondes)
     * 24 heures = 86400 secondes
     */
    private const COOLDOWN_DUREE = 86400;
    
    /**
     * Nom du cookie pour tracking persistant
     */
    private const COOKIE_NAME = 'vehicle_views_tracking';
    
    /**
     * Vérifie si une vue doit être comptabilisée pour un véhicule
     * 
     * @param int $vehicleId ID du véhicule
     * @param int|null $userId ID de l'utilisateur connecté (null si non connecté)
     * @param int|null $ownerId ID du propriétaire du véhicule
     * @return bool True si la vue doit être comptée
     */
    public static function doitCompterVue($vehicleId, $userId = null, $ownerId = null) {
        // 1. Ne JAMAIS compter les vues du propriétaire
        if ($userId && $ownerId && $userId == $ownerId) {
            return false;
        }
        
        // 2. Vérifier si déjà vu récemment (session)
        if (self::estVuDansSession($vehicleId)) {
            return false;
        }
        
        // 3. Vérifier le cookie persistant (24h cooldown)
        if (self::estVuDansCookie($vehicleId)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Enregistre qu'un véhicule a été vu
     * 
     * @param int $vehicleId ID du véhicule
     */
    public static function marquerCommeVu($vehicleId) {
        // Marquer en session
        self::ajouterASession($vehicleId);
        
        // Marquer en cookie (persistant 24h)
        self::ajouterACookie($vehicleId);
    }
    
    /**
     * Vérifie si un véhicule a été vu dans la session actuelle
     */
    private static function estVuDansSession($vehicleId) {
        if (!isset($_SESSION['viewed_vehicles'])) {
            $_SESSION['viewed_vehicles'] = [];
        }
        
        return isset($_SESSION['viewed_vehicles'][$vehicleId]);
    }
    
    /**
     * Vérifie si un véhicule a été vu dans les dernières 24h (cookie)
     */
    private static function estVuDansCookie($vehicleId) {
        if (!isset($_COOKIE[self::COOKIE_NAME])) {
            return false;
        }
        
        // Décoder le cookie
        $viewedVehicles = json_decode($_COOKIE[self::COOKIE_NAME], true);
        if (!is_array($viewedVehicles)) {
            return false;
        }
        
        // Vérifier si le véhicule est dans le cookie et si le cooldown n'est pas expiré
        if (isset($viewedVehicles[$vehicleId])) {
            $lastViewTime = $viewedVehicles[$vehicleId];
            $timeSinceView = time() - $lastViewTime;
            
            // Si moins de 24h, ne pas compter
            if ($timeSinceView < self::COOLDOWN_DUREE) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Ajoute un véhicule à la liste des vus en session
     */
    private static function ajouterASession($vehicleId) {
        if (!isset($_SESSION['viewed_vehicles'])) {
            $_SESSION['viewed_vehicles'] = [];
        }
        
        $_SESSION['viewed_vehicles'][$vehicleId] = time();
    }
    
    /**
     * Ajoute un véhicule à la liste des vus en cookie (24h)
     */
    private static function ajouterACookie($vehicleId) {
        // Récupérer les véhicules déjà vus
        $viewedVehicles = [];
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            $viewedVehicles = json_decode($_COOKIE[self::COOKIE_NAME], true);
            if (!is_array($viewedVehicles)) {
                $viewedVehicles = [];
            }
        }
        
        // Nettoyer les anciennes entrées expirées (> 24h)
        $now = time();
        $viewedVehicles = array_filter($viewedVehicles, function($timestamp) use ($now) {
            return ($now - $timestamp) < self::COOLDOWN_DUREE;
        });
        
        // Limiter à 100 véhicules max pour éviter un cookie trop gros
        if (count($viewedVehicles) >= 100) {
            // Garder les 50 plus récents
            arsort($viewedVehicles);
            $viewedVehicles = array_slice($viewedVehicles, 0, 50, true);
        }
        
        // Ajouter le nouveau véhicule
        $viewedVehicles[$vehicleId] = $now;
        
        // Sauvegarder dans le cookie (expire dans 30 jours mais cooldown est 24h)
        $cookieExpire = time() + (30 * 24 * 60 * 60);
        $cookiePath = '/';
        $cookieSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $cookieHttpOnly = true;
        $cookieSameSite = 'Lax';
        
        setcookie(
            self::COOKIE_NAME,
            json_encode($viewedVehicles),
            [
                'expires' => $cookieExpire,
                'path' => $cookiePath,
                'secure' => $cookieSecure,
                'httponly' => $cookieHttpOnly,
                'samesite' => $cookieSameSite
            ]
        );
    }
    
    /**
     * Obtenir les statistiques de vues récentes (pour debug/analytics)
     * 
     * @return array Tableau avec le nombre de véhicules vus en session et cookie
     */
    public static function obtenirStatistiques() {
        $stats = [
            'session_count' => 0,
            'cookie_count' => 0,
            'session_vehicles' => [],
            'cookie_vehicles' => []
        ];
        
        if (isset($_SESSION['viewed_vehicles'])) {
            $stats['session_count'] = count($_SESSION['viewed_vehicles']);
            $stats['session_vehicles'] = array_keys($_SESSION['viewed_vehicles']);
        }
        
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            $viewedVehicles = json_decode($_COOKIE[self::COOKIE_NAME], true);
            if (is_array($viewedVehicles)) {
                $stats['cookie_count'] = count($viewedVehicles);
                $stats['cookie_vehicles'] = array_keys($viewedVehicles);
            }
        }
        
        return $stats;
    }
}
