/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE MESSAGERIE - SYSTÈME DE MESSAGERIE EN TEMPS RÉEL
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe gère le système de messagerie entre acheteurs et vendeurs :
 * - Liste des conversations avec aperçu du dernier message
 * - Chat en temps réel avec rafraîchissement automatique
 * - Création de nouvelles conversations
 * - Système de propositions de prix
 * - Filtrage/recherche des conversations
 * - Interface responsive (sidebar/chat mobile)
 * 
 * Fonctionnalités des propositions :
 * - Création d'une proposition de prix
 * - Acceptation/refus par le vendeur
 * - Historique des propositions dans la conversation
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @see     ControleurMessagerie (PHP) Pour le traitement serveur
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { echapperHTML, obtenirUrlApi } from '../../application.js';

export default class VueMessagerie {
    
    /**
     * Initialise la vue messagerie avec les paramètres par défaut
     * 
     * Configure l'URL de l'API et initialise l'état de la conversation.
     */
    constructor() {
        /** @type {string} URL de base de l'API messagerie */
        this.URL_API = obtenirUrlApi('/messagerie');
        
        /** @type {number|null} ID de l'utilisateur connecté */
        this.idUtilisateurCourant = null;
        
        /** @type {number|null} ID de la conversation actuellement ouverte */
        this.idConvCourante = null;
        
        /** @type {Object|null} Données de la conversation courante */
        this.convCourante = null;
        
        /** @type {number|null} Intervalle de rafraîchissement automatique */
        this.intervalleRafraichissement = null;
        
        /** @type {boolean} Indique si c'est le premier chargement */
        this.estPremierChargement = true;
        
        /** @type {Object|null} Proposition de prix active */
        this.propositionActive = null;

        this.initialiser();
    }

    /**
     * Initialise la messagerie : vérifie l'auth et charge les conversations
     * 
     * Gère également les paramètres URL pour ouvrir directement
     * une conversation avec un vendeur depuis la page véhicule.
     * 
     * @async
     */
    async initialiser() {
        // Vérification de l'authentification via l'API messagerie
        const reponse = await fetch(`${this.URL_API}?action=infos_utilisateur`);
        if (!reponse.ok) {
            // Redirection vers la connexion si non authentifié
            window.location.href = 'connexion';
            return;
        }
        
        const donnees = await reponse.json();
        this.idUtilisateurCourant = Number(donnees.utilisateur.id);

        // Gestion responsive : afficher la sidebar par défaut sur mobile
        const sidebar = document.querySelector('.msg-sidebar');
        const chat = document.querySelector('.msg-chat');
        if (window.innerWidth <= 900) {
            if (sidebar) sidebar.classList.add('active');
            if (chat) chat.classList.remove('active');
        }

        // Chargement initial des conversations
        await this.chargerConversations();

        // Gestion des paramètres URL (ouverture depuis page véhicule)
        const parametres = new URLSearchParams(window.location.search);
        if (parametres.has('vehicle_id') && parametres.has('seller_id')) {
            this.creerOuOuvrirConversation(
                parametres.get('vehicle_id'), 
                parametres.get('seller_id')
            );
        }

        // Configuration du formulaire d'envoi de message
        const formulaire = document.getElementById('formulaire-message');
        if (formulaire) {
            formulaire.addEventListener('submit', (evenement) => this.gererEnvoiMessage(evenement));
        }

        // Bouton retour mobile (de chat vers liste)
        const btnRetour = document.getElementById('btn-retour-mobile');
        if (btnRetour) {
            btnRetour.addEventListener('click', () => this.retourListeConversations());
        }

        // Barre de recherche des conversations
        const champRecherche = document.getElementById('recherche-conv');
        if (champRecherche) {
            champRecherche.addEventListener('input', (evenement) => 
                this.filtrerConversations(evenement.target.value)
            );
        }

        // Bouton d'ouverture de la modal proposition
        const btnProposition = document.getElementById('btn-proposition');
        if (btnProposition) {
            btnProposition.addEventListener('click', () => this.ouvrirModalProposition());
        }

        // Configuration de la modal de proposition
        this.configurerModalProposition();
    }

    /**
     * Configure les événements de la modal de proposition de prix
     * 
     * Gère la fermeture de la modal et la soumission du formulaire.
     */
    configurerModalProposition() {
        const modal = document.getElementById('modal-proposition');
        const btnFermer = document.getElementById('fermer-modal-proposition');
        const formulaire = document.getElementById('formulaire-proposition');

        // Bouton de fermeture
        if (btnFermer) {
            btnFermer.addEventListener('click', () => this.fermerModalProposition());
        }

        // Clic en dehors de la modal pour fermer
        if (modal) {
            modal.addEventListener('click', (evenement) => {
                if (evenement.target === modal) this.fermerModalProposition();
            });
        }

        // Soumission du formulaire de proposition
        if (formulaire) {
            formulaire.addEventListener('submit', (evenement) => this.envoyerProposition(evenement));
        }
    }

    /**
     * Ouvre la modal de proposition de prix
     * 
     * Pré-remplit le montant avec le prix du véhicule si disponible.
     */
    ouvrirModalProposition() {
        const modal = document.getElementById('modal-proposition');
        const champMontant = document.getElementById('montant-proposition');
        
        if (modal) {
            modal.classList.add('active');
            if (champMontant) {
                // Pré-remplissage avec le prix du véhicule
                if (this.convCourante?.prix) {
                    champMontant.value = this.convCourante.prix;
                }
                champMontant.focus();
            }
        }
    }

    /**
     * Ferme la modal de proposition de prix
     */
    fermerModalProposition() {
        const modal = document.getElementById('modal-proposition');
        if (modal) modal.classList.remove('active');
    }

    /**
     * Envoie une proposition de prix au vendeur
     * 
     * @param {Event} evenement - Événement de soumission du formulaire
     * @async
     */
    async envoyerProposition(evenement) {
        evenement.preventDefault();
        
        const champMontant = document.getElementById('montant-proposition');
        const montant = parseFloat(champMontant?.value || 0);

        // Validation du montant
        if (!montant || montant <= 0 || !this.idConvCourante) {
            alert('Veuillez entrer un montant valide.');
            return;
        }

        try {
            const reponse = await fetch(this.URL_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'creer_proposition',
                    id_conversation: this.idConvCourante,
                    montant: montant
                })
            });

            const donnees = await reponse.json();

            if (reponse.ok) {
                // Fermeture de la modal et rafraîchissement
                this.fermerModalProposition();
                champMontant.value = '';
                await this.chargerMessages();
            } else {
                alert(donnees.erreur || 'Erreur lors de la création de la proposition');
            }
        } catch (erreur) {
            console.error('Erreur réseau:', erreur);
            alert('Erreur réseau');
        }
    }

    /**
     * Retourne à la liste des conversations (mobile uniquement)
     * 
     * Bascule l'affichage de la zone chat vers la sidebar.
     */
    retourListeConversations() {
        const sidebar = document.querySelector('.msg-sidebar');
        const chat = document.querySelector('.msg-chat');
        if (sidebar) sidebar.classList.add('active');
        if (chat) chat.classList.remove('active');
    }

    filtrerConversations(recherche) {
        const elements = document.querySelectorAll('#liste-conv .element-conv');
        const rechercheMin = recherche.toLowerCase();
        
        elements.forEach(el => {
            const nom = el.querySelector('.info-conv h4')?.textContent.toLowerCase() || '';
            const vehicule = el.querySelector('.info-conv p')?.textContent.toLowerCase() || '';
            
            if (nom.includes(rechercheMin) || vehicule.includes(rechercheMin)) {
                el.style.display = 'flex';
            } else {
                el.style.display = 'none';
            }
        });
    }

    async chargerConversations() {
        const conteneurListe = document.getElementById('liste-conv');
        const elChargement = document.getElementById('chargement-conv');
        const elVide = document.getElementById('conv-vide');
        const compteur = document.getElementById('compteur-messages');

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

            // Mettre à jour le compteur
            if (compteur) compteur.textContent = convs.length;

            if (convs.length === 0) {
                if (elVide) {
                    elVide.hidden = false;
                    elVide.style.display = 'flex';
                }
                return;
            }

            convs.forEach(c => {
                const div = document.createElement('div');
                div.className = `element-conv ${this.idConvCourante === c.id ? 'active' : ''} ${c.non_lu ? 'non-lu' : ''}`;
                div.onclick = () => this.ouvrirConversation(c);
                
                const avatarHtml = c.avatar_autre_utilisateur 
                    ? `<img src="${echapperHTML(c.avatar_autre_utilisateur)}" alt="Avatar" class="avatar-conv-img">`
                    : `<div class="avatar-conv"><i class="fas fa-user"></i></div>`;

                // Badge de messages non lus
                const badgeNonLu = c.nb_non_lus > 0
                    ? `<span class="badge-non-lu">${c.nb_non_lus}</span>` 
                    : '';

                // Détails de l'annonce
                let annonceHtml = '';
                if (c.marque && c.vehicle_id) {
                    const prixFormate = c.prix ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(c.prix) : '';
                    annonceHtml = `
                        <a href="vehicule?id=${c.vehicle_id}" class="annonce-conv" onclick="event.stopPropagation();">
                            <i class="fas fa-car"></i>
                            <span>${echapperHTML(c.marque)} ${echapperHTML(c.modele)}${c.annee ? ' • ' + c.annee : ''}${prixFormate ? ' • ' + prixFormate : ''}</span>
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    `;
                } else {
                    annonceHtml = `
                        <div class="annonce-conv annonce-supprimee">
                            <i class="fas fa-ban"></i>
                            <span>Annonce supprimée</span>
                        </div>
                    `;
                }

                div.innerHTML = `
                    ${avatarHtml}
                    <div class="info-conv">
                        <div class="info-conv__header">
                            <h4>${echapperHTML(c.nom_autre_utilisateur)}</h4>
                            ${badgeNonLu}
                        </div>
                        ${annonceHtml}
                    </div>
                `;
                conteneurListe.appendChild(div);
            });
        } catch (e) {
            console.error(e);
            if (elChargement) elChargement.hidden = true;
            conteneurListe.innerHTML = `<div class="msg-empty" style="display: flex;"><p>Impossible de charger les conversations.</p></div>`;
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
        this.convCourante = conv;
        this.estPremierChargement = true;
        this.propositionActive = null;
        
        await this.chargerConversations(); 

        const placeholder = document.getElementById('placeholder-chat');
        if (placeholder) placeholder.style.display = 'none';
        
        const contenuChat = document.getElementById('contenu-chat');
        if (contenuChat) contenuChat.style.display = 'flex';
        
        const nomPartenaire = document.getElementById('nom-partenaire-chat');
        if (nomPartenaire) nomPartenaire.textContent = conv.nom_autre_utilisateur;

        // Mise à jour de l'avatar dans l'en-tête du chat
        const avatarContainer = document.querySelector('.msg-chat__avatar');
        if (avatarContainer) {
             if (conv.avatar_autre_utilisateur) {
                 avatarContainer.innerHTML = `<img src="${echapperHTML(conv.avatar_autre_utilisateur)}" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`;
                 avatarContainer.style.overflow = 'hidden';
                 avatarContainer.style.background = 'transparent';
             } else {
                 avatarContainer.innerHTML = `<i class="fas fa-user"></i>`;
                 avatarContainer.style.background = '';
             }
        }

        const infoVehicule = document.getElementById('info-vehicule-chat');
        if (infoVehicule) {
            if (conv.marque && conv.vehicle_id) {
                const prixFormate = conv.prix ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(conv.prix) : '';
                infoVehicule.innerHTML = `<i class="fas fa-car"></i> ${echapperHTML(conv.marque)} ${echapperHTML(conv.modele)}${conv.annee ? ' • ' + conv.annee : ''}${prixFormate ? ' • ' + prixFormate : ''}`;
                infoVehicule.style.cursor = 'pointer';
                infoVehicule.onclick = () => window.location.href = `vehicule?id=${conv.vehicle_id}`;
            } else {
                infoVehicule.innerHTML = `<i class="fas fa-ban"></i> Annonce supprimée`;
                infoVehicule.style.cursor = 'default';
                infoVehicule.onclick = null;
            }
        }

        // Mobile: afficher le chat et masquer la sidebar
        const sidebar = document.querySelector('.msg-sidebar');
        const chat = document.querySelector('.msg-chat');
        if (window.innerWidth <= 900) {
            if (sidebar) sidebar.classList.remove('active');
            if (chat) chat.classList.add('active');
        }

        await this.chargerMessages();
        
        if (this.intervalleRafraichissement) clearInterval(this.intervalleRafraichissement);
        this.intervalleRafraichissement = setInterval(() => {
            this.chargerMessages();
        }, 5000);
    }

    async chargerPropositions() {
        if (!this.idConvCourante) return;

        try {
            const res = await fetch(`${this.URL_API}?action=propositions&id_conversation=${this.idConvCourante}`);
            const data = await res.json();

            if (res.ok && data.propositions) {
                this.propositions = data.propositions;
            } else {
                this.propositions = [];
            }
        } catch (e) {
            console.error('Erreur chargement propositions:', e);
            this.propositions = [];
        }
    }

    creerBulleProposition(prop) {
        const estMonOffre = Number(prop.sender_id) === this.idUtilisateurCourant;
        const montantFormate = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(prop.amount || 0);
        
        let statusClass = 'pending';
        let statusIcon = 'fa-clock';
        let statusText = 'En attente';
        let actionsHtml = '';
        let expiryHtml = '';

        switch (prop.status) {
            case 'pending':
                statusClass = 'pending';
                statusIcon = 'fa-clock';
                statusText = 'En attente';
                
                const dateExpiration = new Date(prop.expires_at);
                const tempsRestant = this.calculerTempsRestant(dateExpiration);
                expiryHtml = `<span class="offre-expiry"><i class="fas fa-clock"></i> Expire ${tempsRestant}</span>`;
                
                if (estMonOffre) {
                    actionsHtml = `
                        <button class="offre-btn offre-btn--cancel" onclick="window.vueMessagerie.annulerProposition(${prop.id})">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                    `;
                } else {
                    actionsHtml = `
                        <button class="offre-btn offre-btn--accept" onclick="window.vueMessagerie.accepterProposition(${prop.id})">
                            <i class="fas fa-check"></i> Accepter
                        </button>
                        <button class="offre-btn offre-btn--decline" onclick="window.vueMessagerie.refuserProposition(${prop.id})">
                            <i class="fas fa-times"></i> Refuser
                        </button>
                    `;
                }
                break;
                
            case 'accepted':
                statusClass = 'accepted';
                statusIcon = 'fa-check-circle';
                statusText = 'Acceptée';
                
                const datePayment = new Date(prop.payment_expires_at);
                const tempsPayment = this.calculerTempsRestant(datePayment);
                expiryHtml = `<span class="offre-expiry"><i class="fas fa-credit-card"></i> Paiement ${tempsPayment}</span>`;
                
                const estAcheteur = this.convCourante && Number(this.convCourante.buyer_id) === this.idUtilisateurCourant;
                
                if (estAcheteur) {
                    actionsHtml = `
                        <button class="offre-btn offre-btn--pay" onclick="window.vueMessagerie.payerProposition(${prop.id})">
                            <i class="fas fa-credit-card"></i> Payer
                        </button>
                        <button class="offre-btn offre-btn--cancel" onclick="window.vueMessagerie.annulerProposition(${prop.id})">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                    `;
                } else {
                    actionsHtml = `
                        <span class="offre-waiting"><i class="fas fa-hourglass-half"></i> En attente du paiement</span>
                        <button class="offre-btn offre-btn--cancel" onclick="window.vueMessagerie.annulerProposition(${prop.id})">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                    `;
                }
                break;
                
            case 'declined':
                statusClass = 'declined';
                statusIcon = 'fa-times-circle';
                statusText = 'Refusée';
                break;
                
            case 'expired':
                statusClass = 'expired';
                statusIcon = 'fa-hourglass-end';
                statusText = 'Expirée';
                break;
                
            case 'cancelled':
                statusClass = 'cancelled';
                statusIcon = 'fa-ban';
                statusText = 'Annulée';
                break;
                
            case 'paid':
                statusClass = 'paid';
                statusIcon = 'fa-check-double';
                statusText = 'Payée';
                break;
        }

        const heureFormatee = new Date(prop.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

        return `
            <div class="message-offre message-offre--${statusClass} ${estMonOffre ? 'envoye' : 'recu'}">
                <div class="offre-content">
                    <div class="offre-header">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span>Proposition de prix</span>
                        <span class="offre-status offre-status--${statusClass}">
                            <i class="fas ${statusIcon}"></i> ${statusText}
                        </span>
                    </div>
                    <div class="offre-montant">${montantFormate}</div>
                    <div class="offre-auteur">
                        ${estMonOffre ? 'Votre proposition' : `De ${echapperHTML(prop.first_name)}`}
                    </div>
                    ${expiryHtml}
                    ${actionsHtml ? `<div class="offre-actions">${actionsHtml}</div>` : ''}
                    <span class="heure-message">${heureFormatee}</span>
                </div>
            </div>
        `;
    }

    calculerTempsRestant(date) {
        const maintenant = new Date();
        const diff = date - maintenant;

        if (diff <= 0) return 'expiré';

        const heures = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

        if (heures >= 24) {
            const jours = Math.floor(heures / 24);
            return `dans ${jours}j ${heures % 24}h`;
        }
        return `dans ${heures}h ${minutes}min`;
    }

    async accepterProposition(idOffre) {
        if (!confirm('Voulez-vous accepter cette proposition ? L\'acheteur aura 48h pour effectuer le paiement.')) return;

        try {
            const res = await fetch(this.URL_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'accepter_proposition', id_offre: idOffre })
            });
            const data = await res.json();

            if (res.ok) {
                await this.chargerMessages();
            } else {
                alert(data.erreur || 'Erreur');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async refuserProposition(idOffre) {
        if (!confirm('Voulez-vous refuser cette proposition ?')) return;

        try {
            const res = await fetch(this.URL_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'refuser_proposition', id_offre: idOffre })
            });
            const data = await res.json();

            if (res.ok) {
                await this.chargerMessages();
            } else {
                alert(data.erreur || 'Erreur');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async annulerProposition(idOffre) {
        if (!confirm('Voulez-vous annuler cette proposition ?')) return;

        try {
            const res = await fetch(this.URL_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'annuler_proposition', id_offre: idOffre })
            });
            const data = await res.json();

            if (res.ok) {
                await this.chargerMessages();
            } else {
                alert(data.erreur || 'Erreur');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async payerProposition(idOffre) {
        // Simulation de paiement
        alert('Redirection vers le prestataire de paiement...\n\n(Fonctionnalité de paiement à intégrer ultérieurement avec un prestataire comme Stripe, PayPal, etc.)');
        
        if (!confirm('Simuler le paiement réussi ?')) return;

        try {
            const res = await fetch(this.URL_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'payer_proposition', id_offre: idOffre })
            });
            const data = await res.json();

            if (res.ok) {
                alert('🎉 Paiement effectué avec succès ! La transaction est complète.');
                await this.chargerMessages();
            } else {
                alert(data.erreur || 'Erreur lors du paiement');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async chargerMessages() {
        if (!this.idConvCourante) return;
        const conteneur = document.getElementById('conteneur-messages');
        if (!conteneur) return;
        
        const estEnBas = conteneur.scrollHeight - conteneur.scrollTop <= conteneur.clientHeight + 100;

        try {
            // Charger les messages et les propositions en parallèle
            const [resMessages, resPropositions] = await Promise.all([
                fetch(`${this.URL_API}?id_conversation=${this.idConvCourante}`),
                fetch(`${this.URL_API}?action=propositions&id_conversation=${this.idConvCourante}`)
            ]);
            
            const dataMessages = await resMessages.json();
            const dataPropositions = await resPropositions.json();
            
            if (dataMessages.erreur) return;

            if (window.mettreAJourBadgeMessages) window.mettreAJourBadgeMessages();

            const scrollTopPrecedent = conteneur.scrollTop;

            // Combiner messages et propositions, trier par date
            const elements = [];
            
            // Ajouter les messages
            dataMessages.messages.forEach(m => {
                elements.push({
                    type: 'message',
                    data: m,
                    date: new Date(m.created_at)
                });
            });
            
            // Ajouter les propositions
            if (dataPropositions.propositions) {
                dataPropositions.propositions.forEach(p => {
                    elements.push({
                        type: 'proposition',
                        data: p,
                        date: new Date(p.created_at)
                    });
                });
            }
            
            // Trier par date
            elements.sort((a, b) => a.date - b.date);

            conteneur.innerHTML = '';
            
            elements.forEach(el => {
                if (el.type === 'message') {
                    const m = el.data;
                    const div = document.createElement('div');
                    const estMoi = (Number(m.sender_id) === this.idUtilisateurCourant);
                    div.className = `message ${estMoi ? 'envoye' : 'recu'}`;
                    
                    const contenuMessage = m.contenu_clair || '[Message non disponible]';
                    
                    div.innerHTML = `
                        <span class="message-texte">${echapperHTML(contenuMessage)}</span>
                        <span class="heure-message">${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    `;
                    
                    if (contenuMessage.includes('[Message ancien')) {
                        div.classList.add('system');
                        div.innerHTML = `<i class="fas fa-lock"></i> Ancien message chiffré (illisible)`;
                    } else if (contenuMessage === '[Erreur de déchiffrement]') {
                        div.classList.add('system');
                        div.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Erreur de déchiffrement`;
                    }

                    conteneur.appendChild(div);
                } else if (el.type === 'proposition') {
                    const bulleHtml = this.creerBulleProposition(el.data);
                    conteneur.insertAdjacentHTML('beforeend', bulleHtml);
                }
            });

            // Exposer pour les onclick
            window.vueMessagerie = this;
            
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
