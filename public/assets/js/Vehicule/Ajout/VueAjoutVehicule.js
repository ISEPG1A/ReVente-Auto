import { obtenirUrlApi } from '../../app.js';

export default class VueAjoutVehicule {
    constructor() {
        this.etapeActuelle = 1;
        this.totalEtapes = 4;
        this.initialiser();
    }

    initialiser() {
        this.formulaire = document.getElementById('formulaire-vehicule');
        this.messages = document.querySelector('.messages-formulaire');
        this.boutonSoumettre = document.getElementById('bouton-soumettre');
        
        // Éléments de progression
        this.etapes = document.querySelectorAll('.form-step');
        this.stepperSteps = document.querySelectorAll('.stepper__step');
        
        // Éléments de formulaire
        this.entreeImages = document.getElementById('images');
        this.conteneurApercu = document.getElementById('conteneur-apercu');
        this.boutonToutSupprimer = document.getElementById('bouton-tout-supprimer');
        this.zoneTelechargement = document.querySelector('.ajout-upload__zone');

        // Sélecteurs de type de véhicule
        this.radiosTypeVehicule = document.querySelectorAll('input[name="type_vehicule"]');

        // Éléments de récapitulatif
        this.summaryType = document.getElementById('summary-type');
        this.summaryVehicle = document.getElementById('summary-vehicle');
        this.summaryPrice = document.getElementById('summary-price');
        this.summaryPhotos = document.getElementById('summary-photos');

        // Modal
        this.modalSucces = document.getElementById('modal-succes');

        // Construction de l'URL API
        this.urlApi = obtenirUrlApi('/vehicule/ajout');

        this.attacherEvenements();
        this.gererAffichageConditionnelChamps();
    }

    attacherEvenements() {
        // Navigation entre les étapes
        document.querySelectorAll('.btn-step--next').forEach(btn => {
            btn.addEventListener('click', () => {
                const nextStep = parseInt(btn.dataset.next);
                if (this.validerEtapeActuelle()) {
                    this.allerAEtape(nextStep);
                }
            });
        });

        document.querySelectorAll('.btn-step--prev').forEach(btn => {
            btn.addEventListener('click', () => {
                const prevStep = parseInt(btn.dataset.prev);
                this.allerAEtape(prevStep);
            });
        });

        // Soumission du formulaire
        if (this.formulaire) {
            this.formulaire.addEventListener('submit', (e) => this.gererSoumission(e));
        }

        // Gestion des images
        if (this.entreeImages) {
            this.entreeImages.addEventListener('change', (e) => this.gererChangementImages(e));
        }

        if (this.boutonToutSupprimer) {
            this.boutonToutSupprimer.addEventListener('click', () => this.gererSuppressionImages());
        }

        // Type de véhicule
        this.radiosTypeVehicule.forEach(radio => {
            radio.addEventListener('change', () => {
                this.gererAffichageConditionnelChamps();
                this.mettreAJourRecapitulatif();
            });
        });

        // Drag and drop
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

        // Mise à jour du récapitulatif en temps réel
        const champsRecap = ['marque', 'modele', 'prix', 'annee'];
        champsRecap.forEach(id => {
            const champ = document.getElementById(id);
            if (champ) {
                champ.addEventListener('input', () => this.mettreAJourRecapitulatif());
            }
        });
    }

    /**
     * Valide l'étape actuelle avant de passer à la suivante
     */
    validerEtapeActuelle() {
        const etapeElement = document.querySelector(`.form-step[data-step="${this.etapeActuelle}"]`);
        if (!etapeElement) return true;

        // Récupérer tous les champs requis dans l'étape actuelle
        const champsRequis = etapeElement.querySelectorAll('[required]:not([disabled])');
        let valide = true;

        champsRequis.forEach(champ => {
            // Retirer les styles d'erreur précédents
            champ.classList.remove('input-error');
            
            if (!champ.value || champ.value.trim() === '') {
                champ.classList.add('input-error');
                valide = false;
            }
        });

        if (!valide) {
            this.afficherMessage('Veuillez remplir tous les champs obligatoires (*)', 'erreur');
            // Scroll vers le premier champ en erreur
            const premierChampErreur = etapeElement.querySelector('.input-error');
            if (premierChampErreur) {
                premierChampErreur.scrollIntoView({ behavior: 'smooth', block: 'center' });
                premierChampErreur.focus();
            }
        }

        return valide;
    }

    /**
     * Navigue vers une étape spécifique
     */
    allerAEtape(numeroEtape) {
        if (numeroEtape < 1 || numeroEtape > this.totalEtapes) return;

        // Masquer l'étape actuelle
        this.etapes.forEach(etape => {
            etape.classList.remove('form-step--active');
            if (parseInt(etape.dataset.step) === numeroEtape) {
                etape.classList.add('form-step--active');
            }
        });

        // Mettre à jour le stepper
        this.stepperSteps.forEach((step, index) => {
            const stepNum = index + 1;
            step.classList.remove('stepper__step--active', 'stepper__step--completed');
            
            if (stepNum < numeroEtape) {
                step.classList.add('stepper__step--completed');
            } else if (stepNum === numeroEtape) {
                step.classList.add('stepper__step--active');
            }
        });

        this.etapeActuelle = numeroEtape;

        // Scroll vers le haut
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Effacer les messages d'erreur
        if (this.messages) this.messages.innerHTML = '';

        // Mettre à jour le récapitulatif si on est à l'étape 4
        if (numeroEtape === 4) {
            this.mettreAJourRecapitulatif();
        }
    }

    /**
     * Gère l'affichage conditionnel des champs selon le type de véhicule
     */
    gererAffichageConditionnelChamps() {
        const typeSelectionne = document.querySelector('input[name="type_vehicule"]:checked')?.value || 'voiture';
        
        const elementsHideFor = document.querySelectorAll('[data-hide-for]');
        const elementsShowFor = document.querySelectorAll('[data-show-for]');
        
        elementsHideFor.forEach(element => {
            const hideForTypes = element.dataset.hideFor.split(',').map(t => t.trim());
            if (hideForTypes.includes(typeSelectionne)) {
                element.style.display = 'none';
                const inputs = element.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.disabled = true;
                    if (input.hasAttribute('required')) {
                        input.dataset.wasRequired = 'true';
                        input.removeAttribute('required');
                    }
                });
            } else {
                element.style.display = '';
                const inputs = element.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.disabled = false;
                    if (input.dataset.wasRequired === 'true') {
                        input.setAttribute('required', '');
                    }
                });
            }
        });
        
        elementsShowFor.forEach(element => {
            const showForTypes = element.dataset.showFor.split(',').map(t => t.trim());
            if (showForTypes.includes(typeSelectionne)) {
                element.style.display = '';
                const inputs = element.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.disabled = false;
                    if (input.dataset.wasRequired === 'true') {
                        input.setAttribute('required', '');
                    }
                });
            } else {
                element.style.display = 'none';
                const inputs = element.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.disabled = true;
                    if (input.hasAttribute('required')) {
                        input.dataset.wasRequired = 'true';
                        input.removeAttribute('required');
                    }
                });
            }
        });
    }

    /**
     * Met à jour le récapitulatif à l'étape 4
     */
    mettreAJourRecapitulatif() {
        // Type de véhicule
        const type = document.querySelector('input[name="type_vehicule"]:checked');
        if (type && this.summaryType) {
            const typeLabels = {
                'voiture': '🚗 Voiture',
                'moto': '🏍️ Moto',
                'camion': '🚚 Camion'
            };
            this.summaryType.textContent = typeLabels[type.value] || type.value;
        }

        // Véhicule
        const marque = document.getElementById('marque')?.value || '';
        const modele = document.getElementById('modele')?.value || '';
        const annee = document.getElementById('annee')?.value || '';
        if (this.summaryVehicle) {
            this.summaryVehicle.textContent = marque && modele ? `${marque} ${modele} ${annee ? `(${annee})` : ''}` : '--';
        }

        // Prix
        const prix = document.getElementById('prix')?.value;
        if (this.summaryPrice && prix) {
            this.summaryPrice.textContent = `${this.formaterNombre(prix)} €`;
        } else if (this.summaryPrice) {
            this.summaryPrice.textContent = '--';
        }

        // Photos
        const nbPhotos = this.conteneurApercu?.querySelectorAll('.image-wrapper').length || 0;
        if (this.summaryPhotos) {
            this.summaryPhotos.textContent = `${nbPhotos} photo(s)`;
        }
    }

    /**
     * Gère le changement de fichiers images
     */
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
                    
                    const coverBadge = document.createElement('span');
                    coverBadge.className = 'cover-badge';
                    coverBadge.innerHTML = '<i class="fas fa-star"></i> Couverture';
                    
                    const selectCover = document.createElement('span');
                    selectCover.className = 'select-cover';
                    selectCover.textContent = 'Définir couverture';
                    
                    wrapper.onclick = () => this.definirCouverture(wrapper);
                    
                    wrapper.appendChild(img);
                    wrapper.appendChild(btnSupprimer);
                    wrapper.appendChild(coverBadge);
                    wrapper.appendChild(selectCover);
                    this.conteneurApercu.appendChild(wrapper);

                    if (index === 0) {
                        this.definirCouverture(wrapper);
                    }
                };
                lecteur.readAsDataURL(fichier);
            });

            this.mettreAJourRecapitulatif();
        }
    }

    definirCouverture(wrapper) {
        const allWrappers = this.conteneurApercu.querySelectorAll('.image-wrapper');
        allWrappers.forEach(w => w.classList.remove('cover'));
        wrapper.classList.add('cover');
    }

    supprimerImage(wrapper) {
        const wasCover = wrapper.classList.contains('cover');
        wrapper.remove();
        const remaining = this.conteneurApercu.querySelectorAll('.image-wrapper');
        
        if (remaining.length === 0) {
            this.gererSuppressionImages();
        } else if (wasCover) {
            this.definirCouverture(remaining[0]);
        }
        this.mettreAJourRecapitulatif();
    }

    gererSuppressionImages() {
        if (this.entreeImages) this.entreeImages.value = '';
        if (this.conteneurApercu) {
            this.conteneurApercu.innerHTML = '';
            this.conteneurApercu.hidden = true;
        }
        if (this.boutonToutSupprimer) this.boutonToutSupprimer.hidden = true;
        if (this.zoneTelechargement) this.zoneTelechargement.style.display = 'flex';
        this.mettreAJourRecapitulatif();
    }

    afficherMessage(texte, type = 'succes') {
        if (this.messages) {
            const iconMap = {
                succes: 'check-circle',
                erreur: 'exclamation-circle',
                info: 'info-circle'
            };
            const icon = iconMap[type] || 'info-circle';
            const cssClass = type === 'erreur' ? 'msg--err' : 'msg--ok';
            this.messages.innerHTML = `
                <div class="msg ${cssClass}">
                    <i class="fas fa-${icon}"></i>
                    <span>${texte}</span>
                </div>
            `;
        }
    }

    formaterNombre(n) {
        return new Intl.NumberFormat('fr-FR').format(n);
    }

    async gererSoumission(e) {
        e.preventDefault();
        
        // Vérifier qu'on est bien à l'étape 4
        if (this.etapeActuelle !== 4) {
            return;
        }

        if (this.messages) this.messages.innerHTML = '';

        const donneesFormulaire = new FormData(this.formulaire);

        try {
            this.boutonSoumettre.disabled = true;
            this.boutonSoumettre.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publication...';

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

            // Afficher le modal de succès
            if (this.modalSucces) {
                this.modalSucces.classList.add('active');
                
                // Redirection automatique après 5 secondes
                setTimeout(() => {
                    window.location.href = 'galerie';
                }, 5000);
            } else {
                this.afficherMessage('Véhicule ajouté avec succès ! Redirection...', 'succes');
                setTimeout(() => {
                    window.location.href = 'galerie';
                }, 1500);
            }

        } catch (erreur) {
            console.error(erreur);
            this.afficherMessage(erreur.message, 'erreur');
            this.boutonSoumettre.disabled = false;
            this.boutonSoumettre.innerHTML = '<i class="fas fa-rocket"></i> Publier l\'annonce';
        }
    }
}
