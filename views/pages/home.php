<?php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
if (strpos($scriptName, '/public/') !== false) {
    $prefix = substr($scriptName, 0, strpos($scriptName, '/public/')) . '/';
} else {
    $prefix = '/';
}
?>
<section class="section section--list">
  <div class="container">
    <h2>Bienvenue 👋</h2>
    <p>Cette application de démonstration sépare parfaitement HTML, CSS, JavaScript, PHP et MySQL.</p>
    <ul class="list">
      <li>Consulte la <strong>galerie</strong> pour voir la liste des véhicules et en ajouter.</li>
      <li>Visite la page <strong>À propos</strong> pour comprendre l'architecture.</li>
    </ul>
    <div class="actions" style="margin-top:16px;">
      <a class="button" href="<?= $prefix ?>galerie">Voir la galerie</a>
      <a class="button button--ghost" href="<?= $prefix ?>apropos">À propos</a>
    </div>
  </div>
</section>
