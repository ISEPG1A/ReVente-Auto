<!-- 
Page de détails d'un véhicule
Design moderne avec hero, galerie et informations complètes
-->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<!-- Hero Section avec image principale -->
<section class="details-hero">
  <div class="details-hero__shapes">
    <div class="details-shape details-shape--1"></div>
    <div class="details-shape details-shape--2"></div>
  </div>
  
  <div class="conteneur">
    <a href="galerie" class="details-retour">
      <i class="fas fa-arrow-left"></i>
      <span>Retour à la galerie</span>
    </a>
  </div>
</section>

<!-- Contenu Principal -->
<section class="details-main">
  <div class="conteneur">
    
    <!-- Chargement -->
    <div id="chargement-details" class="details-loading">
      <div class="details-loading__spinner">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
      <p>Chargement des détails...</p>
    </div>

    <!-- Erreur -->
    <div id="erreur-details" class="details-error" hidden>
      <i class="fas fa-exclamation-triangle"></i>
      <p>Une erreur est survenue</p>
    </div>

    <!-- Contenu -->
    <div id="contenu-details" class="details-content" hidden>
      
      <!-- Layout principal -->
      <div class="details-layout">
        
        <!-- Colonne gauche : Galerie -->
        <div class="details-galerie">
          
          <!-- Image principale -->
          <div class="details-galerie__main">
            <div class="conteneur-image-principale">
              <div class="image-placeholder-lg">
                <i class="fas fa-car"></i>
              </div>
            </div>
            
            <!-- Badge favoris -->
            <button class="details-galerie__favori" id="btn-favori-detail">
              <i class="far fa-heart"></i>
            </button>
            
            <!-- Badges sur l'image -->
            <div class="details-galerie__badges">
              <span class="details-badge details-badge--year" id="badge-annee">
                <i class="fas fa-calendar-alt"></i> --
              </span>
            </div>
          </div>
          
          <!-- Miniatures -->
          <div class="details-galerie__thumbs">
            <div class="rangee-miniatures">
              <div class="miniature active"><i class="fas fa-car"></i></div>
              <div class="miniature"><i class="fas fa-image"></i></div>
              <div class="miniature"><i class="fas fa-image"></i></div>
            </div>
          </div>
          
        </div>
        
        <!-- Colonne droite : Informations -->
        <div class="details-info">
          
          <!-- Carte principale -->
          <div class="details-card details-card--main">
            <div class="details-card__header">
              <div class="details-card__titles">
                <h1 id="titre-detail" class="details-card__title">Chargement...</h1>
                <p id="sous-titre-detail" class="details-card__subtitle">...</p>
              </div>
              <div id="prix-detail" class="details-card__price">-- €</div>
            </div>
            
            <!-- Tags rapides -->
            <div class="details-tags">
              <div class="details-tag" id="tag-annee">
                <i class="fas fa-calendar-alt"></i>
                <span>--</span>
              </div>
              <div class="details-tag" id="tag-km">
                <i class="fas fa-tachometer-alt"></i>
                <span>-- km</span>
              </div>
              <div class="details-tag" id="tag-carburant">
                <i class="fas fa-gas-pump"></i>
                <span>--</span>
              </div>
              <div class="details-tag" id="tag-boite">
                <i class="fas fa-cog"></i>
                <span>--</span>
              </div>
            </div>
          </div>
          
          <!-- Carte Vendeur -->
          <div class="details-card details-card--seller">
            <h3 class="details-card__section-title">
              <i class="fas fa-user-tie"></i> Vendeur
            </h3>
            
            <div class="details-seller">
              <div class="details-seller__avatar">
                <i class="fas fa-user"></i>
              </div>
              <div class="details-seller__info">
                <h4 id="nom-vendeur">Vendeur</h4>
                <span class="details-seller__badge">
                  <i class="fas fa-check-circle"></i> Particulier vérifié
                </span>
              </div>
            </div>
            
            <div class="details-seller__actions">
              <button id="bouton-contact" class="details-btn details-btn--primary">
                <i class="fas fa-envelope"></i>
                <span>Envoyer un message</span>
              </button>
              <button id="bouton-telephone" class="details-btn details-btn--secondary">
                <i class="fas fa-phone"></i>
                <span>Voir le numéro</span>
              </button>
            </div>
          </div>
          
          <!-- Carte Localisation -->
          <div class="details-card details-card--location">
            <h3 class="details-card__section-title">
              <i class="fas fa-map-marker-alt"></i> Localisation
            </h3>
            <p id="localisation-detail" class="details-location__address">
              <i class="fas fa-location-dot"></i> France
            </p>
            <div id="map" class="details-map"></div>
          </div>
          
        </div>
        
      </div>
      
      <!-- Section Description et Caractéristiques -->
      <div class="details-bottom">
        
        <!-- Description -->
        <div class="details-card details-card--description">
          <h2 class="details-section-title">
            <i class="fas fa-align-left"></i> Description
          </h2>
          <div class="details-description__content">
            <p id="description-detail">
              Aucune description fournie pour ce véhicule.
            </p>
          </div>
        </div>
        
        <!-- Caractéristiques -->
        <div class="details-card details-card--specs">
          <h2 class="details-section-title">
            <i class="fas fa-list-check"></i> Caractéristiques
          </h2>
          
          <div class="details-specs">
            <div class="details-spec">
              <div class="details-spec__icon">
                <i class="fas fa-car"></i>
              </div>
              <div class="details-spec__content">
                <span class="details-spec__label">Marque</span>
                <span class="details-spec__value" id="spec-marque">--</span>
              </div>
            </div>
            
            <div class="details-spec">
              <div class="details-spec__icon">
                <i class="fas fa-car-side"></i>
              </div>
              <div class="details-spec__content">
                <span class="details-spec__label">Modèle</span>
                <span class="details-spec__value" id="spec-modele">--</span>
              </div>
            </div>
            
            <div class="details-spec">
              <div class="details-spec__icon">
                <i class="fas fa-calendar"></i>
              </div>
              <div class="details-spec__content">
                <span class="details-spec__label">Année modèle</span>
                <span class="details-spec__value" id="spec-annee">--</span>
              </div>
            </div>
            
            <div class="details-spec">
              <div class="details-spec__icon">
                <i class="fas fa-calendar-check"></i>
              </div>
              <div class="details-spec__content">
                <span class="details-spec__label">Mise en circulation</span>
                <span class="details-spec__value" id="spec-date">--</span>
              </div>
            </div>
            
            <div class="details-spec">
              <div class="details-spec__icon">
                <i class="fas fa-road"></i>
              </div>
              <div class="details-spec__content">
                <span class="details-spec__label">Kilométrage</span>
                <span class="details-spec__value" id="spec-km">--</span>
              </div>
            </div>
            
            <div class="details-spec">
              <div class="details-spec__icon">
                <i class="fas fa-gas-pump"></i>
              </div>
              <div class="details-spec__content">
                <span class="details-spec__label">Carburant</span>
                <span class="details-spec__value" id="spec-carburant">--</span>
              </div>
            </div>
            
            <div class="details-spec">
              <div class="details-spec__icon">
                <i class="fas fa-gears"></i>
              </div>
              <div class="details-spec__content">
                <span class="details-spec__label">Boîte de vitesse</span>
                <span class="details-spec__value" id="spec-boite">--</span>
              </div>
            </div>
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
