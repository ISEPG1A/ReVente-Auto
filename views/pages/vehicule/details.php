<!-- 
Page de détails d'un véhicule
Design moderne avec hero, galerie et informations complètes
Adapté selon le type de véhicule (voiture, moto, camion)
-->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<link rel="stylesheet" href="./assets/css/composants/suppression.css?v=<?= time() ?>">

<!-- Hero Section avec navigation -->
<section class="hero hero--compact">
  <div class="hero__background">
    <div class="hero__shapes">
      <div class="hero__shape hero__shape--1"></div>
      <div class="hero__shape hero__shape--2"></div>
    </div>
  </div>
  
  <div class="conteneur">
    <div class="hero__content">
      <a href="galerie" class="bouton bouton--fantome">
        <i class="fas fa-arrow-left"></i>
        <span>Retour à la galerie</span>
      </a>
    </div>
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
      
      <!-- En-tête avec titre et prix -->
      <div class="details-header">
        <!-- Ligne du haut : Titre + Prix -->
        <div class="details-header__top">
          <div class="details-header__content">
            <h1 id="titre-detail" class="details-header__title">Chargement...</h1>
            <p id="sous-titre-detail" class="details-header__subtitle">...</p>
          </div>
          <div class="details-header__price-box">
            <div id="prix-detail" class="details-header__price">-- €</div>
          </div>
        </div>
        <!-- Ligne du bas : Badges + Favori -->
        <div class="details-header__actions">
          <span class="details-badge details-badge--type" id="badge-type-header">
            <i class="fas fa-tag"></i> <span>--</span>
          </span>
          <span class="details-badge details-badge--etat" id="badge-etat-header">
            <i class="fas fa-star"></i> <span>--</span>
          </span>
          <button class="details-header__favori" id="btn-favori-detail">
            <i class="far fa-heart"></i>
            <span>Sauvegarder</span>
          </button>
        </div>
      </div>

      <!-- Layout en 2 colonnes -->
      <div class="details-grid">
        
        <!-- Colonne principale (gauche) -->
        <div class="details-primary">
          
          <!-- Galerie d'images -->
          <div class="details-card details-card--gallery">
            <div class="details-gallery">
              <div class="details-gallery__main">
                <div class="conteneur-image-principale">
                  <div class="image-placeholder-lg">
                    <i class="fas fa-car" id="icon-type-vehicule"></i>
                  </div>
                </div>
              </div>
              
              <div class="details-gallery__thumbs">
                <div class="rangee-miniatures">
                  <div class="miniature active"><i class="fas fa-car"></i></div>
                  <div class="miniature"><i class="fas fa-image"></i></div>
                  <div class="miniature"><i class="fas fa-image"></i></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Informations essentielles -->
          <div class="details-card details-card--quick-info">
            <div class="details-quick-grid">
              <div class="details-quick-item">
                <i class="fas fa-calendar-alt"></i>
                <div>
                  <span class="details-quick-label">Année</span>
                  <span class="details-quick-value" id="quick-annee">--</span>
                </div>
              </div>
              <div class="details-quick-item">
                <i class="fas fa-tachometer-alt"></i>
                <div>
                  <span class="details-quick-label">Kilométrage</span>
                  <span class="details-quick-value" id="quick-km">--</span>
                </div>
              </div>
              <div class="details-quick-item">
                <i class="fas fa-gas-pump"></i>
                <div>
                  <span class="details-quick-label">Carburant</span>
                  <span class="details-quick-value" id="quick-carburant">--</span>
                </div>
              </div>
              <div class="details-quick-item" id="quick-boite-container" data-type="voiture,camion">
                <i class="fas fa-cog"></i>
                <div>
                  <span class="details-quick-label">Boîte</span>
                  <span class="details-quick-value" id="quick-boite">--</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div class="details-card details-card--description">
            <h2 class="details-section-title">
              <i class="fas fa-align-left"></i> Description
            </h2>
            <div class="details-description">
              <p id="description-detail">Aucune description fournie pour ce véhicule.</p>
            </div>
          </div>

          <!-- Caractéristiques en grille continue -->
          <!-- Caractéristiques organisées en sections thématiques -->
          <div class="details-specs-container">
            
            <!-- ============ SECTION : INFORMATIONS GÉNÉRALES ============ -->
            <div class="details-specs-section">
              <h3 class="details-specs-section__title">
                <i class="fas fa-info-circle"></i>
                <span>Informations générales</span>
              </h3>
              <div class="details-specs-grid">
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-tag"></i> Marque</span>
                  <span class="details-spec__value" id="spec-marque">--</span>
                </div>
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-car-side"></i> Modèle</span>
                  <span class="details-spec__value" id="spec-modele">--</span>
                </div>
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-calendar"></i> Année</span>
                  <span class="details-spec__value" id="spec-annee">--</span>
                </div>
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-road"></i> Kilométrage</span>
                  <span class="details-spec__value" id="spec-km">--</span>
                </div>
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-star"></i> État</span>
                  <span class="details-spec__value" id="spec-etat">--</span>
                </div>
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-palette"></i> Couleur</span>
                  <span class="details-spec__value" id="spec-couleur">--</span>
                </div>
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-globe"></i> Provenance</span>
                  <span class="details-spec__value" id="spec-provenance">--</span>
                </div>
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-clipboard-check"></i> Contrôle technique</span>
                  <span class="details-spec__value" id="spec-controle-technique">--</span>
                </div>
              </div>
            </div>
            
            <!-- ============ SECTION : MOTORISATION ============ -->
            <div class="details-specs-section">
              <h3 class="details-specs-section__title">
                <i class="fas fa-engine"></i>
                <span>Motorisation</span>
              </h3>
              <div class="details-specs-grid">
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-gas-pump"></i> Carburant</span>
                  <span class="details-spec__value" id="spec-carburant">--</span>
                </div>
                <div class="details-spec" id="spec-boite-container" data-type="voiture,camion">
                  <span class="details-spec__label"><i class="fas fa-gears"></i> Boîte de vitesse</span>
                  <span class="details-spec__value" id="spec-boite">--</span>
                </div>
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-bolt"></i> Puissance</span>
                  <span class="details-spec__value" id="spec-puissance">--</span>
                </div>
                <div class="details-spec" id="spec-type-hybride-container" style="display: none;">
                  <span class="details-spec__label"><i class="fas fa-leaf"></i> Type hybride</span>
                  <span class="details-spec__value" id="spec-type-hybride">--</span>
                </div>
              </div>
            </div>
            
            <!-- ============ SECTION : HABITABILITÉ ============ -->
            <div class="details-specs-section" id="section-habitabilite">
              <h3 class="details-specs-section__title">
                <i class="fas fa-couch"></i>
                <span>Habitabilité</span>
              </h3>
              <div class="details-specs-grid">
                <div class="details-spec" id="spec-portes-container" data-type="voiture">
                  <span class="details-spec__label"><i class="fas fa-door-open"></i> Nombre de portes</span>
                  <span class="details-spec__value" id="spec-portes">--</span>
                </div>
                <div class="details-spec" id="spec-places-container" data-type="voiture,camion">
                  <span class="details-spec__label"><i class="fas fa-user-friends"></i> Nombre de places</span>
                  <span class="details-spec__value" id="spec-places">--</span>
                </div>
                <div class="details-spec" id="spec-coffre-container" data-type="voiture,camion">
                  <span class="details-spec__label"><i class="fas fa-box"></i> <span id="label-coffre">Volume coffre</span></span>
                  <span class="details-spec__value" id="spec-coffre">--</span>
                </div>
              </div>
            </div>
            
            <!-- ============ SECTION : DIMENSIONS ============ -->
            <div class="details-specs-section" id="section-dimensions">
              <h3 class="details-specs-section__title">
                <i class="fas fa-ruler-combined"></i>
                <span>Dimensions</span>
              </h3>
              <div class="details-specs-grid">
                <div class="details-spec" id="spec-longueur-container">
                  <span class="details-spec__label"><i class="fas fa-arrows-left-right"></i> Longueur</span>
                  <span class="details-spec__value" id="spec-longueur">--</span>
                </div>
                <div class="details-spec" id="spec-largeur-container">
                  <span class="details-spec__label"><i class="fas fa-arrows-left-right-to-line"></i> Largeur</span>
                  <span class="details-spec__value" id="spec-largeur">--</span>
                </div>
                <div class="details-spec" id="spec-hauteur-container">
                  <span class="details-spec__label"><i class="fas fa-ruler-vertical"></i> Hauteur</span>
                  <span class="details-spec__value" id="spec-hauteur">--</span>
                </div>
              </div>
            </div>
            
            <!-- ============ SECTION : ENVIRONNEMENT & CONSOMMATION ============ -->
            <div class="details-specs-section">
              <h3 class="details-specs-section__title">
                <i class="fas fa-leaf"></i>
                <span>Environnement & Consommation</span>
              </h3>
              <div class="details-specs-grid">
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-wind"></i> Vignette Crit'Air</span>
                  <span class="details-spec__value" id="spec-crit-air">--</span>
                </div>
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-euro-sign"></i> Norme Euro</span>
                  <span class="details-spec__value" id="spec-norme-euro">--</span>
                </div>
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-smog"></i> Émissions CO2</span>
                  <span class="details-spec__value" id="spec-emission-co2">--</span>
                </div>
                <div class="details-spec">
                  <span class="details-spec__label"><i class="fas fa-gauge-high"></i> <span id="label-consommation">Consommation mixte</span></span>
                  <span class="details-spec__value" id="spec-consommation">--</span>
                </div>
                <div class="details-spec" id="spec-conso-secondaire-container" style="display: none;">
                  <span class="details-spec__label"><i class="fas fa-charging-station"></i> Consommation électrique</span>
                  <span class="details-spec__value" id="spec-consommation-secondaire">--</span>
                </div>
                <div class="details-spec" id="spec-autonomie-container" style="display: none;">
                  <span class="details-spec__label"><i class="fas fa-battery-full"></i> <span id="label-autonomie">Autonomie</span></span>
                  <span class="details-spec__value" id="spec-autonomie">--</span>
                </div>
              </div>
            </div>

          </div>
          <!-- Fin des caractéristiques -->

        </div>
        <!-- Fin colonne principale -->
        
        <!-- Colonne secondaire (sidebar droite) -->
        <div class="details-sidebar">
          
          <!-- Vendeur -->
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

          <!-- Actions propriétaire -->
          <div id="actions-proprietaire" class="details-owner-actions" hidden>
            <div class="details-owner-actions__card">
              <h4 class="details-owner-actions__title">
                <i class="fas fa-cog"></i> Actions propriétaire
              </h4>
              <div class="details-owner-actions__buttons">
                <a id="lien-modifier-detail" href="#" class="details-btn details-btn--outline">
                  <i class="fas fa-edit"></i>
                  <span>Modifier l'annonce</span>
                </a>
                <button id="bouton-supprimer-detail" class="details-btn details-btn--danger">
                  <i class="fas fa-trash-alt"></i>
                  <span>Supprimer l'annonce</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Score IA -->
          <div id="score-ia-container" class="score-ia">
            <!-- Contenu chargé dynamiquement par VueScoreIA.js -->
          </div>

          <!-- Localisation -->
          <div id="localisation-container">
            <!-- Contenu chargé dynamiquement par VueLocalisation.js -->
          </div>

        </div>
        <!-- Fin sidebar -->

      </div>
      <!-- Fin details-grid -->
      
    </div>
    
  </div>
</section>

<!-- Modal de confirmation de suppression -->
<div id="modal-suppression" class="modal" hidden>
    <div class="modal__overlay"></div>
    <div class="modal__content">
        <div class="modal__header">
            <h3 class="modal__title">
                <i class="fas fa-exclamation-triangle"></i> Confirmer la suppression
            </h3>
            <button class="modal__close" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal__body">
            <p>Êtes-vous sûr de vouloir supprimer cette annonce ?</p>
            <p class="modal__warning">Cette action est irréversible. Toutes les images et données associées seront également supprimées.</p>
            <div class="modal__vehicle-info">
                <strong id="modal-vehicle-name"></strong>
            </div>
        </div>
        <div class="modal__footer">
            <button class="annonces-btn annonces-btn--outline modal__cancel">
                <i class="fas fa-times"></i> Annuler
            </button>
            <button id="modal-confirm-delete" class="annonces-btn annonces-btn--danger">
                <i class="fas fa-trash"></i> Supprimer définitivement
            </button>
        </div>
    </div>
</div>

<input type="hidden" id="csrf-token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

<script type="module">
    import VueDetails from './assets/js/modules/vehicule/VueDetails.js?v=<?= time() ?>';
    
    document.addEventListener('DOMContentLoaded', () => {
        new VueDetails();
    });
</script>
