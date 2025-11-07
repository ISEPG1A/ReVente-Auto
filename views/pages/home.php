<?php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefix = strpos($scriptName, '/public/') !== false 
    ? substr($scriptName, 0, strpos($scriptName, '/public/')) . '/' 
    : '/';
?>
<section class="section section--list">
  <div class="container">
    <h2>Bienvenue 👋</h2>
    <p>Application de gestion et vente de véhicules d'occasion.</p>
    <ul class="list">
      <li>Consultez la <strong>galerie</strong> pour voir les véhicules disponibles</li>
      <li>Créez un compte pour ajouter vos propres annonces</li>
    </ul>
    <div class="actions" style="margin-top:16px">
      <a class="button" href="<?= $prefix ?>galerie">Voir la galerie</a>
      <a class="button button--ghost" href="<?= $prefix ?>apropos">À propos</a>
    </div>
  </div>
</section>
