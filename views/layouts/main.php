<?php
// Layout principal
// Variables attendues: $title (string), $current (home|galerie|apropos|connexion), $view (chemin de la vue)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$title = $title ?? 'Ultra App';
$current = $current ?? '';
$view = $view ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="Ultra App — Démo propre HTML/CSS/JS/PHP + MySQL" />
  <?php
    // Calcul du préfixe base pour assets et navigation
    // Détection automatique du chemin de l'application
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    // Extraire le chemin jusqu'à /public (ex: /test/ReVente-Auto)
    if (strpos($scriptName, '/public/') !== false) {
        // Base doit pointer vers le dossier public pour les assets
        $basePath = substr($scriptName, 0, strpos($scriptName, '/public/')) . '/public/';
    } else {
        $basePath = '/';
    }
    $apiBase = rtrim(dirname($basePath), '/') . '/api';
  ?>
  <base href="<?= htmlspecialchars($basePath) ?>">
  <meta name="api-base" content="<?= htmlspecialchars($apiBase) ?>" />
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- CSS global depuis le dossier assets (chemin relatif pour compatibilité sous-dossier) -->
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
  <header class="site-header" role="banner">
    <div class="container">
      <div class="site-header__bar">
        <h1 class="site-header__title">Ultra App <span class="site-header__subtitle">Catalogue véhicules</span></h1>
        <button class="nav-toggle" aria-controls="site-menu" aria-expanded="false" aria-label="Menu">
          <span class="nav-toggle__bar"></span>
          <span class="nav-toggle__bar"></span>
          <span class="nav-toggle__bar"></span>
        </button>
      </div>
      <?php $CURRENT_PAGE = $current; include __DIR__ . '/../partials/nav.php'; ?>
    </div>
  </header>

  <main id="contenu" class="site-main" role="main">
    <?php if ($view && file_exists($view)) { include $view; } ?>
  </main>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <noscript>
    <div class="container">
      Certaines fonctionnalités (galerie, menu mobile) nécessitent JavaScript.
    </div>
  </noscript>

  <script type="module" src="assets/js/nav.js?v=<?= time() ?>"></script>
  <script type="module" src="assets/js/app.js?v=<?= time() ?>"></script>
  <script type="module" src="assets/js/auth.js?v=<?= time() ?>"></script>
</body>
</html>
