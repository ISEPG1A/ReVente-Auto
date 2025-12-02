<!-- Page d'estimation de véhicule -->
<section id="estimation" class="section section--form">
  <div class="container">
    <h2>Estimation de prix par IA</h2>
    <p class="section__description">Remplissez les informations ci-dessous pour obtenir une estimation instantanée de la valeur de votre véhicule grâce à notre intelligence artificielle.</p>
    
    <div class="grid-layout">
      <!-- Formulaire d'estimation -->
      <form id="estimate-form" class="form" novalidate>
        <div class="grid">
          <!-- Marque -->
          <div class="field">
            <label class="label" for="est-marque">Marque</label>
            <input id="est-marque" name="marque" class="input" type="text" required placeholder="Ex: Peugeot" />
          </div>
          
          <!-- Modèle -->
          <div class="field">
            <label class="label" for="est-modele">Modèle</label>
            <input id="est-modele" name="modele" class="input" type="text" required placeholder="Ex: 208" />
          </div>
          
          <!-- Année -->
          <div class="field">
            <label class="label" for="est-annee">Année</label>
            <input id="est-annee" name="annee" class="input" type="number" required min="1900" max="2100" placeholder="Ex: 2019" />
          </div>
          
          <!-- Kilométrage -->
          <div class="field">
            <label class="label" for="est-km">Kilométrage</label>
            <input id="est-km" name="kilometrage" class="input" type="number" required min="0" placeholder="Ex: 45000" />
          </div>

          <!-- Carburant -->
          <div class="field">
            <label class="label" for="est-carburant">Carburant</label>
            <select id="est-carburant" name="carburant" class="select" required>
              <option value="">Sélectionner...</option>
              <option value="Essence">Essence</option>
              <option value="Diesel">Diesel</option>
              <option value="Hybride">Hybride</option>
              <option value="Électrique">Électrique</option>
              <option value="GPL">GPL</option>
              <option value="Ethanol">Ethanol</option>
            </select>
          </div>

          <!-- Boîte de vitesse -->
          <div class="field">
            <label class="label" for="est-boite">Boîte de vitesse</label>
            <select id="est-boite" name="boite" class="select" required>
              <option value="">Sélectionner...</option>
              <option value="Manuelle">Manuelle</option>
              <option value="Automatique">Automatique</option>
            </select>
          </div>

          <!-- État -->
          <div class="field">
            <label class="label" for="est-etat">État général</label>
            <select id="est-etat" name="etat" class="select" required>
              <option value="Excellent">Excellent (Comme neuf)</option>
              <option value="Bon">Bon (Quelques traces d'usure)</option>
              <option value="Moyen">Moyen (Rayures, bosses visibles)</option>
              <option value="Mauvais">Mauvais (Réparations à prévoir)</option>
            </select>
          </div>
        </div>
        
        <div class="actions">
          <button id="btn-estimate" class="button button--primary" type="submit">
            <span class="button__text">Estimer le prix</span>
            <span class="loader" hidden></span>
          </button>
        </div>
        
        <div class="form__messages" aria-live="polite"></div>
      </form>

      <!-- Résultat de l'estimation -->
      <div id="estimate-result" class="estimate-card" hidden>
        <div class="estimate-card__content">
          <h3 class="estimate-card__title">Estimation du prix</h3>
          <div class="estimate-card__price">
            <span id="price-value">---</span>
            <span class="currency">€</span>
          </div>
          <p class="estimate-card__info">Cette estimation est basée sur les données du marché actuel fournies par l'IA.</p>
          <button id="btn-new-estimate" class="button button--ghost">Nouvelle estimation</button>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
/* Styles spécifiques pour la page d'estimation */
.estimate-card {
  background: var(--surface-2);
  border: 1px solid var(--bordure);
  border-radius: var(--rayon-bordure);
  padding: 2rem;
  text-align: center;
  margin-top: 2rem;
  box-shadow: var(--ombre);
  animation: slideIn 0.3s ease-out;
}

.estimate-card__price {
  font-size: 3rem;
  font-weight: 700;
  color: var(--couleur-principale);
  margin: 1rem 0;
}

.estimate-card__info {
  color: var(--texte-attenue);
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Loader simple */
.loader {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 1s ease-in-out infinite;
  margin-left: 8px;
}

.loader[hidden] {
  display: none;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
