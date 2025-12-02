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
        this.entreeImages = document.getElementById('images');
        this.conteneurApercu = document.getElementById('conteneur-apercu');
        this.boutonToutSupprimer = document.getElementById('bouton-tout-supprimer');
        this.zoneTelechargement = document.querySelector('.zone-telechargement');

        // Construction de l'URL API
        this.urlApi = obtenirUrlApi('/vehicule/ajout');

        if (this.formulaire) {
            this.formulaire.addEventListener('submit', (e) => this.gererSoumission(e));
        }

        if (this.entreeImages) {
            this.entreeImages.addEventListener('change', (e) => this.gererChangementImages(e));
        }

        if (this.boutonToutSupprimer) {
            this.boutonToutSupprimer.addEventListener('click', () => this.gererSuppressionImages());
        }
    }

    gererChangementImages(e) {
        const fichiers = Array.from(e.target.files);
        
        if (fichiers.length > 10) {
            alert("Vous ne pouvez sélectionner que 10 images maximum.");
            this.entreeImages.value = ''; // Reset
            return;
        }

        if (fichiers.length > 0) {
            this.conteneurApercu.innerHTML = ''; // Vider les anciens aperçus
            this.conteneurApercu.hidden = false;
            this.boutonToutSupprimer.hidden = false;
            this.zoneTelechargement.hidden = true;

            fichiers.forEach(fichier => {
                const lecteur = new FileReader();
                lecteur.onload = (evt) => {
                    const div = document.createElement('div');
                    div.className = 'vignette-apercu';
                    div.style.position = 'relative';
                    div.style.display = 'inline-block';
                    div.style.margin = '5px';
                    
                    const img = document.createElement('img');
                    img.src = evt.target.result;
                    img.alt = "Aperçu";
                    img.style.width = '100px';
                    img.style.height = '100px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '4px';
                    
                    div.appendChild(img);
                    this.conteneurApercu.appendChild(div);
                };
                lecteur.readAsDataURL(fichier);
            });
        }
    }

    gererSuppressionImages() {
        if (this.entreeImages) this.entreeImages.value = '';
        if (this.conteneurApercu) {
            this.conteneurApercu.innerHTML = '';
            this.conteneurApercu.hidden = true;
        }
        if (this.boutonToutSupprimer) this.boutonToutSupprimer.hidden = true;
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
            this.gererSuppressionImages();

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
