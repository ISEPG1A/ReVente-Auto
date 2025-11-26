<!-- 
Page des favoris - Liste des véhicules enregistrés par l'utilisateur
-->
<section id="favoris" class="section section--liste">
  <div class="conteneur">
    <div class="entete-section" style="margin-bottom: 30px;">
      <h2 class="titre-section" style="color: var(--couleur-principale);">Mes Favoris</h2>
      <p class="sous-titre-section" style="color: var(--texte-attenue);">Retrouvez ici tous les véhicules que vous avez sauvegardés.</p>
    </div>

    <ul id="liste-favoris" class="cartes-horizontales" aria-live="polite" aria-busy="false"></ul>
    
    <div id="favoris-vide" class="vide" hidden style="text-align: center; padding: 40px; background: var(--surface-2); border-radius: var(--rayon-bordure);">
      <i class="far fa-heart fa-3x" style="color: var(--texte-attenue); margin-bottom: 20px;"></i>
      <p>Vous n'avez aucun véhicule en favoris pour le moment.</p>
      <a href="galerie" class="bouton" style="margin-top: 20px; display: inline-block;">Parcourir la galerie</a>
    </div>
  </div>
</section>

<script type="module">
      import { VueFavoris } from './assets/js/Favoris/VueFavoris.js';

  const vue = new VueFavoris();
  vue.initialiser();
</script>
