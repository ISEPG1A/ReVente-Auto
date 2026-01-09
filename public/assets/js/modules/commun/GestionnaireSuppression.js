/**
 * GestionnaireSuppression
 * Classe réutilisable pour gérer la suppression de véhicules avec modal de confirmation
 * Utilisée par: VueMesAnnonces, VueModificationVehicule, VueDetails
 */

import { obtenirUrlApi } from '../../application.js';

export default class GestionnaireSuppression {
    
    /**
     * @param {Object} options - Options de configuration
     * @param {Function} options.onSuccess - Callback appelé après suppression réussie
     * @param {Function} options.onError - Callback appelé en cas d'erreur
     */
    constructor(options = {}) {
        this.onSuccess = options.onSuccess || (() => {});
        this.onError = options.onError || ((error) => console.error(error));
        
        // Éléments du DOM
        this.modal = document.getElementById('modal-suppression');
        this.modalVehicleName = document.getElementById('modal-vehicle-name');
        this.modalConfirm = document.getElementById('modal-confirm-delete');
        this.modalClose = this.modal?.querySelector('.modal__close');
        this.modalCancel = this.modal?.querySelector('.modal__cancel');
        
        // Véhicule en cours de suppression
        this.vehiculeASupprimer = null;
        
        this.initialiser();
    }
    
    /**
     * Initialise les événements du modal
     */
    initialiser() {
        if (!this.modal) {
            console.warn('Modal de suppression non trouvé dans le DOM');
            return;
        }
        
        // Fermer le modal
        if (this.modalClose) {
            this.modalClose.addEventListener('click', () => this.fermer());
        }
        
        if (this.modalCancel) {
            this.modalCancel.addEventListener('click', () => this.fermer());
        }
        
        // Confirmer la suppression
        if (this.modalConfirm) {
            this.modalConfirm.addEventListener('click', () => this.confirmer());
        }
        
        // Fermer avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.modal.hidden) {
                this.fermer();
            }
        });
    }
    
    /**
     * Ouvre le modal avec les informations du véhicule
     * @param {Object} vehicule - Données du véhicule {id, marque, modele} ou {id, nom}
     */
    ouvrir(vehicule) {
        if (!this.modal) return;
        
        this.vehiculeASupprimer = vehicule;
        
        // Afficher le nom du véhicule
        if (this.modalVehicleName) {
            const nom = vehicule.nom || `${vehicule.marque || ''} ${vehicule.modele || ''}`.trim() || 'Ce véhicule';
            this.modalVehicleName.textContent = nom;
        }
        
        // Afficher le modal
        this.modal.hidden = false;
        document.body.style.overflow = 'hidden';
        
        // Réinitialiser le bouton
        if (this.modalConfirm) {
            this.modalConfirm.disabled = false;
            this.modalConfirm.innerHTML = '<i class="fas fa-trash"></i> Supprimer définitivement';
        }
    }
    
    /**
     * Ferme le modal
     */
    fermer() {
        if (!this.modal) return;
        
        this.modal.hidden = true;
        document.body.style.overflow = '';
        this.vehiculeASupprimer = null;
    }
    
    /**
     * Confirme et exécute la suppression
     */
    async confirmer() {
        if (!this.vehiculeASupprimer) return;
        
        const vehiculeId = this.vehiculeASupprimer.id;
        
        // Désactiver le bouton pendant le traitement
        if (this.modalConfirm) {
            this.modalConfirm.disabled = true;
            this.modalConfirm.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';
        }
        
        try {
            // Récupérer le token CSRF
            const csrfToken = document.getElementById('csrf-token')?.value || '';
            
            // Appel API
            const response = await fetch(`${obtenirUrlApi('')}/api/vehicule/${vehiculeId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || data.message || 'Erreur lors de la suppression');
            }
            
            // Fermer le modal
            this.fermer();
            
            // Callback de succès
            this.onSuccess(vehiculeId, data);
            
        } catch (error) {
            console.error('Erreur suppression:', error);
            
            // Fermer le modal
            this.fermer();
            
            // Callback d'erreur
            this.onError(error.message || 'Une erreur est survenue');
            
            // Réactiver le bouton en cas d'erreur
            if (this.modalConfirm) {
                this.modalConfirm.disabled = false;
                this.modalConfirm.innerHTML = '<i class="fas fa-trash"></i> Supprimer définitivement';
            }
        }
    }
    
    /**
     * Méthode statique pour créer et utiliser directement
     * @param {Object} vehicule - Véhicule à supprimer
     * @param {Function} onSuccess - Callback de succès
     * @param {Function} onError - Callback d'erreur
     */
    static supprimer(vehicule, onSuccess, onError) {
        const gestionnaire = new GestionnaireSuppression({ onSuccess, onError });
        gestionnaire.ouvrir(vehicule);
    }
}
