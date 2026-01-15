<?php
/**
 * Partiel : Navigation principale
 * 
 * Ce fichier génère le menu de navigation avec :
 * - Les liens principaux (Accueil, Galerie, À propos)
 * - Le bouton de changement de thème
 * - Le lien de connexion (si non connecté)
 * - Le menu utilisateur avec avatar (si connecté)
 */

// Récupérer la page active depuis le layout
$pageActive = $PAGE_COURANTE ?? '';

/**
 * Déterminer si un lien est actif
 */
if (!function_exists('lienActif')) {
    function lienActif($cle, $pageActive) {
        return $cle === $pageActive ? ' aria-current="page"' : '';
    }
}

// Calculer le préfixe d'URL pour les liens
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeURL = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
$prefixeURLSafe = htmlspecialchars($prefixeURL, ENT_QUOTES, 'UTF-8');
?>
<nav id="menu-site" class="navigation-site" aria-label="Navigation principale">
  <ul class="liste-navigation-site">
    <!-- Liens principaux -->
    <li>
      <a class="lien-navigation-site" href="<?= $prefixeURLSafe ?>accueil" <?= lienActif('accueil', $pageActive) ?>>
        Accueil
      </a>
    </li>
    <li>
      <a class="lien-navigation-site" href="<?= $prefixeURLSafe ?>galerie" <?= lienActif('galerie', $pageActive) ?>>
        Galerie
      </a>
    </li>
    <li>
      <a class="lien-navigation-site" href="<?= $prefixeURLSafe ?>ajout_vehicule" <?= lienActif('ajout_vehicule', $pageActive) ?>>
        Ajouter
      </a>
    </li>
    <li>
      <a class="lien-navigation-site" href="<?= $prefixeURLSafe ?>estimation" <?= lienActif('estimation', $pageActive) ?>>
        Estimation
      </a>
    </li>
    <li>
      <a class="lien-navigation-site" href="<?= $prefixeURLSafe ?>apropos" <?= lienActif('apropos', $pageActive) ?>>
        À propos
      </a>
    </li>
    <li>
      <a class="lien-navigation-site" href="<?= $prefixeURLSafe ?>contact" <?= lienActif('contact', $pageActive) ?>>
        Contact
      </a>
    </li>
    <li>
      <a class="lien-navigation-site" href="<?= $prefixeURLSafe ?>faq" <?= lienActif('faq', $pageActive) ?>>
        FAQ
      </a>
    </li>
    
    <!-- Bouton de changement de thème -->
    <li class="theme-switch-wrapper">
      <button type="button" id="theme-toggle" class="theme-switch" aria-label="Changer le thème">
        <span class="theme-switch__slider">
          <i class="fas fa-sun theme-switch__sun" aria-hidden="true"></i>
          <i class="fas fa-moon theme-switch__moon" aria-hidden="true"></i>
        </span>
      </button>
    </li>
    
    <?php if (isset($_SESSION['user']['id'])): ?>
      <!-- Bouton Messagerie (hors du menu dropdown) -->
      <li class="nav-messagerie">
        <a href="<?= $prefixeURLSafe ?>messagerie" class="lien-navigation-site lien-messagerie" <?= lienActif('messagerie', $pageActive) ?> aria-label="Messagerie">
          <i class="fas fa-envelope" aria-hidden="true"></i>
          <span class="lien-messagerie__texte">Messagerie</span>
          <span id="badge-msg-nav" class="badge-notification" hidden>0</span>
        </a>
      </li>
      
      <!-- Utilisateur connecté -->
      <li class="menu-utilisateur">
        <button id="bouton-menu-utilisateur" class="bouton-menu-utilisateur" aria-expanded="false" aria-haspopup="true" aria-label="Menu utilisateur">
          <?php
          $avatarPath = $_SESSION['user']['avatar_path'] ?? null;
          $prenom = $_SESSION['user']['first_name'] ?? 'Utilisateur';
          $avatarDefaut = 'assets/images/avatar-default.svg';
          ?>
          <img src="<?= htmlspecialchars($avatarPath ?: $avatarDefaut, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="avatar">
          <i class="fas fa-chevron-down avatar-chevron" aria-hidden="true"></i>
        </button>
        
        <!-- Menu déroulant -->
        <ul id="menu-utilisateur" class="menu-deroulant-utilisateur" role="menu" hidden>
          <li class="menu-utilisateur__header">
            <img src="<?= htmlspecialchars($avatarPath ?: $avatarDefaut, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="menu-utilisateur__avatar">
            <div class="menu-utilisateur__info">
              <span class="menu-utilisateur__nom"><?= htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') ?></span>
              <span class="menu-utilisateur__email"><?= htmlspecialchars($_SESSION['user']['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          </li>
          
          <li><hr class="menu-utilisateur__divider"></li>
          
          <li role="none">
            <a href="<?= $prefixeURLSafe ?>mes-annonces" class="element-menu-utilisateur" role="menuitem">
              <i class="fas fa-car" aria-hidden="true"></i>
              Mes annonces
            </a>
          </li>
          <li role="none">
            <a href="<?= $prefixeURLSafe ?>favoris" class="element-menu-utilisateur" role="menuitem">
              <i class="fas fa-heart" aria-hidden="true"></i>
              Favoris
            </a>
          </li>
          <li role="none">
            <a href="<?= $prefixeURLSafe ?>parametres" class="element-menu-utilisateur" role="menuitem">
              <i class="fas fa-cog" aria-hidden="true"></i>
              Paramètres
            </a>
          </li>
          
          <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
          <li><hr class="menu-utilisateur__divider"></li>
          <li role="none">
            <a href="<?= $prefixeURLSafe ?>admin" class="element-menu-utilisateur" role="menuitem">
              <i class="fas fa-shield-alt" aria-hidden="true"></i>
              Administration
            </a>
          </li>
          <?php endif; ?>
          
          <li><hr class="menu-utilisateur__divider"></li>
          <li role="none">
            <button id="bouton-deconnexion" class="element-menu-utilisateur element-menu-utilisateur--danger" role="menuitem">
              <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
              Déconnexion
            </button>
          </li>
        </ul>
      </li>
    <?php else: ?>
      <!-- Utilisateur non connecté -->
      <li>
        <a class="lien-navigation-site lien-connexion" href="<?= $prefixeURLSafe ?>connexion" <?= lienActif('connexion', $pageActive) ?>>
          <i class="fas fa-user" aria-hidden="true"></i>
          Connexion
        </a>
      </li>
    <?php endif; ?>
  </ul>
</nav>
