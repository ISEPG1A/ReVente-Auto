<!-- Page de Messagerie Sécurisée -->
<section id="messagerie" class="section section-messagerie">
    <div class="mise-en-page-messagerie">
      
      <div class="liste-conversations">
        <div class="entete-liste">
            <h2><i class="fas fa-comments"></i> Messages</h2>
        </div>
        <div id="conteneur-conv" class="elements-conv">
            <div id="chargement-conv" class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>
            
            <div id="conv-vide" class="etat-conv-vide" hidden>
                <div class="cercle-icone-vide">
                    <i class="far fa-comments fa-2x"></i>
                </div>
                <h3>Aucune conversation</h3>
                <p>Retrouvez ici tous vos échanges.</p>
                <p style="font-size: 0.85rem; margin-top: 10px;">Pour démarrer une discussion, allez sur une annonce et cliquez sur "Envoyer un message".</p>
                <a href="galerie" class="bouton bouton--sm" style="margin-top: 15px;">Parcourir les annonces</a>
            </div>
            
            <div id="liste-conv"></div>
        </div>
      </div>

      <div class="zone-chat">
        <div id="placeholder-chat" class="placeholder-chat">
            <i class="far fa-paper-plane fa-4x"></i>
            <h3>Sélectionnez une conversation</h3>
            <p>Vos échanges sont chiffrés de bout en bout.</p>
        </div>

        <div id="contenu-chat" class="contenu-chat" style="display: none;">
            <div class="entete-chat">
                <div class="info-utilisateur-chat">
                    <div class="cercle-avatar"><i class="fas fa-user"></i></div>
                    <div>
                        <h3 id="nom-partenaire-chat">Utilisateur</h3>
                        <span id="info-vehicule-chat" class="badge-vehicule">Véhicule</span>
                    </div>
                </div>
            </div>
            
            <div id="conteneur-messages" class="conteneur-messages"></div>

            <form id="formulaire-message" class="zone-saisie-message">
                <input type="text" id="saisie-message" placeholder="Écrivez votre message..." autocomplete="off" required>
                <button type="submit" class="bouton-envoyer"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
      </div>

    </div>
</section>



<script type="module">
    import VueMessagerie from './assets/js/Messagerie/VueMessagerie.js';

    document.addEventListener('DOMContentLoaded', () => {
        new VueMessagerie();
    });
</script>
