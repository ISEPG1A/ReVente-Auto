import { obtenirUrlApi, formaterMonnaie } from '../../app.js';

export default class VueModificationVehicule {
    constructor() {
        this.idVehicule = new URLSearchParams(window.location.search).get('id');
        this.urlApiModification = obtenirUrlApi('/vehicule/modification');
        this.urlApiDetails = obtenirUrlApi('/vehicule/details');
        this.imagesExistantes = [];
        this.nouvellesImages = []; // { file: File, dataUrl: string }
        this.couvertureType = 'existante'; // 'existante' ou 'nouvelle'
        this.couvertureIndex = 0; // Index de l'image de couverture
        
        this.initialiser();
    }

    async initialiser() {
        // Éléments de la page
        this.elChargement = document.getElementById('chargement-modification');
        this.elErreur = document.getElementById('erreur-modification');
        this.elContenu = document.getElementById('contenu-modification');
        
        // Formulaire et champs
        this.formulaire = document.getElementById('formulaire-modification');
        this.champId = document.getElementById('vehicule-id');
        this.champMarque = document.getElementById('marque');
        this.champModele = document.getElementById('modele');
        this.champAnnee = document.getElementById('annee');
        this.champPrix = document.getElementById('prix');
        this.champKm = document.getElementById('km');
        this.champVille = document.getElementById('ville');
        this.champCarburant = document.getElementById('carburant');
        this.champBoite = document.getElementById('boite');
        this.champDescription = document.getElementById('description');
        
        // Zones d'images
        this.zoneImagesExistantes = document.getElementById('images-existantes');
        this.entreeImages = document.getElementById('images');
        this.conteneurApercuNouvelles = document.getElementById('conteneur-apercu-nouvelles');
        
        // Prévisualisation
        this.previewImage = document.getElementById('preview-image');
        this.previewTitle = document.getElementById('preview-title');
        this.previewYear = document.getElementById('preview-year');
        this.previewKm = document.getElementById('preview-km');
        this.previewFuel = document.getElementById('preview-fuel');
        this.previewLocation = document.getElementById('preview-location');
        this.previewPrice = document.getElementById('preview-price');
        
        // Boutons
        this.boutonModifier = document.getElementById('bouton-modifier');
        this.boutonModifierMobile = document.getElementById('bouton-modifier-mobile');
        this.boutonSupprimer = document.getElementById('bouton-supprimer');
        this.lienVoirAnnonce = document.getElementById('lien-voir-annonce');
        this.lienAnnuler = document.getElementById('lien-annuler');
        
        this.messages = document.querySelector('.messages-formulaire');

        // Vérifier qu'on a un ID
        if (!this.idVehicule) {
            this.afficherErreur("Aucun véhicule spécifié.");
            return;
        }

        // Mettre à jour les liens
        if (this.lienVoirAnnonce) {
            this.lienVoirAnnonce.href = `vehicule?id=${this.idVehicule}`;
        }
        if (this.lienAnnuler) {
            this.lienAnnuler.href = `vehicule?id=${this.idVehicule}`;
        }

        // Charger les données du véhicule
        await this.chargerDonnees();
        
        // Attacher les événements
        this.attacherEvenements();
    }

    async chargerDonnees() {
        try {
            const res = await fetch(`${this.urlApiModification}?id=${this.idVehicule}`);
            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.error || 'Erreur lors du chargement');
            }

            this.preRemplirFormulaire(data);
            this.afficherContenu();
            
        } catch (err) {
            this.afficherErreur(err.message);
        }
    }

    preRemplirFormulaire(vehicule) {
        // ID
        if (this.champId) this.champId.value = vehicule.id;
        
        // Champs texte
        if (this.champMarque) this.champMarque.value = vehicule.marque || '';
        if (this.champModele) this.champModele.value = vehicule.modele || '';
        if (this.champAnnee) this.champAnnee.value = vehicule.annee || '';
        if (this.champPrix) this.champPrix.value = vehicule.prix || '';
        if (this.champKm) this.champKm.value = vehicule.km || '';
        if (this.champVille) this.champVille.value = vehicule.ville || '';
        if (this.champDescription) this.champDescription.value = vehicule.description || '';
        
        // Selects
        if (this.champCarburant && vehicule.carburant) {
            this.champCarburant.value = vehicule.carburant;
        }
        if (this.champBoite && vehicule.boite) {
            this.champBoite.value = vehicule.boite;
        }

        // Images existantes
        this.imagesExistantes = vehicule.images || [];
        if (vehicule.image_path && !this.imagesExistantes.includes(vehicule.image_path)) {
            this.imagesExistantes.unshift(vehicule.image_path);
        }
        this.afficherImagesExistantes();

        // Mettre à jour la prévisualisation
        this.mettreAJourPreview();
    }

    afficherImagesExistantes() {
        if (!this.zoneImagesExistantes) return;
        
        this.zoneImagesExistantes.innerHTML = '';
        
        this.imagesExistantes.forEach((src, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'image-wrapper image-wrapper--existante';
            wrapper.dataset.src = src;
            wrapper.dataset.index = index;
            
            const img = document.createElement('img');
            img.src = src;
            img.alt = `Photo ${index + 1}`;
            
            const btnSupprimer = document.createElement('button');
            btnSupprimer.type = 'button';
            btnSupprimer.className = 'btn-supprimer';
            btnSupprimer.innerHTML = '<i class="fas fa-times"></i>';
            btnSupprimer.title = 'Supprimer cette photo';
            btnSupprimer.onclick = (e) => {
                e.stopPropagation();
                this.supprimerImageExistante(src, index);
            };
            
            // Badge pour image de couverture
            const coverBadge = document.createElement('span');
            coverBadge.className = 'cover-badge';
            coverBadge.innerHTML = '<i class="fas fa-star"></i> Couverture';
            wrapper.appendChild(coverBadge);
            
            // Indicateur "Définir comme couverture" au survol
            const selectCover = document.createElement('span');
            selectCover.className = 'select-cover';
            selectCover.innerHTML = '<i class="fas fa-star"></i> Définir couverture';
            wrapper.appendChild(selectCover);
            
            // Vérifier si c'est la couverture
            if (this.couvertureType === 'existante' && this.couvertureIndex === index) {
                wrapper.classList.add('is-cover');
            }
            
            // Clic pour définir comme couverture
            wrapper.onclick = () => {
                this.definirCouvertureExistante(index);
            };
            
            wrapper.appendChild(img);
            wrapper.appendChild(btnSupprimer);
            this.zoneImagesExistantes.appendChild(wrapper);
        });

        // Mettre à jour la prévisualisation de l'image
        this.mettreAJourPreviewImage();
    }

    definirCouvertureExistante(index) {
        this.couvertureType = 'existante';
        this.couvertureIndex = index;
        
        // Réafficher les deux zones d'images
        this.afficherImagesExistantes();
        this.afficherNouvellesImages();
    }

    supprimerImageExistante(src, index) {
        // Si c'était la couverture, passer à la suivante
        if (this.couvertureType === 'existante' && this.couvertureIndex === index) {
            if (this.imagesExistantes.length > 1) {
                this.couvertureIndex = 0;
            } else if (this.nouvellesImages.length > 0) {
                this.couvertureType = 'nouvelle';
                this.couvertureIndex = 0;
            }
        } else if (this.couvertureType === 'existante' && this.couvertureIndex > index) {
            this.couvertureIndex--;
        }
        
        this.imagesExistantes = this.imagesExistantes.filter(img => img !== src);
        this.afficherImagesExistantes();
        if (this.nouvellesImages.length > 0) {
            this.afficherNouvellesImages();
        }

        this.mettreAJourPreviewImage();
    }

    attacherEvenements() {
        // Soumission du formulaire
        if (this.formulaire) {
            this.formulaire.addEventListener('submit', (e) => this.gererSoumission(e));
        }

        // Ajout de nouvelles images
        if (this.entreeImages) {
            this.entreeImages.addEventListener('change', (e) => this.gererNouvellesImages(e));
        }

        // Suppression de l'annonce
        if (this.boutonSupprimer) {
            this.boutonSupprimer.addEventListener('click', () => this.gererSuppression());
        }

        // Prévisualisation en temps réel
        const champsPreview = [
            { champ: this.champMarque, handler: () => this.mettreAJourPreview() },
            { champ: this.champModele, handler: () => this.mettreAJourPreview() },
            { champ: this.champAnnee, handler: () => this.mettreAJourPreview() },
            { champ: this.champPrix, handler: () => this.mettreAJourPreview() },
            { champ: this.champKm, handler: () => this.mettreAJourPreview() },
            { champ: this.champVille, handler: () => this.mettreAJourPreview() },
            { champ: this.champCarburant, handler: () => this.mettreAJourPreview() }
        ];

        champsPreview.forEach(({ champ, handler }) => {
            if (champ) {
                champ.addEventListener('input', handler);
                champ.addEventListener('change', handler);
            }
        });
    }

    gererNouvellesImages(e) {
        const fichiers = Array.from(e.target.files);
        const totalImages = this.imagesExistantes.length + this.nouvellesImages.length + fichiers.length;
        
        if (totalImages > 10) {
            this.afficherMessage(`Vous ne pouvez avoir que 10 images maximum. Actuellement: ${this.imagesExistantes.length + this.nouvellesImages.length} images.`, 'erreur');
            this.entreeImages.value = '';
            return;
        }

        if (fichiers.length > 0) {
            // Ajouter les nouveaux fichiers à la liste
            fichiers.forEach((fichier) => {
                const lecteur = new FileReader();
                lecteur.onload = (evt) => {
                    this.nouvellesImages.push({
                        file: fichier,
                        dataUrl: evt.target.result
                    });
                    this.afficherNouvellesImages();
                };
                lecteur.readAsDataURL(fichier);
            });
        }
    }

    afficherNouvellesImages() {
        if (!this.conteneurApercuNouvelles) return;
        
        this.conteneurApercuNouvelles.innerHTML = '';
        this.conteneurApercuNouvelles.hidden = this.nouvellesImages.length === 0;

        this.nouvellesImages.forEach((imgData, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'image-wrapper image-wrapper--nouvelle';
            wrapper.dataset.index = index;
            
            const img = document.createElement('img');
            img.src = imgData.dataUrl;
            img.alt = `Nouvelle photo ${index + 1}`;
            
            // Badge nouvelle ou couverture
            const badge = document.createElement('span');
            if (this.couvertureType === 'nouvelle' && this.couvertureIndex === index) {
                badge.className = 'cover-badge';
                badge.innerHTML = '<i class="fas fa-star"></i> Couverture';
                wrapper.classList.add('is-cover');
            } else {
                badge.className = 'new-badge';
                badge.innerHTML = '<i class="fas fa-plus"></i> Nouvelle';
            }
            
            // Indicateur "Définir comme couverture" au survol
            const selectCover = document.createElement('span');
            selectCover.className = 'select-cover';
            selectCover.innerHTML = '<i class="fas fa-star"></i> Définir couverture';
            wrapper.appendChild(selectCover);
            
            const btnSupprimer = document.createElement('button');
            btnSupprimer.type = 'button';
            btnSupprimer.className = 'btn-supprimer';
            btnSupprimer.innerHTML = '<i class="fas fa-times"></i>';
            btnSupprimer.title = 'Supprimer cette photo';
            btnSupprimer.onclick = (e) => {
                e.stopPropagation();
                this.supprimerNouvelleImage(index);
            };
            
            // Clic pour définir comme couverture
            wrapper.onclick = () => {
                this.definirCouvertureNouvelle(index);
            };
            
            wrapper.appendChild(img);
            wrapper.appendChild(badge);
            wrapper.appendChild(btnSupprimer);
            this.conteneurApercuNouvelles.appendChild(wrapper);
        });

        this.mettreAJourPreviewImage();
    }

    supprimerNouvelleImage(index) {
        // Si c'était la couverture, revenir à la première image existante
        if (this.couvertureType === 'nouvelle' && this.couvertureIndex === index) {
            this.couvertureType = 'existante';
            this.couvertureIndex = 0;
            this.afficherImagesExistantes();
        } else if (this.couvertureType === 'nouvelle' && this.couvertureIndex > index) {
            // Ajuster l'index si on supprime une image avant la couverture
            this.couvertureIndex--;
        }
        
        this.nouvellesImages.splice(index, 1);
        this.afficherNouvellesImages();
        
        // Reconstruire l'input file avec les fichiers restants
        this.reconstruireInputFile();
    }

    reconstruireInputFile() {
        // Créer un nouveau DataTransfer pour mettre à jour l'input file
        const dt = new DataTransfer();
        this.nouvellesImages.forEach(imgData => {
            dt.items.add(imgData.file);
        });
        this.entreeImages.files = dt.files;
    }

    definirCouvertureNouvelle(index) {
        this.couvertureType = 'nouvelle';
        this.couvertureIndex = index;
        
        // Réafficher les deux zones d'images
        this.afficherImagesExistantes();
        this.afficherNouvellesImages();
    }

    mettreAJourPreview() {
        // Titre
        if (this.previewTitle) {
            const marque = this.champMarque?.value || 'Marque';
            const modele = this.champModele?.value || 'Modèle';
            this.previewTitle.textContent = `${marque} ${modele}`;
        }

        // Année
        if (this.previewYear) {
            const annee = this.champAnnee?.value || '----';
            this.previewYear.innerHTML = `<i class="fas fa-calendar"></i> ${annee}`;
        }

        // Kilométrage
        if (this.previewKm) {
            const km = this.champKm?.value ? this.formaterNombre(this.champKm.value) : '--';
            this.previewKm.innerHTML = `<i class="fas fa-road"></i> ${km} km`;
        }

        // Carburant
        if (this.previewFuel) {
            const carburant = this.champCarburant?.value || '--';
            this.previewFuel.innerHTML = `<i class="fas fa-gas-pump"></i> ${carburant}`;
        }

        // Ville
        if (this.previewLocation) {
            const ville = this.champVille?.value || '--';
            this.previewLocation.innerHTML = `<i class="fas fa-map-marker-alt"></i> ${ville}`;
        }

        // Prix
        if (this.previewPrice) {
            const prix = this.champPrix?.value ? this.formaterNombre(this.champPrix.value) : '--';
            this.previewPrice.textContent = `${prix} €`;
        }
    }

    mettreAJourPreviewImage() {
        if (!this.previewImage) return;

        let imgSrc = null;
        
        // Déterminer l'image de couverture
        if (this.couvertureType === 'existante' && this.imagesExistantes.length > 0) {
            imgSrc = this.imagesExistantes[this.couvertureIndex] || this.imagesExistantes[0];
        } else if (this.couvertureType === 'nouvelle' && this.nouvellesImages.length > 0) {
            imgSrc = this.nouvellesImages[this.couvertureIndex]?.dataUrl || this.nouvellesImages[0]?.dataUrl;
        } else if (this.imagesExistantes.length > 0) {
            imgSrc = this.imagesExistantes[0];
        } else if (this.nouvellesImages.length > 0) {
            imgSrc = this.nouvellesImages[0]?.dataUrl;
        }

        if (imgSrc) {
            this.previewImage.innerHTML = `<img src="${imgSrc}" alt="Aperçu" style="width:100%; height:100%; object-fit:cover; border-radius:8px;">`;
        } else {
            this.previewImage.innerHTML = `
                <i class="fas fa-image"></i>
                <span>Aucune photo</span>
            `;
        }
    }

    formaterNombre(n) {
        return new Intl.NumberFormat('fr-FR').format(n);
    }

    async gererSoumission(e) {
        e.preventDefault();

        // Effacer les messages
        if (this.messages) this.messages.innerHTML = '';

        const formData = new FormData(this.formulaire);
        
        // Réorganiser les images pour que la couverture soit en premier
        let imagesOrganisees = [...this.imagesExistantes];
        if (this.couvertureType === 'existante' && this.couvertureIndex > 0) {
            const couverture = imagesOrganisees.splice(this.couvertureIndex, 1)[0];
            imagesOrganisees.unshift(couverture);
        }
        
        // Ajouter les images existantes à conserver (réorganisées)
        formData.append('images_existantes', JSON.stringify(imagesOrganisees));
        
        // Indiquer si une nouvelle image est la couverture
        if (this.couvertureType === 'nouvelle') {
            formData.append('couverture_nouvelle_index', this.couvertureIndex);
        }

        // Désactiver les boutons
        this.setChargement(true);

        try {
            const res = await fetch(this.urlApiModification, {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.error || 'Erreur lors de la modification');
            }

            this.afficherMessage('Annonce modifiée avec succès ! Redirection...', 'succes');
            
            // Redirection vers la page de détails
            setTimeout(() => {
                window.location.href = `vehicule?id=${this.idVehicule}`;
            }, 1500);

        } catch (err) {
            this.afficherMessage(err.message, 'erreur');
        } finally {
            this.setChargement(false);
        }
    }

    async gererSuppression() {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette annonce ? Cette action est irréversible.')) {
            return;
        }

        try {
            const res = await fetch(`${this.urlApiDetails}?id=${this.idVehicule}`, {
                method: 'DELETE'
            });

            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.error || 'Erreur lors de la suppression');
            }

            alert('Annonce supprimée avec succès.');
            window.location.href = 'galerie';

        } catch (err) {
            alert(err.message);
        }
    }

    setChargement(enChargement) {
        if (this.boutonModifier) {
            this.boutonModifier.disabled = enChargement;
            this.boutonModifier.innerHTML = enChargement
                ? '<i class="fas fa-spinner fa-spin"></i> <span>Enregistrement...</span>'
                : '<i class="fas fa-save"></i> <span>Enregistrer les modifications</span>';
        }
        if (this.boutonModifierMobile) {
            this.boutonModifierMobile.disabled = enChargement;
            this.boutonModifierMobile.innerHTML = enChargement
                ? '<i class="fas fa-spinner fa-spin"></i> <span>Enregistrement...</span>'
                : '<i class="fas fa-save"></i> <span>Enregistrer les modifications</span>';
        }
    }

    afficherMessage(texte, type = 'succes') {
        if (this.messages) {
            this.messages.innerHTML = `<div class="message message--${type}">${texte}</div>`;
        }
    }

    afficherErreur(msg, type = 'generic') {
        if (this.elChargement) this.elChargement.hidden = true;
        if (this.elErreur) {
            const iconEl = this.elErreur.querySelector('.modification-erreur-icon i');
            const titreEl = this.elErreur.querySelector('.modification-erreur-titre');
            const messageEl = this.elErreur.querySelector('.modification-erreur-message');
            const btnConnexion = this.elErreur.querySelector('.modification-erreur-btn--primary');
            
            // Adapter selon le type d'erreur
            if (msg.includes('Authentification') || msg.includes('connecté')) {
                if (iconEl) iconEl.className = 'fas fa-lock';
                if (titreEl) titreEl.textContent = 'Connexion requise';
                if (messageEl) messageEl.textContent = 'Vous devez être connecté pour modifier une annonce.';
                if (btnConnexion) btnConnexion.style.display = 'inline-flex';
            } else if (msg.includes('autorisé') || msg.includes('403')) {
                if (iconEl) iconEl.className = 'fas fa-ban';
                if (titreEl) titreEl.textContent = 'Accès refusé';
                if (messageEl) messageEl.textContent = 'Vous n\'êtes pas autorisé à modifier cette annonce.';
                if (btnConnexion) btnConnexion.style.display = 'none';
            } else if (msg.includes('introuvable') || msg.includes('404')) {
                if (iconEl) iconEl.className = 'fas fa-search';
                if (titreEl) titreEl.textContent = 'Annonce introuvable';
                if (messageEl) messageEl.textContent = 'Cette annonce n\'existe pas ou a été supprimée.';
                if (btnConnexion) btnConnexion.style.display = 'none';
            } else {
                if (iconEl) iconEl.className = 'fas fa-exclamation-triangle';
                if (titreEl) titreEl.textContent = 'Erreur';
                if (messageEl) messageEl.textContent = msg;
                if (btnConnexion) btnConnexion.style.display = 'none';
            }
            
            this.elErreur.hidden = false;
        }
    }

    afficherContenu() {
        if (this.elChargement) {
            this.elChargement.hidden = true;
            this.elChargement.style.display = 'none';
        }
        if (this.elErreur) {
            this.elErreur.hidden = true;
            this.elErreur.style.display = 'none';
        }
        if (this.elContenu) {
            this.elContenu.hidden = false;
            this.elContenu.style.display = 'block';
        }
    }
}
