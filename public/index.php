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

// Extraire le préfixe de base (chemin avant /public si présent)
$script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
if (strpos($script_name, '/public/') !== false) {
    $base_path = substr($script_name, 0, strpos($script_name, '/public/'));
} else {
    $base_path = '';
}

// Retirer le préfixe de l'URI si présent
if ($base_path !== '' && strpos($request_uri, $base_path) === 0) {
    $request_uri = substr($request_uri, strlen($base_path));
}

// Retirer les paramètres GET de l'URI
$request_uri = strtok($request_uri, '?');

// Retirer le slash final s'il existe
$request_uri = rtrim($request_uri, '/');

// Si vide, c'est la racine
if ($request_uri === '') {
    $request_uri = '/';
}

// Assurer qu'on a toujours un slash au début (sauf si déjà présent)
if ($request_uri !== '/' && $request_uri[0] !== '/') {
    $request_uri = '/' . $request_uri;
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
