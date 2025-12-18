/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE MODIFICATION VÉHICULE - FORMULAIRE D'ÉDITION D'ANNONCE EXISTANTE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe gère le formulaire de modification d'une annonce existante :
 * - Chargement des données du véhicule depuis l'API
 * - Navigation multi-étapes (3 étapes)
 * - Gestion des images existantes et nouvelles
 * - Définition de l'image de couverture
 * - Validation des modifications
 * - Suppression de l'annonce
 * 
 * Particularités par rapport à l'ajout :
 * - Le type de véhicule est en lecture seule
 * - Les images existantes peuvent être supprimées
 * - Une image de couverture peut être désignée
 * - Comparaison des valeurs pour détecter les changements
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @see     ControleurVehiculeModification (PHP) Pour le traitement serveur
 * @see     utilitaires-vehicule.js Pour les fonctions partagées
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi } from '../../application.js';
import { 
    filtrerOptionsCritAir, 
    filtrerOptionsNormeEuro, 
    adapterOptionsTailleCoffre
} from '../utilitaires-vehicule.js';

export default class VueModificationVehicule {
    
    /**
     * Initialise la vue de modification avec les paramètres du véhicule
     * 
     * Récupère l'ID du véhicule depuis l'URL et configure
     * les structures de données pour la gestion des images.
     */
    constructor() {
        /** @type {string|null} ID du véhicule à modifier (depuis l'URL) */
        this.idVehicule = new URLSearchParams(window.location.search).get('id');
        
        /** @type {number} Étape actuelle du formulaire (1-3) */
        this.etapeActuelle = 1;
        
        /** @type {number} Nombre total d'étapes */
        this.totalEtapes = 3;
        
        // ═══════════════════════════════════════════════════════════════════
        // GESTION DES IMAGES
        // ═══════════════════════════════════════════════════════════════════
        
        /** @type {string[]} URLs des images existantes en base de données */
        this.imagesExistantes = [];
        
        /** @type {string[]} URLs des images à supprimer lors de la sauvegarde */
        this.imagesASupprimer = [];
        
        /** @type {File[]} Nouveaux fichiers images à uploader */
        this.nouvellesImages = [];
        
        /** @type {number} Nombre maximum d'images par annonce */
        this.MAX_IMAGES = 10;
        
        // ═══════════════════════════════════════════════════════════════════
        // GESTION DE L'IMAGE DE COUVERTURE
        // ═══════════════════════════════════════════════════════════════════
        
        /** @type {'existante'|'nouvelle'} Type de l'image de couverture */
        this.couvertureType = 'existante';
        
        /** @type {number} Index de l'image de couverture dans son tableau */
        this.couvertureIndex = 0;
        
        /** @type {Object} Valeurs initiales pour détection des modifications */
        this.valeursInitiales = {};
        
        /** @type {string|null} Type de véhicule (chargé depuis la BDD) */
        this.typeVehicule = null;
        
        /** @type {boolean} Flag pour détecter le premier chargement */
        this.premierChargement = true;
        
        this.initialiser();
    }

    /**
     * Initialise la vue : vérifie l'ID, charge les données et configure les événements
     * 
     * @async
     */
    async initialiser() {
        // Vérification de la présence de l'ID véhicule
        if (!this.idVehicule) {
            this.afficherErreur("Aucun véhicule spécifié.");
            return;
        }

        // ═══════════════════════════════════════════════════════════════════
        // RÉFÉRENCES AUX ÉLÉMENTS DOM
        // ═══════════════════════════════════════════════════════════════════
        
        this.elChargement = document.getElementById('chargement-modification');
        this.elErreur = document.getElementById('erreur-modification');
        this.elContenu = document.getElementById('contenu-modification');
        this.elProgressSection = document.getElementById('progress-section');
        
        // Formulaire principal
        this.formulaire = document.getElementById('formulaire-modification');
        this.messages = document.querySelector('.messages-formulaire');
        
        // Éléments du stepper de progression
        this.etapes = document.querySelectorAll('.form-step');
        this.stepperSteps = document.querySelectorAll('.stepper__step');
        
        // Champs cachés
        this.champId = document.getElementById('vehicule-id');
        this.champTypeHidden = document.getElementById('type-vehicule-hidden');
        
        // Éléments de gestion des images
        this.zoneImagesExistantes = document.getElementById('images-existantes');
        this.entreeImages = document.getElementById('images');
        this.conteneurApercuNouvelles = document.getElementById('conteneur-apercu-nouvelles');
        this.boutonSupprimerNouvelles = document.getElementById('bouton-tout-supprimer-nouvelles');
        this.compteurExistantes = document.getElementById('compteur-existantes');
        this.compteurNouvelles = document.getElementById('compteur-nouvelles');
        this.compteurTotal = document.getElementById('compteur-total');
        
        // Carte de prévisualisation
        this.cartePreview = document.getElementById('carte-preview');
        
        // Liens et actions
        this.lienVoirAnnonce1 = document.getElementById('lien-voir-annonce-header');
        this.lienVoirAnnonce2 = document.getElementById('lien-voir-annonce-sidebar');
        this.boutonSupprimer = document.getElementById('bouton-supprimer');
        
        // URLs des API
        this.urlApiModification = obtenirUrlApi('/vehicule/modification');
        this.urlApiDetails = obtenirUrlApi('/vehicule/details');

        // Mise à jour des liens vers l'annonce
        if (this.lienVoirAnnonce1) {
            this.lienVoirAnnonce1.href = `vehicule?id=${this.idVehicule}`;
        }
        if (this.lienVoirAnnonce2) {
            this.lienVoirAnnonce2.href = `vehicule?id=${this.idVehicule}`;
        }

        // Chargement des données du véhicule
        await this.chargerDonnees();
        
        // Configuration des événements
        this.attacherEvenements();
    }

    /**
     * Attache tous les écouteurs d'événements du formulaire
     * 
     * Configure les handlers pour la navigation, la soumission,
     * la gestion des images et les mises à jour temps réel.
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
            // Ignorer le bouton de suppression des nouvelles images
            if (btn.id === 'bouton-tout-supprimer-nouvelles') return;
            
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

        if (this.boutonSupprimerNouvelles) {
            this.boutonSupprimerNouvelles.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.supprimerToutesNouvellesImages();
            });
        }

        // Mise à jour temps réel
        const champsTexte = ['marque', 'modele', 'annee', 'prix', 'km', 'ville', 'carburant', 'boite', 'longueur', 'largeur', 'hauteur'];
        champsTexte.forEach(id => {
            const champ = document.getElementById(id);
            if (champ) {
                champ.addEventListener('input', () => {
                    this.mettreAJourPrevisualisation();
                    this.mettreAJourRecapitulatif();
                });
                champ.addEventListener('change', () => {
                    this.mettreAJourPrevisualisation();
                    this.mettreAJourRecapitulatif();
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

        // Gestion affichage conditionnel carburant
        const champCarburant = document.getElementById('carburant');
        if (champCarburant) {
            champCarburant.addEventListener('change', () => {
                this.gererAffichageConsommation();
                this.mettreAJourRecapitulatif();
            });
        }

        // Gestion type d'hybride
        const champTypeHybride = document.getElementById('type_hybride');
        if (champTypeHybride) {
            champTypeHybride.addEventListener('change', () => {
                this.gererTypeHybride();
                this.mettreAJourRecapitulatif();
            });
        }

        // Bouton supprimer
        if (this.boutonSupprimer) {
            this.boutonSupprimer.addEventListener('click', () => this.confirmerSuppression());
        }
        
        // Effacer les erreurs quand on interagit avec un champ
        this.attacherEvenementsChampsErreur();
    }
    
    attacherEvenementsChampsErreur() {
        // Pour tous les champs input, select, textarea
        const champs = this.formulaire.querySelectorAll('input, select, textarea');
        champs.forEach(champ => {
            // Événements multiples pour capturer toutes les interactions
            ['input', 'change', 'blur'].forEach(eventType => {
                champ.addEventListener(eventType, () => {
                    if (champ.classList.contains('input-error')) {
                        // Retirer l'erreur SEULEMENT si le champ est maintenant valide
                        if (champ.value && champ.value.trim() !== '') {
                            champ.classList.remove('input-error');
                            
                            // Masquer le message d'erreur si tous les champs sont corrects
                            const autresErreurs = this.formulaire.querySelectorAll('.input-error');
                            if (autresErreurs.length === 0 && this.messages) {
                                this.messages.innerHTML = '';
                            }
                        }
                    }
                });
            });
        });
    }

    async chargerDonnees() {
        try {
            const res = await fetch(`${this.urlApiModification}?id=${this.idVehicule}`);
            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.error || 'Erreur lors du chargement');
            }

            // Correction : L'API renvoie { vehicule: {...} }
            const donneesVehicule = data.vehicule || data;
            this.preRemplirFormulaire(donneesVehicule);
            this.afficherContenu();
            
        } catch (err) {
            this.afficherErreur(err.message);
        }
    }

    preRemplirFormulaire(vehicule) {
        
        // ID et type
        if (this.champId) this.champId.value = vehicule.id;
        
        this.typeVehicule = vehicule.type_vehicule;
        if (this.champTypeHidden) this.champTypeHidden.value = this.typeVehicule || '';
        
        // 1. RECONSTRUIRE LES OPTIONS DYNAMIQUES AVANT DE REMPLIR LES VALEURS
        // C'est crucial car sinon l'assignation .value �choue si l'option n'existe pas encore dans le DOM
        filtrerOptionsCritAir(this.typeVehicule);
        filtrerOptionsNormeEuro(this.typeVehicule);
        if (this.typeVehicule === 'camion' || this.typeVehicule === 'voiture') {
            adapterOptionsTailleCoffre(this.typeVehicule);
        }
        
        // Stocker les valeurs initiales pour comparaison
        this.valeursInitiales = {
            type: vehicule.type_vehicule,
            marque: vehicule.marque,
            modele: vehicule.modele,
            annee: vehicule.annee,
            prix: vehicule.prix,
            km: vehicule.km,
            ville: vehicule.ville,
            couleur: vehicule.couleur,
            puissance_cv: vehicule.puissance_cv,
            consommation: vehicule.consommation,
            consommation_secondaire: vehicule.consommation_secondaire,
            type_hybride: vehicule.type_hybride,
            emission_co2: vehicule.emission_co2,
            autonomie: vehicule.autonomie,
            provenance: vehicule.provenance,
            longueur: vehicule.longueur,
            largeur: vehicule.largeur,
            hauteur: vehicule.hauteur,
            description: vehicule.description,
            carburant: vehicule.carburant,
            boite: vehicule.boite,
            etat: vehicule.etat,
            crit_air: vehicule.crit_air,
            nb_portes: vehicule.nb_portes,
            nb_places: vehicule.nb_places,
            taille_coffre: vehicule.taille_coffre,
            norme_euro: vehicule.norme_euro,
            controle_technique: vehicule.controle_technique,
            nb_photos: (vehicule.images || []).length
        };
        
        // Champs texte
        const champsSimples = {
            'marque': vehicule.marque,
            'modele': vehicule.modele,
            'annee': vehicule.annee,
            'prix': vehicule.prix,
            'km': vehicule.km,
            'ville': vehicule.ville,
            'couleur': vehicule.couleur,
            'puissance_cv': vehicule.puissance_cv,
            'consommation': vehicule.consommation,
            'consommation_secondaire': vehicule.consommation_secondaire,
            'emission_co2': vehicule.emission_co2,
            'autonomie': vehicule.autonomie,
            'provenance': vehicule.provenance,
            'longueur': vehicule.longueur,
            'largeur': vehicule.largeur,
            'hauteur': vehicule.hauteur,
            'description': vehicule.description
        };

        Object.keys(champsSimples).forEach(id => {
            const champ = document.getElementById(id);
            if (champ && champsSimples[id] !== null && champsSimples[id] !== undefined) {
                champ.value = champsSimples[id];
            }
        });
        
        // Selects (IMPORTANT : carburant en premier pour gererAffichageConditionnelChamps)
        const champsSelect = {
            'carburant': vehicule.carburant,
            'boite': vehicule.boite,
            'etat': vehicule.etat,
            'crit_air': vehicule.crit_air,
            'nb_portes': vehicule.nb_portes,
            'taille_coffre': vehicule.taille_coffre,
            'norme_euro': vehicule.norme_euro,
            'controle_technique': vehicule.controle_technique
        };

        // Gestion spécifique pour nb_places (pour gérer le cas 6+)
        const nbPlaces = parseInt(vehicule.nb_places);
        if (nbPlaces >= 6) {
            champsSelect['nb_places'] = '6+';
        } else {
            champsSelect['nb_places'] = vehicule.nb_places;
        }

        Object.keys(champsSelect).forEach(id => {
            const champ = document.getElementById(id);
            if (champ && champsSelect[id] !== null && champsSelect[id] !== undefined) {
                champ.value = champsSelect[id];
            }
        });
        
        // MAINTENANT gérer l'affichage conditionnel (après que carburant soit rempli)
        // Note: Les options des selects ont déjà été générées au début de la fonction,
        // cette méthode va maintenant gérer la visibilité et les interactions (hybride, etc.)
        // IMPORTANT: premierChargement est encore true pour éviter de vider les champs conditionnels
        this.gererAffichageConditionnelChamps();
        
        // Préremplir le type_hybride APRÈS l'affichage conditionnel (car c'est un select conditionnel)
        if (vehicule.type_hybride) {
            const selectTypeHybride = document.getElementById('type_hybride');
            if (selectTypeHybride) {
                selectTypeHybride.value = vehicule.type_hybride;
                // Mettre à jour l'affichage des champs de consommation selon le type d'hybride
                this.gererTypeHybride();
            }
        }
        
        // Marquer que le premier chargement est terminé APRÈS avoir rempli tous les champs conditionnels
        this.premierChargement = false;

        // Images existantes
        // S'assurer que vehicule.images est un tableau
        let imagesFromDb = [];
        if (Array.isArray(vehicule.images)) {
            imagesFromDb = vehicule.images;
        } else if (typeof vehicule.images === 'string') {
            // Cas où l'API renverrait une chaîne JSON ou séparée par virgules (juste au cas où)
            try {
                imagesFromDb = JSON.parse(vehicule.images);
            } catch (e) {
                imagesFromDb = [vehicule.images];
            }
        }

        this.imagesExistantes = imagesFromDb;
        
        // Ajouter l'image principale si elle n'est pas dans la liste (cas legacy)
        if (vehicule.image_path && !this.imagesExistantes.includes(vehicule.image_path)) {
            this.imagesExistantes.unshift(vehicule.image_path);
        }
        
        this.afficherImagesExistantes();
        this.mettreAJourCompteursImages();
        
        // Mettre � jour le r�capitulatif et la pr�visualisation
        this.mettreAJourRecapitulatif();
        this.mettreAJourPrevisualisation();
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
            this.elContenu.style.display = '';
        }
        if (this.elProgressSection) {
            this.elProgressSection.hidden = false;
            this.elProgressSection.style.display = '';
        }
    }

    afficherErreur(message) {
        if (this.elChargement) {
            this.elChargement.hidden = true;
            this.elChargement.style.display = 'none';
        }
        if (this.elContenu) {
            this.elContenu.hidden = true;
            this.elContenu.style.display = 'none';
        }
        if (this.elProgressSection) {
            this.elProgressSection.hidden = true;
            this.elProgressSection.style.display = 'none';
        }
        if (this.elErreur) {
            this.elErreur.hidden = false;
            this.elErreur.style.display = 'block';
            const msgElement = document.getElementById('message-erreur');
            if (msgElement) msgElement.textContent = message;
        }
    }

    /**
     * Validation de l'étape actuelle
     */
    validerEtapeActuelle() {
        
        const etapeElement = document.querySelector(`.form-step[data-step="${this.etapeActuelle}"]`);
        if (!etapeElement) return true;

        // VALIDATION ÉTAPE 1 : Tous les champs (requis ou non, validation format)
        if (this.etapeActuelle === 1) {
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
                'autonomie': { min: 50, max: 9999, msg: 'L\'autonomie doit être comprise entre 50 et 9999 km.' },
                'longueur': { min: 1.5, max: 20, msg: 'La longueur doit être comprise entre 1.5m et 20m.' },
                'largeur': { min: 1, max: 4, msg: 'La largeur doit être comprise entre 1m et 4m.' },
                'hauteur': { min: 0.5, max: 5, msg: 'La hauteur doit être comprise entre 0.5m et 5m.' },
                'nb_portes': { min: 2, max: 6, msg: 'Le nombre de portes doit être compris entre 2 et 6.' }
            };

            tousLesChamps.forEach(champ => {
                champ.classList.remove('input-error');
                
                // Ignorer les champs disabled ou masqués
                if (champ.disabled) return;
                const row = champ.closest('.grille') || champ.closest('.champ');
                if (row && (window.getComputedStyle(row).display === 'none' || row.hidden)) return;
                
                // Ignorer les champs masqués par un parent avec style="display: none" (cas spécifique modification)
                const parentMasque = champ.closest('[style*="display: none"]');
                if (parentMasque) return;

                // 1. Validation HTML5 complète
                if (!champ.checkValidity() || champ.validity.badInput) {
                    valide = false;
                    champ.classList.add('input-error');
                    if (!premierChampInvalide) premierChampInvalide = champ;
                    
                    champ.animate([
                        { transform: 'translateX(0)' },
                        { transform: 'translateX(-10px)' },
                        { transform: 'translateX(10px)' },
                        { transform: 'translateX(0)' }
                    ], { duration: 300, iterations: 1 });
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
                            // Couleur : Lettres et espaces uniquement
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
                            
                            champ.animate([
                                { transform: 'translateX(0)' },
                                { transform: 'translateX(-10px)' },
                                { transform: 'translateX(10px)' },
                                { transform: 'translateX(0)' }
                            ], { duration: 300, iterations: 1 });
                            return;
                        }

                        // Arrondir automatiquement à 2 décimales si c'est un nombre valide
                        if (['longueur', 'largeur', 'hauteur', 'puissance_cv', 'consommation', 'consommation_secondaire', 'emission_co2', 'autonomie'].includes(champ.id)) {
                            champ.value = valeur.toFixed(2);
                        }
                    }
                }
            });
            
            if (!valide) {
                const msg = messageErreurSpecifique || 'Veuillez corriger les erreurs dans le formulaire (champs rouges).';
                this.afficherMessage(msg, 'erreur');
                if (premierChampInvalide) {
                    premierChampInvalide.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    premierChampInvalide.focus();
                }
                return false;
            }
            
        }

        // VALIDATION ÉTAPE 2 : Minimum 3 photos (existantes + nouvelles - supprimées)
        if (this.etapeActuelle === 2) {
            const totalPhotos = this.imagesExistantes.length + this.nouvellesImages.length - this.imagesASupprimer.length;
            
            if (totalPhotos < 3) {
                const nombreManquant = 3 - totalPhotos;
                const imageTexte = nombreManquant > 1 ? 'images' : 'image';
                const messageErreur = `Il vous manque ${nombreManquant} ${imageTexte} ! Vous devez avoir au minimum 3 photos (actuellement : ${totalPhotos}/3)`;
                this.afficherMessage(messageErreur, 'erreur');
                return false;
            }
        }

        return true;
    }

    /**
     * Navigation entre les étapes
     */
    allerAEtape(numeroEtape) {
        if (numeroEtape < 1 || numeroEtape > this.totalEtapes) return;

        // Masquer toutes les étapes
        this.etapes.forEach(etape => {
            etape.classList.remove('form-step--active');
        });

        // Afficher l'étape ciblée
        const etapeCible = document.querySelector(`.form-step[data-step="${numeroEtape}"]`);
        if (etapeCible) {
            etapeCible.classList.add('form-step--active');
        }

        // Mettre à jour le stepper
        this.stepperSteps.forEach((step, index) => {
            const stepNum = index + 1;
            if (stepNum < numeroEtape) {
                step.classList.add('stepper__step--completed');
                step.classList.remove('stepper__step--active');
            } else if (stepNum === numeroEtape) {
                step.classList.add('stepper__step--active');
                step.classList.remove('stepper__step--completed');
            } else {
                step.classList.remove('stepper__step--active', 'stepper__step--completed');
            }
        });

        this.etapeActuelle = numeroEtape;
        
        // Scroll en haut
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Mettre à jour le récapitulatif si on est à l'étape 3
        if (numeroEtape === 3) {
            this.mettreAJourRecapitulatif();
        }
    }

    /**
     * Filtre les options Norme Euro selon le type de véhicule
     */
    filtrerOptionsNormeEuro(typeVehicule) {
        const selectNormeEuro = document.getElementById('norme_euro');
        if (!selectNormeEuro) return;

        const valeurActuelle = selectNormeEuro.value;

        if (typeVehicule === 'moto') {
            // Motos : Euro 3, 4, 5 uniquement
            selectNormeEuro.innerHTML = `
                <option value="">-- Non renseigné --</option>
                <option value="Euro 3">Euro 3</option>
                <option value="Euro 4">Euro 4</option>
                <option value="Euro 5">Euro 5</option>
            `;
        } else {
            // Voitures et Camions : Euro 1 à 6d
            selectNormeEuro.innerHTML = `
                <option value="">-- Non renseigné --</option>
                <option value="Euro 1">Euro 1</option>
                <option value="Euro 2">Euro 2</option>
                <option value="Euro 3">Euro 3</option>
                <option value="Euro 4">Euro 4</option>
                <option value="Euro 5">Euro 5</option>
                <option value="Euro 6">Euro 6</option>
                <option value="Euro 6d">Euro 6d</option>
            `;
        }

        // Restaurer la valeur si elle est toujours valide
        const optionsDisponibles = Array.from(selectNormeEuro.options).map(opt => opt.value);
        if (optionsDisponibles.includes(valeurActuelle)) {
            selectNormeEuro.value = valeurActuelle;
        }
    }

    /**
     * Filtre les options Crit'Air selon le type de véhicule
     */
    filtrerOptionsCritAir(typeVehicule) {
        const selectCritAir = document.getElementById('crit_air');
        if (!selectCritAir) return;

        const valeurActuelle = selectCritAir.value;

        if (typeVehicule === 'moto') {
            // Motos : Crit'Air 1, 2, 3 uniquement (pas 0, 4, 5)
            selectCritAir.innerHTML = `
                <option value="">-- Non renseigné --</option>
                <option value="1">🟣 Crit'Air 1</option>
                <option value="2">🟡 Crit'Air 2</option>
                <option value="3">🟠 Crit'Air 3</option>
            `;
        } else {
            // Voitures et Camions : Crit'Air 0 à 5
            selectCritAir.innerHTML = `
                <option value="">-- Non renseigné --</option>
                <option value="0">🟢 Crit'Air 0 (Électrique)</option>
                <option value="1">🟣 Crit'Air 1</option>
                <option value="2">🟡 Crit'Air 2</option>
                <option value="3">🟠 Crit'Air 3</option>
                <option value="4">🟤 Crit'Air 4</option>
                <option value="5">⚫ Crit'Air 5</option>
            `;
        }

        // Restaurer la valeur si elle est toujours valide
        const optionsDisponibles = Array.from(selectCritAir.options).map(opt => opt.value);
        if (optionsDisponibles.includes(valeurActuelle)) {
            selectCritAir.value = valeurActuelle;
        }
    }

    /**
     * Adapte les options de la taille coffre selon le type de véhicule
     */
    adapterOptionsTailleCoffre(typeVehicule) {
        const selectTailleCoffre = document.getElementById('taille_coffre');
        if (!selectTailleCoffre) return;

        const valeurActuelle = selectTailleCoffre.value;

        if (typeVehicule === 'camion') {
            // Camions : Volume en m³
            selectTailleCoffre.innerHTML = `
                <option value="">-- Non renseigné --</option>
                <option value="petit">🔹 Petit (< 10 m³)</option>
                <option value="moyen">🔸 Moyen (10-20 m³)</option>
                <option value="grand">🔶 Grand (> 20 m³)</option>
            `;
        } else {
            // Voitures : Volume en Litres
            selectTailleCoffre.innerHTML = `
                <option value="">-- Non renseigné --</option>
                <option value="petit">🔹 Petit (< 300L)</option>
                <option value="moyen">🔸 Moyen (300-500L)</option>
                <option value="grand">🔶 Grand (> 500L)</option>
            `;
        }

        // Restaurer la valeur
        selectTailleCoffre.value = valeurActuelle;
    }

    /**
     * Gestion affichage conditionnel des champs
     */
    gererAffichageConditionnelChamps() {
        const carburant = document.getElementById('carburant')?.value;
        
        const fieldBoite = document.getElementById('field-boite');
        const fieldPortesPlaces = document.getElementById('field-portes-places');
        const fieldCoffre = document.getElementById('field-coffre');
        const fieldControleTechnique = document.querySelector('[for="controle_technique"]')?.parentElement;

        // Gestion selon le type de v�hicule
        const estMoto = this.typeVehicule === 'moto';
        
        // Filtrer Crit'Air selon le type (fonction utilitaire)
        filtrerOptionsCritAir(this.typeVehicule);
        
        // Filtrer Norme Euro selon le type (fonction utilitaire)
        filtrerOptionsNormeEuro(this.typeVehicule);
        
        // Adapter taille coffre selon le type (fonction utilitaire)
        if (this.typeVehicule === 'camion' || this.typeVehicule === 'voiture') {
            adapterOptionsTailleCoffre(this.typeVehicule);
        }
        
        // Bo�te de vitesse (moto n'a pas)
        if (fieldBoite) {
            fieldBoite.style.display = estMoto ? 'none' : 'block';
            const inputBoite = document.getElementById('boite');
            if (inputBoite) {
                inputBoite.disabled = estMoto;
                inputBoite.required = !estMoto;
                if (estMoto) inputBoite.value = '';
            }
        }
        
        // Portes et places (moto n'a pas)
        if (fieldPortesPlaces) {
            fieldPortesPlaces.style.display = estMoto ? 'none' : 'flex';
            const inputPortes = document.getElementById('nb_portes');
            const inputPlaces = document.getElementById('nb_places');
            if (inputPortes) {
                inputPortes.disabled = estMoto;
                inputPortes.required = !estMoto;
                if (estMoto) inputPortes.value = '';
            }
            if (inputPlaces) {
                inputPlaces.disabled = estMoto;
                if (estMoto) inputPlaces.value = '';
            }
        }
        
        // Taille coffre (moto n'a pas)
        if (fieldCoffre) {
            fieldCoffre.style.display = estMoto ? 'none' : 'flex';
            const inputCoffre = document.getElementById('taille_coffre');
            if (inputCoffre) {
                inputCoffre.disabled = estMoto;
                if (estMoto) inputCoffre.value = '';
            }
        }
        
        // Contrôle technique (moto n'a pas)
        if (fieldControleTechnique) {
            fieldControleTechnique.style.display = estMoto ? 'none' : 'flex';
            const inputCT = document.getElementById('controle_technique');
            if (inputCT) {
                inputCT.disabled = estMoto;
                inputCT.required = !estMoto;
                if (estMoto) inputCT.value = '';
            }
        }
        
        // Carburant - Filtrer les options selon le type
        const selectCarburant = document.getElementById('carburant');
        if (selectCarburant) {
            const valeurActuelle = selectCarburant.value;
            const options = selectCarburant.querySelectorAll('option');
            let carburantValide = false;
            
            options.forEach(option => {
                if (option.value === '') return; // Garder l'option vide
                
                // Moto : seulement Essence et Électrique
                if (estMoto) {
                    if (['Essence', 'Électrique'].includes(option.value)) {
                        option.disabled = false;
                        option.style.display = '';
                        if (option.value === valeurActuelle) carburantValide = true;
                    } else {
                        option.disabled = true;
                        option.style.display = 'none';
                    }
                } else {
                    // Voiture/Camion : tous les carburants
                    option.disabled = false;
                    option.style.display = '';
                    if (option.value === valeurActuelle) carburantValide = true;
                }
            });
            
            // Si le carburant actuel n'est plus valide, réinitialiser
            if (!carburantValide && valeurActuelle !== '') {
                selectCarburant.value = '';
                // Vider aussi les champs de consommation liés
                const inputConsoPrincipale = document.getElementById('consommation_principale');
                const inputConsoSecondaire = document.getElementById('consommation_secondaire');
                const inputAutonomie = document.getElementById('autonomie');
                const selectTypeHybride = document.getElementById('type_hybride');
                if (inputConsoPrincipale) inputConsoPrincipale.value = '';
                if (inputConsoSecondaire) inputConsoSecondaire.value = '';
                if (inputAutonomie) inputAutonomie.value = '';
                if (selectTypeHybride) selectTypeHybride.value = '';
            }
        }
        
        // Crit'Air - Tous les véhicules l'ont (pas de masquage)

        // Gestion selon le carburant (consommation)
        this.gererAffichageConsommation();
    }

    /**
     * Gère l'affichage des champs de consommation selon le carburant
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
        
        const inputConsoPrincipale = document.getElementById('consommation_principale');
        const inputConsoSecondaire = document.getElementById('consommation_secondaire');
        const inputAutonomie = document.getElementById('autonomie');

        if (!selectCarburant || !fieldConsoPrincipale) return;

        const carburantSelectionne = selectCarburant.value;

        // Réinitialiser l'affichage des champs
        fieldTypeHybride.style.display = 'none';
        fieldConsoSecondaire.style.display = 'none';
        fieldAutonomie.style.display = 'none';
        if (selectTypeHybride) {
            selectTypeHybride.required = false;
        }
        
        // Vider les champs seulement s'il ne s'agit pas du premier chargement
        if (!this.premierChargement) {
            if (selectTypeHybride) {
                selectTypeHybride.value = '';
            }
            if (inputConsoSecondaire) {
                inputConsoSecondaire.value = '';
            }
            if (inputAutonomie) {
                inputAutonomie.value = '';
            }
        }

        if (carburantSelectionne === 'Électrique') {
            // Électrique pur : consommation en kWh + autonomie
            fieldConsoPrincipale.style.display = 'block';
            labelConso.textContent = 'Consommation électrique';
            uniteConso.textContent = 'kWh/100km';
            fieldAutonomie.style.display = 'block';
            
        } else if (carburantSelectionne === 'Hybride') {
            // Hybride : afficher le sélecteur de type
            fieldConsoPrincipale.style.display = 'block';
            fieldTypeHybride.style.display = 'block';
            if (selectTypeHybride) selectTypeHybride.required = true;
            
            // Adapter les champs selon le type d'hybride sélectionné
            this.gererTypeHybride();
            
        } else {
            // Thermique pur (Essence, Diesel, GPL)
            fieldConsoPrincipale.style.display = 'block';
            labelConso.textContent = 'Consommation';
            uniteConso.textContent = 'L/100km';
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

        if (!selectTypeHybride) return;

        const typeHybride = selectTypeHybride.value;

        if (!typeHybride) {
            // Aucun type sélectionné : masquer tout
            fieldConsoSecondaire.style.display = 'none';
            fieldAutonomie.style.display = 'none';
            return;
        }

        fieldConsoSecondaire.style.display = 'block';

        switch(typeHybride) {
            case 'essence_electrique':
            case 'diesel_electrique':
                // 🔒 SÉCURITÉ : HEV non rechargeable - PAS d'autonomie utile (2-3 km max)
                labelConso.textContent = typeHybride === 'essence_electrique' ? 'Consommation essence' : 'Consommation diesel';
                uniteConso.textContent = 'L/100km';
                labelConsoSecondaire.textContent = 'Consommation électrique';
                uniteConsoSecondaire.textContent = 'kWh/100km';
                fieldAutonomie.style.display = 'none';
                if (champAutonomie) champAutonomie.value = ''; // Vider le champ
                break;
                
            case 'essence_electrique_rechargeable':
            case 'diesel_electrique_rechargeable':
                // 🔒 SÉCURITÉ : PHEV rechargeable - autonomie électrique requise (30-80 km)
                labelConso.textContent = typeHybride === 'essence_electrique_rechargeable' ? 'Consommation essence' : 'Consommation diesel';
                uniteConso.textContent = 'L/100km';
                labelConsoSecondaire.textContent = 'Consommation électrique';
                uniteConsoSecondaire.textContent = 'kWh/100km';
                fieldAutonomie.style.display = 'block';
                break;
                
            case 'gpl_essence':
                // GPL + Essence - pas d'électrique donc pas d'autonomie
                labelConso.textContent = 'Consommation GPL';
                uniteConso.textContent = 'L/100km';
                labelConsoSecondaire.textContent = 'Consommation essence';
                uniteConsoSecondaire.textContent = 'L/100km';
                fieldAutonomie.style.display = 'none';
                if (champAutonomie) champAutonomie.value = ''; // Vider le champ
                break;
        }
    }

    /**
     * Gestion des images existantes
     */
    afficherImagesExistantes() {
        if (!this.zoneImagesExistantes) return;
        
        this.zoneImagesExistantes.innerHTML = '';
        
        this.imagesExistantes.forEach((src, index) => {
            // Ne pas afficher les images marquées pour suppression
            if (this.imagesASupprimer.includes(src)) return;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'image-wrapper';
            wrapper.dataset.type = 'existante';
            wrapper.dataset.index = index;
            
            const img = document.createElement('img');
            img.src = src;
            img.alt = `Photo ${index + 1}`;
            
            const coverBadge = document.createElement('div');
            coverBadge.className = 'cover-badge';
            coverBadge.innerHTML = '<i class="fas fa-star"></i> Couverture';
            
            const selectCover = document.createElement('button');
            selectCover.type = 'button';
            selectCover.className = 'btn-set-cover';
            selectCover.innerHTML = '<i class="fas fa-star"></i>';
            selectCover.title = 'Définir comme couverture';
            selectCover.onclick = (e) => {
                e.stopPropagation();
                this.definirCouverte('existante', index);
            };
            
            const btnSupprimer = document.createElement('button');
            btnSupprimer.type = 'button';
            btnSupprimer.className = 'btn-supprimer';
            btnSupprimer.innerHTML = '<i class="fas fa-times"></i>';
            btnSupprimer.title = 'Supprimer cette photo';
            btnSupprimer.onclick = (e) => {
                e.stopPropagation();
                this.marquerImagePourSuppression(src);
            };
            
            wrapper.appendChild(img);
            wrapper.appendChild(coverBadge);
            wrapper.appendChild(selectCover);
            wrapper.appendChild(btnSupprimer);
            this.zoneImagesExistantes.appendChild(wrapper);
        });
        
        this.mettreAJourCompteursImages();
        this.mettreAJourAffichageCouverture();
    }

    marquerImagePourSuppression(src) {
        if (!this.imagesASupprimer.includes(src)) {
            this.imagesASupprimer.push(src);
            
            // Si c'est l'image de couverture, transférer à la suivante
            const indexSupprime = this.imagesExistantes.indexOf(src);
            if (indexSupprime === 0) {
                // Trouver la prochaine image non supprimée
                const prochaineImage = this.imagesExistantes.find((img, idx) => 
                    idx > 0 && !this.imagesASupprimer.includes(img)
                );
                
                if (prochaineImage) {
                    const indexProchaine = this.imagesExistantes.indexOf(prochaineImage);
                    this.definirCouverte('existante', indexProchaine);
                } else if (this.nouvellesImages.length > 0) {
                    this.definirCouverte('nouvelle', 0);
                }
            }
            
            this.afficherImagesExistantes();
            this.afficherNouvellesImages();
            this.mettreAJourPrevisualisation();
            this.mettreAJourRecapitulatif();
        }
    }

    /**
     * Gestion des nouvelles images
     */
    gererAjoutImages(e) {
        const fichiers = Array.from(e.target.files);
        
        // Validation des formats
        const formatsAutorises = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        const extensionsAutorisees = ['.jpg', '.jpeg', '.png', '.webp'];
        
        const fichiersValides = [];
        const fichiersRejetes = [];
        
        fichiers.forEach(fichier => {
            const extension = '.' + fichier.name.split('.').pop().toLowerCase();
            if (formatsAutorises.includes(fichier.type) && extensionsAutorisees.includes(extension)) {
                fichiersValides.push(fichier);
            } else {
                fichiersRejetes.push(fichier.name);
            }
        });
        
        if (fichiersRejetes.length > 0) {
            const rejeteTexte = fichiersRejetes.length === 1 ? 'fichier rejeté' : 'fichiers rejetés';
            this.afficherMessage(`${fichiersRejetes.length} ${rejeteTexte} (formats acceptés : JPEG, PNG, WebP)`, 'erreur');
        }
        
        if (fichiersValides.length === 0) return;
        
        // Vérifier le nombre total
        const totalActuel = this.imagesExistantes.length - this.imagesASupprimer.length + this.nouvellesImages.length;
        const espaceDispo = this.MAX_IMAGES - totalActuel;
        
        if (espaceDispo <= 0) {
            this.afficherMessage(`Maximum ${this.MAX_IMAGES} photos atteint`, 'erreur');
            return;
        }
        
        const fichiersAAjouter = fichiersValides.slice(0, espaceDispo);
        
        fichiersAAjouter.forEach(fichier => {
            this.nouvellesImages.push(fichier);
        });
        
        this.afficherNouvellesImages();
        this.mettreAJourPrevisualisation();
        this.mettreAJourRecapitulatif();
        e.target.value = '';
    }

    afficherNouvellesImages() {
        if (!this.conteneurApercuNouvelles) return;
        
        // Vider complètement le conteneur
        this.conteneurApercuNouvelles.innerHTML = '';
        
        // Si aucune nouvelle image, masquer le bouton et sortir
        if (this.nouvellesImages.length === 0) {
            if (this.boutonSupprimerNouvelles) {
                this.boutonSupprimerNouvelles.hidden = true;
            }
            this.mettreAJourCompteursImages();
            return;
        }
        
        // Ajouter titre si nouvelles images
        const header = document.createElement('div');
        header.className = 'modification-images__header';
        header.style.marginTop = '2rem';
        header.innerHTML = `
            <h3 class="modification-images__title">
                <i class="fas fa-plus-circle"></i> Nouvelles photos (à ajouter)
            </h3>
            <span class="modification-images__hint">Cliquez sur une photo pour la définir comme couverture • Cliquez sur ✕ pour supprimer</span>
        `;
        this.conteneurApercuNouvelles.appendChild(header);
        
        // Créer la grille
        const grid = document.createElement('div');
        grid.className = 'modification-images__grid';
        
        this.nouvellesImages.forEach((fichier, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'image-wrapper';
            wrapper.dataset.type = 'nouvelle';
            wrapper.dataset.index = index;
            
            const img = document.createElement('img');
            const lecteur = new FileReader();
            lecteur.onload = (e) => {
                img.src = e.target.result;
            };
            lecteur.readAsDataURL(fichier);
            img.alt = `Nouvelle photo ${index + 1}`;
            
            // Badge "Nouvelle"
            const badge = document.createElement('span');
            badge.className = 'nouvelle-badge';
            badge.innerHTML = '<i class="fas fa-plus"></i> Nouvelle';
            
            // Badge couverture (si c'est la couverture)
            const coverBadge = document.createElement('div');
            coverBadge.className = 'cover-badge';
            coverBadge.innerHTML = '<i class="fas fa-star"></i> Couverture';
            
            // Bouton sélectionner comme couverture
            const selectCover = document.createElement('button');
            selectCover.type = 'button';
            selectCover.className = 'btn-set-cover';
            selectCover.innerHTML = '<i class="fas fa-star"></i>';
            selectCover.title = 'Définir comme couverture';
            selectCover.onclick = (e) => {
                e.stopPropagation();
                this.definirCouverte('nouvelle', index);
            };
            
            // Bouton supprimer
            const btnSupprimer = document.createElement('button');
            btnSupprimer.type = 'button';
            btnSupprimer.className = 'btn-supprimer';
            btnSupprimer.innerHTML = '<i class="fas fa-times"></i>';
            btnSupprimer.title = 'Supprimer cette photo';
            btnSupprimer.onclick = (e) => {
                e.stopPropagation();
                this.supprimerNouvelleImage(index);
            };
            
            wrapper.appendChild(img);
            wrapper.appendChild(badge);
            wrapper.appendChild(coverBadge);
            wrapper.appendChild(selectCover);
            wrapper.appendChild(btnSupprimer);
            grid.appendChild(wrapper);
        });
        
        // Ajouter la grille au conteneur
        this.conteneurApercuNouvelles.appendChild(grid);
        
        // Afficher le bouton "Tout supprimer" (il est en dehors du conteneur dans le HTML)
        if (this.boutonSupprimerNouvelles) {
            this.boutonSupprimerNouvelles.hidden = false;
        }
        
        this.mettreAJourCompteursImages();
        this.mettreAJourAffichageCouverture();
    }

    supprimerNouvelleImage(index) {
        this.nouvellesImages.splice(index, 1);
        
        // Si c'était la couverture, réajuster
        if (this.couvertureType === 'nouvelle' && this.couvertureIndex === index) {
            // Trouver une nouvelle couverture
            const premiereExistante = this.imagesExistantes.find(img => !this.imagesASupprimer.includes(img));
            if (premiereExistante) {
                const idx = this.imagesExistantes.indexOf(premiereExistante);
                this.definirCouverte('existante', idx);
            } else if (this.nouvellesImages.length > 0) {
                this.definirCouverte('nouvelle', 0);
            }
        }
        
        this.afficherNouvellesImages();
        this.mettreAJourPrevisualisation();
        this.mettreAJourRecapitulatif();
    }

    /**
     * Définir une image comme couverture
     */
    definirCouverte(type, index) {
        this.couvertureType = type;
        this.couvertureIndex = index;
        this.mettreAJourAffichageCouverture();
        this.mettreAJourPrevisualisation();
    }

    /**
     * Mettre à jour l'affichage de la couverture
     */
    mettreAJourAffichageCouverture() {
        // Retirer tous les badges is-cover
        document.querySelectorAll('.image-wrapper').forEach(wrapper => {
            wrapper.classList.remove('is-cover');
        });
        
        // Ajouter is-cover à la bonne image en utilisant data-index
        if (this.couvertureType === 'existante') {
            const wrapper = document.querySelector(`.image-wrapper[data-type="existante"][data-index="${this.couvertureIndex}"]`);
            if (wrapper) {
                wrapper.classList.add('is-cover');
            }
        } else if (this.couvertureType === 'nouvelle') {
            const wrapper = document.querySelector(`.image-wrapper[data-type="nouvelle"][data-index="${this.couvertureIndex}"]`);
            if (wrapper) {
                wrapper.classList.add('is-cover');
            }
        }
    }

    supprimerToutesNouvellesImages() {
        this.nouvellesImages = [];
        
        // Si la couverture était sur une nouvelle image, la réinitialiser sur la première existante
        if (this.couvertureType === 'nouvelle') {
            this.couvertureType = 'existante';
            this.couvertureIndex = 0;
        }
        
        this.afficherNouvellesImages();
        this.afficherImagesExistantes(); // Rafraîchir pour mettre à jour le badge de couverture
        this.mettreAJourPrevisualisation();
        this.mettreAJourRecapitulatif();
    }

    mettreAJourCompteursImages() {
        const nbExistantes = this.imagesExistantes.length - this.imagesASupprimer.length;
        const nbNouvelles = this.nouvellesImages.length;
        const nbTotal = nbExistantes + nbNouvelles;
        
        if (this.compteurExistantes) this.compteurExistantes.textContent = nbExistantes;
        if (this.compteurNouvelles) this.compteurNouvelles.textContent = nbNouvelles;
        if (this.compteurTotal) this.compteurTotal.textContent = nbTotal;
        
        // Mettre à jour le compteur dans la preview
        const previewCount = document.getElementById('preview-photo-count');
        if (previewCount) previewCount.textContent = nbTotal;
    }

    /**
     * Mise à jour du récapitulatif - affiche UNIQUEMENT les modifications
     */
    mettreAJourRecapitulatif() {
        const summaryContent = document.getElementById('summary-content');
        if (!summaryContent) return;

        // Récupération des valeurs actuelles
        const valeursActuelles = {
            type: this.typeVehicule,
            marque: document.getElementById('marque')?.value.trim() || null,
            modele: document.getElementById('modele')?.value.trim() || null,
            annee: document.getElementById('annee')?.value || null,
            prix: document.getElementById('prix')?.value || null,
            km: document.getElementById('km')?.value || null,
            ville: document.getElementById('ville')?.value.trim() || null,
            carburant: document.getElementById('carburant')?.value || null,
            type_hybride: document.getElementById('type_hybride')?.value || null,
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
            emission_co2: document.getElementById('emission_co2')?.value || null,
            autonomie: document.getElementById('autonomie')?.value || null,
            controle_technique: document.getElementById('controle_technique')?.value || null,
            provenance: document.getElementById('provenance')?.value.trim() || null,
            longueur: document.getElementById('longueur')?.value || null,
            largeur: document.getElementById('largeur')?.value || null,
            hauteur: document.getElementById('hauteur')?.value || null,
            nb_photos: this.imagesExistantes.length - this.imagesASupprimer.length + this.nouvellesImages.length
        };
        
        // Détecter les modifications
        const modifications = [];
        
        Object.keys(valeursActuelles).forEach(cle => {
            // Ignorer nb_photos car géré séparément
            if (cle === 'nb_photos') return;

            // Ignorer les champs non pertinents pour le type de véhicule
            if (this.typeVehicule === 'moto') {
                if (['boite', 'nb_portes', 'nb_places', 'taille_coffre', 'controle_technique'].includes(cle)) return;
            }
            
            const ancienne = this.valeursInitiales[cle];
            const nouvelle = valeursActuelles[cle];
            
            if (!this.sontValeursEquivalentes(ancienne, nouvelle)) {
                modifications.push({ cle, ancienne, nouvelle });
            }
        });
        
        // Ajout/suppression d'images
        const photosInitiales = this.valeursInitiales.nb_photos || 0;
        const photosFinales = valeursActuelles.nb_photos;
        const photosSupp = this.imagesASupprimer.length;
        const photosAjout = this.nouvellesImages.length;
        
        if (photosSupp > 0 || photosAjout > 0) {
            modifications.push({ cle: 'photos', ancienne: photosInitiales, nouvelle: photosFinales, detail: { supp: photosSupp, ajout: photosAjout } });
        }

        // Construction du HTML
        let html = '';
        
        if (modifications.length === 0) {
            html = '<div class="summary-empty"><i class="fas fa-info-circle"></i> Aucune modification détectée</div>';
        } else {
            html += '<div class="summary-header"><i class="fas fa-edit"></i> <strong>' + modifications.length + '</strong> modification(s) détectée(s)</div>';
            html += '<div class="summary-section">';
            
            modifications.forEach(modif => {
                html += this.genererLigneModification(modif.cle, modif.ancienne, modif.nouvelle, modif.detail);
            });
            
            html += '</div>';
        }

        summaryContent.innerHTML = html;
    }
    
    sontValeursEquivalentes(v1, v2) {
        // Traitement des valeurs vides
        const estVide1 = (v1 === null || v1 === undefined || v1 === '');
        const estVide2 = (v2 === null || v2 === undefined || v2 === '');
        
        if (estVide1 && estVide2) return true;
        if (estVide1 !== estVide2) return false;
        
        // Comparaison numérique si possible (pour éviter "211" !== "211.00")
        // On vérifie d'abord si ce sont des nombres valides et non des chaînes purement textuelles
        // (ex: "123 rue" ne doit pas être parsé comme 123)
        const isNumeric = (val) => !isNaN(parseFloat(val)) && isFinite(val);
        
        if (isNumeric(v1) && isNumeric(v2)) {
            return Math.abs(parseFloat(v1) - parseFloat(v2)) < 0.001;
        }
        
        // Comparaison string standard
        return String(v1).trim() === String(v2).trim();
    }

    genererLigneModification(cle, ancienne, nouvelle, detail = null) {
        const labels = {
            type: 'Type de véhicule',
            marque: 'Marque',
            modele: 'Modèle',
            annee: 'Année',
            prix: 'Prix',
            km: 'Kilométrage',
            ville: 'Ville',
            carburant: 'Carburant',
            type_hybride: 'Type hybride',
            boite: 'Boîte de vitesse',
            etat: 'État',
            couleur: 'Couleur',
            crit_air: 'Crit\'Air',
            nb_portes: 'Nb portes',
            nb_places: 'Nb places',
            taille_coffre: 'Taille coffre',
            puissance_cv: 'Puissance (CV)',
            norme_euro: 'Norme Euro',
            consommation: 'Consommation',
            consommation_secondaire: 'Conso. secondaire',
            emission_co2: 'Émissions CO₂',
            autonomie: 'Autonomie',
            controle_technique: 'Contrôle technique',
            provenance: 'Provenance',
            longueur: 'Longueur',
            largeur: 'Largeur',
            hauteur: 'Hauteur',
            nb_photos: 'Photos',
            photos: 'Photos'
        };
        
        const label = labels[cle] || cle;
        
        // Formatage spécial pour photos
        if (cle === 'photos' && detail) {
            let texte = '';
            if (detail.supp > 0) texte += `${detail.supp} supprimée(s)`;
            if (detail.ajout > 0) {
                if (texte) texte += ' • ';
                texte += `${detail.ajout} ajoutée(s)`;
            }
            return `
                <div class="summary-modification">
                    <div class="summary-modification-label">${label}</div>
                    <div class="summary-modification-change">
                        <span class="summary-old">${ancienne || 0} photo(s)</span>
                        <i class="fas fa-arrow-right"></i>
                        <span class="summary-new">${nouvelle || 0} photo(s)</span>
                    </div>
                    <div class="summary-modification-detail">${texte}</div>
                </div>
            `;
        }
        
        // Formatage des valeurs
        const valeurAncienne = this.formaterValeurRecap(cle, ancienne);
        const valeurNouvelle = this.formaterValeurRecap(cle, nouvelle);
        
        return `
            <div class="summary-modification">
                <div class="summary-modification-label">${label}</div>
                <div class="summary-modification-change">
                    <span class="summary-old">${valeurAncienne}</span>
                    <i class="fas fa-arrow-right"></i>
                    <span class="summary-new">${valeurNouvelle}</span>
                </div>
            </div>
        `;
    }
    
    formaterValeurRecap(cle, valeur) {
        if (valeur === null || valeur === undefined || valeur === '') return '<em>Non renseigné</em>';
        
        switch(cle) {
            case 'type': return this.formaterType(valeur);
            case 'prix': return `${this.formaterNombre(valeur)} €`;
            case 'km': return `${this.formaterNombre(valeur)} km`;
            case 'carburant': return this.formaterCarburant(valeur);
            case 'boite': return this.formaterBoite(valeur);
            case 'etat': return this.formaterEtat(valeur);
            case 'crit_air': return this.formaterCritAir(valeur);
            case 'taille_coffre': return this.formaterTailleCoffre(valeur);
            case 'controle_technique': return this.formaterControleTechnique(valeur);
            case 'puissance_cv': return `${valeur} CV`;
            case 'consommation': 
                // Adapté selon le carburant
                const carburant = document.getElementById('carburant')?.value;
                if (carburant === 'Électrique') {
                    return `${valeur} kWh/100km`;
                }
                return `${valeur} L/100km`;
            case 'consommation_secondaire': 
                // Adapté selon le type d'hybride
                const typeHybride = document.getElementById('type_hybride')?.value;
                if (typeHybride === 'gpl_essence') {
                    return `${valeur} L/100km`; // Essence
                }
                return `${valeur} kWh/100km`; // Électrique
            case 'type_hybride': return this.formaterTypeHybride(valeur);
            case 'emission_co2': return `${valeur} g/km`;
            case 'autonomie': return `${valeur} km`;
            case 'longueur': return `${valeur} m`;
            case 'largeur': return `${valeur} m`;
            case 'hauteur': return `${valeur} m`;
            case 'nb_portes': return `${valeur} portes`;
            case 'nb_places': return valeur === '6+' ? '6 ou plus' : `${valeur} places`;
            default: return valeur;
        }
    }

    // Formateurs (identiques à VueAjoutVehicule.js)
    formaterType(type) {
        const types = { 'voiture': '🚗 Voiture', 'moto': '🏍️ Moto', 'camion': '🚚 Camion' };
        return type ? types[type] : null;
    }

    formaterEtat(etat) {
        const etats = { 'neuf': '✨ Neuf', 'bon': '👍 Bon état', 'moyen': '👌 État moyen', 'mauvais': '👎 Mauvais état' };
        return etat ? etats[etat] : null;
    }

    formaterCarburant(carburant) {
        const carburants = { 'Essence': '🔴 Essence', 'Diesel': '⚫ Diesel', 'Hybride': '🟢 Hybride', 'Électrique': '🔵 Électrique', 'GPL': '🟡 GPL' };
        return carburant ? carburants[carburant] : null;
    }

    formaterBoite(boite) {
        const boites = { 'Manuelle': '⚙️ Manuelle', 'Automatique': '🅰️ Automatique' };
        return boite ? boites[boite] : null;
    }

    formaterTailleCoffre(taille) {
        const tailles = { 'petit': '🔹 Petit (< 300L)', 'moyen': '🔸 Moyen (300-500L)', 'grand': '🔶 Grand (> 500L)' };
        return taille ? tailles[taille] : null;
    }

    formaterCritAir(critAir) {
        if (!critAir) return null;
        const vignettes = { '0': '🟢 Crit\'Air 0', '1': '🟣 Crit\'Air 1', '2': '🟡 Crit\'Air 2', '3': '🟠 Crit\'Air 3', '4': '🟤 Crit\'Air 4', '5': '⚫ Crit\'Air 5' };
        return vignettes[critAir] || `Crit'Air ${critAir}`;
    }

    formaterControleTechnique(ct) {
        const statuts = { 'non_requis': '🔘 Non requis', 'oui': '✅ Oui, à jour', 'non': '❌ Non, à faire' };
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

    formaterNombre(n) {
        return new Intl.NumberFormat('fr-FR').format(n);
    }

    /**
     * Mise à jour de la prévisualisation
     */
    mettreAJourPrevisualisation() {
        if (!this.cartePreview) return;

        const marque = document.getElementById('marque')?.value || '';
        const modele = document.getElementById('modele')?.value || '';
        const prix = document.getElementById('prix')?.value || '';
        const annee = document.getElementById('annee')?.value || '';
        const km = document.getElementById('km')?.value || '';
        const carburant = document.getElementById('carburant')?.value || '';
        const ville = document.getElementById('ville')?.value || '';

        // Image
        const imgPreview = this.cartePreview.querySelector('.preview-card__image img');
        if (imgPreview) {
            // Utiliser l'image de couverture sélectionnée
            if (this.couvertureType === 'nouvelle' && this.nouvellesImages[this.couvertureIndex]) {
                const lecteur = new FileReader();
                lecteur.onload = (e) => {
                    imgPreview.src = e.target.result;
                    imgPreview.classList.remove('preview-card__placeholder');
                };
                lecteur.readAsDataURL(this.nouvellesImages[this.couvertureIndex]);
            } else if (this.couvertureType === 'existante') {
                const imagesRestantes = this.imagesExistantes.filter(img => !this.imagesASupprimer.includes(img));
                // Trouver l'index réel de la couverture après filtrage des supprimées
                const imageCouverture = this.imagesExistantes[this.couvertureIndex];
                if (imageCouverture && !this.imagesASupprimer.includes(imageCouverture)) {
                    imgPreview.src = imageCouverture;
                    imgPreview.classList.remove('preview-card__placeholder');
                } else if (imagesRestantes.length > 0) {
                    imgPreview.src = imagesRestantes[0];
                    imgPreview.classList.remove('preview-card__placeholder');
                } else {
                    // Pas d'image : Masquer l'img et afficher le placeholder CSS
                    imgPreview.style.display = 'none';
                    
                    // Créer ou réutiliser le conteneur placeholder
                    let placeholderDiv = this.cartePreview.querySelector('.placeholder-icon-container');
                    if (!placeholderDiv) {
                        placeholderDiv = document.createElement('div');
                        placeholderDiv.className = 'placeholder-icon-container';
                        placeholderDiv.style.cssText = 'width:100%; height:100%; background: #f1f5f9; display:flex; align-items:center; justify-content:center; color: #94a3b8;';
                        placeholderDiv.innerHTML = '<i class="fas fa-car fa-3x"></i>';
                        imgPreview.parentNode.appendChild(placeholderDiv);
                    } else {
                        placeholderDiv.style.display = 'flex';
                    }
                }
            }
        } else {
            // Si imgPreview n'existe pas, on essaie de trouver le conteneur image
            const containerImg = this.cartePreview.querySelector('.preview-card__image');
            if (containerImg) {
                let placeholderDiv = containerImg.querySelector('.placeholder-icon-container');
                if (!placeholderDiv) {
                    placeholderDiv = document.createElement('div');
                    placeholderDiv.className = 'placeholder-icon-container';
                    placeholderDiv.style.cssText = 'width:100%; height:100%; background: #f1f5f9; display:flex; align-items:center; justify-content:center; color: #94a3b8;';
                    placeholderDiv.innerHTML = '<i class="fas fa-car fa-3x"></i>';
                    containerImg.appendChild(placeholderDiv);
                }
            }
        }

        // Titre
        const previewTitle = document.getElementById('preview-title');
        if (previewTitle) {
            previewTitle.textContent = marque && modele ? `${marque} ${modele}` : 'Votre véhicule';
        }

        // Prix
        const previewPrice = document.getElementById('preview-price');
        if (previewPrice) {
            previewPrice.textContent = prix ? `${this.formaterNombre(prix)} €` : '-- €';
        }

        // Specs avec data-preview
        const anneePreview = this.cartePreview.querySelector('[data-preview="annee"]');
        if (anneePreview) anneePreview.textContent = annee || '--';

        const kmPreview = this.cartePreview.querySelector('[data-preview="km"]');
        if (kmPreview) kmPreview.textContent = km ? `${this.formaterNombre(km)} km` : '-- km';

        const carburantPreview = this.cartePreview.querySelector('[data-preview="carburant"]');
        if (carburantPreview) carburantPreview.textContent = carburant || '--';

        const boitePreview = this.cartePreview.querySelector('[data-preview="boite"]');
        const boite = document.getElementById('boite')?.value || '';
        if (boitePreview) boitePreview.textContent = boite || '--';

        // Ville
        const villePreview = this.cartePreview.querySelector('[data-preview="ville"]');
        if (villePreview) villePreview.textContent = ville || '--';
        
        // Badge photo
        const photoBadge = this.cartePreview.querySelector('.preview-card__photo-badge');
        const nbTotal = this.imagesExistantes.length - this.imagesASupprimer.length + this.nouvellesImages.length;
        if (photoBadge) {
            photoBadge.style.display = nbTotal > 0 ? 'flex' : 'none';
        }
    }

    /**
     * Affichage des messages
     */
    afficherMessage(texte, type = 'succes') {
        
        if (!this.messages) {
            console.error('Élément messages-formulaire introuvable !');
            alert(texte);
            return;
        }
        
        const iconMap = { succes: 'check-circle', erreur: 'exclamation-circle', info: 'info-circle' };
        const icon = iconMap[type] || 'info-circle';
        const cssClass = type === 'erreur' ? 'msg--err' : 'msg--ok';
        
        this.messages.innerHTML = `
            <div class="msg ${cssClass}">
                <i class="fas fa-${icon}"></i>
                <span>${texte}</span>
            </div>
        `;
        
        this.messages.style.display = 'block';
        this.messages.style.visibility = 'visible';
        this.messages.style.opacity = '1';
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        if (type === 'erreur') {
            return;
        }
        
        setTimeout(() => {
            if (this.messages && this.messages.innerHTML.includes(texte)) {
                this.messages.innerHTML = '';
            }
        }, 5000);
    }

    /**
     * Soumission du formulaire
     */
    async gererSoumission(e) {
        e.preventDefault();
        
        if (this.etapeActuelle !== 3) return;
        
        // Validation finale
        if (!this.validerEtapeActuelle()) return;
        
        const boutonEnregistrer = document.getElementById('bouton-enregistrer');
        if (boutonEnregistrer) {
            boutonEnregistrer.disabled = true;
            boutonEnregistrer.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
        }

        const formData = new FormData(this.formulaire);
        
        // 🔒 SÉCURITÉ : Nettoyer les champs selon le carburant et type hybride
        const carburant = document.getElementById('carburant')?.value;
        const typeHybride = document.getElementById('type_hybride')?.value;
        const champAutonomie = document.getElementById('autonomie');
        const champConsoSecondaire = document.getElementById('consommation_secondaire');
        const champTypeHybride = document.getElementById('type_hybride');
        
        // Types hybrides avec autonomie (PHEV uniquement)
        const typesAvecAutonomie = ['essence_electrique_rechargeable', 'diesel_electrique_rechargeable'];
        
        // Si pas électrique ET pas PHEV, forcer autonomie à vide
        if (carburant !== 'Électrique' && !typesAvecAutonomie.includes(typeHybride)) {
            if (champAutonomie) {
                champAutonomie.value = '';
            }
            formData.set('autonomie', '');
        }
        
        // Si pas hybride, vider la consommation secondaire et type_hybride
        if (carburant !== 'Hybride') {
            if (champConsoSecondaire) {
                champConsoSecondaire.value = '';
            }
            if (champTypeHybride) {
                champTypeHybride.value = '';
            }
            formData.set('consommation_secondaire', '');
            formData.set('type_hybride', '');
        }
        
        // Ajouter les images existantes conservées (non supprimées)
        const imagesConservees = this.imagesExistantes.filter(img => !this.imagesASupprimer.includes(img));
        formData.append('images_existantes', JSON.stringify(imagesConservees));
        
        // Ajouter les images à supprimer
        this.imagesASupprimer.forEach(img => {
            formData.append('images_a_supprimer[]', img);
        });
        
        // Ajouter les nouvelles images (le serveur attend 'images[]')
        this.nouvellesImages.forEach(fichier => {
            formData.append('images[]', fichier);
        });
        
        // Ajouter l'information de couverture
        formData.append('couverture_type', this.couvertureType);
        formData.append('couverture_index', this.couvertureIndex);

        try {
            // Ajout de l'ID dans l'URL pour que le contrôleur puisse le récupérer via $_GET['id']
            const res = await fetch(`${this.urlApiModification}?id=${this.idVehicule}`, {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (res.ok) {
                this.afficherMessage('✅ Modifications enregistrées avec succès !', 'succes');
                setTimeout(() => {
                    window.location.href = `vehicule?id=${this.idVehicule}`;
                }, 1500);
            } else {
                throw new Error(data.error || 'Erreur lors de la modification');
            }
        } catch (err) {
            this.afficherMessage(err.message, 'erreur');
            if (boutonEnregistrer) {
                boutonEnregistrer.disabled = false;
                boutonEnregistrer.innerHTML = '<i class="fas fa-save"></i> Enregistrer les modifications';
            }
        }
    }

    /**
     * Confirmation de suppression
     */
    confirmerSuppression() {
        if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer cette annonce ? Cette action est irréversible.')) {
            this.supprimerVehicule();
        }
    }

    async supprimerVehicule() {
        try {
            const res = await fetch(obtenirUrlApi('/vehicule/supprimer'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: this.idVehicule })
            });

            const data = await res.json();

            if (res.ok) {
                this.afficherMessage('✅ Annonce supprimée avec succès', 'succes');
                setTimeout(() => {
                    window.location.href = 'galerie';
                }, 1500);
            } else {
                throw new Error(data.error || 'Erreur lors de la suppression');
            }
        } catch (err) {
            this.afficherMessage(err.message, 'erreur');
        }
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
}