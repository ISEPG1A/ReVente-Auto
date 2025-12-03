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

    /**
     * Démarre la session de manière sécurisée et vérifie l'inactivité
     */
    public static function demarrerSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuration sécurisée des cookies de session
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            // ini_set('session.cookie_secure', 1); // À activer si HTTPS est disponible
            
            session_start();
        }

        self::verifierInactivite();
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
                    Utils::envoyerJSON(['erreur' => 'Session expirée', 'redirect' => '/connexion'], 401);
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
}
