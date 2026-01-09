/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE AJOUT VÉHICULE - FORMULAIRE MULTI-ÉTAPES D'AJOUT D'ANNONCE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe gère le formulaire complexe d'ajout de véhicule avec :
 * - Navigation multi-étapes (4 étapes avec stepper visuel)
 * - Validation en temps réel des champs
 * - Gestion des images (upload multiple, drag & drop, aperçu)
 * - Affichage conditionnel selon le type de véhicule
 * - Prévisualisation de l'annonce en temps réel
 * - Modal de confirmation après création
 * 
 * Structure des étapes :
 * 1. Informations générales (type, marque, modèle, prix, année, km)
 * 2. Caractéristiques techniques (motorisation, dimensions, équipements)
 * 3. Photos du véhicule (minimum 3 obligatoires, maximum 10)
 * 4. Récapitulatif et validation finale
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @see     ControleurVehiculeAjout (PHP) Pour le traitement serveur
 * @see     utilitaires-vehicule.js Pour les fonctions partagées
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi } from '../../application.js';
import { 
    filtrerOptionsCritAir, 
    filtrerOptionsNormeEuro, 
    adapterOptionsTailleCoffre, 
    adapterLabelTailleCoffre,
    obtenirReglesValidation,
    validerChampTexte,
    animerErreur 
} from './utilitaires-vehicule.js';

export default class VueAjoutVehicule {
    
    /**
     * Initialise la vue avec les paramètres par défaut
     * 
     * @property {number} etapeActuelle - Étape courante (1-4)
     * @property {number} totalEtapes - Nombre total d'étapes
     * @property {File[]} imagesSelectionnees - Fichiers images sélectionnés
     * @property {number} MAX_IMAGES - Nombre maximum d'images autorisées
     */
    constructor() {
        /** @type {number} Étape actuelle du formulaire (1-4) */
        this.etapeActuelle = 1;
        
        /** @type {number} Nombre total d'étapes dans le formulaire */
        this.totalEtapes = 4;
        
        /** @type {File[]} Tableau des fichiers images sélectionnés */
        this.imagesSelectionnees = [];
        
        /** @type {number} Nombre maximum d'images autorisées par annonce */
        this.MAX_IMAGES = 10;
        
        this.initialiser();
    }

    /**
     * Initialise tous les éléments DOM et configure les événements
     * 
     * Récupère les références aux éléments du formulaire,
     * configure les écouteurs d'événements et initialise
     * l'affichage conditionnel des champs.
     */
    initialiser() {
        // Références aux éléments principaux du formulaire
        this.formulaire = document.getElementById('formulaire-vehicule');
        this.messages = document.querySelector('.messages-formulaire');
        this.boutonSoumettre = document.getElementById('bouton-soumettre');
        
        // Éléments du stepper de progression
        this.etapes = document.querySelectorAll('.form-step');
        this.stepperSteps = document.querySelectorAll('.stepper__step');
        
        // Éléments de gestion des images
        this.entreeImages = document.getElementById('images');
        this.conteneurApercu = document.getElementById('conteneur-apercu');
        this.boutonToutSupprimer = document.getElementById('bouton-tout-supprimer');
        this.zoneTelechargement = document.querySelector('.ajout-upload__zone');
        this.compteurImages = document.getElementById('compteur-images');

        // Sélecteurs de type de véhicule (voiture, utilitaire, moto)
        this.radiosTypeVehicule = document.querySelectorAll('input[name="type_vehicule"]');
        
        // Mémoriser le type de véhicule précédent pour détecter les changements
        this.typeVehiculePrecedent = null;

        // Carte de prévisualisation en temps réel
        this.cartePreview = document.getElementById('carte-preview');

        // Modal de confirmation après création réussie
        this.modalSucces = document.getElementById('modal-succes');

        // Construction de l'URL API pour la soumission
        this.urlApi = obtenirUrlApi('/vehicule/ajout');

        // Configuration des événements et initialisation de l'affichage
        this.attacherEvenements();
        this.gererAffichageConditionnelChamps();
        this.mettreAJourPrevisualisation();
    }

    /**
     * Attache tous les écouteurs d'événements du formulaire
     * 
     * Configure les handlers pour :
     * - Navigation entre étapes (suivant/précédent)
     * - Soumission du formulaire
     * - Gestion des images (sélection, suppression, drag & drop)
     * - Changement de type de véhicule
     * - Mise à jour temps réel de la prévisualisation
     */
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
            this.entreeImages.addEventListener('change', (e) => this.gererAjoutImages(e));
        }

        if (this.boutonToutSupprimer) {
            this.boutonToutSupprimer.addEventListener('click', () => this.gererSuppressionToutesImages());
        }

        // Type de véhicule
        this.radiosTypeVehicule.forEach(radio => {
            radio.addEventListener('change', () => {
                this.gererAffichageConditionnelChamps();
                this.mettreAJourRecapitulatif();
                this.mettreAJourPrevisualisation();
            });
        });

        // Carburant - Pour afficher/masquer les champs de consommation
        const selectCarburant = document.getElementById('carburant');
        if (selectCarburant) {
            selectCarburant.addEventListener('change', () => {
                this.gererAffichageConsommation();
            });
        }

        // Type d'hybride - Pour adapter les labels et unités
        const selectTypeHybride = document.getElementById('type_hybride');
        if (selectTypeHybride) {
            selectTypeHybride.addEventListener('change', () => {
                this.gererTypeHybride();
            });
        }

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
                    this.gererAjoutImages({ target: { files: e.dataTransfer.files } });
                }
            });
        }

        // Mise à jour du récapitulatif et prévisualisation en temps réel
        const champsPrev = ['marque', 'modele', 'prix', 'annee', 'km', 'carburant', 'boite', 'ville', 'longueur', 'largeur', 'hauteur'];
        champsPrev.forEach(id => {
            const champ = document.getElementById(id);
            if (champ) {
                champ.addEventListener('input', () => {
                    this.mettreAJourRecapitulatif();
                    this.mettreAJourPrevisualisation();
                });
                champ.addEventListener('change', () => {
                    this.mettreAJourRecapitulatif();
                    this.mettreAJourPrevisualisation();
                });
            }
        });
        
        // Normalisation automatique des champs texte (1ère lettre majuscule, reste minuscule)
        // EXCEPTION : modele n'est PAS normalisé (ex: GTI, RS6, AMG doivent rester tels quels)
        const champsTexteANormaliser = ['marque', 'ville', 'couleur', 'provenance'];
        champsTexteANormaliser.forEach(id => {
            const champ = document.getElementById(id);
            if (champ) {
                champ.addEventListener('blur', () => {
                    champ.value = this.normaliserTexte(champ.value);
                });
            }
        });
    }
    
    /**
     * Normalise un texte : 1ère lettre de chaque mot en majuscule, reste en minuscule
     * Gère les tirets et apostrophes (ex: Mantes-la-Jolie, L'Haÿ-les-Roses)
     * 
     * @param {string} texte - Le texte à normaliser
     * @returns {string} Le texte normalisé
     */
    normaliserTexte(texte) {
        if (!texte || typeof texte !== 'string') return texte;
        
        return texte
            .trim()
            .toLowerCase()
            .split(/(\s+|-|')/) // Séparer par espaces, tirets ou apostrophes
            .map((mot, index, array) => {
                // Garder les séparateurs tels quels
                if (mot === ' ' || mot === '-' || mot === "'") return mot;
                // Ne pas capitaliser les petits mots après un tiret (la, le, les, sur, sous, etc.)
                const petitsMots = ['la', 'le', 'les', 'du', 'de', 'des', 'sur', 'sous', 'en', 'aux'];
                if (index > 0 && petitsMots.includes(mot)) {
                    return mot;
                }
                // Capitaliser la première lettre
                return mot.charAt(0).toUpperCase() + mot.slice(1);
            })
            .join('');
    }

    /**
     * Valide l'étape actuelle avant de passer à la suivante
     */
    validerEtapeActuelle() {
        const etapeElement = document.querySelector(`.form-step[data-step="${this.etapeActuelle}"]`);
        if (!etapeElement) return true;

        // VALIDATION SPÉCIALE ÉTAPE 3 : Minimum 3 photos obligatoires
        if (this.etapeActuelle === 3) {
            if (this.imagesSelectionnees.length < 3) {
                const nombreManquant = 3 - this.imagesSelectionnees.length;
                const imageTexte = nombreManquant > 1 ? 'images' : 'image';
                const messageErreur = `Il vous manque ${nombreManquant} ${imageTexte} ! Vous devez ajouter au minimum 3 photos de votre véhicule (actuellement : ${this.imagesSelectionnees.length}/3)`;
                this.afficherMessage(messageErreur, 'erreur');
                return false;
            }
        }

        // Récupérer tous les champs (requis ou non) pour validation complète
        const tousLesChamps = etapeElement.querySelectorAll('input, select, textarea');
        let valide = true;
        let premierChampInvalide = null;
        let messageErreurSpecifique = null;

        // Règles de validation strictes (identiques au PHP)
        const anneeMax = Math.min(new Date().getFullYear(), 2025);
        const regles = {
            'annee': { min: 1900, max: anneeMax, msg: `L'année doit être comprise entre 1900 et ${anneeMax}.` },
            'km': { min: 10, max: 9999999, msg: 'Le kilométrage doit être compris entre 10 et 9 999 999 km.' },
            'prix': { min: 50, max: 10000000, msg: 'Le prix doit être compris entre 50 € et 10 000 000 €.' },
            'puissance_cv': { min: 1, max: 2000, msg: 'La puissance doit être comprise entre 1 et 2000 CV.' },
            'consommation': { min: 0.1, max: 99.9, msg: 'La consommation doit être comprise entre 0.1 et 99.9.' },
            'consommation_secondaire': { min: 0.1, max: 99.9, msg: 'La consommation secondaire doit être comprise entre 0.1 et 99.9.' },
            'emission_co2': { min: 0, max: 999, msg: 'L\'émission de CO2 doit être comprise entre 0 et 999 g/km.' },
            'autonomie': { min: 50, max: 9999, msg: 'L\'autonomie doit être comprise entre 50 et 9999 km.' },
            'longueur': { min: 1.5, max: 20, msg: 'La longueur doit être comprise entre 1.5m et 20m.' },
            'largeur': { min: 1, max: 4, msg: 'La largeur doit être comprise entre 1m et 4m.' },
            'hauteur': { min: 0.5, max: 5, msg: 'La hauteur doit être comprise entre 0.5m et 5m.' },
            'nb_portes': { min: 2, max: 6, msg: 'Le nombre de portes doit être compris entre 2 et 6.' }
        };

        tousLesChamps.forEach(champ => {
            // Retirer les styles d'erreur précédents
            champ.classList.remove('input-error');
            
            // Ignorer les champs disabled ou masqués
            if (champ.disabled) return;
            const parentMasque = champ.closest('[data-hide-for], [data-show-for]');
            if (parentMasque && parentMasque.style.display === 'none') return;
            
            // 1. Validation champs requis
            if (champ.hasAttribute('required') && (!champ.value || champ.value.trim() === '')) {
                champ.classList.add('input-error');
                valide = false;
                if (!premierChampInvalide) premierChampInvalide = champ;
                return;
            }

            // 1b. Validation HTML5 (badInput) pour attraper les entrées invalides dans type="number"
            if (champ.validity.badInput) {
                champ.classList.add('input-error');
                valide = false;
                if (!premierChampInvalide) {
                    premierChampInvalide = champ;
                    messageErreurSpecifique = `Le champ ${champ.id} contient une valeur invalide (texte interdit).`;
                }
                return;
            }

            // 2. Validation règles spécifiques (si valeur présente)
            if (champ.value && champ.value.trim() !== '') {
                const regle = regles[champ.id];
                
                // Validation champs texte (ville, couleur, provenance)
                if (['ville', 'couleur', 'provenance'].includes(champ.id)) {
                    // 1. Interdire les chiffres
                    if (/\d/.test(champ.value)) {
                        champ.classList.add('input-error');
                        valide = false;
                        if (!premierChampInvalide) {
                            premierChampInvalide = champ;
                            messageErreurSpecifique = `Le champ ${champ.id} ne doit pas contenir de chiffres.`;
                        }
                        return;
                    }

                    // 2. Validation des symboles
                    if (champ.id === 'couleur') {
                        // Couleur : Lettres et espaces uniquement (pas de tiret, pas d'apostrophe)
                        if (!/^[a-zA-ZÀ-ÿ\s]+$/.test(champ.value)) {
                            champ.classList.add('input-error');
                            valide = false;
                            if (!premierChampInvalide) {
                                premierChampInvalide = champ;
                                messageErreurSpecifique = `La couleur ne doit contenir que des lettres et des espaces (pas de symboles).`;
                            }
                            return;
                        }
                    } else {
                        // Ville et Provenance : Lettres, espaces, tirets, apostrophes
                        if (!/^[a-zA-ZÀ-ÿ\s\'-]+$/.test(champ.value)) {
                            champ.classList.add('input-error');
                            valide = false;
                            if (!premierChampInvalide) {
                                premierChampInvalide = champ;
                                messageErreurSpecifique = `Le champ ${champ.id} contient des caractères interdits (seuls tirets et apostrophes sont autorisés).`;
                            }
                            return;
                        }
                    }
                }

                if (regle) {
                    const valeur = parseFloat(champ.value);
                    if (isNaN(valeur) || valeur < regle.min || valeur > regle.max) {
                        champ.classList.add('input-error');
                        valide = false;
                        if (!premierChampInvalide) {
                            premierChampInvalide = champ;
                            messageErreurSpecifique = regle.msg;
                        }
                        
                        // Animation shake
                        champ.animate([
                            { transform: 'translateX(0)' },
                            { transform: 'translateX(-10px)' },
                            { transform: 'translateX(10px)' },
                            { transform: 'translateX(0)' }
                        ], { duration: 300, iterations: 1 });
                    } else {
                        // Arrondir automatiquement à 2 décimales si c'est un nombre valide
                        if (['longueur', 'largeur', 'hauteur', 'puissance_cv', 'consommation', 'consommation_secondaire', 'emission_co2', 'autonomie'].includes(champ.id)) {
                            champ.value = valeur.toFixed(2);
                        }
                    }
                }
            }
        });

        if (!valide) {
            const msg = messageErreurSpecifique || 'Veuillez corriger les erreurs dans le formulaire (champs rouges).';
            this.afficherMessage(msg, 'erreur');
            // Scroll vers le premier champ en erreur
            if (premierChampInvalide) {
                premierChampInvalide.scrollIntoView({ behavior: 'smooth', block: 'center' });
                premierChampInvalide.focus();
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
     * Réinitialise TOUS les champs du formulaire (sauf le type de véhicule)
     */
    reinitialiserTousLesChamps() {
        // Vider tous les inputs text, number, email, tel, url, date
        document.querySelectorAll('input[type="text"], input[type="number"], input[type="email"], input[type="tel"], input[type="url"], input[type="date"]').forEach(input => {
            if (input.name !== 'type_vehicule') {
                input.value = '';
            }
        });
        
        // Vider tous les textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.value = '';
        });
        
        // Réinitialiser tous les selects à leur première option vide
        document.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
        });
        
        // Décocher tous les checkboxes
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Réinitialiser les radios (sauf type_vehicule)
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            if (radio.name !== 'type_vehicule') {
                radio.checked = false;
            }
        });
        
        // Vider les images uploadées
        if (this.imagesSelectionnees) {
            this.imagesSelectionnees = [];
            const previewContainer = document.querySelector('.preview-images');
            if (previewContainer) previewContainer.innerHTML = '';
        }
        
        // Réinitialiser le compteur d'images
        const compteurImages = document.getElementById('compteur-images');
        if (compteurImages) compteurImages.textContent = '0/10';
    }

    /**
     * Gère l'affichage conditionnel des champs selon le type de véhicule
     */
    gererAffichageConditionnelChamps() {
        const typeSelectionne = document.querySelector('input[name="type_vehicule"]:checked')?.value || 'voiture';
        
        // IMPORTANT : Réinitialiser TOUS les champs avant de gérer l'affichage
        // (sauf si c'est le premier chargement)
        if (this.typeVehiculePrecedent && this.typeVehiculePrecedent !== typeSelectionne) {
            this.reinitialiserTousLesChamps();
        }
        this.typeVehiculePrecedent = typeSelectionne;
        
        // Gérer data-hide-for et data-show-for
        const elementsHideFor = document.querySelectorAll('[data-hide-for]');
        const elementsShowFor = document.querySelectorAll('[data-show-for]');
        
        elementsHideFor.forEach(element => {
            const hideForTypes = element.dataset.hideFor.split(',').map(t => t.trim());
            if (hideForTypes.includes(typeSelectionne)) {
                element.style.display = 'none';
                const inputs = element.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.disabled = true;
                    input.value = ''; // Vider le champ
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
                    input.value = ''; // Vider le champ
                    if (input.hasAttribute('required')) {
                        input.dataset.wasRequired = 'true';
                        input.removeAttribute('required');
                    }
                });
            }
        });

        // Filtrer les options de carburant selon le type
        this.filtrerOptionsCarburant(typeSelectionne);

        // Filtrer les options Crit'Air selon le type (fonction utilitaire)
        filtrerOptionsCritAir(typeSelectionne);

        // Filtrer les options Norme Euro selon le type (fonction utilitaire)
        filtrerOptionsNormeEuro(typeSelectionne);

        // Adapter le label de la taille coffre selon le type (fonction utilitaire)
        adapterLabelTailleCoffre(typeSelectionne);

        // Adapter les options de la taille coffre selon le type (fonction utilitaire)
        adapterOptionsTailleCoffre(typeSelectionne);

        // Gérer l'affichage des champs de consommation selon le carburant
        this.gererAffichageConsommation();
    }

    /**
     * Filtre les options de carburant selon le type de véhicule
     */
    filtrerOptionsCarburant(typeVehicule) {
        const selectCarburant = document.getElementById('carburant');
        if (!selectCarburant) return;

        const valeurActuelle = selectCarburant.value;
        
        if (typeVehicule === 'moto') {
            // Motos : Essence et Électrique uniquement
            selectCarburant.innerHTML = `
                <option value="">-- Sélectionnez --</option>
                <option value="Essence">🔴 Essence</option>
                <option value="Électrique">🔵 Électrique</option>
            `;
        } else if (typeVehicule === 'camion') {
            // Camions : Essence, Diesel, GPL, Électrique, Hybride
            selectCarburant.innerHTML = `
                <option value="">-- Sélectionnez --</option>
                <option value="Essence">🔴 Essence</option>
                <option value="Diesel">⚫ Diesel</option>
                <option value="GPL">🟡 GPL</option>
                <option value="Électrique">🔵 Électrique</option>
                <option value="Hybride">🟢 Hybride</option>
            `;
        } else {
            // Voitures : Toutes les options
            selectCarburant.innerHTML = `
                <option value="">-- Sélectionnez --</option>
                <option value="Essence">🔴 Essence</option>
                <option value="Diesel">⚫ Diesel</option>
                <option value="Hybride">🟢 Hybride</option>
                <option value="Électrique">🔵 Électrique</option>
                <option value="GPL">🟡 GPL</option>
            `;
        }

        // Restaurer la valeur si elle est toujours valide
        const optionsDisponibles = Array.from(selectCarburant.options).map(opt => opt.value);
        if (optionsDisponibles.includes(valeurActuelle)) {
            selectCarburant.value = valeurActuelle;
        }

        // Gérer l'affichage de la consommation électrique
        this.gererAffichageConsommation();
    }

    /**
     * Gère l'affichage de la consommation selon le carburant (thermique ou électrique)
     */
    gererAffichageConsommation() {
        const selectCarburant = document.getElementById('carburant');
        const selectTypeHybride = document.getElementById('type_hybride');
        const fieldTypeHybride = document.getElementById('field-type-hybride');
        const fieldConsoPrincipale = document.getElementById('field-consommation-principale');
        const fieldConsoSecondaire = document.getElementById('field-consommation-secondaire');
        const fieldAutonomie = document.getElementById('field-autonomie');
        
        const labelConso = document.getElementById('label-consommation');
        const uniteConso = document.getElementById('unite-consommation');
        
        const inputConsoSecondaire = document.getElementById('consommation_secondaire');
        const inputAutonomie = document.getElementById('autonomie');

        if (!selectCarburant || !fieldConsoPrincipale) {
            return;
        }

        const carburantSelectionne = selectCarburant.value;

        // Réinitialiser tous les champs ET vider leurs valeurs
        if (fieldTypeHybride) fieldTypeHybride.style.display = 'none';
        if (fieldConsoSecondaire) fieldConsoSecondaire.style.display = 'none';
        if (fieldAutonomie) fieldAutonomie.style.display = 'none';
        if (selectTypeHybride) {
            selectTypeHybride.required = false;
            selectTypeHybride.value = ''; // Vider le select
        }
        if (inputConsoSecondaire) inputConsoSecondaire.value = ''; // Vider la 2e consommation
        if (inputAutonomie) inputAutonomie.value = ''; // Vider l'autonomie

        if (carburantSelectionne === 'Électrique') {
            // Électrique pur : consommation en kWh + autonomie
            fieldConsoPrincipale.style.display = 'block';
            if (labelConso) labelConso.textContent = 'Consommation électrique';
            if (uniteConso) uniteConso.textContent = 'kWh/100km';
            if (fieldAutonomie) fieldAutonomie.style.display = 'block';
            
        } else if (carburantSelectionne === 'Hybride') {
            // Hybride : afficher le sélecteur de type
            fieldConsoPrincipale.style.display = 'block';
            if (fieldTypeHybride) {
                fieldTypeHybride.style.display = 'block';
            }
            if (selectTypeHybride) selectTypeHybride.required = true;
            
            // Adapter les champs selon le type d'hybride sélectionné
            this.gererTypeHybride();
            
        } else {
            // Thermique pur (Essence, Diesel, GPL)
            fieldConsoPrincipale.style.display = 'block';
            if (labelConso) labelConso.textContent = 'Consommation';
            if (uniteConso) uniteConso.textContent = 'L/100km';
        }
    }

    /**
     * Gère l'affichage des champs selon le type d'hybride
     */
    gererTypeHybride() {
        const selectTypeHybride = document.getElementById('type_hybride');
        const fieldConsoSecondaire = document.getElementById('field-consommation-secondaire');
        const fieldAutonomie = document.getElementById('field-autonomie');
        const champAutonomie = document.getElementById('autonomie');
        
        const labelConso = document.getElementById('label-consommation');
        const uniteConso = document.getElementById('unite-consommation');
        const labelConsoSecondaire = document.getElementById('label-consommation-secondaire');
        const uniteConsoSecondaire = document.getElementById('unite-consommation-secondaire');

        if (!selectTypeHybride) {
            return;
        }

        const typeHybride = selectTypeHybride.value;

        if (!typeHybride) {
            // Aucun type sélectionné : masquer tout
            if (fieldConsoSecondaire) fieldConsoSecondaire.style.display = 'none';
            if (fieldAutonomie) fieldAutonomie.style.display = 'none';
            return;
        }

        if (fieldConsoSecondaire) fieldConsoSecondaire.style.display = 'block';

        switch(typeHybride) {
            case 'essence_electrique':
            case 'diesel_electrique':
                // 🔒 SÉCURITÉ : HEV non rechargeable - PAS d'autonomie utile (2-3 km max)
                if (labelConso) labelConso.textContent = typeHybride === 'essence_electrique' ? 'Consommation essence' : 'Consommation diesel';
                if (uniteConso) uniteConso.textContent = 'L/100km';
                if (labelConsoSecondaire) labelConsoSecondaire.textContent = 'Consommation électrique';
                if (uniteConsoSecondaire) uniteConsoSecondaire.textContent = 'kWh/100km';
                if (fieldAutonomie) fieldAutonomie.style.display = 'none';
                if (champAutonomie) champAutonomie.value = ''; // Vider le champ
                break;
                
            case 'essence_electrique_rechargeable':
            case 'diesel_electrique_rechargeable':
                // 🔒 SÉCURITÉ : PHEV rechargeable - autonomie électrique requise (30-80 km)
                if (labelConso) labelConso.textContent = typeHybride === 'essence_electrique_rechargeable' ? 'Consommation essence' : 'Consommation diesel';
                if (uniteConso) uniteConso.textContent = 'L/100km';
                if (labelConsoSecondaire) labelConsoSecondaire.textContent = 'Consommation électrique';
                if (uniteConsoSecondaire) uniteConsoSecondaire.textContent = 'kWh/100km';
                if (fieldAutonomie) fieldAutonomie.style.display = 'block';
                break;
                
            case 'gpl_essence':
                // GPL + Essence - pas d'électrique donc pas d'autonomie
                if (labelConso) labelConso.textContent = 'Consommation GPL';
                if (uniteConso) uniteConso.textContent = 'L/100km';
                if (labelConsoSecondaire) labelConsoSecondaire.textContent = 'Consommation essence';
                if (uniteConsoSecondaire) uniteConsoSecondaire.textContent = 'L/100km';
                if (fieldAutonomie) fieldAutonomie.style.display = 'none';
                if (champAutonomie) champAutonomie.value = ''; // Vider le champ
                break;
        }
    }

    /**
     * Met à jour le récapitulatif à l'étape 4
     */
    mettreAJourRecapitulatif() {
        const summaryContent = document.getElementById('summary-content');
        if (!summaryContent) {
            return;
        }

        try {
        // Récupération de toutes les valeurs du formulaire
        const data = {
            // Étape 1
            type: document.querySelector('input[name="type_vehicule"]:checked')?.value || null,
            
            // Étape 2
            marque: document.getElementById('marque')?.value.trim() || null,
            modele: document.getElementById('modele')?.value.trim() || null,
            annee: document.getElementById('annee')?.value || null,
            prix: document.getElementById('prix')?.value || null,
            km: document.getElementById('km')?.value || null,
            code_postal: document.getElementById('code_postal')?.value.trim() || null,
            ville: document.getElementById('ville')?.value.trim() || null,
            carburant: document.getElementById('carburant')?.value || null,
            boite: document.getElementById('boite')?.value || null,
            etat: document.getElementById('etat')?.value || null,
            couleur: document.getElementById('couleur')?.value.trim() || null,
            crit_air: document.getElementById('crit_air')?.value || null,
            nb_portes: document.getElementById('nb_portes')?.value || null,
            nb_places: document.getElementById('nb_places')?.value || null,
            taille_coffre: document.getElementById('taille_coffre')?.value || null,
            puissance_cv: document.getElementById('puissance_cv')?.value || null,
            norme_euro: document.getElementById('norme_euro')?.value || null,
            consommation: document.getElementById('consommation')?.value || null,
            consommation_secondaire: document.getElementById('consommation_secondaire')?.value || null,
            type_hybride: document.getElementById('type_hybride')?.value || null,
            emission_co2: document.getElementById('emission_co2')?.value || null,
            autonomie: document.getElementById('autonomie')?.value || null,
            controle_technique: document.getElementById('controle_technique')?.value || null,
            provenance: document.getElementById('provenance')?.value.trim() || null,
            longueur: document.getElementById('longueur')?.value || null,
            largeur: document.getElementById('largeur')?.value || null,
            hauteur: document.getElementById('hauteur')?.value || null,
            
            // Étape 3
            photos: this.imagesSelectionnees.length
        };

        // Construction du HTML du récapitulatif
        let html = '';

        // Section : Identité du véhicule
        html += '<div class="summary-section">';
        html += '<h4 class="summary-section-title"><i class="fas fa-car"></i> Identité du véhicule</h4>';
        
        html += this.genererLigneRecap('Type de véhicule', this.formaterType(data.type));
        html += this.genererLigneRecap('Marque', data.marque);
        html += this.genererLigneRecap('Modèle', data.modele);
        html += this.genererLigneRecap('Année', data.annee);
        html += this.genererLigneRecap('Couleur', data.couleur);
        html += this.genererLigneRecap('État général', this.formaterEtat(data.etat));
        html += this.genererLigneRecap('Provenance', data.provenance);
        
        if (data.type !== 'moto') {
            html += this.genererLigneRecap('Contrôle technique', this.formaterControleTechnique(data.controle_technique));
        }
        
        html += '</div>';

        // Section : Prix et localisation
        html += '<div class="summary-section">';
        html += '<h4 class="summary-section-title"><i class="fas fa-tag"></i> Prix et localisation</h4>';
        
        html += this.genererLigneRecap('Prix de vente', data.prix ? `${this.formaterNombre(data.prix)} €` : null, true);
        const villeTexte = data.code_postal && data.ville ? `${data.ville} (${data.code_postal})` : data.ville;
        html += this.genererLigneRecap('Localisation', villeTexte);
        html += this.genererLigneRecap('Kilométrage', data.km ? `${this.formaterNombre(data.km)} km` : null);
        
        html += '</div>';

        // Section : Moteur et performances
        html += '<div class="summary-section">';
        html += '<h4 class="summary-section-title"><i class="fas fa-tachometer-alt"></i> Moteur et performances</h4>';
        
        html += this.genererLigneRecap('Type de carburant', this.formaterCarburant(data.carburant));
        html += this.genererLigneRecap('Puissance fiscale', data.puissance_cv ? `${data.puissance_cv} CV` : null);
        
        // Boîte de vitesse (pas pour les motos)
        if (data.type !== 'moto') {
            html += this.genererLigneRecap('Boîte de vitesse', this.formaterBoite(data.boite));
        }
        
        // Consommation intelligente selon le carburant
        if (data.carburant === 'Électrique') {
            html += this.genererLigneRecap('Consommation électrique', data.consommation ? `${data.consommation} kWh/100km` : null);
            html += this.genererLigneRecap('Autonomie électrique', data.autonomie ? `${data.autonomie} km` : null);
        } else if (data.carburant === 'Hybride') {
            // Affichage adapté selon le type d'hybride
            if (data.type_hybride) {
                const typeFormate = this.formaterTypeHybride(data.type_hybride);
                if (typeFormate) {
                    html += this.genererLigneRecap('Type d\'hybride', typeFormate);
                }
                
                if (data.type_hybride === 'gpl_essence') {
                    html += this.genererLigneRecap('Consommation GPL', data.consommation ? `${data.consommation} L/100km` : null);
                    html += this.genererLigneRecap('Consommation essence', data.consommation_secondaire ? `${data.consommation_secondaire} L/100km` : null);
                } else if (data.type_hybride.includes('essence')) {
                    html += this.genererLigneRecap('Consommation essence', data.consommation ? `${data.consommation} L/100km` : null);
                    html += this.genererLigneRecap('Consommation électrique', data.consommation_secondaire ? `${data.consommation_secondaire} kWh/100km` : null);
                    html += this.genererLigneRecap('Autonomie électrique', data.autonomie ? `${data.autonomie} km` : null);
                } else if (data.type_hybride.includes('diesel')) {
                    html += this.genererLigneRecap('Consommation diesel', data.consommation ? `${data.consommation} L/100km` : null);
                    html += this.genererLigneRecap('Consommation électrique', data.consommation_secondaire ? `${data.consommation_secondaire} kWh/100km` : null);
                    html += this.genererLigneRecap('Autonomie électrique', data.autonomie ? `${data.autonomie} km` : null);
                }
            } else {
                // Si type_hybride n'est pas encore sélectionné
                html += this.genererLigneRecap('Consommation principale', data.consommation ? `${data.consommation} L/100km` : null);
                html += this.genererLigneRecap('Consommation secondaire', data.consommation_secondaire ? `${data.consommation_secondaire} kWh/100km` : null);
            }
        } else {
            html += this.genererLigneRecap('Consommation', data.consommation ? `${data.consommation} L/100km` : null);
        }
        
        html += this.genererLigneRecap('Émissions CO₂', data.emission_co2 ? `${data.emission_co2} g/km` : null);
        
        html += '</div>';

        // Section : Équipement (seulement si pas moto)
        if (data.type !== 'moto') {
            html += '<div class="summary-section">';
            html += '<h4 class="summary-section-title"><i class="fas fa-tools"></i> Équipement</h4>';
            
            html += this.genererLigneRecap('Nombre de portes', data.nb_portes ? `${data.nb_portes} portes` : null);
            html += this.genererLigneRecap('Nombre de places', data.nb_places ? `${data.nb_places === '6+' ? '6 ou plus' : data.nb_places + ' places'}` : null);
            html += this.genererLigneRecap('Taille du coffre', this.formaterTailleCoffre(data.taille_coffre));
            
            html += '</div>';
        }

        // Section : Dimensions
        html += '<div class="summary-section">';
        html += '<h4 class="summary-section-title"><i class="fas fa-ruler-combined"></i> Dimensions</h4>';
        
        html += this.genererLigneRecap('Longueur', data.longueur ? `${data.longueur} m` : null);
        html += this.genererLigneRecap('Largeur', data.largeur ? `${data.largeur} m` : null);
        html += this.genererLigneRecap('Hauteur', data.hauteur ? `${data.hauteur} m` : null);
        
        html += '</div>';

        // Section : Normes environnementales
        html += '<div class="summary-section">';
        html += '<h4 class="summary-section-title"><i class="fas fa-leaf"></i> Normes environnementales</h4>';
        
        html += this.genererLigneRecap('Vignette Crit\'Air', this.formaterCritAir(data.crit_air));
        html += this.genererLigneRecap('Norme Euro', data.norme_euro);
        
        // Contrôle technique (pas pour les motos)
        if (data.type !== 'moto') {
            html += this.genererLigneRecap('Contrôle technique', this.formaterControleTechnique(data.controle_technique));
        }
        
        html += '</div>';

        // Section : Photos
        html += '<div class="summary-section">';
        html += '<h4 class="summary-section-title"><i class="fas fa-camera"></i> Photos</h4>';
        
        const photosTexte = data.photos === 0 ? 'Aucune photo' : 
                           data.photos === 1 ? '1 photo' : 
                           `${data.photos} photos`;
        html += this.genererLigneRecap('Photos ajoutées', photosTexte, data.photos >= 3);
        
        html += '</div>';

        summaryContent.innerHTML = html;
        
        } catch (error) {
            console.error('❌ Erreur dans mettreAJourRecapitulatif:', error);
            summaryContent.innerHTML = '<div class="summary-error">Erreur lors de la mise à jour du récapitulatif</div>';
        }
    }

    /**
     * Génère une ligne de récapitulatif
     */
    genererLigneRecap(label, valeur, important = false) {
        const valeurFinale = valeur || '<span class="summary-na">N/A</span>';
        const classeImportant = important ? ' summary-item--important' : '';
        return `
            <div class="summary-item${classeImportant}">
                <span class="summary-label">${label}</span>
                <span class="summary-value">${valeurFinale}</span>
            </div>
        `;
    }

    /**
     * Formateurs pour affichage amélioré
     */
    formaterType(type) {
        const types = {
            'voiture': '🚗 Voiture',
            'moto': '🏍️ Moto',
            'camion': '🚚 Camion'
        };
        return type ? types[type] : null;
    }

    formaterVehicule(marque, modele, annee) {
        if (!marque || !modele) return null;
        return `${marque} ${modele}${annee ? ` (${annee})` : ''}`;
    }

    formaterEtat(etat) {
        const etats = {
            'neuf': '✨ Neuf',
            'bon': '👍 Bon état',
            'moyen': '👌 État moyen',
            'mauvais': '👎 Mauvais état'
        };
        return etat ? etats[etat] : null;
    }

    formaterCarburant(carburant) {
        const carburants = {
            'Essence': '🔴 Essence',
            'Diesel': '⚫ Diesel',
            'Hybride': '🟢 Hybride',
            'Électrique': '🔵 Électrique',
            'GPL': '🟡 GPL'
        };
        return carburant ? carburants[carburant] : null;
    }

    formaterBoite(boite) {
        const boites = {
            'Manuelle': '⚙️ Manuelle',
            'Automatique': '🅰️ Automatique'
        };
        return boite ? boites[boite] : null;
    }

    formaterTailleCoffre(taille) {
        const tailles = {
            'petit': '🔹 Petit (< 300L)',
            'moyen': '🔸 Moyen (300-500L)',
            'grand': '🔶 Grand (> 500L)'
        };
        return taille ? tailles[taille] : null;
    }

    formaterCritAir(critAir) {
        if (!critAir) return null;
        const vignettes = {
            '0': '🟢 Crit\'Air 0',
            '1': '🟣 Crit\'Air 1',
            '2': '🟡 Crit\'Air 2',
            '3': '🟠 Crit\'Air 3',
            '4': '🟤 Crit\'Air 4',
            '5': '⚫ Crit\'Air 5'
        };
        return vignettes[critAir] || `Crit'Air ${critAir}`;
    }

    formaterControleTechnique(ct) {
        const statuts = {
            'non_requis': '🔘 Non requis',
            'oui': '✅ Oui, à jour',
            'non': '❌ Non, à faire'
        };
        return ct ? statuts[ct] : null;
    }

    formaterTypeHybride(type) {
        const types = {
            'essence_electrique': 'Essence + Électrique (HEV)',
            'essence_electrique_rechargeable': 'Essence + Électrique rechargeable (PHEV)',
            'diesel_electrique': 'Diesel + Électrique (HEV)',
            'diesel_electrique_rechargeable': 'Diesel + Électrique rechargeable (PHEV)',
            'gpl_essence': 'GPL + Essence'
        };
        return type ? types[type] : null;
    }

    /**
     * Met à jour la carte de prévisualisation en temps réel
     */
    mettreAJourPrevisualisation() {
        if (!this.cartePreview) return;

        const marque = document.getElementById('marque')?.value || '';
        const modele = document.getElementById('modele')?.value || '';
        const prix = document.getElementById('prix')?.value || '';
        const annee = document.getElementById('annee')?.value || '';
        const km = document.getElementById('km')?.value || '';
        const carburant = document.getElementById('carburant')?.value || '';
        const boite = document.getElementById('boite')?.value || '';
        const ville = document.getElementById('ville')?.value || '';

        // Image de prévisualisation
        const imgPreview = this.cartePreview.querySelector('.preview-card__image img');
        const placeholderIcon = this.cartePreview.querySelector('.preview-card__placeholder-icon');
        
        if (imgPreview) {
            if (this.imagesSelectionnees.length > 0) {
                const lecteur = new FileReader();
                lecteur.onload = (e) => {
                    imgPreview.src = e.target.result;
                    imgPreview.style.display = 'block';
                    if (placeholderIcon) placeholderIcon.style.display = 'none';
                };
                lecteur.readAsDataURL(this.imagesSelectionnees[0]);
            } else {
                imgPreview.style.display = 'none';
                if (placeholderIcon) placeholderIcon.style.display = 'flex';
            }
        }

        // Compteur photos
        const photoCount = this.cartePreview.querySelector('.preview-card__photo-count');
        if (photoCount) {
            photoCount.textContent = this.imagesSelectionnees.length;
            photoCount.parentElement.style.display = this.imagesSelectionnees.length > 0 ? 'flex' : 'none';
        }

        // Titre
        const titrePreview = this.cartePreview.querySelector('.preview-card__title');
        if (titrePreview) {
            titrePreview.textContent = marque && modele ? `${marque} ${modele}` : 'Votre véhicule';
        }

        // Prix
        const prixPreview = this.cartePreview.querySelector('.preview-card__price');
        if (prixPreview) {
            prixPreview.textContent = prix ? `${this.formaterNombre(prix)} €` : '-- €';
        }

        // Infos (année, km, carburant, boîte)
        const anneePreview = this.cartePreview.querySelector('[data-preview="annee"]');
        if (anneePreview) anneePreview.textContent = annee || '--';

        const kmPreview = this.cartePreview.querySelector('[data-preview="km"]');
        if (kmPreview) kmPreview.textContent = km ? `${this.formaterNombre(km)} km` : '-- km';

        const carburantPreview = this.cartePreview.querySelector('[data-preview="carburant"]');
        if (carburantPreview) carburantPreview.textContent = carburant || '--';

        const boitePreview = this.cartePreview.querySelector('[data-preview="boite"]');
        if (boitePreview) boitePreview.textContent = boite || '--';

        // Ville avec code postal
        const villePreview = this.cartePreview.querySelector('[data-preview="ville"]');
        const codePostal = document.getElementById('code_postal')?.value || '';
        const villeTexte = codePostal && ville ? `${ville} (${codePostal})` : (ville || '--');
        if (villePreview) villePreview.textContent = villeTexte;
    }

    /**
     * Gère l'ajout de nouvelles images (permet d'ajouter à la sélection existante)
     */
    gererAjoutImages(e) {
        const nouveauxFichiers = Array.from(e.target.files);
        
        // SÉCURITÉ : Validation des formats d'image autorisés
        const formatsAutorises = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        const extensionsAutorisees = ['.jpg', '.jpeg', '.png', '.webp'];
        
        const fichiersValides = [];
        const fichiersInvalides = [];
        
        nouveauxFichiers.forEach(fichier => {
            const estFormatValide = formatsAutorises.includes(fichier.type.toLowerCase());
            const extensionValide = extensionsAutorisees.some(ext => 
                fichier.name.toLowerCase().endsWith(ext)
            );
            
            if (estFormatValide || extensionValide) {
                fichiersValides.push(fichier);
            } else {
                fichiersInvalides.push(fichier.name);
            }
        });
        
        // Afficher message d'erreur si des fichiers sont invalides
        if (fichiersInvalides.length > 0) {
            const fichierTexte = fichiersInvalides.length > 1 ? 'fichiers' : 'fichier';
            const messageInvalide = `${fichiersInvalides.length} ${fichierTexte} rejeté(s) : seuls les formats JPEG, PNG et WebP sont autorisés.`;
            this.afficherMessage(messageInvalide, 'erreur');
        }
        
        // Si aucun fichier valide, arrêter
        if (fichiersValides.length === 0) {
            this.entreeImages.value = '';
            return;
        }
        
        // Vérifier si on dépasse la limite
        const totalApresAjout = this.imagesSelectionnees.length + fichiersValides.length;
        
        if (totalApresAjout > this.MAX_IMAGES) {
            const placesRestantes = this.MAX_IMAGES - this.imagesSelectionnees.length;
            if (placesRestantes <= 0) {
                this.afficherMessage(`Vous avez atteint la limite de ${this.MAX_IMAGES} images.`, 'erreur');
                this.entreeImages.value = '';
                return;
            }
            this.afficherMessage(`Vous ne pouvez ajouter que ${placesRestantes} image(s) supplémentaire(s). Les premières ont été ajoutées.`, 'info');
            // Prendre uniquement les images qui rentrent
            fichiersValides.splice(placesRestantes);
        }

        // Ajouter les fichiers valides à la sélection
        fichiersValides.forEach(fichier => {
            this.imagesSelectionnees.push(fichier);
        });

        // Réinitialiser l'input pour permettre de sélectionner les mêmes fichiers
        this.entreeImages.value = '';

        // Afficher les aperçus
        this.afficherApercusImages();
        this.mettreAJourEtatBoutonAjout();
        this.mettreAJourRecapitulatif();
        this.mettreAJourPrevisualisation();
    }

    /**
     * Affiche les aperçus de toutes les images sélectionnées
     */
    afficherApercusImages() {
        if (this.imagesSelectionnees.length === 0) {
            this.conteneurApercu.innerHTML = '';
            this.conteneurApercu.hidden = true;
            this.boutonToutSupprimer.hidden = true;
            if (this.zoneTelechargement) this.zoneTelechargement.classList.remove('ajout-upload__zone--mini');
            return;
        }

        this.conteneurApercu.innerHTML = '';
        this.conteneurApercu.hidden = false;
        this.boutonToutSupprimer.hidden = false;
        
        // Réduire la zone de téléchargement mais la garder visible
        if (this.zoneTelechargement) {
            this.zoneTelechargement.classList.add('ajout-upload__zone--mini');
        }

        this.imagesSelectionnees.forEach((fichier, index) => {
            const lecteur = new FileReader();
            lecteur.onload = (evt) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'image-wrapper';
                wrapper.dataset.index = index;
                
                const img = document.createElement('img');
                img.src = evt.target.result;
                img.alt = `Photo ${index + 1}`;
                
                // Badge couverture
                const coverBadge = document.createElement('div');
                coverBadge.className = 'cover-badge';
                coverBadge.innerHTML = '<i class="fas fa-star"></i> Couverture';
                
                // Bouton sélectionner comme couverture (Style identique à Modification)
                const selectCover = document.createElement('button');
                selectCover.type = 'button';
                selectCover.className = 'btn-set-cover';
                selectCover.innerHTML = '<i class="fas fa-star"></i>';
                selectCover.title = 'Définir comme couverture';
                selectCover.onclick = (e) => {
                    e.stopPropagation();
                    this.definirCouverture(index);
                };

                // Bouton supprimer
                const btnSupprimer = document.createElement('button');
                btnSupprimer.type = 'button';
                btnSupprimer.className = 'btn-supprimer';
                btnSupprimer.innerHTML = '<i class="fas fa-times"></i>';
                btnSupprimer.onclick = (e) => {
                    e.stopPropagation();
                    this.supprimerImage(index);
                };
                
                // Click sur le wrapper définit aussi la couverture (UX)
                wrapper.onclick = () => this.definirCouverture(index);
                
                wrapper.appendChild(img);
                wrapper.appendChild(coverBadge);
                wrapper.appendChild(selectCover);
                wrapper.appendChild(btnSupprimer);
                this.conteneurApercu.appendChild(wrapper);

                // Première image = couverture par défaut
                if (index === 0 && !this.conteneurApercu.querySelector('.cover')) {
                    wrapper.classList.add('cover');
                }
            };
            lecteur.readAsDataURL(fichier);
        });
    }

    /**
     * Met à jour l'état du bouton d'ajout (désactivé si limite atteinte)
     */
    mettreAJourEtatBoutonAjout() {
        if (this.zoneTelechargement) {
            if (this.imagesSelectionnees.length >= this.MAX_IMAGES) {
                this.zoneTelechargement.classList.add('ajout-upload__zone--disabled');
                this.zoneTelechargement.querySelector('.ajout-upload__text').textContent = `Limite de ${this.MAX_IMAGES} photos atteinte`;
            } else {
                this.zoneTelechargement.classList.remove('ajout-upload__zone--disabled');
                const restantes = this.MAX_IMAGES - this.imagesSelectionnees.length;
                this.zoneTelechargement.querySelector('.ajout-upload__text').textContent = 
                    this.imagesSelectionnees.length > 0 
                        ? `Ajouter d'autres photos (${restantes} restante${restantes > 1 ? 's' : ''})` 
                        : 'Cliquez ou glissez vos photos ici';
            }
        }
        
        // Mettre à jour le compteur si présent
        if (this.compteurImages) {
            this.compteurImages.textContent = `${this.imagesSelectionnees.length}/${this.MAX_IMAGES}`;
        }
    }

    definirCouverture(index) {
        const allWrappers = this.conteneurApercu.querySelectorAll('.image-wrapper');
        allWrappers.forEach(w => w.classList.remove('cover'));
        const targetWrapper = this.conteneurApercu.querySelector(`[data-index="${index}"]`);
        if (targetWrapper) {
            targetWrapper.classList.add('cover');
            
            // Réorganiser le tableau pour que l'image de couverture soit en premier
            const [imageCouverture] = this.imagesSelectionnees.splice(index, 1);
            this.imagesSelectionnees.unshift(imageCouverture);
            this.afficherApercusImages();
        }
        this.mettreAJourPrevisualisation();
    }

    supprimerImage(index) {
        this.imagesSelectionnees.splice(index, 1);
        this.afficherApercusImages();
        this.mettreAJourEtatBoutonAjout();
        this.mettreAJourRecapitulatif();
        this.mettreAJourPrevisualisation();
    }

    gererSuppressionToutesImages() {
        this.imagesSelectionnees = [];
        if (this.entreeImages) this.entreeImages.value = '';
        this.afficherApercusImages();
        this.mettreAJourEtatBoutonAjout();
        this.mettreAJourRecapitulatif();
        this.mettreAJourPrevisualisation();
    }

    afficherMessage(texte, type = 'succes') {
        
        if (!this.messages) {
            console.error('Élément messages-formulaire introuvable !');
            alert(texte); // Fallback si l'élément n'existe pas
            return;
        }
        
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
        
        // Forcer l'affichage et le positionnement
        this.messages.style.display = 'block';
        this.messages.style.visibility = 'visible';
        this.messages.style.opacity = '1';
        
        // Scroll vers le haut de la page pour voir le message
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Pour les erreurs, garder le message visible plus longtemps
        if (type === 'erreur') {
            // Ne pas effacer automatiquement les messages d'erreur
            return;
        }
        
        // Pour les succès/info, effacer après 5 secondes
        setTimeout(() => {
            if (this.messages && this.messages.innerHTML.includes(texte)) {
                this.messages.innerHTML = '';
            }
        }, 5000);
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
        
        // Supprimer les images de l'input file (qui est vide) et ajouter celles du tableau
        donneesFormulaire.delete('images[]');
        this.imagesSelectionnees.forEach((fichier, index) => {
            donneesFormulaire.append('images[]', fichier);
        });

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
