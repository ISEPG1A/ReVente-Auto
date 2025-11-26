import { obtenirUrlApi } from '../../app.js';

export default class VueGalerie {
    constructor() {
        this.urlApi = obtenirUrlApi('/vehicule/galerie');
        this.etat = {
            vehicules: [],
            filtres: [],
            favoris: [],
            criteres: {
                type: 'tous',
                marque: 'toutes',
                anneeMin: null,
                anneeMax: null,
                prixMin: null,
                prixMax: null,
                carburant: [],
                boite: []
            },
            tri: 'recent',
            utilisateur: null
        };
    }

    async initialiser() {
        // Initialiser l'état
        await this.chargerUtilisateur();
        await this.chargerFavoris();
        await this.chargerVehicules();
        
        this.attacherEvenements();
    }

    attacherEvenements() {
        // Accordéons
        document.querySelectorAll('.entete-filtre').forEach(entete => {
            entete.addEventListener('click', () => {
                const contenu = entete.nextElementSibling;
                const icone = entete.querySelector('.icone-filtre');
                if (contenu.style.display === 'none' || !contenu.style.display) {
                    contenu.style.display = 'block';
                    icone.style.transform = 'rotate(90deg)';
                } else {
                    contenu.style.display = 'none';
                    icone.style.transform = 'rotate(0deg)';
                }
            });
        });

        // Filtres
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

    async chargerUtilisateur() {
        try {
            const url = obtenirUrlApi('/connexion?action=me');
            
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const donnees = await res.json();
            if (res.ok && donnees.user) this.etat.utilisateur = donnees.user;
        } catch (e) { console.error(e); }
    }

    async chargerFavoris() {
        if (!this.etat.utilisateur) return;
        try {
            const url = obtenirUrlApi('/favoris?ids_only=1');
            
            const res = await fetch(url);
            if (res.ok) this.etat.favoris = await res.json();
        } catch (e) { console.error(e); }
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
        } catch (e) {
            console.error(e);
        } finally {
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
        
        this.etat.criteres = {
            type: donneesForm.get('type') || 'tous',
            marque: donneesForm.get('marque') || 'toutes',
            anneeMin: donneesForm.get('annee_min') ? Number(donneesForm.get('annee_min')) : null,
            anneeMax: donneesForm.get('annee_max') ? Number(donneesForm.get('annee_max')) : null,
            prixMin: donneesForm.get('prix_min') ? Number(donneesForm.get('prix_min')) : null,
            prixMax: donneesForm.get('prix_max') ? Number(donneesForm.get('prix_max')) : null,
            carburant: donneesForm.getAll('carburant'),
            boite: donneesForm.getAll('boite')
        };
        
        this.appliquerFiltresEtTri();
        this.afficherListe();
    }

    appliquerFiltresEtTri() {
        let filtres = [...this.etat.vehicules];
        const c = this.etat.criteres;

        filtres = filtres.filter(v => {
            if (c.type !== 'tous' && c.type && v.type !== c.type) return false;
            if (c.marque !== 'toutes' && v.marque !== c.marque) return false;
            if (c.anneeMin && v.annee < c.anneeMin) return false;
            if (c.anneeMax && v.annee > c.anneeMax) return false;
            if (c.prixMin && v.prix < c.prixMin) return false;
            if (c.prixMax && v.prix > c.prixMax) return false;
            if (c.carburant.length > 0 && !c.carburant.includes(v.carburant)) return false;
            if (c.boite.length > 0 && !c.boite.includes(v.boite)) return false;
            return true;
        });

        switch (this.etat.tri) {
            case 'prix-croissant': filtres.sort((a, b) => a.prix - b.prix); break;
            case 'prix-decroissant': filtres.sort((a, b) => b.prix - a.prix); break;
            case 'annee-decroissante': filtres.sort((a, b) => b.annee - a.annee); break;
            case 'annee-croissante': filtres.sort((a, b) => a.annee - b.annee); break;
            default: filtres.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        }

        this.etat.filtres = filtres;
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
            li.className = 'carte-vehicule-horizontale';
            li.innerHTML = this.genererHtmlCarte(v);
            
            // Event listeners
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
        
        const km = v.km ? v.km : Math.floor(Math.random() * 150000);
        const img = v.image_path 
            ? `<img src="${v.image_path}" alt="${v.marque}" style="width:100%; height:100%; object-fit:cover;">`
            : `<div style="width:100%; height:100%; background: #252a35; display:flex; align-items:center; justify-content:center;"><i class="fas fa-car fa-3x"></i></div>`;

        return `
            <div class="conteneur-image-carte">${img}</div>
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
                    <span class="element-spec"><i class="fas fa-calendar-alt"></i> ${v.annee}</span>
                    <span class="element-spec"><i class="fas fa-tachometer-alt"></i> ${km.toLocaleString()} km</span>
                    <span class="element-spec"><i class="fas fa-gas-pump"></i> ${v.carburant || 'N/A'}</span>
                    <span class="element-spec"><i class="fas fa-cog"></i> ${v.boite || 'N/A'}</span>
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
        } catch (e) { console.error(e); }
    }
}
