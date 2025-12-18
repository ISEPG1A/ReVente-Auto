<!-- 
Page d'ajout de véhicule - VERSION MULTI-ÉTAPES
Formulaire progressif avec 4 étapes : Type → Informations → Photos → Description
-->

<?php
// Vérification de l'authentification
$estConnecte = !empty($_SESSION['user']);
?>

<?php if (!$estConnecte): ?>
<!-- Section authentification requise - Box centrée -->
<section class="auth-required-section">
  <div class="conteneur">
    <div class="auth-required-box">
      <div class="auth-required-box__icon">
        <i class="fas fa-lock"></i>
      </div>
      
      <h1 class="auth-required-box__title">Connexion requise</h1>
      
      <p class="auth-required-box__description">
        Pour publier une annonce et vendre votre véhicule, vous devez être connecté à votre compte.
      </p>
      
      <div class="auth-required-box__features">
        <div class="auth-required-box__feature">
          <i class="fas fa-check-circle"></i>
          <span>Gérez vos annonces facilement</span>
        </div>
        <div class="auth-required-box__feature">
          <i class="fas fa-check-circle"></i>
          <span>Recevez les messages des acheteurs</span>
        </div>
        <div class="auth-required-box__feature">
          <i class="fas fa-check-circle"></i>
          <span>Suivez vos favoris</span>
        </div>
      </div>
      
      <div class="auth-required-box__actions">
        <a href="connexion" class="bouton">
          <i class="fas fa-sign-in-alt"></i>
          Se connecter
        </a>
        <a href="connexion?mode=inscription" class="bouton">
          <i class="fas fa-user-plus"></i>
          Créer un compte
        </a>
      </div>
    </div>
  </div>
</section>

<?php else: ?>
<!-- Hero Section Minimaliste pour Multi-Steps -->
<section class="hero hero--compact">
  <div class="hero__background">
    <div class="hero__shapes">
      <div class="hero__shape hero__shape--1"></div>
      <div class="hero__shape hero__shape--2"></div>
    </div>
  </div>
  
  <div class="hero__content">
    <h1 class="hero__title">
      <i class="fas fa-plus-circle"></i>
      Publier une annonce
    </h1>
    <p class="hero__description">
      Complétez les étapes pour mettre votre véhicule en vente
    </p>
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
    <div class="ajout-layout">
      <!-- Colonne formulaire -->
      <div class="ajout-layout__form">
    
    <form id="formulaire-vehicule" class="ajout-form ajout-form--steps" novalidate enctype="multipart/form-data">
      
      <!-- Messages de validation -->
      <div class="messages-formulaire" aria-live="polite"></div>
      
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
              <div class="grille">
                <div class="champ">
                  <label class="etiquette" for="marque">
                    <i class="fas fa-industry"></i> Marque <span class="ajout-required">*</span>
                  </label>
                  <input id="marque" name="marque" class="saisie" type="text" placeholder="Ex: Peugeot" required maxlength="50" />
                </div>
                
                <div class="champ">
                  <label class="etiquette" for="modele">
                    <i class="fas fa-car"></i> Modèle <span class="ajout-required">*</span>
                  </label>
                  <input id="modele" name="modele" class="saisie" type="text" placeholder="Ex: 208 GT Line" required maxlength="50" />
                </div>
              </div>
              
              <!-- Année et Prix -->
              <div class="grille">
                <div class="champ">
                  <label class="etiquette" for="annee">
                    <i class="fas fa-calendar-alt"></i> Année <span class="ajout-required">*</span>
                  </label>
                  <input id="annee" name="annee" class="saisie" type="number" min="1900" max="2100" placeholder="Ex: 2020" required />
                </div>
                
                <div class="champ">
                  <label class="etiquette" for="prix">
                    <i class="fas fa-euro-sign"></i> Prix <span class="ajout-required">*</span>
                  </label>
                  <div class="ajout-input-group">
                    <input id="prix" name="prix" class="saisie" type="number" min="0" step="1" placeholder="Ex: 15000" required />
                    <span class="ajout-input-suffix">€</span>
                  </div>
                </div>
              </div>
              
              <!-- Kilométrage et Ville -->
              <div class="grille">
                <div class="champ">
                  <label class="etiquette" for="km">
                    <i class="fas fa-tachometer-alt"></i> Kilométrage <span class="ajout-required">*</span>
                  </label>
                  <div class="ajout-input-group">
                    <input id="km" name="km" class="saisie" type="number" min="0" step="1" placeholder="Ex: 45000" required />
                    <span class="ajout-input-suffix">km</span>
                  </div>
                </div>
                
                <div class="champ">
                  <label class="etiquette" for="ville">
                    <i class="fas fa-map-marker-alt"></i> Ville <span class="ajout-required">*</span>
                  </label>
                  <input id="ville" name="ville" class="saisie" type="text" placeholder="Ex: Paris" required />
                </div>
              </div>
              
              <!-- Carburant et Boîte -->
              <div class="grille">
                <div class="champ">
                  <label class="etiquette" for="carburant">
                    <i class="fas fa-gas-pump"></i> Carburant <span class="ajout-required">*</span>
                  </label>
                  <select id="carburant" name="carburant" class="selecteur" required>
                    <option value="">-- Sélectionnez --</option>
                    <!-- Options filtrées dynamiquement selon le type -->
                    <option value="Essence">🔴 Essence</option>
                    <option value="Diesel">⚫ Diesel</option>
                    <option value="Hybride">🟢 Hybride</option>
                    <option value="Électrique">🔵 Électrique</option>
                    <option value="GPL">🟡 GPL</option>
                  </select>
                </div>
                
                <div class="champ" data-hide-for="moto">
                  <label class="etiquette" for="boite">
                    <i class="fas fa-cog"></i> Boîte de vitesse <span class="ajout-required">*</span>
                  </label>
                  <select id="boite" name="boite" class="selecteur" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="Manuelle">⚙️ Manuelle</option>
                    <option value="Automatique">🅰️ Automatique</option>
                  </select>
                </div>
              </div>
              
              <!-- État et Couleur -->
              <div class="grille">
                <div class="champ">
                  <label class="etiquette" for="etat">
                    <i class="fas fa-star-half-alt"></i> État du véhicule <span class="ajout-required">*</span>
                  </label>
                  <select id="etat" name="etat" class="selecteur" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="neuf">✨ Neuf</option>
                    <option value="bon">👍 Bon état</option>
                    <option value="moyen">👌 État moyen</option>
                    <option value="mauvais">👎 Mauvais état</option>
                  </select>
                </div>
                
                <div class="champ">
                  <label class="etiquette" for="couleur">
                    <i class="fas fa-palette"></i> Couleur <span class="ajout-required">*</span>
                  </label>
                  <input id="couleur" name="couleur" class="saisie" type="text" placeholder="Ex: Noir métallisé" maxlength="50" required />
                </div>
              </div>
              
              <!-- Crit'Air -->
              <div class="grille">
                <div class="champ">
                  <label class="etiquette" for="crit_air">
                    <i class="fas fa-wind"></i> Vignette Crit'Air
                  </label>
                  <select id="crit_air" name="crit_air" class="selecteur">
                    <option value="">-- Non renseigné --</option>
                    <!-- Options filtrées dynamiquement selon le type (motos: 1-3, voitures/camions: 0-5) -->
                    <option value="0">🟢 Crit'Air 0 (Électrique)</option>
                    <option value="1">🟣 Crit'Air 1</option>
                    <option value="2">🟡 Crit'Air 2</option>
                    <option value="3">🟠 Crit'Air 3</option>
                    <option value="4">🟤 Crit'Air 4</option>
                    <option value="5">⚫ Crit'Air 5</option>
                  </select>
                </div>
                
                <div class="champ"></div>
              </div>
              
              <!-- Nombre de portes et places -->
              <div class="grille" data-hide-for="moto">
                <div class="champ">
                  <label class="etiquette" for="nb_portes">
                    <i class="fas fa-door-open"></i> Nombre de portes <span class="ajout-required">*</span>
                  </label>
                  <select id="nb_portes" name="nb_portes" class="selecteur" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="2">2 portes</option>
                    <option value="3">3 portes</option>
                    <option value="4">4 portes</option>
                    <option value="5">5 portes</option>
                  </select>
                </div>
                
                <div class="champ">
                  <label class="etiquette" for="nb_places">
                    <i class="fas fa-users"></i> Nombre de places <span class="ajout-required">*</span>
                  </label>
                  <select id="nb_places" name="nb_places" class="selecteur" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="2">2 places</option>
                    <option value="3">3 places</option>
                    <option value="4">4 places</option>
                    <option value="5">5 places</option>
                    <option value="6+">6 ou plus</option>
                  </select>
                </div>
              </div>
              
              <!-- Taille coffre (voiture et camion) -->
              <div class="grille" data-hide-for="moto">
                <div class="champ">
                  <label class="etiquette" for="taille_coffre">
                    <i class="fas fa-suitcase"></i> <span id="label-taille-coffre">Taille du coffre</span>
                  </label>
                  <select id="taille_coffre" name="taille_coffre" class="selecteur">
                    <option value="">-- Non renseigné --</option>
                    <!-- Options adaptées selon le type (L pour voiture, m³ pour camion) -->
                    <option value="petit">🔹 Petit (< 300L)</option>
                    <option value="moyen">🔸 Moyen (300-500L)</option>
                    <option value="grand">🔶 Grand (> 500L)</option>
                  </select>
                </div>
                <div class="champ"></div>
              </div>

              <!-- Dimensions (Longueur / Largeur) -->
              <div class="grille">
                <div class="champ">
                  <label class="etiquette" for="longueur">
                    <i class="fas fa-ruler-horizontal"></i> Longueur
                  </label>
                  <div class="ajout-input-group">
                    <input id="longueur" name="longueur" class="saisie" type="number" step="0.01" min="1.5" max="20" placeholder="Ex: 4.50" />
                    <span class="ajout-input-suffix">m</span>
                  </div>
                </div>
                
                <div class="champ">
                  <label class="etiquette" for="largeur">
                    <i class="fas fa-ruler-vertical"></i> Largeur
                  </label>
                  <div class="ajout-input-group">
                    <input id="largeur" name="largeur" class="saisie" type="number" step="0.01" min="1" max="4" placeholder="Ex: 1.80" />
                    <span class="ajout-input-suffix">m</span>
                  </div>
                </div>
              </div>

              <!-- Dimensions (Hauteur) -->
              <div class="grille">
                <div class="champ">
                  <label class="etiquette" for="hauteur">
                    <i class="fas fa-ruler-vertical"></i> Hauteur
                  </label>
                  <div class="ajout-input-group">
                    <input id="hauteur" name="hauteur" class="saisie" type="number" step="0.01" min="0.5" max="5" placeholder="Ex: 1.50" />
                    <span class="ajout-input-suffix">m</span>
                  </div>
                </div>
                <div class="champ"></div>
              </div>
              
              <!-- Puissance et Norme Euro -->
              <div class="grille">
                <div class="champ">
                  <label class="etiquette" for="puissance_cv">
                    <i class="fas fa-horse"></i> Puissance
                  </label>
                  <div class="ajout-input-group">
                    <input id="puissance_cv" name="puissance_cv" class="saisie" type="number" min="1" max="2000" placeholder="Ex: 130" />
                    <span class="ajout-input-suffix">CV</span>
                  </div>
                </div>
                
                <div class="champ">
                  <label class="etiquette" for="norme_euro">
                    <i class="fas fa-leaf"></i> Norme Euro
                  </label>
                  <select id="norme_euro" name="norme_euro" class="selecteur">
                    <option value="">-- Non renseigné --</option>
                    <!-- Options filtrées selon le type (motos: Euro 3-5, voitures/camions: Euro 1-6d) -->
                    <option value="Euro 1">Euro 1</option>
                    <option value="Euro 2">Euro 2</option>
                    <option value="Euro 3">Euro 3</option>
                    <option value="Euro 4">Euro 4</option>
                    <option value="Euro 5">Euro 5</option>
                    <option value="Euro 6">Euro 6</option>
                    <option value="Euro 6d">Euro 6d</option>
                  </select>
                </div>
              </div>
              
              <!-- Type d'hybride (masqué par défaut, affiché si carburant = Hybride) -->
              <div class="grille" id="field-type-hybride" style="display: none;">
                <div class="champ">
                  <label class="etiquette" for="type_hybride">
                    <i class="fas fa-leaf"></i> Type d'hybride <span class="ajout-required">*</span>
                  </label>
                  <select id="type_hybride" name="type_hybride" class="selecteur">
                    <option value="">-- Sélectionnez --</option>
                    <option value="essence_electrique">Essence + Électrique (HEV)</option>
                    <option value="essence_electrique_rechargeable">Essence + Électrique rechargeable (PHEV)</option>
                    <option value="diesel_electrique">Diesel + Électrique (HEV)</option>
                    <option value="diesel_electrique_rechargeable">Diesel + Électrique rechargeable (PHEV)</option>
                    <option value="gpl_essence">GPL + Essence</option>
                  </select>
                </div>
                <div class="champ"></div>
              </div>
              
              <!-- Consommation et Émissions CO2 -->
              <div class="grille">
                <!-- Consommation principale -->
                <div class="champ" id="field-consommation-principale">
                  <label class="etiquette" for="consommation">
                    <i class="fas fa-tint"></i> <span id="label-consommation">Consommation</span>
                  </label>
                  <div class="ajout-input-group">
                    <input id="consommation" name="consommation" class="saisie" type="number" step="0.1" min="0.1" max="99.9" placeholder="Ex: 5.5" />
                    <span class="ajout-input-suffix" id="unite-consommation">L/100km</span>
                  </div>
                </div>
                
                <!-- Consommation secondaire (pour hybrides) -->
                <div class="champ" id="field-consommation-secondaire" style="display: none;">
                  <label class="etiquette" for="consommation_secondaire">
                    <i class="fas fa-bolt"></i> <span id="label-consommation-secondaire">Consommation secondaire</span>
                  </label>
                  <div class="ajout-input-group">
                    <input id="consommation_secondaire" name="consommation_secondaire" class="saisie" type="number" step="0.1" min="0.1" max="99.9" placeholder="Ex: 15.5" />
                    <span class="ajout-input-suffix" id="unite-consommation-secondaire">kWh/100km</span>
                  </div>
                </div>
              </div>
              
              <!-- Autonomie électrique -->
              <div class="grille">
                <div class="champ" id="field-autonomie" style="display: none;">
                  <label class="etiquette" for="autonomie">
                    <i class="fas fa-battery-three-quarters"></i> Autonomie électrique
                  </label>
                  <div class="ajout-input-group">
                    <input id="autonomie" name="autonomie" class="saisie" type="number" min="50" max="9999" placeholder="Ex: 450" />
                    <span class="ajout-input-suffix">km</span>
                  </div>
                </div>
                
                <!-- Émissions CO2 -->
                <div class="champ">
                  <label class="etiquette" for="emission_co2">
                    <i class="fas fa-smog"></i> Émissions CO2
                  </label>
                  <div class="ajout-input-group">
                    <input id="emission_co2" name="emission_co2" class="saisie" type="number" min="0" max="500" placeholder="Ex: 120" />
                    <span class="ajout-input-suffix">g/km</span>
                  </div>
                </div>
              </div>
              
              <!-- Contrôle technique et Provenance -->
              <div class="grille">
                <div class="champ" data-hide-for="moto">
                  <label class="etiquette" for="controle_technique">
                    <i class="fas fa-clipboard-check"></i> Contrôle technique <span class="ajout-required">*</span>
                  </label>
                  <select id="controle_technique" name="controle_technique" class="selecteur" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="non_requis">🔘 Non requis (< 4 ans)</option>
                    <option value="oui">✅ Oui, à jour</option>
                    <option value="non">❌ Non, à faire</option>
                  </select>
                </div>
                
                <div class="champ">
                  <label class="etiquette" for="provenance">
                    <i class="fas fa-globe-europe"></i> Provenance
                  </label>
                  <input id="provenance" name="provenance" class="saisie" type="text" placeholder="Ex: France" maxlength="100" />
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
              <h2 class="ajout-card__title">Photos du véhicule</h2>
              <p class="ajout-card__subtitle">Ajoutez jusqu'à 10 photos de qualité pour séduire les acheteurs</p>
            </div>
            
            <div class="ajout-upload">
              <label for="images" class="ajout-upload__zone">
                <div class="ajout-upload__icon">
                  <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <span class="ajout-upload__text">Cliquez ou glissez vos photos ici</span>
                <span class="ajout-upload__hint">JPEG, PNG, WebP • Max 10 photos • 5 Mo par image</span>
                <input type="file" id="images" name="images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple hidden>
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
              <div class="champ">
                <textarea id="description" name="description" class="saisie" rows="6" placeholder="Décrivez l'état du véhicule, les options, l'historique d'entretien, les éventuels défauts..."></textarea>
                <div class="ajout-textarea-hint">
                  <i class="fas fa-lightbulb"></i>
                  <span>Conseil : Mentionnez le contrôle technique, l'entretien, les options et l'historique du véhicule.</span>
                </div>
              </div>
            </div>
            
            <!-- Récapitulatif -->
            <div class="ajout-card ajout-card--summary">
              <h3 class="summary-title">
                <i class="fas fa-clipboard-check"></i> Récapitulatif de votre annonce
              </h3>
              <div class="summary-content" id="summary-content">
                <!-- Le contenu sera généré dynamiquement par JavaScript -->
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
        </div>
      </div>
      
    </form>
    
      </div>
      
      <!-- Colonne prévisualisation -->
      <div class="ajout-layout__preview">
        <div class="preview-sticky">
          <h3 class="preview-title">
            <i class="fas fa-eye"></i> Aperçu de votre annonce
          </h3>
          
          <div id="carte-preview" class="preview-card">
            <div class="preview-card__image">
              <img src="" alt="Aperçu véhicule" style="display: none;">
              <div class="preview-card__placeholder-icon">
                <i class="fas fa-car-side"></i>
              </div>
              <div class="preview-card__photo-badge" style="display: none;">
                <i class="fas fa-camera"></i>
                <span class="preview-card__photo-count">0</span>
              </div>
            </div>
            
            <div class="preview-card__content">
              <h4 class="preview-card__title">Votre véhicule</h4>
              <p class="preview-card__price">-- €</p>
              
              <div class="preview-card__specs">
                <div class="preview-card__spec">
                  <i class="fas fa-calendar"></i>
                  <span data-preview="annee">--</span>
                </div>
                <div class="preview-card__spec">
                  <i class="fas fa-tachometer-alt"></i>
                  <span data-preview="km">-- km</span>
                </div>
                <div class="preview-card__spec">
                  <i class="fas fa-gas-pump"></i>
                  <span data-preview="carburant">--</span>
                </div>
                <div class="preview-card__spec">
                  <i class="fas fa-cog"></i>
                  <span data-preview="boite">--</span>
                </div>
              </div>
              
              <div class="preview-card__location">
                <i class="fas fa-map-marker-alt"></i>
                <span data-preview="ville">--</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
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
    import VueAjoutVehicule from './assets/js/Vehicule/Ajout/VueAjoutVehicule.js?v=<?php echo time(); ?>';
    document.addEventListener('DOMContentLoaded', () => {
        new VueAjoutVehicule();
    });
</script>

<?php endif; ?>
