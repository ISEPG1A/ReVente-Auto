<?php
/**
 * Routeur central de l'application ReVente-Auto
 * Gère toutes les routes et charge les vues appropriées
 */

// Démarrer la session
session_start();

// Fonction helper pour inclure une vue avec un layout
function render_view(string $view, string $title, string $current = '') {
    $CURRENT_PAGE = $current;
    include __DIR__ . '/../views/layouts/main.php';
}

// Récupérer l'URI demandée et nettoyer
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = dirname($_SERVER['SCRIPT_NAME']);

// Retirer le chemin de base si l'app n'est pas à la racine
if ($script_name !== '/' && $script_name !== '\\') {
    $request_uri = substr($request_uri, strlen($script_name));
}

// Retirer les paramètres GET de l'URI
$request_uri = strtok($request_uri, '?');

// Retirer le slash final s'il existe
$request_uri = rtrim($request_uri, '/');

// Si vide, c'est la racine
if ($request_uri === '') {
    $request_uri = '/';
}

// Définition des routes
$routes = [
    '/' => [
        'view' => __DIR__ . '/../views/pages/home.php',
        'title' => 'Accueil',
        'current' => 'home'
    ],
    '/home' => [
        'view' => __DIR__ . '/../views/pages/home.php',
        'title' => 'Accueil',
        'current' => 'home'
    ],
    '/galerie' => [
        'view' => __DIR__ . '/../views/pages/gallery.php',
        'title' => 'Galerie de véhicules',
        'current' => 'galerie'
    ],
    '/apropos' => [
        'view' => __DIR__ . '/../views/pages/about.php',
        'title' => 'À propos',
        'current' => 'apropos'
    ],
    '/connexion' => [
        'view' => __DIR__ . '/../views/pages/auth.php',
        'title' => 'Connexion / Inscription',
        'current' => 'connexion'
    ],
    '/parametres' => [
        'view' => __DIR__ . '/../views/pages/settings.php',
        'title' => 'Paramètres',
        'current' => 'parametres'
    ],
    // Route d'accès à Adminer (outil d'administration DB) protégée par auth basique dans adminer.php
    '/adminer' => [
        'view' => __DIR__ . '/adminer.php',
        'title' => 'Adminer',
        'current' => 'adminer'
    ],
    // Page générant un lien pré-rempli vers le service Adminer externe
    '/adminer-link' => [
        'view' => __DIR__ . '/adminer-link.php',
        'title' => 'Lien Adminer',
        'current' => 'adminer'
    ],
];

// Chercher la route correspondante
if (isset($routes[$request_uri])) {
    $route = $routes[$request_uri];
    $view = $route['view'];
    $title = $route['title'];
    $current = $route['current'];
    
    // Inclure le layout qui chargera la vue
    include __DIR__ . '/../views/layouts/main.php';
    exit;
}

// Si aucune route ne correspond, afficher la page 404
http_response_code(404);
$title = 'Page introuvable';
$current = '';
$view = __DIR__ . '/../views/pages/404.php';
include __DIR__ . '/../views/layouts/main.php';
exit;
