/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE RÉINITIALISATION MOT DE PASSE - GESTION COMPLÈTE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Gère DEUX processus distincts :
 * 1. DEMANDE de réinitialisation (formulaire dans connexion.php)
 *    - L'utilisateur saisit son email
 *    - Le serveur envoie un lien avec token
 * 
 * 2. RÉINITIALISATION avec token (formulaire dans reset_mot_de_passe.php)
 *    - L'utilisateur clique sur le lien reçu
 *    - Saisit son nouveau mot de passe
 *    - Le mot de passe est mis à jour
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0 (Fusion VueMotDePasseOublie + VueResetMotDePasse)
 * @see     ControleurMotDePasseOublie (PHP) Pour le traitement serveur
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi } from '../../application.js';

export default class VueResetMotDePasse {
    
    /**
     * Initialise la vue avec l'URL de l'API
     */
    constructor() {
        this.urlApi = obtenirUrlApi('/auth/reset-password');
        this.initialiser();
    }

    /**
     * Initialise les références DOM et attache les événements
     * Gère les deux types de formulaires (demande + reset avec token)
     */
    initialiser() {
        // ═══════════════════════════════════════════════════════════════
        // FORMULAIRE 1 : DEMANDE DE RÉINITIALISATION (dans connexion.php)
        // ═══════════════════════════════════════════════════════════════
        const formulaireOubli = document.getElementById('formulaire-oubli');
        if (formulaireOubli) {
            formulaireOubli.addEventListener('submit', (e) => this.gererDemandeReinitialisation(e));
        }
        
        // Lien retour vers connexion depuis formulaire oubli
        document.getElementById('lien-connexion-oubli')?.addEventListener('click', (evenement) => {
            evenement.preventDefault();
            document.dispatchEvent(new CustomEvent('navigate', { detail: 'connexion' }));
        });
        
        // ═══════════════════════════════════════════════════════════════
        // FORMULAIRE 2 : RÉINITIALISATION AVEC TOKEN (dans reset_mot_de_passe.php)
        // ═══════════════════════════════════════════════════════════════
        this.formulaire = document.getElementById('formulaire-reset-password');
        this.champPassword = document.getElementById('nouveau-password');
        this.champConfirmation = document.getElementById('confirmation-password');
        this.conteneurMessages = document.getElementById('messages-reset');
        this.boutonsToggle = document.querySelectorAll('.reset-form__toggle-password');

        // Attacher les événements du formulaire de reset
        if (this.formulaire) {
            this.formulaire.addEventListener('submit', (e) => this.gererSoumission(e));
        }

        // Gérer l'affichage/masquage des mots de passe
        this.boutonsToggle.forEach((bouton) => {
            bouton.addEventListener('click', (e) => {
                e.preventDefault();
                this.togglePasswordVisibility(bouton);
            });
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PARTIE 1 : DEMANDE DE RÉINITIALISATION (ENVOI EMAIL)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Traite la demande de réinitialisation de mot de passe
     * Envoie l'email de l'utilisateur au serveur qui génère un lien avec token
     * 
     * @param {Event} evenement - Événement de soumission du formulaire
     * @async
     */
    async gererDemandeReinitialisation(evenement) {
        evenement.preventDefault();
        
        const conteneurMessages = document.querySelector('#formulaire-oubli .messages-formulaire');
        conteneurMessages.innerHTML = '';
        
        const email = document.getElementById('oubli-email').value.trim();
        const boutonSubmit = evenement.target.querySelector('button[type="submit"]');

        // Texte original fixe du bouton
        const texteOriginal = '<i class="fas fa-paper-plane"></i> Envoyer le lien';
        
        // Désactiver le bouton et afficher l'état de chargement
        boutonSubmit.disabled = true;
        boutonSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';

        try {
            const reponse = await fetch(this.urlApi + '?action=forgot', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email })
            });
            
            const donnees = await reponse.json();
            
            if (!reponse.ok) {
                throw new Error(donnees.erreur || 'Erreur lors de la demande');
            }
            
            // Affichage du lien (mode démo) ou message de confirmation
            if (donnees.reset_link) {
                conteneurMessages.innerHTML = `<div class="message message--succes">Lien (démo): <a href="${donnees.reset_link}">Cliquez ici</a></div>`;
            } else {
                conteneurMessages.innerHTML = `<div class="message message--succes">${donnees.message}</div>`;
            }
            
            // 🔒 COOLDOWN 30 SECONDES : Désactiver le bouton pour éviter le spam
            this.demarrerCooldown(boutonSubmit, texteOriginal, 30);
            
        } catch (erreur) {
            conteneurMessages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
            
            // En cas d'erreur, réactiver le bouton après 5 secondes (cooldown court)
            setTimeout(() => {
                boutonSubmit.disabled = false;
                boutonSubmit.innerHTML = texteOriginal;
            }, 5000);
        }
    }

    /**
     * Démarre un cooldown sur un bouton pour éviter le spam
     * 
     * @param {HTMLButtonElement} bouton - Bouton à désactiver
     * @param {string} texteOriginal - Texte original du bouton
     * @param {number} duree - Durée du cooldown en secondes
     */
    demarrerCooldown(bouton, texteOriginal, duree) {
        let secondesRestantes = duree;
        
        const mettreAJourBouton = () => {
            bouton.innerHTML = `<i class="fas fa-clock"></i> Réessayer dans ${secondesRestantes}s`;
        };
        
        mettreAJourBouton();
        
        const interval = setInterval(() => {
            secondesRestantes--;
            
            if (secondesRestantes <= 0) {
                clearInterval(interval);
                bouton.disabled = false;
                bouton.innerHTML = texteOriginal;
            } else {
                mettreAJourBouton();
            }
        }, 1000);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PARTIE 2 : RÉINITIALISATION AVEC TOKEN (NOUVEAU MOT DE PASSE)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Affiche ou masque le mot de passe
     * 
     * @param {HTMLButtonElement} bouton - Bouton toggle cliqué
     */
    togglePasswordVisibility(bouton) {
        const wrapper = bouton.closest('.reset-form__password-wrapper');
        
        if (!wrapper) {
            return;
        }
        
        const input = wrapper.querySelector('input');
        
        if (!input) {
            return;
        }
        
        const icone = bouton.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icone.classList.remove('fa-eye');
            icone.classList.add('fa-eye-slash');
            bouton.setAttribute('aria-label', 'Masquer le mot de passe');
        } else {
            input.type = 'password';
            icone.classList.remove('fa-eye-slash');
            icone.classList.add('fa-eye');
            bouton.setAttribute('aria-label', 'Afficher le mot de passe');
        }
    }

    /**
     * Gère la soumission du formulaire de réinitialisation
     * Envoie le nouveau mot de passe avec le token au serveur
     * 
     * @param {Event} evenement - Événement de soumission
     * @async
     */
    async gererSoumission(evenement) {
        evenement.preventDefault();
        
        this.conteneurMessages.innerHTML = '';

        // Récupérer les valeurs
        const donneesFormulaire = new FormData(this.formulaire);
        const motDePasse = donneesFormulaire.get('password');
        const confirmation = donneesFormulaire.get('password_confirm');
        const token = donneesFormulaire.get('token');

        // Validation côté client
        if (motDePasse.length < 8) {
            this.afficherErreur('Le mot de passe doit contenir au moins 8 caractères.');
            return;
        }

        if (!/[a-z]/.test(motDePasse) || !/[A-Z]/.test(motDePasse) || !/\d/.test(motDePasse)) {
            this.afficherErreur('Le mot de passe doit contenir des majuscules, minuscules et chiffres.');
            return;
        }

        if (motDePasse !== confirmation) {
            this.afficherErreur('Les mots de passe ne correspondent pas.');
            return;
        }

        // Désactiver le bouton pendant la soumission
        const boutonSubmit = this.formulaire.querySelector('button[type="submit"]');
        boutonSubmit.disabled = true;
        boutonSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Modification en cours...';

        try {
            const url = this.urlApi + '?action=reset';
            
            const body = { 
                token, 
                password: motDePasse,
                password_confirm: confirmation
            };

            const reponse = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body)
            });

            const donnees = await reponse.json();

            if (!reponse.ok) {
                throw new Error(donnees.error || 'Une erreur est survenue');
            }

            // Succès : afficher la page de confirmation
            this.afficherPageSucces();

        } catch (erreur) {
            this.afficherErreur(erreur.message);
            
            // Réactiver le bouton
            boutonSubmit.disabled = false;
            boutonSubmit.innerHTML = '<i class="fas fa-lock"></i> Modifier le mot de passe';
        }
    }

    /**
     * Affiche un message d'erreur dans le conteneur
     * 
     * @param {string} message - Message d'erreur à afficher
     */
    afficherErreur(message) {
        this.conteneurMessages.innerHTML = `
            <div class="message message--erreur">
                <i class="fas fa-exclamation-triangle"></i>
                ${message}
            </div>
        `;
    }

    /**
     * Affiche un message de succès dans le conteneur
     * 
     * @param {string} message - Message de succès à afficher
     */
    afficherSucces(message) {
        this.conteneurMessages.innerHTML = `
            <div class="message message--succes">
                <i class="fas fa-check-circle"></i>
                ${message}
            </div>
        `;
    }

    /**
     * Remplace le contenu du formulaire par un message de confirmation
     * et démarre un compte à rebours de redirection
     */
    afficherPageSucces() {
        const carte = document.querySelector('.reset-carte');
        if (!carte) return;
        
        carte.innerHTML = `
            <div class="verification-icone verification-icone--succes">
                <i class="fas fa-check"></i>
            </div>
            
            <h1>Mot de passe modifié !</h1>
            
            <p>Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</p>
            
            <div class="verification-actions">
                <a href="${document.querySelector('base')?.href || '/'}connexion" class="bouton bouton--lg">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </a>
            </div>
            
            <div class="verification-compte-rebours">
                Redirection automatique dans <strong id="compte-rebours">5</strong> secondes...
            </div>
        `;
        
        // Démarrer le compte à rebours
        let secondesRestantes = 5;
        const elementCompteur = document.getElementById('compte-rebours');
        
        const interval = setInterval(() => {
            secondesRestantes--;
            if (elementCompteur) {
                elementCompteur.textContent = secondesRestantes;
            }
            
            if (secondesRestantes <= 0) {
                clearInterval(interval);
                const urlBase = document.querySelector('base')?.href || '/';
                window.location.href = urlBase + 'connexion';
            }
        }, 1000);
    }
}
