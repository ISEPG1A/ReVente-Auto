import { obtenirUrlApi } from '../../app.js';

export default class VueInscription {
    constructor() {
        this.urlApi = obtenirUrlApi('/inscription');
    }

    initialiser() {
        const formulaire = document.getElementById('formulaire-inscription');
        if (formulaire) {
            formulaire.addEventListener('submit', (e) => this.gererSoumission(e));
        }
        
        document.getElementById('lien-connexion-inscription')?.addEventListener('click', (e) => {
            e.preventDefault();
            document.dispatchEvent(new CustomEvent('navigate', { detail: 'connexion' }));
        });
    }

    estMotDePasseFort(motDePasse) {
        return motDePasse.length >= 8 && 
               /[a-z]/.test(motDePasse) && 
               /[A-Z]/.test(motDePasse) && 
               /\d/.test(motDePasse);
    }

    async gererSoumission(e) {
        e.preventDefault();
        const messages = document.querySelector('#formulaire-inscription .messages-formulaire');
        messages.innerHTML = '';
        
        const motDePasse = document.getElementById('inscription-password').value;
        if (!this.estMotDePasseFort(motDePasse)) {
            messages.innerHTML = '<div class="message message--erreur">Mot de passe trop faible.</div>';
            return;
        }

        const donneesFormulaire = new FormData(document.getElementById('formulaire-inscription'));

        try {
            const reponse = await fetch(this.urlApi, {
                method: 'POST',
                body: donneesFormulaire
            });

            const donnees = await reponse.json();

            if (!reponse.ok) {
                throw new Error(donnees.error || 'Erreur d\'inscription');
            }

            const urlBase = document.querySelector('base')?.href || '/';
            window.location.href = urlBase + 'accueil';

        } catch (erreur) {
            messages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }
}
