<!-- Page d'ajout de véhicule -->
<section id="ajout" class="section section--formulaire">
  <div class="conteneur">
    <div class="entete-formulaire">
        <h2>Vendre mon véhicule</h2>
        <p>Remplissez le formulaire ci-dessous pour publier votre annonce.</p>
    </div>
    
    <form id="formulaire-vehicule" class="formulaire formulaire--carte" novalidate enctype="multipart/form-data">
      
      <!-- Section Photos -->
      <div class="section-formulaire">
        <h3>Photos</h3>
        <div class="champ-telechargement">
            <label for="image" class="zone-telechargement">
                <i class="fas fa-cloud-upload-alt fa-2x"></i>
                <span>Cliquez pour ajouter une photo principale</span>
                <input type="file" id="image" name="image" accept="image/*" hidden>
            </label>
            <div id="conteneur-apercu" class="conteneur-apercu" hidden>
                <img id="apercu-image" src="" alt="Aperçu">
                <button type="button" id="bouton-supprimer-image" class="bouton-supprimer"><i class="fas fa-times"></i></button>
            </div>
        </div>
      </div>

      <div class="grille-formulaire-2">
          <!-- Informations principales -->
          <div class="colonne-formulaire">
            <h3>Informations principales</h3>
            
            <div class="champ">
              <label class="etiquette" for="marque">Marque <span class="requis">*</span></label>
              <input id="marque" name="marque" class="saisie" type="text" placeholder="Ex: Peugeot" required maxlength="50" />
            </div>
            
            <div class="champ">
              <label class="etiquette" for="modele">Modèle <span class="requis">*</span></label>
              <input id="modele" name="modele" class="saisie" type="text" placeholder="Ex: 208" required maxlength="50" />
            </div>
            
            <div class="rangee-formulaire">
                <div class="champ">
                  <label class="etiquette" for="annee">Année <span class="requis">*</span></label>
                  <input id="annee" name="annee" class="saisie" type="number" min="1900" max="2100" required />
                </div>
                <div class="champ">
                  <label class="etiquette" for="prix">Prix (€) <span class="requis">*</span></label>
                  <input id="prix" name="prix" class="saisie" type="number" min="0" step="100" required />
                </div>
            </div>
          </div>

          <!-- Détails techniques -->
          <div class="colonne-formulaire">
            <h3>Détails techniques</h3>
            
            <div class="champ">
              <label class="etiquette" for="km">Kilométrage <span class="requis">*</span></label>
              <input id="km" name="km" class="saisie" type="number" min="0" step="100" required />
            </div>

            <div class="rangee-formulaire">
                <div class="champ">
                  <label class="etiquette" for="carburant">Carburant</label>
                  <select id="carburant" name="carburant" class="saisie selection">
                    <option value="Essence">Essence</option>
                    <option value="Diesel">Diesel</option>
                    <option value="Hybride">Hybride</option>
                    <option value="Électrique">Électrique</option>
                    <option value="GPL">GPL</option>
                  </select>
                </div>
                <div class="champ">
                  <label class="etiquette" for="boite">Boîte de vitesse</label>
                  <select id="boite" name="boite" class="saisie selection">
                    <option value="Manuelle">Manuelle</option>
                    <option value="Automatique">Automatique</option>
                  </select>
                </div>
            </div>
            
            <div class="champ">
                <label class="etiquette" for="ville">Ville <span class="requis">*</span></label>
                <input id="ville" name="ville" class="saisie" type="text" placeholder="Ex: Paris" required />
            </div>
          </div>
      </div>

      <!-- Description -->
      <div class="section-formulaire">
        <div class="champ">
          <label class="etiquette" for="description">Description de l'annonce</label>
          <textarea id="description" name="description" class="saisie zone-texte" rows="5" placeholder="Décrivez l'état du véhicule, les options, l'entretien..."></textarea>
        </div>
      </div>
      
      <!-- Actions -->
      <div class="actions">
        <button id="bouton-soumettre" class="bouton bouton--primaire bouton--large" type="submit">
            <i class="fas fa-check"></i> Publier l'annonce
        </button>
      </div>
      
      <div class="messages-formulaire" aria-live="polite"></div>
    </form>
  </div>
</section>



<script type="module">
    import VueAjoutVehicule from './assets/js/Vehicule/Ajout/VueAjoutVehicule.js';
    document.addEventListener('DOMContentLoaded', () => {
        new VueAjoutVehicule();
    });
</script>
