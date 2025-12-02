import { echapperHTML, obtenirUrlApi } from '../app.js';

export default class VueMessagerie {
    constructor() {
        this.URL_API = obtenirUrlApi('/messagerie');
        
        this.idUtilisateurCourant = null;
        this.idConvCourante = null;
        this.intervalleRafraichissement = null;
        this.estPremierChargement = true;

        this.initialiser();
    }

    async initialiser() {
        // Vérifier auth via le contrôleur de messagerie
        const res = await fetch(`${this.URL_API}?action=infos_utilisateur`);
        if (!res.ok) {
            window.location.href = 'connexion';
            return;
        }
        const data = await res.json();
        this.idUtilisateurCourant = Number(data.utilisateur.id);

        await this.chargerConversations();

        const params = new URLSearchParams(window.location.search);
        if (params.has('vehicle_id') && params.has('seller_id')) {
            this.creerOuOuvrirConversation(params.get('vehicle_id'), params.get('seller_id'));
        }

        const formulaire = document.getElementById('formulaire-message');
        if (formulaire) {
            formulaire.addEventListener('submit', (e) => this.gererEnvoiMessage(e));
        }
    }

    async chargerConversations() {
        const conteneurListe = document.getElementById('liste-conv');
        const elChargement = document.getElementById('chargement-conv');
        const elVide = document.getElementById('conv-vide');

        if (!conteneurListe) return;

        try {
            if (elChargement) elChargement.hidden = false;
            if (elVide) {
                elVide.hidden = true;
                elVide.style.display = 'none';
            }
            conteneurListe.innerHTML = '';

            const res = await fetch(this.URL_API);
            if (!res.ok) throw new Error('Erreur réseau');
            const convs = await res.json();
            
            if (elChargement) elChargement.hidden = true;

            if (convs.length === 0) {
                if (elVide) {
                    elVide.hidden = false;
                    elVide.style.display = 'flex';
                }
                return;
            }

            convs.forEach(c => {
                const div = document.createElement('div');
                div.className = `element-conv ${this.idConvCourante === c.id ? 'active' : ''}`;
                div.onclick = () => this.ouvrirConversation(c);
                
                const avatarHtml = c.avatar_autre_utilisateur 
                    ? `<img src="${echapperHTML(c.avatar_autre_utilisateur)}" alt="Avatar" class="avatar-conv-img">`
                    : `<div class="avatar-conv"><i class="fas fa-user"></i></div>`;

                div.innerHTML = `
                    ${avatarHtml}
                    <div class="info-conv">
                        <h4>${echapperHTML(c.nom_autre_utilisateur)}</h4>
                        <p>${c.marque ? echapperHTML(c.marque + ' ' + c.modele) : 'Véhicule supprimé'}</p>
                    </div>
                `;
                conteneurListe.appendChild(div);
            });
        } catch (e) {
            console.error(e);
            if (elChargement) elChargement.hidden = true;
            conteneurListe.innerHTML = `<div class="msg msg--err" style="margin: 20px;">Impossible de charger les conversations.</div>`;
        }
    }

    async creerOuOuvrirConversation(idVehicule, idVendeur) {
        try {
            const res = await fetch(this.URL_API, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'creer_conv',
                    id_vehicule: idVehicule,
                    id_vendeur: idVendeur
                })
            });
            const data = await res.json();
            if (data.id) {
                await this.chargerConversations();
                setTimeout(() => {
                     // On cherche dans le nouveau conteneur
                     const elementsConv = document.querySelectorAll('#liste-conv .element-conv');
                     if(elementsConv.length > 0) elementsConv[0].click();
                }, 100);
            } else {
                alert(data.erreur || 'Erreur création conversation');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async ouvrirConversation(conv) {
        this.idConvCourante = conv.id;
        this.estPremierChargement = true;
        
        await this.chargerConversations(); 

        const placeholder = document.getElementById('placeholder-chat');
        if (placeholder) placeholder.style.display = 'none';
        
        const contenuChat = document.getElementById('contenu-chat');
        if (contenuChat) contenuChat.style.display = 'flex';
        
        const nomPartenaire = document.getElementById('nom-partenaire-chat');
        if (nomPartenaire) nomPartenaire.textContent = conv.nom_autre_utilisateur;

        // Mise à jour de l'avatar dans l'en-tête du chat
        const avatarContainer = document.querySelector('.info-utilisateur-chat .cercle-avatar');
        if (avatarContainer) {
             if (conv.avatar_autre_utilisateur) {
                 avatarContainer.innerHTML = `<img src="${echapperHTML(conv.avatar_autre_utilisateur)}" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`;
                 avatarContainer.style.overflow = 'hidden';
                 avatarContainer.style.background = 'transparent'; // Enlever le fond dégradé si image
             } else {
                 avatarContainer.innerHTML = `<i class="fas fa-user"></i>`;
                 avatarContainer.style.background = ''; // Rétablir le fond par défaut
             }
        }

        const infoVehicule = document.getElementById('info-vehicule-chat');
        if (infoVehicule) infoVehicule.textContent = conv.marque ? `${conv.marque} ${conv.modele}` : 'Annonce supprimée';

        await this.chargerMessages();
        
        if (this.intervalleRafraichissement) clearInterval(this.intervalleRafraichissement);
        this.intervalleRafraichissement = setInterval(() => this.chargerMessages(), 5000);
    }

    async chargerMessages() {
        if (!this.idConvCourante) return;
        const conteneur = document.getElementById('conteneur-messages');
        if (!conteneur) return;
        
        const estEnBas = conteneur.scrollHeight - conteneur.scrollTop <= conteneur.clientHeight + 100;

        try {
            const res = await fetch(`${this.URL_API}?id_conversation=${this.idConvCourante}`);
            const data = await res.json();
            
            if (data.erreur) return;

            if (window.mettreAJourBadgeMessages) window.mettreAJourBadgeMessages();

            const scrollTopPrecedent = conteneur.scrollTop;

            conteneur.innerHTML = '';
            
            data.messages.forEach(m => {
                const div = document.createElement('div');
                const estMoi = (Number(m.sender_id) === this.idUtilisateurCourant);
                div.className = `message ${estMoi ? 'envoye' : 'recu'}`;
                
                div.innerHTML = `
                    ${echapperHTML(m.contenu_clair)}
                    <span class="heure-message">${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                `;
                
                if (estMoi && m.contenu_clair.includes('[Message ancien')) {
                    div.classList.add('system');
                    div.innerHTML = `<i class="fas fa-lock"></i> Ancien message chiffré (illisible)`;
                } else if (m.contenu_clair === '[Erreur de déchiffrement]') {
                    div.classList.add('system');
                    div.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Erreur de déchiffrement`;
                }

                conteneur.appendChild(div);
            });
            
            if (this.estPremierChargement) {
                conteneur.scrollTop = conteneur.scrollHeight;
                this.estPremierChargement = false;
            } else if (estEnBas) {
                conteneur.scrollTop = conteneur.scrollHeight;
            } else {
                conteneur.scrollTop = scrollTopPrecedent;
            }
            
        } catch (e) {
            console.error(e);
        }
    }

    async gererEnvoiMessage(e) {
        e.preventDefault();
        const champSaisie = document.getElementById('saisie-message');
        const contenu = champSaisie.value.trim();
        if (!contenu || !this.idConvCourante) return;

        try {
            const res = await fetch(this.URL_API, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    id_conversation: this.idConvCourante,
                    contenu: contenu
                })
            });
            
            if (res.ok) {
                champSaisie.value = '';
                const conteneur = document.getElementById('conteneur-messages');
                conteneur.scrollTop = conteneur.scrollHeight;
                setTimeout(() => this.chargerMessages(), 100);
            } else {
                alert('Erreur envoi');
            }
        } catch (e) {
            console.error(e);
        }
    }
}
