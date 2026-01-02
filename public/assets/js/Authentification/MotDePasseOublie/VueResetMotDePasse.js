/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE RESET MOT DE PASSE - GESTION DU FORMULAIRE DE RÉINITIALISATION
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Gère le formulaire de réinitialisation du mot de passe avec token
 * - Validation du formulaire
 * - Vérification de la correspondance des mots de passe
 * - Affichage/masquage du mot de passe
 * - Soumission et redirection
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi } from '../../application.js';

export default class VueResetMotDePasse {
    
    /**
     * Initialise la vue de réinitialisation du mot de passe
     */
    constructor() {
        this.urlApi = obtenirUrlApi('/auth/reset-password');
        this.initialiser();
    }

    /**
     * Initialise les références DOM et attache les événements
     */
    initialiser() {
        // Références aux éléments DOM
        this.formulaire = document.getElementById('formulaire-reset-password');
        this.champPassword = document.getElementById('nouveau-password');
        this.champConfirmation = document.getElementById('confirmation-password');
        this.conteneurMessages = document.getElementById('messages-reset');
        this.boutonsToggle = document.querySelectorAll('.reset-form__toggle-password');

        // Attacher les événements
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
     * Gère la soumission du formulaire
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
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(body)
            });

            const donnees = await reponse.json();

            if (!reponse.ok) {
                throw new Error(donnees.error || 'Erreur lors de la réinitialisation');
            }

            // Succès - Rediriger directement vers la page de succès
            this.afficherPageSucces();

        } catch (erreur) {
            this.afficherErreur(erreur.message || 'Une erreur est survenue');
            boutonSubmit.disabled = false;
            boutonSubmit.innerHTML = '<i class="fas fa-check"></i> Modifier mon mot de passe';
        }
    }

    /**
     * Affiche un message d'erreur
     * 
     * @param {string} message - Message à afficher
     */
    afficherErreur(message) {
        this.conteneurMessages.innerHTML = `<div class="message message--erreur">${message}</div>`;
    }

    /**
     * Affiche un message de succès
     * 
     * @param {string} message - Message à afficher
     */
    afficherSucces(message) {
        this.conteneurMessages.innerHTML = `<div class="message message--succes">${message}</div>`;
    }
    
    /**
     * Affiche la page de succès après la réinitialisation
     */
    afficherPageSucces() {
        // Remplacer le contenu de la carte par le message de succès
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
