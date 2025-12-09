import { formaterMonnaie, echapperHTML, obtenirUrlApi } from '../../app.js';

export default class VueDetails {
    constructor() {
        this.idVehicule = new URLSearchParams(window.location.search).get('id');
        
        this.apiUrl = obtenirUrlApi('/vehicule/details');
        this.authUrl = obtenirUrlApi('/connexion');
        this.favorisUrl = obtenirUrlApi('/favoris');
        
        this.estFavori = false;
        this.utilisateurConnecte = null;
        
        this.initialiser();
    }

    initialiser() {
        this.elChargement = document.getElementById('chargement-details');
        this.elErreur = document.getElementById('erreur-details');
        this.elContenu = document.getElementById('contenu-details');
        
        // Charger le script Leaflet dynamiquement s'il n'est pas présent
        if (!window.L) {
            // Charger le CSS de Leaflet
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            link.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
            link.crossOrigin = '';
            document.head.appendChild(link);

            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
            script.crossOrigin = '';
            
            // Gestionnaire de succès
            script.onload = () => this.chargerDetails();
            
            // Gestionnaire d'erreur : on charge quand même les détails (sans la carte)
            script.onerror = () => {
                console.warn("Impossible de charger Leaflet (carte).");
                this.chargerDetails();
            };

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
            // Récupérer l'utilisateur courant (peut échouer si non connecté, ce n'est pas grave)
            let currentUser = null;
            try {
                const userRes = await fetch(`${this.authUrl}?action=me`);
                if (userRes.ok) {
                    const userData = await userRes.json();
                    currentUser = userData.user;
                    this.utilisateurConnecte = currentUser;
                }
            } catch (e) {
                console.warn("Utilisateur non connecté ou erreur auth", e);
            }

            // Vérifier si le véhicule est en favoris
            if (currentUser) {
                await this.verifierFavori();
            }

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
            this.elErreur.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <p>${msg}</p>
            `;
            this.elErreur.hidden = false;
        }
    }

    afficherDetails(v, currentUser) {
        if (this.elChargement) this.elChargement.hidden = true;
        if (this.elContenu) this.elContenu.hidden = false;

        const idVendeur = v.user_id || v.seller_id;
        const estProprietaire = currentUser && idVendeur && Number(idVendeur) === Number(currentUser.id);
        const estAdmin = currentUser && currentUser.role === 'admin';

        // Configurer le bouton favori (sauf si propriétaire)
        if (!estProprietaire) {
            this.configurerBoutonFavori();
        } else {
            // Cacher le bouton favori pour le propriétaire
            const btnFavori = document.getElementById('btn-favori-detail');
            if (btnFavori) btnFavori.style.display = 'none';
        }

        const km = (v.km !== null && v.km !== undefined) ? v.km : (Math.floor(Math.random() * 150000) + 10000);
        const carburant = (v.carburant !== null && v.carburant !== undefined) ? v.carburant : 'Essence';
        const boite = (v.boite !== null && v.boite !== undefined) ? v.boite : 'Manuelle';
        const description = (v.description !== null && v.description !== undefined) ? v.description : "Ce véhicule est en excellent état. Contrôle technique OK. Entretien à jour. Idéal pour jeune conducteur ou famille. N'hésitez pas à me contacter pour plus d'informations ou pour convenir d'un essai.";
        const ville = (v.ville !== null && v.ville !== undefined) ? v.ville : "Paris (75)";

        // Gestion de la galerie d'images
        const images = (v.images && v.images.length > 0) ? v.images : (v.image_path ? [v.image_path] : []);
        const conteneurImage = document.querySelector('.conteneur-image-principale');
        const rangeeMiniatures = document.querySelector('.rangee-miniatures');

        if (images.length > 0) {
             // Fonction pour afficher l'image principale
             const afficherImagePrincipale = (src) => {
                 if (conteneurImage) conteneurImage.innerHTML = `<img src="${src}" alt="${v.marque} ${v.modele}" style="width:100%; height:100%; object-fit:cover;">`;
             };
             
             // Afficher la première image par défaut
             afficherImagePrincipale(images[0]);

             // Générer les miniatures
             if (rangeeMiniatures) {
                 rangeeMiniatures.innerHTML = '';
                 // Si une seule image, pas besoin de miniatures
                 if (images.length > 1) {
                     images.forEach((src, index) => {
                         const div = document.createElement('div');
                         div.className = `miniature ${index === 0 ? 'active' : ''}`;
                         // Style inline pour s'assurer que l'image remplit la miniature
                         div.innerHTML = `<img src="${src}" style="width:100%; height:100%; object-fit:cover; border-radius:4px;">`;
                         div.onclick = () => {
                             afficherImagePrincipale(src);
                             document.querySelectorAll('.miniature').forEach(m => m.classList.remove('active'));
                             div.classList.add('active');
                         };
                         rangeeMiniatures.appendChild(div);
                     });
                 }
             }
        } else {
             if (conteneurImage) conteneurImage.innerHTML = `
                <div class="image-placeholder-lg">
                    <i class="fas fa-car fa-5x"></i>
                </div>`;
             if (rangeeMiniatures) rangeeMiniatures.innerHTML = '';
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
        
        // Mise à jour de l'avatar du vendeur (nouveau sélecteur)
        const avatarVendeur = document.querySelector('.details-seller__avatar');
        if (avatarVendeur) {
            if (v.seller_avatar) {
                avatarVendeur.innerHTML = `<img src="${echapperHTML(v.seller_avatar)}" alt="Vendeur" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`;
                avatarVendeur.style.overflow = 'hidden';
            } else {
                avatarVendeur.innerHTML = `<i class="fas fa-user"></i>`;
            }
        }
        
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

        // Mise à jour du badge année sur l'image
        const badgeAnnee = document.getElementById('badge-annee');
        if (badgeAnnee) {
            badgeAnnee.innerHTML = `<i class="fas fa-calendar-alt"></i> ${v.annee}`;
        }

        // Carte vendeur (nouveau sélecteur)
        const carteVendeur = document.querySelector('.details-card--seller');
        const actionsVendeur = document.querySelector('.details-seller__actions');
        
        if (estProprietaire || estAdmin) {
            // Supprimer les boutons de contact pour le propriétaire
            if (actionsVendeur) {
                actionsVendeur.innerHTML = '';
                
                // Bouton modifier l'annonce
                const boutonModifier = document.createElement('a');
                boutonModifier.href = `modification_vehicule?id=${v.id}`;
                boutonModifier.className = 'details-btn details-btn--secondary';
                boutonModifier.innerHTML = '<i class="fas fa-edit"></i> <span>Modifier l\'annonce</span>';
                actionsVendeur.appendChild(boutonModifier);
                
                // Bouton supprimer l'annonce
                const boutonSupprimer = document.createElement('button');
                boutonSupprimer.className = 'details-btn details-btn--danger';
                boutonSupprimer.innerHTML = '<i class="fas fa-trash"></i> <span>Supprimer l\'annonce</span>';
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
                actionsVendeur.appendChild(boutonSupprimer);
            }
        } else {
            // Visiteur normal - configurer les boutons contact
            const boutonTelephone = document.getElementById('bouton-telephone');
            if (boutonTelephone) {
                if (v.seller_phone) {
                    boutonTelephone.onclick = () => {
                        boutonTelephone.innerHTML = `<i class="fas fa-phone"></i> <span>${echapperHTML(v.seller_phone)}</span>`;
                        boutonTelephone.classList.add('details-btn--revealed');
                    };
                } else {
                    boutonTelephone.style.display = 'none';
                }
            }
            
            const boutonContact = document.getElementById('bouton-contact');
            if (boutonContact) {
                boutonContact.onclick = () => {
                    window.location.href = `messagerie?vehicle_id=${v.id}&seller_id=${v.user_id || v.seller_id}`;
                };
            }
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

    /**
     * Vérifie si le véhicule est dans les favoris de l'utilisateur
     */
    async verifierFavori() {
        try {
            const res = await fetch(`${this.favorisUrl}?ids_only=1`);
            if (res.ok) {
                const ids = await res.json();
                this.estFavori = ids.includes(parseInt(this.idVehicule));
                this.mettreAJourBoutonFavori();
            }
        } catch (e) {
            console.warn("Erreur vérification favoris", e);
        }
    }

    /**
     * Met à jour l'apparence du bouton favori
     */
    mettreAJourBoutonFavori() {
        const btnFavori = document.getElementById('btn-favori-detail');
        if (!btnFavori) return;

        if (this.estFavori) {
            btnFavori.innerHTML = '<i class="fas fa-heart"></i>';
            btnFavori.classList.add('active');
            btnFavori.title = 'Retirer des favoris';
        } else {
            btnFavori.innerHTML = '<i class="far fa-heart"></i>';
            btnFavori.classList.remove('active');
            btnFavori.title = 'Ajouter aux favoris';
        }
    }

    /**
     * Configure le bouton favori
     */
    configurerBoutonFavori() {
        const btnFavori = document.getElementById('btn-favori-detail');
        if (!btnFavori) return;

        btnFavori.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            // Vérifier si l'utilisateur est connecté
            if (!this.utilisateurConnecte) {
                if (confirm('Vous devez être connecté pour ajouter aux favoris. Voulez-vous vous connecter ?')) {
                    window.location.href = 'connexion';
                }
                return;
            }

            try {
                if (this.estFavori) {
                    // Retirer des favoris
                    const res = await fetch(`${this.favorisUrl}?id=${this.idVehicule}`, {
                        method: 'DELETE'
                    });
                    if (res.ok) {
                        this.estFavori = false;
                        this.mettreAJourBoutonFavori();
                    }
                } else {
                    // Ajouter aux favoris
                    const res = await fetch(this.favorisUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ vehicle_id: parseInt(this.idVehicule) })
                    });
                    if (res.ok) {
                        this.estFavori = true;
                        this.mettreAJourBoutonFavori();
                    }
                }
            } catch (e) {
                console.error("Erreur favori", e);
            }
        });

        // Mettre à jour l'apparence initiale
        this.mettreAJourBoutonFavori();
    }
}