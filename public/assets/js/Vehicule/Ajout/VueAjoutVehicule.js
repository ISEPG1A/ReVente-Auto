import { obtenirUrlApi } from '../../app.js';

export default class VueAjoutVehicule {
    constructor() {
        this.initialiser();
    }

    initialiser() {
        this.formulaire = document.getElementById('formulaire-vehicule');
        this.messages = document.querySelector('.messages-formulaire');
        this.boutonSoumettre = document.getElementById('bouton-soumettre');
        this.boutonSoumettreMobile = document.getElementById('bouton-soumettre-mobile');
        
        // Éléments de prévisualisation d'image
        this.entreeImages = document.getElementById('images');
        this.conteneurApercu = document.getElementById('conteneur-apercu');
        this.boutonToutSupprimer = document.getElementById('bouton-tout-supprimer');
        this.zoneTelechargement = document.querySelector('.ajout-upload__zone');

        // Éléments de prévisualisation en temps réel
        this.previewImage = document.getElementById('preview-image');
        this.previewTitle = document.getElementById('preview-title');
        this.previewYear = document.getElementById('preview-year');
        this.previewKm = document.getElementById('preview-km');
        this.previewFuel = document.getElementById('preview-fuel');
        this.previewLocation = document.getElementById('preview-location');
        this.previewPrice = document.getElementById('preview-price');

        // Champs du formulaire
        this.champMarque = document.getElementById('marque');
        this.champModele = document.getElementById('modele');
        this.champAnnee = document.getElementById('annee');
        this.champPrix = document.getElementById('prix');
        this.champKm = document.getElementById('km');
        this.champVille = document.getElementById('ville');
        this.champCarburant = document.getElementById('carburant');

        // Construction de l'URL API
        this.urlApi = obtenirUrlApi('/vehicule/ajout');

        this.attacherEvenements();
    }

    attacherEvenements() {
        if (this.formulaire) {
            this.formulaire.addEventListener('submit', (e) => this.gererSoumission(e));
        }

        if (this.entreeImages) {
            this.entreeImages.addEventListener('change', (e) => this.gererChangementImages(e));
        }

        if (this.boutonToutSupprimer) {
            this.boutonToutSupprimer.addEventListener('click', () => this.gererSuppressionImages());
        }

        // Drag and drop sur la zone de téléchargement
        if (this.zoneTelechargement) {
            this.zoneTelechargement.addEventListener('dragover', (e) => {
                e.preventDefault();
                this.zoneTelechargement.classList.add('dragging');
            });
            
            this.zoneTelechargement.addEventListener('dragleave', () => {
                this.zoneTelechargement.classList.remove('dragging');
            });
            
            this.zoneTelechargement.addEventListener('drop', (e) => {
                e.preventDefault();
                this.zoneTelechargement.classList.remove('dragging');
                if (e.dataTransfer.files.length > 0) {
                    this.entreeImages.files = e.dataTransfer.files;
                    this.gererChangementImages({ target: { files: e.dataTransfer.files } });
                }
            });
        }

        // Prévisualisation en temps réel
        const champsPreview = [
            { champ: this.champMarque, handler: () => this.mettreAJourTitre() },
            { champ: this.champModele, handler: () => this.mettreAJourTitre() },
            { champ: this.champAnnee, handler: () => this.mettreAJourAnnee() },
            { champ: this.champPrix, handler: () => this.mettreAJourPrix() },
            { champ: this.champKm, handler: () => this.mettreAJourKm() },
            { champ: this.champVille, handler: () => this.mettreAJourVille() },
            { champ: this.champCarburant, handler: () => this.mettreAJourCarburant() }
        ];

        champsPreview.forEach(({ champ, handler }) => {
            if (champ) {
                champ.addEventListener('input', handler);
                champ.addEventListener('change', handler);
            }
        });
    }

    // Méthodes de mise à jour de la prévisualisation
    mettreAJourTitre() {
        if (this.previewTitle) {
            const marque = this.champMarque?.value || 'Marque';
            const modele = this.champModele?.value || 'Modèle';
            this.previewTitle.textContent = `${marque} ${modele}`;
        }
    }

    mettreAJourAnnee() {
        if (this.previewYear) {
            const annee = this.champAnnee?.value || '----';
            this.previewYear.innerHTML = `<i class="fas fa-calendar"></i> ${annee}`;
        }
    }

    mettreAJourKm() {
        if (this.previewKm) {
            const km = this.champKm?.value ? this.formaterNombre(this.champKm.value) : '--';
            this.previewKm.innerHTML = `<i class="fas fa-road"></i> ${km} km`;
        }
    }

    mettreAJourCarburant() {
        if (this.previewFuel) {
            const carburant = this.champCarburant?.value || '--';
            this.previewFuel.innerHTML = `<i class="fas fa-gas-pump"></i> ${carburant}`;
        }
    }

    mettreAJourVille() {
        if (this.previewLocation) {
            const ville = this.champVille?.value || '--';
            this.previewLocation.innerHTML = `<i class="fas fa-map-marker-alt"></i> ${ville}`;
        }
    }

    mettreAJourPrix() {
        if (this.previewPrice) {
            const prix = this.champPrix?.value ? this.formaterNombre(this.champPrix.value) : '--';
            this.previewPrice.textContent = `${prix} €`;
        }
    }

    formaterNombre(n) {
        return new Intl.NumberFormat('fr-FR').format(n);
    }

    gererChangementImages(e) {
        const fichiers = Array.from(e.target.files);
        
        if (fichiers.length > 10) {
            this.afficherMessage("Vous ne pouvez sélectionner que 10 images maximum.", 'erreur');
            this.entreeImages.value = '';
            return;
        }

        if (fichiers.length > 0) {
            this.conteneurApercu.innerHTML = '';
            this.conteneurApercu.hidden = false;
            this.boutonToutSupprimer.hidden = false;
            if (this.zoneTelechargement) this.zoneTelechargement.style.display = 'none';

            fichiers.forEach((fichier, index) => {
                const lecteur = new FileReader();
                lecteur.onload = (evt) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'image-wrapper';
                    wrapper.dataset.index = index;
                    
                    const img = document.createElement('img');
                    img.src = evt.target.result;
                    img.alt = `Photo ${index + 1}`;
                    
                    const btnSupprimer = document.createElement('button');
                    btnSupprimer.type = 'button';
                    btnSupprimer.className = 'btn-supprimer';
                    btnSupprimer.innerHTML = '<i class="fas fa-times"></i>';
                    btnSupprimer.onclick = (e) => {
                        e.stopPropagation();
                        this.supprimerImage(wrapper);
                    };
                    
                    // Badge pour image de couverture
                    const coverBadge = document.createElement('span');
                    coverBadge.className = 'cover-badge';
                    coverBadge.innerHTML = '<i class="fas fa-star"></i> Couverture';
                    
                    // Texte pour sélectionner comme couverture
                    const selectCover = document.createElement('span');
                    selectCover.className = 'select-cover';
                    selectCover.textContent = 'Définir couverture';
                    
                    // Clic pour définir comme couverture
                    wrapper.onclick = () => this.definirCouverture(wrapper);
                    
                    wrapper.appendChild(img);
                    wrapper.appendChild(btnSupprimer);
                    wrapper.appendChild(coverBadge);
                    wrapper.appendChild(selectCover);
                    this.conteneurApercu.appendChild(wrapper);

                    // La première image est la couverture par défaut
                    if (index === 0) {
                        this.definirCouverture(wrapper);
                    }
                };
                lecteur.readAsDataURL(fichier);
            });
        }
    }

    definirCouverture(wrapper) {
        // Retirer la classe cover de toutes les images
        const allWrappers = this.conteneurApercu.querySelectorAll('.image-wrapper');
        allWrappers.forEach(w => w.classList.remove('cover'));
        
        // Ajouter la classe cover à l'image sélectionnée
        wrapper.classList.add('cover');
        
        // Mettre à jour la prévisualisation
        const img = wrapper.querySelector('img');
        if (img && this.previewImage) {
            this.previewImage.style.backgroundImage = `url(${img.src})`;
            this.previewImage.classList.add('has-image');
        }
    }

    supprimerImage(wrapper) {
        const wasCover = wrapper.classList.contains('cover');
        wrapper.remove();
        const remaining = this.conteneurApercu.querySelectorAll('.image-wrapper');
        
        if (remaining.length === 0) {
            this.gererSuppressionImages();
        } else if (wasCover) {
            // Si c'était l'image de couverture, définir la première comme nouvelle couverture
            this.definirCouverture(remaining[0]);
        }
    }

    gererSuppressionImages() {
        if (this.entreeImages) this.entreeImages.value = '';
        if (this.conteneurApercu) {
            this.conteneurApercu.innerHTML = '';
            this.conteneurApercu.hidden = true;
        }
        if (this.boutonToutSupprimer) this.boutonToutSupprimer.hidden = true;
        if (this.zoneTelechargement) this.zoneTelechargement.style.display = 'flex';
        
        // Réinitialiser la prévisualisation
        if (this.previewImage) {
            this.previewImage.style.backgroundImage = '';
            this.previewImage.classList.remove('has-image');
        }
    }

    afficherMessage(texte, type = 'succes') {
        if (this.messages) {
            const iconMap = {
                succes: 'check-circle',
                erreur: 'exclamation-circle',
                info: 'info-circle'
            };
            const icon = iconMap[type] || 'info-circle';
            this.messages.innerHTML = `
                <div class="alerte alerte--${type}">
                    <i class="fas fa-${icon}"></i>
                    <span>${texte}</span>
                </div>
            `;
        }
    }

    desactiverBoutons(etat) {
        if (this.boutonSoumettre) this.boutonSoumettre.disabled = etat;
        if (this.boutonSoumettreMobile) this.boutonSoumettreMobile.disabled = etat;
    }

    async gererSoumission(e) {
        e.preventDefault();
        
        // Effacer les messages précédents
        if (this.messages) this.messages.innerHTML = '';

        const donneesFormulaire = new FormData(this.formulaire);

        try {
            this.desactiverBoutons(true);

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

            // Réinitialiser la prévisualisation
            this.mettreAJourTitre();
            this.mettreAJourAnnee();
            this.mettreAJourKm();
            this.mettreAJourCarburant();
            this.mettreAJourVille();
            this.mettreAJourPrix();

            // Redirection après un court délai
            setTimeout(() => {
                window.location.href = 'galerie';
            }, 1500);

        } catch (erreur) {
            console.error(erreur);
            this.afficherMessage(erreur.message, 'erreur');
        } finally {
            this.desactiverBoutons(false);
        }
    }
}
