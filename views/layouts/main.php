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

// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) session_start();

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
  
  <!-- Feuille de style principale -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <!-- En-tête du site avec navigation -->
  <header class="site-header" role="banner">
    <div class="container">
      <div class="site-header__bar">
        <h1 class="site-header__title">
          ReVente-Auto 
          <span class="site-header__subtitle">Véhicules d'occasion</span>
        </h1>
        
        <!-- Bouton menu mobile -->
        <button class="nav-toggle" aria-controls="site-menu" aria-expanded="false" aria-label="Menu">
          <span class="nav-toggle__bar"></span>
          <span class="nav-toggle__bar"></span>
          <span class="nav-toggle__bar"></span>
        </button>
      </div>
      
      <!-- Inclusion de la navigation -->
      <?php 
      $CURRENT_PAGE = $pageActive; 
      include __DIR__ . '/../partials/nav.php'; 
      ?>
    </div>
  </header>

  <!-- Contenu principal de la page -->
  <main id="contenu" class="site-main" role="main">
    <?php 
    // Inclure la vue spécifique si elle existe
    if ($cheminVue && file_exists($cheminVue)) {
        include $cheminVue; 
    }
    ?>
  </main>

  <!-- Pied de page -->
  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <!-- Scripts JavaScript (avec cache busting) -->
  <script type="module" src="assets/js/nav.js?v=<?= time() ?>"></script>
  <script type="module" src="assets/js/app.js?v=<?= time() ?>"></script>
  <script type="module" src="assets/js/auth.js?v=<?= time() ?>"></script>
</body>
</html>
