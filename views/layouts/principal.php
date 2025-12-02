<?php
/**
 * Layout principal de l'application
 * 
 * Ce fichier génère la structure HTML commune à toutes les pages :
 * - En-tête (<head>) avec métadonnées, liens CSS et polices
 * - En-tête du site (<header>) avec navigation
 * - Contenu principal (<main>) où la vue spécifique est incluse
 * - Pied de page (<footer>)
 * - Scripts JavaScript
 */

// Démarrer la session si elle n'est pas déjà active via le Gestionnaire
if (session_status() === PHP_SESSION_NONE) {
    GestionnaireSession::demarrerSession();
}

// Variables passées par le routeur (avec valeurs par défaut)
$titrePage = $title ?? 'ReVente-Auto';           // Titre de la page (balise <title>)
$pageActive = $current ?? '';                     // Identifiant de la page active
$cheminVue = $view ?? null;                       // Chemin de la vue à inclure

// ============================================
// Calcul du chemin de base de l'application
// ============================================

$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Si le script est dans /public/, extraire le chemin de base
if (strpos($nomScript, '/public/') !== false) {
    $cheminBase = substr($nomScript, 0, strpos($nomScript, '/public/')) . '/public/';
} else {
    $cheminBase = '/';
}

// Chemin de base pour l'API (utilisé par JavaScript)
$cheminBaseAPI = rtrim(dirname($cheminBase), '/') . '/api';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Application de vente de véhicules d'occasion">
  
  <!-- Balise <base> pour les chemins relatifs -->
  <base href="<?= htmlspecialchars($cheminBase) ?>">
  
  <!-- Chemin de base de l'API (pour JavaScript) -->
  <meta name="api-base" content="<?= htmlspecialchars($cheminBaseAPI) ?>">
  
  <title><?= htmlspecialchars($titrePage) ?></title>
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  
  <!-- Polices Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Feuille de style principale -->
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body>
  <!-- En-tête du site avec navigation -->
  <header class="entete-site" role="banner">
    <div class="conteneur">
      <div class="barre-entete-site">
        <h1 class="titre-entete-site">
          ReVente-Auto 
          <span class="sous-titre-entete-site">Véhicules d'occasion</span>
        </h1>
        
        <!-- Bouton menu mobile -->
        <button class="bascule-nav" aria-controls="menu-site" aria-expanded="false" aria-label="Menu">
          <span class="barre-bascule-nav"></span>
          <span class="barre-bascule-nav"></span>
          <span class="barre-bascule-nav"></span>
        </button>
      </div>
      
      <!-- Inclusion de la navigation -->
      <?php 
      $PAGE_COURANTE = $pageActive; 
      include __DIR__ . '/../partials/navigation.php'; 
      ?>
    </div>
  </header>

  <!-- Contenu principal de la page -->
  <main id="contenu" class="contenu-principal" role="main">
    <?php 
    // Inclure la vue spécifique si elle existe
    if ($cheminVue && file_exists($cheminVue)) {
        include $cheminVue; 
    }
    ?>
  </main>

  <!-- Pied de page -->
  <?php include __DIR__ . '/../partials/pied_de_page.php'; ?>

  <!-- Scripts JavaScript (avec cache busting) -->
  <script type="module" src="assets/js/nav.js?v=<?= time() ?>"></script>
  <script type="module" src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
