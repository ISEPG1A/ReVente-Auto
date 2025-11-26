<!-- Page d'estimation -->
<section id="estimation" class="section">
  <div class="conteneur">
    <h2>Estimation de prix par IA</h2>
    <p class="description-section">Remplissez les informations ci-dessous pour obtenir une estimation instantanée de la valeur de votre véhicule grâce à notre intelligence artificielle.</p>
    
    <div class="disposition-grille">
      <!-- Formulaire d'estimation -->
      <form id="formulaire-estimation" class="formulaire" novalidate>
        <div class="grille">
          <!-- Marque -->
          <div class="champ">
            <label class="etiquette" for="est-marque">Marque</label>
            <input id="est-marque" name="marque" class="saisie" type="text" required placeholder="Ex: Peugeot" />
          </div>
          
          <!-- Modèle -->
          <div class="champ">
            <label class="etiquette" for="est-modele">Modèle</label>
            <input id="est-modele" name="modele" class="saisie" type="text" required placeholder="Ex: 208" />
          </div>
          
          <!-- Année -->
          <div class="champ">
            <label class="etiquette" for="est-annee">Année</label>
            <input id="est-annee" name="annee" class="saisie" type="number" required min="1900" max="2100" placeholder="Ex: 2019" />
          </div>
          
          <!-- Kilométrage -->
          <div class="champ">
            <label class="etiquette" for="est-km">Kilométrage</label>
            <input id="est-km" name="kilometrage" class="saisie" type="number" required min="0" placeholder="Ex: 45000" />
          </div>

          <!-- Carburant -->
          <div class="champ">
            <label class="etiquette" for="est-carburant">Carburant</label>
            <select id="est-carburant" name="carburant" class="selection" required>
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
          <div class="champ">
            <label class="etiquette" for="est-boite">Boîte de vitesse</label>
            <select id="est-boite" name="boite" class="selection" required>
              <option value="">Sélectionner...</option>
              <option value="Manuelle">Manuelle</option>
              <option value="Automatique">Automatique</option>
            </select>
          </div>

          <!-- État -->
          <div class="champ">
            <label class="etiquette" for="est-etat">État général</label>
            <select id="est-etat" name="etat" class="selection" required>
              <option value="Excellent">Excellent (Comme neuf)</option>
              <option value="Bon">Bon (Quelques traces d'usure)</option>
              <option value="Moyen">Moyen (Rayures, bosses visibles)</option>
              <option value="Mauvais">Mauvais (Réparations à prévoir)</option>
            </select>
          </div>
        </div>
        
        <div class="actions">
          <button id="bouton-estimer" class="bouton bouton--primaire" type="submit">
            <span class="texte-bouton">Estimer le prix</span>
            <span class="chargeur" hidden></span>
          </button>
        </div>
        
        <div class="messages-formulaire" aria-live="polite"></div>
      </form>

      <!-- Résultat de l'estimation -->
      <div id="resultat-estimation" class="carte-estimation" hidden>
        <div class="contenu-carte-estimation">
          <h3 class="titre-carte-estimation">Estimation du prix</h3>
          <div class="prix-carte-estimation">
            <span id="valeur-prix">---</span>
            <span class="devise">€</span>
          </div>
          <p class="info-carte-estimation">Cette estimation est basée sur les données du marché actuel fournies par l'IA.</p>
          <button id="bouton-nouvelle-estimation" class="bouton bouton--fantome">Nouvelle estimation</button>
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
