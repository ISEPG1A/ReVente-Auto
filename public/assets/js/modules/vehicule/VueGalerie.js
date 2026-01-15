/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE GALERIE - AFFICHAGE ET FILTRAGE DES ANNONCES VÉHICULES
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi, afficherNotificationGlobale } from '../../application.js';

export default class VueGalerie {
    
    constructor() {
        this.urlApi = obtenirUrlApi('/vehicule/galerie');
        
        this.etat = {
            vehicules: [],
            filtres: [],
            favoris: [],
            criteres: {
                type: '',
                marque: '',
                anneeMin: null,
                anneeMax: null,
                prixMin: null,
                prixMax: null,
                kmMin: null,
                kmMax: null,
                carburant: [],
                boite: [],
                etat: [],
                crit_air: [],
                nb_portes: [],
                controle_technique: [],
                recherche: '',
                // Localisation
                latitude: null,
                longitude: null,
                rayon: 20,
                nomLocalisation: ''
            },
            tri: 'recent',
            utilisateur: null,
            pagination: {
                page: 1,
                parPage: 12
            }
        };
    }

    async initialiser() {
        await this.chargerUtilisateur();
        await this.chargerFavoris();
        await this.chargerVehicules();
        this.attacherEvenements();
    }

    attacherEvenements() {
        // Accordéons des filtres
        document.querySelectorAll('.entete-filtre').forEach(entete => {
            entete.addEventListener('click', (e) => {
                e.preventDefault();
                const contenu = entete.nextElementSibling;
                const expanded = entete.getAttribute('aria-expanded') === 'true';
                
                if (expanded) {
                    contenu.hidden = true;
                    entete.setAttribute('aria-expanded', 'false');
                } else {
                    contenu.hidden = false;
                    entete.setAttribute('aria-expanded', 'true');
                }
            });
        });

        // Empêcher les clics dans le contenu des filtres de fermer l'accordéon
        document.querySelectorAll('.contenu-filtre').forEach(contenu => {
            contenu.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        });

        // Boutons de type véhicule
        document.querySelectorAll('.type-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('type-btn--active'));
                btn.classList.add('type-btn--active');
                
                const type = btn.dataset.type || '';
                const selectType = document.getElementById('filtre-type');
                if (selectType) selectType.value = type;
                
                this.gererFiltresConditionnels(type);
                this.etat.criteres.type = type;
                this.appliquerFiltresEtTri();
                this.afficherListe();
            });
        });

        // Raccourcis prix
        document.querySelectorAll('.raccourci-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const min = btn.dataset.min ? Number(btn.dataset.min) : null;
                const max = btn.dataset.max ? Number(btn.dataset.max) : null;
                
                const inputMin = document.getElementById('filtre-prix-min');
                const inputMax = document.getElementById('filtre-prix-max');
                
                if (inputMin) inputMin.value = min || '';
                if (inputMax) inputMax.value = max || '';
                
                this.lireFiltres();
            });
        });

        // Raccourcis kilométrage
        document.querySelectorAll('.raccourci-km-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const min = btn.dataset.min ? Number(btn.dataset.min) : null;
                const max = btn.dataset.max ? Number(btn.dataset.max) : null;
                
                const inputMin = document.getElementById('filtre-km-min');
                const inputMax = document.getElementById('filtre-km-max');
                
                if (inputMin) inputMin.value = min || '';
                if (inputMax) inputMax.value = max || '';
                
                this.lireFiltres();
            });
        });

        // Toggle filtres mobile
        const toggleMobile = document.getElementById('toggle-filtres-mobile');
        const barreFiltre = document.querySelector('.barre-laterale-filtres');
        if (toggleMobile && barreFiltre) {
            toggleMobile.addEventListener('click', () => {
                barreFiltre.classList.toggle('filtres-ouverts');
                toggleMobile.innerHTML = barreFiltre.classList.contains('filtres-ouverts') 
                    ? '<i class="fas fa-times"></i> Fermer les filtres'
                    : '<i class="fas fa-filter"></i> Afficher les filtres';
            });
        }

        // Recherche rapide
        const rechercheRapide = document.getElementById('recherche-rapide');
        if (rechercheRapide) {
            let timeoutRecherche;
            rechercheRapide.addEventListener('input', () => {
                clearTimeout(timeoutRecherche);
                timeoutRecherche = setTimeout(() => {
                    this.etat.criteres.recherche = rechercheRapide.value.trim().toLowerCase();
                    this.appliquerFiltresEtTri();
                    this.afficherListe();
                }, 300);
            });
        }

        // Bouton reset
        const resetFiltres = document.getElementById('reset-filtres');
        if (resetFiltres) {
            resetFiltres.addEventListener('click', () => this.reinitialiserFiltres());
        }

        // Formulaire
        const formulaire = document.getElementById('formulaire-filtres');
        if (formulaire) {
            formulaire.addEventListener('change', () => this.lireFiltres());
            formulaire.addEventListener('submit', (e) => {
                e.preventDefault();
                this.lireFiltres();
            });
            document.getElementById('bouton-appliquer-filtres')?.addEventListener('click', () => this.lireFiltres());
        }

        // Tri
        const selecteurTri = document.getElementById('selection-tri');
        if (selecteurTri) {
            selecteurTri.addEventListener('change', () => {
                this.etat.tri = selecteurTri.value;
                this.appliquerFiltresEtTri();
                this.afficherListe();
            });
        }
        
        // Événements de localisation
        this.attacherEvenementsLocalisation();
    }
    
    /**
     * Attache les événements spécifiques à la localisation
     */
    attacherEvenementsLocalisation() {
        // Bouton géolocalisation
        const btnGeo = document.getElementById('btn-geolocalisation');
        if (btnGeo) {
            btnGeo.addEventListener('click', (e) => {
                e.stopPropagation();
                this.geolocalisationUtilisateur();
            });
        }
        
        // Saisie ville avec autocomplétion
        const inputVille = document.getElementById('filtre-ville');
        const listeSuggestions = document.getElementById('suggestions-villes');
        let timeoutRecherche = null;
        
        if (inputVille && listeSuggestions) {
            inputVille.addEventListener('input', () => {
                clearTimeout(timeoutRecherche);
                const valeur = inputVille.value.trim();
                
                if (valeur.length < 2) {
                    listeSuggestions.hidden = true;
                    return;
                }
                
                timeoutRecherche = setTimeout(() => {
                    this.rechercherVilles(valeur);
                }, 300);
            });
            
            // Fermer les suggestions si on clique ailleurs
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.champ-localisation')) {
                    listeSuggestions.hidden = true;
                }
            });
        }
        
        // Boutons de rayon
        document.querySelectorAll('.btn-rayon').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('.btn-rayon').forEach(b => b.classList.remove('btn-rayon--active'));
                btn.classList.add('btn-rayon--active');
                
                const rayon = parseInt(btn.dataset.rayon);
                document.getElementById('filtre-rayon').value = rayon;
                this.etat.criteres.rayon = rayon;
                
                // Réappliquer le filtre si une localisation est déjà sélectionnée
                if (this.etat.criteres.latitude && this.etat.criteres.longitude) {
                    this.appliquerFiltresEtTri();
                    this.afficherListe();
                }
            });
        });
        
        // Bouton effacer localisation
        const btnEffacer = document.getElementById('btn-effacer-localisation');
        if (btnEffacer) {
            btnEffacer.addEventListener('click', (e) => {
                e.stopPropagation();
                this.effacerLocalisation();
            });
        }
    }
    
    /**
     * Géolocalisation de l'utilisateur via le navigateur
     */
    async geolocalisationUtilisateur() {
        const btnGeo = document.getElementById('btn-geolocalisation');
        
        if (!navigator.geolocation) {
            afficherNotificationGlobale('La géolocalisation n\'est pas supportée par votre navigateur.', 'erreur');
            return;
        }
        
        // Afficher le chargement
        if (btnGeo) {
            btnGeo.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Localisation...';
            btnGeo.disabled = true;
        }
        
        try {
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            });
            
            const { latitude, longitude } = position.coords;
            
            // Récupérer le nom de la ville via reverse geocoding
            const nomVille = await this.reverseGeocode(latitude, longitude);
            
            this.definirLocalisation(latitude, longitude, nomVille || 'Ma position');
            afficherNotificationGlobale('Position détectée avec succès !', 'succes');
            
        } catch (error) {
            let message = 'Impossible de vous géolocaliser.';
            if (error.code === 1) message = 'Vous avez refusé la géolocalisation.';
            else if (error.code === 2) message = 'Position non disponible.';
            else if (error.code === 3) message = 'Délai dépassé.';
            
            afficherNotificationGlobale(message, 'erreur');
        } finally {
            if (btnGeo) {
                btnGeo.innerHTML = '<i class="fas fa-crosshairs"></i> Me géolocaliser';
                btnGeo.disabled = false;
            }
        }
    }
    
    /**
     * Reverse geocoding pour obtenir le nom de la ville à partir des coordonnées
     */
    async reverseGeocode(lat, lon) {
        try {
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=10&addressdetails=1`;
            const res = await fetch(url, {
                headers: { 'Accept-Language': 'fr' }
            });
            const data = await res.json();
            
            if (data.address) {
                return data.address.city || data.address.town || data.address.village || data.address.municipality || 'Ma position';
            }
        } catch (e) {
            console.error('Erreur reverse geocoding:', e);
        }
        return null;
    }
    
    /**
     * Recherche de villes via l'API gouv.fr
     */
    async rechercherVilles(terme) {
        const listeSuggestions = document.getElementById('suggestions-villes');
        if (!listeSuggestions) return;
        
        try {
            // Déterminer si c'est un code postal ou un nom de ville
            const estCodePostal = /^\d{2,5}$/.test(terme);
            let url;
            
            if (estCodePostal) {
                url = `https://geo.api.gouv.fr/communes?codePostal=${terme}&fields=nom,centre,codesPostaux&limit=10`;
            } else {
                url = `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(terme)}&fields=nom,centre,codesPostaux&limit=10`;
            }
            
            const res = await fetch(url);
            const villes = await res.json();
            
            if (villes.length === 0) {
                listeSuggestions.innerHTML = '<li class="suggestion-vide">Aucune ville trouvée</li>';
                listeSuggestions.hidden = false;
                return;
            }
            
            listeSuggestions.innerHTML = villes.map(ville => {
                const codePostal = ville.codesPostaux?.[0] || '';
                const coords = ville.centre?.coordinates || [null, null];
                return `
                    <li class="suggestion-ville" 
                        data-lat="${coords[1]}" 
                        data-lon="${coords[0]}" 
                        data-nom="${ville.nom}">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${ville.nom}</span>
                        <span class="code-postal">${codePostal}</span>
                    </li>
                `;
            }).join('');
            
            listeSuggestions.hidden = false;
            
            // Attacher les événements de clic
            listeSuggestions.querySelectorAll('.suggestion-ville').forEach(item => {
                item.addEventListener('click', () => {
                    const lat = parseFloat(item.dataset.lat);
                    const lon = parseFloat(item.dataset.lon);
                    const nom = item.dataset.nom;
                    
                    if (lat && lon) {
                        this.definirLocalisation(lat, lon, nom);
                    }
                    
                    listeSuggestions.hidden = true;
                    document.getElementById('filtre-ville').value = '';
                });
            });
            
        } catch (e) {
            console.error('Erreur recherche villes:', e);
            listeSuggestions.hidden = true;
        }
    }
    
    /**
     * Définit la localisation sélectionnée
     */
    definirLocalisation(lat, lon, nom) {
        this.etat.criteres.latitude = lat;
        this.etat.criteres.longitude = lon;
        this.etat.criteres.nomLocalisation = nom;
        
        // Mettre à jour l'UI
        document.getElementById('filtre-latitude').value = lat;
        document.getElementById('filtre-longitude').value = lon;
        
        const localisationSelectionnee = document.getElementById('localisation-selectionnee');
        const nomLocalisation = document.getElementById('nom-localisation');
        const champRayon = document.getElementById('champ-rayon');
        
        if (localisationSelectionnee && nomLocalisation) {
            nomLocalisation.textContent = nom;
            localisationSelectionnee.hidden = false;
        }
        
        if (champRayon) {
            champRayon.hidden = false;
        }
        
        // Appliquer le filtre
        this.appliquerFiltresEtTri();
        this.afficherListe();
    }
    
    /**
     * Efface la localisation sélectionnée
     */
    effacerLocalisation() {
        // Réinitialiser les critères de localisation
        this.etat.criteres.latitude = null;
        this.etat.criteres.longitude = null;
        this.etat.criteres.nomLocalisation = '';
        this.etat.criteres.rayon = 20;
        
        // Réinitialiser les champs du formulaire
        document.getElementById('filtre-latitude').value = '';
        document.getElementById('filtre-longitude').value = '';
        document.getElementById('filtre-ville').value = '';
        document.getElementById('filtre-rayon').value = '20';
        
        // Cacher les éléments d'affichage
        const localisationSelectionnee = document.getElementById('localisation-selectionnee');
        const champRayon = document.getElementById('champ-rayon');
        const nomLocalisation = document.getElementById('nom-localisation');
        
        if (localisationSelectionnee) localisationSelectionnee.hidden = true;
        if (champRayon) champRayon.hidden = true;
        if (nomLocalisation) nomLocalisation.textContent = '';
        
        // Réinitialiser les boutons de rayon
        document.querySelectorAll('.btn-rayon').forEach(b => b.classList.remove('btn-rayon--active'));
        document.querySelector('.btn-rayon[data-rayon="20"]')?.classList.add('btn-rayon--active');
        
        // Nettoyer les propriétés _distance de tous les véhicules
        this.etat.vehicules.forEach(v => {
            delete v._distance;
        });
        
        // Réappliquer les filtres et afficher la liste
        this.appliquerFiltresEtTri();
        this.afficherListe();
    }
    
    /**
     * Calcule la distance entre deux points GPS (formule Haversine)
     * @param {number} lat1 Latitude du point 1
     * @param {number} lon1 Longitude du point 1
     * @param {number} lat2 Latitude du point 2
     * @param {number} lon2 Longitude du point 2
     * @returns {number} Distance en kilomètres
     */
    calculerDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Rayon de la Terre en km
        const dLat = this.degresVersRadians(lat2 - lat1);
        const dLon = this.degresVersRadians(lon2 - lon1);
        
        const a = 
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(this.degresVersRadians(lat1)) * Math.cos(this.degresVersRadians(lat2)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }
    
    /**
     * Convertit des degrés en radians
     */
    degresVersRadians(deg) {
        return deg * (Math.PI / 180);
    }

    reinitialiserFiltres() {
        const formulaire = document.getElementById('formulaire-filtres');
        if (formulaire) formulaire.reset();
        
        const rechercheRapide = document.getElementById('recherche-rapide');
        if (rechercheRapide) rechercheRapide.value = '';
        
        document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('type-btn--active'));
        document.querySelector('.type-btn[data-type=""]')?.classList.add('type-btn--active');
        
        // Effacer la localisation (ceci va nettoyer tous les éléments liés)
        this.effacerLocalisation();
        
        // Réinitialiser les critères
        this.etat.criteres = {
            type: '',
            marque: '',
            anneeMin: null,
            anneeMax: null,
            prixMin: null,
            prixMax: null,
            kmMin: null,
            kmMax: null,
            carburant: [],
            boite: [],
            etat: [],
            crit_air: [],
            nb_portes: [],
            controle_technique: [],
            recherche: '',
            latitude: null,
            longitude: null,
            rayon: 20,
            nomLocalisation: ''
        };
        
        this.gererFiltresConditionnels('');
        this.appliquerFiltresEtTri();
        this.afficherListe();
    }

    gererFiltresConditionnels(type) {
        const filtreBoite = document.getElementById('groupe-filtre-boite');
        const filtrePortes = document.getElementById('groupe-filtre-portes');
        const filtreCT = document.getElementById('groupe-filtre-ct');
        
        if (filtreBoite) {
            filtreBoite.style.display = (type === 'moto') ? 'none' : 'block';
        }
        
        if (filtrePortes) {
            filtrePortes.style.display = (type === 'voiture') ? 'block' : 'none';
        }
        
        if (filtreCT) {
            filtreCT.style.display = 'block';
        }
    }

    async chargerUtilisateur() {
        // Ne pas faire de requête si l'utilisateur n'est pas connecté (vérification DOM)
        const estConnecte = document.querySelector('.menu-utilisateur') !== null || 
                           document.querySelector('[data-utilisateur-connecte]') !== null;
        if (!estConnecte) return;
        
        try {
            const url = obtenirUrlApi('/connexion?action=me');
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const donnees = await res.json();
            if (res.ok && donnees.user) this.etat.utilisateur = donnees.user;
        } catch (e) {}
    }

    async chargerFavoris() {
        if (!this.etat.utilisateur) return;
        try {
            const url = obtenirUrlApi('/favoris?ids_only=1');
            const res = await fetch(url);
            if (res.ok) this.etat.favoris = await res.json();
        } catch (e) {}
    }

    async chargerVehicules() {
        const liste = document.getElementById('liste-vehicules');
        if (liste) liste.setAttribute('aria-busy', 'true');
        
        try {
            const res = await fetch(this.urlApi, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Erreur chargement');
            this.etat.vehicules = await res.json();
            this.mettreAJourFiltreMarque();
            this.appliquerFiltresEtTri();
            this.afficherListe();
        } catch (e) {} finally {
            if (liste) liste.setAttribute('aria-busy', 'false');
        }
    }

    mettreAJourFiltreMarque() {
        const selecteur = document.getElementById('filtre-marque');
        if (!selecteur) return;
        const courant = selecteur.value;
        const marques = [...new Set(this.etat.vehicules.map(v => v.marque))].sort();
        
        selecteur.innerHTML = '<option value="toutes">Toutes les marques</option>';
        marques.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m;
            opt.textContent = m;
            selecteur.appendChild(opt);
        });
        if (marques.includes(courant)) selecteur.value = courant;
    }

    lireFiltres() {
        const formulaire = document.getElementById('formulaire-filtres');
        if (!formulaire) return;
        const donneesForm = new FormData(formulaire);
        
        const rechercheActuelle = this.etat.criteres.recherche || '';
        
        // Préserver les critères de localisation
        const latitudeActuelle = this.etat.criteres.latitude;
        const longitudeActuelle = this.etat.criteres.longitude;
        const rayonActuel = this.etat.criteres.rayon;
        const nomLocalisationActuel = this.etat.criteres.nomLocalisation;
        
        this.etat.criteres = {
            type: donneesForm.get('type') || '',
            marque: (donneesForm.get('marque') && donneesForm.get('marque') !== 'toutes') ? donneesForm.get('marque') : '',
            anneeMin: donneesForm.get('annee_min') ? Number(donneesForm.get('annee_min')) : null,
            anneeMax: donneesForm.get('annee_max') ? Number(donneesForm.get('annee_max')) : null,
            prixMin: donneesForm.get('prix_min') ? Number(donneesForm.get('prix_min')) : null,
            prixMax: donneesForm.get('prix_max') ? Number(donneesForm.get('prix_max')) : null,
            kmMin: donneesForm.get('km_min') ? Number(donneesForm.get('km_min')) : null,
            kmMax: donneesForm.get('km_max') ? Number(donneesForm.get('km_max')) : null,
            carburant: donneesForm.getAll('carburant'),
            boite: donneesForm.getAll('boite'),
            etat: donneesForm.getAll('etat'),
            crit_air: donneesForm.getAll('crit_air'),
            nb_portes: donneesForm.getAll('nb_portes'),
            controle_technique: donneesForm.getAll('controle_technique'),
            recherche: rechercheActuelle,
            // Préserver la localisation
            latitude: latitudeActuelle,
            longitude: longitudeActuelle,
            rayon: rayonActuel,
            nomLocalisation: nomLocalisationActuel
        };
        
        document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('type-btn--active'));
        const btnType = document.querySelector(`.type-btn[data-type="${this.etat.criteres.type}"]`);
        if (btnType) btnType.classList.add('type-btn--active');
        
        this.gererFiltresConditionnels(this.etat.criteres.type);
        this.appliquerFiltresEtTri();
        this.afficherListe();
    }

    appliquerFiltresEtTri() {
        // Réinitialiser la pagination lors d'un changement de filtres
        this.etat.pagination.page = 1;
        
        let filtres = [...this.etat.vehicules];
        const c = this.etat.criteres;

        filtres = filtres.filter(v => {
            // Filtre par localisation/distance
            if (c.latitude && c.longitude) {
                // Si le véhicule n'a pas de coordonnées, l'exclure
                if (!v.latitude || !v.longitude) return false;
                
                const distance = this.calculerDistance(
                    c.latitude, c.longitude,
                    parseFloat(v.latitude), parseFloat(v.longitude)
                );
                
                // Stocker la distance pour affichage et tri optionnel
                v._distance = distance;
                
                if (distance > c.rayon) return false;
            }
            
            if (c.recherche && c.recherche.length > 0) {
                const typeVehicule = v.type_vehicule || v.type || '';
                const texteRecherche = `${v.marque} ${v.modele} ${typeVehicule} ${v.carburant || ''} ${v.ville || ''}`.toLowerCase();
                if (!texteRecherche.includes(c.recherche)) return false;
            }
            
            const typeVehicule = v.type_vehicule || v.type;
            if (c.type && typeVehicule !== c.type) return false;
            
            if (c.marque && v.marque !== c.marque) return false;
            if (c.anneeMin && v.annee < c.anneeMin) return false;
            if (c.anneeMax && v.annee > c.anneeMax) return false;
            if (c.prixMin && v.prix < c.prixMin) return false;
            if (c.prixMax && v.prix > c.prixMax) return false;
            
            const km = v.km || 0;
            if (c.kmMin && km < c.kmMin) return false;
            if (c.kmMax && km > c.kmMax) return false;
            
            if (c.carburant.length > 0 && !c.carburant.includes(v.carburant)) return false;
            
            const typeMoto = (v.type_vehicule || v.type) === 'moto';
            if (c.boite.length > 0 && !typeMoto && !c.boite.includes(v.boite)) return false;
            
            // Comparaison insensible à la casse pour l'état
            if (c.etat.length > 0) {
                const etatVehicule = (v.etat || '').toLowerCase();
                const etatsRecherches = c.etat.map(e => e.toLowerCase());
                if (!etatsRecherches.includes(etatVehicule)) return false;
            }
            
            if (c.crit_air.length > 0 && !c.crit_air.includes(String(v.crit_air))) return false;
            
            const typeVoiture = (v.type_vehicule || v.type) === 'voiture';
            if (c.nb_portes.length > 0 && typeVoiture && !c.nb_portes.includes(String(v.nb_portes))) return false;
            
            // Comparaison insensible à la casse pour le contrôle technique
            if (c.controle_technique.length > 0) {
                const ctVehicule = (v.controle_technique || '').toLowerCase();
                const ctsRecherches = c.controle_technique.map(ct => ct.toLowerCase());
                if (!ctsRecherches.includes(ctVehicule)) return false;
            }
            
            return true;
        });

        switch (this.etat.tri) {
            case 'score-ia':
                // Tri par score IA décroissant, les scores null/undefined en dernier
                filtres.sort((a, b) => {
                    const scoreA = a.score_ia;
                    const scoreB = b.score_ia;
                    // Si les deux sont null/undefined, garder l'ordre par date
                    if ((scoreA === null || scoreA === undefined) && (scoreB === null || scoreB === undefined)) {
                        return new Date(b.created_at) - new Date(a.created_at);
                    }
                    // Si A est null, le mettre après B
                    if (scoreA === null || scoreA === undefined) return 1;
                    // Si B est null, le mettre après A
                    if (scoreB === null || scoreB === undefined) return -1;
                    // Sinon trier par score décroissant (meilleurs scores en premier)
                    return scoreB - scoreA;
                });
                break;
            case 'prix-croissant': 
                filtres.sort((a, b) => a.prix - b.prix); 
                break;
            case 'prix-decroissant': 
                filtres.sort((a, b) => b.prix - a.prix); 
                break;
            case 'km-croissant': 
                filtres.sort((a, b) => (a.km || 0) - (b.km || 0)); 
                break;
            case 'km-decroissant': 
                filtres.sort((a, b) => (b.km || 0) - (a.km || 0)); 
                break;
            case 'annee-decroissante': 
                filtres.sort((a, b) => b.annee - a.annee); 
                break;
            case 'annee-croissante': 
                filtres.sort((a, b) => a.annee - b.annee); 
                break;
            case 'distance':
                // Tri par distance (seulement si localisation active)
                filtres.sort((a, b) => {
                    const distA = a._distance ?? Infinity;
                    const distB = b._distance ?? Infinity;
                    return distA - distB;
                });
                break;
            default: 
                // Si localisation active, trier par distance par défaut
                if (this.etat.criteres.latitude && this.etat.criteres.longitude) {
                    filtres.sort((a, b) => {
                        const distA = a._distance ?? Infinity;
                        const distB = b._distance ?? Infinity;
                        return distA - distB;
                    });
                } else {
                    filtres.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                }
        }

        this.etat.filtres = filtres;
        this.mettreAJourCompteurs();
    }

    mettreAJourCompteurs() {
        const statTotal = document.getElementById('stat-total-vehicules');
        if (statTotal) statTotal.textContent = this.etat.vehicules.length;
        
        const nbResultats = document.getElementById('nombre-resultats');
        if (nbResultats) nbResultats.textContent = this.etat.filtres.length;
    }

    afficherListe() {
        const liste = document.getElementById('liste-vehicules');
        const vide = document.getElementById('etat-vide');
        if (!liste || !vide) return;

        liste.innerHTML = '';
        if (this.etat.filtres.length === 0) {
            vide.hidden = false;
            this.afficherPagination();
            return;
        }
        vide.hidden = true;

        // Calcul pagination
        const { page, parPage } = this.etat.pagination;
        const debut = (page - 1) * parPage;
        const fin = debut + parPage;
        const vehiculesPage = this.etat.filtres.slice(debut, fin);

        const fragment = document.createDocumentFragment();
        vehiculesPage.forEach(v => {
            const li = document.createElement('li');
            li.className = 'carte-vehicule';
            li.innerHTML = this.genererHtmlCarte(v);
            
            li.addEventListener('click', (e) => {
                if (!e.target.closest('button') && !e.target.closest('a')) {
                    window.location.href = `vehicule?id=${v.id}`;
                }
            });

            const btnCoeur = li.querySelector('.bouton-coeur');
            if (btnCoeur) {
                btnCoeur.addEventListener('click', (e) => this.basculerFavori(e, v.id, btnCoeur));
            }

            fragment.appendChild(li);
        });
        liste.appendChild(fragment);
        
        this.afficherPagination();
    }

    /**
     * Affiche les contrôles de pagination
     */
    afficherPagination() {
        const conteneur = document.getElementById('pagination-galerie');
        if (!conteneur) return;

        const { page, parPage } = this.etat.pagination;
        const total = this.etat.filtres.length;
        const totalPages = Math.ceil(total / parPage);

        if (totalPages <= 1) {
            conteneur.innerHTML = '';
            return;
        }

        let html = '<div class="pagination">';
        
        // Bouton précédent
        html += `<button class="pagination__btn pagination__btn--nav ${page === 1 ? 'pagination__btn--disabled' : ''}" 
            data-page="${page - 1}" ${page === 1 ? 'disabled' : ''}>
            <i class="fas fa-chevron-left"></i>
        </button>`;

        // Génération des numéros de page avec ellipses
        const pagesAffichees = this.genererNumerosPagination(page, totalPages);
        pagesAffichees.forEach(p => {
            if (p === '...') {
                html += `<span class="pagination__ellipsis">...</span>`;
            } else {
                html += `<button class="pagination__btn ${p === page ? 'pagination__btn--active' : ''}" data-page="${p}">${p}</button>`;
            }
        });

        // Bouton suivant
        html += `<button class="pagination__btn pagination__btn--nav ${page === totalPages ? 'pagination__btn--disabled' : ''}" 
            data-page="${page + 1}" ${page === totalPages ? 'disabled' : ''}>
            <i class="fas fa-chevron-right"></i>
        </button>`;

        html += '</div>';
        
        // Info pagination
        const debut = (page - 1) * parPage + 1;
        const fin = Math.min(page * parPage, total);
        html += `<div class="pagination__info">Affichage ${debut}-${fin} sur ${total} véhicule${total > 1 ? 's' : ''}</div>`;

        conteneur.innerHTML = html;

        // Attacher événements
        conteneur.querySelectorAll('.pagination__btn:not([disabled])').forEach(btn => {
            btn.addEventListener('click', () => {
                const nouvellePage = parseInt(btn.dataset.page);
                if (nouvellePage >= 1 && nouvellePage <= totalPages) {
                    this.changerPage(nouvellePage);
                }
            });
        });
    }

    /**
     * Génère les numéros de pages à afficher avec ellipses
     */
    genererNumerosPagination(pageCourante, totalPages) {
        const pages = [];
        const delta = 2; // Nombre de pages autour de la page courante

        if (totalPages <= 7) {
            // Afficher toutes les pages
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            // Toujours afficher la première page
            pages.push(1);

            if (pageCourante > delta + 2) {
                pages.push('...');
            }

            const start = Math.max(2, pageCourante - delta);
            const end = Math.min(totalPages - 1, pageCourante + delta);

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            if (pageCourante < totalPages - delta - 1) {
                pages.push('...');
            }

            // Toujours afficher la dernière page
            if (!pages.includes(totalPages)) {
                pages.push(totalPages);
            }
        }

        return pages;
    }

    /**
     * Change la page courante et met à jour l'affichage
     */
    changerPage(nouvellePage) {
        this.etat.pagination.page = nouvellePage;
        this.afficherListe();
        
        // Scroll vers le haut de la grille
        const grille = document.getElementById('liste-vehicules');
        if (grille) {
            grille.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    genererHtmlCarte(v) {
        const estFavori = this.etat.favoris.includes(v.id);
        const classeCoeur = estFavori ? 'fas fa-heart' : 'far fa-heart';
        const styleCoeur = estFavori ? 'color: var(--couleur-danger);' : '';
        
        // Vérifier si l'utilisateur est le propriétaire
        const estProprietaire = this.etat.utilisateur && 
            (parseInt(v.user_id) === parseInt(this.etat.utilisateur.id));
        
        const type = v.type_vehicule || v.type || 'voiture';
        const km = v.km || 0;
        
        const iconsMap = { 'voiture': 'fa-car', 'moto': 'fa-motorcycle', 'camion': 'fa-truck' };
        const iconType = iconsMap[type] || 'fa-car';
        
        const img = v.image_path 
            ? `<img src="${v.image_path}" alt="${v.marque}" style="width:100%; height:100%; object-fit:cover; display:block;">`
            : `<div style="width:100%; height:100%; background: #252a35; display:flex; align-items:center; justify-content:center;"><i class="fas ${iconType} fa-3x"></i></div>`;

        let specsHtml = `
            <span class="element-spec"><i class="fas fa-calendar-alt"></i> ${v.annee}</span>
            <span class="element-spec"><i class="fas fa-tachometer-alt"></i> ${km.toLocaleString()} km</span>
            <span class="element-spec"><i class="fas fa-gas-pump"></i> ${v.carburant || 'N/A'}</span>
        `;
        
        if (type !== 'moto') {
            specsHtml += `<span class="element-spec"><i class="fas fa-cog"></i> ${v.boite || 'N/A'}</span>`;
        }

        const badgeType = `<span class="badge-type badge-type--${type}"><i class="fas ${iconType}"></i> ${type.charAt(0).toUpperCase() + type.slice(1)}</span>`;

        // Bouton favori uniquement si pas propriétaire
        const boutonFavori = estProprietaire ? '' : `
            <button class="bouton-coeur" title="${estFavori ? 'Retirer' : 'Ajouter'}" style="${styleCoeur}">
                <i class="${classeCoeur}"></i>
            </button>
        `;

        // Badge distance pour l'image
        let badgeDistanceHtml = '';
        if (v._distance !== undefined && v._distance !== null) {
            const distanceText = v._distance < 1 
                ? `${Math.round(v._distance * 1000)} m` 
                : `${v._distance.toFixed(1)} km`;
            badgeDistanceHtml = `<span class="badge-distance"><i class="fas fa-route"></i> ${distanceText}</span>`;
        }

        // Badge état de la voiture
        let badgeEtatHtml = '';
        if (v.etat) {
            const etatLabels = {
                'neuf': 'Neuf',
                'occasion': 'Occasion',
                'excellent': 'Excellent',
                'tres_bon': 'Très bon',
                'bon': 'Bon',
                'correct': 'Correct'
            };
            const etatLabel = etatLabels[v.etat] || v.etat;
            badgeEtatHtml = `<span class="badge-etat badge-etat--${v.etat}">${etatLabel}</span>`;
        }

        // Affichage de la localisation
        const localisationHtml = `<span class="element-spec" style="font-size:0.9rem; color:var(--texte-attenue);"><i class="fas fa-map-marker-alt"></i> ${v.ville || 'France'}${v.code_postal ? ' (' + v.code_postal + ')' : ''}</span>`;

        return `
            <div class="conteneur-image-carte">
                ${img}
                <div class="badges-carte badges-carte--gauche">
                    ${badgeType}
                </div>
                ${badgeEtatHtml ? `<div class="badges-carte badges-carte--droite">${badgeEtatHtml}</div>` : ''}
                ${badgeDistanceHtml ? `<div class="badges-carte badges-carte--bas-droite">${badgeDistanceHtml}</div>` : ''}
            </div>
            <div class="details-carte">
                <div class="rangee-entete-carte">
                    <h3 class="titre-carte-h">${v.marque} ${v.modele}</h3>
                    <div class="actions-carte-h">
                        ${boutonFavori}
                    </div>
                </div>
                <div class="rangee-specs-carte">
                    ${specsHtml}
                </div>
                <div class="rangee-pied-carte" style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                    ${localisationHtml}
                    <div class="prix-carte-h" style="margin:0;">${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(v.prix)}</div>
                </div>
            </div>
        `;
    }

    async basculerFavori(e, id, btn) {
        e.stopPropagation();
        if (!this.etat.utilisateur) {
            // Redirection directe vers la page de connexion
            window.location.href = 'connexion';
            return;
        }

        const estFavori = this.etat.favoris.includes(id);
        const methode = estFavori ? 'DELETE' : 'POST';
        const url = obtenirUrlApi('/favoris') + (estFavori ? `?id=${id}` : '');

        try {
            const options = { method: methode };
            if (!estFavori) {
                options.headers = { 'Content-Type': 'application/json' };
                options.body = JSON.stringify({ vehicle_id: id });
            }
            const res = await fetch(url, options);
            if (res.ok) {
                if (estFavori) {
                    this.etat.favoris = this.etat.favoris.filter(fid => fid !== id);
                    btn.querySelector('i').className = 'far fa-heart';
                    btn.style.color = '';
                } else {
                    this.etat.favoris.push(id);
                    btn.querySelector('i').className = 'fas fa-heart';
                    btn.style.color = 'var(--couleur-danger)';
                }
            }
        } catch (e) {}
    }
}
