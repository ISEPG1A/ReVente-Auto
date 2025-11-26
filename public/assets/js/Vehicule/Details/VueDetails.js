import { formaterMonnaie, echapperHTML, getApiUrl } from '../../app.js';

export default class VueDetails {
    constructor() {
        this.idVehicule = new URLSearchParams(window.location.search).get('id');
        
        this.apiUrl = getApiUrl('/vehicule/details');
        this.authUrl = getApiUrl('/connexion');
        
        this.initialiser();
    }

    initialiser() {
        this.elChargement = document.getElementById('chargement-details');
        this.elErreur = document.getElementById('erreur-details');
        this.elContenu = document.getElementById('contenu-details');
        
        // Charger le script Leaflet dynamiquement s'il n'est pas présent
        if (!window.L) {
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
            script.crossOrigin = '';
            script.onload = () => this.chargerDetails();
            document.head.appendChild(script);
        } else {
            this.chargerDetails();
        }
    }

    async chargerDetails() {
        if (!this.idVehicule) {
            this.afficherErreur("Aucun véhicule spécifié.");
            return;
        }

        try {
            const userRes = await fetch(`${this.authUrl}?action=me`);
            const userData = await userRes.json();
            const currentUser = userData.user;

            const res = await fetch(`${this.apiUrl}?id=${this.idVehicule}`);
            if (!res.ok) throw new Error("Véhicule introuvable ou erreur serveur.");
            
            const vehicule = await res.json();
            this.afficherDetails(vehicule, currentUser);
        } catch (err) {
            this.afficherErreur(err.message);
        }
    }

    afficherErreur(msg) {
        if (this.elChargement) this.elChargement.hidden = true;
        if (this.elErreur) {
            this.elErreur.textContent = msg;
            this.elErreur.hidden = false;
        }
    }

    afficherDetails(v, currentUser) {
        if (this.elChargement) this.elChargement.hidden = true;
        if (this.elContenu) this.elContenu.hidden = false;

        const idVendeur = v.user_id || v.seller_id;
        const estProprietaire = currentUser && idVendeur && Number(idVendeur) === Number(currentUser.id);
        const estAdmin = currentUser && currentUser.role === 'admin';

        const km = (v.km !== null && v.km !== undefined) ? v.km : (Math.floor(Math.random() * 150000) + 10000);
        const carburant = (v.carburant !== null && v.carburant !== undefined) ? v.carburant : 'Essence';
        const boite = (v.boite !== null && v.boite !== undefined) ? v.boite : 'Manuelle';
        const description = (v.description !== null && v.description !== undefined) ? v.description : "Ce véhicule est en excellent état. Contrôle technique OK. Entretien à jour. Idéal pour jeune conducteur ou famille. N'hésitez pas à me contacter pour plus d'informations ou pour convenir d'un essai.";
        const ville = (v.ville !== null && v.ville !== undefined) ? v.ville : "Paris (75)";

        if (v.image_path) {
             const conteneurImage = document.querySelector('.conteneur-image-principale');
             if (conteneurImage) conteneurImage.innerHTML = `<img src="${v.image_path}" alt="${v.marque} ${v.modele}" style="width:100%; height:100%; object-fit:cover;">`;
        } else {
             const conteneurImage = document.querySelector('.conteneur-image-principale');
             if (conteneurImage) conteneurImage.innerHTML = `
                <div class="image-placeholder-lg">
                    <i class="fas fa-car fa-5x"></i>
                </div>`;
        }

        const setContent = (id, text) => {
            const el = document.getElementById(id);
            if (el) el.textContent = text;
        };

        setContent('titre-detail', `${v.marque} ${v.modele}`);
        setContent('sous-titre-detail', `Réf. #${v.id} • Publié le ${new Date(v.created_at).toLocaleDateString()}`);
        setContent('prix-detail', formaterMonnaie(v.prix));
        
        const setTag = (id, text) => {
            const el = document.getElementById(id);
            if (el) el.querySelector('span').textContent = text;
        };

        setTag('tag-annee', v.annee);
        setTag('tag-km', km.toLocaleString() + ' km');
        setTag('tag-carburant', carburant);
        setTag('tag-boite', boite);

        const nomVendeur = (v.seller_first_name || v.seller_last_name) 
            ? `${v.seller_first_name || ''} ${v.seller_last_name || ''}`.trim() 
            : 'Vendeur inconnu';
        setContent('nom-vendeur', nomVendeur);
        
        setContent('localisation-detail', ville);
        this.initMap(ville);

        setContent('description-detail', description);

        setContent('spec-marque', v.marque);
        setContent('spec-modele', v.modele);
        setContent('spec-annee', v.annee);
        setContent('spec-date', new Date(v.created_at).toLocaleDateString());
        setContent('spec-km', km.toLocaleString() + ' km');
        setContent('spec-carburant', carburant);
        setContent('spec-boite', boite);

        const carteVendeur = document.querySelector('.carte-vendeur');
        
        if (estProprietaire || estAdmin) {
            const boutonsContact = carteVendeur.querySelectorAll('.bouton-contact');
            boutonsContact.forEach(btn => btn.remove());
            
            const boutonSupprimer = document.createElement('button');
            boutonSupprimer.className = 'bouton bouton--danger bouton-contact';
            boutonSupprimer.innerHTML = '<i class="fas fa-trash"></i> Supprimer l\'annonce';
            boutonSupprimer.onclick = async () => {
                if (!confirm('Êtes-vous sûr de vouloir supprimer cette annonce ? Cette action est irréversible.')) return;
                
                try {
                    const res = await fetch(`${this.apiUrl}?id=${v.id}`, { method: 'DELETE' });
                    const data = await res.json();
                    
                    if (res.ok) {
                        alert('Annonce supprimée avec succès.');
                        window.location.href = 'galerie';
                    } else {
                        alert(data.error || 'Erreur lors de la suppression');
                    }
                } catch (e) {
                    alert('Erreur serveur');
                }
            };
            carteVendeur.appendChild(boutonSupprimer);
        } else {
            const boutonTelephone = document.getElementById('bouton-telephone');
            if (v.seller_phone) {
                boutonTelephone.onclick = () => {
                    boutonTelephone.innerHTML = `<i class="fas fa-phone"></i> ${echapperHTML(v.seller_phone)}`;
                    boutonTelephone.classList.remove('bouton--fantome');
                };
            } else {
                boutonTelephone.style.display = 'none';
            }
            
            const boutonContact = document.getElementById('bouton-contact');
            boutonContact.onclick = () => {
                window.location.href = `messagerie?vehicle_id=${v.id}&seller_id=${v.user_id || v.seller_id}`;
            };
        }
    }

    async initMap(ville) {
        const conteneurCarte = document.getElementById('map');
        if (!conteneurCarte) return;

        if (conteneurCarte._leaflet_id) {
            conteneurCarte._leaflet_id = null;
            conteneurCarte.innerHTML = '';
        }

        if (!window.L) return;

        const map = L.map('map').setView([46.603354, 1.888334], 5);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        if (!ville) return;

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(ville)}`);
            const data = await response.json();

            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);
                
                map.setView([lat, lon], 12);
                L.marker([lat, lon]).addTo(map)
                    .bindPopup(`<i class="fas fa-map-marker-alt"></i> <b>${echapperHTML(ville)}</b>`)
                    .openPopup();
            }
        } catch (e) {
            console.error("Erreur lors du chargement de la carte", e);
        }
    }
}