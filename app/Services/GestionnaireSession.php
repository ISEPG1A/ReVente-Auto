<?php

/**
 * Gestionnaire de session sécurisé
 * Gère le démarrage, la sécurité et l'expiration automatique des sessions.
 */
class GestionnaireSession {
    // Durée d'inactivité avant déconnexion (20 minutes en secondes)
    private const DUREE_INACTIVITE = 1200;
    
    // Durée avant affichage de l'avertissement (17 minutes en secondes)
    public const DUREE_AVANT_AVERTISSEMENT = 1020;
    
    // Durée du timer d'avertissement (3 minutes en secondes)
    public const DUREE_AVERTISSEMENT = 180;
    
    // 🔒 SÉCURITÉ : Limite d'annonces par utilisateur par jour
    private const LIMITE_ANNONCES_PAR_JOUR = 30; 

    /**
     * Démarre la session de manière sécurisée et vérifie l'inactivité
     */
    public static function demarrerSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuration sécurisée des cookies de session
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Lax'); // 🔒 Protection CSRF navigateur
            // ini_set('session.cookie_secure', 1); // À activer si HTTPS est disponible
            
            session_start();
            
            // 🔒 SÉCURITÉ : Protection contre Session Fixation
            if (!isset($_SESSION['initialisee'])) {
                session_regenerate_id(true);
                $_SESSION['initialisee'] = true;
                $_SESSION['ip_utilisateur'] = $_SERVER['REMOTE_ADDR'] ?? '';
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            }
            
            // 🔒 SÉCURITÉ : Validation IP et User-Agent
            self::validerSession();
        }

        self::verifierInactivite();
        
        // 🔒 SÉCURITÉ : Valider le token de session (déconnexion si mot de passe changé)
        if (self::estConnecte()) {
            self::validerTokenSession();
        }
    }

    /**
     * 🔒 SÉCURITÉ : Valide que la session n'a pas été détournée
     */
    private static function validerSession() {
        // Vérifier IP (si configurée)
        if (isset($_SESSION['ip_utilisateur'])) {
            $ipActuelle = $_SERVER['REMOTE_ADDR'] ?? '';
            if ($_SESSION['ip_utilisateur'] !== $ipActuelle) {
                // Session potentiellement détournée
                self::detruireSession();
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    Utilitaires::envoyerJSON(['erreur' => 'Session invalide', 'redirect' => '/connexion'], 401);
                }
                return;
            }
        }
        
        // Vérifier User-Agent
        if (isset($_SESSION['user_agent'])) {
            $userAgentActuel = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if ($_SESSION['user_agent'] !== $userAgentActuel) {
                // Session potentiellement détournée
                self::detruireSession();
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    Utilitaires::envoyerJSON(['erreur' => 'Session invalide', 'redirect' => '/connexion'], 401);
                }
                return;
            }
        }
    }

    /**
     * Vérifie si l'utilisateur a été inactif trop longtemps
     */
    private static function verifierInactivite() {
        if (isset($_SESSION['derniere_activite'])) {
            $tempsEcoule = time() - $_SESSION['derniere_activite'];
            
            if ($tempsEcoule > self::DUREE_INACTIVITE) {
                self::detruireSession();
                // Si c'est une requête AJAX, on renvoie une erreur 401
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    Utilitaires::envoyerJSON(['erreur' => 'Session expirée', 'redirect' => '/connexion'], 401);
                }
                // Sinon redirection vers connexion (si on n'est pas déjà dessus)
                // Note: La redirection se fait souvent côté client ou via header si pas de sortie
            }
        }
        
        // Mise à jour du timestamp d'activité
        $_SESSION['derniere_activite'] = time();
    }

    /**
     * Détruit proprement la session
     */
    public static function detruireSession() {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    public static function estConnecte() {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }

    /**
     * Récupère les données de l'utilisateur connecté
     */
    public static function obtenirUtilisateur() {
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * 🔒 SÉCURITÉ : Génère un token CSRF
     */
    public static function genererTokenCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * 🔒 SÉCURITÉ : Valide le token CSRF
     */
    public static function validerTokenCSRF($token) {
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * 🔒 SÉCURITÉ : Vérifie le rate limiting pour les annonces
     * Limite à 30 annonces par utilisateur par jour
     */
    public static function verifierLimiteAnnonces($userId) {
        $aujourdhui = date('Y-m-d');
        
        // Initialiser le compteur si nécessaire
        if (!isset($_SESSION['annonces_compteur'])) {
            $_SESSION['annonces_compteur'] = [];
        }
        
        // Réinitialiser si c'est un nouveau jour
        if (!isset($_SESSION['annonces_date']) || $_SESSION['annonces_date'] !== $aujourdhui) {
            $_SESSION['annonces_compteur'] = [];
            $_SESSION['annonces_date'] = $aujourdhui;
        }
        
        // Vérifier le compteur pour cet utilisateur
        $compteur = $_SESSION['annonces_compteur'][$userId] ?? 0;
        
        if ($compteur >= self::LIMITE_ANNONCES_PAR_JOUR) {
            return [
                'autorise' => false,
                'message' => "Limite atteinte : vous ne pouvez publier que " . self::LIMITE_ANNONCES_PAR_JOUR . " annonces par jour. Réessayez demain.",
                'compteur' => $compteur,
                'limite' => self::LIMITE_ANNONCES_PAR_JOUR
            ];
        }
        
        return [
            'autorise' => true,
            'compteur' => $compteur,
            'limite' => self::LIMITE_ANNONCES_PAR_JOUR
        ];
    }
    
    /**
     * 🔒 SÉCURITÉ : Incrémente le compteur d'annonces
     */
    public static function incrementerCompteurAnnonces($userId) {
        $aujourdhui = date('Y-m-d');
        
        if (!isset($_SESSION['annonces_compteur'])) {
            $_SESSION['annonces_compteur'] = [];
        }
        
        if (!isset($_SESSION['annonces_date']) || $_SESSION['annonces_date'] !== $aujourdhui) {
            $_SESSION['annonces_compteur'] = [];
            $_SESSION['annonces_date'] = $aujourdhui;
        }
        
        if (!isset($_SESSION['annonces_compteur'][$userId])) {
            $_SESSION['annonces_compteur'][$userId] = 0;
        }
        
        $_SESSION['annonces_compteur'][$userId]++;
    }
    
    /**
     * 🔒 SÉCURITÉ : Détruit toutes les sessions d'un utilisateur
     * Génère un nouveau token de session pour invalider toutes les sessions actives
     * Utilisé après changement de mot de passe ou en cas de compromission
     * 
     * @param int $userId ID de l'utilisateur
     */
    public static function detruireToutesSessions($userId) {
        try {
            $db = BaseDeDonnees::obtenirConnexion();
            
            // Générer un nouveau token de session unique
            $nouveauToken = bin2hex(random_bytes(32));
            
            $requete = $db->prepare("
                UPDATE users 
                SET session_token = :token 
                WHERE id = :userId
            ");
            
            $requete->execute([
                'token' => $nouveauToken,
                'userId' => $userId
            ]);
            
            // Si c'est l'utilisateur actuellement connecté, détruire sa session aussi
            if (isset($_SESSION['user']['id']) && $_SESSION['user']['id'] == $userId) {
                self::detruireSession();
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur lors de la destruction des sessions : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 🔒 SÉCURITÉ : Valide le token de session de l'utilisateur
     * Vérifie que le token stocké en session correspond au token en BDD
     * 
     * @return bool True si valide, False sinon
     */
    public static function validerTokenSession() {
        if (!self::estConnecte()) {
            return false;
        }
        
        $userId = $_SESSION['user']['id'] ?? null;
        $sessionToken = $_SESSION['session_token'] ?? null;
        
        if (!$userId || !$sessionToken) {
            return false;
        }
        
        try {
            $db = BaseDeDonnees::obtenirConnexion();
            
            $requete = $db->prepare("
                SELECT session_token 
                FROM users 
                WHERE id = :userId
            ");
            
            $requete->execute(['userId' => $userId]);
            $user = $requete->fetch();
            
            if (!$user || $user['session_token'] !== $sessionToken) {
                // Token invalide : déconnecter l'utilisateur
                self::detruireSession();
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur validation token session : " . $e->getMessage());
            return false;
        }
    }
}
