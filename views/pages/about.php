<?php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
if (strpos($scriptName, '/public/') !== false) {
    $prefix = substr($scriptName, 0, strpos($scriptName, '/public/')) . '/';
} else {
    $prefix = '/';
}
?>
<section class="section section--about">
  <div class="container">
    <h2>À propos de cette démo</h2>
    <ul class="list">
      <li>HTML s'occupe de la structure et de l'accessibilité.</li>
      <li>CSS gère toute la présentation (aucun style inline).</li>
      <li>JavaScript enrichit l'interface (fetch API, validation, rendu dynamique).</li>
      <li>PHP expose une API REST minimale et valide les données côté serveur.</li>
      <li>MySQL stocke les données de façon pérenne (voir <code>database/schema.sql</code>).</li>
    </ul>
    <p>Backend: <code>./api/api.php</code> — Base de données: <code>ultra_app</code>, table <code>vehicles</code>.</p>
  <p>La page <a href="<?= $prefix ?>galerie">Galerie</a> liste et permet d'ajouter des véhicules.</p>
  </div>
</section>
