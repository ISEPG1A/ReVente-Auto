<?php
/**
 * Page de modification de véhicule - Version complète refonte
 * Système multi-étapes aligné avec ajout_vehicule.php
 * Tous les nouveaux champs inclus
 */
?>
<link rel="stylesheet" href="./assets/css/components/modal_suppression.css?v=<?php echo time(); ?>">
<!-- Header discret de modification -->
<section class="modification-header">
  <div class="conteneur">
    <div class="modification-header__content">
      <a href="javascript:history.back()" class="modification-header__back">
        <i class="fas fa-arrow-left"></i>
      </a>
      <div class="modification-header__info">
        <span class="modification-header__badge">
          <i class="fas fa-edit"></i> Modification
        </span>
        <h1 class="modification-header__title">Modifier votre annonce</h1>
      </div>
      <a id="lien-voir-annonce-header" href="#" class="modification-header__view" target="_blank">
        <i class="fas fa-eye"></i>
        <span>Voir l'annonce</span>
      </a>
    </div>
  </div>
</section>

<!-- Zone de chargement -->
<section class="modification-loading-section">
  <div id="chargement-modification" class="chargement-page">
    <div class="chargement-spinner"></div>
    <p>Vérification des droits d'accès...</p>
  </div>
</section>

<!-- Zone d'erreur -->
<section id="erreur-modification" class="modification-erreur-section" hidden style="display: none;">
  <div class="conteneur">
    <div class="modification-erreur-container">
      <div class="modification-erreur-card">
        <div class="modification-erreur-icon">
          <i class="fas fa-lock"></i>
        </div>
        <h2 class="modification-erreur-titre">Accès refusé</h2>
        <p class="modification-erreur-message" id="message-erreur">Une erreur est survenue</p>
        <div class="modification-erreur-actions">
          <a href="connexion" class="modification-erreur-btn modification-erreur-btn--primary">
            <i class="fas fa-sign-in-alt"></i> Se connecter
          </a>
          <a href="galerie" class="modification-erreur-btn modification-erreur-btn--secondary">
            <i class="fas fa-arrow-left"></i> Retour à la galerie
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Barre de progression -->
<section id="progress-section" class="progress-section" hidden>
  <div class="conteneur">
    <div class="stepper">
      <div class="stepper__step stepper__step--active" data-step="1">
        <div class="stepper__circle">
          <i class="fas fa-info-circle"></i>
        </div>
        <span class="stepper__label">Informations</span>
      </div>
      
      <div class="stepper__step" data-step="2">
        <div class="stepper__circle">
          <i class="fas fa-camera"></i>
        </div>
        <span class="stepper__label">Photos</span>
      </div>
      
      <div class="stepper__step" data-step="3">
        <div class="stepper__circle">
          <i class="fas fa-align-left"></i>
        </div>
        <span class="stepper__label">Description</span>
      </div>
    </div>
  </div>
</section>

<!-- Section principale -->
<section id="contenu-modification" class="ajout-main" hidden>
  <div class="conteneur">
    <div class="ajout-layout">
      
      <!-- Colonne formulaire -->
      <div class="ajout-layout__form">
    
        <form id="formulaire-modification" class="ajout-form ajout-form--steps" novalidate enctype="multipart/form-data">
          <input type="hidden" id="vehicule-id" name="id" value="">
          <input type="hidden" id="type-vehicule-hidden" name="type_vehicule" value="">
          
          <!-- Messages de validation -->
          <div class="messages-formulaire" aria-live="polite"></div>
          
          <!-- Étape 1 : Informations du véhicule -->
          <div class="form-step form-step--active" data-step="1">
            <div class="step-container">
              <div class="ajout-card">
                <div class="ajout-card__header ajout-card__header--center">
                  <h2 class="ajout-card__title">Informations du véhicule</h2>
                  <p class="ajout-card__subtitle">Modifiez les détails de votre véhicule</p>
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
                  
                  <!-- Kilométrage et Code Postal -->
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
                      <label class="etiquette" for="code_postal">
                        <i class="fas fa-map-pin"></i> Code postal <span class="ajout-required">*</span>
                      </label>
                      <input id="code_postal" name="code_postal" class="saisie" type="text" pattern="\d{5}" maxlength="5" placeholder="Ex: 75001" required />
                    </div>
                  </div>
                  
                  <!-- Ville (dépend du code postal) -->
                  <div class="champ">
                    <label class="etiquette" for="ville">
                      <i class="fas fa-map-marker-alt"></i> Ville <span class="ajout-required">*</span>
                    </label>
                    <select id="ville" name="ville" class="selecteur" required disabled>
                      <option value="">-- Entrez d'abord le code postal --</option>
                    </select>
                  </div>
                  
                  <!-- Carburant et Boîte -->
                  <div class="grille">
                    <div class="champ">
                      <label class="etiquette" for="carburant">
                        <i class="fas fa-gas-pump"></i> Carburant <span class="ajout-required">*</span>
                      </label>
                      <select id="carburant" name="carburant" class="selecteur" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="Essence">🔴 Essence</option>
                        <option value="Diesel">⚫ Diesel</option>
                        <option value="Hybride">🟢 Hybride</option>
                        <option value="Électrique">🔵 Électrique</option>
                        <option value="GPL">🟡 GPL</option>
                      </select>
                    </div>
                    
                    <div class="champ" id="field-boite">
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
                  <div class="grille" id="field-portes-places">
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
                  
                  <!-- Taille coffre -->
                  <div class="grille" id="field-coffre">
                    <div class="champ">
                      <label class="etiquette" for="taille_coffre">
                        <i class="fas fa-suitcase"></i> Taille du coffre
                      </label>
                      <select id="taille_coffre" name="taille_coffre" class="selecteur">
                        <option value="">-- Non renseigné --</option>
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
                    <div class="champ" id="field-consommation-principale">
                      <label class="etiquette" for="consommation">
                        <i class="fas fa-tint"></i> <span id="label-consommation">Consommation</span>
                      </label>
                      <div class="ajout-input-group">
                        <input id="consommation" name="consommation" class="saisie" type="number" step="0.1" min="0.1" max="99.9" placeholder="Ex: 5.5" />
                        <span class="ajout-input-suffix" id="unite-consommation">L/100km</span>
                      </div>
                    </div>
                    
                    <div class="champ" id="field-consommation-secondaire" style="display: none;">
                      <label class="etiquette" for="consommation_secondaire">
                        <i class="fas fa-bolt"></i> <span id="label-consommation-secondaire">Consommation secondaire</span>
                      </label>
                      <div class="ajout-input-group">
                        <input id="consommation_secondaire" name="consommation_secondaire" class="saisie" type="number" step="0.1" min="0.1" max="99.9" placeholder="Ex: 15.5" />
                        <span class="ajout-input-suffix" id="unite-consommation-secondaire">kWh/100km</span>
                      </div>
                    </div>
                    
                    <div class="champ" id="field-emission-co2">
                      <label class="etiquette" for="emission_co2">
                        <i class="fas fa-smog"></i> Émissions CO₂
                      </label>
                      <div class="ajout-input-group">
                        <input id="emission_co2" name="emission_co2" class="saisie" type="number" min="0" max="500" placeholder="Ex: 120" />
                        <span class="ajout-input-suffix">g/km</span>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Autonomie électrique -->
                  <div class="grille">
                    <div class="champ" id="field-autonomie" style="display: none;">
                      <label class="etiquette" for="autonomie">
                        <i class="fas fa-battery-full"></i> Autonomie électrique
                      </label>
                      <div class="ajout-input-group">
                        <input id="autonomie" name="autonomie" class="saisie" type="number" min="50" max="9999" placeholder="Ex: 400" />
                        <span class="ajout-input-suffix">km</span>
                      </div>
                    </div>
                    <div class="champ"></div>
                  </div>
                  
                  <!-- Contrôle technique et Provenance -->
                  <div class="grille">
                    <div class="champ">
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
                <button type="button" class="btn-step btn-step--next" data-next="2">
                  Continuer
                  <i class="fas fa-arrow-right"></i>
                </button>
              </div>
            </div>
          </div>
          
          <!-- Étape 2 : Photos du véhicule -->
          <div class="form-step" data-step="2">
            <div class="step-container">
              <div class="ajout-card">
                <div class="ajout-card__header ajout-card__header--center">
                  <h2 class="ajout-card__title">Photos du véhicule</h2>
                  <p class="ajout-card__subtitle">Gérez les photos de votre annonce (minimum 3 photos)</p>
                </div>
                
                <!-- Photos existantes -->
                <div class="modification-images" id="section-images-existantes" style="margin-bottom: 2rem;">
                  <div class="modification-images__header">
                    <h3 class="modification-images__title">
                      <i class="fas fa-images"></i> Photos actuelles (<span id="compteur-existantes">0</span>)
                    </h3>
                    <span class="modification-images__hint">Cliquez sur une photo pour la définir comme couverture • Cliquez sur ✕ pour supprimer</span>
                  </div>
                  <div id="images-existantes" class="modification-images__grid"></div>
                </div>
                
                <!-- Ajout de nouvelles photos -->
                <div class="ajout-upload">
                  <label for="images" class="ajout-upload__zone">
                    <div class="ajout-upload__icon">
                      <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <span class="ajout-upload__text">Ajouter de nouvelles photos</span>
                    <span class="ajout-upload__hint">JPEG, PNG, WebP • Max 10 photos au total • 5 Mo par image</span>
                    <input type="file" id="images" name="images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple hidden />
                  </label>
                  
                  <div id="conteneur-apercu-nouvelles" class="ajout-upload__previews"></div>
                  
                  <div class="ajout-upload__actions">
                    <button type="button" id="bouton-tout-supprimer-nouvelles" class="btn-step btn-step--prev" style="margin-top: 1rem;" hidden>
                      <i class="fas fa-trash-alt"></i> Supprimer toutes les nouvelles photos
                    </button>
                    <p class="ajout-upload__counter">
                      <span id="compteur-nouvelles">0</span> nouvelle(s) photo(s) • 
                      <span id="compteur-total">0</span> photo(s) au total
                    </p>
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
          
          <!-- Étape 3 : Description et Validation -->
          <div class="form-step" data-step="3">
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
                    <i class="fas fa-clipboard-check"></i> Récapitulatif de vos modifications
                  </h3>
                  <div class="summary-content" id="summary-content">
                    <!-- Le contenu sera généré dynamiquement par JavaScript -->
                  </div>
                </div>
              </div>
              
              <div class="step-actions">
                <button type="button" class="btn-step btn-step--prev" data-prev="2">
                  <i class="fas fa-arrow-left"></i>
                  Retour
                </button>
                <button id="bouton-enregistrer" class="btn-step btn-step--submit" type="submit">
                  <i class="fas fa-save"></i>
                  Enregistrer les modifications
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
              <img src="assets/images/placeholder-car.svg" alt="Aperçu" class="preview-card__placeholder">
              <div class="preview-card__photo-badge" style="display: none;">
                <i class="fas fa-camera"></i>
                <span class="preview-card__photo-count" id="preview-photo-count">0</span>
              </div>
            </div>
            
            <div class="preview-card__content">
              <h3 class="preview-card__title" id="preview-title">Marque Modèle</h3>
              <div class="preview-card__price" id="preview-price">-- €</div>
              
              <div class="preview-card__specs">
                <div class="preview-card__spec">
                  <i class="fas fa-calendar-alt"></i>
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
                  <i class="fas fa-cogs"></i>
                  <span data-preview="boite">--</span>
                </div>
              </div>
              
              <div class="preview-card__location">
                <i class="fas fa-map-marker-alt"></i>
                <span data-preview="ville">--</span>
              </div>
            </div>
          </div>
          
          <!-- Actions supplémentaires -->
          <div class="modification-actions-card">
            <h4 class="modification-actions-card__title">
              <i class="fas fa-cog"></i> Actions
            </h4>
            <div class="modification-actions-card__content">
              <a id="lien-voir-annonce-sidebar" href="#" target="_blank" class="modification-action-btn modification-action-btn--view">
                <i class="fas fa-external-link-alt"></i>
                <span>Voir l'annonce publique</span>
              </a>
              <button id="bouton-supprimer" type="button" class="modification-action-btn modification-action-btn--danger">
                <i class="fas fa-trash-alt"></i>
                <span>Supprimer cette annonce</span>
              </button>
            </div>
          </div>
        </div>
      </div>
      
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

<!-- Scripts -->
<script src="./assets/js/Localisation/GestionnaireCodePostal.js?v=<?php echo time(); ?>"></script>

<script type="module">
    import VueModificationVehicule from './assets/js/Vehicule/Modification/VueModificationVehicule.js?v=<?php echo time(); ?>';
    document.addEventListener('DOMContentLoaded', () => {
        new VueModificationVehicule();
    });
</script>

