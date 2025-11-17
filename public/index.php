<?php
/**
 * Routeur central de l'application
 * 
 * Ce fichier analyse l'URI demandée et charge la vue correspondante
 * en passant par le layout principal (views/layouts/main.php)
 */

// Démarrer la session pour gérer l'authentification
session_start();

// ============================================
// Analyse de l'URI
// ============================================

// Récupérer l'URI sans les paramètres GET
$uriDemandee = strtok($_SERVER['REQUEST_URI'], '?');
$cheminScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Extraire le chemin de base de l'application
// Si le script est dans /public/, on retire cette partie de l'URI
if (strpos($cheminScript, '/public/') !== false) {
    $cheminBase = substr($cheminScript, 0, strpos($cheminScript, '/public/'));
    if (strpos($uriDemandee, $cheminBase) === 0) {
        $uriDemandee = substr($uriDemandee, strlen($cheminBase));
    }
}

// Normaliser l'URI : retirer le slash final ou utiliser '/' par défaut
$uriDemandee = rtrim($uriDemandee, '/') ?: '/';

// ============================================
// Définition des routes
// ============================================

/**
 * Table de routage : URI => Configuration de la page
 * 
 * Chaque route contient :
 * - view : le fichier de vue à charger (dans views/pages/)
 * - title : le titre de la page (balise <title>)
 * - current : identifiant de la page active (pour la navigation)
 */
$tableRoutage = [
    '/' => [
        'view' => 'home.php', 
        'title' => 'Accueil', 
        'current' => 'home'
    ],
    '/home' => [
        'view' => 'home.php', 
        'title' => 'Accueil', 
        'current' => 'home'
    ],
    '/galerie' => [
        'view' => 'gallery.php', 
        'title' => 'Galerie', 
        'current' => 'galerie'
    ],
    '/apropos' => [
        'view' => 'about.php', 
        'title' => 'À propos', 
        'current' => 'apropos'
    ],
    '/connexion' => [
        'view' => 'auth.php', 
        'title' => 'Connexion', 
        'current' => 'connexion'
    ],
    '/parametres' => [
        'view' => 'settings.php', 
        'title' => 'Paramètres', 
        'current' => 'parametres'
    ],
    '/contact' => [
        'view' => 'contact.php',
        'title' => 'Contact',
        'current' => 'contact'
    ],
];

// ============================================
// Résolution de la route
// ============================================

// Vérifier si la route existe dans la table de routage
if (isset($tableRoutage[$uriDemandee])) {
    // Route trouvée : charger la configuration
    $configurationRoute = $tableRoutage[$uriDemandee];
    $cheminVue = __DIR__ . '/../views/pages/' . $configurationRoute['view'];
    $titrePage = $configurationRoute['title'];
    $pageActive = $configurationRoute['current'];
} else {
    // Route introuvable : afficher la page 404
    http_response_code(404);
    $cheminVue = __DIR__ . '/../views/pages/404.php';
    $titrePage = 'Page introuvable';
    $pageActive = '';
}

// ============================================
// Rendu de la page
// ============================================

// Passer les variables au layout principal
$view = $cheminVue;      // Chemin de la vue à inclure
$title = $titrePage;     // Titre de la page
$current = $pageActive;  // Page active pour la navigation

// Inclure le layout principal qui va afficher la vue
include __DIR__ . '/../views/layouts/main.php';
