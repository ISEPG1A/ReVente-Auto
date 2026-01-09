/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE DÉTAILS - AFFICHAGE COMPLET D'UNE ANNONCE VÉHICULE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe gère la page de détails d'un véhicule avec :
 * - Chargement des données complètes depuis l'API
 * - Galerie d'images avec miniatures cliquables
 * - Carte de localisation via Leaflet (chargé dynamiquement)
 * - Gestion des favoris (ajout/retrait)
 * - Actions propriétaire (modification, suppression)
 * - Lien vers la messagerie pour contacter le vendeur
 * 
 * Chargement Leaflet :
 * - La bibliothèque Leaflet est chargée dynamiquement au besoin
 * - Si le chargement échoue, la page s'affiche sans carte
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @see     ControleurVehiculeDetails (PHP) Pour la récupération des données
 * @see     https://leafletjs.com/ Documentation Leaflet
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { formaterMonnaie, echapperHTML, obtenirUrlApi } from '../../application.js';
import GestionnaireSuppression from '../commun/GestionnaireSuppression.js';

export default class VueDetails {
    
    /**
     * Initialise la vue détails avec l'ID du véhicule
     * 
     * Récupère l'ID depuis l'URL et configure les URLs des API.
     */
    constructor() {
        /** @type {string|null} ID du véhicule (depuis l'URL) */
        this.idVehicule = new URLSearchParams(window.location.search).get('id');
        
        /** @type {string} URL de l'API des détails véhicule */
        this.urlApi = obtenirUrlApi('/vehicule/details');
        
        /** @type {string} URL de l'API d'authentification */
        this.urlAuth = obtenirUrlApi('/connexion');
        
        /** @type {string} URL de l'API des favoris */
        this.urlFavoris = obtenirUrlApi('/favoris');
        
        /** @type {boolean} Indique si le véhicule est en favoris */
        this.estFavori = false;
        
        /** @type {Object|null} Données de l'utilisateur connecté */
        this.utilisateurConnecte = null;
        
        // Gestionnaire de suppression centralisé
        this.gestionnaireSuppression = new GestionnaireSuppression({
            onSuccess: () => {
                alert('✅ Annonce supprimée avec succès.');
                window.location.href = 'mes-annonces';
            },
            onError: (error) => alert('❌ Erreur: ' + error)
        });
        
        this.initialiser();
    }

    /**
     * Initialise la vue : charge Leaflet puis les détails du véhicule
     * 
     * Leaflet est chargé dynamiquement pour éviter de bloquer
     * le chargement initial si la bibliothèque n'est pas disponible.
     */
    initialiser() {
        // Références aux éléments DOM d'état
        this.elChargement = document.getElementById('chargement-details');
        this.elErreur = document.getElementById('erreur-details');
        this.elContenu = document.getElementById('contenu-details');
        
        // Initialiser les composants Score IA et Localisation
        this.initialiserComposants();
        
        // Vérification et chargement de Leaflet pour la carte
        if (!window.L) {
            // Chargement du CSS de Leaflet
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            link.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
            link.crossOrigin = '';
            document.head.appendChild(link);

            // Chargement du script Leaflet
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
            script.crossOrigin = '';
            
            // Chargement des détails après le script (succès ou échec)
            script.onload = () => this.chargerDetails();
            script.onerror = () => {
                console.warn("Impossible de charger Leaflet (carte).");
                this.chargerDetails();
            };

            document.head.appendChild(script);
        } else {
            this.chargerDetails();
        }
    }
    
    /**
     * Initialise les composants Score IA et Localisation
     */
    async initialiserComposants() {
        try {
            // Import dynamique des composants
            const [{ default: VueScoreIA }, { default: VueLocalisation }] = await Promise.all([
                import('../../ScoreIA/VueScoreIA.js'),
                import('../../Localisation/VueLocalisation.js')
            ]);
            
            // Initialiser le score IA
            if (this.idVehicule) {
                const scoreIA = new VueScoreIA();
                scoreIA.init(this.idVehicule);
            }
            
            // La localisation sera initialisée par afficherDetails après le chargement du véhicule
            window.vueLocalisation = new VueLocalisation();
        } catch (erreur) {
            console.error('Erreur lors du chargement des composants:', erreur);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MÉTHODES DE FORMATAGE DES VALEURS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Formate le contrôle technique pour un affichage lisible
     * @param {string} valeur - Valeur brute (oui, non, non_requis, a_faire)
     * @returns {string} Valeur formatée
     */
    formaterControleTechnique(valeur) {
        if (!valeur) return 'Non spécifié';
        const formats = {
            'oui': '✅ Valide',
            'non': '❌ Non valide',
            'non_requis': '➖ Non requis',
            'a_faire': '⚠️ À faire'
        };
        return formats[valeur.toLowerCase()] || valeur;
    }

    /**
     * Formate le type d'hybride pour un affichage lisible
     * @param {string} valeur - Valeur brute (essence_electrique, etc.)
     * @returns {string} Valeur formatée
     */
    formaterTypeHybride(valeur) {
        if (!valeur) return 'Non spécifié';
        const formats = {
            'essence_electrique': 'Essence + Électrique (HEV)',
            'essence_electrique_rechargeable': 'Essence + Électrique rechargeable (PHEV)',
            'diesel_electrique': 'Diesel + Électrique (HEV)',
            'diesel_electrique_rechargeable': 'Diesel + Électrique rechargeable (PHEV)',
            'gpl_essence': 'GPL + Essence'
        };
        return formats[valeur.toLowerCase()] || valeur;
    }

    /**
     * Formate l'état du véhicule pour un affichage lisible
     * @param {string} valeur - Valeur brute (neuf, bon, moyen, mauvais)
     * @returns {string} Valeur formatée
     */
    formaterEtat(valeur) {
        if (!valeur) return 'Non spécifié';
        const formats = {
            'neuf': '✨ Neuf',
            'bon': '👍 Bon état',
            'moyen': '👌 État moyen',
            'mauvais': '👎 Mauvais état'
        };
        return formats[valeur.toLowerCase()] || valeur;
    }

    /**
     * Formate la boîte de vitesse pour un affichage lisible
     * @param {string} valeur - Valeur brute (Manuelle, Automatique)
     * @returns {string} Valeur formatée
     */
    formaterBoite(valeur) {
        if (!valeur) return 'Non spécifié';
        const formats = {
            'manuelle': '⚙️ Manuelle',
            'automatique': '🅰️ Automatique'
        };
        return formats[valeur.toLowerCase()] || valeur;
    }

    /**
     * Formate le carburant pour un affichage lisible
     * @param {string} valeur - Valeur brute
     * @returns {string} Valeur formatée
     */
    formaterCarburant(valeur) {
        if (!valeur) return 'Non spécifié';
        const formats = {
            'essence': '🔴 Essence',
            'diesel': '⚫ Diesel',
            'hybride': '🟢 Hybride',
            'électrique': '🔵 Électrique',
            'electrique': '🔵 Électrique',
            'gpl': '🟡 GPL'
        };
        return formats[valeur.toLowerCase()] || valeur;
    }

    /**
     * Formate la taille du coffre pour un affichage lisible
     * @param {string} valeur - Valeur brute (petit, moyen, grand)
     * @returns {string} Valeur formatée
     */
    formaterTailleCoffre(valeur) {
        if (!valeur) return 'Non spécifié';
        const formats = {
            'petit': '🔹 Petit (< 300L)',
            'moyen': '🔸 Moyen (300-500L)',
            'grand': '🔶 Grand (> 500L)'
        };
        return formats[valeur.toLowerCase()] || valeur;
    }

    /**
     * Formate la vignette Crit'Air pour un affichage lisible
     * @param {string} valeur - Valeur brute (0, 1, 2, 3, 4, 5)
     * @returns {string} Valeur formatée
     */
    formaterCritAir(valeur) {
        if (!valeur) return 'Non spécifié';
        const formats = {
            '0': '🟢 Crit\'Air 0 (Électrique)',
            '1': '🟣 Crit\'Air 1',
            '2': '🟡 Crit\'Air 2',
            '3': '🟠 Crit\'Air 3',
            '4': '🟤 Crit\'Air 4',
            '5': '⚫ Crit\'Air 5'
        };
        return formats[valeur] || `Crit'Air ${valeur}`;
    }

    /**
     * Formate la norme Euro pour un affichage lisible
     * @param {string} valeur - Valeur brute (Euro 1, Euro 2, etc.)
     * @returns {string} Valeur formatée
     */
    formaterNormeEuro(valeur) {
        if (!valeur) return 'Non spécifié';
        // Ajouter l'icône environnementale
        const numero = valeur.replace(/[^0-9]/g, '');
        if (numero >= 6) return `🌿 ${valeur}`;
        if (numero >= 5) return `🌱 ${valeur}`;
        return `📋 ${valeur}`;
    }

    /**
     * Charge les détails du véhicule et les informations utilisateur
     * 
     * Vérifie d'abord l'authentification pour déterminer si l'utilisateur
     * est le propriétaire ou peut ajouter en favoris.
     * 
     * @async
     */
    async chargerDetails() {
        if (!this.idVehicule) {
            this.afficherErreur("Aucun véhicule spécifié.");
            return;
        }

        try {
            // Tentative de récupération de l'utilisateur connecté
            let utilisateurCourant = null;
            try {
                const reponseUtilisateur = await fetch(`${this.urlAuth}?action=me`);
                if (reponseUtilisateur.ok) {
                    const donneesUtilisateur = await reponseUtilisateur.json();
                    utilisateurCourant = donneesUtilisateur.user;
                    this.utilisateurConnecte = utilisateurCourant;
                }
            } catch (erreur) {
                console.warn("Utilisateur non connecté ou erreur d'authentification", erreur);
            }

            // Vérification du statut favori si connecté
            if (utilisateurCourant) {
                await this.verifierFavori();
            }

            // Récupération des détails du véhicule
            const reponse = await fetch(`${this.urlApi}?id=${this.idVehicule}`);
            if (!reponse.ok) {
                throw new Error("Véhicule introuvable ou erreur serveur.");
            }
            
            const vehicule = await reponse.json();
            this.afficherDetails(vehicule, utilisateurCourant);
            
        } catch (erreur) {
            console.error('❌ Erreur lors du chargement:', erreur);
            this.afficherErreur(erreur.message);
        }
    }

    /**
     * Affiche un message d'erreur et masque le contenu
     * 
     * @param {string} message - Message d'erreur à afficher
     */
    afficherErreur(message) {
        if (this.elChargement) this.elChargement.hidden = true;
        if (this.elErreur) {
            this.elErreur.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
            `;
            this.elErreur.hidden = false;
        }
    }

    /**
     * Affiche les détails complets du véhicule
     * 
     * Configure l'affichage selon le rôle de l'utilisateur et le type de véhicule :
     * - Propriétaire : boutons modifier/supprimer
     * - Visiteur connecté : bouton favori
     * - Visiteur non connecté : bouton contact
     * - Type véhicule : adapte les champs affichés (voiture/moto/camion)
     * 
     * @param {Object} vehicule - Données du véhicule
     * @param {Object|null} utilisateurCourant - Utilisateur connecté
     */
    afficherDetails(vehicule, utilisateurCourant) {
        if (this.elChargement) this.elChargement.hidden = true;
        if (this.elContenu) this.elContenu.hidden = false;

        // Type de véhicule pour conditionner l'affichage
        const typeVehicule = vehicule.type_vehicule || 'voiture';
        
        // Détermination du rôle de l'utilisateur
        const idVendeur = vehicule.user_id || vehicule.seller_id;
        const estProprietaire = utilisateurCourant && idVendeur && Number(idVendeur) === Number(utilisateurCourant.id);
        const estAdmin = utilisateurCourant && utilisateurCourant.role === 'admin';

        // Configuration du bouton favori (masqué pour le propriétaire)
        if (!estProprietaire) {
            this.configurerBoutonFavori();
        } else {
            const btnFavori = document.getElementById('btn-favori-detail');
            if (btnFavori) btnFavori.style.display = 'none';
        }

        // Valeurs par défaut pour les champs optionnels
        const km = vehicule.km ?? 0;
        const carburant = vehicule.carburant ?? 'Non spécifié';
        const boite = vehicule.boite ?? 'Non spécifié';
        const description = vehicule.description ?? "Aucune description fournie pour ce véhicule.";
        const ville = vehicule.ville ?? "Non spécifié";
        const etat = vehicule.etat ?? 'Non spécifié';

        // ═══════════════════════════════════════════════════════════════════
        // ICÔNE SELON LE TYPE DE VÉHICULE
        // ═══════════════════════════════════════════════════════════════════
        const iconType = document.getElementById('icon-type-vehicule');
        if (iconType) {
            const iconsMap = {
                'voiture': 'fa-car',
                'moto': 'fa-motorcycle',
                'camion': 'fa-truck'
            };
            iconType.className = `fas ${iconsMap[typeVehicule] || 'fa-car'}`;
        }

        // ═══════════════════════════════════════════════════════════════════
        // GALERIE D'IMAGES
        // ═══════════════════════════════════════════════════════════════════
        
        const images = (vehicule.images && vehicule.images.length > 0) ? vehicule.images : (vehicule.image_path ? [vehicule.image_path] : []);
        const conteneurImage = document.querySelector('.conteneur-image-principale');
        const rangeeMiniatures = document.querySelector('.rangee-miniatures');

        if (images.length > 0) {
             const afficherImagePrincipale = (src) => {
                 if (conteneurImage) conteneurImage.innerHTML = `<img src="${src}" alt="${vehicule.marque} ${vehicule.modele}" style="width:100%; height:100%; object-fit:cover;">`;
             };
             
             afficherImagePrincipale(images[0]);

             if (rangeeMiniatures) {
                 rangeeMiniatures.innerHTML = '';
                 if (images.length > 1) {
                     images.forEach((src, index) => {
                         const div = document.createElement('div');
                         div.className = `miniature ${index === 0 ? 'active' : ''}`;
                         div.innerHTML = `<img src="${src}" style="width:100%; height:100%; object-fit:cover; border-radius:4px;">`;
                         div.onclick = () => {
                             afficherImagePrincipale(src);
                             document.querySelectorAll('.miniature').forEach(m => m.classList.remove('active'));
                             div.classList.add('active');
                         };
                         rangeeMiniatures.appendChild(div);
                     });
                 }
             }
        } else {
             const iconsMap = { 'voiture': 'fa-car', 'moto': 'fa-motorcycle', 'camion': 'fa-truck' };
             if (conteneurImage) conteneurImage.innerHTML = `
                <div class="image-placeholder-lg">
                    <i class="fas ${iconsMap[typeVehicule] || 'fa-car'} fa-5x"></i>
                </div>`;
             if (rangeeMiniatures) rangeeMiniatures.innerHTML = '';
        }

        // ═══════════════════════════════════════════════════════════════════
        // HELPERS POUR MISE À JOUR DU DOM
        // ═══════════════════════════════════════════════════════════════════
        const setContent = (id, text) => {
            const el = document.getElementById(id);
            if (el) el.textContent = text;
        };

        const setTag = (id, text) => {
            const el = document.getElementById(id);
            if (el) el.querySelector('span').textContent = text;
        };

        const showHide = (id, show) => {
            const el = document.getElementById(id);
            if (el) el.style.display = show ? 'flex' : 'none';
        };

        const showHideByType = (selector) => {
            document.querySelectorAll(`[data-type]`).forEach(el => {
                const types = el.dataset.type.split(',');
                el.style.display = types.includes(typeVehicule) ? 'flex' : 'none';
            });
        };

        // ═══════════════════════════════════════════════════════════════════
        // EN-TÊTE ET PRIX
        // ═══════════════════════════════════════════════════════════════════
        setContent('titre-detail', `${vehicule.marque} ${vehicule.modele}`);
        setContent('sous-titre-detail', `${typeVehicule.charAt(0).toUpperCase() + typeVehicule.slice(1)} • Réf. #${vehicule.id} • Publié le ${new Date(vehicule.created_at).toLocaleDateString()}`);
        setContent('prix-detail', formaterMonnaie(vehicule.prix));
        
        // Badges dans l'en-tête
        const badgeTypeHeader = document.getElementById('badge-type-header');
        if (badgeTypeHeader) {
            const iconsMap = { 'voiture': 'fa-car', 'moto': 'fa-motorcycle', 'camion': 'fa-truck' };
            badgeTypeHeader.innerHTML = `<i class="fas ${iconsMap[typeVehicule] || 'fa-tag'}"></i> <span>${typeVehicule.charAt(0).toUpperCase() + typeVehicule.slice(1)}</span>`;
        }
        
        const badgeEtatHeader = document.getElementById('badge-etat-header');
        if (badgeEtatHeader) {
            badgeEtatHeader.innerHTML = `<i class="fas fa-star"></i> <span>${etat}</span>`;
        }
        
        // ═══════════════════════════════════════════════════════════════════
        // INFOS RAPIDES
        // ═══════════════════════════════════════════════════════════════════
        setContent('quick-annee', vehicule.annee);
        setContent('quick-km', km.toLocaleString() + ' km');
        setContent('quick-carburant', carburant);
        
        // Boîte de vitesse (masquée pour moto)
        const quickBoite = document.getElementById('quick-boite-container');
        if (quickBoite) {
            if (typeVehicule === 'moto') {
                quickBoite.style.display = 'none';
            } else {
                quickBoite.style.display = 'flex';
                setContent('quick-boite', boite);
            }
        }
        
        // Ancienne structure (info-*) - On la remplit aussi pour compatibilité
        setTag('info-annee', vehicule.annee);
        setTag('info-km', km.toLocaleString() + ' km');
        setTag('info-carburant', carburant);
        
        // Boîte de vitesse (masquée pour moto)
        const infoBoite = document.getElementById('info-boite');
        if (infoBoite) {
            if (typeVehicule === 'moto') {
                infoBoite.style.display = 'none';
            } else {
                infoBoite.style.display = 'flex';
                infoBoite.querySelector('span').textContent = boite;
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // BADGES SUR L'IMAGE (ancienne structure)
        // ═══════════════════════════════════════════════════════════════════
        const badgeType = document.getElementById('badge-type');
        if (badgeType) {
            const iconsMap = { 'voiture': 'fa-car', 'moto': 'fa-motorcycle', 'camion': 'fa-truck' };
            badgeType.innerHTML = `<i class="fas ${iconsMap[typeVehicule] || 'fa-tag'}"></i> <span>${typeVehicule.charAt(0).toUpperCase() + typeVehicule.slice(1)}</span>`;
        }
        
        const badgeAnnee = document.getElementById('badge-annee');
        if (badgeAnnee) {
            badgeAnnee.innerHTML = `<i class="fas fa-calendar-alt"></i> <span>${vehicule.annee}</span>`;
        }
        
        const badgeEtat = document.getElementById('badge-etat');
        if (badgeEtat) {
            badgeEtat.innerHTML = `<i class="fas fa-star"></i> <span>${etat}</span>`;
        }

        // ═══════════════════════════════════════════════════════════════════
        // VENDEUR
        // ═══════════════════════════════════════════════════════════════════
        const nomVendeur = (vehicule.seller_first_name || vehicule.seller_last_name) 
            ? `${vehicule.seller_first_name || ''} ${vehicule.seller_last_name || ''}`.trim() 
            : 'Vendeur inconnu';
        setContent('nom-vendeur', nomVendeur);
        
        const avatarVendeur = document.querySelector('.details-seller__avatar');
        if (avatarVendeur) {
            if (vehicule.seller_avatar) {
                avatarVendeur.innerHTML = `<img src="${echapperHTML(vehicule.seller_avatar)}" alt="Vendeur" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`;
                avatarVendeur.style.overflow = 'hidden';
            } else {
                avatarVendeur.innerHTML = `<i class="fas fa-user"></i>`;
            }
        }
        
        // ═══════════════════════════════════════════════════════════════════
        // LOCALISATION & CARTE
        // ═══════════════════════════════════════════════════════════════════
        // Initialiser le composant Localisation avec code postal
        const codePostal = vehicule.code_postal || null;
        const villeAffichage = codePostal ? `${ville} (${codePostal})` : ville;
        if (window.vueLocalisation) {
            window.vueLocalisation.init(ville, codePostal, villeAffichage);
        }

        // ═══════════════════════════════════════════════════════════════════
        // DESCRIPTION
        // ═══════════════════════════════════════════════════════════════════
        const descriptionEl = document.getElementById('description-detail');
        if (descriptionEl) {
            if (description && description.trim() && description !== "Aucune description fournie pour ce véhicule.") {
                descriptionEl.textContent = description;
                descriptionEl.style.opacity = '1';
                descriptionEl.style.fontStyle = 'normal';
            } else {
                descriptionEl.textContent = 'Aucune description disponible';
                descriptionEl.style.opacity = '0.5';
                descriptionEl.style.fontStyle = 'italic';
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // CARACTÉRISTIQUES GÉNÉRALES
        // ═══════════════════════════════════════════════════════════════════
        setContent('spec-marque', vehicule.marque);
        setContent('spec-modele', vehicule.modele);
        setContent('spec-annee', vehicule.annee);
        setContent('spec-km', km.toLocaleString() + ' km');
        setContent('spec-etat', this.formaterEtat(vehicule.etat));
        setContent('spec-couleur', vehicule.couleur || 'Non spécifié');
        setContent('spec-provenance', vehicule.provenance || 'Non spécifié');
        setContent('spec-controle-technique', this.formaterControleTechnique(vehicule.controle_technique));

        // ═══════════════════════════════════════════════════════════════════
        // MOTORISATION
        // ═══════════════════════════════════════════════════════════════════
        setContent('spec-carburant', this.formaterCarburant(carburant));
        
        // Type hybride (si applicable)
        if (vehicule.type_hybride && (carburant.toLowerCase().includes('hybride') || carburant.toLowerCase() === 'hybride')) {
            setContent('spec-type-hybride', this.formaterTypeHybride(vehicule.type_hybride));
            showHide('spec-type-hybride-container', true);
        } else {
            showHide('spec-type-hybride-container', false);
        }
        
        // Boîte de vitesse (voiture/camion)
        if (typeVehicule === 'moto') {
            showHide('spec-boite-container', false);
        } else {
            setContent('spec-boite', this.formaterBoite(boite));
            showHide('spec-boite-container', true);
        }
        
        setContent('spec-puissance', vehicule.puissance_cv ? `${vehicule.puissance_cv} cv` : 'Non spécifié');

        // ═══════════════════════════════════════════════════════════════════
        // HABITABILITÉ (voiture/camion uniquement)
        // ═══════════════════════════════════════════════════════════════════
        const sectionHabitabilite = document.getElementById('section-habitabilite');
        if (sectionHabitabilite) {
            sectionHabitabilite.style.display = (typeVehicule === 'moto') ? 'none' : 'block';
        }
        
        // Nombre de portes (voiture uniquement)
        if (typeVehicule === 'voiture') {
            setContent('spec-portes', vehicule.nb_portes || 'Non spécifié');
            showHide('spec-portes-container', true);
        } else {
            showHide('spec-portes-container', false);
        }
        
        // Nombre de places
        if (typeVehicule !== 'moto') {
            setContent('spec-places', vehicule.nb_places || 'Non spécifié');
            showHide('spec-places-container', true);
        } else {
            showHide('spec-places-container', false);
        }
        
        // Volume coffre / Charge utile
        if (typeVehicule !== 'moto') {
            const labelCoffre = document.getElementById('label-coffre');
            if (labelCoffre) {
                labelCoffre.textContent = typeVehicule === 'camion' ? 'Charge utile' : 'Volume coffre';
            }
            setContent('spec-coffre', this.formaterTailleCoffre(vehicule.taille_coffre));
            showHide('spec-coffre-container', true);
        } else {
            showHide('spec-coffre-container', false);
        }

        // ═══════════════════════════════════════════════════════════════════
        // DIMENSIONS - Afficher/masquer la section complète si aucune dimension
        // ═══════════════════════════════════════════════════════════════════
        const afficherDimension = (id, valeur, unite) => {
            if (valeur) {
                setContent(id, `${valeur} ${unite}`);
                showHide(`${id}-container`, true);
                return true;
            } else {
                showHide(`${id}-container`, false);
                return false;
            }
        };
        
        const hasLongueur = afficherDimension('spec-longueur', vehicule.longueur, 'm');
        const hasLargeur = afficherDimension('spec-largeur', vehicule.largeur, 'm');
        const hasHauteur = afficherDimension('spec-hauteur', vehicule.hauteur, 'm');
        
        // Masquer la section Dimensions si aucune dimension n'est renseignée
        const sectionDimensions = document.getElementById('section-dimensions');
        if (sectionDimensions) {
            sectionDimensions.style.display = (hasLongueur || hasLargeur || hasHauteur) ? 'block' : 'none';
        }

        // ═══════════════════════════════════════════════════════════════════
        // ENVIRONNEMENT & CONSOMMATION
        // ═══════════════════════════════════════════════════════════════════
        setContent('spec-crit-air', this.formaterCritAir(vehicule.crit_air));
        setContent('spec-norme-euro', this.formaterNormeEuro(vehicule.norme_euro));
        setContent('spec-emission-co2', vehicule.emission_co2 ? `${vehicule.emission_co2} g/km` : 'Non spécifié');
        
        // Consommation principale - label dynamique selon carburant/type hybride
        const labelConsommation = document.getElementById('label-consommation');
        const labelConsoSecondaire = document.querySelector('#spec-conso-secondaire-container .details-spec__label');
        
        if (labelConsommation) {
            const carburantLower = carburant.toLowerCase();
            const typeHybride = vehicule.type_hybride || '';
            
            if (carburantLower === 'électrique') {
                labelConsommation.textContent = 'Consommation électrique';
            } else if (carburantLower === 'hybride' && typeHybride) {
                // Hybride : adapter selon le type
                switch(typeHybride) {
                    case 'essence_electrique':
                    case 'essence_electrique_rechargeable':
                        labelConsommation.textContent = 'Consommation essence';
                        break;
                    case 'diesel_electrique':
                    case 'diesel_electrique_rechargeable':
                        labelConsommation.textContent = 'Consommation diesel';
                        break;
                    case 'gpl_essence':
                        labelConsommation.textContent = 'Consommation GPL';
                        break;
                    default:
                        labelConsommation.textContent = 'Consommation';
                }
            } else if (carburantLower === 'essence') {
                labelConsommation.textContent = 'Consommation essence';
            } else if (carburantLower === 'diesel') {
                labelConsommation.textContent = 'Consommation diesel';
            } else if (carburantLower === 'gpl') {
                labelConsommation.textContent = 'Consommation GPL';
            } else {
                labelConsommation.textContent = 'Consommation';
            }
        }
        
        // Unité de consommation
        const uniteConsommation = carburant.toLowerCase() === 'électrique' ? 'kWh/100km' : 'L/100km';
        setContent('spec-consommation', vehicule.consommation ? `${vehicule.consommation} ${uniteConsommation}` : 'Non spécifié');
        
        // Consommation secondaire (pour hybrides)
        const typeHybride = vehicule.type_hybride || '';
        if (vehicule.consommation_secondaire && carburant.toLowerCase() === 'hybride' && typeHybride) {
            // Déterminer le label de la consommation secondaire
            let labelSecondaire = 'Consommation secondaire';
            let uniteSecondaire = 'L/100km';
            
            switch(typeHybride) {
                case 'essence_electrique':
                case 'essence_electrique_rechargeable':
                case 'diesel_electrique':
                case 'diesel_electrique_rechargeable':
                    labelSecondaire = 'Consommation électrique';
                    uniteSecondaire = 'kWh/100km';
                    break;
                case 'gpl_essence':
                    labelSecondaire = 'Consommation essence';
                    uniteSecondaire = 'L/100km';
                    break;
            }
            
            if (labelConsoSecondaire) {
                labelConsoSecondaire.textContent = labelSecondaire;
            }
            setContent('spec-consommation-secondaire', `${vehicule.consommation_secondaire} ${uniteSecondaire}`);
            showHide('spec-conso-secondaire-container', true);
        } else {
            showHide('spec-conso-secondaire-container', false);
        }
        
        // Autonomie (électrique/hybride)
        if (vehicule.autonomie && (carburant.toLowerCase() === 'électrique' || carburant.toLowerCase().includes('hybride'))) {
            const labelAutonomie = document.getElementById('label-autonomie');
            if (labelAutonomie) {
                labelAutonomie.textContent = carburant.toLowerCase() === 'électrique' ? 'Autonomie' : 'Autonomie électrique';
            }
            setContent('spec-autonomie', `${vehicule.autonomie} km`);
            showHide('spec-autonomie-container', true);
        } else {
            showHide('spec-autonomie-container', false);
        }

        // ═══════════════════════════════════════════════════════════════════
        // ACTIONS VENDEUR / VISITEUR
        // ═══════════════════════════════════════════════════════════════════
        const carteVendeur = document.querySelector('.details-card--seller');
        const actionsVendeur = document.querySelector('.details-seller__actions');
        
        if (estProprietaire || estAdmin) {
            // Afficher la section des actions propriétaire
            const actionsProprietaire = document.getElementById('actions-proprietaire');
            if (actionsProprietaire) {
                actionsProprietaire.hidden = false;
                
                // Configurer le lien de modification
                const lienModifier = document.getElementById('lien-modifier-detail');
                if (lienModifier) {
                    lienModifier.href = `modification_vehicule?id=${vehicule.id}`;
                }
                
                // Configurer le bouton de suppression
                const boutonSupprimer = document.getElementById('bouton-supprimer-detail');
                if (boutonSupprimer) {
                    boutonSupprimer.onclick = () => this.ouvrirModalSuppression(vehicule);
                }
            }
            
            // Masquer les actions visiteur
            if (actionsVendeur) {
                actionsVendeur.style.display = 'none';
            }
        } else {
            const boutonTelephone = document.getElementById('bouton-telephone');
            if (boutonTelephone) {
                if (vehicule.seller_phone) {
                    boutonTelephone.onclick = () => {
                        boutonTelephone.innerHTML = `<i class="fas fa-phone"></i> <span>${echapperHTML(vehicule.seller_phone)}</span>`;
                        boutonTelephone.classList.add('details-btn--revealed');
                    };
                } else {
                    boutonTelephone.style.display = 'none';
                }
            }
            
            const boutonContact = document.getElementById('bouton-contact');
            if (boutonContact) {
                boutonContact.onclick = () => {
                    window.location.href = `messagerie?vehicle_id=${vehicule.id}&seller_id=${vehicule.user_id || vehicule.seller_id}`;
                };
            }
        }
    }

    /**
     * Vérifie si le véhicule est dans les favoris de l'utilisateur
     */
    async verifierFavori() {
        try {
            const res = await fetch(`${this.urlFavoris}?ids_only=1`);
            if (res.ok) {
                const ids = await res.json();
                this.estFavori = ids.includes(parseInt(this.idVehicule));
                this.mettreAJourBoutonFavori();
            }
        } catch (e) {
            console.warn("Erreur vérification favoris", e);
        }
    }

    /**
     * Met à jour l'apparence du bouton favori
     */
    mettreAJourBoutonFavori() {
        const btnFavori = document.getElementById('btn-favori-detail');
        if (!btnFavori) return;

        if (this.estFavori) {
            btnFavori.innerHTML = '<i class="fas fa-heart"></i><span>Retirer</span>';
            btnFavori.classList.add('active');
            btnFavori.title = 'Retirer des favoris';
        } else {
            btnFavori.innerHTML = '<i class="far fa-heart"></i><span>Sauvegarder</span>';
            btnFavori.classList.remove('active');
            btnFavori.title = 'Ajouter aux favoris';
        }
    }

    /**
     * Configure le bouton favori
     */
    configurerBoutonFavori() {
        const btnFavori = document.getElementById('btn-favori-detail');
        if (!btnFavori) return;

        btnFavori.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            // Vérifier si l'utilisateur est connecté
            if (!this.utilisateurConnecte) {
                if (confirm('Vous devez être connecté pour ajouter aux favoris. Voulez-vous vous connecter ?')) {
                    window.location.href = 'connexion';
                }
                return;
            }

            try {
                if (this.estFavori) {
                    // Retirer des favoris
                    const res = await fetch(`${this.favorisUrl}?id=${this.idVehicule}`, {
                        method: 'DELETE'
                    });
                    if (res.ok) {
                        this.estFavori = false;
                        this.mettreAJourBoutonFavori();
                    }
                } else {
                    // Ajouter aux favoris
                    const res = await fetch(this.favorisUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ vehicle_id: parseInt(this.idVehicule) })
                    });
                    if (res.ok) {
                        this.estFavori = true;
                        this.mettreAJourBoutonFavori();
                    }
                }
            } catch (e) {
                console.error("Erreur favori", e);
            }
        });

        // Mettre à jour l'apparence initiale
        this.mettreAJourBoutonFavori();
    }
    
    /**
     * Ouvre le modal de confirmation de suppression
     */
    ouvrirModalSuppression(vehicule) {
        this.gestionnaireSuppression.ouvrir({
            id: vehicule.id,
            marque: vehicule.marque,
            modele: vehicule.modele
        });
    }
}