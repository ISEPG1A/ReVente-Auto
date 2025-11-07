<?php
$CURRENT_PAGE = $CURRENT_PAGE ?? '';
function nav_active($key, $current) {
  return $key === $current ? ' aria-current="page"' : '';
}

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefix = strpos($scriptName, '/public/') !== false 
    ? substr($scriptName, 0, strpos($scriptName, '/public/')) . '/' 
    : '/';
?>
<nav id="site-menu" class="site-nav" aria-label="Navigation principale">
  <ul class="site-nav__list">
  <li><a class="site-nav__link" href="<?= $prefix ?>home"<?= nav_active('home', $CURRENT_PAGE) ?>>Accueil</a></li>
  <li><a class="site-nav__link" href="<?= $prefix ?>galerie"<?= nav_active('galerie', $CURRENT_PAGE) ?>>Galerie</a></li>
  <li><a class="site-nav__link" href="<?= $prefix ?>apropos"<?= nav_active('apropos', $CURRENT_PAGE) ?>>À propos</a></li>
    <li class="site-nav__spacer" aria-hidden="true"></li>
    <?php if (!empty($_SESSION['user'])): ?>
      <?php $avatar = $_SESSION['user']['avatar_path'] ?? null; ?>
      <li class="user-menu">
        <button class="user-menu__btn" id="user-menu-btn" aria-haspopup="true" aria-expanded="false" aria-label="Menu utilisateur">
          <?php if ($avatar): ?>
            <img class="avatar" src="<?= htmlspecialchars($avatar) ?>" alt="Profil">
          <?php else: ?>
            <span class="avatar avatar--placeholder" aria-hidden="true">👤</span>
          <?php endif; ?>
        </button>
        <div class="user-menu__menu" id="user-menu" role="menu" hidden>
          <a class="user-menu__item" role="menuitem" href="<?= $prefix ?>parametres">Paramètres</a>
          <button class="user-menu__item" role="menuitem" id="logout-btn" type="button">Déconnexion</button>
        </div>
      </li>
    <?php else: ?>
  <li><a class="site-nav__link site-nav__link--primary" href="<?= $prefix ?>connexion"<?= nav_active('connexion', $CURRENT_PAGE) ?>>Connexion</a></li>
    <?php endif; ?>
  </ul>
</nav>
