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
        
        if (this.countTotal) this.countTotal.textContent = total;
        if (this.countPublic) this.countPublic.textContent = publiques;
        if (this.countPrive) this.countPrive.textContent = privees;
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
        
        const statusClass = annonce.status === 'public' ? 'annonce-status--public' : 'annonce-status--prive';
        const statusLabel = annonce.status === 'public' ? 'Publique' : 'Privée';
        const statusIcon = annonce.status === 'public' ? 'fa-eye' : 'fa-eye-slash';
        
        const prixFormate = new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'EUR',
            maximumFractionDigits: 0 
        }).format(annonce.prix);
        
        const kmFormate = new Intl.NumberFormat('fr-FR').format(annonce.km);
        
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
                
                <div class="annonce-carte__actions">
                    <a href="${this.prefixeUrl}vehicule?id=${annonce.id}" class="annonces-btn annonces-btn--outline annonces-btn--small" title="Consulter">
                        <i class="fas fa-eye"></i> Consulter
                    </a>
                    <a href="${this.prefixeUrl}modification_vehicule?id=${annonce.id}" class="annonces-btn annonces-btn--outline annonces-btn--small" title="Modifier">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <button class="annonces-btn annonces-btn--outline annonces-btn--small btn-toggle-status" 
                            data-id="${annonce.id}" 
                            data-status="${annonce.status}"
                            title="${annonce.status === 'public' ? 'Rendre privée' : 'Rendre publique'}">
                        <i class="fas ${annonce.status === 'public' ? 'fa-eye-slash' : 'fa-eye'}"></i>
                        ${annonce.status === 'public' ? 'Masquer' : 'Publier'}
                    </button>
                    <button class="annonces-btn annonces-btn--danger annonces-btn--small btn-supprimer" 
                            data-id="${annonce.id}" 
                            data-nom="${annonce.marque} ${annonce.modele}"
                            title="Supprimer">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </div>
            </article>
        `;
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
        
        // Mettre à jour l'affichage
        this.mettreAJourCompteurs();
        this.afficherAnnonces();
        
        // Notification
        this.afficherNotification('Annonce supprimée avec succès', 'success');
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
