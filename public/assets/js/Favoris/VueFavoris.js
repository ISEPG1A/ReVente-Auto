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
            this.configurerObservateurUI();
        } else {
            console.error("VueFavoris: Élément #liste-favoris introuvable dans le DOM.");
        }
    }
    
    /**
     * Configure l'observateur pour mettre à jour l'UI (loading et compteur)
     */
    configurerObservateurUI() {
        const updateUI = () => {
            const loadingEl = document.getElementById('favoris-loading');
            const items = document.querySelectorAll('#liste-favoris > *');
            const counter = document.getElementById('favoris-count');
            
            // Masquer le loading
            if (loadingEl) {
                loadingEl.style.display = 'none';
            }
            
            // Mettre à jour le compteur
            if (counter) {
                counter.textContent = items.length;
            }
        };
        
        // Observer les changements dans la liste
        const observer = new MutationObserver(() => {
            setTimeout(updateUI, 100);
        });
        
        if (this.listeElement) {
            observer.observe(this.listeElement, { childList: true, subtree: true });
            
            // Vérifier aussi après un délai pour le chargement initial
            setTimeout(updateUI, 500);
            setTimeout(updateUI, 1500);
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
     * Style identique aux cartes de la galerie
     * 
     * @param   {Object} vehicule - Données du véhicule
     * @returns {HTMLLIElement} Élément LI de la carte
     */
    creerCarteVehicule(vehicule) {
        const li = document.createElement('li');
        li.className = 'carte-vehicule';
        
        // Gestion de l'image avec placeholder si absente
        const type = vehicule.type_vehicule || vehicule.type || 'voiture';
        const iconsMap = { 'voiture': 'fa-car', 'moto': 'fa-motorcycle', 'camion': 'fa-truck' };
        const iconType = iconsMap[type] || 'fa-car';
        
        let htmlImage;
        if (vehicule.image_path) {
            htmlImage = `<img src="${vehicule.image_path}" alt="${echapperHTML(vehicule.marque)} ${echapperHTML(vehicule.modele)}" style="width:100%; height:100%; object-fit:cover; display:block;" loading="lazy">`;
        } else {
            htmlImage = `<div style="width:100%; height:100%; background: var(--surface-2); display:flex; align-items:center; justify-content:center;"><i class="fas ${iconType}" style="font-size:3rem; color:var(--texte-attenue); opacity:0.5;"></i></div>`;
        }

        // Formatage du prix en euros
        const prixFormate = new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'EUR' 
        }).format(vehicule.prix);
        
        // Badge du type de véhicule
        const badgeType = `<span class="badge-type badge-type--${type}"><i class="fas ${iconType}"></i> ${type.charAt(0).toUpperCase() + type.slice(1)}</span>`;
        
        // Specs HTML
        let specsHtml = `
            <span class="element-spec"><i class="fas fa-calendar-alt"></i> ${vehicule.annee}</span>
            <span class="element-spec"><i class="fas fa-tachometer-alt"></i> ${Number(vehicule.km).toLocaleString()} km</span>
            <span class="element-spec"><i class="fas fa-gas-pump"></i> ${echapperHTML(vehicule.carburant || 'N/A')}</span>
        `;
        if (type !== 'moto') {
            specsHtml += `<span class="element-spec"><i class="fas fa-cog"></i> ${echapperHTML(vehicule.boite || 'N/A')}</span>`;
        }

        // Construction du HTML de la carte (identique à la galerie)
        li.innerHTML = `
            <div class="conteneur-image-carte">
                ${htmlImage}
                <div class="badges-carte">
                    ${badgeType}
                </div>
            </div>
            <div class="details-carte">
                <div class="rangee-entete-carte">
                    <h3 class="titre-carte-h">${echapperHTML(vehicule.marque)} ${echapperHTML(vehicule.modele)}</h3>
                    <div class="actions-carte-h">
                        <button class="bouton-coeur active" title="Retirer des favoris" style="color: var(--couleur-danger); border-color: var(--couleur-danger); background: rgba(255, 107, 107, 0.1);">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
                <div class="rangee-specs-carte">
                    ${specsHtml}
                </div>
                <div class="rangee-pied-carte" style="display:flex; justify-content:space-between; align-items:center; margin-top:auto; padding-top:12px; border-top:1px solid var(--bordure);">
                    <span class="element-spec" style="font-size:0.9rem; color:var(--texte-attenue);"><i class="fas fa-map-marker-alt"></i> ${echapperHTML(vehicule.ville || 'France')}</span>
                    <div class="prix-carte-h" style="margin:0;">${prixFormate}</div>
                </div>
                <a href="vehicule?id=${vehicule.id}" class="btn-consulter-favori">
                    <i class="fas fa-eye"></i> Consulter
                </a>
            </div>
        `;

        // Gestionnaire du bouton de suppression des favoris
        const boutonCoeur = li.querySelector('.bouton-coeur');
        boutonCoeur.addEventListener('click', (evenement) => 
            this.retirerFavori(evenement, vehicule.id, li)
        );
        
        // Clic sur la carte (hors bouton) redirige vers détails
        li.addEventListener('click', (e) => {
            if (!e.target.closest('.bouton-coeur') && !e.target.closest('.btn-consulter-favori')) {
                window.location.href = `vehicule?id=${vehicule.id}`;
            }
        });

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
