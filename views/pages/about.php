<?php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefix = strpos($scriptName, '/public/') !== false 
    ? substr($scriptName, 0, strpos($scriptName, '/public/')) . '/' 
    : '/';
?>
<section class="section section--about">
  <div class="container">
    <h2>À propos</h2>
    <p><strong>ReVente-Auto</strong> est une application web de gestion de véhicules d'occasion.</p>
    
    <h3>Technologies utilisées</h3>
    <ul class="list">
      <li><strong>PHP 8.2</strong> - Backend et API REST</li>
      <li><strong>MySQL 8.0</strong> - Base de données</li>
      <li><strong>JavaScript ES6+</strong> - Interactivité côté client</li>
      <li><strong>CSS3</strong> - Design responsive</li>
    </ul>
    
    <h3>Fonctionnalités</h3>
    <ul class="list">
      <li>Authentification utilisateur (inscription, connexion, gestion de profil)</li>
      <li>Galerie de véhicules avec recherche et tri</li>
      <li>Ajout et suppression d'annonces</li>
      <li>Upload de photos de profil</li>
    </ul>
    
    <p style="margin-top:20px"><a class="button" href="<?= $prefix ?>galerie">Voir la galerie</a></p>
  </div>
</section>
