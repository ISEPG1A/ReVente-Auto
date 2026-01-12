/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE ADMIN - Logique du dashboard administrateur
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Gère :
 * - Chargement et affichage des statistiques
 * - Navigation entre onglets
 * - Gestion des utilisateurs (liste, recherche, suppression)
 * - Gestion des véhicules (liste, recherche, masquage, suppression)
 * - Pagination
 * - Modales de confirmation
 * 
 * @version 2.0 - Refonte complète
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi, echapperHTML, formaterMonnaie } from '../../application.js';

export default class VueAdmin {
    constructor() {
        this.apiUrl = obtenirUrlApi('/admin');
        this.data = null;
        this.currentTab = 'users';
        this.currentUserPage = 1;
        this.currentVehiclePage = 1;
        this.currentContactPage = 1;
        this.currentModerationPage = 1;
        this.currentActivityPage = 1;
        this.userFilter = 'all';
        this.vehicleFilter = 'all';
        this.contactFilter = 'all';
        this.searchUserTimeout = null;
        this.searchVehicleTimeout = null;
        this.currentContactId = null;
        this.currentModerationId = null;
        // ID de l'utilisateur connecté (admin actuel)
        this.currentUserId = parseInt(document.body.dataset.userId || '0');
    }

    /**
     * Initialisation
     */
    async initialiser() {
        this.attacherEvenements();
        await this.chargerResume();
        await this.chargerCompteurs(); // Charger les badges
    }

    /**
     * Attacher les écouteurs d'événements
     */
    attacherEvenements() {
        // Bouton actualiser
        document.getElementById('btn-refresh')?.addEventListener('click', () => this.chargerResume());

        // Navigation onglets
        document.querySelectorAll('.admin-tab').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tab = e.currentTarget.dataset.tab;
                this.changerOnglet(tab);
            });
        });

        // Recherche utilisateurs
        document.getElementById('search-users')?.addEventListener('input', (e) => {
            clearTimeout(this.searchUserTimeout);
            this.searchUserTimeout = setTimeout(() => {
                this.currentUserPage = 1;
                this.chargerUtilisateurs(e.target.value);
            }, 500);
        });

        // Filtre utilisateurs
        document.getElementById('filter-users')?.addEventListener('change', (e) => {
            this.userFilter = e.target.value;
            this.currentUserPage = 1;
            this.chargerUtilisateurs();
        });

        // Recherche véhicules
        document.getElementById('search-vehicles')?.addEventListener('input', (e) => {
            clearTimeout(this.searchVehicleTimeout);
            this.searchVehicleTimeout = setTimeout(() => {
                this.currentVehiclePage = 1;
                this.chargerVehicules(e.target.value);
            }, 500);
        });

        // Filtre véhicules
        document.getElementById('filter-vehicles')?.addEventListener('change', (e) => {
            this.vehicleFilter = e.target.value;
            this.currentVehiclePage = 1;
            this.chargerVehicules();
        });

        // Filtre activité - application automatique au changement de date
        document.getElementById('date-debut')?.addEventListener('change', () => {
            this.currentActivityPage = 1;
            this.chargerActivite();
            this.afficherBoutonResetDateActivite();
        });

        document.getElementById('date-fin')?.addEventListener('change', () => {
            this.currentActivityPage = 1;
            this.chargerActivite();
            this.afficherBoutonResetDateActivite();
        });

        // Réinitialisation des dates d'activité
        document.getElementById('btn-reset-date-activite')?.addEventListener('click', () => {
            document.getElementById('date-debut').value = '';
            document.getElementById('date-fin').value = '';
            this.currentActivityPage = 1;
            this.chargerActivite();
            this.afficherBoutonResetDateActivite();
        });

        // Filtre contacts
        document.getElementById('filter-contacts')?.addEventListener('change', (e) => {
            this.contactFilter = e.target.value;
            this.currentContactPage = 1;
            this.chargerContacts();
        });

        // Filtres de date pour les contacts
        document.getElementById('filter-contacts-date-debut')?.addEventListener('change', () => {
            this.currentContactPage = 1;
            this.chargerContacts();
            this.afficherBoutonResetDateContacts();
        });

        document.getElementById('filter-contacts-date-fin')?.addEventListener('change', () => {
            this.currentContactPage = 1;
            this.chargerContacts();
            this.afficherBoutonResetDateContacts();
        });

        document.getElementById('btn-reset-date-contacts')?.addEventListener('click', () => {
            document.getElementById('filter-contacts-date-debut').value = '';
            document.getElementById('filter-contacts-date-fin').value = '';
            this.currentContactPage = 1;
            this.chargerContacts();
            this.afficherBoutonResetDateContacts();
        });

        // Modale
        document.getElementById('modal-close')?.addEventListener('click', () => this.fermerModale());
        document.getElementById('modal-cancel')?.addEventListener('click', () => this.fermerModale());
        document.querySelector('.modal-overlay')?.addEventListener('click', () => this.fermerModale());
        
        // Exposer l'instance pour les onclick
        window.admin = this;
    }

    /**
     * Charger le résumé complet
     */
    async chargerResume() {
        try {
            const response = await fetch(`${this.apiUrl}?action=resume`);
            const result = await response.json();

            if (result.success) {
                this.data = result.data;
                this.afficherStatistiques();
                
                // Charger l'activité avec pagination
                await this.chargerActivite();
                
                // Charger les données selon l'onglet actif
                if (this.currentTab === 'users') {
                    await this.chargerUtilisateurs();
                } else if (this.currentTab === 'vehicles') {
                    await this.chargerVehicules();
                } else if (this.currentTab === 'stats') {
                    await this.chargerStatistiques();
                }
            } else {
                this.afficherErreur(result.error);
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherErreur('Impossible de charger les données');
        }
    }
    
    /**
     * Charger l'activité avec pagination et filtres de date
     */
    async chargerActivite() {
        const timeline = document.getElementById('activity-timeline');
        if (!timeline) return;
        
        timeline.innerHTML = '<div class="activity-item activity-item--loading"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
        
        try {
            const params = new URLSearchParams({
                action: 'activite',
                page: this.currentActivityPage
            });
            
            const dateDebut = document.getElementById('date-debut')?.value;
            const dateFin = document.getElementById('date-fin')?.value;
            
            if (dateDebut) params.append('date_debut', dateDebut);
            if (dateFin) params.append('date_fin', dateFin);
            
            const response = await fetch(`${this.apiUrl}?${params}`);
            const result = await response.json();
            
            if (result.success) {
                this.afficherActivite(result.data);
            } else {
                timeline.innerHTML = `<div class="activity-item activity-item--error">${echapperHTML(result.error)}</div>`;
            }
        } catch (error) {
            console.error('Erreur:', error);
            timeline.innerHTML = '<div class="activity-item activity-item--error">Erreur de chargement</div>';
        }
    }

    /**
     * Afficher les statistiques globales
     */
    afficherStatistiques() {
        if (!this.data) return;

        const { utilisateurs, vehicules, conversations } = this.data;

        document.getElementById('stat-users-total').textContent = utilisateurs.total;
        document.getElementById('stat-users-new').textContent = utilisateurs.nouveaux_7j;

        document.getElementById('stat-vehicles-total').textContent = vehicules.total;
        document.getElementById('stat-vehicles-new').textContent = vehicules.nouveaux_7j;

        document.getElementById('stat-conversations-total').textContent = conversations.total_conversations;
        document.getElementById('stat-conversations-active').textContent = conversations.actives_7j;

        document.getElementById('stat-price-avg').textContent = formaterMonnaie(vehicules.prix_moyen);
    }

    /**
     * Changer d'onglet
     */
    changerOnglet(tab) {
        this.currentTab = tab;

        // Mettre à jour les boutons
        document.querySelectorAll('.admin-tab').forEach(btn => {
            btn.classList.toggle('admin-tab--active', btn.dataset.tab === tab);
        });

        // Mettre à jour les panneaux
        document.querySelectorAll('.admin-panel').forEach(panel => {
            panel.classList.toggle('admin-panel--active', panel.dataset.panel === tab);
        });

        // Charger les données selon l'onglet
        if (tab === 'users') {
            this.chargerUtilisateurs();
        } else if (tab === 'vehicles') {
            this.chargerVehicules();
        } else if (tab === 'stats') {
            this.chargerStatistiques();
        } else if (tab === 'contacts') {
            this.chargerContacts();
        } else if (tab === 'moderation') {
            this.chargerModeration();
        } else if (tab === 'activity') {
            this.chargerActivite();
        }
    }

    /**
     * Charger la liste des utilisateurs
     */
    async chargerUtilisateurs(recherche = '') {
        const tbody = document.getElementById('table-users-body');
        tbody.innerHTML = '<tr><td colspan="8" class="admin-table__loading"><i class="fas fa-spinner fa-spin"></i> Chargement...</td></tr>';

        try {
            const params = new URLSearchParams({
                action: 'utilisateurs',
                page: this.currentUserPage,
                limite: 20,
                filtre: this.userFilter,
                recherche: recherche
            });

            const response = await fetch(`${this.apiUrl}?${params}`);
            const result = await response.json();

            if (result.success) {
                this.afficherUtilisateurs(result.data);
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="admin-table__error">${echapperHTML(result.error)}</td></tr>`;
            }
        } catch (error) {
            console.error('Erreur:', error);
            tbody.innerHTML = '<tr><td colspan="8" class="admin-table__error">Erreur de chargement</td></tr>';
        }
    }

    /**
     * Afficher les utilisateurs
     */
    afficherUtilisateurs(data) {
        const tbody = document.getElementById('table-users-body');
        
        if (!data.utilisateurs || data.utilisateurs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="admin-table__empty">Aucun utilisateur trouvé</td></tr>';
            return;
        }

        tbody.innerHTML = data.utilisateurs.map(user => {
            const firstName = echapperHTML(user.first_name || '');
            const lastName = echapperHTML(user.last_name || '');
            const fullName = `${firstName} ${lastName}`;
            const estBanni = !!user.banned_at;
            const estAdmin = user.role === 'admin';
            const estMoi = user.id === this.currentUserId;
            
            return `
            <tr class="${estBanni ? 'row-banned' : ''} ${estMoi ? 'row-current-user' : ''}">
                <td>#${user.id}</td>
                <td title="${fullName}">
                    <div class="user-info" style="cursor: pointer;" onclick="window.admin.voirUtilisateur(${user.id})">
                        ${user.avatar_path ? 
                            `<img src="${echapperHTML(user.avatar_path)}" alt="" class="user-avatar">` : 
                            '<span class="user-avatar user-avatar--default">👤</span>'
                        }
                        <span>${fullName}${estMoi ? ' <em style="color: var(--couleur-principale);">(moi)</em>' : ''}</span>
                    </div>
                </td>
                <td title="${echapperHTML(user.email)}">${echapperHTML(user.email)}</td>
                <td title="${echapperHTML(user.phone || '-')}">${echapperHTML(user.phone || '-')}</td>
                <td>
                    ${estBanni ? 
                        '<span class="badge badge--danger">Banni</span>' : 
                        (user.email_verified_at ? 
                            '<span class="badge badge--success">Vérifié</span>' : 
                            '<span class="badge badge--warning">Non vérifié</span>'
                        )
                    }
                    ${estAdmin ? '<span class="badge badge--primary">Admin</span>' : ''}
                </td>
                <td>${user.nb_annonces || 0}</td>
                <td>${this.formaterDate(user.created_at)}</td>
                <td class="admin-table__actions">
                    <div class="actions-wrapper">
                    <button class="btn-icon btn-icon--info" 
                            onclick="window.admin.voirUtilisateur(${user.id});" 
                            title="Voir les détails">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${estMoi ? '' : (estBanni ? `
                        <button class="btn-icon btn-icon--success" 
                                onclick="window.admin.debannirUtilisateur(${user.id}, '${fullName.replace(/'/g, "\\'")}');" 
                                title="Débannir">
                            <i class="fas fa-user-check"></i>
                        </button>
                    ` : `
                        <button class="btn-icon btn-icon--warning" 
                                onclick="window.admin.bannirUtilisateur(${user.id}, '${fullName.replace(/'/g, "\\'")}');" 
                                title="Bannir">
                            <i class="fas fa-user-slash"></i>
                        </button>
                    `)}
                    ${estMoi ? '' : `
                    <button class="btn-icon btn-icon--${estAdmin ? 'secondary' : 'primary'}" 
                            onclick="window.admin.changerRole(${user.id}, '${estAdmin ? 'user' : 'admin'}', '${fullName.replace(/'/g, "\\'")}');" 
                            title="${estAdmin ? 'Retirer admin' : 'Promouvoir admin'}">
                        <i class="fas fa-${estAdmin ? 'user' : 'crown'}"></i>
                    </button>
                    `}
                    ${estMoi ? '' : `
                    <button class="btn-icon btn-icon--danger" 
                            onclick="window.admin.supprimerUtilisateur(${user.id}, '${fullName.replace(/'/g, "\\'")}');" 
                            title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                    `}
                    </div>
                </td>
            </tr>
        `;
        }).join('');

        this.afficherPagination('users', data.page, data.pages_total);
    }

    /**
     * Voir les détails d'un utilisateur
     */
    async voirUtilisateur(id) {
        try {
            const response = await fetch(`${this.apiUrl}?action=utilisateur_complet&id=${id}`);
            const result = await response.json();

            if (result.success) {
                this.afficherModaleUtilisateur(result.data);
            } else {
                this.afficherNotification(result.error, 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherNotification('Erreur lors du chargement', 'error');
        }
    }

    /**
     * Afficher la modale utilisateur
     */
    afficherModaleUtilisateur(user) {
        // Supprimer modale existante
        document.getElementById('modal-utilisateur')?.remove();
        
        const estBanni = !!user.banned_at;
        const estAdmin = user.role === 'admin';
        const estMoi = user.id === this.currentUserId;
        
        // Conteneur modal
        const modalContainer = document.createElement('div');
        modalContainer.id = 'modal-utilisateur';
        modalContainer.className = 'modal';
        modalContainer.style.cssText = 'display: flex; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 10000; align-items: center; justify-content: center; padding: 1rem;';
        
        // Générer les annonces si l'utilisateur en a
        let annoncesHtml = '';
        if (user.annonces && user.annonces.length > 0) {
            annoncesHtml = `
                <div style="background: var(--arriere-plan); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <h5 style="margin: 0 0 12px; color: var(--couleur-principale); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-car"></i> Annonces (${user.annonces.length})
                    </h5>
                    <div style="display: flex; flex-direction: column; gap: 8px; max-height: 200px; overflow-y: auto;">
                        ${user.annonces.map(a => `
                            <a href="vehicule?id=${a.id}" target="_blank" style="display: flex; align-items: center; gap: 10px; padding: 10px; background: var(--surface); border-radius: 6px; text-decoration: none; color: inherit; transition: transform 0.2s;">
                                <img src="${echapperHTML(a.image_path || '/assets/images/default-car.jpg')}" alt="" style="width: 50px; height: 35px; object-fit: cover; border-radius: 4px;">
                                <div style="flex: 1; min-width: 0;">
                                    <p style="margin: 0; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${echapperHTML(a.marque)} ${echapperHTML(a.modele)}</p>
                                    <p style="margin: 2px 0 0; font-size: 0.85rem; color: var(--texte-secondaire);">${a.annee} • ${this.formaterMonnaie(a.prix)}</p>
                                </div>
                                <span class="badge badge--${a.status === 'public' ? 'success' : a.status === 'en_attente' ? 'warning' : 'secondary'}" style="font-size: 0.7rem;">${a.status}</span>
                            </a>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        
        modalContainer.innerHTML = `
            <div class="modal-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);"></div>
            <div class="modal-content" style="position: relative; max-width: 550px; width: 100%; max-height: 85vh; overflow-y: auto; background: var(--surface); border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.3);">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem; border-bottom: 1px solid var(--bordure);">
                    <h3 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-user" style="color: var(--couleur-principale);"></i> Détails de l'utilisateur
                    </h3>
                    <button class="modal-close" style="width: 36px; height: 36px; border: none; background: transparent; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--texte-secondaire); transition: all 0.2s;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" style="padding: 1.25rem;">
                    <!-- Avatar et nom -->
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                        ${user.avatar_path ? 
                            `<img src="${echapperHTML(user.avatar_path)}" alt="" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 3px solid var(--couleur-principale);">` :
                            '<div style="width: 70px; height: 70px; border-radius: 50%; background: var(--arriere-plan); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; border: 3px solid var(--bordure);">👤</div>'
                        }
                        <div>
                            <h4 style="margin: 0; font-size: 1.3rem;">${echapperHTML(user.first_name || '')} ${echapperHTML(user.last_name || '')}</h4>
                            <p style="margin: 4px 0 0; color: var(--texte-secondaire); font-size: 0.9rem;">ID #${user.id}</p>
                            <div style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap;">
                                ${estBanni ? '<span class="badge badge--danger">Banni</span>' : ''}
                                ${estAdmin ? '<span class="badge badge--primary">Admin</span>' : '<span class="badge badge--secondary">Utilisateur</span>'}
                                ${user.email_verified_at ? '<span class="badge badge--success">Vérifié</span>' : '<span class="badge badge--warning">Non vérifié</span>'}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Infos contact -->
                    <div style="background: var(--arriere-plan); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <h5 style="margin: 0 0 10px; color: var(--couleur-principale); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-address-card"></i> Contact
                        </h5>
                        <div style="display: grid; gap: 8px;">
                            <p style="margin: 0; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-envelope" style="width: 16px; color: var(--texte-secondaire);"></i>
                                <span>${echapperHTML(user.email)}</span>
                            </p>
                            <p style="margin: 0; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-phone" style="width: 16px; color: var(--texte-secondaire);"></i>
                                <span>${echapperHTML(user.phone || 'Non renseigné')}</span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Statistiques -->
                    <div style="background: var(--arriere-plan); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <h5 style="margin: 0 0 12px; color: var(--couleur-principale); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-chart-bar"></i> Statistiques
                        </h5>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center;">
                            <div style="background: var(--surface); padding: 12px 8px; border-radius: 8px;">
                                <p style="margin: 0; font-size: 1.4rem; font-weight: 700; color: var(--couleur-principale);">${user.nb_annonces || 0}</p>
                                <p style="margin: 4px 0 0; font-size: 0.75rem; color: var(--texte-secondaire);">Annonces</p>
                            </div>
                            <div style="background: var(--surface); padding: 12px 8px; border-radius: 8px;">
                                <p style="margin: 0; font-size: 1.4rem; font-weight: 700; color: var(--couleur-principale);">${user.nb_favoris || 0}</p>
                                <p style="margin: 4px 0 0; font-size: 0.75rem; color: var(--texte-secondaire);">Favoris</p>
                            </div>
                            <div style="background: var(--surface); padding: 12px 8px; border-radius: 8px;">
                                <p style="margin: 0; font-size: 1.4rem; font-weight: 700; color: var(--couleur-principale);">${user.nb_conversations || 0}</p>
                                <p style="margin: 4px 0 0; font-size: 0.75rem; color: var(--texte-secondaire);">Messages</p>
                            </div>
                        </div>
                    </div>
                    
                    ${annoncesHtml}
                    
                    <!-- Dates -->
                    <div style="background: var(--arriere-plan); padding: 15px; border-radius: 8px; ${estBanni ? 'margin-bottom: 15px;' : ''}">
                        <h5 style="margin: 0 0 10px; color: var(--couleur-principale); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-calendar-alt"></i> Historique
                        </h5>
                        <p style="margin: 0 0 5px; font-size: 0.9rem;"><strong>Inscription :</strong> ${this.formaterDate(user.created_at)}</p>
                        <p style="margin: 0; font-size: 0.9rem;"><strong>Dernière connexion :</strong> ${user.last_login_at ? this.formaterDate(user.last_login_at) : 'Jamais'}</p>
                    </div>
                    
                    ${estBanni ? `
                    <!-- Info bannissement -->
                    <div style="background: rgba(239, 68, 68, 0.1); padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444;">
                        <h5 style="margin: 0 0 10px; color: #f87171; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-ban"></i> Bannissement
                        </h5>
                        <p style="margin: 0 0 5px; font-size: 0.9rem;"><strong>Date :</strong> ${this.formaterDate(user.banned_at)}</p>
                        <p style="margin: 0; font-size: 0.9rem;"><strong>Raison :</strong> ${echapperHTML(user.ban_reason || 'Non spécifiée')}</p>
                    </div>
                    ` : ''}
                </div>
                <div class="modal-footer" style="padding: 1rem 1.25rem; border-top: 1px solid var(--bordure); display: flex; gap: 10px; justify-content: flex-end;">
                    ${estMoi ? '' : (estBanni ? `
                        <button class="bouton bouton--succes bouton--small" onclick="window.admin.debannirUtilisateur(${user.id}, '${echapperHTML(user.first_name)} ${echapperHTML(user.last_name)}'); document.getElementById('modal-utilisateur').remove();">
                            <i class="fas fa-user-check"></i> Débannir
                        </button>
                    ` : `
                        <button class="bouton bouton--alerte bouton--small" onclick="window.admin.bannirUtilisateur(${user.id}, '${echapperHTML(user.first_name)} ${echapperHTML(user.last_name)}'); document.getElementById('modal-utilisateur').remove();">
                            <i class="fas fa-user-slash"></i> Bannir
                        </button>
                    `)}
                    <button class="bouton bouton--secondaire bouton--small" onclick="document.getElementById('modal-utilisateur').remove();">
                        Fermer
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modalContainer);
        
        // Événement fermeture
        modalContainer.querySelector('.modal-overlay').addEventListener('click', () => modalContainer.remove());
        modalContainer.querySelector('.modal-close').addEventListener('click', () => modalContainer.remove());
    }
    
    /**
     * Formater un montant en euros
     */
    formaterMonnaie(montant) {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(montant || 0);
    }

    /**
     * Bannir un utilisateur
     */
    async bannirUtilisateur(id, nom) {
        const raison = await this.afficherModaleBannissement(nom);
        if (!raison) return;
        
        try {
            const response = await fetch(`${this.apiUrl}?action=bannir`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ user_id: id, raison })
            });

            const result = await response.json();

            if (result.success) {
                this.afficherNotification(`${nom} a été banni`, 'success');
                await this.chargerUtilisateurs();
                await this.chargerResume();
            } else {
                this.afficherNotification(result.error, 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherNotification('Erreur lors du bannissement', 'error');
        }
    }

    /**
     * Afficher la modale de bannissement
     */
    async afficherModaleBannissement(nom) {
        return new Promise((resolve) => {
            // Supprimer modale existante
            document.getElementById('modal-ban')?.remove();
            
            const modalContainer = document.createElement('div');
            modalContainer.id = 'modal-ban';
            modalContainer.style.cssText = 'display: flex; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 10001; align-items: center; justify-content: center; padding: 1rem;';
            
            modalContainer.innerHTML = `
                <div class="modal-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);"></div>
                <div class="modal-content" style="position: relative; max-width: 450px; width: 100%; background: var(--surface); border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.3);">
                    <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem; border-bottom: 1px solid var(--bordure);">
                        <h3 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-user-slash" style="color: #ef4444;"></i> Bannir l'utilisateur
                        </h3>
                        <button class="modal-close" style="width: 36px; height: 36px; border: none; background: transparent; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--texte-secondaire);">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body" style="padding: 1.25rem;">
                        <div style="background: rgba(239, 68, 68, 0.1); padding: 12px; border-radius: 8px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                            <span>Vous allez bannir <strong>${echapperHTML(nom)}</strong></span>
                        </div>
                        <p style="color: var(--texte-secondaire); font-size: 0.9rem; margin: 0 0 15px; line-height: 1.5;">
                            L'utilisateur ne pourra plus se connecter et recevra un email l'informant de son bannissement.
                        </p>
                        <label for="ban-raison" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">
                            <i class="fas fa-comment-alt" style="margin-right: 6px; color: var(--texte-secondaire);"></i>
                            Raison du bannissement
                        </label>
                        <textarea id="ban-raison" rows="3" 
                                  placeholder="Ex: Comportement inapproprié, spam, fraude..."
                                  style="width: 100%; padding: 12px; border: 2px solid var(--bordure); border-radius: 8px; background: var(--arriere-plan); color: var(--texte); font-family: inherit; font-size: 0.95rem; resize: vertical; transition: border-color 0.2s;"></textarea>
                    </div>
                    <div class="modal-footer" style="padding: 1rem 1.25rem; border-top: 1px solid var(--bordure); display: flex; gap: 10px; justify-content: flex-end;">
                        <button class="bouton bouton--secondaire bouton--small" id="btn-ban-cancel">
                            Annuler
                        </button>
                        <button class="bouton bouton--alerte bouton--small" id="btn-ban-confirm">
                            <i class="fas fa-ban"></i> Confirmer
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modalContainer);
            
            const textarea = modalContainer.querySelector('#ban-raison');
            textarea.focus();
            
            // Style focus textarea
            textarea.addEventListener('focus', () => textarea.style.borderColor = 'var(--couleur-principale)');
            textarea.addEventListener('blur', () => textarea.style.borderColor = 'var(--bordure)');
            
            // Événements
            const fermer = () => {
                modalContainer.remove();
                resolve(null);
            };
            
            modalContainer.querySelector('.modal-overlay').addEventListener('click', fermer);
            modalContainer.querySelector('.modal-close').addEventListener('click', fermer);
            modalContainer.querySelector('#btn-ban-cancel').addEventListener('click', fermer);
            
            modalContainer.querySelector('#btn-ban-confirm').addEventListener('click', () => {
                const raison = textarea.value.trim();
                if (!raison) {
                    this.afficherNotification('Veuillez indiquer une raison', 'warning');
                    textarea.style.borderColor = '#ef4444';
                    textarea.focus();
                    return;
                }
                modalContainer.remove();
                resolve(raison);
            });
        });
    }

    /**
     * Débannir un utilisateur
     */
    async debannirUtilisateur(id, nom) {
        const confirme = await this.afficherModale(
            'Débannir l\'utilisateur',
            `Êtes-vous sûr de vouloir débannir <strong>${nom}</strong> ? Il pourra à nouveau se connecter.`
        );

        if (!confirme) return;

        try {
            const response = await fetch(`${this.apiUrl}?action=debannir`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ user_id: id })
            });

            const result = await response.json();

            if (result.success) {
                this.afficherNotification(`${nom} a été débanni`, 'success');
                await this.chargerUtilisateurs();
                await this.chargerResume();
            } else {
                this.afficherNotification(result.error, 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherNotification('Erreur lors du débannissement', 'error');
        }
    }

    /**
     * Changer le rôle d'un utilisateur
     */
    async changerRole(id, nouveauRole, nom) {
        const actionTexte = nouveauRole === 'admin' ? 'promouvoir administrateur' : 'retirer les droits admin de';
        const confirme = await this.afficherModale(
            'Modifier le rôle',
            `Êtes-vous sûr de vouloir ${actionTexte} <strong>${nom}</strong> ?`
        );

        if (!confirme) return;

        try {
            const response = await fetch(`${this.apiUrl}?action=changer_role`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ user_id: id, role: nouveauRole })
            });

            const result = await response.json();

            if (result.success) {
                this.afficherNotification(result.message, 'success');
                await this.chargerUtilisateurs();
            } else {
                this.afficherNotification(result.error, 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherNotification('Erreur lors du changement de rôle', 'error');
        }
    }

    /**
     * Charger la liste des véhicules
     */
    async chargerVehicules(recherche = '') {
        const tbody = document.getElementById('table-vehicles-body');
        tbody.innerHTML = '<tr><td colspan="9" class="admin-table__loading"><i class="fas fa-spinner fa-spin"></i> Chargement...</td></tr>';

        try {
            const params = new URLSearchParams({
                action: 'vehicules',
                page: this.currentVehiclePage,
                limite: 20,
                filtre: this.vehicleFilter,
                recherche: recherche
            });

            const response = await fetch(`${this.apiUrl}?${params}`);
            const result = await response.json();

            if (result.success) {
                this.afficherVehicules(result.data);
            } else {
                tbody.innerHTML = `<tr><td colspan="9" class="admin-table__error">${echapperHTML(result.error)}</td></tr>`;
            }
        } catch (error) {
            console.error('Erreur:', error);
            tbody.innerHTML = '<tr><td colspan="9" class="admin-table__error">Erreur de chargement</td></tr>';
        }
    }

    /**
     * Afficher les véhicules
     */
    afficherVehicules(data) {
        const tbody = document.getElementById('table-vehicles-body');
        
        if (!data.vehicules || data.vehicules.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="admin-table__empty">Aucun véhicule trouvé</td></tr>';
            return;
        }

        tbody.innerHTML = data.vehicules.map(vehicle => {
            const marque = echapperHTML(vehicle.marque || '');
            const modele = echapperHTML(vehicle.modele || '');
            const fullName = `${marque} ${modele}`;
            
            // Déterminer le badge selon le statut
            let badgeClass, badgeText;
            switch (vehicle.status) {
                case 'public':
                    badgeClass = 'success';
                    badgeText = 'Public';
                    break;
                case 'prive':
                    badgeClass = 'secondary';
                    badgeText = 'Privé';
                    break;
                case 'en_attente':
                    badgeClass = 'warning';
                    badgeText = 'En attente';
                    break;
                case 'refuse':
                    badgeClass = 'danger';
                    badgeText = 'Refusé';
                    break;
                default:
                    badgeClass = 'secondary';
                    badgeText = vehicle.status;
            }
            
            // Générer les boutons d'action selon le statut
            let actionsHtml = '';
            if (vehicle.status === 'en_attente') {
                // Annonces en attente : Approuver/Refuser
                actionsHtml = `
                    <button class="btn-icon btn-icon--success" 
                            onclick="window.admin.modererRapide(${vehicle.id}, 'public');" 
                            title="Approuver">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="btn-icon btn-icon--danger" 
                            onclick="window.admin.modererRapide(${vehicle.id}, 'refuse');" 
                            title="Refuser">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            } else if (vehicle.status === 'public' || vehicle.status === 'prive') {
                // Annonces publiques/privées : toggle visibilité
                actionsHtml = `
                    <button class="btn-icon btn-icon--${vehicle.status === 'public' ? 'warning' : 'success'}" 
                            onclick="window.admin.changerStatutVehicule(${vehicle.id}, '${vehicle.status === 'public' ? 'prive' : 'public'}');" 
                            title="${vehicle.status === 'public' ? 'Masquer' : 'Publier'}">
                        <i class="fas fa-${vehicle.status === 'public' ? 'eye-slash' : 'eye'}"></i>
                    </button>
                `;
            }
            // Pour les annonces refusées : pas de bouton toggle, seulement suppression
            
            return `
            <tr>
                <td>#${vehicle.id}</td>
                <td>
                    <a href="vehicule?id=${vehicle.id}" target="_blank" style="color: inherit; text-decoration: none;" title="Voir l'annonce">
                        <strong style="color: var(--couleur-principale);">${fullName}</strong><br>
                        <small>${vehicle.annee}</small>
                    </a>
                </td>
                <td>${echapperHTML(vehicle.first_name || '')} ${echapperHTML(vehicle.last_name || '')}</td>
                <td><strong>${formaterMonnaie(vehicle.prix)}</strong></td>
                <td>
                    <span class="badge badge--${badgeClass}">
                        ${badgeText}
                    </span>
                </td>
                <td>${vehicle.views_count || 0}</td>
                <td>${vehicle.nb_favoris || 0}</td>
                <td>${this.formaterDate(vehicle.created_at)}</td>
                <td class="admin-table__actions">
                    <div class="actions-wrapper">
                    <a href="vehicule?id=${vehicle.id}" target="_blank" class="btn-icon btn-icon--secondary" title="Voir l'annonce">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    ${actionsHtml}
                    <button class="btn-icon btn-icon--danger" 
                            onclick="window.admin.supprimerVehicule(${vehicle.id}, '${fullName.replace(/'/g, "\\'")}');" 
                            title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                    </div>
                </td>
            </tr>
        `;
        }).join('');

        this.afficherPagination('vehicles', data.page, data.pages_total);
    }

    /**
     * Afficher l'activité récente (depuis les logs permanents)
     */
    afficherActivite(data = null) {
        const timeline = document.getElementById('activity-timeline');
        
        // Nouvelle structure: data.logs contient les activités
        const activites = data?.logs || data || [];

        if (!activites || activites.length === 0) {
            timeline.innerHTML = '<div class="activity-item activity-item--empty">Aucune activité récente</div>';
            // Masquer la pagination si pas de résultats
            const paginationContainer = document.getElementById('pagination-activite');
            if (paginationContainer) paginationContainer.innerHTML = '';
            return;
        }

        timeline.innerHTML = activites.map(act => {
            let icon, text, colorClass;
            const details = act.details || {};
            const adminNom = act.admin_nom_complet ? `<span style="color: #f59e0b; font-weight: 600;">${echapperHTML(act.admin_nom_complet)}</span> ` : '';

            switch (act.type) {
                case 'inscription':
                    icon = 'user-plus';
                    text = `<strong>${echapperHTML(details.prenom || '')} ${echapperHTML(details.nom || '')}</strong> s'est inscrit(e)`;
                    colorClass = 'primary';
                    break;
                case 'annonce':
                    icon = 'car';
                    text = `<strong>${echapperHTML(details.prenom || '')} ${echapperHTML(details.nom || '')}</strong> a ajouté ${echapperHTML(details.marque || '')} ${echapperHTML(details.modele || '')}`;
                    colorClass = 'success';
                    break;
                case 'suppression':
                case 'suppression_compte':
                case 'suppression_annonce':
                    icon = 'trash';
                    if (details.marque) {
                        text = `${adminNom}Annonce supprimée : <strong>${echapperHTML(details.marque)} ${echapperHTML(details.modele || '')}</strong>`;
                    } else {
                        text = `${adminNom}Compte supprimé : <strong>${echapperHTML(details.prenom || '')} ${echapperHTML(details.nom || '')}</strong> (${echapperHTML(details.email || '')})`;
                    }
                    colorClass = 'danger';
                    break;
                case 'moderation':
                    icon = 'gavel';
                    const action = act.action.includes('approuvée') ? 'a approuvé' : 'a refusé';
                    text = `${adminNom}${action} l'annonce : <strong>${echapperHTML(details.marque || '')} ${echapperHTML(details.modele || '')}</strong>`;
                    if (details.raison) {
                        text += ` <em style="color: #9ca3af;">(${echapperHTML(details.raison)})</em>`;
                    }
                    colorClass = act.action.includes('approuvée') ? 'success' : 'warning';
                    break;
                case 'contact':
                    icon = 'envelope';
                    const statusContact = details.status === 'archive' ? 'archivé' : 'traité';
                    text = `${adminNom}Message de contact ${statusContact}`;
                    colorClass = 'info';
                    break;
                case 'conversation':
                    icon = 'comments';
                    text = `Conversation entre <strong>${echapperHTML(details.acheteur || '')}</strong> et <strong>${echapperHTML(details.vendeur || '')}</strong>`;
                    colorClass = 'info';
                    break;
                default:
                    icon = 'info-circle';
                    text = `${adminNom}${act.action || 'Activité inconnue'}`;
                    colorClass = 'secondary';
            }

            return `
                <div class="activity-item activity-item--${colorClass}">
                    <div class="activity-item__icon">
                        <i class="fas fa-${icon}"></i>
                    </div>
                    <div class="activity-item__content">
                        <p>${text}</p>
                        <small>${this.formaterDate(act.date)}</small>
                    </div>
                </div>
            `;
        }).join('');
        
        // Afficher la pagination si données disponibles
        if (data?.page && data?.pages_total) {
            this.afficherPagination('activite', data.page, data.pages_total);
        }
    }

    /**
     * Charger les statistiques détaillées
     */
    async chargerStatistiques() {
        try {
            const response = await fetch(`${this.apiUrl}?action=statistiques`);
            const result = await response.json();

            if (result.success) {
                this.afficherStatistiquesDetaillees(result.data);
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    /**
     * Afficher les statistiques détaillées
     */
    afficherStatistiquesDetaillees(data) {
        // Top marques
        const topBrands = document.getElementById('chart-top-brands');
        if (data.top_marques && data.top_marques.length > 0) {
            const maxValue = Math.max(...data.top_marques.map(item => parseInt(item.nb_annonces)));
            topBrands.innerHTML = data.top_marques.map((item, index) => {
                const percentage = (parseInt(item.nb_annonces) / maxValue) * 100;
                return `
                    <div class="stat-item">
                        <div style="display: flex; align-items: center; gap: 1rem; width: 100%;">
                            <span class="stat-item__rank">#${index + 1}</span>
                            <span class="stat-item__label">${echapperHTML(item.marque)}</span>
                            <span class="stat-item__value">${item.nb_annonces}</span>
                        </div>
                        <div class="stat-progress">
                            <div class="stat-progress__bar" style="width: ${percentage}%;"></div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            topBrands.innerHTML = '<p class="text-muted" style="padding: 1rem; text-align: center;">Aucune donnée disponible</p>';
        }

        // Répartition par type
        const vehicleTypes = document.getElementById('chart-vehicle-types');
        if (data.repartition_types && data.repartition_types.length > 0) {
            const total = data.repartition_types.reduce((sum, item) => sum + parseInt(item.nb_annonces), 0);
            const colors = {
                'voiture': '#3498db',
                'moto': '#e74c3c',
                'camion': '#f39c12'
            };
            
            vehicleTypes.innerHTML = data.repartition_types.map(item => {
                const percentage = ((parseInt(item.nb_annonces) / total) * 100).toFixed(1);
                const color = colors[item.type_vehicule] || '#95a5a6';
                return `
                    <div class="stat-item">
                        <div style="display: flex; align-items: center; gap: 1rem; width: 100%;">
                            <div class="type-indicator" style="background: ${color};"></div>
                            <span class="stat-item__label" style="flex: 1; text-transform: capitalize;">${echapperHTML(item.type_vehicule)}</span>
                            <span class="stat-item__value">${item.nb_annonces} <small style="color: var(--texte-secondaire);">(${percentage}%)</small></span>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            vehicleTypes.innerHTML = '<p class="text-muted" style="padding: 1rem; text-align: center;">Aucune donnée disponible</p>';
        }

        // Évolution sur 7 jours
        const evolution = document.getElementById('chart-evolution');
        if (data.evolution_inscriptions && data.evolution_annonces) {
            const dates = {};
            
            // Regrouper par date
            data.evolution_inscriptions.forEach(item => {
                if (!dates[item.date]) dates[item.date] = { inscriptions: 0, annonces: 0 };
                dates[item.date].inscriptions = parseInt(item.nb_inscriptions);
            });
            
            data.evolution_annonces.forEach(item => {
                if (!dates[item.date]) dates[item.date] = { inscriptions: 0, annonces: 0 };
                dates[item.date].annonces = parseInt(item.nb_annonces);
            });
            
            const sortedDates = Object.keys(dates).sort();
            
            evolution.innerHTML = sortedDates.map(date => {
                const formattedDate = new Date(date).toLocaleDateString('fr-FR', { 
                    weekday: 'short', 
                    day: 'numeric', 
                    month: 'short' 
                });
                return `
                    <div class="stat-item">
                        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                            <span class="stat-item__label">${formattedDate}</span>
                            <div style="display: flex; gap: 1.5rem; align-items: center;">
                                <span style="color: var(--couleur-principale); display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-user-plus"></i>
                                    <strong>${dates[date].inscriptions}</strong>
                                </span>
                                <span style="color: #27ae60; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-car"></i>
                                    <strong>${dates[date].annonces}</strong>
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            evolution.innerHTML = '<p class="text-muted" style="padding: 1rem; text-align: center;">Aucune donnée disponible</p>';
        }
    }

    /**
     * Charger les compteurs pour les badges
     */
    async chargerCompteurs() {
        try {
            const response = await fetch(`${this.apiUrl}?action=compteurs_moderation`);
            const result = await response.json();

            if (result.success) {
                const { en_attente, contacts_nouveaux } = result.data;
                
                // Badge modération
                const badgeModeration = document.getElementById('badge-moderation');
                if (badgeModeration) {
                    if (en_attente > 0) {
                        badgeModeration.textContent = en_attente;
                        badgeModeration.style.display = 'inline-block';
                    } else {
                        badgeModeration.style.display = 'none';
                    }
                }
                
                // Badge contacts
                const badgeContacts = document.getElementById('badge-contacts');
                if (badgeContacts) {
                    if (contacts_nouveaux > 0) {
                        badgeContacts.textContent = contacts_nouveaux;
                        badgeContacts.style.display = 'inline-block';
                    } else {
                        badgeContacts.style.display = 'none';
                    }
                }
            }
        } catch (error) {
            console.error('Erreur chargement compteurs:', error);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // GESTION DES CONTACTS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Charger les contacts
     */
    async chargerContacts() {
        const tbody = document.getElementById('table-contacts-body');
        tbody.innerHTML = '<tr><td colspan="7" class="admin-table__loading"><i class="fas fa-spinner fa-spin"></i> Chargement...</td></tr>';

        try {
            const params = new URLSearchParams({
                action: 'contacts',
                page: this.currentContactPage,
                limite: 20,
                filtre: this.contactFilter
            });

            // Ajouter les filtres de date si renseignés
            const dateDebut = document.getElementById('filter-contacts-date-debut')?.value;
            const dateFin = document.getElementById('filter-contacts-date-fin')?.value;
            
            if (dateDebut) {
                params.append('date_debut', dateDebut);
            }
            if (dateFin) {
                params.append('date_fin', dateFin + ' 23:59:59');
            }

            const response = await fetch(`${this.apiUrl}?${params}`);
            const result = await response.json();

            if (result.success) {
                this.afficherContacts(result.data);
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="admin-table__error">${echapperHTML(result.error)}</td></tr>`;
            }
        } catch (error) {
            console.error('Erreur:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="admin-table__error">Erreur de chargement</td></tr>';
        }
    }

    /**
     * Afficher les contacts dans le tableau
     */
    afficherContacts(data) {
        const tbody = document.getElementById('table-contacts-body');
        
        if (!data.contacts || data.contacts.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="admin-table__empty">Aucun message de contact</td></tr>';
            return;
        }

        const statusLabels = {
            'nouveau': { label: 'Nouveau', class: 'primary' },
            'lu': { label: 'Lu', class: 'info' },
            'traite': { label: 'Traité', class: 'success' },
            'archive': { label: 'Archivé', class: 'secondary' }
        };

        tbody.innerHTML = data.contacts.map(contact => {
            const status = statusLabels[contact.status] || statusLabels['nouveau'];
            const estArchive = contact.status === 'archive';
            const aRepondu = !!contact.reponse;
            
            // Tronquer les textes longs pour l'affichage
            const nomAffiche = this.tronquerTexte(contact.nom, 20);
            const emailAffiche = this.tronquerTexte(contact.email, 25);
            const sujetAffiche = this.tronquerTexte(contact.sujet || 'Sans sujet', 30);
            
            // Afficher le badge "répondu" si le contact a une réponse (même archivé)
            let badgeHtml = `<span class="badge badge--${status.class}">${status.label}</span>`;
            if (aRepondu && estArchive) {
                badgeHtml += ` <span class="badge badge--success" title="Réponse envoyée"><i class="fas fa-reply"></i></span>`;
            }
            
            return `
            <tr class="${contact.status === 'nouveau' ? 'row-highlight' : ''}">
                <td>#${contact.id}</td>
                <td>${this.formaterDate(contact.created_at)}</td>
                <td title="${echapperHTML(contact.nom)}">${echapperHTML(nomAffiche)}</td>
                <td title="${echapperHTML(contact.email)}">${echapperHTML(emailAffiche)}</td>
                <td title="${echapperHTML(contact.sujet || 'Sans sujet')}">${echapperHTML(sujetAffiche)}</td>
                <td>
                    ${badgeHtml}
                </td>
                <td class="admin-table__actions">
                    <div class="actions-wrapper">
                    <button class="btn-icon btn-icon--primary" 
                            onclick="window.admin.voirContact(${contact.id}, ${aRepondu});" 
                            title="Voir le message">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${!estArchive ? `
                    <button class="btn-icon btn-icon--secondary" 
                            onclick="window.admin.archiverContact(${contact.id});" 
                            title="Archiver">
                        <i class="fas fa-archive"></i>
                    </button>
                    ` : `
                    <button class="btn-icon btn-icon--info" 
                            onclick="window.admin.desarchiverContact(${contact.id}, ${aRepondu});" 
                            title="Retirer des archives">
                        <i class="fas fa-inbox"></i>
                    </button>
                    `}
                    </div>
                </td>
            </tr>
        `;
        }).join('');

        this.afficherPagination('contacts', data.page, data.pages_total);
    }

    /**
     * Afficher/masquer le bouton de réinitialisation des dates pour les contacts
     */
    afficherBoutonResetDateContacts() {
        const dateDebut = document.getElementById('filter-contacts-date-debut')?.value;
        const dateFin = document.getElementById('filter-contacts-date-fin')?.value;
        const btnReset = document.getElementById('btn-reset-date-contacts');
        
        if (btnReset) {
            btnReset.style.display = (dateDebut || dateFin) ? 'inline-block' : 'none';
        }
    }

    /**
     * Afficher/masquer le bouton de réinitialisation des dates pour l'activité
     */
    afficherBoutonResetDateActivite() {
        const dateDebut = document.getElementById('date-debut')?.value;
        const dateFin = document.getElementById('date-fin')?.value;
        const btnReset = document.getElementById('btn-reset-date-activite');
        
        if (btnReset) {
            btnReset.style.display = (dateDebut || dateFin) ? 'inline-block' : 'none';
        }
    }

    /**
     * Voir un contact dans une modale (estTraite indique si on ne peut plus répondre)
     */
    async voirContact(id, aRepondu = false) {
        try {
            // Ne pas passer lecture_seule pour que le statut passe en 'lu' si nouveau
            const response = await fetch(`${this.apiUrl}?action=contact&id=${id}`);
            const result = await response.json();

            if (result.success) {
                const contact = result.data;
                this.currentContactId = id;
                const aReponse = !!contact.reponse;
                const estArchive = contact.status === 'archive';
                
                document.getElementById('contact-nom').textContent = contact.nom;
                document.getElementById('contact-email').textContent = contact.email;
                document.getElementById('contact-date').textContent = this.formaterDate(contact.created_at);
                document.getElementById('contact-sujet').textContent = contact.sujet || 'Sans sujet';
                document.getElementById('contact-message-content').textContent = contact.message;
                
                const btnRepondre = document.getElementById('btn-repondre-contact');
                const reponseContainer = document.getElementById('contact-reponse-container');
                const btnArchiver = document.getElementById('btn-archiver-contact');
                
                // CAS 1 : Le contact a une réponse - afficher en lecture seule
                if (aReponse) {
                    if (reponseContainer) {
                        reponseContainer.innerHTML = `
                            <div style="padding: 15px; background: rgba(34, 197, 94, 0.15); border-radius: 8px; border-left: 4px solid #22c55e;">
                                <p style="margin: 0 0 5px; color: #22c55e; font-weight: 600;"><i class="fas fa-check-circle"></i> Réponse envoyée :</p>
                                <p style="margin: 0; color: #e5e7eb;">${this.echapperHTML(contact.reponse)}</p>
                                <p style="margin: 10px 0 0; color: #9ca3af; font-size: 0.85rem;">
                                    <i class="fas fa-clock"></i> Répondu le ${contact.repondu_le ? this.formaterDate(contact.repondu_le) : 'N/A'}
                                </p>
                            </div>
                            ${estArchive ? `
                                <button id="btn-desarchiver-modal" style="margin-top: 15px; width: 100%; padding: 12px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)';" onmouseout="this.style.transform=''; this.style.boxShadow='';"><i class="fas fa-inbox"></i> Retirer des archives</button>
                            ` : ''}
                        `;
                        // Ajouter l'événement pour désarchiver si archivé
                        if (estArchive) {
                            setTimeout(() => {
                                document.getElementById('btn-desarchiver-modal')?.addEventListener('click', () => {
                                    this.desarchiverContact(contact.id, true);
                                    this.fermerModaleContact();
                                });
                            }, 0);
                        }
                    }
                    if (btnRepondre) btnRepondre.style.display = 'none';
                    if (btnArchiver) btnArchiver.style.display = estArchive ? 'none' : 'inline-flex';
                }
                // CAS 2 : Archivé SANS réponse - message d'avertissement + bouton désarchiver
                else if (estArchive && !aReponse) {
                    if (reponseContainer) {
                        reponseContainer.innerHTML = `
                            <div style="padding: 15px; background: rgba(239, 68, 68, 0.15); border-radius: 8px; border-left: 4px solid #ef4444;">
                                <p style="margin: 0; color: #f87171; font-weight: 600;">
                                    <i class="fas fa-exclamation-triangle"></i> Vous n'avez pas répondu à ce message
                                </p>
                            </div>
                            <button id="btn-desarchiver-modal" style="margin-top: 15px; width: 100%; padding: 12px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)';" onmouseout="this.style.transform=''; this.style.boxShadow='';"><i class="fas fa-inbox"></i> Retirer des archives</button>
                        `;
                        // Ajouter l'événement pour désarchiver
                        setTimeout(() => {
                            document.getElementById('btn-desarchiver-modal')?.addEventListener('click', () => {
                                this.desarchiverContact(contact.id, false);
                                this.fermerModaleContact();
                            });
                        }, 0);
                    }
                    if (btnRepondre) btnRepondre.style.display = 'none';
                    if (btnArchiver) btnArchiver.style.display = 'none';
                }
                // CAS 3 : Non archivé et pas de réponse - afficher le champ de réponse
                else {
                    if (reponseContainer) {
                        reponseContainer.innerHTML = `
                            <label for="contact-reponse"><i class="fas fa-reply"></i> Votre réponse :</label>
                            <textarea id="contact-reponse" class="form-textarea" rows="4" placeholder="Tapez votre réponse ici..."></textarea>
                        `;
                    }
                    if (btnRepondre) btnRepondre.style.display = 'inline-flex';
                    if (btnArchiver) btnArchiver.style.display = 'inline-flex';
                }
                
                document.getElementById('modal-contact').style.display = 'flex';
                
                // Rafraîchir les compteurs et la liste pour mettre à jour le statut 'lu'
                this.chargerCompteurs();
                this.chargerContacts();
            } else {
                this.afficherNotification(result.error, 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherNotification('Erreur lors du chargement du contact', 'error');
        }
    }
    
    /**
     * Tronquer un texte et ajouter ... si trop long
     */
    tronquerTexte(texte, longueurMax) {
        if (!texte) return '';
        if (texte.length <= longueurMax) return texte;
        return texte.substring(0, longueurMax) + '...';
    }
    
    /**
     * Échapper le HTML pour éviter les injections XSS
     */
    echapperHTML(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * Fermer la modale contact
     */
    fermerModaleContact() {
        document.getElementById('modal-contact').style.display = 'none';
        this.currentContactId = null;
        this.currentContactTraite = false;
        // Rafraîchir la liste après fermeture
        this.chargerContacts();
    }

    /**
     * Marquer un contact comme traité
     */
    async marquerContactTraite(id, status) {
        const contactId = id || this.currentContactId;
        if (!contactId) return;

        const reponseEl = document.getElementById('contact-reponse');
        const reponse = reponseEl ? reponseEl.value : null;

        try {
            const response = await fetch(`${this.apiUrl}?action=contact&id=${contactId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ status, reponse })
            });

            const result = await response.json();

            if (result.success) {
                this.afficherNotification(result.message, result.email_envoye ? 'success' : 'warning');
                this.fermerModaleContact();
                this.chargerContacts();
                this.chargerCompteurs();
            } else {
                this.afficherNotification(result.error, 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherNotification('Erreur lors de la mise à jour', 'error');
        }
    }
    
    /**
     * Envoyer une réponse au contact
     */
    async envoyerReponseContact() {
        const reponse = document.getElementById('contact-reponse')?.value?.trim();
        if (!reponse) {
            this.afficherNotification('Veuillez écrire une réponse avant d\'envoyer', 'warning');
            return;
        }
        await this.marquerContactTraite(null, 'traite');
    }
    
    /**
     * Archiver un contact directement depuis la modale
     */
    async archiverContactDirect() {
        await this.marquerContactTraite(null, 'archive');
    }

    /**
     * Archiver un contact directement
     */
    async archiverContact(id) {
        try {
            const response = await fetch(`${this.apiUrl}?action=contact&id=${id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ status: 'archive' })
            });

            const result = await response.json();

            if (result.success) {
                this.afficherNotification('Contact archivé', 'success');
                this.chargerContacts();
                this.chargerCompteurs();
            } else {
                this.afficherNotification(result.error, 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }
    
    /**
     * Désarchiver un contact (retirer des archives)
     */
    async desarchiverContact(id, aReponse = false) {
        try {
            // Si le contact a une réponse, on le remet en 'traite', sinon en 'lu'
            const nouveauStatut = aReponse ? 'traite' : 'lu';
            const response = await fetch(`${this.apiUrl}?action=contact&id=${id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ status: nouveauStatut })
            });

            const result = await response.json();

            if (result.success) {
                this.afficherNotification('Contact retiré des archives', 'success');
                this.chargerContacts();
                this.chargerCompteurs();
            } else {
                this.afficherNotification(result.error, 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MODÉRATION DES ANNONCES
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Charger les annonces en attente de modération
     */
    async chargerModeration() {
        const container = document.getElementById('moderation-list');
        container.innerHTML = '<div class="admin-table__loading"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';

        try {
            const params = new URLSearchParams({
                action: 'moderation',
                page: this.currentModerationPage,
                limite: 20
            });

            const response = await fetch(`${this.apiUrl}?${params}`);
            const result = await response.json();

            if (result.success) {
                this.afficherModeration(result.data);
            } else {
                container.innerHTML = `<div class="admin-table__error">${echapperHTML(result.error)}</div>`;
            }
        } catch (error) {
            console.error('Erreur:', error);
            container.innerHTML = '<div class="admin-table__error">Erreur de chargement</div>';
        }
    }

    /**
     * Afficher les annonces en attente
     */
    afficherModeration(data) {
        const container = document.getElementById('moderation-list');
        
        if (!data.annonces || data.annonces.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem; width: 100%;">
                    <i class="fas fa-check-circle fa-3x" style="color: var(--couleur-succes);"></i>
                    <h3>Aucune annonce en attente</h3>
                    <p>Toutes les annonces ont été modérées.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = data.annonces.map(annonce => {
            const image = annonce.toutes_images?.[0] || annonce.image_path || '/assets/images/default-car.jpg';
            const km = annonce.km || 0;
            return `
            <div class="moderation-card">
                <div class="moderation-card__image">
                    <img src="${echapperHTML(image)}" alt="${echapperHTML(annonce.marque)} ${echapperHTML(annonce.modele)}" onerror="this.src='/assets/images/default-car.jpg'">
                </div>
                <div class="moderation-card__content">
                    <h4>${echapperHTML(annonce.marque)} ${echapperHTML(annonce.modele)}</h4>
                    <p class="price">${formaterMonnaie(annonce.prix)}</p>
                    <p class="details">${annonce.annee} • ${km.toLocaleString('fr-FR')} km • ${echapperHTML(annonce.carburant || 'N/A')}</p>
                    <p class="seller"><i class="fas fa-user"></i> ${echapperHTML(annonce.first_name)} ${echapperHTML(annonce.last_name)}</p>
                    <p class="date"><i class="fas fa-clock"></i> ${this.formaterDate(annonce.created_at)}</p>
                </div>
                <div class="moderation-card__actions">
                    <button class="bouton bouton--primaire bouton--small" onclick="window.admin.voirAnnonce(${annonce.id})">
                        <i class="fas fa-eye"></i> Examiner
                    </button>
                    <div class="quick-actions">
                        <button class="btn-icon btn-icon--success" onclick="window.admin.modererRapide(${annonce.id}, 'public')" title="Approuver">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-icon btn-icon--danger" onclick="window.admin.modererRapide(${annonce.id}, 'refuse')" title="Refuser">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        }).join('');

        this.afficherPagination('moderation', data.page, data.pages_total);
    }

    /**
     * Voir une annonce en détail pour modération
     */
    async voirAnnonce(id) {
        try {
            // Récupérer les données depuis la liste déjà chargée
            const params = new URLSearchParams({ action: 'moderation', page: 1, limite: 100 });
            const response = await fetch(`${this.apiUrl}?${params}`);
            const result = await response.json();

            if (result.success) {
                const annonce = result.data.annonces.find(a => a.id === id);
                if (!annonce) {
                    this.afficherNotification('Annonce introuvable', 'error');
                    return;
                }

                this.currentModerationId = id;

                // Images
                const imagesContainer = document.getElementById('moderation-images');
                const images = annonce.toutes_images || [annonce.image_path || '/assets/images/default-car.jpg'];
                imagesContainer.innerHTML = images.map(img => 
                    `<img src="${echapperHTML(img)}" alt="Photo véhicule" onerror="this.src='/assets/images/default-car.jpg'">`
                ).join('');

                // Infos véhicule
                document.getElementById('moderation-vehicule-titre').textContent = `${annonce.marque} ${annonce.modele}`;
                document.getElementById('moderation-prix').textContent = formaterMonnaie(annonce.prix);
                document.getElementById('moderation-annee').textContent = annonce.annee;
                document.getElementById('moderation-km').textContent = (annonce.km?.toLocaleString('fr-FR') || 'N/A') + ' km';
                document.getElementById('moderation-carburant').textContent = annonce.carburant || 'N/A';
                document.getElementById('moderation-transmission').textContent = annonce.boite || 'N/A';
                document.getElementById('moderation-localisation').textContent = `${annonce.ville || ''} (${annonce.code_postal || ''})`;
                document.getElementById('moderation-description').textContent = annonce.description || 'Aucune description';
                
                // Infos supplémentaires
                document.getElementById('moderation-etat').textContent = this.traduireEtat(annonce.etat);
                document.getElementById('moderation-couleur').textContent = annonce.couleur || 'N/A';
                document.getElementById('moderation-puissance').textContent = annonce.puissance_cv ? `${annonce.puissance_cv} CV` : 'N/A';
                document.getElementById('moderation-portes').textContent = annonce.nb_portes || 'N/A';
                document.getElementById('moderation-places').textContent = annonce.nb_places || 'N/A';
                document.getElementById('moderation-critair').textContent = annonce.crit_air ? `Crit'Air ${annonce.crit_air}` : 'N/A';
                document.getElementById('moderation-ct').textContent = this.traduireControleTechnique(annonce.controle_technique);
                
                // Nouvelles infos
                document.getElementById('moderation-provenance').textContent = annonce.provenance || 'N/A';
                document.getElementById('moderation-norme-euro').textContent = annonce.norme_euro || 'N/A';
                document.getElementById('moderation-consommation').textContent = annonce.consommation ? `${annonce.consommation} L/100km` : 'N/A';
                document.getElementById('moderation-emission').textContent = annonce.emission_co2 ? `${annonce.emission_co2} g/km` : 'N/A';
                document.getElementById('moderation-type-vehicule').textContent = this.traduireTypeVehicule(annonce.type_vehicule);
                document.getElementById('moderation-score-ia').textContent = annonce.score_ia ? `${annonce.score_ia}/100` : 'Non calculé';

                // Infos vendeur
                document.getElementById('moderation-vendeur-nom').textContent = `${annonce.first_name} ${annonce.last_name}`;
                document.getElementById('moderation-vendeur-email').textContent = annonce.email || 'N/A';
                document.getElementById('moderation-vendeur-tel').textContent = annonce.phone || 'N/A';

                // Réinitialiser
                document.getElementById('moderation-raison-container').style.display = 'none';
                document.getElementById('moderation-raison').value = '';

                document.getElementById('modal-moderation').style.display = 'flex';
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherNotification('Erreur lors du chargement', 'error');
        }
    }

    /**
     * Fermer la modale modération
     */
    fermerModaleModeration() {
        document.getElementById('modal-moderation').style.display = 'none';
        this.currentModerationId = null;
    }

    /**
     * Afficher le champ raison de refus
     */
    afficherRaisonRefus() {
        const container = document.getElementById('moderation-raison-container');
        if (container.style.display === 'none') {
            container.style.display = 'block';
            document.getElementById('moderation-raison').focus();
        } else {
            // Confirmer le refus
            this.refuserAnnonce();
        }
    }

    /**
     * Approuver une annonce
     */
    async approuverAnnonce() {
        if (!this.currentModerationId) return;
        const success = await this.modererAnnonce(this.currentModerationId, 'public');
        this.fermerModaleModeration();
    }

    /**
     * Refuser une annonce
     */
    async refuserAnnonce() {
        if (!this.currentModerationId) return;
        const raison = document.getElementById('moderation-raison').value;
        if (!raison || !raison.trim()) {
            this.afficherNotification('Veuillez indiquer une raison de refus', 'warning');
            return;
        }
        await this.modererAnnonce(this.currentModerationId, 'refuse', raison);
        this.fermerModaleModeration();
    }

    /**
     * Modérer rapidement (sans modale)
     */
    async modererRapide(id, decision) {
        if (decision === 'public') {
            // Approuver directement
            const confirme = await this.afficherModale(
                'Approuver l\'annonce',
                'Êtes-vous sûr de vouloir approuver cette annonce ?'
            );
            
            if (confirme) {
                await this.modererAnnonce(id, 'public');
            }
        } else {
            // Refuser : demander la raison
            const raison = await this.afficherModaleRefus();
            if (raison !== null) {
                await this.modererAnnonce(id, 'refuse', raison);
            }
        }
    }

    /**
     * Afficher la modale de refus avec champ raison
     */
    async afficherModaleRefus() {
        return new Promise((resolve) => {
            // Utiliser la modale HTML existante
            const modal = document.getElementById('modal-refus');
            const textarea = document.getElementById('raison-refus-input');
            
            if (modal && textarea) {
                textarea.value = '';
                modal.style.display = 'flex';
                textarea.focus();
                
                // Stocker la promesse de résolution
                window._resolveRefus = resolve;
            } else {
                // Fallback vers prompt simple
                const raison = prompt('Veuillez indiquer la raison du refus :');
                resolve(raison !== null ? (raison || 'Aucune raison spécifiée') : null);
            }
        });
    }
    
    /**
     * Fermer la modale de refus
     */
    fermerModaleRefus() {
        const modal = document.getElementById('modal-refus');
        if (modal) {
            modal.style.display = 'none';
        }
        if (window._resolveRefus) {
            window._resolveRefus(null);
            window._resolveRefus = null;
        }
    }
    
    /**
     * Confirmer le refus depuis la modale
     */
    confirmerRefus() {
        const raison = document.getElementById('raison-refus-input')?.value?.trim() || 'Aucune raison spécifiée';
        const modal = document.getElementById('modal-refus');
        if (modal) {
            modal.style.display = 'none';
        }
        if (window._resolveRefus) {
            window._resolveRefus(raison);
            window._resolveRefus = null;
        }
    }

    /**
     * Envoyer la décision de modération
     */
    async modererAnnonce(id, decision, raison = null) {
        try {
            const response = await fetch(`${this.apiUrl}?action=moderer`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ id, decision, raison })
            });

            const result = await response.json();

            if (result.success) {
                this.afficherNotification(result.message, 'success');
                this.chargerModeration();
                this.chargerCompteurs();
            } else {
                this.afficherNotification(result.error, 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherNotification('Erreur lors de la modération', 'error');
        }
    }

    /**
     * Afficher la pagination
     */
    afficherPagination(type, page, total) {
        const container = document.getElementById(`pagination-${type}`);
        if (!container || total <= 1) {
            if (container) container.innerHTML = '';
            return;
        }

        let html = '<div class="pagination">';

        // Première page
        if (page > 1) {
            html += `<button class="pagination__btn" onclick="window.admin.changerPage('${type}', 1)" title="Première page"><i class="fas fa-angle-double-left"></i></button>`;
        }

        // Précédent
        if (page > 1) {
            html += `<button class="pagination__btn" onclick="window.admin.changerPage('${type}', ${page - 1})" title="Page précédente"><i class="fas fa-chevron-left"></i></button>`;
        }

        // Pages
        for (let i = Math.max(1, page - 2); i <= Math.min(total, page + 2); i++) {
            html += `<button class="pagination__btn ${i === page ? 'pagination__btn--active' : ''}" onclick="window.admin.changerPage('${type}', ${i})">${i}</button>`;
        }

        // Suivant
        if (page < total) {
            html += `<button class="pagination__btn" onclick="window.admin.changerPage('${type}', ${page + 1})" title="Page suivante"><i class="fas fa-chevron-right"></i></button>`;
        }

        // Dernière page
        if (page < total) {
            html += `<button class="pagination__btn" onclick="window.admin.changerPage('${type}', ${total})" title="Dernière page"><i class="fas fa-angle-double-right"></i></button>`;
        }

        html += '</div>';
        container.innerHTML = html;
    }

    /**
     * Changer de page
     */
    changerPage(type, page) {
        if (type === 'users') {
            this.currentUserPage = page;
            this.chargerUtilisateurs(document.getElementById('search-users')?.value || '');
        } else if (type === 'vehicles') {
            this.currentVehiclePage = page;
            this.chargerVehicules(document.getElementById('search-vehicles')?.value || '');
        } else if (type === 'contacts') {
            this.currentContactPage = page;
            this.chargerContacts();
        } else if (type === 'moderation') {
            this.currentModerationPage = page;
            this.chargerModeration();
        } else if (type === 'activite') {
            this.currentActivityPage = page;
            this.chargerActivite();
        }
    }

    /**
     * Supprimer un utilisateur
     */
    async supprimerUtilisateur(id, nom) {
        const confirme = await this.afficherModale(
            'Supprimer l\'utilisateur',
            `Êtes-vous sûr de vouloir supprimer <strong>${nom}</strong> ? Cette action est irréversible.`
        );

        if (!confirme) return;

        try {
            const response = await fetch(`${this.apiUrl}?action=utilisateur&id=${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            const result = await response.json();

            if (result.success) {
                this.afficherNotification('Utilisateur supprimé avec succès', 'success');
                await this.chargerUtilisateurs();
                await this.chargerResume();
            } else {
                this.afficherNotification(result.error, 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherNotification('Erreur lors de la suppression', 'error');
        }
    }

    /**
     * Changer le statut d'un véhicule
     */
    async changerStatutVehicule(id, nouveauStatut) {
        try {
            const response = await fetch(`${this.apiUrl}?action=vehicule&id=${id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ statut: nouveauStatut })
            });

            const result = await response.json();

            if (result.success) {
                this.afficherNotification('Statut modifié avec succès', 'success');
                await this.chargerVehicules();
            } else {
                this.afficherNotification(result.error, 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherNotification('Erreur lors de la modification', 'error');
        }
    }

    /**
     * Supprimer un véhicule
     */
    async supprimerVehicule(id, nom) {
        const confirme = await this.afficherModale(
            'Supprimer l\'annonce',
            `Êtes-vous sûr de vouloir supprimer l'annonce <strong>${nom}</strong> ? Cette action est irréversible.`
        );

        if (!confirme) return;

        try {
            const response = await fetch(`${this.apiUrl}?action=vehicule&id=${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            const result = await response.json();

            if (result.success) {
                this.afficherNotification('Véhicule supprimé avec succès', 'success');
                await this.chargerVehicules();
                await this.chargerResume();
            } else {
                this.afficherNotification(result.error, 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherNotification('Erreur lors de la suppression', 'error');
        }
    }

    /**
     * Afficher une modale de confirmation
     */
    afficherModale(titre, message) {
        return new Promise((resolve) => {
            const modal = document.getElementById('modal-confirm');
            document.getElementById('modal-title').textContent = titre;
            document.getElementById('modal-message').innerHTML = message;
            modal.style.display = 'flex';

            const confirmer = () => {
                modal.style.display = 'none';
                resolve(true);
            };

            const annuler = () => {
                modal.style.display = 'none';
                resolve(false);
            };

            document.getElementById('modal-confirm-btn').onclick = confirmer;
            document.getElementById('modal-cancel').onclick = annuler;
            document.getElementById('modal-close').onclick = annuler;
        });
    }

    /**
     * Fermer la modale
     */
    fermerModale() {
        document.getElementById('modal-confirm').style.display = 'none';
    }

    /**
     * Afficher une notification
     */
    afficherNotification(message, type = 'info') {
        // Créer une notification toast
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        
        let icon = 'fa-info-circle';
        if (type === 'success') icon = 'fa-check-circle';
        else if (type === 'error') icon = 'fa-exclamation-circle';
        else if (type === 'warning') icon = 'fa-exclamation-triangle';
        
        toast.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        `;
        
        // Container pour les toasts
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; display: flex; flex-direction: column; gap: 10px;';
            document.body.appendChild(container);
        }
        
        // Style du toast
        toast.style.cssText = `
            display: flex; align-items: center; gap: 10px; padding: 15px 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
            color: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(100%); transition: transform 0.3s ease;
            min-width: 250px; max-width: 400px;
        `;
        
        container.appendChild(toast);
        
        // Animation d'entrée
        requestAnimationFrame(() => {
            toast.style.transform = 'translateX(0)';
        });
        
        // Supprimer après 4 secondes
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    /**
     * Afficher une erreur
     */
    afficherErreur(message) {
        console.error('Erreur:', message);
        this.afficherNotification('Erreur: ' + message, 'error');
    }

    /**
     * Formater une date
     */
    formaterDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    }

    /**
     * Traduire l'état du véhicule
     */
    traduireEtat(etat) {
        const traductions = {
            'neuf': 'Neuf',
            'bon': 'Bon état',
            'moyen': 'État moyen',
            'mauvais': 'Mauvais état'
        };
        return traductions[etat] || etat || 'N/A';
    }

    /**
     * Traduire le contrôle technique
     */
    traduireControleTechnique(ct) {
        const traductions = {
            'oui': 'OK',
            'non': 'À passer',
            'non_requis': 'Non requis'
        };
        return traductions[ct] || ct || 'N/A';
    }

    /**
     * Traduire le type de véhicule
     */
    traduireTypeVehicule(type) {
        const traductions = {
            'voiture': 'Voiture',
            'moto': 'Moto',
            'camion': 'Camion'
        };
        return traductions[type] || type || 'Voiture';
    }
}

// Exposer l'instance globalement pour les onclick
window.adminInstance = null;
document.addEventListener('DOMContentLoaded', () => {
    window.admin = new VueAdmin();
    window.adminInstance = window.admin;
});
