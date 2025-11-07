<?php
// Routeur central
session_start();

// Parser l'URI
$uri = strtok($_SERVER['REQUEST_URI'], '?');
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Extraire le chemin de base
if (strpos($script, '/public/') !== false) {
    $base = substr($script, 0, strpos($script, '/public/'));
    if (strpos($uri, $base) === 0) {
        $uri = substr($uri, strlen($base));
    }
}

$uri = rtrim($uri, '/') ?: '/';

// Routes
$routes = [
    '/' => ['view' => 'home.php', 'title' => 'Accueil', 'current' => 'home'],
    '/home' => ['view' => 'home.php', 'title' => 'Accueil', 'current' => 'home'],
    '/galerie' => ['view' => 'gallery.php', 'title' => 'Galerie', 'current' => 'galerie'],
    '/apropos' => ['view' => 'about.php', 'title' => 'À propos', 'current' => 'apropos'],
    '/connexion' => ['view' => 'auth.php', 'title' => 'Connexion', 'current' => 'connexion'],
    '/parametres' => ['view' => 'settings.php', 'title' => 'Paramètres', 'current' => 'parametres'],
];

// Charger la route
if (isset($routes[$uri])) {
    $route = $routes[$uri];
    $view = __DIR__ . '/../views/pages/' . $route['view'];
    $title = $route['title'];
    $current = $route['current'];
} else {
    http_response_code(404);
    $view = __DIR__ . '/../views/pages/404.php';
    $title = 'Page introuvable';
    $current = '';
}

include __DIR__ . '/../views/layouts/main.php';
