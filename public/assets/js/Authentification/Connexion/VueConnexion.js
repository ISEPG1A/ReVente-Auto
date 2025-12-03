import { obtenirUrlApi } from '../../app.js';

export default class VueConnexion {
    constructor() {
        this.urlApi = obtenirUrlApi('/connexion');
    }

    initialiser() {
        const formulaire = document.getElementById('formulaire-connexion');
        if (formulaire) {
            formulaire.addEventListener('submit', (e) => this.gererSoumission(e));
        }
        
        // Dispatch custom events for navigation
        document.getElementById('lien-inscription')?.addEventListener('click', (e) => {
            e.preventDefault();
            document.dispatchEvent(new CustomEvent('navigate', { detail: 'inscription' }));
        });
        document.getElementById('lien-oubli')?.addEventListener('click', (e) => {
            e.preventDefault();
            document.dispatchEvent(new CustomEvent('navigate', { detail: 'oubli' }));
        });
    }

    async gererSoumission(e) {
        e.preventDefault();
        const messages = document.querySelector('#formulaire-connexion .messages-formulaire');
        messages.innerHTML = '';
        
        const email = document.getElementById('connexion-email').value.trim();
        const motDePasse = document.getElementById('connexion-password').value;

        try {
            const reponse = await fetch(this.urlApi + '?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email, password: motDePasse })
            });

            const donnees = await reponse.json();

            if (!reponse.ok) {
                throw new Error(donnees.error || 'Erreur de connexion');
            }

            const urlBase = document.querySelector('base')?.href || '/';
            window.location.href = urlBase + 'accueil';

        } catch (erreur) {
            messages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }

    static async deconnexion() {
        const url = obtenirUrlApi('/connexion?action=logout');

        try {
            await fetch(url, { method: 'POST' });
            window.location.href = 'accueil';
        } catch (e) {
            console.error('Erreur deconnexion', e);
        }
    }
}
