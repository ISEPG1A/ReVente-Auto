<?php
// Page "À propos" - Présentation de l'application ReVente-Auto
// Cette page décrit les fonctionnalités et technologies utilisées dans l'application

// Récupération et normalisation du nom du script actuel
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Calcul du préfixe d'URL pour la navigation
// Gestion du cas où l'application est dans un sous-dossier /public/
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>
<!-- Section principale de la page À propos -->
<section class="section section--about">
  <div class="container">
    <h2>À propos</h2>
    
    <!-- Description de l'application -->
    <p><strong>ReVente-Auto</strong> est une application web de gestion de véhicules d'occasion.</p>
    
    <!-- Section des technologies utilisées -->
    <h3>Technologies utilisées</h3>
    <ul class="list">
      <li><strong>PHP 8.2</strong> - Backend et API REST</li>
      <li><strong>MySQL 8.0</strong> - Base de données</li>
      <li><strong>JavaScript ES6+</strong> - Interactivité côté client</li>
      <li><strong>CSS3</strong> - Design responsive</li>
    </ul>
    
    <!-- Section des fonctionnalités -->
    <h3>Fonctionnalités</h3>
    <ul class="list">
      <li>Authentification utilisateur (inscription, connexion, gestion de profil)</li>
      <li>Galerie de véhicules avec recherche et tri</li>
      <li>Ajout et suppression d'annonces</li>
      <li>Upload de photos de profil</li>
    </ul>
    
    <!-- Lien d'action vers la galerie -->
    <p style="margin-top:20px">
      <a class="button" href="<?= $prefixeUrl ?>galerie">Voir la galerie</a>
    </p>
  </div>
</section>
