/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE GALERIE - AFFICHAGE ET FILTRAGE DES ANNONCES VÉHICULES
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi } from '../../application.js';

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
                recherche: ''
            },
            tri: 'recent',
            utilisateur: null
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
            entete.addEventListener('click', () => {
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
    }

    reinitialiserFiltres() {
        const formulaire = document.getElementById('formulaire-filtres');
        if (formulaire) formulaire.reset();
        
        const rechercheRapide = document.getElementById('recherche-rapide');
        if (rechercheRapide) rechercheRapide.value = '';
        
        document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('type-btn--active'));
        document.querySelector('.type-btn[data-type=""]')?.classList.add('type-btn--active');
        
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
            recherche: ''
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
            recherche: rechercheActuelle
        };
        
        document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('type-btn--active'));
        const btnType = document.querySelector(`.type-btn[data-type="${this.etat.criteres.type}"]`);
        if (btnType) btnType.classList.add('type-btn--active');
        
        this.gererFiltresConditionnels(this.etat.criteres.type);
        this.appliquerFiltresEtTri();
        this.afficherListe();
    }

    appliquerFiltresEtTri() {
        let filtres = [...this.etat.vehicules];
        const c = this.etat.criteres;

        filtres = filtres.filter(v => {
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
            default: 
                filtres.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
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
            return;
        }
        vide.hidden = true;

        const fragment = document.createDocumentFragment();
        this.etat.filtres.forEach(v => {
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
    }

    genererHtmlCarte(v) {
        const estFavori = this.etat.favoris.includes(v.id);
        const classeCoeur = estFavori ? 'fas fa-heart' : 'far fa-heart';
        const styleCoeur = estFavori ? 'color: var(--couleur-danger);' : '';
        
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

        return `
            <div class="conteneur-image-carte">
                ${img}
                <div class="badges-carte">
                    ${badgeType}
                </div>
            </div>
            <div class="details-carte">
                <div class="rangee-entete-carte">
                    <h3 class="titre-carte-h">${v.marque} ${v.modele}</h3>
                    <div class="actions-carte-h">
                        <button class="bouton-coeur" title="${estFavori ? 'Retirer' : 'Ajouter'}" style="${styleCoeur}">
                            <i class="${classeCoeur}"></i>
                        </button>
                    </div>
                </div>
                <div class="rangee-specs-carte">
                    ${specsHtml}
                </div>
                <div class="rangee-pied-carte" style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                    <span class="element-spec" style="font-size:0.9rem; color:var(--texte-attenue);"><i class="fas fa-map-marker-alt"></i> ${v.ville || 'France'}</span>
                    <div class="prix-carte-h" style="margin:0;">${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(v.prix)}</div>
                </div>
            </div>
        `;
    }

    async basculerFavori(e, id, btn) {
        e.stopPropagation();
        if (!this.etat.utilisateur) {
            alert('Connectez-vous pour gérer vos favoris.');
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
