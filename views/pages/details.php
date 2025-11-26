<!-- 
Page de détails d'un véhicule
Affiche toutes les informations, photos et contact
-->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<section id="details-vehicule" class="section">
  <div class="conteneur">
    
    <a href="galerie" class="bouton bouton--fantome" style="margin-bottom: 20px; display: inline-flex; align-items: center; gap: 8px;">
      <i class="fas fa-arrow-left"></i> Retour à la galerie
    </a>

    <div id="chargement-details" style="text-align: center; padding: 50px;">
      <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--couleur-principale);"></i>
    </div>

    <div id="erreur-details" class="message message--erreur" hidden></div>

    <div id="contenu-details" class="grille-details" hidden>
      
      <div class="galerie-details">
        <div class="conteneur-image-principale">
            <div class="image-placeholder-lg">
                <i class="fas fa-car fa-5x"></i>
            </div>
        </div>
        <div class="rangee-miniatures">
            <div class="miniature active"><i class="fas fa-car"></i></div>
            <div class="miniature"><i class="fas fa-angle-right"></i></div>
            <div class="miniature"><i class="fas fa-angle-right"></i></div>
        </div>
      </div>

      <div class="barre-laterale-details">
        
        <div class="carte-info-principale">
            <h1 id="titre-detail" class="titre-detail">Chargement...</h1>
            <p id="sous-titre-detail" class="sous-titre-detail">...</p>
            <div id="prix-detail" class="prix-detail">-- €</div>
            
            <div class="tags-detail">
                <span class="tag" id="tag-annee"><i class="fas fa-calendar-alt"></i> <span>--</span></span>
                <span class="tag" id="tag-km"><i class="fas fa-tachometer-alt"></i> <span>-- km</span></span>
                <span class="tag" id="tag-carburant"><i class="fas fa-gas-pump"></i> <span>--</span></span>
                <span class="tag" id="tag-boite"><i class="fas fa-cog"></i> <span>--</span></span>
            </div>

            <div class="carte-vendeur">
                <div class="entete-vendeur">
                    <div class="avatar-vendeur">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="info-vendeur">
                        <h3 id="nom-vendeur">Vendeur</h3>
                        <p class="statut-vendeur">Particulier</p>
                    </div>
                </div>
                <button id="bouton-contact" class="bouton bouton-contact">
                    <i class="fas fa-envelope"></i> Envoyer un message
                </button>
                <button id="bouton-telephone" class="bouton bouton--fantome bouton-contact">
                    <i class="fas fa-phone"></i> Voir le numéro
                </button>
            </div>
        </div>

        <div class="carte-localisation">
            <h3><i class="fas fa-map-marker-alt"></i> Localisation</h3>
            <p id="localisation-detail">France</p>
            <div id="map" style="height: 200px; width: 100%; border-radius: 8px; margin-top: 10px; z-index: 0;"></div>
        </div>

      </div>

      <div class="description-details">
        <h2>Description</h2>
        <p id="description-detail" class="texte-description">
            Aucune description fournie pour ce véhicule.
        </p>

        <h2 style="margin-top: 20px;">Caractéristiques détaillées</h2>
        <div class="grille-specs">
            <div class="rangee-spec">
                <span class="etiquette-spec">Marque</span>
                <span class="valeur-spec" id="spec-marque">--</span>
            </div>
            <div class="rangee-spec">
                <span class="etiquette-spec">Modèle</span>
                <span class="valeur-spec" id="spec-modele">--</span>
            </div>
            <div class="rangee-spec">
                <span class="etiquette-spec">Année modèle</span>
                <span class="valeur-spec" id="spec-annee">--</span>
            </div>
            <div class="rangee-spec">
                <span class="etiquette-spec">Mise en circulation</span>
                <span class="valeur-spec" id="spec-date">--</span>
            </div>
             <div class="rangee-spec">
                <span class="etiquette-spec">Kilométrage</span>
                <span class="valeur-spec" id="spec-km">--</span>
            </div>
             <div class="rangee-spec">
                <span class="etiquette-spec">Carburant</span>
                <span class="valeur-spec" id="spec-carburant">--</span>
            </div>
             <div class="rangee-spec">
                <span class="etiquette-spec">Boîte de vitesse</span>
                <span class="valeur-spec" id="spec-boite">--</span>
            </div>
        </div>
      </div>

    </div>
  </div>
</section>



<script type="module">
    import VueDetails from './assets/js/Vehicule/Details/VueDetails.js';
    document.addEventListener('DOMContentLoaded', () => {
        new VueDetails();
    });
</script>
