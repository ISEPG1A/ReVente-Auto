/**
 * VUE LOCALISATION - AFFICHAGE CARTE INTERACTIVE
 * Gère l'affichage de la carte de localisation via Leaflet et OpenStreetMap.
 */

import { obtenirUrlApi, echapperHTML } from '../commun/utilitaires.js';

export default class VueLocalisation {
    
    constructor(containerId = 'localisation-container') {
        this.container = document.getElementById(containerId);
        this.urlApi = obtenirUrlApi('/localisation');
        this.map = null;
        this.marker = null;
    }
    
    async init(ville, codePostal = null, villeAffichage = null) {
        if (!this.container) {
            console.warn('Container localisation non trouvé');
            return;
        }
        
        this.ville = ville;
        this.codePostal = codePostal;
        this.villeAffichage = villeAffichage || (codePostal ? `${ville} (${codePostal})` : ville);
        
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
    
    async chargerCarte() {
        if (!this.ville || this.ville.trim() === '') {
            this.afficherSansLocalisation();
            return;
        }
        
        const mapContainer = this.container.querySelector('.localisation-map');
        if (!mapContainer) {
            this.afficherErreur();
            return;
        }
        
        if (mapContainer._leaflet_id) {
            mapContainer._leaflet_id = null;
            mapContainer.innerHTML = '';
        }
        
        this.map = L.map(mapContainer).setView([46.603354, 1.888334], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 18
        }).addTo(this.map);
        
        try {
            let url = `${this.urlApi}?ville=${encodeURIComponent(this.ville)}`;
            if (this.codePostal) {
                url += `&code_postal=${encodeURIComponent(this.codePostal)}`;
            }
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.data) {
                const { lat, lon, boundingbox, display_name, contour } = data.data;
                
                let zoomLevel = 13;
                if (boundingbox && boundingbox.length === 4) {
                    const bounds = [
                        [parseFloat(boundingbox[0]), parseFloat(boundingbox[2])],
                        [parseFloat(boundingbox[1]), parseFloat(boundingbox[3])]
                    ];
                    this.map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
                } else {
                    this.map.setView([lat, lon], zoomLevel);
                }
                
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
                
                if (contour && contour.coordinates) {
                    const polygonCoords = contour.coordinates[0].map(coord => [coord[1], coord[0]]);
                    
                    L.polygon(polygonCoords, {
                        color: '#fb9e0b',
                        fillColor: '#fb9e0b',
                        fillOpacity: 0.15,
                        weight: 3,
                        opacity: 0.8
                    }).addTo(this.map);
                    
                    const bounds = L.latLngBounds(polygonCoords);
                    this.map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
                } else if (boundingbox && boundingbox.length === 4) {
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
                this.afficherSansCoordonnees();
            }
        } catch (error) {
            console.error('Erreur géolocalisation:', error);
            this.afficherSansCoordonnees();
        }
    }
    
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
    
    afficherSucces() {
        const loading = this.container.querySelector('.localisation-loading');
        if (loading) {
            loading.remove();
        }
    }
    
    afficherSansCoordonnees() {
        const loading = this.container.querySelector('.localisation-loading');
        if (loading) {
            loading.remove();
        }
        
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
    
    destroy() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
        this.marker = null;
    }
}
