<!-- 
Page de Messagerie Sécurisée
Design moderne avec hero et interface de chat améliorée
-->

<!-- Hero Section -->
<section class="msg-hero">
  <div class="msg-hero__shapes">
    <div class="msg-shape msg-shape--1"></div>
    <div class="msg-shape msg-shape--2"></div>
    <div class="msg-shape msg-shape--3"></div>
  </div>
  
  <div class="conteneur">
    <div class="msg-hero__content">
      <div class="msg-hero__badge">
        <i class="fas fa-lock"></i>
        <span>Messagerie sécurisée</span>
      </div>
      <h1 class="msg-hero__title">
        Vos <span>conversations</span>
      </h1>
      <p class="msg-hero__description">
        Échangez en toute sécurité avec les vendeurs et acheteurs. Vos messages sont chiffrés de bout en bout.
      </p>
      
      <div class="msg-hero__features">
        <div class="msg-feature">
          <i class="fas fa-shield-alt"></i>
          <span>Chiffrement E2E</span>
        </div>
        <div class="msg-feature">
          <i class="fas fa-bell"></i>
          <span>Notifications</span>
        </div>
        <div class="msg-feature">
          <i class="fas fa-history"></i>
          <span>Historique sauvegardé</span>
        </div>
      </div>
    </div>
  </div>
</section>

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
            <div class="msg-chat__actions">
            </div>
          </div>
          
          <div id="conteneur-messages" class="msg-chat__messages"></div>

          <form id="formulaire-message" class="msg-chat__input">
            <div class="msg-input-wrapper">
              <input type="text" id="saisie-message" placeholder="Écrivez votre message..." autocomplete="off" required>
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
    import VueMessagerie from './assets/js/Messagerie/VueMessagerie.js';

    document.addEventListener('DOMContentLoaded', () => {
        new VueMessagerie();
    });
</script>
