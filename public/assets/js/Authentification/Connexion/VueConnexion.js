/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE CONNEXION - GESTIONNAIRE DU FORMULAIRE DE CONNEXION
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe gère toute la logique côté client du formulaire de connexion :
 * - Soumission du formulaire via AJAX
 * - Validation des identifiants
 * - Navigation vers l'inscription ou mot de passe oublié
 * - Déconnexion de l'utilisateur
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @see     ControleurConnexion (PHP) Pour le traitement serveur
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi } from '../../application.js';

export default class VueConnexion {
    
    /**
     * Initialise la vue connexion avec l'URL de l'API
     */
    constructor() {
        /** @type {string} URL de l'endpoint d'authentification */
        this.urlApi = obtenirUrlApi('/connexion');
    }

    /**
     * Configure les écouteurs d'événements du formulaire
     * 
     * Doit être appelé après le chargement du DOM.
     * Attache les handlers pour la soumission et la navigation.
     */
    initialiser() {
        // Gestionnaire de soumission du formulaire
        const formulaire = document.getElementById('formulaire-connexion');
        if (formulaire) {
            formulaire.addEventListener('submit', (evenement) => this.gererSoumission(evenement));
        }
        
        // Navigation vers la page d'inscription
        document.getElementById('lien-inscription')?.addEventListener('click', (evenement) => {
            evenement.preventDefault();
            document.dispatchEvent(new CustomEvent('navigate', { detail: 'inscription' }));
        });
        
        // Navigation vers la page mot de passe oublié
        document.getElementById('lien-oubli')?.addEventListener('click', (evenement) => {
            evenement.preventDefault();
            document.dispatchEvent(new CustomEvent('navigate', { detail: 'oubli' }));
        });
    }

    /**
     * Traite la soumission du formulaire de connexion
     * 
     * Envoie les identifiants au serveur et redirige vers l'accueil
     * en cas de succès, ou affiche une erreur sinon.
     * 
     * @param {Event} evenement - Événement de soumission du formulaire
     * @async
     */
    async gererSoumission(evenement) {
        evenement.preventDefault();
        
        // Conteneur pour les messages d'erreur/succès
        const conteneurMessages = document.querySelector('#formulaire-connexion .messages-formulaire');
        conteneurMessages.innerHTML = '';
        
        // Récupération des valeurs du formulaire
        const email = document.getElementById('connexion-email').value.trim();
        const motDePasse = document.getElementById('connexion-password').value;

        try {
            // Envoi de la requête d'authentification
            const reponse = await fetch(this.urlApi + '?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    email: email, 
                    password: motDePasse 
                })
            });

            const donnees = await reponse.json();

            // Vérification du succès de l'authentification
            if (!reponse.ok) {
                throw new Error(donnees.error || 'Erreur de connexion');
            }

            // Redirection vers la page d'accueil après connexion réussie
            const urlBase = document.querySelector('base')?.href || '/';
            window.location.href = urlBase + 'accueil';

        } catch (erreur) {
            // Affichage du message d'erreur
            conteneurMessages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }

    /**
     * Déconnecte l'utilisateur actuel
     * 
     * Envoie une requête de déconnexion au serveur et redirige
     * vers la page d'accueil.
     * 
     * @static
     * @async
     */
    static async deconnexion() {
        const url = obtenirUrlApi('/connexion?action=logout');

        try {
            await fetch(url, { method: 'POST' });
            window.location.href = 'accueil';
        } catch (erreur) {
            // En cas d'erreur, on redirige quand même
            // (la session sera invalidée côté serveur de toute façon)
            window.location.href = 'accueil';
        }
    }
}
