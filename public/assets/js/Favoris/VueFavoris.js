/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE FAVORIS - GESTION DES VÉHICULES FAVORIS
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe gère l'affichage et les interactions de la page Favoris :
 * - Chargement de la liste des véhicules favoris de l'utilisateur
 * - Affichage sous forme de cartes horizontales
 * - Retrait d'un véhicule des favoris
 * - Redirection vers la connexion si non authentifié
 * 
 * Structure des cartes :
 * - Image du véhicule avec placeholder si absente
 * - Informations principales (marque, modèle, année, km, carburant, boîte)
 * - Localisation et prix
 * - Actions (voir détails, retirer des favoris)
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @see     ControleurFavoris (PHP) Pour le traitement serveur
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi, echapperHTML } from '../application.js';

/**
 * Classe gérant l'affichage et les interactions de la page Favoris
 * @class
 */
export class VueFavoris {
    
    /**
     * Initialise la vue avec l'URL de l'API
     */
    constructor() {
        /** @type {string} URL de l'endpoint des favoris */
        this.urlApi = obtenirUrlApi('/favoris');
        
        /** @type {HTMLElement|null} Conteneur de la liste des favoris */
        this.listeElement = null;
        
        /** @type {HTMLElement|null} Message d'état vide */
        this.videElement = null;
    }

    /**
     * Initialise le composant et charge la liste des favoris
     * 
     * @async
     */
    async initialiser() {
        // Récupération des références DOM
        this.listeElement = document.getElementById('liste-favoris');
        this.videElement = document.getElementById('favoris-vide');

        if (this.listeElement) {
            await this.chargerFavoris();
        } else {
            console.error("VueFavoris: Élément #liste-favoris introuvable dans le DOM.");
        }
    }

    /**
     * Charge les véhicules favoris depuis le serveur
     * 
     * Gère les cas d'erreur :
     * - 401 : Redirection vers la connexion
     * - Autres erreurs : Affichage d'un message d'erreur
     * 
     * @async
     */
    async chargerFavoris() {
        try {
            // Indicateur de chargement
            this.listeElement.setAttribute('aria-busy', 'true');
            
            const reponse = await fetch(this.urlApi);
            
            // Redirection si non authentifié
            if (reponse.status === 401) {
                window.location.href = 'connexion';
                return;
            }
            
            if (!reponse.ok) {
                throw new Error(`Erreur HTTP: ${reponse.status}`);
            }
            
            const donnees = await reponse.json();
            
            if (donnees.erreur) {
                throw new Error(donnees.erreur);
            }
            
            this.afficherListe(donnees);
            
        } catch (erreur) {
            console.error("Erreur lors du chargement des favoris:", erreur);
            this.listeElement.innerHTML = `
                <div class="message message--erreur">
                    Impossible de charger les favoris.
                </div>`;
        } finally {
            this.listeElement.setAttribute('aria-busy', 'false');
        }
    }

    /**
     * Affiche la liste des véhicules favoris dans le DOM
     * 
     * @param {Object[]} vehicules - Liste des véhicules favoris
     */
    afficherListe(vehicules) {
        this.listeElement.innerHTML = '';
        
        // Gestion de l'état vide
        if (!vehicules || vehicules.length === 0) {
            if (this.videElement) this.videElement.hidden = false;
            return;
        }
        
        if (this.videElement) this.videElement.hidden = true;
        
        // Génération des cartes
        vehicules.forEach(vehicule => {
            const carte = this.creerCarteVehicule(vehicule);
            this.listeElement.appendChild(carte);
        });
    }

    /**
     * Crée l'élément HTML pour une carte de véhicule favori
     * 
     * @param   {Object} vehicule - Données du véhicule
     * @returns {HTMLLIElement} Élément LI de la carte
     */
    creerCarteVehicule(vehicule) {
        const li = document.createElement('li');
        li.className = 'carte-vehicule-horizontale';
        
        // Gestion de l'image avec placeholder si absente
        let htmlImage;
        if (vehicule.image_path) {
            htmlImage = `
                <img 
                    src="${vehicule.image_path}" 
                    class="image-carte" 
                    alt="${echapperHTML(vehicule.marque)} ${echapperHTML(vehicule.modele)}" 
                    loading="lazy"
                >`;
        } else {
            htmlImage = `
                <div class="placeholder-image">
                    <i class="fas fa-car"></i>
                </div>`;
        }

        // Formatage du prix en euros
        const prixFormate = new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'EUR' 
        }).format(vehicule.prix);

        // Construction du HTML de la carte
        li.innerHTML = `
            <div class="conteneur-image-carte">
                ${htmlImage}
            </div>
            <div class="details-carte">
                <h3 class="titre-carte-h">
                    ${echapperHTML(vehicule.marque)} ${echapperHTML(vehicule.modele)}
                </h3>
                <div class="rangee-specs-carte">
                    <span class="element-spec">
                        <i class="fas fa-calendar-alt"></i> ${vehicule.annee}
                    </span>
                    <span class="element-spec">
                        <i class="fas fa-tachometer-alt"></i> ${Number(vehicule.km).toLocaleString()} km
                    </span>
                    <span class="element-spec">
                        <i class="fas fa-gas-pump"></i> ${echapperHTML(vehicule.carburant || 'N/A')}
                    </span>
                    <span class="element-spec">
                        <i class="fas fa-cog"></i> ${echapperHTML(vehicule.boite || 'N/A')}
                    </span>
                </div>
                <div class="rangee-pied-carte">
                    <span class="localisation-carte">
                        <i class="fas fa-map-marker-alt"></i> ${echapperHTML(vehicule.ville || 'Non spécifié')}
                    </span>
                    <span class="prix-carte-h">${prixFormate}</span>
                </div>
                <div class="actions-carte-h">
                    <a href="vehicule?id=${vehicule.id}" class="btn-voir-carte">
                        <i class="fas fa-eye"></i> Voir détails
                    </a>
                    <button class="bouton-coeur active" title="Retirer des favoris">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
            </div>
        `;

        // Gestionnaire du bouton de suppression des favoris
        const boutonCoeur = li.querySelector('.bouton-coeur');
        boutonCoeur.addEventListener('click', (evenement) => 
            this.retirerFavori(evenement, vehicule.id, li)
        );

        return li;
    }

    /**
     * Gère la suppression d'un véhicule des favoris
     * 
     * @param   {Event} evenement - Événement click
     * @param   {number} idVehicule - ID du véhicule à retirer
     * @param   {HTMLElement} elementCarte - Élément DOM de la carte
     * @async
     */
    async retirerFavori(evenement, idVehicule, elementCarte) {
        evenement.stopPropagation();
        
        // Demande de confirmation
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
