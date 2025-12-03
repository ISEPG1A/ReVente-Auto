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
$pageActive = $PAGE_COURANTE ?? '';

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
<nav id="menu-site" class="navigation-site" aria-label="Navigation principale">
  <ul class="liste-navigation-site">
    <!-- Liens principaux -->
    <li>
      <a class="lien-navigation-site" 
         href="<?= $prefixeURL ?>accueil"
         <?= lienActif('accueil', $pageActive) ?>>
        Accueil
      </a>
    </li>
    <li>
      <a class="lien-navigation-site" 
         href="<?= $prefixeURL ?>galerie"
         <?= lienActif('galerie', $pageActive) ?>>
        Galerie
      </a>
    </li>
    <li>
      <a class="lien-navigation-site" 
         href="<?= $prefixeURL ?>ajout_vehicule"
         <?= lienActif('ajout_vehicule', $pageActive) ?>>
        Ajouter
      </a>
    </li>
    <li>
      <a class="lien-navigation-site" 
         href="<?= $prefixeURL ?>estimation"
         <?= lienActif('estimation', $pageActive) ?>>
        Estimation
      </a>
    </li>
    <li>
      <a class="lien-navigation-site" 
         href="<?= $prefixeURL ?>apropos"
         <?= lienActif('apropos', $pageActive) ?>>
        À propos
      </a>
    </li>
    <li>
      <a class="lien-navigation-site" 
         href="<?= $prefixeURL ?>contact"
         <?= lienActif('contact', $pageActive) ?>>
        Contact
      </a>
    </li>
    
    <!-- Espaceur pour pousser les éléments suivants à droite -->
    <li class="separateur-navigation-site" aria-hidden="true"></li>
    
    <?php if (!empty($_SESSION['user'])): ?>
      <!-- Lien Messagerie -->
      <li>
        <a class="lien-navigation-site" 
           href="<?= $prefixeURL ?>messagerie"
           <?= lienActif('messagerie', $pageActive) ?>
           style="display: flex; align-items: center; gap: 5px; position: relative;">
          <i class="fas fa-envelope"></i> Messagerie
          <span id="badge-msg-nav" class="badge-notification" hidden>0</span>
        </a>
      </li>

      <!-- Switch Thème Animé -->
      <li style="display: flex; align-items: center;">
        <button id="theme-toggle" class="theme-switch" aria-label="Changer de thème">
          <span class="theme-switch__clouds">
            <span class="theme-switch__cloud"></span>
            <span class="theme-switch__cloud"></span>
          </span>
          <span class="theme-switch__stars">
            <span class="theme-switch__star"></span>
            <span class="theme-switch__star"></span>
            <span class="theme-switch__star"></span>
          </span>
          <span class="theme-switch__slider">
            <i class="fas fa-sun theme-switch__sun"></i>
            <i class="fas fa-moon theme-switch__moon"></i>
          </span>
        </button>
      </li>

      <!-- Menu utilisateur (si connecté) -->
      <?php $cheminAvatar = $_SESSION['user']['avatar_path'] ?? null; ?>
      <?php $prenomUtilisateur = $_SESSION['user']['first_name'] ?? 'Utilisateur'; ?>
      <?php $emailUtilisateur = $_SESSION['user']['email'] ?? ''; ?>
      <li class="menu-utilisateur">
        <button class="bouton-menu-utilisateur" 
                id="bouton-menu-utilisateur" 
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
          <i class="fas fa-chevron-down avatar-chevron"></i>
        </button>
        
        <!-- Menu déroulant amélioré -->
        <div class="menu-deroulant-utilisateur" id="menu-utilisateur" role="menu" hidden>
          <!-- En-tête du menu avec infos utilisateur -->
          <div class="menu-utilisateur__header">
            <?php if ($cheminAvatar): ?>
              <img class="menu-utilisateur__avatar" src="<?= htmlspecialchars($cheminAvatar) ?>" alt="">
            <?php else: ?>
              <span class="menu-utilisateur__avatar menu-utilisateur__avatar--placeholder">👤</span>
            <?php endif; ?>
            <div class="menu-utilisateur__info">
              <span class="menu-utilisateur__nom"><?= htmlspecialchars($prenomUtilisateur) ?></span>
              <span class="menu-utilisateur__email"><?= htmlspecialchars($emailUtilisateur) ?></span>
            </div>
          </div>
          
          <div class="menu-utilisateur__divider"></div>
          
          <!-- Liens du menu -->
          <a class="element-menu-utilisateur" role="menuitem" href="<?= $prefixeURL ?>favoris">
            <i class="fas fa-heart"></i>
            <span>Mes Favoris</span>
          </a>
          <a class="element-menu-utilisateur" role="menuitem" href="<?= $prefixeURL ?>parametres">
            <i class="fas fa-cog"></i>
            <span>Paramètres</span>
          </a>
          
          <div class="menu-utilisateur__divider"></div>
          
          <button class="element-menu-utilisateur element-menu-utilisateur--danger" 
                  role="menuitem" 
                  id="bouton-deconnexion" 
                  type="button">
            <i class="fas fa-sign-out-alt"></i>
            <span>Déconnexion</span>
          </button>
        </div>
      </li>
    <?php else: ?>
      <!-- Switch Thème Animé -->
      <li style="display: flex; align-items: center;">
        <button id="theme-toggle" class="theme-switch" aria-label="Changer de thème">
          <span class="theme-switch__clouds">
            <span class="theme-switch__cloud"></span>
            <span class="theme-switch__cloud"></span>
          </span>
          <span class="theme-switch__stars">
            <span class="theme-switch__star"></span>
            <span class="theme-switch__star"></span>
            <span class="theme-switch__star"></span>
          </span>
          <span class="theme-switch__slider">
            <i class="fas fa-sun theme-switch__sun"></i>
            <i class="fas fa-moon theme-switch__moon"></i>
          </span>
        </button>
      </li>

      <!-- Bouton de connexion (si non connecté) -->
      <li>
        <a class="lien-navigation-site lien-navigation-site--principal" 
           href="<?= $prefixeURL ?>connexion"
           <?= lienActif('connexion', $pageActive) ?>>
          Connexion
        </a>
      </li>
    <?php endif; ?>
  </ul>
</nav>
