<?php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
if (strpos($scriptName, '/public/') !== false) {
    $prefix = substr($scriptName, 0, strpos($scriptName, '/public/')) . '/';
} else {
    $prefix = '/';
}
?>
<section class="section">
  <div class="container">
    <h2>Page introuvable (404)</h2>
    <p>
      Désolé, la page que vous cherchez n'existe pas, est inaccessible ou interdite.
      Vous pouvez revenir à l'accueil ou explorer la galerie.
    </p>
    <p>
      <a class="button" href="<?= $prefix ?>home">Aller à l'accueil</a>
      <a class="button button--ghost" href="<?= $prefix ?>galerie">Voir la galerie</a>
    </p>
  </div>
</section>
