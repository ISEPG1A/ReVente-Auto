/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE MOT DE PASSE OUBLIÉ - RÉCUPÉRATION DE COMPTE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe gère le processus de récupération de mot de passe :
 * - Demande de réinitialisation par email
 * - Formulaire de nouveau mot de passe avec jeton
 * - Navigation vers la page de connexion
 * 
 * Flux de récupération :
 * 1. L'utilisateur entre son email dans le formulaire d'oubli
 * 2. Le serveur envoie un lien avec un jeton de réinitialisation
 * 3. L'utilisateur clique sur le lien et saisit son nouveau mot de passe
 * 4. Le mot de passe est mis à jour et l'utilisateur est redirigé
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @see     ControleurAuth (PHP) Pour le traitement serveur
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi } from '../../application.js';

export default class VueMotDePasseOublie {
    
    /**
     * Initialise la vue avec l'URL de l'API de réinitialisation
     */
    constructor() {
        /** @type {string} URL de l'endpoint de réinitialisation de mot de passe */
        this.urlApi = obtenirUrlApi('/auth/reset-password');
    }

    /**
     * Configure les écouteurs d'événements des formulaires
     * 
     * Attache les handlers pour :
     * - Le formulaire de demande de réinitialisation (email)
     * - Le lien de retour vers la connexion
     * - Le formulaire de nouveau mot de passe
     */
    initialiser() {
        // Formulaire de demande de réinitialisation
        document.getElementById('formulaire-oubli')?.addEventListener('submit', 
            (evenement) => this.gererDemandeReinitialisation(evenement)
        );
        
        // Navigation vers la page de connexion
        document.getElementById('lien-connexion-oubli')?.addEventListener('click', (evenement) => {
            evenement.preventDefault();
            document.dispatchEvent(new CustomEvent('navigate', { detail: 'connexion' }));
        });
        
        // Formulaire de saisie du nouveau mot de passe
        document.getElementById('formulaire-reinitialisation')?.addEventListener('submit', 
            (evenement) => this.gererReinitialisation(evenement)
        );
    }

    /**
     * Traite la demande de réinitialisation de mot de passe
     * 
     * Envoie l'email de l'utilisateur au serveur qui génère
     * un lien de réinitialisation avec un jeton unique.
     * 
     * @param   {Event} evenement - Événement de soumission du formulaire
     * @async
     */
    async gererDemandeReinitialisation(evenement) {
        evenement.preventDefault();
        
        // Conteneur pour les messages
        const conteneurMessages = document.querySelector('#formulaire-oubli .messages-formulaire');
        conteneurMessages.innerHTML = '';
        
        // Récupération de l'email saisi
        const email = document.getElementById('oubli-email').value.trim();

        try {
            // Envoi de la demande de réinitialisation
            const reponse = await fetch(this.urlApi + '?action=forgot', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email })
            });
            
            const donnees = await reponse.json();
            
            if (!reponse.ok) {
                throw new Error(donnees.error || 'Erreur lors de la demande');
            }
            
            // Affichage du lien de réinitialisation (mode démo) ou du message de confirmation
            if (donnees.reset_link) {
                conteneurMessages.innerHTML = `<div class="message message--succes">Lien (démo): <a href="${donnees.reset_link}">Cliquez ici</a></div>`;
            } else {
                conteneurMessages.innerHTML = `<div class="message message--succes">${donnees.message}</div>`;
            }
            
        } catch (erreur) {
            conteneurMessages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }

    /**
     * Traite la soumission du nouveau mot de passe
     * 
     * Récupère le jeton depuis l'URL et envoie le nouveau
     * mot de passe au serveur pour mise à jour.
     * 
     * @param   {Event} evenement - Événement de soumission du formulaire
     * @async
     */
    async gererReinitialisation(evenement) {
        evenement.preventDefault();
        
        // Conteneur pour les messages
        const conteneurMessages = document.querySelector('#formulaire-reinitialisation .messages-formulaire');
        conteneurMessages.innerHTML = '';
        
        // Récupération du jeton depuis les paramètres URL
        const parametresUrl = new URLSearchParams(window.location.search);
        const jetonReinitialisation = parametresUrl.get('reset');
        
        // Récupération du nouveau mot de passe
        const nouveauMotDePasse = document.getElementById('reinitialisation-password').value;

        try {
            // Envoi de la demande de mise à jour du mot de passe
            const reponse = await fetch(this.urlApi + '?action=reset', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    token: jetonReinitialisation, 
                    password: nouveauMotDePasse 
                })
            });
            
            const donnees = await reponse.json();

            if (!reponse.ok) {
                throw new Error(donnees.error || 'Erreur lors de la réinitialisation');
            }

            // Message de succès et redirection vers la connexion
            conteneurMessages.innerHTML = '<div class="message message--succes">Mot de passe mis à jour. Redirection...</div>';
            
            setTimeout(() => {
                document.dispatchEvent(new CustomEvent('navigate', { detail: 'connexion' }));
            }, 1500);
            
        } catch (erreur) {
            conteneurMessages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }
}
