<!-- 
Page d'ajout de véhicule - VERSION MULTI-ÉTAPES
Formulaire progressif avec 4 étapes : Type → Informations → Photos → Description
-->

<!-- Hero Section Minimaliste pour Multi-Steps -->
<section class="ajout-hero ajout-hero--compact">
  <div class="ajout-hero__shapes">
    <div class="ajout-shape ajout-shape--1"></div>
    <div class="ajout-shape ajout-shape--2"></div>
  </div>
  
  <div class="conteneur">
    <div class="ajout-hero__content">
      <h1 class="ajout-hero__title">
        <i class="fas fa-plus-circle"></i>
        Publier une annonce
      </h1>
      <p class="ajout-hero__subtitle">
        Complétez les étapes pour mettre votre véhicule en vente
      </p>
    </div>
  </div>
</section>

<!-- Barre de progression -->
<section class="progress-section">
  <div class="conteneur">
    <div class="stepper">
      <div class="stepper__step stepper__step--active" data-step="1">
        <div class="stepper__circle">
          <i class="fas fa-car"></i>
        </div>
        <span class="stepper__label">Type</span>
      </div>
      
      <div class="stepper__step" data-step="2">
        <div class="stepper__circle">
          <i class="fas fa-info-circle"></i>
        </div>
        <span class="stepper__label">Informations</span>
      </div>
      
      <div class="stepper__step" data-step="3">
        <div class="stepper__circle">
          <i class="fas fa-camera"></i>
        </div>
        <span class="stepper__label">Photos</span>
      </div>
      
      <div class="stepper__step" data-step="4">
        <div class="stepper__circle">
          <i class="fas fa-align-left"></i>
        </div>
        <span class="stepper__label">Description</span>
      </div>
    </div>
  </div>
</section>

<!-- Section principale -->
<section class="ajout-main">
  <div class="conteneur">
    
    <form id="formulaire-vehicule" class="ajout-form ajout-form--steps" novalidate enctype="multipart/form-data">
      
      <!-- Étape 1 : Type de véhicule -->
      <div class="form-step form-step--active" data-step="1">
        <div class="step-container">
          <div class="ajout-card ajout-card--center">
            <div class="ajout-card__icon-header">
              <div class="ajout-icon-circle">
                <i class="fas fa-car-side"></i>
              </div>
            </div>
            <h2 class="ajout-card__title ajout-card__title--center">Quel type de véhicule vendez-vous ?</h2>
            <p class="ajout-card__subtitle ajout-card__subtitle--center">Sélectionnez la catégorie correspondant à votre véhicule</p>
            
            <div class="type-selection">
              <input type="radio" id="type-voiture" name="type_vehicule" value="voiture" checked>
              <label for="type-voiture" class="type-card">
                <div class="type-card__icon">
                  <i class="fas fa-car"></i>
                </div>
                <h3 class="type-card__title">Voiture</h3>
                <p class="type-card__description">Berline, SUV, citadine...</p>
                <div class="type-card__check">
                  <i class="fas fa-check-circle"></i>
                </div>
              </label>
              
              <input type="radio" id="type-moto" name="type_vehicule" value="moto">
              <label for="type-moto" class="type-card">
                <div class="type-card__icon">
                  <i class="fas fa-motorcycle"></i>
                </div>
                <h3 class="type-card__title">Moto</h3>
                <p class="type-card__description">Sportive, roadster, custom...</p>
                <div class="type-card__check">
                  <i class="fas fa-check-circle"></i>
                </div>
              </label>
              
              <input type="radio" id="type-camion" name="type_vehicule" value="camion">
              <label for="type-camion" class="type-card">
                <div class="type-card__icon">
                  <i class="fas fa-truck"></i>
                </div>
                <h3 class="type-card__title">Camion</h3>
                <p class="type-card__description">Utilitaire, poids lourd...</p>
                <div class="type-card__check">
                  <i class="fas fa-check-circle"></i>
                </div>
              </label>
            </div>
          </div>
          
          <div class="step-actions">
            <button type="button" class="btn-step btn-step--next" data-next="2">
              Continuer
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Étape 2 : Informations du véhicule -->
      <div class="form-step" data-step="2">
        <div class="step-container">
          <div class="ajout-card">
            <div class="ajout-card__header ajout-card__header--center">
              <h2 class="ajout-card__title">Informations du véhicule</h2>
              <p class="ajout-card__subtitle">Remplissez les détails de votre véhicule</p>
            </div>
            
            <div class="ajout-fields">
              <!-- Marque et Modèle -->
              <div class="ajout-row">
                <div class="ajout-field">
                  <label class="ajout-label" for="marque">
                    <i class="fas fa-industry"></i> Marque <span class="ajout-required">*</span>
                  </label>
                  <input id="marque" name="marque" class="ajout-input" type="text" placeholder="Ex: Peugeot" required maxlength="50" />
                </div>
                
                <div class="ajout-field">
                  <label class="ajout-label" for="modele">
                    <i class="fas fa-car"></i> Modèle <span class="ajout-required">*</span>
                  </label>
                  <input id="modele" name="modele" class="ajout-input" type="text" placeholder="Ex: 208 GT Line" required maxlength="50" />
                </div>
              </div>
              
              <!-- Année et Prix -->
              <div class="ajout-row">
                <div class="ajout-field">
                  <label class="ajout-label" for="annee">
                    <i class="fas fa-calendar-alt"></i> Année <span class="ajout-required">*</span>
                  </label>
                  <input id="annee" name="annee" class="ajout-input" type="number" min="1900" max="2100" placeholder="Ex: 2020" required />
                </div>
                
                <div class="ajout-field">
                  <label class="ajout-label" for="prix">
                    <i class="fas fa-euro-sign"></i> Prix <span class="ajout-required">*</span>
                  </label>
                  <div class="ajout-input-group">
                    <input id="prix" name="prix" class="ajout-input" type="number" min="0" step="100" placeholder="Ex: 15000" required />
                    <span class="ajout-input-suffix">€</span>
                  </div>
                </div>
              </div>
              
              <!-- Kilométrage et Ville -->
              <div class="ajout-row">
                <div class="ajout-field">
                  <label class="ajout-label" for="km">
                    <i class="fas fa-tachometer-alt"></i> Kilométrage <span class="ajout-required">*</span>
                  </label>
                  <div class="ajout-input-group">
                    <input id="km" name="km" class="ajout-input" type="number" min="0" step="100" placeholder="Ex: 45000" required />
                    <span class="ajout-input-suffix">km</span>
                  </div>
                </div>
                
                <div class="ajout-field">
                  <label class="ajout-label" for="ville">
                    <i class="fas fa-map-marker-alt"></i> Ville <span class="ajout-required">*</span>
                  </label>
                  <input id="ville" name="ville" class="ajout-input" type="text" placeholder="Ex: Paris" required />
                </div>
              </div>
              
              <!-- Carburant et Boîte -->
              <div class="ajout-row">
                <div class="ajout-field">
                  <label class="ajout-label" for="carburant">
                    <i class="fas fa-gas-pump"></i> Carburant <span class="ajout-required">*</span>
                  </label>
                  <select id="carburant" name="carburant" class="ajout-select" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="Essence">🔴 Essence</option>
                    <option value="Diesel">⚫ Diesel</option>
                    <option value="Hybride">🟢 Hybride</option>
                    <option value="Électrique">🔵 Électrique</option>
                    <option value="GPL">🟡 GPL</option>
                  </select>
                </div>
                
                <div class="ajout-field" data-hide-for="moto">
                  <label class="ajout-label" for="boite">
                    <i class="fas fa-cog"></i> Boîte de vitesse <span class="ajout-required">*</span>
                  </label>
                  <select id="boite" name="boite" class="ajout-select" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="Manuelle">⚙️ Manuelle</option>
                    <option value="Automatique">🅰️ Automatique</option>
                  </select>
                </div>
              </div>
              
              <!-- État et Couleur -->
              <div class="ajout-row">
                <div class="ajout-field">
                  <label class="ajout-label" for="etat">
                    <i class="fas fa-star-half-alt"></i> État du véhicule <span class="ajout-required">*</span>
                  </label>
                  <select id="etat" name="etat" class="ajout-select" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="neuf">✨ Neuf</option>
                    <option value="bon">👍 Bon état</option>
                    <option value="moyen">👌 État moyen</option>
                    <option value="mauvais">👎 Mauvais état</option>
                  </select>
                </div>
                
                <div class="ajout-field">
                  <label class="ajout-label" for="couleur">
                    <i class="fas fa-palette"></i> Couleur <span class="ajout-required">*</span>
                  </label>
                  <input id="couleur" name="couleur" class="ajout-input" type="text" placeholder="Ex: Noir métallisé" maxlength="50" required />
                </div>
              </div>
              
              <!-- Crit'Air -->
              <div class="ajout-row">
                <div class="ajout-field">
                  <label class="ajout-label" for="crit_air">
                    <i class="fas fa-wind"></i> Vignette Crit'Air <span class="ajout-required">*</span>
                  </label>
                  <select id="crit_air" name="crit_air" class="ajout-select" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="0">🟢 Crit'Air 0 (Électrique)</option>
                    <option value="1">🟣 Crit'Air 1</option>
                    <option value="2">🟡 Crit'Air 2</option>
                    <option value="3">🟠 Crit'Air 3</option>
                    <option value="4">🟤 Crit'Air 4</option>
                    <option value="5">⚫ Crit'Air 5</option>
                  </select>
                </div>
                
                <div class="ajout-field"></div>
              </div>
              
              <!-- Nombre de portes et places -->
              <div class="ajout-row" data-hide-for="moto">
                <div class="ajout-field">
                  <label class="ajout-label" for="nb_portes">
                    <i class="fas fa-door-open"></i> Nombre de portes <span class="ajout-required">*</span>
                  </label>
                  <select id="nb_portes" name="nb_portes" class="ajout-select" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="2">2 portes</option>
                    <option value="3">3 portes</option>
                    <option value="4">4 portes</option>
                    <option value="5">5 portes</option>
                  </select>
                </div>
                
                <div class="ajout-field">
                  <label class="ajout-label" for="nb_places">
                    <i class="fas fa-users"></i> Nombre de places <span class="ajout-required">*</span>
                  </label>
                  <select id="nb_places" name="nb_places" class="ajout-select" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="2">2 places</option>
                    <option value="4">4 places</option>
                    <option value="5">5 places</option>
                    <option value="7">7 places</option>
                  </select>
                </div>
              </div>
              
              <!-- Taille coffre (voiture uniquement) -->
              <div class="ajout-row" data-show-for="voiture">
                <div class="ajout-field">
                  <label class="ajout-label" for="taille_coffre">
                    <i class="fas fa-suitcase"></i> Taille du coffre
                  </label>
                  <select id="taille_coffre" name="taille_coffre" class="ajout-select">
                    <option value="">-- Non renseigné --</option>
                    <option value="petit">🔹 Petit (< 300L)</option>
                    <option value="moyen">🔸 Moyen (300-500L)</option>
                    <option value="grand">🔶 Grand (> 500L)</option>
                  </select>
                </div>
                <div class="ajout-field"></div>
              </div>
              
              <!-- Puissance et Norme Euro -->
              <div class="ajout-row">
                <div class="ajout-field">
                  <label class="ajout-label" for="puissance_cv">
                    <i class="fas fa-horse"></i> Puissance
                  </label>
                  <div class="ajout-input-group">
                    <input id="puissance_cv" name="puissance_cv" class="ajout-input" type="number" min="0" max="2000" placeholder="Ex: 130" />
                    <span class="ajout-input-suffix">CV</span>
                  </div>
                </div>
                
                <div class="ajout-field">
                  <label class="ajout-label" for="norme_euro">
                    <i class="fas fa-leaf"></i> Norme Euro <span class="ajout-required">*</span>
                  </label>
                  <select id="norme_euro" name="norme_euro" class="ajout-select" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="Euro 4">Euro 4</option>
                    <option value="Euro 5">Euro 5</option>
                    <option value="Euro 6">Euro 6</option>
                    <option value="Euro 6d">Euro 6d</option>
                  </select>
                </div>
              </div>
              
              <!-- Consommation et Émissions CO2 -->
              <div class="ajout-row">
                <div class="ajout-field">
                  <label class="ajout-label" for="consommation">
                    <i class="fas fa-tint"></i> Consommation
                  </label>
                  <div class="ajout-input-group">
                    <input id="consommation" name="consommation" class="ajout-input" type="number" step="0.1" min="0" max="50" placeholder="Ex: 5.5" />
                    <span class="ajout-input-suffix">L/100km</span>
                  </div>
                </div>
                
                <div class="ajout-field">
                  <label class="ajout-label" for="emission_co2">
                    <i class="fas fa-smog"></i> Émissions CO2
                  </label>
                  <div class="ajout-input-group">
                    <input id="emission_co2" name="emission_co2" class="ajout-input" type="number" min="0" max="500" placeholder="Ex: 120" />
                    <span class="ajout-input-suffix">g/km</span>
                  </div>
                </div>
              </div>
              
              <!-- Contrôle technique et Provenance -->
              <div class="ajout-row">
                <div class="ajout-field" data-hide-for="moto">
                  <label class="ajout-label" for="controle_technique">
                    <i class="fas fa-clipboard-check"></i> Contrôle technique <span class="ajout-required">*</span>
                  </label>
                  <select id="controle_technique" name="controle_technique" class="ajout-select" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="non_requis">🔘 Non requis (< 4 ans)</option>
                    <option value="oui">✅ Oui, à jour</option>
                    <option value="non">❌ Non, à faire</option>
                  </select>
                </div>
                
                <div class="ajout-field">
                  <label class="ajout-label" for="provenance">
                    <i class="fas fa-globe-europe"></i> Provenance
                  </label>
                  <input id="provenance" name="provenance" class="ajout-input" type="text" placeholder="Ex: France" maxlength="100" />
                </div>
              </div>
            </div>
          </div>
          
          <div class="step-actions">
            <button type="button" class="btn-step btn-step--prev" data-prev="1">
              <i class="fas fa-arrow-left"></i>
              Retour
            </button>
            <button type="button" class="btn-step btn-step--next" data-next="3">
              Continuer
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Étape 3 : Photos -->
      <div class="form-step" data-step="3">
        <div class="step-container">
          <div class="ajout-card">
            <div class="ajout-card__header ajout-card__header--center">
              <div class="ajout-card__icon-header">
                <div class="ajout-icon-circle">
                  <i class="fas fa-camera"></i>
                </div>
              </div>
              <h2 class="ajout-card__title">Photos du véhicule</h2>
              <p class="ajout-card__subtitle">Ajoutez jusqu'à 10 photos de qualité pour séduire les acheteurs</p>
            </div>
            
            <div class="ajout-upload">
              <label for="images" class="ajout-upload__zone">
                <div class="ajout-upload__icon">
                  <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <span class="ajout-upload__text">Cliquez ou glissez vos photos ici</span>
                <span class="ajout-upload__hint">JPG, PNG • Max 10 photos • 5 Mo par image</span>
                <input type="file" id="images" name="images[]" accept="image/*" multiple hidden>
              </label>
              
              <div id="conteneur-apercu" class="ajout-preview-grid" hidden>
                <!-- Les aperçus seront injectés ici par JS -->
              </div>
              
              <button type="button" id="bouton-tout-supprimer" class="ajout-btn-link ajout-btn-link--danger" hidden>
                <i class="fas fa-trash"></i> Supprimer toutes les photos
              </button>
            </div>
            
            <div class="ajout-tips">
              <h4><i class="fas fa-lightbulb"></i> Conseils pour de belles photos</h4>
              <ul>
                <li><i class="fas fa-check"></i> Prenez les photos en plein jour</li>
                <li><i class="fas fa-check"></i> Nettoyez le véhicule avant</li>
                <li><i class="fas fa-check"></i> Photographiez tous les angles</li>
                <li><i class="fas fa-check"></i> Montrez l'intérieur et le tableau de bord</li>
              </ul>
            </div>
          </div>
          
          <div class="step-actions">
            <button type="button" class="btn-step btn-step--prev" data-prev="2">
              <i class="fas fa-arrow-left"></i>
              Retour
            </button>
            <button type="button" class="btn-step btn-step--next" data-next="4">
              Continuer
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Étape 4 : Description et Publication -->
      <div class="form-step" data-step="4">
        <div class="step-container">
          <div class="ajout-card">
            <div class="ajout-card__header ajout-card__header--center">
              <h2 class="ajout-card__title">Description</h2>
              <p class="ajout-card__subtitle">Décrivez votre véhicule pour convaincre les acheteurs</p>
            </div>
            
            <div class="ajout-fields">
              <div class="ajout-field">
                <textarea id="description" name="description" class="ajout-textarea" rows="6" placeholder="Décrivez l'état du véhicule, les options, l'historique d'entretien, les éventuels défauts..."></textarea>
                <div class="ajout-textarea-hint">
                  <i class="fas fa-lightbulb"></i>
                  <span>Conseil : Mentionnez le contrôle technique, l'entretien, les options et l'historique du véhicule.</span>
                </div>
              </div>
            </div>
            
            <!-- Récapitulatif -->
            <div class="ajout-card ajout-card--summary">
              <h3 class="summary-title">
                <i class="fas fa-clipboard-check"></i> Récapitulatif
              </h3>
              <div class="summary-content" id="summary-content">
                <div class="summary-item">
                  <span class="summary-label">Type</span>
                  <span class="summary-value" id="summary-type">--</span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Véhicule</span>
                  <span class="summary-value" id="summary-vehicle">--</span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Prix</span>
                  <span class="summary-value" id="summary-price">--</span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Photos</span>
                  <span class="summary-value" id="summary-photos">0 photo(s)</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="step-actions">
            <button type="button" class="btn-step btn-step--prev" data-prev="3">
              <i class="fas fa-arrow-left"></i>
              Retour
            </button>
            <button id="bouton-soumettre" class="btn-step btn-step--submit" type="submit">
              <i class="fas fa-rocket"></i>
              Publier l'annonce
            </button>
          </div>
          
          <div class="messages-formulaire" aria-live="polite"></div>
        </div>
      </div>
      
    </form>
    
  </div>
</section>

<!-- Modal de succès -->
<div id="modal-succes" class="modal-success">
  <div class="modal-success__content">
    <div class="modal-success__icon">
      <i class="fas fa-check-circle"></i>
    </div>
    <h2 class="modal-success__title">Annonce publiée !</h2>
    <p class="modal-success__message">Votre véhicule est maintenant visible par des milliers d'acheteurs potentiels.</p>
    <div class="modal-success__actions">
      <a href="galerie" class="btn-success btn-success--primary">
        <i class="fas fa-th"></i> Voir toutes les annonces
      </a>
      <a href="ajout_vehicule" class="btn-success btn-success--secondary">
        <i class="fas fa-plus"></i> Ajouter une autre annonce
      </a>
    </div>
  </div>
</div>

<script type="module">
    import VueAjoutVehicule from './assets/js/Vehicule/Ajout/VueAjoutVehicule.js';
    document.addEventListener('DOMContentLoaded', () => {
        new VueAjoutVehicule();
    });
</script>
