/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE ADMIN - Dashboard Administration
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Module JavaScript pour le dashboard administrateur.
 * Gère le chargement des données, l'affichage et l'interactivité.
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi, formaterMonnaie, echapperHTML } from '../application.js';

export default class VueAdmin {
    constructor() {
        this.urlApiBase = obtenirUrlApi('/admin');
        this.donnees = null;
        this.pageUtilisateurs = 1;
        this.filtreUtilisateurs = 'all';
        this.limiteMessages = 20;
    }

    /**
     * Initialise le dashboard
     */
    async initialiser() {
        this.attacherEvenements();
        await this.chargerDonnees();
    }

    /**
     * Attache les écouteurs d'événements
     */
    attacherEvenements() {
        // Bouton actualiser
        const btnRefresh = document.getElementById('btn-refresh-data');
        if (btnRefresh) {
            btnRefresh.addEventListener('click', () => this.chargerDonnees());
        }

        // Bouton voir tous les utilisateurs
        const btnVoirUtilisateurs = document.getElementById('btn-voir-utilisateurs');
        if (btnVoirUtilisateurs) {
            btnVoirUtilisateurs.addEventListener('click', () => this.afficherSectionUtilisateurs());
        }

        // Bouton fermer section utilisateurs
        const btnFermerUtilisateurs = document.getElementById('btn-fermer-utilisateurs');
        if (btnFermerUtilisateurs) {
            btnFermerUtilisateurs.addEventListener('click', () => this.fermerSectionUtilisateurs());
        }

        // Filtre utilisateurs
        const filtreUtilisateurs = document.getElementById('filtre-utilisateurs');
        if (filtreUtilisateurs) {
            filtreUtilisateurs.addEventListener('change', (e) => {
                this.filtreUtilisateurs = e.target.value;
                this.pageUtilisateurs = 1;
                this.chargerUtilisateursComplet();
            });
        }

        // Filtre messages
        const filtreMessages = document.getElementById('filtre-messages');
        if (filtreMessages) {
            filtreMessages.addEventListener('change', (e) => {
                this.limiteMessages = parseInt(e.target.value);
                this.chargerFluxMessages();
            });
        }

        // Bouton voir plus d'activité
        const btnMoreActivity = document.getElementById('btn-more-activity');
        if (btnMoreActivity) {
            btnMoreActivity.addEventListener('click', () => this.chargerPlusActivite());
        }
    }

    /**
     * Charge toutes les données du dashboard
     */
    async chargerDonnees() {
        const btnRefresh = document.getElementById('btn-refresh-data');
        const icon = btnRefresh?.querySelector('i');
        
        if (btnRefresh) {
            btnRefresh.disabled = true;
            if (icon) {
                icon.classList.add('fa-spin');
            }
        }

        try {
            const reponse = await fetch(`${this.urlApiBase}?action=resume`);
            const data = await reponse.json();

            if (data.ok) {
                this.donnees = data.data;
                this.afficherStatistiques();
                this.afficherActiviteRecente();
                this.afficherDerniersUtilisateurs();
                this.afficherDerniersVehicules();
                this.afficherStatistiquesRapides();
                await this.chargerFluxMessages();
            } else {
                this.afficherErreur(data.error || 'Erreur lors du chargement des données');
            }
        } catch (erreur) {
            console.error('Erreur chargement dashboard:', erreur);
            this.afficherErreur('Impossible de charger les données du dashboard');
        } finally {
            if (btnRefresh) {
                btnRefresh.disabled = false;
                if (icon) {
                    icon.classList.remove('fa-spin');
                }
            }
        }
    }

    /**
     * Affiche les statistiques principales
     */
    afficherStatistiques() {
        const { utilisateurs, vehicules, messagerie, offres, favoris } = this.donnees;

        // Utilisateurs
        this.mettreAJourElement('stat-users-total', utilisateurs.total);
        this.mettreAJourElement('stat-users-detail', `
            <span class="stat-verified">${utilisateurs.email_verifies} vérifiés</span> / 
            <span class="stat-unverified">${utilisateurs.email_non_verifies} non vérifiés</span>
        `);
        this.mettreAJourElement('stat-users-new', `<i class="fas fa-arrow-up"></i> +${utilisateurs.nouveaux_24h} (24h)`);

        // Véhicules
        this.mettreAJourElement('stat-vehicles-total', vehicules.total);
        this.mettreAJourElement('stat-vehicles-detail', `Prix moyen : <strong>${formaterMonnaie(vehicules.prix_moyen)}</strong>`);
        this.mettreAJourElement('stat-vehicles-new', `<i class="fas fa-arrow-up"></i> +${vehicules.nouveaux_24h} (24h)`);

        // Messages
        this.mettreAJourElement('stat-messages-total', messagerie.total_messages);
        this.mettreAJourElement('stat-messages-detail', `<span class="stat-unread">${messagerie.messages_non_lus} non lus</span>`);
        this.mettreAJourElement('stat-messages-new', `<i class="fas fa-arrow-up"></i> +${messagerie.messages_24h} (24h)`);

        // Offres
        this.mettreAJourElement('stat-offers-total', offres.total);
        this.mettreAJourElement('stat-offers-detail', `Taux acceptation : <strong>${offres.taux_acceptation}%</strong>`);
        this.mettreAJourElement('stat-offers-new', `<i class="fas fa-arrow-up"></i> +${offres.offres_24h} (24h)`);

        // Favoris
        this.mettreAJourElement('stat-favorites-total', favoris.total);
        this.mettreAJourElement('stat-favorites-detail', `+${favoris.favoris_24h} aujourd'hui`);

        // Conversations
        this.mettreAJourElement('stat-conversations-total', messagerie.total_conversations);
        this.mettreAJourElement('stat-conversations-detail', `${messagerie.conversations_actives_24h} actives (24h)`);
    }

    /**
     * Affiche l'activité récente dans la timeline
     */
    afficherActiviteRecente() {
        const conteneur = document.getElementById('timeline-activite');
        if (!conteneur) return;

        const activites = this.donnees.activite_recente;

        if (!activites || activites.length === 0) {
            conteneur.innerHTML = `
                <div class="admin-empty">
                    <i class="fas fa-stream"></i>
                    Aucune activité récente
                </div>
            `;
            return;
        }

        conteneur.innerHTML = activites.map(activite => this.genererItemTimeline(activite)).join('');
    }

    /**
     * Génère le HTML d'un item de timeline
     */
    genererItemTimeline(activite) {
        const { type, data, timestamp } = activite;
        const tempsRelatif = this.formaterTempsRelatif(timestamp);

        switch (type) {
            case 'nouveau_utilisateur':
                return `
                    <div class="admin-timeline-item admin-timeline-item--user">
                        <div class="admin-timeline-item__icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="admin-timeline-item__content">
                            <p class="admin-timeline-item__title">
                                <strong>${echapperHTML(data.first_name)} ${echapperHTML(data.last_name)}</strong> s'est inscrit
                            </p>
                            <span class="admin-timeline-item__meta">
                                <i class="fas fa-clock"></i> ${tempsRelatif}
                                <i class="fas fa-envelope"></i> ${echapperHTML(data.email)}
                            </span>
                        </div>
                    </div>
                `;

            case 'nouveau_vehicule':
                return `
                    <div class="admin-timeline-item admin-timeline-item--vehicle">
                        <div class="admin-timeline-item__icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="admin-timeline-item__content">
                            <p class="admin-timeline-item__title">
                                Nouvelle annonce : <strong>${echapperHTML(data.marque)} ${echapperHTML(data.modele)}</strong>
                            </p>
                            <span class="admin-timeline-item__meta">
                                <i class="fas fa-clock"></i> ${tempsRelatif}
                                <i class="fas fa-tag"></i> ${formaterMonnaie(data.prix)}
                                ${data.first_name ? `<i class="fas fa-user"></i> ${echapperHTML(data.first_name)} ${echapperHTML(data.last_name)}` : ''}
                            </span>
                        </div>
                    </div>
                `;

            case 'nouvelle_offre':
                return `
                    <div class="admin-timeline-item admin-timeline-item--offer">
                        <div class="admin-timeline-item__icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="admin-timeline-item__content">
                            <p class="admin-timeline-item__title">
                                Offre de <strong>${formaterMonnaie(data.amount)}</strong> 
                                ${data.marque ? `pour ${echapperHTML(data.marque)} ${echapperHTML(data.modele)}` : ''}
                            </p>
                            <span class="admin-timeline-item__meta">
                                <i class="fas fa-clock"></i> ${tempsRelatif}
                                ${data.first_name ? `<i class="fas fa-user"></i> ${echapperHTML(data.first_name)} ${echapperHTML(data.last_name)}` : ''}
                            </span>
                        </div>
                    </div>
                `;

            default:
                return '';
        }
    }

    /**
     * Affiche les derniers utilisateurs
     */
    afficherDerniersUtilisateurs() {
        const conteneur = document.getElementById('liste-utilisateurs');
        if (!conteneur) return;

        // Prendre les 5 premiers de l'activité récente de type utilisateur
        const utilisateurs = this.donnees.activite_recente
            .filter(a => a.type === 'nouveau_utilisateur')
            .slice(0, 5)
            .map(a => a.data);

        if (utilisateurs.length === 0) {
            conteneur.innerHTML = `
                <div class="admin-empty">
                    <i class="fas fa-users"></i>
                    Aucun nouvel utilisateur
                </div>
            `;
            return;
        }

        conteneur.innerHTML = utilisateurs.map(user => `
            <div class="admin-user-item">
                <div class="admin-user-item__avatar">
                    ${user.avatar_path 
                        ? `<img src="${echapperHTML(user.avatar_path)}" alt="">` 
                        : '👤'}
                </div>
                <div class="admin-user-item__info">
                    <div class="admin-user-item__name">${echapperHTML(user.first_name)} ${echapperHTML(user.last_name)}</div>
                    <div class="admin-user-item__email">${echapperHTML(user.email)}</div>
                </div>
                <span class="admin-user-item__badge ${user.email_verified_at ? 'admin-user-item__badge--verified' : 'admin-user-item__badge--unverified'}">
                    ${user.email_verified_at ? 'Vérifié' : 'Non vérifié'}
                </span>
            </div>
        `).join('');
    }

    /**
     * Affiche les derniers véhicules
     */
    afficherDerniersVehicules() {
        const conteneur = document.getElementById('liste-vehicules');
        if (!conteneur) return;

        const vehicules = this.donnees.derniers_vehicules;

        if (!vehicules || vehicules.length === 0) {
            conteneur.innerHTML = `
                <div class="admin-empty">
                    <i class="fas fa-car"></i>
                    Aucun véhicule récent
                </div>
            `;
            return;
        }

        conteneur.innerHTML = vehicules.map(v => `
            <a href="./vehicule?id=${v.id}" class="admin-vehicle-item" style="text-decoration: none; color: inherit; cursor: pointer;">
                <div class="admin-vehicle-item__icon">
                    <i class="fas fa-${v.type_vehicule === 'moto' ? 'motorcycle' : v.type_vehicule === 'camion' ? 'truck' : 'car'}"></i>
                </div>
                <div class="admin-vehicle-item__info">
                    <div class="admin-vehicle-item__title">${echapperHTML(v.marque)} ${echapperHTML(v.modele)}</div>
                    <div class="admin-vehicle-item__meta">
                        ${v.annee} • ${v.km ? v.km.toLocaleString('fr-FR') + ' km' : 'N/A'}
                        ${v.ville ? ` • ${echapperHTML(v.ville)}` : ''}
                    </div>
                </div>
                <span class="admin-vehicle-item__price">${formaterMonnaie(v.prix)}</span>
            </a>
        `).join('');
    }

    /**
     * Affiche les statistiques rapides (graphiques simplifiés)
     */
    afficherStatistiquesRapides() {
        const { vehicules, offres } = this.donnees;

        // Types de véhicules
        this.afficherBarreStats('stats-types-vehicules', vehicules.par_type, [
            { key: 'voiture', label: 'Voitures', color: 'blue' },
            { key: 'moto', label: 'Motos', color: 'green' },
            { key: 'camion', label: 'Camions', color: 'yellow' }
        ]);

        // Marques populaires
        this.afficherTagsStats('stats-marques', this.donnees.marques_populaires);

        // Statuts des offres
        this.afficherBarreStats('stats-offres-statuts', offres.par_statut, [
            { key: 'pending', label: 'En attente', color: 'yellow' },
            { key: 'accepted', label: 'Acceptées', color: 'green' },
            { key: 'declined', label: 'Refusées', color: 'red' },
            { key: 'expired', label: 'Expirées', color: 'purple' }
        ]);
    }

    /**
     * Affiche des barres de statistiques
     */
    afficherBarreStats(conteneurId, donnees, config) {
        const conteneur = document.getElementById(conteneurId);
        if (!conteneur || !donnees) return;

        const total = Object.values(donnees).reduce((a, b) => a + b, 0) || 1;

        conteneur.innerHTML = config.map(({ key, label, color }) => {
            const valeur = donnees[key] || 0;
            const pourcentage = Math.round((valeur / total) * 100);
            return `
                <div class="admin-stat-bar">
                    <span class="admin-stat-bar__label">${label}</span>
                    <div class="admin-stat-bar__track">
                        <div class="admin-stat-bar__fill admin-stat-bar__fill--${color}" 
                             style="width: ${pourcentage}%"></div>
                    </div>
                    <span class="admin-stat-bar__value">${valeur}</span>
                </div>
            `;
        }).join('');
    }

    /**
     * Affiche des tags de statistiques
     */
    afficherTagsStats(conteneurId, donnees) {
        const conteneur = document.getElementById(conteneurId);
        if (!conteneur) return;

        if (!donnees || donnees.length === 0) {
            conteneur.innerHTML = '<span class="admin-empty">Aucune donnée</span>';
            return;
        }

        conteneur.innerHTML = donnees.map(item => `
            <span class="admin-stat-tag">
                ${echapperHTML(item.marque)} <span>(${item.count})</span>
            </span>
        `).join('');
    }

    /**
     * Charge le flux de messages
     */
    async chargerFluxMessages() {
        const tbody = document.getElementById('tbody-messages');
        if (!tbody) return;

        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="admin-loading">
                    <i class="fas fa-spinner fa-spin"></i> Chargement...
                </td>
            </tr>
        `;

        try {
            const reponse = await fetch(`${this.urlApiBase}?action=messages&limite=${this.limiteMessages}`);
            const data = await reponse.json();

            if (data.ok) {
                this.afficherTableMessages(data.data.messages);
            }
        } catch (erreur) {
            console.error('Erreur chargement messages:', erreur);
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="admin-empty">Erreur de chargement</td>
                </tr>
            `;
        }
    }

    /**
     * Affiche le tableau des messages
     */
    afficherTableMessages(messages) {
        const tbody = document.getElementById('tbody-messages');
        if (!tbody) return;

        if (!messages || messages.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="admin-empty">Aucun message</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = messages.map(msg => `
            <tr>
                <td>${this.formaterDate(msg.created_at)}</td>
                <td>
                    <strong>${echapperHTML(msg.first_name || '')} ${echapperHTML(msg.last_name || '')}</strong>
                    <br><small>${echapperHTML(msg.sender_email || 'N/A')}</small>
                </td>
                <td>
                    ${msg.marque && msg.modele 
                        ? `${echapperHTML(msg.marque)} ${echapperHTML(msg.modele)}` 
                        : '<em>Véhicule supprimé</em>'}
                </td>
                <td>
                    <span class="status-badge ${msg.is_read ? 'status-badge--read' : 'status-badge--unread'}">
                        ${msg.is_read ? 'Lu' : 'Non lu'}
                    </span>
                </td>
                <td>
                    ${msg.vehicle_id 
                        ? `<a href="./vehicule?id=${msg.vehicle_id}" class="btn btn--sm btn--ghost" target="_blank">
                               <i class="fas fa-eye"></i>
                           </a>` 
                        : '-'}
                </td>
            </tr>
        `).join('');
    }

    /**
     * Affiche la section utilisateurs complète
     */
    afficherSectionUtilisateurs() {
        const section = document.getElementById('section-utilisateurs-complet');
        if (section) {
            section.hidden = false;
            section.scrollIntoView({ behavior: 'smooth' });
            this.chargerUtilisateursComplet();
        }
    }

    /**
     * Ferme la section utilisateurs
     */
    fermerSectionUtilisateurs() {
        const section = document.getElementById('section-utilisateurs-complet');
        if (section) {
            section.hidden = true;
        }
    }

    /**
     * Charge la liste complète des utilisateurs avec pagination
     */
    async chargerUtilisateursComplet() {
        const tbody = document.getElementById('tbody-utilisateurs-complet');
        const paginationInfo = document.getElementById('pagination-info-users');
        const paginationContainer = document.getElementById('pagination-utilisateurs');
        
        if (!tbody) return;

        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="admin-loading">
                    <i class="fas fa-spinner fa-spin"></i> Chargement...
                </td>
            </tr>
        `;

        try {
            const reponse = await fetch(
                `${this.urlApiBase}?action=utilisateurs&page=${this.pageUtilisateurs}&filtre=${this.filtreUtilisateurs}`
            );
            const data = await reponse.json();

            if (data.ok) {
                const { utilisateurs, pagination } = data.data;
                this.afficherTableUtilisateurs(utilisateurs);
                this.afficherPagination(paginationContainer, pagination);
                
                const debut = (pagination.page - 1) * pagination.par_page + 1;
                const fin = Math.min(pagination.page * pagination.par_page, pagination.total);
                paginationInfo.innerHTML = `Affichage de <strong>${debut}-${fin}</strong> sur <strong>${pagination.total}</strong> utilisateurs`;
            }
        } catch (erreur) {
            console.error('Erreur chargement utilisateurs:', erreur);
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="admin-empty">Erreur de chargement</td>
                </tr>
            `;
        }
    }

    /**
     * Affiche le tableau des utilisateurs
     */
    afficherTableUtilisateurs(utilisateurs) {
        const tbody = document.getElementById('tbody-utilisateurs-complet');
        if (!tbody) return;

        if (!utilisateurs || utilisateurs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="admin-empty">Aucun utilisateur trouvé</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = utilisateurs.map(user => `
            <tr>
                <td>${user.id}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div class="admin-user-item__avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                            ${user.avatar_path 
                                ? `<img src="${echapperHTML(user.avatar_path)}" alt="" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">` 
                                : '👤'}
                        </div>
                        <strong>${echapperHTML(user.first_name)} ${echapperHTML(user.last_name)}</strong>
                    </div>
                </td>
                <td>${echapperHTML(user.email)}</td>
                <td>${echapperHTML(user.phone || '-')}</td>
                <td>
                    <span class="admin-user-item__badge ${user.role === 'admin' ? 'admin-user-item__badge--admin' : ''}">
                        ${user.role === 'admin' ? 'Admin' : 'Utilisateur'}
                    </span>
                </td>
                <td>
                    <span class="status-badge ${user.email_verified_at ? 'status-badge--accepted' : 'status-badge--pending'}">
                        ${user.email_verified_at ? 'Oui' : 'Non'}
                    </span>
                </td>
                <td>${this.formaterDate(user.created_at)}</td>
            </tr>
        `).join('');
    }

    /**
     * Affiche la pagination
     */
    afficherPagination(conteneur, pagination) {
        if (!conteneur || !pagination) return;

        const { page, total_pages } = pagination;
        let html = '';

        // Bouton précédent
        html += `<button ${page <= 1 ? 'disabled' : ''} data-page="${page - 1}">
            <i class="fas fa-chevron-left"></i>
        </button>`;

        // Pages
        for (let i = 1; i <= total_pages; i++) {
            if (i === 1 || i === total_pages || (i >= page - 2 && i <= page + 2)) {
                html += `<button class="${i === page ? 'active' : ''}" data-page="${i}">${i}</button>`;
            } else if (i === page - 3 || i === page + 3) {
                html += '<button disabled>...</button>';
            }
        }

        // Bouton suivant
        html += `<button ${page >= total_pages ? 'disabled' : ''} data-page="${page + 1}">
            <i class="fas fa-chevron-right"></i>
        </button>`;

        conteneur.innerHTML = html;

        // Attacher les événements
        conteneur.querySelectorAll('button[data-page]').forEach(btn => {
            btn.addEventListener('click', () => {
                const nouvellePage = parseInt(btn.dataset.page);
                if (nouvellePage >= 1 && nouvellePage <= total_pages) {
                    this.pageUtilisateurs = nouvellePage;
                    this.chargerUtilisateursComplet();
                }
            });
        });
    }

    /**
     * Charge plus d'activité
     */
    async chargerPlusActivite() {
        try {
            const reponse = await fetch(`${this.urlApiBase}?action=activite&limite=50`);
            const data = await reponse.json();

            if (data.ok) {
                this.donnees.activite_recente = data.data;
                this.afficherActiviteRecente();
            }
        } catch (erreur) {
            console.error('Erreur chargement activité:', erreur);
        }
    }

    /**
     * Met à jour le contenu d'un élément
     */
    mettreAJourElement(id, contenu) {
        const element = document.getElementById(id);
        if (element) {
            element.innerHTML = contenu;
        }
    }

    /**
     * Affiche une erreur
     */
    afficherErreur(message) {
        console.error('Erreur Dashboard:', message);
        // On pourrait afficher une notification
    }

    /**
     * Formate une date en format lisible
     */
    formaterDate(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        return new Intl.DateTimeFormat('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    }

    /**
     * Formate un temps relatif (il y a X minutes/heures)
     */
    formaterTempsRelatif(dateStr) {
        if (!dateStr) return '';
        
        const date = new Date(dateStr);
        const maintenant = new Date();
        const diffMs = maintenant - date;
        const diffSecondes = Math.floor(diffMs / 1000);
        const diffMinutes = Math.floor(diffSecondes / 60);
        const diffHeures = Math.floor(diffMinutes / 60);
        const diffJours = Math.floor(diffHeures / 24);

        // Moins d'une minute
        if (diffSecondes < 60) return 'moins d\'une minute';
        
        // Moins d'une heure : format précis (1min, 2min, 3min... 59min)
        if (diffMinutes < 60) return `${diffMinutes}min`;
        
        // Moins de 24 heures : format heures (1h, 2h, 3h... 23h)
        if (diffHeures < 24) return `${diffHeures}h`;
        
        // Moins de 7 jours : format jours
        if (diffJours < 7) return `${diffJours}j`;
        
        // Plus de 7 jours : date complète
        return this.formaterDate(dateStr);
    }
}
