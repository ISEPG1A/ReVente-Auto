<!-- 
Page d'estimation par IA
Design moderne avec hero, formulaire interactif et résultat animé
-->

<!-- Hero Section -->
<section class="estimation-hero">
  <div class="estimation-hero__shapes">
    <div class="estimation-shape estimation-shape--1"></div>
    <div class="estimation-shape estimation-shape--2"></div>
    <div class="estimation-shape estimation-shape--3"></div>
  </div>
  
  <div class="conteneur">
    <div class="estimation-hero__content">
      <div class="estimation-hero__badge">
        <i class="fas fa-robot"></i>
        <span>Propulsé par l'IA</span>
      </div>
      <h1 class="estimation-hero__title">
        Estimez la valeur de votre <span>véhicule</span>
      </h1>
      <p class="estimation-hero__description">
        Notre intelligence artificielle analyse les données du marché en temps réel pour vous fournir une estimation précise et fiable.
      </p>
      
      <div class="estimation-hero__features">
        <div class="estimation-feature">
          <i class="fas fa-bolt"></i>
          <span>Résultat instantané</span>
        </div>
        <div class="estimation-feature">
          <i class="fas fa-chart-line"></i>
          <span>Données actualisées</span>
        </div>
        <div class="estimation-feature">
          <i class="fas fa-shield-alt"></i>
          <span>100% gratuit</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Section principale -->
<section class="estimation-main">
  <div class="conteneur">
    
    <div class="estimation-layout">
      
      <!-- Colonne gauche : Formulaire -->
      <div class="estimation-form-container">
        <div class="estimation-card estimation-card--form">
          <div class="estimation-card__header">
            <div class="estimation-card__icon">
              <i class="fas fa-car"></i>
            </div>
            <div>
              <h2 class="estimation-card__title">Informations du véhicule</h2>
              <p class="estimation-card__subtitle">Remplissez tous les champs pour une estimation précise</p>
            </div>
          </div>
          
          <form id="formulaire-estimation" class="estimation-form" novalidate>
            
            <!-- Marque et Modèle -->
            <div class="estimation-form__row">
              <div class="estimation-field">
                <label class="estimation-label" for="est-marque">
                  <i class="fas fa-industry"></i> Marque
                </label>
                <input id="est-marque" name="marque" class="estimation-input" type="text" required placeholder="Ex: Peugeot" />
              </div>
              
              <div class="estimation-field">
                <label class="estimation-label" for="est-modele">
                  <i class="fas fa-car-side"></i> Modèle
                </label>
                <input id="est-modele" name="modele" class="estimation-input" type="text" required placeholder="Ex: 208" />
              </div>
            </div>
            
            <!-- Année et Kilométrage -->
            <div class="estimation-form__row">
              <div class="estimation-field">
                <label class="estimation-label" for="est-annee">
                  <i class="fas fa-calendar-alt"></i> Année
                </label>
                <input id="est-annee" name="annee" class="estimation-input" type="number" required min="1900" max="2100" placeholder="Ex: 2019" />
              </div>
              
              <div class="estimation-field">
                <label class="estimation-label" for="est-km">
                  <i class="fas fa-tachometer-alt"></i> Kilométrage
                </label>
                <div class="estimation-input-group">
                  <input id="est-km" name="kilometrage" class="estimation-input" type="number" required min="0" placeholder="Ex: 45000" />
                  <span class="estimation-input-suffix">km</span>
                </div>
              </div>
            </div>
            
            <!-- Carburant et Boîte -->
            <div class="estimation-form__row">
              <div class="estimation-field">
                <label class="estimation-label" for="est-carburant">
                  <i class="fas fa-gas-pump"></i> Carburant
                </label>
                <select id="est-carburant" name="carburant" class="estimation-select" required>
                  <option value="">Sélectionner...</option>
                  <option value="Essence">🔴 Essence</option>
                  <option value="Diesel">⚫ Diesel</option>
                  <option value="Hybride">🟢 Hybride</option>
                  <option value="Électrique">🔵 Électrique</option>
                  <option value="GPL">🟡 GPL</option>
                  <option value="Ethanol">🟠 Ethanol</option>
                </select>
              </div>
              
              <div class="estimation-field">
                <label class="estimation-label" for="est-boite">
                  <i class="fas fa-cog"></i> Boîte de vitesse
                </label>
                <select id="est-boite" name="boite" class="estimation-select" required>
                  <option value="">Sélectionner...</option>
                  <option value="Manuelle">⚙️ Manuelle</option>
                  <option value="Automatique">🅰️ Automatique</option>
                </select>
              </div>
            </div>
            
            <!-- État général -->
            <div class="estimation-field estimation-field--full">
              <label class="estimation-label" for="est-etat">
                <i class="fas fa-star"></i> État général
              </label>
              <div class="estimation-state-options">
                <label class="estimation-state-option">
                  <input type="radio" name="etat" value="Excellent" checked>
                  <span class="estimation-state-card">
                    <i class="fas fa-star"></i>
                    <strong>Excellent</strong>
                    <small>Comme neuf</small>
                  </span>
                </label>
                <label class="estimation-state-option">
                  <input type="radio" name="etat" value="Bon">
                  <span class="estimation-state-card">
                    <i class="fas fa-thumbs-up"></i>
                    <strong>Bon</strong>
                    <small>Légère usure</small>
                  </span>
                </label>
                <label class="estimation-state-option">
                  <input type="radio" name="etat" value="Moyen">
                  <span class="estimation-state-card">
                    <i class="fas fa-minus-circle"></i>
                    <strong>Moyen</strong>
                    <small>Usure visible</small>
                  </span>
                </label>
                <label class="estimation-state-option">
                  <input type="radio" name="etat" value="Mauvais">
                  <span class="estimation-state-card">
                    <i class="fas fa-tools"></i>
                    <strong>Mauvais</strong>
                    <small>À réparer</small>
                  </span>
                </label>
              </div>
            </div>
            
            <div class="estimation-form__actions">
              <button id="bouton-estimer" class="estimation-btn estimation-btn--primary" type="submit">
                <i class="fas fa-calculator"></i>
                <span class="texte-bouton">Estimer mon véhicule</span>
                <span class="chargeur" hidden></span>
              </button>
            </div>
            
            <div class="messages-formulaire" aria-live="polite"></div>
          </form>
        </div>
      </div>
      
      <!-- Colonne droite : Résultat -->
      <div class="estimation-result-container">
        
        <!-- État initial : En attente -->
        <div id="estimation-waiting" class="estimation-card estimation-card--waiting">
          <div class="estimation-waiting__icon">
            <i class="fas fa-magic"></i>
          </div>
          <h3>Prêt à estimer</h3>
          <p>Remplissez le formulaire et laissez notre IA calculer la valeur de votre véhicule.</p>
          
          <div class="estimation-waiting__steps">
            <div class="estimation-step">
              <div class="estimation-step__number">1</div>
              <span>Renseignez les informations</span>
            </div>
            <div class="estimation-step">
              <div class="estimation-step__number">2</div>
              <span>L'IA analyse le marché</span>
            </div>
            <div class="estimation-step">
              <div class="estimation-step__number">3</div>
              <span>Obtenez votre estimation</span>
            </div>
          </div>
        </div>
        
        <!-- Résultat de l'estimation -->
        <div id="resultat-estimation" class="estimation-card estimation-card--result" hidden>
          <div class="estimation-result__badge">
            <i class="fas fa-check-circle"></i>
            <span>Estimation terminée</span>
          </div>
          
          <div class="estimation-result__vehicle">
            <span id="resultat-vehicule">Véhicule</span>
          </div>
          
          <div class="estimation-result__price">
            <span class="estimation-result__label">Valeur estimée</span>
            <div class="estimation-result__value">
              <span id="valeur-prix">---</span>
              <span class="estimation-result__currency">€</span>
            </div>
          </div>
          
          <div class="estimation-result__range">
            <div class="estimation-range">
              <span class="estimation-range__min" id="prix-min">-- €</span>
              <div class="estimation-range__bar">
                <div class="estimation-range__fill"></div>
                <div class="estimation-range__marker"></div>
              </div>
              <span class="estimation-range__max" id="prix-max">-- €</span>
            </div>
            <p class="estimation-range__info">Fourchette de prix selon l'état et le marché</p>
          </div>
          
          <div class="estimation-result__details">
            <div class="estimation-detail">
              <i class="fas fa-chart-line"></i>
              <div>
                <strong>Tendance marché</strong>
                <span id="tendance-marche">Stable</span>
              </div>
            </div>
            <div class="estimation-detail">
              <i class="fas fa-clock"></i>
              <div>
                <strong>Temps de vente estimé</strong>
                <span id="temps-vente">2-4 semaines</span>
              </div>
            </div>
          </div>
          
          <div class="estimation-result__actions">
            <button id="bouton-nouvelle-estimation" class="estimation-btn estimation-btn--secondary">
              <i class="fas fa-redo"></i>
              <span>Nouvelle estimation</span>
            </button>
            <a href="ajout_vehicule" class="estimation-btn estimation-btn--primary">
              <i class="fas fa-plus"></i>
              <span>Publier une annonce</span>
            </a>
          </div>
          
          <div class="estimation-result__disclaimer">
            <i class="fas fa-info-circle"></i>
            <p>
              <strong>Avertissement :</strong> Cette estimation est générée par une intelligence artificielle à titre indicatif uniquement. 
              Elle peut contenir des erreurs et ne constitue pas une évaluation officielle. 
              Nous vous recommandons de consulter plusieurs sources et/ou un professionnel avant toute transaction.
            </p>
          </div>
        </div>
        
        <!-- Erreur d'estimation -->
        <div id="estimation-error" class="estimation-card estimation-card--error" hidden>
          <div class="estimation-error__icon">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          
          <h3 class="estimation-error__title">Estimation impossible</h3>
          
          <div class="estimation-error__vehicle">
            <span id="erreur-vehicule">Véhicule</span>
          </div>
          
          <p class="estimation-error__message">
            Notre service n'a pas pu estimer la valeur de ce véhicule.
          </p>
          
          <div class="estimation-error__causes">
            <h4><i class="fas fa-question-circle"></i> Causes possibles :</h4>
            <ul>
              <li><i class="fas fa-times-circle"></i> Le véhicule n'existe pas ou la combinaison marque/modèle est incorrecte</li>
              <li><i class="fas fa-times-circle"></i> L'année saisie ne correspond pas à ce modèle</li>
              <li><i class="fas fa-times-circle"></i> Le véhicule est trop ancien ou trop rare pour être estimé</li>
              <li><i class="fas fa-times-circle"></i> Erreur de saisie dans les informations (faute de frappe)</li>
            </ul>
          </div>
          
          <div class="estimation-error__tips">
            <h4><i class="fas fa-lightbulb"></i> Conseils :</h4>
            <ul>
              <li><i class="fas fa-check"></i> Vérifiez l'orthographe de la marque et du modèle</li>
              <li><i class="fas fa-check"></i> Assurez-vous que l'année correspond bien au modèle</li>
              <li><i class="fas fa-check"></i> Essayez avec le nom complet du modèle (ex: "308 GT Line")</li>
            </ul>
          </div>
          
          <div class="estimation-error__actions">
            <button id="bouton-reessayer" class="estimation-btn estimation-btn--primary">
              <i class="fas fa-redo"></i>
              <span>Réessayer</span>
            </button>
          </div>
        </div>
        
      </div>
      
    </div>
    
  </div>
</section>

<script type="module">
    import VueEstimation from './assets/js/Estimation/VueEstimation.js';
    document.addEventListener('DOMContentLoaded', () => {
        new VueEstimation();
    });
</script>
