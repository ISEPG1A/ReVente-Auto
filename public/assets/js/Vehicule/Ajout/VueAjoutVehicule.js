import { obtenirUrlApi } from '../../app.js';

export default class VueAjoutVehicule {
    constructor() {
        this.initialiser();
    }

    initialiser() {
        this.formulaire = document.getElementById('formulaire-vehicule');
        this.messages = document.querySelector('.messages-formulaire');
        this.boutonSoumettre = document.getElementById('bouton-soumettre');
        
        // Éléments de prévisualisation d'image
        this.entreeImage = document.getElementById('image');
        this.conteneurApercu = document.getElementById('conteneur-apercu');
        this.apercuImage = document.getElementById('apercu-image');
        this.boutonSupprimer = document.getElementById('bouton-supprimer-image');
        this.zoneTelechargement = document.querySelector('.zone-telechargement');

        // Construction de l'URL API
        this.urlApi = obtenirUrlApi('/vehicule/ajout');

        if (this.formulaire) {
            this.formulaire.addEventListener('submit', (e) => this.gererSoumission(e));
        }

        if (this.entreeImage) {
            this.entreeImage.addEventListener('change', (e) => this.gererChangementImage(e));
        }

        if (this.boutonSupprimer) {
            this.boutonSupprimer.addEventListener('click', () => this.gererSuppressionImage());
        }
    }

    gererChangementImage(e) {
        const fichier = e.target.files[0];
        if (fichier) {
            const lecteur = new FileReader();
            lecteur.onload = (e) => {
                if (this.apercuImage) this.apercuImage.src = e.target.result;
                if (this.conteneurApercu) this.conteneurApercu.hidden = false;
                if (this.zoneTelechargement) this.zoneTelechargement.hidden = true;
            };
            lecteur.readAsDataURL(fichier);
        }
    }

    gererSuppressionImage() {
        if (this.entreeImage) this.entreeImage.value = '';
        if (this.conteneurApercu) this.conteneurApercu.hidden = true;
        if (this.zoneTelechargement) this.zoneTelechargement.hidden = false;
    }

    afficherMessage(texte, type = 'succes') {
        if (this.messages) {
            this.messages.innerHTML = `<div class="message message--${type}">${texte}</div>`;
        }
    }

    async gererSoumission(e) {
        e.preventDefault();
        
        // Effacer les messages précédents
        if (this.messages) this.messages.innerHTML = '';

        const donneesFormulaire = new FormData(this.formulaire);

        try {
            if (this.boutonSoumettre) this.boutonSoumettre.disabled = true;

            const reponse = await fetch(this.urlApi, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: donneesFormulaire
            });

            const resultat = await reponse.json();

            if (!reponse.ok) {
                throw new Error(resultat.error || 'Une erreur est survenue lors de l\'ajout du véhicule.');
            }

            this.afficherMessage('Véhicule ajouté avec succès ! Redirection...', 'succes');
            this.formulaire.reset();
            this.gererSuppressionImage();

            // Redirection après un court délai
            setTimeout(() => {
                window.location.href = 'galerie';
            }, 1500);

        } catch (erreur) {
            console.error(erreur);
            this.afficherMessage(erreur.message, 'erreur');
        } finally {
            if (this.boutonSoumettre) this.boutonSoumettre.disabled = false;
        }
    }
}
