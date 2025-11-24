<!-- Page de Messagerie Sécurisée -->
<section id="messagerie" class="section messaging-section">
    <div class="messaging-layout">
      
      <!-- Liste des conversations (Gauche) -->
      <div class="conversations-list">
        <div class="list-header">
            <h2><i class="fas fa-comments"></i> Messages</h2>
        </div>
        <div id="conv-container" class="conv-items">
            <!-- Injecté par JS -->
            <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>
        </div>
      </div>

      <!-- Zone de chat (Droite) -->
      <div class="chat-area">
        <div id="chat-placeholder" class="chat-placeholder">
            <i class="far fa-paper-plane fa-4x"></i>
            <h3>Sélectionnez une conversation</h3>
            <p>Vos échanges sont chiffrés de bout en bout.</p>
        </div>

        <div id="chat-content" class="chat-content" style="display: none;">
            <div class="chat-header">
                <div class="chat-user-info">
                    <div class="avatar-circle"><i class="fas fa-user"></i></div>
                    <div>
                        <h3 id="chat-partner-name">Utilisateur</h3>
                        <span id="chat-vehicle-info" class="vehicle-badge">Véhicule</span>
                    </div>
                </div>
            </div>
            
            <div id="messages-container" class="messages-container">
                <!-- Messages injectés ici -->
            </div>

            <form id="message-form" class="message-input-area">
                <input type="text" id="message-input" placeholder="Écrivez votre message..." autocomplete="off" required>
                <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
      </div>

    </div>
</section>

<style>
    /* === Layout Plein Écran (App-like) === */
    
    /* Bloquer le scroll sur le body */
    body {
        height: 100vh;
        overflow: hidden;
    }

    /* Adapter le conteneur principal */
    .site-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding: 0 !important;
        max-width: none !important;
        width: 100%;
    }

    /* Cacher le footer sur cette page */
    .site-footer {
        display: none !important;
    }

    /* Section messagerie prenant tout l'espace */
    .messaging-section {
        flex: 1;
        height: 100%;
        padding: 0 !important;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .messaging-layout {
        flex: 1;
        display: grid;
        grid-template-columns: 350px 1fr;
        background: var(--surface-2);
        overflow: hidden;
        position: relative;
        border: none;
        border-radius: 0;
    }

    /* Liste Conversations */
    .conversations-list {
        border-right: 1px solid var(--bordure);
        display: flex;
        flex-direction: column;
        background: var(--surface);
        height: 100%;
        overflow: hidden;
    }

    .list-header {
        padding: 20px;
        border-bottom: 1px solid var(--bordure);
    }
    .list-header h2 { margin: 0; font-size: 1.2rem; }

    .conv-items {
        flex: 1;
        overflow-y: auto;
    }

    .conv-item {
        padding: 15px 20px;
        border-bottom: 1px solid var(--bordure);
        cursor: pointer;
        transition: background 0.2s;
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .conv-item:hover { background: var(--surface-2); }
    .conv-item.active { background: rgba(255, 107, 107, 0.1); border-left: 4px solid var(--couleur-principale); }

    .conv-avatar {
        width: 40px; height: 40px;
        background: var(--bordure);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: var(--texte-attenue);
    }

    .conv-info h4 { margin: 0 0 5px 0; font-size: 1rem; color: var(--texte); }
    .conv-info p { margin: 0; font-size: 0.85rem; color: var(--texte-attenue); }

    /* Zone Chat */
    .chat-area {
        display: flex;
        flex-direction: column;
        background: var(--surface-2);
        position: relative;
        height: 100%;
        overflow: hidden;
        min-width: 0;
    }

    .chat-placeholder {
        height: 100%;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--texte-attenue);
        text-align: center;
        position: absolute;
        top: 0; left: 0;
        z-index: 1;
    }

    .chat-content {
        display: flex;
        flex-direction: column;
        height: 100%;
        width: 100%;
        overflow: hidden;
        position: relative;
        z-index: 2;
    }

    .chat-header {
        padding: 15px 20px;
        border-bottom: 1px solid var(--bordure);
        background: var(--surface);
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        z-index: 10;
        flex-shrink: 0;
    }

    .chat-user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .avatar-circle {
        width: 42px; height: 42px;
        background: linear-gradient(135deg, var(--couleur-principale), #4a69bd);
        color: white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .vehicle-badge {
        font-size: 0.75rem;
        background: var(--surface-2);
        padding: 3px 10px;
        border-radius: 12px;
        color: var(--texte-attenue);
        border: 1px solid var(--bordure);
        display: inline-block;
        margin-top: 2px;
    }

    .messages-container {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        min-height: 0;
    }

    .message {
        max-width: 70%;
        padding: 12px 16px;
        border-radius: 18px;
        position: relative;
        font-size: 0.95rem;
        line-height: 1.5;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .message.received {
        align-self: flex-start;
        background: var(--surface);
        border: 1px solid var(--bordure);
        border-bottom-left-radius: 4px;
        color: var(--texte);
    }

    .message.sent {
        align-self: flex-end;
        background: linear-gradient(135deg, var(--couleur-principale), #4a69bd);
        color: white;
        border-bottom-right-radius: 4px;
        border: none;
    }
    
    .message-time {
        font-size: 0.65rem;
        opacity: 0.7;
        margin-top: 4px;
        display: block;
        text-align: right;
    }

    .message-input-area {
        padding: 20px;
        background: var(--surface);
        border-top: 1px solid var(--bordure);
        display: flex;
        gap: 12px;
        align-items: center;
        flex-shrink: 0;
        z-index: 20;
        position: relative;
        min-height: 80px; /* Ensure minimum height */
    }

    #message-input {
        flex: 1;
        padding: 14px 20px;
        border-radius: 25px;
        border: 1px solid var(--bordure);
        background: var(--surface-2);
        color: var(--texte);
        font-size: 0.95rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    
    #message-input:focus {
        outline: none;
        border-color: var(--couleur-principale);
        box-shadow: 0 0 0 3px rgba(var(--couleur-principale-rgb), 0.1);
    }

    .btn-send {
        width: 48px; height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--couleur-principale), #4a69bd);
        color: white;
        border: none;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .btn-send:hover { 
        transform: scale(1.05); 
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    /* Styles pour l'état vide de la liste des conversations */
    .empty-conv-state {
        padding: 40px 20px;
        text-align: center;
        color: var(--texte-attenue);
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .empty-icon-circle {
        width: 60px; height: 60px;
        background: var(--surface-2);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 15px;
        color: var(--couleur-principale);
    }

    .empty-conv-state h3 {
        color: var(--texte);
        margin: 0 0 10px 0;
        font-size: 1.1rem;
    }
    
    .empty-conv-state p {
        margin: 0;
        line-height: 1.5;
    }

    .bouton--sm {
        padding: 8px 16px;
        font-size: 0.85rem;
    }

    @media (max-width: 768px) {
        .messaging-layout { grid-template-columns: 1fr; }
        .conversations-list { display: none; } /* Mobile logic to be improved */
        .conversations-list.active { display: flex; width: 100%; }
        .chat-area { display: none; }
        .chat-area.active { display: flex; }
    }
</style>

<script type="module">
    import { echapperHTML } from './assets/js/app.js';

    const metaApiBase = document.querySelector('meta[name="api-base"]');
    const baseUrl = metaApiBase ? metaApiBase.getAttribute('content') : './api';
    const URL_MSG = baseUrl + '/messages.php';
    const URL_AUTH = baseUrl + '/auth.php?action=me';

    let currentUserId = null;
    let currentConvId = null;
    let refreshInterval = null;
    let isFirstLoad = true;

    // Initialisation
    async function init() {
        // Vérifier auth
        const res = await fetch(URL_AUTH);
        const data = await res.json();
        if (!data.user) {
            window.location.href = 'connexion';
            return;
        }
        currentUserId = Number(data.user.id);

        // Charger conversations
        await chargerConversations();

        // Si paramètre URL (redirection depuis annonce)
        const params = new URLSearchParams(window.location.search);
        if (params.has('vehicle_id') && params.has('seller_id')) {
            creerOuOuvrirConversation(params.get('vehicle_id'), params.get('seller_id'));
        }
    }

    async function chargerConversations() {
        const container = document.getElementById('conv-container');
        try {
            const res = await fetch(URL_MSG);
            const convs = await res.json();
            
            container.innerHTML = '';
            if (convs.length === 0) {
                container.innerHTML = `
                    <div class="empty-conv-state">
                        <div class="empty-icon-circle">
                            <i class="far fa-comments fa-2x"></i>
                        </div>
                        <h3>Aucune conversation</h3>
                        <p>Retrouvez ici tous vos échanges.</p>
                        <p style="font-size: 0.85rem; margin-top: 10px;">Pour démarrer une discussion, allez sur une annonce et cliquez sur "Envoyer un message".</p>
                        <a href="galerie" class="bouton bouton--sm" style="margin-top: 15px;">Parcourir les annonces</a>
                    </div>
                `;
                return;
            }

            convs.forEach(c => {
                const div = document.createElement('div');
                div.className = `conv-item ${currentConvId === c.id ? 'active' : ''}`;
                div.onclick = () => ouvrirConversation(c);
                div.innerHTML = `
                    <div class="conv-avatar"><i class="fas fa-user"></i></div>
                    <div class="conv-info">
                        <h4>${echapperHTML(c.other_user_name)}</h4>
                        <p>${c.marque ? echapperHTML(c.marque + ' ' + c.modele) : 'Véhicule supprimé'}</p>
                    </div>
                `;
                container.appendChild(div);
            });
        } catch (e) {
            console.error(e);
        }
    }

    async function creerOuOuvrirConversation(vehicleId, sellerId) {
        try {
            const res = await fetch(URL_MSG, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'create_conv',
                    vehicle_id: vehicleId,
                    seller_id: sellerId
                })
            });
            const data = await res.json();
            if (data.id) {
                // Recharger la liste et ouvrir
                await chargerConversations();
                // Trouver l'objet conv dans la liste (ou refetch)
                // Pour simplifier, on simule un clic sur le premier élément qui correspondrait
                // Idéalement, on recharge proprement.
                const convItems = document.querySelectorAll('.conv-item');
                // On suppose que c'est la dernière modifiée, donc la première de la liste
                if(convItems.length > 0) convItems[0].click();
            } else {
                alert(data.error || 'Erreur création conversation');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function ouvrirConversation(conv) {
        currentConvId = conv.id;
        isFirstLoad = true;
        
        // UI Update
        document.querySelectorAll('.conv-item').forEach(el => el.classList.remove('active'));
        
        document.getElementById('chat-placeholder').style.display = 'none';
        
        const chatContent = document.getElementById('chat-content');
        chatContent.style.display = 'flex';
        
        document.getElementById('chat-partner-name').textContent = conv.other_user_name;
        document.getElementById('chat-vehicle-info').textContent = conv.marque ? `${conv.marque} ${conv.modele}` : 'Annonce supprimée';

        await chargerMessages();
        
        // Auto refresh
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(chargerMessages, 5000);
    }

    async function chargerMessages() {
        if (!currentConvId) return;
        const container = document.getElementById('messages-container');
        
        // Détection si l'utilisateur est en bas (avec une marge de tolérance)
        const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;

        try {
            const res = await fetch(`${URL_MSG}?conversation_id=${currentConvId}`);
            const data = await res.json();
            
            if (data.error) return;

            // Mettre à jour le badge global si on lit les messages
            if (window.mettreAJourBadgeMessages) window.mettreAJourBadgeMessages();

            // Sauvegarde de la position de scroll si on n'est pas en bas
            const previousScrollHeight = container.scrollHeight;
            const previousScrollTop = container.scrollTop;

            // On vide tout et on remet (pas optimal mais simple)
            container.innerHTML = '';
            
            data.messages.forEach(m => {
                const div = document.createElement('div');
                const isMe = (Number(m.sender_id) === currentUserId);
                div.className = `message ${isMe ? 'sent' : 'received'}`;
                
                // Contenu déchiffré
                div.innerHTML = `
                    ${echapperHTML(m.content_clear)}
                    <span class="message-time">${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                `;
                
                // Si c'est moi qui ai envoyé, et que le contenu est le placeholder "Message chiffré envoyé"
                if (isMe && m.content_clear.includes('[Message ancien')) {
                    div.classList.add('system');
                    div.innerHTML = `<i class="fas fa-lock"></i> Ancien message chiffré (illisible)`;
                } else if (m.content_clear === '[Erreur de déchiffrement]') {
                    div.classList.add('system');
                    div.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Erreur de déchiffrement`;
                }

                container.appendChild(div);
            });
            
            // Gestion du scroll
            if (isFirstLoad) {
                container.scrollTop = container.scrollHeight;
                isFirstLoad = false;
            } else if (isAtBottom) {
                container.scrollTop = container.scrollHeight;
            } else {
                // Si on n'était pas en bas, on essaie de maintenir la position relative
                // (Optionnel : container.scrollTop = previousScrollTop;)
                // Mais comme on remplace tout le contenu, le navigateur risque de remettre à 0 ou de garder la même valeur numérique.
                // Si le contenu a changé (nouveaux messages), garder la même valeur numérique est correct pour "rester au même endroit" par rapport au haut.
                container.scrollTop = previousScrollTop;
            }
            
        } catch (e) {
            console.error(e);
        }
    }

    // Envoi message
    document.getElementById('message-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('message-input');
        const content = input.value.trim();
        if (!content || !currentConvId) return;

        try {
            const res = await fetch(URL_MSG, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    conversation_id: currentConvId,
                    content: content
                })
            });
            
            if (res.ok) {
                input.value = '';
                // Force le scroll en bas après envoi
                const container = document.getElementById('messages-container');
                container.scrollTop = container.scrollHeight;
                // On recharge pour afficher le message envoyé (avec délai court pour laisser le temps au serveur)
                setTimeout(chargerMessages, 100);
            } else {
                alert('Erreur envoi');
            }
        } catch (e) {
            console.error(e);
        }
    });

    document.addEventListener('DOMContentLoaded', init);
</script>
