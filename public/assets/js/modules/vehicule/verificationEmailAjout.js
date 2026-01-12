/**
 * Module de gestion de la vérification email sur la page d'ajout de véhicule
 * 
 * Permet aux utilisateurs non vérifiés de renvoyer l'email de vérification
 * avec un système de cooldown pour éviter le spam.
 */

import { obtenirUrlApi } from '../../application.js';

export default class VerificationEmailAjout {
    constructor() {
        this.boutonVerifier = document.getElementById('btn-renvoyer-verification-ajout');
        this.conteneurMessages = document.getElementById('message-verification-ajout');
        this.urlApi = obtenirUrlApi('/profil');
        
        this.init();
    }
    
    /**
     * Initialisation des événements
     */
    init() {
        if (this.boutonVerifier) {
            this.boutonVerifier.addEventListener('click', () => this.gererVerificationEmail());
        }
    }
    
    /**
     * Gère l'envoi de l'email de vérification
     * Avec système de cooldown de 30 secondes.
     * 
     * @async
     */
    async gererVerificationEmail() {
        this.conteneurMessages.innerHTML = '';
        
        // Désactiver le bouton temporairement
        if (this.boutonVerifier) {
            this.boutonVerifier.disabled = true;
        }

        try {
            const reponse = await fetch(this.urlApi + '?action=request_email_verification', {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            const donnees = await reponse.json();

            if (!reponse.ok) {
                // Si erreur de cooldown, gérer l'affichage du temps restant
                if (donnees.cooldown) {
                    this.demarrerCooldown(donnees.cooldown);
                }
                throw new Error(donnees.erreur || donnees.error || 'Envoi du lien impossible');
            }

            // Affichage du lien (mode démo) ou confirmation
            if (donnees.verification_link) {
                this.conteneurMessages.innerHTML = `<div class="message message--succes">Lien de vérification : <a href="${donnees.verification_link}" target="_blank">${donnees.verification_link}</a></div>`;
            } else {
                this.conteneurMessages.innerHTML = '<div class="message message--succes">Email de vérification envoyé avec succès. Vérifiez votre boîte mail.</div>';
            }
            
            // Démarrer le cooldown de 30 secondes
            this.demarrerCooldown(30);
            
        } catch (erreur) {
            this.conteneurMessages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
            // Réactiver le bouton en cas d'erreur (sauf si cooldown)
            if (this.boutonVerifier && !erreur.message.includes('attendre')) {
                this.boutonVerifier.disabled = false;
            }
        }
    }
    
    /**
     * Démarre un cooldown sur le bouton de vérification email
     * 
     * @param {number} secondes - Nombre de secondes du cooldown
     */
    demarrerCooldown(secondes) {
        if (!this.boutonVerifier) return;
        
        let tempsRestant = secondes;
        const texteOriginal = this.boutonVerifier.innerHTML;
        
        // Mettre à jour l'affichage chaque seconde
        const interval = setInterval(() => {
            this.boutonVerifier.disabled = true;
            this.boutonVerifier.innerHTML = `<i class="fas fa-clock"></i> Attendre ${tempsRestant}s`;
            tempsRestant--;
            
            if (tempsRestant < 0) {
                clearInterval(interval);
                this.boutonVerifier.disabled = false;
                this.boutonVerifier.innerHTML = texteOriginal;
            }
        }, 1000);
    }
}
