/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE INSCRIPTION - GESTIONNAIRE DU FORMULAIRE D'INSCRIPTION
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe gère toute la logique côté client du formulaire d'inscription :
 * - Validation du mot de passe (force minimale requise)
 * - Soumission du formulaire via AJAX avec FormData
 * - Navigation vers la page de connexion
 * - Affichage des messages d'erreur/succès
 * 
 * Critères de validation du mot de passe :
 * - Minimum 8 caractères
 * - Au moins une minuscule
 * - Au moins une majuscule
 * - Au moins un chiffre
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @see     ControleurInscription (PHP) Pour le traitement serveur
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi } from '../../application.js';

export default class VueInscription {
    
    /**
     * Initialise la vue inscription avec l'URL de l'API
     */
    constructor() {
        /** @type {string} URL de l'endpoint d'inscription */
        this.urlApi = obtenirUrlApi('/inscription');
    }

    /**
     * Configure les écouteurs d'événements du formulaire
     * 
     * Attache les handlers pour la soumission du formulaire
     * et la navigation vers la page de connexion.
     */
    initialiser() {
        // Gestionnaire de soumission du formulaire d'inscription
        const formulaire = document.getElementById('formulaire-inscription');
        if (formulaire) {
            formulaire.addEventListener('submit', (evenement) => this.gererSoumission(evenement));
        }
        
        // Navigation vers la page de connexion
        document.getElementById('lien-connexion-inscription')?.addEventListener('click', (evenement) => {
            evenement.preventDefault();
            document.dispatchEvent(new CustomEvent('navigate', { detail: 'connexion' }));
        });
    }

    /**
     * Vérifie la force du mot de passe
     * 
     * Le mot de passe doit respecter les critères suivants :
     * - Longueur minimale de 8 caractères
     * - Contenir au moins une lettre minuscule (a-z)
     * - Contenir au moins une lettre majuscule (A-Z)
     * - Contenir au moins un chiffre (0-9)
     * 
     * @param   {string}  motDePasse - Le mot de passe à valider
     * @returns {boolean} true si le mot de passe est suffisamment fort
     */
    estMotDePasseFort(motDePasse) {
        const longueurMinimale = motDePasse.length >= 8;
        const contientMinuscule = /[a-z]/.test(motDePasse);
        const contientMajuscule = /[A-Z]/.test(motDePasse);
        const contientChiffre = /\d/.test(motDePasse);
        
        return longueurMinimale && contientMinuscule && contientMajuscule && contientChiffre;
    }

    /**
     * Traite la soumission du formulaire d'inscription
     * 
     * Valide d'abord le mot de passe côté client, puis envoie
     * les données au serveur via FormData (pour supporter l'upload d'avatar).
     * 
     * @param   {Event} evenement - Événement de soumission du formulaire
     * @async
     */
    async gererSoumission(evenement) {
        evenement.preventDefault();
        
        // Conteneur pour les messages d'erreur/succès
        const conteneurMessages = document.querySelector('#formulaire-inscription .messages-formulaire');
        conteneurMessages.innerHTML = '';
        
        // Validation côté client du mot de passe
        const motDePasse = document.getElementById('inscription-password').value;
        if (!this.estMotDePasseFort(motDePasse)) {
            conteneurMessages.innerHTML = '<div class="message message--erreur">Mot de passe trop faible.</div>';
            return;
        }

        // Préparation des données avec FormData (nécessaire pour l'upload d'avatar)
        const donneesFormulaire = new FormData(document.getElementById('formulaire-inscription'));

        try {
            // Envoi de la requête d'inscription
            const reponse = await fetch(this.urlApi, {
                method: 'POST',
                body: donneesFormulaire
            });

            const donnees = await reponse.json();

            // Vérification du succès de l'inscription
            if (!reponse.ok) {
                throw new Error(donnees.error || 'Erreur d\'inscription');
            }

            // Redirection vers la page d'accueil après inscription réussie
            const urlBase = document.querySelector('base')?.href || '/';
            window.location.href = urlBase + 'accueil';

        } catch (erreur) {
            // Affichage du message d'erreur
            conteneurMessages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }
}
