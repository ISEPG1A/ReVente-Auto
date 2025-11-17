<?php
/**
 * Partiel : Navigation principale
 * 
 * Ce fichier génère le menu de navigation avec :
 * - Les liens principaux (Accueil, Galerie, À propos)
 * - Le lien de connexion (si non connecté)
 * - Le menu utilisateur avec avatar (si connecté)
 */

// Récupérer la page active depuis le layout
$PAGE_ACTIVE = $CURRENT_PAGE ?? '';

/**
 * Déterminer si un lien est actif
 * 
 * @param string $cle Identifiant de la page
 * @param string $pageActive Page actuellement active
 * @return string Attribut HTML aria-current si actif, chaîne vide sinon
 */
function lienActif($cle, $pageActive) {
  return $cle === $pageActive ? ' aria-current="page"' : '';
}

// Calculer le préfixe d'URL pour les liens
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeURL = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>
<nav id="site-menu" class="site-nav" aria-label="Navigation principale">
  <ul class="site-nav__list">
    <!-- Liens principaux -->
    <li>
      <a class="site-nav__link" 
         href="<?= $prefixeURL ?>home"
         <?= lienActif('home', $PAGE_ACTIVE) ?>>
        Accueil
      </a>
    </li>
    <li>
      <a class="site-nav__link" 
         href="<?= $prefixeURL ?>galerie"
         <?= lienActif('galerie', $PAGE_ACTIVE) ?>>
        Galerie
      </a>
    </li>
    <li>
      <a class="site-nav__link" 
         href="<?= $prefixeURL ?>apropos"
         <?= lienActif('apropos', $PAGE_ACTIVE) ?>>
        À propos
      </a>
    </li>
    <li>
      <a class="site-nav__link" 
         href="<?= $prefixeURL ?>contact"
         <?= lienActif('contact', $PAGE_ACTIVE) ?>>
        Contact
      </a>
    </li>
    
    <!-- Espaceur pour pousser les éléments suivants à droite -->
    <li class="site-nav__spacer" aria-hidden="true"></li>
    
    <?php if (!empty($_SESSION['user'])): ?>
      <!-- Menu utilisateur (si connecté) -->
      <?php $cheminAvatar = $_SESSION['user']['avatar_path'] ?? null; ?>
      <li class="user-menu">
        <button class="user-menu__btn" 
                id="user-menu-btn" 
                aria-haspopup="true" 
                aria-expanded="false" 
                aria-label="Menu utilisateur">
          <?php if ($cheminAvatar): ?>
            <!-- Avatar personnalisé -->
            <img class="avatar" 
                 src="<?= htmlspecialchars($cheminAvatar) ?>" 
                 alt="Profil">
          <?php else: ?>
            <!-- Avatar par défaut (émoji) -->
            <span class="avatar avatar--placeholder" aria-hidden="true">👤</span>
          <?php endif; ?>
        </button>
        
        <!-- Menu déroulant -->
        <div class="user-menu__menu" id="user-menu" role="menu" hidden>
          <a class="user-menu__item" 
             role="menuitem" 
             href="<?= $prefixeURL ?>parametres">
            Paramètres
          </a>
          <button class="user-menu__item" 
                  role="menuitem" 
                  id="logout-btn" 
                  type="button">
            Déconnexion
          </button>
        </div>
      </li>
    <?php else: ?>
      <!-- Bouton de connexion (si non connecté) -->
      <li>
        <a class="site-nav__link site-nav__link--primary" 
           href="<?= $prefixeURL ?>connexion"
           <?= lienActif('connexion', $PAGE_ACTIVE) ?>>
          Connexion
        </a>
      </li>
    <?php endif; ?>
  </ul>
</nav>
