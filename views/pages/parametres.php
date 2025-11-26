<!-- Page des paramètres -->
<section class="section">
  <div class="conteneur">
    <h2>Paramètres</h2>
    
    <div id="chargement-profil" class="indicateur-chargement"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>

    <!-- Contenu si non connecté -->
    <div id="profil-connexion-requise" hidden>
        <p>Vous devez être connecté.</p>
        <p>
            <a class="bouton" href="connexion">Se connecter</a>
        </p>
    </div>

    <!-- Contenu du profil (formulaire) -->
    <div id="contenu-profil" hidden>
        <form id="formulaire-parametres" class="formulaire" enctype="multipart/form-data">
            <div class="grille">
              <div class="champ">
                <label class="etiquette" for="prenom">Prénom</label>
                <input id="prenom" name="first_name" class="saisie" type="text" required maxlength="60">
              </div>
              
              <div class="champ">
                <label class="etiquette" for="nom">Nom</label>
                <input id="nom" name="last_name" class="saisie" type="text" required maxlength="60">
              </div>
              
              <div class="champ">
                <label class="etiquette" for="telephone">Téléphone</label>
                <input id="telephone" name="phone" class="saisie" type="tel" pattern="^[0-9 +().-]{6,}$" required>
              </div>
              
              <div class="champ">
                <label class="etiquette" for="avatar">Photo de profil</label>
                <input id="avatar" name="avatar" class="saisie" type="file" accept="image/*">
              </div>
            </div>
            
            <div class="actions">
              <button class="bouton" type="submit">Enregistrer</button>
            </div>
            
            <div class="messages-formulaire" aria-live="polite" role="status"></div>
        </form>

        <hr style="border-color: var(--bordure); margin: 24px 0;">

        <div class="formulaire">
            <h3>Vérifications</h3>
            <div class="grille">
              <div>
                <p id="statut-email" class="aide">Statut email: inconnu</p>
                <button id="bouton-verifier-email" class="bouton" type="button">Vérifier email</button>
                <div id="message-verification-email" class="messages-formulaire" aria-live="polite" role="status"></div>
              </div>
              
              <div>
                <p id="statut-telephone" class="aide">Statut téléphone: inconnu</p>
                <div class="actions">
                  <button id="bouton-code-telephone" class="bouton" type="button">Recevoir code</button>
                  <input id="code-telephone" class="saisie" type="text" placeholder="Code" style="max-width:160px">
                  <button id="bouton-verifier-telephone" class="bouton bouton--fantome" type="button">Vérifier</button>
                </div>
                <div id="message-verification-telephone" class="messages-formulaire" aria-live="polite" role="status"></div>
              </div>
            </div>
        </div>

        <hr style="border-color: var(--bordure); margin: 24px 0;">

        <div>
            <h3>Supprimer mon compte</h3>
            <p>Cette action est définitive.</p>
            <button id="supprimer-compte" class="bouton bouton--danger" type="button">Supprimer</button>
        </div>
    </div>
  </div>
</section>

<script type="module">
    import VueProfil from './assets/js/Authentification/Profil/VueProfil.js';
    document.addEventListener('DOMContentLoaded', () => {
        new VueProfil();
    });
</script>
