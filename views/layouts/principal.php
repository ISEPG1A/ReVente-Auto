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

// Si le script est dans /public/, extraire le chemin de base SANS /public/
if (strpos($nomScript, '/public/') !== false) {
    // Extraire la racine de l'application (sans /public/)
    $racineApp = substr($nomScript, 0, strpos($nomScript, '/public/'));
    $cheminBase = $racineApp . '/';
    $cheminBaseAPI = $racineApp . '/api';
} else {
    $cheminBase = '/';
    $cheminBaseAPI = '/api';
}
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
  
  <!-- 🔒 SÉCURITÉ : Token CSRF pour protection des requêtes -->
  <?= AideCSRF::baliseMetaDonnees() ?>
  
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
<link rel="stylesheet" href="assets/css/style.css?v=<?= Utilitaires::versionAsset('assets/css/style.css') ?>">

  <!-- Script de gestion du thème (pour éviter le flash) -->
  <script>
    (function() {
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
      }
    })();
  </script>
</head>
<body<?php if (isset($_SESSION['user']['id'])): ?> data-user-id="<?= $_SESSION['user']['id'] ?>"<?php endif; ?>>
  <!-- En-tête du site avec navigation -->
  <header class="entete-site" role="banner">
    <div class="conteneur">
      <div class="barre-entete-site">
        <a href="accueil" class="logo-entete-site" aria-label="ReVente-Auto - Accueil">
          <img src="assets/images/logo/LogoVoitureNoir.png" alt="ReVente-Auto" class="logo-image logo-clair">
          <img src="assets/images/logo/LogoVoitureBlanc.png" alt="ReVente-Auto" class="logo-image logo-sombre">
        </a>
        
        <!-- Bouton menu mobile -->
        <button class="bascule-nav" aria-controls="menu-site" aria-expanded="false" aria-label="Menu">
          <span class="barre-bascule-nav"></span>
          <span class="barre-bascule-nav"></span>
          <span class="barre-bascule-nav"></span>
        </button>
        
        <!-- Inclusion de la navigation -->
        <?php 
        $PAGE_COURANTE = $pageActive; 
        include __DIR__ . '/../partials/navigation.php'; 
        ?>
      </div>
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

  <!-- ═══════════════════════════════════════════════════════════════════════
       SCRIPTS JAVASCRIPT (avec cache busting via paramètre de version)
       ═══════════════════════════════════════════════════════════════════════ -->
  
  <!-- 🔒 SÉCURITÉ : Protection CSRF (doit être chargé EN PREMIER, avant toute requête AJAX) -->
  <script src="assets/js/modules/commun/protection-csrf.js?v=<?= Utilitaires::versionAsset('assets/js/modules/commun/protection-csrf.js') ?>"></script>
  
  <!-- Navigation et fonctions globales de l'application -->
  <script type="module" src="assets/js/navigation.js?v=<?= Utilitaires::versionAsset('assets/js/navigation.js') ?>"></script>
  <script type="module" src="assets/js/application.js?v=<?= Utilitaires::versionAsset('assets/js/application.js') ?>"></script>
  
  <!-- Script de gestion de l'inactivité (uniquement si utilisateur connecté) -->
  <?php if (GestionnaireSession::estConnecte()): ?>
  <script src="assets/js/GestionnaireInactivite.js?v=<?= Utilitaires::versionAsset('assets/js/GestionnaireInactivite.js') ?>"></script>
  <?php endif; ?>
  
  <!-- Script du switch de thème (inline pour fonctionner sur toutes les pages) -->
  <script>
    (function() {
      const btnTheme = document.getElementById('theme-toggle');
      const html = document.documentElement;
      
      if (btnTheme) {
        btnTheme.addEventListener('click', function() {
          const currentTheme = html.getAttribute('data-theme');
          if (currentTheme === 'dark') {
            html.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
          } else {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
          }
        });
      }
    })();
  </script>
</body>
</html>
