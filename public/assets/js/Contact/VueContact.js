/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE CONTACT - FORMULAIRE DE CONTACT DU SITE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe gère le formulaire de contact :
 * - Validation côté client des champs obligatoires
 * - Soumission AJAX du formulaire
 * - Affichage des messages de succès/erreur
 * - État de chargement du bouton d'envoi
 * 
 * Validation :
 * - Nom : obligatoire
 * - Message : obligatoire, minimum 10 caractères
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @see     ControleurContact (PHP) Pour le traitement serveur
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi } from '../application.js';

/**
 * Classe gérant le formulaire de contact
 * @class
 */
export class VueContact {
    
    /**
     * Initialise la vue avec l'URL de l'API
     */
    constructor() {
        /** @type {string} URL de l'endpoint du contact */
        this.urlApi = obtenirUrlApi('/contact');
        
        /** @type {HTMLFormElement|null} Formulaire de contact */
        this.formulaire = null;
        
        /** @type {HTMLButtonElement|null} Bouton d'envoi */
        this.boutonEnvoi = null;
        
        /** @type {HTMLElement|null} Zone d'affichage des messages */
        this.zoneMessages = null;
    }

    /**
     * Initialise le composant et attache les événements
     */
    initialiser() {
        // Récupération des références DOM
        this.formulaire = document.getElementById('formulaire-contact');
        this.boutonEnvoi = document.getElementById('bouton-envoi');
        
        if (this.formulaire) {
            this.zoneMessages = this.formulaire.querySelector('.messages-formulaire');
            this.formulaire.addEventListener('submit', (evenement) => this.gererSoumission(evenement));
        }
    }

    /**
     * Affiche un message de retour à l'utilisateur
     * 
     * @param {string} texte - Message à afficher
     * @param {boolean} succes - true pour succès, false pour erreur
     */
    afficherMessage(texte, succes = true) {
        if (!this.zoneMessages) return;
        
        const classeMessage = succes ? 'succes' : 'erreur';
        this.zoneMessages.innerHTML = `
            <div class="message message--${classeMessage}">
                ${texte}
            </div>`;
    }

    /**
     * Active ou désactive le bouton d'envoi (état de chargement)
     * 
     * @param {boolean} chargement - true pour désactiver, false pour activer
     */
    definirChargement(chargement) {
        if (this.boutonEnvoi) {
            this.boutonEnvoi.disabled = chargement;
            this.boutonEnvoi.textContent = chargement ? 'Envoi...' : 'Envoyer';
        }
    }

    /**
     * Gère la soumission du formulaire via AJAX
     * 
     * Effectue une validation côté client avant l'envoi,
     * puis traite la réponse du serveur.
     * 
     * @param {Event} evenement - Événement de soumission
     * @async
     */
    async gererSoumission(evenement) {
        evenement.preventDefault();
        this.zoneMessages.innerHTML = '';
        this.definirChargement(true);

        try {
            const donneesFormulaire = new FormData(this.formulaire);
            
            // Validation côté client
            const nom = donneesFormulaire.get('nom')?.toString().trim();
            const message = donneesFormulaire.get('message')?.toString().trim();

            if (!nom || !message) {
                throw new Error('Veuillez remplir tous les champs obligatoires.');
            }
            
            if (message.length < 10) {
                throw new Error('Le message est trop court (minimum 10 caractères).');
            }

            // Envoi de la requête
            const reponse = await fetch(this.urlApi, {
                method: 'POST',
                body: donneesFormulaire,
                headers: { 'Accept': 'application/json' }
            });

            const resultat = await reponse.json().catch(() => ({}));

            if (!reponse.ok) {
                throw new Error(resultat.erreur || 'Une erreur est survenue lors de l\'envoi.');
            }

            // Succès : affichage du message et réinitialisation
            this.afficherMessage(resultat.message || 'Message envoyé avec succès !', true);
            this.formulaire.reset();

        } catch (erreur) {
            this.afficherMessage(erreur.message, false);
        } finally {
            this.definirChargement(false);
        }
    }
}
