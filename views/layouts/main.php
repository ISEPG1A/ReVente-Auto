<?php
// Layout principal
if (session_status() === PHP_SESSION_NONE) session_start();
$title = $title ?? 'ReVente-Auto';
$current = $current ?? '';
$view = $view ?? null;

// Calcul du chemin de base
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
if (strpos($scriptName, '/public/') !== false) {
    $basePath = substr($scriptName, 0, strpos($scriptName, '/public/')) . '/public/';
} else {
    $basePath = '/';
}
$apiBase = rtrim(dirname($basePath), '/') . '/api';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Application de vente de véhicules d'occasion">
  <base href="<?= htmlspecialchars($basePath) ?>">
  <meta name="api-base" content="<?= htmlspecialchars($apiBase) ?>">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header" role="banner">
    <div class="container">
      <div class="site-header__bar">
        <h1 class="site-header__title">ReVente-Auto <span class="site-header__subtitle">Véhicules d'occasion</span></h1>
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
    <?php if ($view && file_exists($view)) include $view; ?>
  </main>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <script type="module" src="assets/js/nav.js?v=<?= time() ?>"></script>
  <script type="module" src="assets/js/app.js?v=<?= time() ?>"></script>
  <script type="module" src="assets/js/auth.js?v=<?= time() ?>"></script>
</body>
</html>
