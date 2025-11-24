<!-- Page d'ajout de véhicule -->
<section id="ajout" class="section section--form">
  <div class="container">
    <div class="form-header">
        <h2>Vendre mon véhicule</h2>
        <p>Remplissez le formulaire ci-dessous pour publier votre annonce.</p>
    </div>
    
    <form id="vehicle-form" class="form form--card" novalidate enctype="multipart/form-data">
      
      <!-- Section Photos -->
      <div class="form-section">
        <h3>Photos</h3>
        <div class="field-upload">
            <label for="image" class="upload-area">
                <i class="fas fa-cloud-upload-alt fa-2x"></i>
                <span>Cliquez pour ajouter une photo principale</span>
                <input type="file" id="image" name="image" accept="image/*" hidden>
            </label>
            <div id="preview-container" class="preview-container" hidden>
                <img id="image-preview" src="" alt="Aperçu">
                <button type="button" id="remove-image" class="btn-remove"><i class="fas fa-times"></i></button>
            </div>
        </div>
      </div>

      <div class="form-grid-2">
          <!-- Informations principales -->
          <div class="form-column">
            <h3>Informations principales</h3>
            
            <div class="field">
              <label class="label" for="marque">Marque <span class="required">*</span></label>
              <input id="marque" name="marque" class="input" type="text" placeholder="Ex: Peugeot" required maxlength="50" />
            </div>
            
            <div class="field">
              <label class="label" for="modele">Modèle <span class="required">*</span></label>
              <input id="modele" name="modele" class="input" type="text" placeholder="Ex: 208" required maxlength="50" />
            </div>
            
            <div class="form-row">
                <div class="field">
                  <label class="label" for="annee">Année <span class="required">*</span></label>
                  <input id="annee" name="annee" class="input" type="number" min="1900" max="2100" required />
                </div>
                <div class="field">
                  <label class="label" for="prix">Prix (€) <span class="required">*</span></label>
                  <input id="prix" name="prix" class="input" type="number" min="0" step="100" required />
                </div>
            </div>
          </div>

          <!-- Détails techniques -->
          <div class="form-column">
            <h3>Détails techniques</h3>
            
            <div class="field">
              <label class="label" for="km">Kilométrage <span class="required">*</span></label>
              <input id="km" name="km" class="input" type="number" min="0" step="100" required />
            </div>

            <div class="form-row">
                <div class="field">
                  <label class="label" for="carburant">Carburant</label>
                  <select id="carburant" name="carburant" class="input select">
                    <option value="Essence">Essence</option>
                    <option value="Diesel">Diesel</option>
                    <option value="Hybride">Hybride</option>
                    <option value="Électrique">Électrique</option>
                    <option value="GPL">GPL</option>
                  </select>
                </div>
                <div class="field">
                  <label class="label" for="boite">Boîte de vitesse</label>
                  <select id="boite" name="boite" class="input select">
                    <option value="Manuelle">Manuelle</option>
                    <option value="Automatique">Automatique</option>
                  </select>
                </div>
            </div>
            
            <div class="field">
                <label class="label" for="ville">Ville <span class="required">*</span></label>
                <input id="ville" name="ville" class="input" type="text" placeholder="Ex: Paris" required />
            </div>
          </div>
      </div>

      <!-- Description -->
      <div class="form-section">
        <div class="field">
          <label class="label" for="description">Description de l'annonce</label>
          <textarea id="description" name="description" class="input textarea" rows="5" placeholder="Décrivez l'état du véhicule, les options, l'entretien..."></textarea>
        </div>
      </div>
      
      <!-- Actions -->
      <div class="actions">
        <button id="submit" class="button button--primary button--large" type="submit">
            <i class="fas fa-check"></i> Publier l'annonce
        </button>
      </div>
      
      <div class="form__messages" aria-live="polite"></div>
    </form>
  </div>
</section>

<style>
    .form--card {
        background: var(--surface-2);
        padding: 40px;
        border-radius: var(--rayon-bordure);
        border: 1px solid var(--bordure);
        max-width: 900px;
        margin: 0 auto;
    }
    
    .form-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-bottom: 30px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .form-section {
        margin-bottom: 30px;
    }
    
    .form-section h3, .form-column h3 {
        font-size: 1.1rem;
        margin-bottom: 20px;
        color: var(--couleur-principale);
        border-bottom: 1px solid var(--bordure);
        padding-bottom: 10px;
    }

    .required { color: var(--couleur-danger); }
    
    .upload-area {
        border: 2px dashed var(--bordure);
        border-radius: var(--rayon-bordure);
        padding: 40px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        color: var(--texte-attenue);
    }
    
    .upload-area:hover {
        border-color: var(--couleur-principale);
        color: var(--couleur-principale);
        background: var(--surface);
    }
    
    .preview-container {
        position: relative;
        margin-top: 20px;
        width: 100%;
        height: 300px;
        border-radius: var(--rayon-bordure);
        overflow: hidden;
        background: #000;
    }
    
    .preview-container img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    
    .btn-remove {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0,0,0,0.7);
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .button--large {
        width: 100%;
        justify-content: center;
        font-size: 1.1rem;
        padding: 15px;
    }

    @media (max-width: 768px) {
        .form-grid-2 { grid-template-columns: 1fr; }
        .form--card { padding: 20px; }
    }
</style>

<script>
    // Prévisualisation de l'image
    const inputImage = document.getElementById('image');
    const previewContainer = document.getElementById('preview-container');
    const imagePreview = document.getElementById('image-preview');
    const removeBtn = document.getElementById('remove-image');
    const uploadArea = document.querySelector('.upload-area');

    if (inputImage) {
        inputImage.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.hidden = false;
                    uploadArea.hidden = true;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            inputImage.value = '';
            previewContainer.hidden = true;
            uploadArea.hidden = false;
        });
    }
</script>
