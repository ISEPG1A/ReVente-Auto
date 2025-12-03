import { obtenirUrlApi, echapperHTML } from '../app.js';

/**
 * Classe gérant l'affichage et les interactions de la page Favoris.
 */
export class VueFavoris {
    
    /**
     * Constructeur de la vue.
     */
    constructor() {
        this.apiUrl = obtenirUrlApi('/favoris');
        this.listeElement = null;
        this.videElement = null;
    }

    /**
     * Initialise le composant : charge la liste des favoris.
     */
    async initialiser() {
        this.listeElement = document.getElementById('liste-favoris');
        this.videElement = document.getElementById('favoris-vide');

        if (this.listeElement) {
            await this.chargerFavoris();
        } else {
            console.error("VueFavoris: Élément #liste-favoris introuvable.");
        }
    }

    /**
     * Charge les favoris depuis le serveur.
     */
    async chargerFavoris() {
        try {
            this.listeElement.setAttribute('aria-busy', 'true');
            
            const reponse = await fetch(this.apiUrl);
            
            if (reponse.status === 401) {
                // Redirection vers la page de connexion si non authentifié
                window.location.href = 'connexion';
                return;
            }
            
            if (!reponse.ok) {
                throw new Error(`Erreur HTTP: ${reponse.status}`);
            }
            
            const donnees = await reponse.json();
            
            if (donnees.erreur) throw new Error(donnees.erreur);
            
            this.afficherListe(donnees);
            
        } catch (erreur) {
            console.error("VueFavoris erreur:", erreur);
            this.listeElement.innerHTML = `<div class="message message--erreur">Impossible de charger les favoris.</div>`;
        } finally {
            this.listeElement.setAttribute('aria-busy', 'false');
        }
    }

    /**
     * Affiche la liste des véhicules favoris dans le DOM.
     * @param {Array} vehicules Liste des véhicules
     */
    afficherListe(vehicules) {
        this.listeElement.innerHTML = '';
        
        if (!vehicules || vehicules.length === 0) {
            if (this.videElement) this.videElement.hidden = false;
            return;
        }
        
        if (this.videElement) this.videElement.hidden = true;
        
        vehicules.forEach(v => {
            const carte = this.creerCarteVehicule(v);
            this.listeElement.appendChild(carte);
        });
    }

    /**
     * Crée l'élément HTML pour une carte de véhicule.
     * @param {Object} v Les données du véhicule
     * @return {HTMLElement} L'élément LI de la carte
     */
    creerCarteVehicule(v) {
        const li = document.createElement('li');
        li.className = 'carte-vehicule-horizontale';
        
        // Gestion de l'image - utiliser directement image_path comme dans VueGalerie
        let htmlImage;
        if (v.image_path) {
            htmlImage = `<img src="${v.image_path}" class="image-carte" alt="${echapperHTML(v.marque)} ${echapperHTML(v.modele)}" loading="lazy">`;
        } else {
            htmlImage = `
                <div class="placeholder-image">
                    <i class="fas fa-car"></i>
                </div>`;
        }

        const prixFormate = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(v.prix);

        li.innerHTML = `
            <div class="conteneur-image-carte">
                ${htmlImage}
            </div>
            <div class="details-carte">
                <h3 class="titre-carte-h">${echapperHTML(v.marque)} ${echapperHTML(v.modele)}</h3>
                <div class="rangee-specs-carte">
                    <span class="element-spec"><i class="fas fa-calendar-alt"></i> ${v.annee}</span>
                    <span class="element-spec"><i class="fas fa-tachometer-alt"></i> ${Number(v.km).toLocaleString()} km</span>
                    <span class="element-spec"><i class="fas fa-gas-pump"></i> ${echapperHTML(v.carburant || 'N/A')}</span>
                    <span class="element-spec"><i class="fas fa-cog"></i> ${echapperHTML(v.boite || 'N/A')}</span>
                </div>
                <div class="rangee-pied-carte">
                    <span class="localisation-carte"><i class="fas fa-map-marker-alt"></i> ${echapperHTML(v.ville || 'Non spécifié')}</span>
                    <span class="prix-carte-h">${prixFormate}</span>
                </div>
                <div class="actions-carte-h">
                    <a href="vehicule?id=${v.id}" class="btn-voir-carte">
                        <i class="fas fa-eye"></i> Voir détails
                    </a>
                    <button class="bouton-coeur active" title="Retirer des favoris">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
            </div>
        `;

        // Gestion du clic sur le coeur (suppression)
        const boutonCoeur = li.querySelector('.bouton-coeur');
        boutonCoeur.addEventListener('click', (e) => this.retirerFavori(e, v.id, li));

        return li;
    }

    /**
     * Gère la suppression d'un favori au clic.
     * @param {Event} e L'événement click
     * @param {number} id L'ID du véhicule
     * @param {HTMLElement} elementCarte L'élément DOM de la carte
     */
    async retirerFavori(e, id, elementCarte) {
        e.stopPropagation();
        if (!confirm('Retirer ce véhicule des favoris ?')) return;

        try {
            const reponse = await fetch(`${this.apiUrl}?id=${id}`, { method: 'DELETE' });
            const resultat = await reponse.json();

            if (resultat.succes) {
                elementCarte.remove();
                if (this.listeElement.children.length === 0) {
                    if (this.videElement) this.videElement.hidden = false;
                }
            }
        } catch (erreur) {
            console.error(erreur);
        }
    }
}
