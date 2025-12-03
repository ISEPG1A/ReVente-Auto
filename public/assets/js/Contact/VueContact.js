import { obtenirUrlApi } from '../app.js';

/**
 * Classe gérant l'affichage et les interactions du formulaire de contact côté client.
 */
export class VueContact {
    
    /**
     * Constructeur de la vue.
     * Initialise les sélecteurs et l'URL de l'API.
     */
    constructor() {
        // Le point de terminaison pointe maintenant vers le Contrôleur PHP
        this.urlApi = obtenirUrlApi('/contact');
        this.formulaire = null;
        this.boutonEnvoi = null;
        this.zoneMessages = null;
    }

    /**
     * Initialise le composant : attache les événements.
     */
    initialiser() {
        this.formulaire = document.getElementById('formulaire-contact');
        this.boutonEnvoi = document.getElementById('bouton-envoi');
        
        if (this.formulaire) {
            this.zoneMessages = this.formulaire.querySelector('.messages-formulaire');
            this.formulaire.addEventListener('submit', (e) => this.gererSoumission(e));
        }
    }

    /**
     * Affiche un message de retour à l'utilisateur.
     * 
     * @param {string} texte Le message à afficher
     * @param {boolean} succes Vrai si c'est un succès, Faux pour une erreur
     */
    afficherMessage(texte, succes = true) {
        if (!this.zoneMessages) return;
        this.zoneMessages.innerHTML = `<div class="message message--${succes ? 'succes' : 'erreur'}">${texte}</div>`;
    }

    /**
     * Active ou désactive le bouton d'envoi (état de chargement).
     * 
     * @param {boolean} chargement Vrai pour désactiver, Faux pour activer
     */
    definirChargement(chargement) {
        if (this.boutonEnvoi) {
            this.boutonEnvoi.disabled = chargement;
            this.boutonEnvoi.textContent = chargement ? 'Envoi...' : 'Envoyer';
        }
    }

    /**
     * Gère la soumission du formulaire via AJAX.
     * 
     * @param {Event} e L'événement de soumission
     */
    async gererSoumission(e) {
        e.preventDefault();
        this.zoneMessages.innerHTML = '';
        this.definirChargement(true);

        try {
            const donneesFormulaire = new FormData(this.formulaire);
            
            // Validation basique côté client
            const nom = donneesFormulaire.get('nom')?.toString().trim();
            const message = donneesFormulaire.get('message')?.toString().trim();

            if (!nom || !message) throw new Error('Veuillez remplir tous les champs obligatoires.');
            if (message.length < 10) throw new Error('Le message est trop court.');

            const reponse = await fetch(this.urlApi, {
                method: 'POST',
                body: donneesFormulaire,
                headers: { 'Accept': 'application/json' }
            });

            const resultat = await reponse.json().catch(() => ({}));

            if (!reponse.ok) {
                throw new Error(resultat.erreur || 'Une erreur est survenue lors de l\'envoi.');
            }

            this.afficherMessage(resultat.message || 'Message envoyé avec succès !', true);
            this.formulaire.reset();

        } catch (erreur) {
            this.afficherMessage(erreur.message, false);
        } finally {
            this.definirChargement(false);
        }
    }
}
