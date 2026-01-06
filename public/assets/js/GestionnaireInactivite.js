/**
 * Gestionnaire d'inactivité utilisateur
 * Affiche un avertissement après 17 minutes d'inactivité
 * Déconnecte automatiquement après 3 minutes supplémentaires (20 minutes au total)
 */
class GestionnaireInactivite {
    constructor() {
        // Configuration des durées (en millisecondes)
        this.DUREE_AVANT_AVERTISSEMENT = 17 * 60 * 1000; // 17 minutes
        this.DUREE_AVERTISSEMENT = 3 * 60 * 1000;        // 3 minutes
        
        this.timerInactivite = null;
        this.timerDeconnexion = null;
        this.intervalCompteur = null;
        this.tempsRestant = 0;
        this.modalAffichee = false;
        
        this.init();
    }

    /**
     * Initialise le gestionnaire
     */
    init() {
        // Ne pas initialiser si l'utilisateur n'est pas connecté
        if (!this.utilisateurConnecte()) {
            return;
        }

        this.creerModalAvertissement();
        this.ecouterActivite();
        this.demarrerTimerInactivite();
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    utilisateurConnecte() {
        // Vérifie la présence d'éléments indiquant une connexion
        return document.querySelector('.menu-utilisateur') !== null || 
               document.querySelector('[data-utilisateur-connecte]') !== null;
    }

    /**
     * Crée la modal d'avertissement
     */
    creerModalAvertissement() {
        const modal = document.createElement('div');
        modal.id = 'modal-inactivite';
        modal.className = 'modal-inactivite';
        modal.innerHTML = `
            <div class="modal-inactivite__overlay"></div>
            <div class="modal-inactivite__contenu">
                <div class="modal-inactivite__icone">
                    <i class="fas fa-clock"></i>
                </div>
                <h2 class="modal-inactivite__titre">Session inactive</h2>
                <p class="modal-inactivite__message">
                    Vous allez être déconnecté dans
                </p>
                <div class="modal-inactivite__timer">
                    <span id="timer-minutes">03</span>:<span id="timer-secondes">00</span>
                </div>
                <p class="modal-inactivite__sous-message">
                    Cliquez n'importe où ou bougez la souris pour rester connecté
                </p>
                <button class="modal-inactivite__bouton bouton" id="btn-rester-connecte">
                    <i class="fas fa-check"></i> Rester connecté
                </button>
            </div>
        `;
        document.body.appendChild(modal);

        // Ajouter les styles
        this.ajouterStyles();

        // Écouteur pour le bouton
        document.getElementById('btn-rester-connecte').addEventListener('click', () => {
            this.annulerDeconnexion();
        });
    }

    /**
     * Ajoute les styles CSS pour la modal
     */
    ajouterStyles() {
        const styles = document.createElement('style');
        styles.textContent = `
            .modal-inactivite {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10000;
                font-family: 'Inter', sans-serif;
            }

            .modal-inactivite.active {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .modal-inactivite__overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                backdrop-filter: blur(5px);
            }

            .modal-inactivite__contenu {
                position: relative;
                background: var(--surface, #fff);
                border-radius: 20px;
                padding: 40px;
                text-align: center;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                animation: modalApparition 0.3s ease;
            }

            @keyframes modalApparition {
                from {
                    opacity: 0;
                    transform: scale(0.9) translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: scale(1) translateY(0);
                }
            }

            .modal-inactivite__icone {
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
                background: linear-gradient(135deg, var(--couleur-principale, #f59e0b), #e67e00);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2rem;
                color: #1a1a1a;
                animation: pulse 2s infinite;
            }

            @keyframes pulse {
                0%, 100% {
                    transform: scale(1);
                    box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
                }
                50% {
                    transform: scale(1.05);
                    box-shadow: 0 0 0 15px rgba(245, 158, 11, 0);
                }
            }

            .modal-inactivite__titre {
                font-size: 1.5rem;
                font-weight: 700;
                margin: 0 0 10px;
                color: var(--texte, #1a1a1a);
            }

            .modal-inactivite__message {
                color: var(--texte-attenue, #666);
                margin: 0 0 15px;
                font-size: 1rem;
            }

            .modal-inactivite__timer {
                font-size: 3.5rem;
                font-weight: 800;
                color: var(--couleur-danger, #dc2626);
                margin: 0 0 15px;
                font-variant-numeric: tabular-nums;
                letter-spacing: 2px;
            }

            .modal-inactivite__sous-message {
                color: var(--texte-attenue, #666);
                margin: 0 0 25px;
                font-size: 0.85rem;
            }

            .modal-inactivite__bouton {
                padding: 14px 30px;
                font-size: 1rem;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .modal-inactivite__bouton:hover {
                transform: translateY(-2px);
            }
        `;
        document.head.appendChild(styles);
    }

    /**
     * Écoute les événements d'activité utilisateur
     */
    ecouterActivite() {
        const evenements = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'];
        
        evenements.forEach(event => {
            document.addEventListener(event, () => this.surActivite(), { passive: true });
        });
    }

    /**
     * Appelé lors d'une activité utilisateur
     */
    surActivite() {
        // Si la modal est affichée, l'activité annule la déconnexion
        if (this.modalAffichee) {
            this.annulerDeconnexion();
            return;
        }

        // Redémarre le timer d'inactivité
        this.demarrerTimerInactivite();
    }

    /**
     * Démarre le timer d'inactivité
     */
    demarrerTimerInactivite() {
        // Annule le timer existant
        if (this.timerInactivite) {
            clearTimeout(this.timerInactivite);
        }

        // Démarre un nouveau timer
        this.timerInactivite = setTimeout(() => {
            this.afficherAvertissement();
        }, this.DUREE_AVANT_AVERTISSEMENT);
    }

    /**
     * Affiche l'avertissement de déconnexion
     */
    afficherAvertissement() {
        this.modalAffichee = true;
        this.tempsRestant = this.DUREE_AVERTISSEMENT / 1000; // En secondes

        const modal = document.getElementById('modal-inactivite');
        modal.classList.add('active');

        // Mettre à jour le compteur
        this.mettreAJourCompteur();

        // Démarrer l'intervalle du compteur
        this.intervalCompteur = setInterval(() => {
            this.tempsRestant--;
            this.mettreAJourCompteur();

            if (this.tempsRestant <= 0) {
                this.deconnecter();
            }
        }, 1000);

        // Timer de déconnexion de secours
        this.timerDeconnexion = setTimeout(() => {
            this.deconnecter();
        }, this.DUREE_AVERTISSEMENT);
    }

    /**
     * Met à jour l'affichage du compteur
     */
    mettreAJourCompteur() {
        const minutes = Math.floor(this.tempsRestant / 60);
        const secondes = this.tempsRestant % 60;

        document.getElementById('timer-minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('timer-secondes').textContent = String(secondes).padStart(2, '0');
    }

    /**
     * Annule la déconnexion et cache la modal
     */
    annulerDeconnexion() {
        this.modalAffichee = false;

        // Cacher la modal
        const modal = document.getElementById('modal-inactivite');
        if (modal) {
            modal.classList.remove('active');
        }

        // Annuler les timers
        if (this.timerDeconnexion) {
            clearTimeout(this.timerDeconnexion);
            this.timerDeconnexion = null;
        }

        if (this.intervalCompteur) {
            clearInterval(this.intervalCompteur);
            this.intervalCompteur = null;
        }

        // Notifier le serveur que l'utilisateur est actif (réinitialise le timestamp)
        this.pingServeur();

        // Redémarrer le timer d'inactivité
        this.demarrerTimerInactivite();
    }

    /**
     * Envoie un ping au serveur pour réinitialiser le timeout de session
     */
    pingServeur() {
        fetch(window.location.href, {
            method: 'HEAD',
            credentials: 'same-origin'
        }).catch(() => {
            // Ignorer les erreurs de ping
        });
    }

    /**
     * Déconnecte l'utilisateur
     */
    async deconnecter() {
        // Annuler tous les timers
        if (this.timerInactivite) clearTimeout(this.timerInactivite);
        if (this.timerDeconnexion) clearTimeout(this.timerDeconnexion);
        if (this.intervalCompteur) clearInterval(this.intervalCompteur);

        try {
            // Récupérer l'URL de l'API
            const metaApiBase = document.querySelector('meta[name="api-base"]');
            const apiBase = metaApiBase ? metaApiBase.getAttribute('content') : './api';
            const urlDeconnexion = apiBase.replace(/\/$/, '') + '/connexion?action=logout';
            
            // Appeler l'API de déconnexion
            const reponse = await fetch(urlDeconnexion, {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            
            // Rediriger vers l'accueil
            const urlAccueil = apiBase.replace(/\/api\/?$/, '') + '/accueil';
            window.location.href = urlAccueil;
        } catch (erreur) {
            console.error('Erreur lors de la déconnexion automatique:', erreur);
            // En cas d'erreur, forcer la redirection vers l'accueil
            window.location.href = 'accueil';
        }
    }
}

// Initialiser le gestionnaire au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    new GestionnaireInactivite();
});
