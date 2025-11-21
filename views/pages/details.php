<!-- 
Page de détails d'un véhicule
Affiche toutes les informations, photos et contact
-->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<section id="details-vehicule" class="section">
  <div class="container">
    
    <!-- Bouton retour -->
    <a href="galerie" class="bouton bouton--fantome" style="margin-bottom: 20px; display: inline-flex; align-items: center; gap: 8px;">
      <i class="fas fa-arrow-left"></i> Retour à la galerie
    </a>

    <div id="loading-details" style="text-align: center; padding: 50px;">
      <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--couleur-principale);"></i>
    </div>

    <div id="error-details" class="msg msg--err" hidden></div>

    <div id="content-details" class="details-grid" hidden>
      
      <!-- Colonne Gauche : Photos -->
      <div class="details-gallery">
        <div class="main-image-container">
            <div class="placeholder-image-lg">
                <i class="fas fa-car fa-5x"></i>
            </div>
            <!-- <img src="..." alt="..." class="main-image"> -->
        </div>
        <!-- Miniatures (simulées pour l'instant) -->
        <div class="thumbnails-row">
            <div class="thumbnail active"><i class="fas fa-car"></i></div>
            <div class="thumbnail"><i class="fas fa-angle-right"></i></div>
            <div class="thumbnail"><i class="fas fa-angle-right"></i></div>
        </div>
      </div>

      <!-- Colonne Droite : Infos principales & Contact -->
      <div class="details-sidebar">
        
        <div class="card-info-principal">
            <h1 id="detail-title" class="detail-title">Chargement...</h1>
            <p id="detail-subtitle" class="detail-subtitle">...</p>
            <div id="detail-price" class="detail-price">-- €</div>
            
            <div class="detail-tags">
                <span class="tag" id="tag-year"><i class="fas fa-calendar-alt"></i> <span>--</span></span>
                <span class="tag" id="tag-km"><i class="fas fa-tachometer-alt"></i> <span>-- km</span></span>
                <span class="tag" id="tag-fuel"><i class="fas fa-gas-pump"></i> <span>--</span></span>
                <span class="tag" id="tag-gearbox"><i class="fas fa-cog"></i> <span>--</span></span>
            </div>

            <div class="seller-card">
                <div class="seller-header">
                    <div class="seller-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="seller-info">
                        <h3 id="seller-name">Vendeur</h3>
                        <p class="seller-status">Particulier</p>
                    </div>
                </div>
                <button id="btn-contact" class="bouton bouton-contact">
                    <i class="fas fa-envelope"></i> Envoyer un message
                </button>
                <button id="btn-phone" class="bouton bouton--fantome bouton-contact">
                    <i class="fas fa-phone"></i> Voir le numéro
                </button>
            </div>
        </div>

        <div class="card-location">
            <h3><i class="fas fa-map-marker-alt"></i> Localisation</h3>
            <p id="detail-location">France</p>
            <!-- Carte dynamique -->
            <div id="map" style="height: 200px; width: 100%; border-radius: 8px; margin-top: 10px; z-index: 0;"></div>
        </div>

      </div>

      <!-- Bas de page : Description détaillée -->
      <div class="details-description">
        <h2>Description</h2>
        <p id="detail-description" class="description-text">
            Aucune description fournie pour ce véhicule.
        </p>

        <h2 style="margin-top: 20px;">Caractéristiques détaillées</h2>
        <div class="specs-grid">
            <div class="spec-row">
                <span class="spec-label">Marque</span>
                <span class="spec-value" id="spec-marque">--</span>
            </div>
            <div class="spec-row">
                <span class="spec-label">Modèle</span>
                <span class="spec-value" id="spec-modele">--</span>
            </div>
            <div class="spec-row">
                <span class="spec-label">Année modèle</span>
                <span class="spec-value" id="spec-annee">--</span>
            </div>
            <div class="spec-row">
                <span class="spec-label">Mise en circulation</span>
                <span class="spec-value" id="spec-date">--</span>
            </div>
             <div class="spec-row">
                <span class="spec-label">Kilométrage</span>
                <span class="spec-value" id="spec-km">--</span>
            </div>
             <div class="spec-row">
                <span class="spec-label">Carburant</span>
                <span class="spec-value" id="spec-carburant">--</span>
            </div>
             <div class="spec-row">
                <span class="spec-label">Boîte de vitesse</span>
                <span class="spec-value" id="spec-boite">--</span>
            </div>
        </div>
      </div>

    </div>
  </div>
</section>

<style>
    .details-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 15px;
        margin-top: 15px;
    }

    /* Galerie Photos */
    .main-image-container {
        width: 100%;
        height: 400px;
        background: var(--surface-2);
        border-radius: var(--rayon-bordure);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--bordure);
    }
    
    .placeholder-image-lg {
        color: var(--texte-attenue);
    }

    .thumbnails-row {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    .thumbnail {
        width: 80px;
        height: 60px;
        background: var(--surface-2);
        border: 1px solid var(--bordure);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--texte-attenue);
    }

    .thumbnail.active {
        border-color: var(--couleur-principale);
        color: var(--couleur-principale);
    }

    /* Sidebar Info */
    .card-info-principal, .card-location {
        background: var(--surface-2);
        border: 1px solid var(--bordure);
        border-radius: var(--rayon-bordure);
        padding: 15px;
        margin-bottom: 15px;
    }

    .detail-title {
        font-size: 1.8rem;
        margin: 0 0 5px 0;
        color: var(--texte);
    }

    .detail-subtitle {
        color: var(--texte-attenue);
        margin: 0 0 15px 0;
        font-size: 0.9rem;
    }

    .detail-price {
        font-size: 2rem;
        font-weight: 700;
        color: var(--couleur-principale);
        margin-bottom: 20px;
    }

    .detail-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 25px;
    }

    .tag {
        background: var(--surface);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 6px;
        border: 1px solid var(--bordure);
    }
    
    .tag i { color: var(--couleur-principale); }

    /* Carte Vendeur */
    .seller-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--bordure);
    }

    .seller-avatar {
        width: 50px;
        height: 50px;
        background: var(--surface);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: var(--texte-attenue);
    }

    .seller-info h3 { margin: 0; font-size: 1.1rem; }
    .seller-status { margin: 0; font-size: 0.85rem; color: var(--texte-attenue); }

    .bouton-contact {
        width: 100%;
        margin-bottom: 10px;
        justify-content: center;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Description */
    .details-description {
        grid-column: 1 / -1; /* Prend toute la largeur en bas si besoin, ou juste gauche */
        background: var(--surface-2);
        padding: 20px;
        border-radius: var(--rayon-bordure);
        border: 1px solid var(--bordure);
    }
    
    @media (min-width: 900px) {
        .details-description {
            grid-column: 1 / 2; /* Reste à gauche sur desktop */
        }
    }

    .description-text {
        white-space: pre-line;
        color: var(--texte-attenue);
        line-height: 1.8;
    }

    .specs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }

    .spec-row {
        display: flex;
        justify-content: space-between;
        padding: 10px;
        background: var(--surface);
        border-radius: 8px;
    }

    .spec-label { color: var(--texte-attenue); }
    .spec-value { font-weight: 600; }

    .map-placeholder {
        width: 100%;
        height: 150px;
        background: var(--surface);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--texte-attenue);
        margin-top: 10px;
    }

    @media (max-width: 900px) {
        .details-grid {
            grid-template-columns: 1fr;
        }
        .main-image-container {
            height: 250px;
        }
    }
</style>

<script type="module">
    import { formaterMonnaie, echapperHTML } from './assets/js/app.js';

    const metaApiBase = document.querySelector('meta[name="api-base"]');
    const baseUrl = metaApiBase ? metaApiBase.getAttribute('content') : './api';
    const URL_API = baseUrl + '/api.php';

    // Récupérer l'ID depuis l'URL
    const params = new URLSearchParams(window.location.search);
    const idVehicule = params.get('id');

    const loadingEl = document.getElementById('loading-details');
    const errorEl = document.getElementById('error-details');
    const contentEl = document.getElementById('content-details');

    async function chargerDetails() {
        if (!idVehicule) {
            afficherErreur("Aucun véhicule spécifié.");
            return;
        }

        try {
            // Récupérer l'utilisateur courant pour vérifier si c'est le propriétaire
            const userRes = await fetch(`${baseUrl}/auth.php?action=me`);
            const userData = await userRes.json();
            const currentUser = userData.user;

            const res = await fetch(`${URL_API}?id=${idVehicule}`);
            if (!res.ok) throw new Error("Véhicule introuvable ou erreur serveur.");
            
            const vehicule = await res.json();
            afficherDetails(vehicule, currentUser);
        } catch (err) {
            afficherErreur(err.message);
        }
    }

    function afficherErreur(msg) {
        loadingEl.hidden = true;
        errorEl.textContent = msg;
        errorEl.hidden = false;
    }

    function afficherDetails(v, currentUser) {
        loadingEl.hidden = true;
        contentEl.hidden = false;

        // Vérifier si l'utilisateur est propriétaire ou admin
        const idVendeur = v.user_id || v.seller_id;
        const estProprietaire = currentUser && idVendeur && Number(idVendeur) === Number(currentUser.id);
        const estAdmin = currentUser && currentUser.role === 'admin';

        // Utilisation des données réelles si disponibles (même vides), sinon fallback sur simulation pour les anciens véhicules (NULL)
        const km = (v.km !== null && v.km !== undefined) ? v.km : (Math.floor(Math.random() * 150000) + 10000);
        const carburant = (v.carburant !== null && v.carburant !== undefined) ? v.carburant : 'Essence';
        const boite = (v.boite !== null && v.boite !== undefined) ? v.boite : 'Manuelle';
        const description = (v.description !== null && v.description !== undefined) ? v.description : "Ce véhicule est en excellent état. Contrôle technique OK. Entretien à jour. Idéal pour jeune conducteur ou famille. N'hésitez pas à me contacter pour plus d'informations ou pour convenir d'un essai.";
        const ville = (v.ville !== null && v.ville !== undefined) ? v.ville : "Paris (75)";

        // Gestion de l'image
        if (v.image_path) {
             const mainImageContainer = document.querySelector('.main-image-container');
             mainImageContainer.innerHTML = `<img src="${v.image_path}" alt="${v.marque} ${v.modele}" style="width:100%; height:100%; object-fit:cover;">`;
        } else {
             // Image par défaut si pas d'image
             const mainImageContainer = document.querySelector('.main-image-container');
             mainImageContainer.innerHTML = `
                <div class="placeholder-image-lg">
                    <i class="fas fa-car fa-5x"></i>
                </div>`;
        }

        // Remplissage des champs
        document.getElementById('detail-title').textContent = `${v.marque} ${v.modele}`;
        document.getElementById('detail-subtitle').textContent = `Réf. #${v.id} • Publié le ${new Date(v.created_at).toLocaleDateString()}`;
        document.getElementById('detail-price').textContent = formaterMonnaie(v.prix);
        
        document.getElementById('tag-year').querySelector('span').textContent = v.annee;
        document.getElementById('tag-km').querySelector('span').textContent = km.toLocaleString() + ' km';
        document.getElementById('tag-fuel').querySelector('span').textContent = carburant;
        document.getElementById('tag-gearbox').querySelector('span').textContent = boite;

        // Vendeur
        const nomVendeur = (v.seller_first_name || v.seller_last_name) 
            ? `${v.seller_first_name || ''} ${v.seller_last_name || ''}`.trim() 
            : 'Vendeur inconnu';
        document.getElementById('seller-name').textContent = nomVendeur;
        
        // Localisation
        document.getElementById('detail-location').textContent = ville;
        initMap(ville);

        // Description
        document.getElementById('detail-description').textContent = description;

        // Specs
        document.getElementById('spec-marque').textContent = v.marque;
        document.getElementById('spec-modele').textContent = v.modele;
        document.getElementById('spec-annee').textContent = v.annee;
        document.getElementById('spec-date').textContent = new Date(v.created_at).toLocaleDateString();
        document.getElementById('spec-km').textContent = km.toLocaleString() + ' km';
        document.getElementById('spec-carburant').textContent = carburant;
        document.getElementById('spec-boite').textContent = boite;

        // Gestion des boutons d'action (Contact ou Suppression)
        const sellerCard = document.querySelector('.seller-card');
        
        if (estProprietaire || estAdmin) {
            // Remplacer les boutons de contact par le bouton de suppression
            const contactButtons = sellerCard.querySelectorAll('.bouton-contact');
            contactButtons.forEach(btn => btn.remove());
            
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'bouton button--danger bouton-contact';
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Supprimer l\'annonce';
            deleteBtn.onclick = async () => {
                if (!confirm('Êtes-vous sûr de vouloir supprimer cette annonce ? Cette action est irréversible.')) return;
                
                try {
                    const res = await fetch(`${URL_API}?id=${v.id}`, { method: 'DELETE' });
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
            sellerCard.appendChild(deleteBtn);
        } else {
            // Bouton téléphone
            const btnPhone = document.getElementById('btn-phone');
            if (v.seller_phone) {
                btnPhone.onclick = () => {
                    btnPhone.innerHTML = `<i class="fas fa-phone"></i> ${echapperHTML(v.seller_phone)}`;
                    btnPhone.classList.remove('bouton--fantome');
                };
            } else {
                btnPhone.style.display = 'none';
            }
            
            // Bouton email
            const btnContact = document.getElementById('btn-contact');
            btnContact.onclick = () => {
                window.location.href = `mailto:${v.seller_email}?subject=Intéressé par votre ${v.marque} ${v.modele}`;
            };
        }
    }

    async function initMap(ville) {
        const mapContainer = document.getElementById('map');
        if (!mapContainer) return;

        // Si une carte existe déjà, on la nettoie (important si on recharge sans recharger la page)
        if (mapContainer._leaflet_id) {
            mapContainer._leaflet_id = null;
            mapContainer.innerHTML = '';
        }

        // Initialiser la carte (vue par défaut sur la France)
        const map = L.map('map').setView([46.603354, 1.888334], 5);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        if (!ville) return;

        try {
            // Géocodage de la ville via Nominatim
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(ville)}`);
            const data = await response.json();

            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);
                
                // Centrer la carte et ajouter un marqueur
                map.setView([lat, lon], 12);
                L.marker([lat, lon]).addTo(map)
                    .bindPopup(`<i class="fas fa-map-marker-alt"></i> <b>${echapperHTML(ville)}</b>`)
                    .openPopup();
            }
        } catch (e) {
            console.error("Erreur lors du chargement de la carte", e);
        }
    }

    document.addEventListener('DOMContentLoaded', chargerDetails);
</script>
