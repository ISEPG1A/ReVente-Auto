<!-- 
Page de modification de véhicule
Design discret avec header compact
-->

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
      <a id="lien-voir-annonce" href="#" class="modification-header__view">
        <i class="fas fa-eye"></i>
        <span>Voir l'annonce</span>
      </a>
    </div>
  </div>
</section>

<!-- Zone de chargement -->
<div id="chargement-modification" class="chargement-page">
  <div class="chargement-spinner"></div>
  <p>Chargement des données...</p>
</div>

<!-- Zone d'erreur -->
<div id="erreur-modification" class="modification-erreur-container" hidden>
  <div class="modification-erreur-card">
    <div class="modification-erreur-icon">
      <i class="fas fa-lock"></i>
    </div>
    <h2 class="modification-erreur-titre">Accès refusé</h2>
    <p class="modification-erreur-message">Une erreur est survenue</p>
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

<!-- Section principale -->
<section id="contenu-modification" class="ajout-main" hidden>
  <div class="conteneur">
    
    <form id="formulaire-modification" class="ajout-form" novalidate enctype="multipart/form-data">
      <input type="hidden" id="vehicule-id" name="id" value="">
      
      <div class="ajout-layout">
        
        <!-- Colonne gauche : Formulaire -->
        <div class="ajout-form-container">
          
          <!-- Section Photos -->
          <div class="ajout-card">
            <div class="ajout-card__header">
              <div class="ajout-card__step">1</div>
              <div>
                <h2 class="ajout-card__title">Photos du véhicule</h2>
                <p class="ajout-card__subtitle">Gérez les photos de votre annonce</p>
              </div>
            </div>
            
            <div class="ajout-upload">
              <!-- Zone des images existantes -->
              <div class="modification-images">
                <div class="modification-images__header">
                  <h3 class="modification-images__title">
                    <i class="fas fa-images"></i> Photos actuelles
                  </h3>
                  <span class="modification-images__hint">Cliquez sur une photo pour la définir comme couverture</span>
                </div>
                <div id="images-existantes" class="modification-images__grid"></div>
              </div>
              
              <!-- Zone pour ajouter de nouvelles photos -->
              <div class="modification-nouvelles">
                <label for="images" class="ajout-upload__zone" id="zone-ajout-photos">
                  <div class="ajout-upload__icon">
                    <i class="fas fa-plus"></i>
                  </div>
                  <span class="ajout-upload__text">Ajouter d'autres photos</span>
                  <span class="ajout-upload__hint">JPG, PNG • Max 10 photos au total</span>
                  <input type="file" id="images" name="images[]" accept="image/*" multiple hidden>
                </label>
                
                <div id="conteneur-apercu-nouvelles" class="modification-images__grid" hidden>
                  <!-- Les aperçus des nouvelles images seront injectés ici par JS -->
                </div>
              </div>
            </div>
          </div>
          
          <!-- Section Informations -->
          <div class="ajout-card">
            <div class="ajout-card__header">
              <div class="ajout-card__step">2</div>
              <div>
                <h2 class="ajout-card__title">Informations du véhicule</h2>
                <p class="ajout-card__subtitle">Modifiez les détails de votre véhicule</p>
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
                <p class="ajout-card__subtitle">Mettez à jour la description de votre véhicule</p>
              </div>
            </div>
            
            <div class="ajout-fields">
              <div class="ajout-field">
                <textarea id="description" name="description" class="ajout-textarea" rows="6" placeholder="Décrivez l'état du véhicule, les options, l'historique d'entretien, les éventuels défauts..."></textarea>
                <div class="ajout-textarea-hint">
                  <i class="fas fa-lightbulb"></i>
                  <span>Conseil : Une description détaillée attire plus d'acheteurs.</span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Bouton de soumission (mobile) -->
          <div class="ajout-submit ajout-submit--mobile">
            <button id="bouton-modifier-mobile" class="ajout-btn ajout-btn--primary" type="submit">
              <i class="fas fa-save"></i>
              <span>Enregistrer les modifications</span>
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
              <button id="bouton-modifier" class="ajout-btn ajout-btn--primary" type="submit">
                <i class="fas fa-save"></i>
                <span>Enregistrer les modifications</span>
              </button>
              
              <a id="lien-annuler" href="vehicule" class="ajout-btn ajout-btn--secondary">
                <i class="fas fa-times"></i>
                <span>Annuler</span>
              </a>
              
              <p class="ajout-submit__hint">
                <i class="fas fa-info-circle"></i>
                Les modifications seront visibles immédiatement.
              </p>
            </div>
            
            <!-- Actions supplémentaires -->
            <div class="ajout-card ajout-card--actions">
              <h4><i class="fas fa-cog"></i> Actions</h4>
              <a id="lien-voir-annonce" href="#" class="ajout-btn-link">
                <i class="fas fa-external-link-alt"></i> Voir l'annonce actuelle
              </a>
              <button id="bouton-supprimer" type="button" class="ajout-btn-link ajout-btn-link--danger">
                <i class="fas fa-trash"></i> Supprimer cette annonce
              </button>
            </div>
            
          </div>
        </div>
        
      </div>
      
    </form>
    
  </div>
</section>

<script type="module">
    import VueModificationVehicule from './assets/js/Vehicule/Modification/VueModificationVehicule.js';
    document.addEventListener('DOMContentLoaded', () => {
        new VueModificationVehicule();
    });
</script>
