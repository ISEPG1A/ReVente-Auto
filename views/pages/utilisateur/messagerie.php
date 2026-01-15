<!-- 
Page de Messagerie Sécurisée
Design moderne avec interface de chat
-->

<!-- Section Messagerie -->
<section class="msg-main">
  <div class="conteneur">
    <div class="msg-container">
      
      <!-- Liste des conversations -->
      <div class="msg-sidebar">
        <div class="msg-sidebar__header">
          <h2><i class="fas fa-inbox"></i> Boîte de réception</h2>
          <span class="msg-sidebar__count" id="compteur-messages">0</span>
        </div>
        
        <div class="msg-sidebar__search">
          <i class="fas fa-search"></i>
          <input type="text" id="recherche-conv" placeholder="Rechercher une conversation...">
        </div>
        
        <div id="conteneur-conv" class="msg-sidebar__list">
          <div id="chargement-conv" class="msg-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Chargement des conversations...</span>
          </div>
          
          <div id="conv-vide" class="msg-empty" hidden>
            <div class="msg-empty__icon">
              <i class="far fa-comments"></i>
            </div>
            <h3>Aucune conversation</h3>
            <p>Retrouvez ici tous vos échanges avec les vendeurs et acheteurs.</p>
            <a href="galerie" class="msg-empty__btn">
              <i class="fas fa-car"></i>
              Parcourir les annonces
            </a>
          </div>
          
          <div id="liste-conv"></div>
        </div>
      </div>

      <!-- Zone de chat -->
      <div class="msg-chat">
        <div id="placeholder-chat" class="msg-placeholder">
          <div class="msg-placeholder__icon">
            <i class="far fa-paper-plane"></i>
          </div>
          <h3>Sélectionnez une conversation</h3>
          <p>Choisissez une conversation dans la liste pour commencer à discuter.</p>
          <div class="msg-placeholder__features">
            <span><i class="fas fa-check"></i> Réponse rapide</span>
            <span><i class="fas fa-check"></i> Messages chiffrés</span>
            <span><i class="fas fa-check"></i> Historique complet</span>
          </div>
        </div>

        <div id="contenu-chat" class="msg-chat__content" style="display: none;">
          <div class="msg-chat__header">
            <button class="msg-chat__back" id="btn-retour-mobile">
              <i class="fas fa-arrow-left"></i>
            </button>
            <div class="msg-chat__user">
              <div class="msg-chat__avatar">
                <i class="fas fa-user"></i>
              </div>
              <div class="msg-chat__info">
                <h3 id="nom-partenaire-chat">Utilisateur</h3>
                <span id="info-vehicule-chat" class="msg-chat__vehicle">
                  <i class="fas fa-car"></i>
                  Véhicule
                </span>
              </div>
            </div>
          </div>
          
          <div id="conteneur-messages" class="msg-chat__messages"></div>

          <form id="formulaire-message" class="msg-chat__input">
            <div class="msg-input-wrapper">
              <input type="text" id="saisie-message" placeholder="Écrivez votre message..." autocomplete="off" required>
              <button type="button" id="btn-proposition" class="msg-proposition-btn" title="Faire une proposition de prix">
                <i class="fas fa-hand-holding-usd"></i>
              </button>
              <button type="submit" class="msg-send-btn">
                <i class="fas fa-paper-plane"></i>
              </button>
            </div>
            <span class="msg-input-hint">
              <i class="fas fa-lock"></i> Message chiffré de bout en bout
            </span>
          </form>
        </div>
      </div>

    </div>
  </div>
</section>

<script type="module">
    import VueMessagerie from './assets/js/modules/utilisateur/VueMessagerie.js?v=<?= time() ?>';

    document.addEventListener('DOMContentLoaded', () => {
        window.vueMessagerie = new VueMessagerie();
    });
</script>

<!-- Modal Proposition de prix -->
<div id="modal-proposition" class="modal-proposition">
  <div class="modal-proposition__content">
    <div class="modal-proposition__header">
      <h3><i class="fas fa-hand-holding-usd"></i> Faire une proposition</h3>
      <button id="fermer-modal-proposition" class="modal-proposition__close">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <form id="formulaire-proposition" class="modal-proposition__body">
      <p class="modal-proposition__info">
        <i class="fas fa-info-circle"></i>
        Votre proposition sera valable <strong>48 heures</strong>. 
        L'autre partie pourra l'accepter ou la refuser.
      </p>
      <div class="modal-proposition__field">
        <label for="montant-proposition">Montant proposé (€)</label>
        <input 
          type="number" 
          id="montant-proposition" 
          name="montant" 
          min="1" 
          step="1" 
          placeholder="Ex: 15000"
          required
        >
      </div>
      <div class="modal-proposition__actions">
        <button type="button" class="modal-proposition__btn modal-proposition__btn--cancel" onclick="document.getElementById('modal-proposition').classList.remove('active')">
          Annuler
        </button>
        <button type="submit" class="modal-proposition__btn modal-proposition__btn--submit">
          <i class="fas fa-paper-plane"></i> Envoyer la proposition
        </button>
      </div>
    </form>
  </div>
</div>
