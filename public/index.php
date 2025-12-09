<?php
/**
 * Routeur central de l'application
 * 
 * Ce fichier analyse l'URI demandée et charge la vue correspondante
 * en passant par le layout principal (views/layouts/main.php)
 */

// Chargement automatique des classes (Autoload)
require_once __DIR__ . '/../app/autochargement.php';

// Application des en-têtes de sécurité globaux
Securite::ajouterEnTetes();

// Démarrer la session pour gérer l'authentification
// Utilisation du GestionnaireSession pour gérer le timeout et la sécurité
GestionnaireSession::demarrerSession();

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
    
    // Si l'URI commence par /public, on le retire aussi (cas où on accède via /public/)
    if (strpos($uriDemandee, '/public') === 0) {
        $uriDemandee = substr($uriDemandee, 7);
    }
}

// Normaliser l'URI : retirer le slash final ou utiliser '/' par défaut
$uriDemandee = rtrim($uriDemandee, '/') ?: '/';

// ============================================
// Routage API
// ============================================
if (strpos($uriDemandee, '/api/') === 0) {
    $apiRoutes = [
        '/api/messagerie' => __DIR__ . '/../app/Messagerie/ControleurMessagerie.php',
        '/api/profil' => __DIR__ . '/../app/Authentification/Profil/ControleurProfil.php',
        '/api/connexion' => __DIR__ . '/../app/Authentification/Connexion/ControleurConnexion.php',
        '/api/inscription' => __DIR__ . '/../app/Authentification/Inscription/ControleurInscription.php',
        '/api/contact' => __DIR__ . '/../app/Contact/ControleurContact.php',
        '/api/favoris' => __DIR__ . '/../app/Favoris/ControleurFavoris.php',
        '/api/estimation' => __DIR__ . '/../app/Estimation/ControleurEstimation.php',
        '/api/vehicule/ajout' => __DIR__ . '/../app/Vehicule/Ajout/ControleurAjout.php',
        '/api/vehicule/details' => __DIR__ . '/../app/Vehicule/Details/ControleurDetails.php',
        '/api/vehicule/galerie' => __DIR__ . '/../app/Vehicule/Galerie/ControleurGalerie.php',
        '/api/auth/reset-password' => __DIR__ . '/../app/Authentification/MotDePasseOublie/ControleurMotDePasseOublie.php',
    ];

    if (isset($apiRoutes[$uriDemandee])) {
        if (file_exists($apiRoutes[$uriDemandee])) {
            require_once $apiRoutes[$uriDemandee];
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Controller file not found']);
            exit;
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API Route not found']);
        exit;
    }
}

// ============================================
// Définition des routes (Vues)
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
        'view' => 'accueil.php', 
        'title' => 'Accueil', 
        'current' => 'accueil'
    ],
    '/accueil' => [
        'view' => 'accueil.php', 
        'title' => 'Accueil', 
        'current' => 'accueil'
    ],
    '/galerie' => [
        'view' => 'galerie.php', 
        'title' => 'Galerie', 
        'current' => 'galerie'
    ],
    '/ajout_vehicule' => [
        'view' => 'ajout_vehicule.php', 
        'title' => 'Ajouter un véhicule', 
        'current' => 'ajout_vehicule'
    ],
    '/estimation' => [
        'view' => 'estimation.php', 
        'title' => 'Estimation Prix', 
        'current' => 'estimation'
    ],
    '/apropos' => [
        'view' => 'apropos.php', 
        'title' => 'À propos', 
        'current' => 'apropos'
    ],
    '/connexion' => [
        'view' => 'connexion.php', 
        'title' => 'Connexion', 
        'current' => 'connexion'
    ],
    '/parametres' => [
        'view' => 'parametres.php', 
        'title' => 'Paramètres', 
        'current' => 'parametres'
    ],
    '/contact' => [
        'view' => 'contact.php',
        'title' => 'Contact',
        'current' => 'contact'
    ],
    '/favoris' => [
        'view' => 'favoris.php',
        'title' => 'Mes Favoris',
        'current' => 'favoris'
    ],
    '/vehicule' => [
        'view' => 'details.php',
        'title' => 'Détails du véhicule',
        'current' => 'galerie'
    ],
    '/messagerie' => [
        'view' => 'messagerie.php',
        'title' => 'Messagerie Sécurisée',
        'current' => 'messagerie'
    ],
    '/equipe' => [
        'view' => 'equipe.php',
        'title' => 'Notre équipe',
        'current' => 'equipe'
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
include __DIR__ . '/../views/layouts/principal.php';
