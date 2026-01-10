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
        this.userFilter = 'all';
        this.vehicleFilter = 'all';
        this.searchUserTimeout = null;
        this.searchVehicleTimeout = null;
    }

    /**
     * Initialisation
     */
    async initialiser() {
        this.attacherEvenements();
        await this.chargerResume();
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

        // Filtre activité
        document.getElementById('btn-filtrer-activite')?.addEventListener('click', () => {
            this.chargerActiviteFiltree();
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
                this.afficherActivite();
                
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
            
            return `
            <tr>
                <td>#${user.id}</td>
                <td>
                    <div class="user-info">
                        ${user.avatar_path ? 
                            `<img src="${echapperHTML(user.avatar_path)}" alt="" class="user-avatar">` : 
                            '<span class="user-avatar user-avatar--default">👤</span>'
                        }
                        <span>${fullName}</span>
                    </div>
                </td>
                <td>${echapperHTML(user.email)}</td>
                <td>${echapperHTML(user.phone)}</td>
                <td>
                    ${user.email_verified_at ? 
                        '<span class="badge badge--success">Vérifié</span>' : 
                        '<span class="badge badge--warning">Non vérifié</span>'
                    }
                    ${user.role === 'admin' ? '<span class="badge badge--primary">Admin</span>' : ''}
                </td>
                <td>${user.nb_annonces || 0}</td>
                <td>${this.formaterDate(user.created_at)}</td>
                <td class="admin-table__actions">
                    <button class="btn-icon btn-icon--danger" 
                            onclick="window.admin.supprimerUtilisateur(${user.id}, '${fullName.replace(/'/g, "\\'")}');" 
                            title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        }).join('');

        this.afficherPagination('users', data.page, data.pages_total);
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
            
            return `
            <tr>
                <td>#${vehicle.id}</td>
                <td>
                    <strong>${fullName}</strong><br>
                    <small>${vehicle.annee}</small>
                </td>
                <td>${echapperHTML(vehicle.first_name)} ${echapperHTML(vehicle.last_name)}</td>
                <td><strong>${formaterMonnaie(vehicle.prix)}</strong></td>
                <td>
                    <span class="badge badge--${vehicle.status === 'public' ? 'success' : 'secondary'}">
                        ${vehicle.status === 'public' ? 'Public' : 'Privé'}
                    </span>
                </td>
                <td>${vehicle.views_count || 0}</td>
                <td>${vehicle.nb_favoris || 0}</td>
                <td>${this.formaterDate(vehicle.created_at)}</td>
                <td class="admin-table__actions">
                    <button class="btn-icon btn-icon--${vehicle.status === 'public' ? 'warning' : 'success'}" 
                            onclick="window.admin.changerStatutVehicule(${vehicle.id}, '${vehicle.status === 'public' ? 'prive' : 'public'}');" 
                            title="${vehicle.status === 'public' ? 'Masquer' : 'Publier'}">
                        <i class="fas fa-${vehicle.status === 'public' ? 'eye-slash' : 'eye'}"></i>
                    </button>
                    <button class="btn-icon btn-icon--danger" 
                            onclick="window.admin.supprimerVehicule(${vehicle.id}, '${fullName.replace(/'/g, "\\'")}');" 
                            title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        }).join('');

        this.afficherPagination('vehicles', data.page, data.pages_total);
    }

    /**
     * Afficher l'activité récente
     */
    afficherActivite() {
        if (!this.data || !this.data.activite_recente) return;

        const timeline = document.getElementById('activity-timeline');
        const activites = this.data.activite_recente;

        if (activites.length === 0) {
            timeline.innerHTML = '<div class="activity-item activity-item--empty">Aucune activité récente</div>';
            return;
        }

        timeline.innerHTML = activites.map(act => {
            let icon, text, colorClass;

            switch (act.type) {
                case 'inscription':
                    icon = 'user-plus';
                    text = `<strong>${echapperHTML(act.first_name)} ${echapperHTML(act.last_name)}</strong> s'est inscrit(e)`;
                    colorClass = 'primary';
                    break;
                case 'annonce':
                    icon = 'car';
                    text = `<strong>${echapperHTML(act.first_name)} ${echapperHTML(act.last_name)}</strong> a ajouté ${echapperHTML(act.marque)} ${echapperHTML(act.modele)}`;
                    colorClass = 'success';
                    break;
                case 'conversation':
                    icon = 'comments';
                    text = `Conversation entre <strong>${echapperHTML(act.acheteur_prenom)}</strong> et <strong>${echapperHTML(act.vendeur_prenom)}</strong>`;
                    colorClass = 'info';
                    break;
                default:
                    icon = 'info-circle';
                    text = 'Activité inconnue';
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
    }

    /**
     * Charger l'activité avec filtres
     */
    async chargerActiviteFiltree() {
        const dateDebut = document.getElementById('date-debut')?.value || '';
        const dateFin = document.getElementById('date-fin')?.value || '';
        const limite = document.getElementById('limite-activite')?.value || 20;

        try {
            // Construire l'URL avec paramètres
            let url = `${this.apiUrl}?action=resume&limite=${limite}`;
            if (dateDebut) url += `&date_debut=${dateDebut}`;
            if (dateFin) url += `&date_fin=${dateFin}`;
            
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success && result.data) {
                this.afficherActivite(result.data.activite_recente);
            } else {
                throw new Error(result.error || 'Erreur lors du chargement');
            }
        } catch (error) {
            console.error('Erreur lors du filtrage de l\'activité:', error);
            alert('Erreur lors du chargement de l\'activité filtrée');
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
     * Afficher la pagination
     */
    afficherPagination(type, page, total) {
        const container = document.getElementById(`pagination-${type}`);
        if (!container || total <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<div class="pagination">';

        // Précédent
        if (page > 1) {
            html += `<button class="pagination__btn" onclick="window.admin.changerPage('${type}', ${page - 1})"><i class="fas fa-chevron-left"></i></button>`;
        }

        // Pages
        for (let i = Math.max(1, page - 2); i <= Math.min(total, page + 2); i++) {
            html += `<button class="pagination__btn ${i === page ? 'pagination__btn--active' : ''}" onclick="window.admin.changerPage('${type}', ${i})">${i}</button>`;
        }

        // Suivant
        if (page < total) {
            html += `<button class="pagination__btn" onclick="window.admin.changerPage('${type}', ${page + 1})"><i class="fas fa-chevron-right"></i></button>`;
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
                this.chargerUtilisateurs();
                this.chargerResume();
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
                this.chargerVehicules();
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
                this.chargerVehicules();
                this.chargerResume();
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
        // Utiliser le système de notification existant
        console.log(`[${type}]`, message);
        // TODO: Intégrer avec le système de notification de l'application
    }

    /**
     * Afficher une erreur
     */
    afficherErreur(message) {
        console.error('Erreur:', message);
        alert('Erreur: ' + message);
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
}

// Exposer l'instance globalement pour les onclick
window.adminInstance = null;
document.addEventListener('DOMContentLoaded', () => {
    window.admin = new VueAdmin();
    window.adminInstance = window.admin;
});
