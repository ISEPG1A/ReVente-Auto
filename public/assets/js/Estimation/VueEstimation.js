import { obtenirUrlApi } from '../app.js';

export default class VueEstimation {
    constructor() {
        // Construction de l'URL du contrôleur
        this.urlApi = obtenirUrlApi('/estimation');

        this.initialiser();
    }

    initialiser() {
        this.formulaireEstimation = document.getElementById('formulaire-estimation');
        this.carteResultat = document.getElementById('resultat-estimation');
        this.valeurPrix = document.getElementById('valeur-prix');
        this.boutonNouvelleEstimation = document.getElementById('bouton-nouvelle-estimation');
        this.boutonSoumettre = document.getElementById('bouton-estimer');
        this.chargeur = this.boutonSoumettre?.querySelector('.chargeur');
        this.texteBouton = this.boutonSoumettre?.querySelector('.texte-bouton');
        this.messages = document.querySelector('.messages-formulaire');

        if (this.formulaireEstimation) {
            this.formulaireEstimation.addEventListener('submit', (e) => this.gererSoumission(e));
        }

        if (this.boutonNouvelleEstimation) {
            this.boutonNouvelleEstimation.addEventListener('click', () => this.reinitialiserFormulaire());
        }
    }

    definirChargement(estEnChargement) {
        if (this.boutonSoumettre) {
            this.boutonSoumettre.disabled = estEnChargement;
            if (this.chargeur) this.chargeur.hidden = !estEnChargement;
            if (this.texteBouton) this.texteBouton.style.opacity = estEnChargement ? '0.7' : '1';
        }
    }

    afficherMessage(texte, type = 'ok') {
        if (this.messages) {
            if (!texte) {
                this.messages.innerHTML = '';
                return;
            }
            this.messages.innerHTML = `<div class="msg msg--${type}">${texte}</div>`;
        }
    }

    async gererSoumission(e) {
        e.preventDefault();
        
        const donneesFormulaire = new FormData(this.formulaireEstimation);
        const donnees = Object.fromEntries(donneesFormulaire.entries());
        
        if (!donnees.marque || !donnees.modele || !donnees.annee) {
            this.afficherMessage('Veuillez remplir tous les champs obligatoires.', 'err');
            return;
        }

        try {
            this.definirChargement(true);
            this.afficherMessage('');
            
            const reponse = await fetch(this.urlApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(donnees)
            });

            const resultat = await reponse.json();

            if (!reponse.ok) {
                throw new Error(resultat.error || 'Une erreur est survenue lors de l\'estimation.');
            }

            if (resultat.prix !== null) {
                this.valeurPrix.textContent = new Intl.NumberFormat('fr-FR').format(resultat.prix);
                this.formulaireEstimation.hidden = true;
                this.carteResultat.hidden = false;
            } else {
                this.afficherMessage(resultat.message || 'Estimation impossible pour ce véhicule.', 'err');
            }

        } catch (erreur) {
            console.error(erreur);
            this.afficherMessage(erreur.message, 'err');
        } finally {
            this.definirChargement(false);
        }
    }

    reinitialiserFormulaire() {
        this.carteResultat.hidden = true;
        this.formulaireEstimation.hidden = false;
        this.formulaireEstimation.reset();
        this.afficherMessage('');
    }
}
