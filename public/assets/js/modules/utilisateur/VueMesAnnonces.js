/**
 * VueMesAnnonces.js
 * Gestion de l'affichage et des interactions de la page "Mes Annonces"
 */

import GestionnaireSuppression from '../commun/GestionnaireSuppression.js';

class VueMesAnnonces {
    constructor() {
        this.listeAnnonces = document.getElementById('liste-annonces');
        this.annoncesVide = document.getElementById('annonces-vide');
        this.annoncesLoading = document.getElementById('annonces-loading');
        this.annoncesErreur = document.getElementById('annonces-erreur');
        this.annoncesErreurMessage = document.getElementById('annonces-erreur-message');
        this.annoncesRetry = document.getElementById('annonces-retry');
        this.countTotal = document.getElementById('annonces-count');
        this.countPublic = document.getElementById('annonces-public-count');
        this.countPrive = document.getElementById('annonces-prive-count');
        this.csrfToken = document.getElementById('csrf-token')?.value || '';
        
        this.annonces = [];
        
        this.prefixeUrl = this.detecterPrefixeUrl();
        
        // Gestionnaire de suppression centralisé
        this.gestionnaireSuppression = new GestionnaireSuppression({
            onSuccess: (vehiculeId) => this.onSuppressionReussie(vehiculeId),
            onError: (error) => this.afficherNotification(error, 'error')
        });
        
        this.init();
    }
    
    detecterPrefixeUrl() {
        const scripts = document.querySelectorAll('script[src*="VueMesAnnonces"]');
        if (scripts.length > 0) {
            const src = scripts[0].getAttribute('src');
            console.log('[DEBUG] Script src:', src);
            const index = src.indexOf('assets/js');
            if (index > 0) {
                const prefix = src.substring(0, index);
                console.log('[DEBUG] Prefix URL détecté:', prefix);
                return prefix;
            }
        }
        console.log('[DEBUG] Prefix URL par défaut: /');
        return '/';
    }
    
    init() {
        console.log('[DEBUG] Initialisation VueMesAnnonces');
        console.log('[DEBUG] Prefix URL utilisé:', this.prefixeUrl);
        this.chargerAnnonces();
        this.attacherEvenements();
    }
    
    attacherEvenements() {
        // Réessayer
        this.annoncesRetry?.addEventListener('click', () => this.chargerAnnonces());
    }
    
    async chargerAnnonces() {
        this.afficherChargement();
        
        try {
            const url = `${this.prefixeUrl}api/mes-annonces`;
            console.log('[DEBUG] Chargement annonces depuis:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                credentials: 'same-origin'
            });
            
            console.log('[DEBUG] Réponse status:', response.status);
            const data = await response.json();
            console.log('[DEBUG] Données reçues:', data);
            
            if (!response.ok) {
                throw new Error(data.message || 'Erreur lors du chargement');
            }
            
            this.annonces = data.annonces || [];
            console.log('[DEBUG] Nombre d\'annonces:', this.annonces.length);
            this.mettreAJourCompteurs();
            this.afficherAnnonces();
            
        } catch (error) {
            console.error('Erreur chargement annonces:', error);
            this.afficherErreur(error.message);
        }
    }
    
    mettreAJourCompteurs() {
        const total = this.annonces.length;
        const publiques = this.annonces.filter(a => a.status === 'public').length;
        const privees = this.annonces.filter(a => a.status === 'prive').length;
        const enAttente = this.annonces.filter(a => a.status === 'en_attente').length;
        const refusees = this.annonces.filter(a => a.status === 'refuse').length;
        
        if (this.countTotal) this.countTotal.textContent = total;
        if (this.countPublic) this.countPublic.textContent = publiques;
        if (this.countPrive) this.countPrive.textContent = privees;
        if (document.getElementById('annonces-attente-count')) {
            document.getElementById('annonces-attente-count').textContent = enAttente;
        }
        if (document.getElementById('annonces-refuse-count')) {
            document.getElementById('annonces-refuse-count').textContent = refusees;
        }
    }
    
    afficherChargement() {
        if (this.listeAnnonces) this.listeAnnonces.innerHTML = '';
        if (this.annoncesLoading) this.annoncesLoading.hidden = false;
        if (this.annoncesVide) this.annoncesVide.hidden = true;
        if (this.annoncesErreur) this.annoncesErreur.hidden = true;
    }
    
    afficherErreur(message) {
        if (this.annoncesLoading) this.annoncesLoading.hidden = true;
        if (this.annoncesVide) this.annoncesVide.hidden = true;
        if (this.annoncesErreur) this.annoncesErreur.hidden = false;
        if (this.annoncesErreurMessage) this.annoncesErreurMessage.textContent = message;
    }
    
    afficherAnnonces() {
        if (this.annoncesLoading) this.annoncesLoading.hidden = true;
        
        if (this.annonces.length === 0) {
            if (this.annoncesVide) this.annoncesVide.hidden = false;
            return;
        }
        
        if (this.annoncesVide) this.annoncesVide.hidden = true;
        if (this.listeAnnonces) {
            this.listeAnnonces.innerHTML = this.annonces.map(annonce => this.creerCarteAnnonce(annonce)).join('');
            this.attacherEvenementsCartes();
        }
    }
    
    creerCarteAnnonce(annonce) {
        const imageSrc = annonce.image_path 
            ? `${this.prefixeUrl}${annonce.image_path}`
            : `${this.prefixeUrl}assets/images/placeholder-car.jpg`;
        
        // Déterminer le statut, label et icône selon le status
        let statusClass, statusLabel, statusIcon;
        switch (annonce.status) {
            case 'public':
                statusClass = 'annonce-status--public';
                statusLabel = 'Publique';
                statusIcon = 'fa-eye';
                break;
            case 'prive':
                statusClass = 'annonce-status--prive';
                statusLabel = 'Privée';
                statusIcon = 'fa-eye-slash';
                break;
            case 'en_attente':
                statusClass = 'annonce-status--attente';
                statusLabel = 'En vérification';
                statusIcon = 'fa-hourglass-half';
                break;
            case 'refuse':
                statusClass = 'annonce-status--refuse';
                statusLabel = 'Refusée';
                statusIcon = 'fa-times-circle';
                break;
            default:
                statusClass = 'annonce-status--prive';
                statusLabel = annonce.status;
                statusIcon = 'fa-question-circle';
        }
        
        const prixFormate = new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'EUR',
            maximumFractionDigits: 0 
        }).format(annonce.prix);
        
        const kmFormate = new Intl.NumberFormat('fr-FR').format(annonce.km || 0);
        
        // Utiliser les stats live ou les compteurs enregistrés
        const vues = annonce.views_count || 0;
        const contacts = annonce.contacts_live || annonce.contacts_count || 0;
        const favoris = annonce.favorites_live || annonce.favorites_count || 0;
        
        const dateCreation = new Date(annonce.created_at).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
        
        return `
            <article class="annonce-carte" data-id="${annonce.id}">
                <div class="annonce-carte__image-wrapper">
                    <img src="${imageSrc}" alt="${annonce.marque} ${annonce.modele}" class="annonce-carte__image" loading="lazy">
                    <span class="annonce-carte__status ${statusClass}">
                        <i class="fas ${statusIcon}"></i> ${statusLabel}
                    </span>
                    <span class="annonce-carte__type">${annonce.type_vehicule}</span>
                </div>
                
                <div class="annonce-carte__content">
                    <h3 class="annonce-carte__title">${annonce.marque} ${annonce.modele}</h3>
                    <p class="annonce-carte__subtitle">${annonce.annee} • ${kmFormate} km</p>
                    <p class="annonce-carte__price">${prixFormate}</p>
                    <p class="annonce-carte__location">
                        <i class="fas fa-map-marker-alt"></i> ${annonce.ville} (${annonce.code_postal})
                    </p>
                    <p class="annonce-carte__date">
                        <i class="fas fa-calendar-alt"></i> Publiée le ${dateCreation}
                    </p>
                    
                    <!-- Statistiques -->
                    <div class="annonce-carte__stats">
                        <div class="annonce-stat" title="Consultations">
                            <i class="fas fa-eye"></i>
                            <span>${vues}</span>
                        </div>
                        <div class="annonce-stat" title="Contacts">
                            <i class="fas fa-envelope"></i>
                            <span>${contacts}</span>
                        </div>
                        <div class="annonce-stat" title="Favoris">
                            <i class="fas fa-heart"></i>
                            <span>${favoris}</span>
                        </div>
                    </div>
                </div>
                
                ${annonce.status === 'refuse' ? `
                <div class="annonce-carte__refus-raison" style="margin: 0 15px 15px; padding: 12px 15px; background: rgba(239, 68, 68, 0.2); border-left: 4px solid #ef4444; border-radius: 4px;">
                    <div style="display: flex; align-items: center; gap: 8px; color: #f87171; font-weight: 600; margin-bottom: 6px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Raison du refus :</span>
                    </div>
                    ${annonce.raison_refus && annonce.raison_refus.length > 80 ? `
                        <p style="margin: 0; color: #fecaca; line-height: 1.4;">
                            ${this.echapperHTML(annonce.raison_refus.substring(0, 80))}...
                            <button class="btn-voir-raison" data-raison="${this.echapperHTML(annonce.raison_refus)}" 
                                    style="background: none; border: none; color: #60a5fa; cursor: pointer; font-weight: 600; margin-left: 5px; text-decoration: underline;">
                                En savoir plus
                            </button>
                        </p>
                    ` : `
                        <p style="margin: 0; color: #fecaca; line-height: 1.4;">${annonce.raison_refus ? this.echapperHTML(annonce.raison_refus) : 'Aucune raison spécifiée'}</p>
                    `}
                </div>
                ` : ''}
                
                ${annonce.status === 'en_attente' ? `
                <div class="annonce-carte__attente-zone" style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 20px; display: flex; align-items: center; justify-content: center; border-radius: 0 0 16px 16px; margin-top: auto;">
                    <span style="color: white; font-weight: 600; font-size: 0.95rem; text-align: center;">
                        <i class="fas fa-hourglass-half" style="margin-right: 8px;"></i> En cours de vérification par notre équipe
                    </span>
                </div>
                ` : `
                <div class="annonce-carte__actions">
                    ${this.genererBoutonsAction(annonce)}
                </div>
                `}
            </article>
        `;
    }
    
    genererBoutonsAction(annonce) {
        let html = '';
        
        // Bouton Consulter - seulement si public ou privé
        if (annonce.status === 'public' || annonce.status === 'prive') {
            html += `<a href="${this.prefixeUrl}vehicule?id=${annonce.id}" class="annonces-btn annonces-btn--outline annonces-btn--small" title="Consulter">
                <i class="fas fa-eye"></i> Consulter
            </a>`;
        }
        
        // Bouton Modifier - sauf si en_attente
        if (annonce.status !== 'en_attente') {
            html += `<a href="${this.prefixeUrl}modification_vehicule?id=${annonce.id}" class="annonces-btn annonces-btn--outline annonces-btn--small" title="Modifier">
                <i class="fas fa-edit"></i> Modifier
            </a>`;
        }
        
        // Bouton changer statut - seulement si public ou privé
        if (annonce.status === 'public' || annonce.status === 'prive') {
            html += `<button class="annonces-btn annonces-btn--outline annonces-btn--small btn-toggle-status" 
                    data-id="${annonce.id}" 
                    data-status="${annonce.status}"
                    title="${annonce.status === 'public' ? 'Rendre privée' : 'Rendre publique'}">
                <i class="fas ${annonce.status === 'public' ? 'fa-eye-slash' : 'fa-eye'}"></i>
                ${annonce.status === 'public' ? 'Masquer' : 'Publier'}
            </button>`;
        }
        
        // Note: Pour re-soumettre une annonce refusée, l'utilisateur doit la modifier
        
        // Bouton Supprimer - toujours sauf en_attente
        if (annonce.status !== 'en_attente') {
            html += `<button class="annonces-btn annonces-btn--danger annonces-btn--small btn-supprimer" 
                    data-id="${annonce.id}" 
                    data-nom="${annonce.marque} ${annonce.modele}"
                    title="Supprimer">
                <i class="fas fa-trash"></i> Supprimer
            </button>`;
        }
        
        return html;
    }
    
    echapperHTML(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    attacherEvenementsCartes() {
        // Toggle status
        document.querySelectorAll('.btn-toggle-status').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.basculerStatut(e);
            });
        });
        
        // Supprimer
        document.querySelectorAll('.btn-supprimer').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.ouvrirModalSuppression(e);
            });
        });
        
        // Voir raison du refus complète
        document.querySelectorAll('.btn-voir-raison').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const raison = btn.dataset.raison;
                this.afficherRaisonRefus(raison);
            });
        });
        
        // Re-soumettre (pour les annonces refusées)
        document.querySelectorAll('.btn-resoumettre').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.resoumettrePourVerification(e);
            });
        });
    }
    
    async resoumettrePourVerification(event) {
        const btn = event.currentTarget;
        const vehiculeId = btn.dataset.id;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        try {
            const response = await fetch(`${this.prefixeUrl}api/mes-annonces/resoumettre`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({ vehicule_id: vehiculeId })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || data.erreur || 'Erreur lors de la soumission');
            }
            
            // Mettre à jour localement
            const annonce = this.annonces.find(a => a.id == vehiculeId);
            if (annonce) {
                annonce.status = 'en_attente';
                annonce.raison_refus = null;
            }
            
            this.mettreAJourCompteurs();
            this.afficherAnnonces();
            
            this.afficherNotification('Annonce soumise pour vérification', 'success');
            
        } catch (error) {
            console.error('Erreur re-soumission:', error);
            this.afficherNotification(error.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Re-soumettre';
        }
    }
    
    async basculerStatut(event) {
        const btn = event.currentTarget;
        const vehiculeId = btn.dataset.id;
        const statutActuel = btn.dataset.status;
        const nouveauStatut = statutActuel === 'public' ? 'prive' : 'public';
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        try {
            const response = await fetch(`${this.prefixeUrl}api/mes-annonces/statut`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    vehicule_id: vehiculeId,
                    status: nouveauStatut
                })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Erreur lors du changement de statut');
            }
            
            // Mettre à jour localement
            const annonce = this.annonces.find(a => a.id == vehiculeId);
            if (annonce) {
                annonce.status = nouveauStatut;
            }
            
            this.mettreAJourCompteurs();
            this.afficherAnnonces();
            
            this.afficherNotification(`Annonce ${nouveauStatut === 'public' ? 'publiée' : 'masquée'} avec succès`, 'success');
            
        } catch (error) {
            console.error('Erreur toggle status:', error);
            this.afficherNotification(error.message, 'error');
            btn.disabled = false;
            btn.innerHTML = `<i class="fas ${statutActuel === 'public' ? 'fa-eye-slash' : 'fa-eye'}"></i> ${statutActuel === 'public' ? 'Masquer' : 'Publier'}`;
        }
    }
    
    ouvrirModalSuppression(event) {
        const btn = event.currentTarget;
        const vehicule = {
            id: btn.dataset.id,
            nom: btn.dataset.nom
        };
        
        this.gestionnaireSuppression.ouvrir(vehicule);
    }
    
    onSuppressionReussie(vehiculeId) {
        // Retirer de la liste locale
        this.annonces = this.annonces.filter(a => a.id != vehiculeId);
        
        // Supprimer visuellement la carte d'abord
        const carte = document.querySelector(`.annonce-carte[data-id="${vehiculeId}"]`);
        if (carte) {
            carte.remove();
        }
        
        // Mettre à jour l'affichage complet (gère le cas où liste vide)
        this.mettreAJourCompteurs();
        
        // Afficher le message vide si plus d'annonces
        if (this.annonces.length === 0) {
            if (this.annoncesVide) this.annoncesVide.hidden = false;
            if (this.listeAnnonces) this.listeAnnonces.innerHTML = '';
        }
        
        // Notification
        this.afficherNotification('Annonce supprimée avec succès', 'success');
    }
    
    afficherRaisonRefus(raison) {
        // Créer une modale pour afficher la raison complète
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px;';
        
        overlay.innerHTML = `
            <div class="modal-raison" style="background: linear-gradient(145deg, #1e293b, #0f172a); border-radius: 16px; max-width: 500px; width: 100%; padding: 24px; border: 1px solid rgba(239, 68, 68, 0.3); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; color: #f87171;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem;"></i>
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;">Raison du refus</h3>
                </div>
                <p style="color: #e2e8f0; line-height: 1.6; margin: 0 0 20px 0; white-space: pre-wrap;">${this.echapperHTML(raison)}</p>
                <button class="btn-fermer-raison" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                    Compris
                </button>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        // Fermer au clic sur le bouton ou l'overlay
        overlay.querySelector('.btn-fermer-raison').addEventListener('click', () => overlay.remove());
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.remove();
        });
    }
    
    afficherNotification(message, type = 'info') {
        // Créer une notification toast
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        toast.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        // Container pour les toasts
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        container.appendChild(toast);
        
        // Animation d'entrée
        requestAnimationFrame(() => {
            toast.classList.add('toast--visible');
        });
        
        // Supprimer après 3 secondes
        setTimeout(() => {
            toast.classList.remove('toast--visible');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialiser au chargement
document.addEventListener('DOMContentLoaded', () => {
    new VueMesAnnonces();
});
