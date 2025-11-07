<?php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefix = strpos($scriptName, '/public/') !== false 
    ? substr($scriptName, 0, strpos($scriptName, '/public/')) . '/' 
    : '/';
?>
<section class="section">
  <div class="container">
    <h2>Page introuvable (404)</h2>
    <p>La page que vous cherchez n'existe pas.</p>
    <p>
      <a class="button" href="<?= $prefix ?>home">Retour à l'accueil</a>
      <a class="button button--ghost" href="<?= $prefix ?>galerie">Voir la galerie</a>
    </p>
  </div>
</section>
