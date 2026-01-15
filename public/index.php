<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ROUTEUR CENTRAL DE L'APPLICATION
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Architecture MVC : Requête → Routeur → Contrôleur → (Modèle) → Vue
 * 
 * Ce fichier :
 * 1. Analyse l'URI demandée
 * 2. Détermine le contrôleur approprié
 * 3. Instancie et exécute le contrôleur
 * 4. Le contrôleur gère le modèle et retourne la vue
 */

// ============================================
// INITIALISATION
// ============================================

// Chargement automatique des classes (Autoload)
require_once __DIR__ . '/../app/autochargement.php';

// Application des en-têtes de sécurité globaux
Securite::ajouterEnTetes();

// Démarrer la session
GestionnaireSession::demarrerSession();

// ============================================
// ANALYSE DE L'URI
// ============================================

// Récupérer l'URI sans les paramètres GET
$uriDemandee = strtok($_SERVER['REQUEST_URI'], '?');
$cheminScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Initialiser le chemin de base
$cheminBase = '/';

// Extraire le chemin de base de l'application (enlever /public/index.php)
if (strpos($cheminScript, '/public/') !== false) {
    $cheminBase = substr($cheminScript, 0, strpos($cheminScript, '/public/'));
    if (strpos($uriDemandee, $cheminBase) === 0) {
        $uriDemandee = substr($uriDemandee, strlen($cheminBase));
    }
}

// Normaliser l'URI (enlever les slashes multiples)
$uriDemandee = rtrim($uriDemandee, '/') ?: '/';

// ============================================
// GESTION DES FICHIERS .PHP INEXISTANTS
// ============================================
// Si l'URI se termine par .php et que ce n'est pas index.php,
// vérifier si le fichier existe, sinon afficher 404 personnalisé
if (preg_match('/\.php$/i', $uriDemandee) && $uriDemandee !== '/index.php') {
    // Extraire le nom du fichier demandé
    $fichierDemande = __DIR__ . $uriDemandee;
    
    // Si le fichier n'existe pas, afficher la page 404 personnalisée
    if (!file_exists($fichierDemande)) {
        http_response_code(404);
        $controleur = new ControleurErreur();
        $controleur->index();
        exit;
    }
}

// ============================================
// ROUTAGE API (retourne JSON)
// ============================================

if (strpos($uriDemandee, '/api/') !== false || strpos($uriDemandee, 'api/') === 0) {
    // Normaliser l'URI API
    $uriApi = $uriDemandee;
    if (strpos($uriApi, 'api/') === 0 && strpos($uriApi, '/api/') !== 0) {
        $uriApi = '/' . $uriApi;
    }
    
    // Table de routage API → Contrôleurs API
    $routesApi = [
        '/api/messagerie'           => 'ControleurMessagerie',
        '/api/profil'               => 'ControleurProfil',
        '/api/connexion'            => 'ControleurConnexion',
        '/api/inscription'          => 'ControleurInscription',
        '/api/contact'              => 'ControleurContact',
        '/api/favoris'              => 'ControleurFavoris',
        '/api/estimation'           => 'ControleurEstimation',
        '/api/score-ia'             => 'ControleurScoreIA',
        '/api/localisation'         => 'ControleurLocalisation',
        '/api/cgu'                  => 'ControleurCGU',
        '/api/faq'                  => 'ControleurFAQ',
        '/api/politique-confidentialite' => 'ApiPolitiqueConfidentialite',
        '/api/vehicule/ajout'       => 'ControleurAjoutVehicule',
        '/api/vehicule/details'     => 'ControleurDetailsVehicule',
        '/api/vehicule/galerie'     => 'ControleurGalerieVehicule',
        '/api/vehicule/modification'=> 'ControleurModificationVehicule',
        '/api/auth/reset-password'  => 'ControleurMotDePasseOublie',
        '/api/mes-annonces'         => 'ControleurMesAnnonces',
        '/api/mes-annonces/statut'  => 'ControleurMesAnnonces',
        '/api/admin'                => 'ControleurAdmin',
    ];
    
    // Support pour routes dynamiques /api/vehicule/{id}
    if (!isset($routesApi[$uriApi]) && preg_match('#^/api/vehicule/(\d+)$#', $uriApi, $matches)) {
        $_GET['id'] = $matches[1];
        $uriApi = '/api/vehicule/details';
    }
    
    // Support pour routes dynamiques /api/cgu/* (articles, sections, points, versions)
    if (!isset($routesApi[$uriApi]) && preg_match('#^/api/cgu/(articles|sections|points|versions)#', $uriApi)) {
        $uriApi = '/api/cgu';
    }
    
    // Support pour routes dynamiques /api/faq/* 
    if (!isset($routesApi[$uriApi]) && preg_match('#^/api/faq/\d+#', $uriApi)) {
        $uriApi = '/api/faq';
    }
    
    // Support pour routes dynamiques /api/politique-confidentialite/* 
    if (!isset($routesApi[$uriApi]) && preg_match('#^/api/politique-confidentialite/\d+#', $uriApi)) {
        $uriApi = '/api/politique-confidentialite';
    }

    if (isset($routesApi[$uriApi])) {
        $nomControleur = $routesApi[$uriApi];
        $cheminControleur = __DIR__ . '/../app/Controleurs/Api/' . $nomControleur . '.php';
        
        if (file_exists($cheminControleur)) {
            require_once $cheminControleur;
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Contrôleur API introuvable: ' . $nomControleur]);
            exit;
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Route API introuvable', 'uri' => $uriApi]);
        exit;
    }
}

// ============================================
// ROUTAGE PAGES (retourne HTML via Contrôleurs)
// ============================================

/**
 * Table de routage Pages → Contrôleurs de pages
 * 
 * Chaque route pointe vers une classe de contrôleur
 * qui hérite de ControleurBase et implémente index()
 */
$routesPages = [
    '/'                     => 'ControleurAccueil',
    '/accueil'              => 'ControleurAccueil',
    '/galerie'              => 'ControleurGalerie',
    '/vehicule'             => 'ControleurDetailsPage',
    '/ajout_vehicule'       => 'ControleurAjoutPage',
    '/modification_vehicule'=> 'ControleurModificationPage',
    '/connexion'            => 'ControleurConnexionPage',
    '/parametres'           => 'ControleurParametres',
    '/favoris'              => 'ControleurFavorisPage',
    '/mes-annonces'         => 'ControleurMesAnnoncesPage',
    '/messagerie'           => 'ControleurMessageriePage',
    '/estimation'           => 'ControleurEstimationPage',
    '/contact'              => 'ControleurContactPage',
    '/apropos'              => 'ControleurApropos',
    '/faq'                  => 'ControleurFaq',
    '/cgu'                  => 'ControleurCgu',
    '/politique-confidentialite' => 'ControleurPolitiqueConfidentialite',
    '/mentions-legales'     => 'ControleurMentionsLegales',
    '/equipe'               => 'ControleurEquipe',
    '/admin'                => 'ControleurAdminPage',
];

// ============================================
// RÉSOLUTION ET EXÉCUTION DU CONTRÔLEUR
// ============================================

if (isset($routesPages[$uriDemandee])) {
    // Route trouvée → Instancier et exécuter le contrôleur
    $nomControleur = $routesPages[$uriDemandee];
    
    // Vérifier que la classe existe (autoload la chargera)
    if (class_exists($nomControleur)) {
        $controleur = new $nomControleur();
        $controleur->index();
    } else {
        // Classe non trouvée
        http_response_code(500);
        echo "Erreur: Contrôleur '$nomControleur' introuvable.";
    }
} else {
    // Route introuvable → Afficher page 404
    $controleur = new ControleurErreur();
    $controleur->index();
}
