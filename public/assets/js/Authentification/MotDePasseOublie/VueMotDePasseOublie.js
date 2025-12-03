import { obtenirUrlApi } from '../../app.js';

export default class VueMotDePasseOublie {
    constructor() {
        this.urlApi = obtenirUrlApi('/auth/reset-password');
    }

    initialiser() {
        document.getElementById('formulaire-oubli')?.addEventListener('submit', (e) => this.gererOubli(e));
        document.getElementById('lien-connexion-oubli')?.addEventListener('click', (e) => {
            e.preventDefault();
            document.dispatchEvent(new CustomEvent('navigate', { detail: 'connexion' }));
        });
        document.getElementById('formulaire-reinitialisation')?.addEventListener('submit', (e) => this.gererReinitialisation(e));
    }

    async gererOubli(e) {
        e.preventDefault();
        const boiteMessage = document.querySelector('#formulaire-oubli .messages-formulaire');
        boiteMessage.innerHTML = '';
        const email = document.getElementById('oubli-email').value.trim();

        try {
            const reponse = await fetch(this.urlApi + '?action=forgot', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email })
            });
            const donnees = await reponse.json();
            
            if (!reponse.ok) throw new Error(donnees.error || 'Erreur');
            
            boiteMessage.innerHTML = `<div class="message message--succes">${donnees.reset_link ? `Lien (démo): <a href="${donnees.reset_link}">Cliquez ici</a>` : donnees.message}</div>`;
        } catch (erreur) {
            boiteMessage.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }

    async gererReinitialisation(e) {
        e.preventDefault();
        const boiteMessage = document.querySelector('#formulaire-reinitialisation .messages-formulaire');
        boiteMessage.innerHTML = '';
        
        const parametresUrl = new URLSearchParams(window.location.search);
        const jeton = parametresUrl.get('reset');
        const motDePasse = document.getElementById('reinitialisation-password').value;

        try {
            const reponse = await fetch(this.urlApi + '?action=reset', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: jeton, password: motDePasse })
            });
            const donnees = await reponse.json();

            if (!reponse.ok) throw new Error(donnees.error || 'Erreur');

            boiteMessage.innerHTML = '<div class="message message--succes">Mot de passe mis à jour. Redirection...</div>';
            setTimeout(() => {
                document.dispatchEvent(new CustomEvent('navigate', { detail: 'connexion' }));
            }, 1500);
        } catch (erreur) {
            boiteMessage.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }
}
