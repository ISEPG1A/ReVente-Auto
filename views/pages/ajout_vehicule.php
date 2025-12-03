<!-- 
Page d'ajout de véhicule
Design moderne avec étapes et prévisualisation
-->

<!-- Hero Section -->
<section class="ajout-hero">
  <div class="ajout-hero__shapes">
    <div class="ajout-shape ajout-shape--1"></div>
    <div class="ajout-shape ajout-shape--2"></div>
    <div class="ajout-shape ajout-shape--3"></div>
  </div>
  
  <div class="conteneur">
    <div class="ajout-hero__content">
      <div class="ajout-hero__badge">
        <i class="fas fa-plus-circle"></i>
        <span>Nouvelle annonce</span>
      </div>
      <h1 class="ajout-hero__title">
        Vendez votre <span>véhicule</span>
      </h1>
      <p class="ajout-hero__description">
        Publiez votre annonce en quelques minutes et touchez des milliers d'acheteurs potentiels.
      </p>
      
      <div class="ajout-hero__features">
        <div class="ajout-feature">
          <i class="fas fa-eye"></i>
          <span>10K+ visiteurs/jour</span>
        </div>
        <div class="ajout-feature">
          <i class="fas fa-clock"></i>
          <span>24h en moyenne</span>
        </div>
        <div class="ajout-feature">
          <i class="fas fa-shield-alt"></i>
          <span>100% sécurisé</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Section principale -->
<section class="ajout-main">
  <div class="conteneur">
    
    <form id="formulaire-vehicule" class="ajout-form" novalidate enctype="multipart/form-data">
      
      <div class="ajout-layout">
        
        <!-- Colonne gauche : Formulaire -->
        <div class="ajout-form-container">
          
          <!-- Section Photos -->
          <div class="ajout-card">
            <div class="ajout-card__header">
              <div class="ajout-card__step">1</div>
              <div>
                <h2 class="ajout-card__title">Photos du véhicule</h2>
                <p class="ajout-card__subtitle">Ajoutez jusqu'à 10 photos pour attirer plus d'acheteurs</p>
              </div>
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
          </div>
          
          <!-- Section Informations -->
          <div class="ajout-card">
            <div class="ajout-card__header">
              <div class="ajout-card__step">2</div>
              <div>
                <h2 class="ajout-card__title">Informations du véhicule</h2>
                <p class="ajout-card__subtitle">Décrivez votre véhicule avec précision</p>
              </div>
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
                    <i class="fas fa-gas-pump"></i> Carburant
                  </label>
                  <select id="carburant" name="carburant" class="ajout-select">
                    <option value="Essence">🔴 Essence</option>
                    <option value="Diesel">⚫ Diesel</option>
                    <option value="Hybride">🟢 Hybride</option>
                    <option value="Électrique">🔵 Électrique</option>
                    <option value="GPL">🟡 GPL</option>
                  </select>
                </div>
                
                <div class="ajout-field">
                  <label class="ajout-label" for="boite">
                    <i class="fas fa-cog"></i> Boîte de vitesse
                  </label>
                  <select id="boite" name="boite" class="ajout-select">
                    <option value="Manuelle">⚙️ Manuelle</option>
                    <option value="Automatique">🅰️ Automatique</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Section Description -->
          <div class="ajout-card">
            <div class="ajout-card__header">
              <div class="ajout-card__step">3</div>
              <div>
                <h2 class="ajout-card__title">Description</h2>
                <p class="ajout-card__subtitle">Donnez envie aux acheteurs avec une description détaillée</p>
              </div>
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
          </div>
          
          <!-- Bouton de soumission (mobile) -->
          <div class="ajout-submit ajout-submit--mobile">
            <button id="bouton-soumettre-mobile" class="ajout-btn ajout-btn--primary" type="submit">
              <i class="fas fa-check-circle"></i>
              <span>Publier mon annonce</span>
            </button>
          </div>
          
          <div class="messages-formulaire" aria-live="polite"></div>
          
        </div>
        
        <!-- Colonne droite : Prévisualisation -->
        <div class="ajout-preview-container">
          <div class="ajout-preview-sticky">
            
            <div class="ajout-card ajout-card--preview">
              <div class="ajout-preview__header">
                <i class="fas fa-eye"></i>
                <span>Prévisualisation</span>
              </div>
              
              <div class="ajout-preview__card">
                <div class="ajout-preview__image" id="preview-image">
                  <i class="fas fa-image"></i>
                  <span>Aperçu de la photo</span>
                </div>
                
                <div class="ajout-preview__content">
                  <h3 class="ajout-preview__title" id="preview-title">Marque Modèle</h3>
                  
                  <div class="ajout-preview__tags">
                    <span class="ajout-preview__tag" id="preview-year">
                      <i class="fas fa-calendar"></i> ----
                    </span>
                    <span class="ajout-preview__tag" id="preview-km">
                      <i class="fas fa-road"></i> -- km
                    </span>
                    <span class="ajout-preview__tag" id="preview-fuel">
                      <i class="fas fa-gas-pump"></i> --
                    </span>
                  </div>
                  
                  <div class="ajout-preview__location" id="preview-location">
                    <i class="fas fa-map-marker-alt"></i> --
                  </div>
                  
                  <div class="ajout-preview__price" id="preview-price">-- €</div>
                </div>
              </div>
            </div>
            
            <!-- Bouton de soumission (desktop) -->
            <div class="ajout-submit ajout-submit--desktop">
              <button id="bouton-soumettre" class="ajout-btn ajout-btn--primary" type="submit">
                <i class="fas fa-check-circle"></i>
                <span>Publier mon annonce</span>
              </button>
              
              <p class="ajout-submit__hint">
                <i class="fas fa-shield-alt"></i>
                Votre annonce sera visible immédiatement après publication.
              </p>
            </div>
            
            <!-- Tips -->
            <div class="ajout-card ajout-card--tips">
              <h4><i class="fas fa-lightbulb"></i> Conseils pour vendre rapidement</h4>
              <ul>
                <li><i class="fas fa-check"></i> Ajoutez au moins 5 photos de qualité</li>
                <li><i class="fas fa-check"></i> Fixez un prix réaliste selon le marché</li>
                <li><i class="fas fa-check"></i> Rédigez une description détaillée</li>
                <li><i class="fas fa-check"></i> Répondez rapidement aux messages</li>
              </ul>
            </div>
            
          </div>
        </div>
        
      </div>
      
    </form>
    
  </div>
</section>

<script type="module">
    import VueAjoutVehicule from './assets/js/Vehicule/Ajout/VueAjoutVehicule.js';
    document.addEventListener('DOMContentLoaded', () => {
        new VueAjoutVehicule();
    });
</script>
