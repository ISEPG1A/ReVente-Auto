/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE LOCALISATION - AFFICHAGE CARTE INTERACTIVE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe gère l'affichage de la carte de localisation d'un véhicule
 * via Leaflet et OpenStreetMap. Elle charge dynamiquement les coordonnées
 * GPS de la ville et affiche un marqueur sur la carte.
 * 
 * Fonctionnalités :
 * - Chargement dynamique de Leaflet si nécessaire
 * - Géocodage de la ville via API interne
 * - Affichage d'une carte interactive avec marqueur
 * - Gestion des états (chargement, erreur, succès)
 * - Centrage automatique sur la localisation
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * @see     ControleurLocalisation (PHP) Pour la récupération des coordonnées
 * @see     https://leafletjs.com/ Documentation Leaflet
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi, echapperHTML } from '../Commun/utilitaires.js';

export default class VueLocalisation {
    
    /**
     * Initialise la vue de localisation
     * 
     * @param {string} containerId ID du conteneur HTML
     */
    constructor(containerId = 'localisation-container') {
        this.container = document.getElementById(containerId);
        this.urlApi = obtenirUrlApi('/localisation');
        this.map = null;
        this.marker = null;
    }
    
    /**
     * Initialise et affiche la carte avec la ville
     * 
     * @param {string} ville Nom de la ville à localiser
     * @param {string|null} villeAffichage Nom de la ville pour l'affichage (optionnel)
     */
    async init(ville, villeAffichage = null) {
        if (!this.container) {
            console.warn('Container localisation non trouvé');
            return;
        }
        
        this.ville = ville;
        this.villeAffichage = villeAffichage || ville;
        
        // Vérifier si Leaflet est chargé
        if (!window.L) {
            console.warn('Leaflet n\'est pas chargé, impossible d\'afficher la carte');
            this.afficherIndisponible();
            return;
        }
        
        this.afficherChargement();
        
        try {
            await this.chargerCarte();
        } catch (error) {
            console.error('Erreur chargement carte:', error);
            this.afficherErreur();
        }
    }
    
    /**
     * Charge et affiche la carte avec géolocalisation
     */
    async chargerCarte() {
        if (!this.ville || this.ville.trim() === '') {
            this.afficherSansLocalisation();
            return;
        }
        
        // Initialiser la carte Leaflet
        const mapContainer = this.container.querySelector('.localisation-map');
        if (!mapContainer) {
            this.afficherErreur();
            return;
        }
        
        // Nettoyer la carte existante si elle existe
        if (mapContainer._leaflet_id) {
            mapContainer._leaflet_id = null;
            mapContainer.innerHTML = '';
        }
        
        // Créer la carte centrée sur la France par défaut
        this.map = L.map(mapContainer).setView([46.603354, 1.888334], 6);
        
        // Ajouter les tuiles OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 18
        }).addTo(this.map);
        
        // Géocoder la ville et ajouter le marqueur avec zone
        try {
            const response = await fetch(`${this.urlApi}?ville=${encodeURIComponent(this.ville)}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const { lat, lon, boundingbox, display_name, contour } = data.data;
                
                // Calculer le niveau de zoom approprié selon la taille de la ville
                let zoomLevel = 13;
                if (boundingbox && boundingbox.length === 4) {
                    // Créer des limites de la carte
                    const bounds = [
                        [parseFloat(boundingbox[0]), parseFloat(boundingbox[2])], // Sud-Ouest
                        [parseFloat(boundingbox[1]), parseFloat(boundingbox[3])]  // Nord-Est
                    ];
                    this.map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
                } else {
                    // Centrer la carte sur la ville avec zoom par défaut
                    this.map.setView([lat, lon], zoomLevel);
                }
                
                // Ajouter le marqueur au centre
                this.marker = L.marker([lat, lon], {
                    icon: L.divIcon({
                        className: 'localisation-marker',
                        html: '<div style="background: #fb9e0b; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    })
                }).addTo(this.map);
                
                this.marker.bindPopup(`
                    <div style="text-align: center; padding: 8px;">
                        <i class="fas fa-map-marker-alt" style="color: #fb9e0b; font-size: 1.25rem;"></i><br>
                        <strong>${echapperHTML(this.villeAffichage)}</strong>
                    </div>
                `).openPopup();
                
                // Ajouter le contour de la ville si disponible
                if (contour && contour.coordinates) {
                    // Convertir les coordonnées GeoJSON [lon, lat] en Leaflet [lat, lon]
                    const polygonCoords = contour.coordinates[0].map(coord => [coord[1], coord[0]]);
                    
                    // Dessiner le polygone de la ville
                    L.polygon(polygonCoords, {
                        color: '#fb9e0b',
                        fillColor: '#fb9e0b',
                        fillOpacity: 0.15,
                        weight: 3,
                        opacity: 0.8
                    }).addTo(this.map);
                    
                    // Ajuster la vue sur le contour
                    const bounds = L.latLngBounds(polygonCoords);
                    this.map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
                } else if (boundingbox && boundingbox.length === 4) {
                    // Fallback: utiliser un cercle si pas de contour
                    const latDiff = parseFloat(boundingbox[1]) - parseFloat(boundingbox[0]);
                    const lonDiff = parseFloat(boundingbox[3]) - parseFloat(boundingbox[2]);
                    const radius = Math.max(latDiff, lonDiff) * 111000 / 2;
                    
                    L.circle([lat, lon], {
                        color: '#fb9e0b',
                        fillColor: '#fb9e0b',
                        fillOpacity: 0.15,
                        weight: 2,
                        dashArray: '5, 10',
                        radius: Math.max(radius, 3000)
                    }).addTo(this.map);
                }
                
                this.afficherSucces();
            } else {
                // Ville non trouvée, afficher quand même la carte
                this.afficherSansCoordonnees();
            }
        } catch (error) {
            console.error('Erreur géolocalisation:', error);
            // Afficher la carte sans marqueur en cas d'erreur
            this.afficherSansCoordonnees();
        }
    }
    
    /**
     * Affiche l'état de chargement
     */
    afficherChargement() {
        this.container.innerHTML = `
            <div class="localisation-card">
                <h3 class="localisation-card__title">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Localisation</span>
                </h3>
                <p class="localisation-address">
                    <i class="fas fa-map-pin"></i>
                    <span>${echapperHTML(this.villeAffichage)}</span>
                </p>
                <div class="localisation-map">
                    <div class="localisation-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Chargement de la carte...</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Affiche la carte avec succès
     */
    afficherSucces() {
        // La carte est déjà affichée, on ne fait rien
        const loading = this.container.querySelector('.localisation-loading');
        if (loading) {
            loading.remove();
        }
    }
    
    /**
     * Affiche la carte sans coordonnées précises
     */
    afficherSansCoordonnees() {
        const loading = this.container.querySelector('.localisation-loading');
        if (loading) {
            loading.remove();
        }
        
        // Ajouter un message informatif
        const mapContainer = this.container.querySelector('.localisation-map');
        if (mapContainer && this.map) {
            const infoDiv = document.createElement('div');
            infoDiv.className = 'localisation-info';
            infoDiv.innerHTML = `
                <i class="fas fa-info-circle"></i>
                <span>Position approximative</span>
            `;
            mapContainer.appendChild(infoDiv);
        }
    }
    
    /**
     * Affiche un message quand il n'y a pas de localisation
     */
    afficherSansLocalisation() {
        this.container.innerHTML = `
            <div class="localisation-card">
                <h3 class="localisation-card__title">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Localisation</span>
                </h3>
                <div class="localisation-unavailable">
                    <i class="fas fa-map-marked-alt"></i>
                    <p>Localisation non spécifiée</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Affiche un message d'erreur
     */
    afficherErreur() {
        this.container.innerHTML = `
            <div class="localisation-card">
                <h3 class="localisation-card__title">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Localisation</span>
                </h3>
                <p class="localisation-address">
                    <i class="fas fa-map-pin"></i>
                    <span>${echapperHTML(this.villeAffichage)}</span>
                </p>
                <div class="localisation-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Impossible de charger la carte</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Affiche un message quand Leaflet n'est pas disponible
     */
    afficherIndisponible() {
        this.container.innerHTML = `
            <div class="localisation-card">
                <h3 class="localisation-card__title">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Localisation</span>
                </h3>
                <p class="localisation-address">
                    <i class="fas fa-map-pin"></i>
                    <span>${echapperHTML(this.villeAffichage)}</span>
                </p>
                <div class="localisation-unavailable">
                    <i class="fas fa-map"></i>
                    <p>Carte interactive non disponible</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Nettoie la carte avant destruction
     */
    destroy() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
        this.marker = null;
    }
}
