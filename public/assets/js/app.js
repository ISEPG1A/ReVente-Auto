// Gestion des véhicules (galerie)

// Récupération de l'URL de base de l'API depuis les métadonnées
const metaApiBase = document.querySelector('meta[name="api-base"]');
const URL_API = (metaApiBase ? metaApiBase.getAttribute('content') : './api') + '/api.php';

// Fonction utilitaire pour sélectionner un élément DOM (querySelector simplifié)
export const selecteur = (s, el = document) => el.querySelector(s);
export const selecteurTous = (s, el = document) => el.querySelectorAll(s);

// Fonction pour formater un nombre en monnaie française (euros)
export const formaterMonnaie = (n) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(n);

// Fonction de débounce pour limiter la fréquence d'exécution d'une fonction
export const debouncer = (fn, delai = 250) => {
  let minuteur;
  return (...args) => {
    clearTimeout(minuteur);
    minuteur = setTimeout(() => fn(...args), delai);
  };
};

// État global de l'application contenant les données des véhicules
export const etatApplication = { 
  vehicules: [],           // Liste complète des véhicules
  filtres: [],            // Liste filtrée des véhicules
  favoris: [],            // Liste des IDs des favoris
  criteres: {             // Critères de filtrage
    type: 'all',
    marque: 'all',
    anneeMin: null,
    anneeMax: null,
    prixMin: null,
    prixMax: null,
    carburant: [],
    boite: []
  },
  tri: 'recent',          // Type de tri appliqué
  utilisateur: null       // Utilisateur connecté
};

// Fonction pour définir l'état de chargement d'un élément
export function definirChargement(element, enChargement) {
  element && element.setAttribute('aria-busy', String(enChargement));
}

// Fonction pour afficher un message dans un conteneur
export function afficherMessage(conteneur, texte, type = 'ok') {
  if (conteneur) conteneur.innerHTML = `<div class="msg msg--${type === 'ok' ? 'ok' : 'err'}">${texte}</div>`;
}

// Fonction pour échapper les caractères HTML et éviter les injections XSS
export function echapperHTML(s) {
  return String(s).replace(/[&<>"]+/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
}

// Fonction asynchrone pour récupérer les favoris de l'utilisateur
async function recupererFavoris() {
    if (!etatApplication.utilisateur) return;
    try {
        const url = (metaApiBase ? metaApiBase.getAttribute('content') : './api') + '/favorites.php?ids_only=1';
        const reponse = await fetch(url);
        if (reponse.ok) {
            etatApplication.favoris = await reponse.json();
        }
    } catch (e) {
        console.error('Erreur chargement favoris', e);
    }
}

// Fonction pour rendre la liste des véhicules dans le DOM
function afficherListe(elementListe, elementVide) {
  if (!elementListe || !elementVide) return;
  elementListe.innerHTML = '';
  const elements = etatApplication.filtres;
  
  // Si aucun véhicule trouvé, afficher le message vide
  if (!elements.length) { 
    elementVide.hidden = false; 
    return; 
  }
  
  elementVide.hidden = true;
  const fragment = document.createDocumentFragment();
  
  // Parcourir chaque véhicule et créer sa carte
  for (const vehicule of elements) {
    const elementListeItem = document.createElement('li');
    elementListeItem.className = 'vehicle-card-horizontal';
    
    // Vérifier si l'utilisateur est propriétaire du véhicule ou administrateur
    // On vérifie user_id (retourné par GET) ou seller_id (retourné par POST)
    const idVendeur = vehicule.user_id || vehicule.seller_id;
    const estProprietaire = etatApplication.utilisateur && idVendeur && Number(idVendeur) === Number(etatApplication.utilisateur.id);
    const estAdmin = etatApplication.utilisateur && etatApplication.utilisateur.role === 'admin';
    
    // Vérifier si le véhicule est en favori
    const estFavori = etatApplication.favoris.includes(vehicule.id);
    const classeCoeur = estFavori ? 'fas fa-heart' : 'far fa-heart';
    const styleCoeur = estFavori ? 'color: var(--couleur-danger);' : '';

    // Données simulées pour l'affichage (à remplacer par de vraies données si disponibles dans l'API)
    // On vérifie explicitement null/undefined pour ne pas écraser une valeur vide ("") ou 0
    const km = (vehicule.km !== null && vehicule.km !== undefined) ? vehicule.km : (Math.floor(Math.random() * 150000) + 10000);
    const carburant = (vehicule.carburant !== null && vehicule.carburant !== undefined) ? vehicule.carburant : 'Essence';
    const boite = (vehicule.boite !== null && vehicule.boite !== undefined) ? vehicule.boite : 'Manuelle';
    const ville = (vehicule.ville !== null && vehicule.ville !== undefined) ? vehicule.ville : 'France';
    
    // Gestion de l'image
    let imageHtml = `
        <div style="width:100%; height:100%; background: #252a35; display:flex; align-items:center; justify-content:center; color:#4a505c;">
            <i class="fas fa-car fa-3x"></i>
        </div>`;
    
    if (vehicule.image_path) {
        imageHtml = `<img src="${vehicule.image_path}" alt="${echapperHTML(vehicule.marque)}" style="width:100%; height:100%; object-fit:cover;">`;
    }

    // Construire le HTML de la carte horizontale
    elementListeItem.innerHTML = `
      <div class="card-image-container">
        ${imageHtml}
      </div>
      <div class="card-details">
        <div class="card-header-row">
            <h3 class="card-title-h">${echapperHTML(vehicule.marque)} ${echapperHTML(vehicule.modele)}</h3>
            <div class="card-actions-h">
                <button class="btn-heart" data-id="${vehicule.id}" title="${estFavori ? 'Retirer des favoris' : 'Ajouter aux favoris'}" style="${styleCoeur}">
                    <i class="${classeCoeur}"></i>
                </button>
            </div>
        </div>
        <div class="card-specs-row">
            <span class="spec-item" title="Année"><i class="fas fa-calendar-alt"></i> ${vehicule.annee}</span>
            <span class="spec-item" title="Kilométrage"><i class="fas fa-tachometer-alt"></i> ${km.toLocaleString()} km</span>
            <span class="spec-item" title="Carburant"><i class="fas fa-gas-pump"></i> ${echapperHTML(carburant)}</span>
            <span class="spec-item" title="Boîte de vitesse"><i class="fas fa-cog"></i> ${echapperHTML(boite)}</span>
        </div>
        <div class="card-footer-row" style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="spec-item" title="Localisation" style="font-size:0.9rem; color:var(--texte-attenue);"><i class="fas fa-map-marker-alt"></i> ${echapperHTML(ville)}</span>
                ${estProprietaire || estAdmin ? `<button class="button button--danger btn-delete" data-id="${vehicule.id}" style="padding: 4px 8px; font-size:0.8rem;" title="Supprimer l'annonce"><i class="fas fa-trash"></i></button>` : ''}
            </div>
            <div class="card-price-h" style="margin:0;">${formaterMonnaie(vehicule.prix)}</div>
        </div>
      </div>
    `;
    
    // Gestion du clic sur le coeur (Favoris)
    const btnHeart = elementListeItem.querySelector('.btn-heart');
    if (btnHeart) {
        btnHeart.addEventListener('click', async (e) => {
            e.stopPropagation(); // Empêcher le clic sur la carte
            
            if (!etatApplication.utilisateur) {
                alert('Veuillez vous connecter pour gérer vos favoris.');
                return;
            }
            
            const id = vehicule.id;
            const estDejaFavori = etatApplication.favoris.includes(id);
            const method = estDejaFavori ? 'DELETE' : 'POST';
            const url = (metaApiBase ? metaApiBase.getAttribute('content') : './api') + '/favorites.php' + (estDejaFavori ? `?id=${id}` : '');
            
            try {
                const options = { method };
                if (!estDejaFavori) {
                    options.headers = { 'Content-Type': 'application/json' };
                    options.body = JSON.stringify({ vehicle_id: id });
                }
                
                const res = await fetch(url, options);
                if (res.ok) {
                    // Mettre à jour l'état local
                    if (estDejaFavori) {
                        etatApplication.favoris = etatApplication.favoris.filter(fid => fid !== id);
                    } else {
                        etatApplication.favoris.push(id);
                    }
                    
                    // Mettre à jour l'UI
                    const icon = btnHeart.querySelector('i');
                    if (estDejaFavori) {
                        icon.className = 'far fa-heart';
                        btnHeart.style.color = '';
                        btnHeart.title = 'Ajouter aux favoris';
                    } else {
                        icon.className = 'fas fa-heart';
                        btnHeart.style.color = 'var(--couleur-danger)';
                        btnHeart.title = 'Retirer des favoris';
                    }
                } else {
                    const err = await res.json();
                    if (err.code === 'NO_TABLE') {
                        alert("La fonctionnalité de favoris n'est pas encore activée sur le serveur (table manquante).");
                    } else {
                        console.error(err);
                    }
                }
            } catch (err) {
                console.error(err);
            }
        });
    }

    // Rendre la carte cliquable pour voir les détails
    elementListeItem.addEventListener('click', (e) => {
        // Ne pas déclencher si on clique sur un bouton interactif
        if (e.target.closest('button') || e.target.closest('a')) return;
        
        // Redirection vers la page de détails
        window.location.href = `vehicule?id=${vehicule.id}`;
    });
    
    // Gestionnaire d'événement pour la suppression
    const btnDelete = elementListeItem.querySelector('.btn-delete');
    if (btnDelete) {
        btnDelete.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (!confirm('Supprimer cette annonce ?')) return;
            try {
                const url = new URL(URL_API, window.location.href);
                url.searchParams.set('id', vehicule.id);
                const reponse = await fetch(url, { 
                    method: 'DELETE', 
                    headers: { 'Accept': 'application/json' } 
                });
                const donnees = await reponse.json().catch(() => ({}));
                if (!reponse.ok) throw new Error(donnees?.error || 'Suppression impossible');
                await recupererVehicules();
            } catch (erreur) {
                alert(erreur.message || 'Erreur');
            }
        });
    }

    fragment.appendChild(elementListeItem);
  }
  elementListe.appendChild(fragment);
}

// Fonction pour appliquer les filtres et le tri sur la liste des véhicules
function appliquerFiltresEtTri() {
  let tableauVehicules = [...etatApplication.vehicules];
  const c = etatApplication.criteres;

  // Filtrage
  tableauVehicules = tableauVehicules.filter(v => {
    // Type
    if (c.type !== 'all' && v.type !== c.type) return false; // Supposant que 'type' existe dans l'objet véhicule
    
    // Marque
    if (c.marque !== 'all' && v.marque !== c.marque) return false;

    // Année
    if (c.anneeMin && v.annee < c.anneeMin) return false;
    if (c.anneeMax && v.annee > c.anneeMax) return false;

    // Prix
    if (c.prixMin && v.prix < c.prixMin) return false;
    if (c.prixMax && v.prix > c.prixMax) return false;

    // Carburant (Checkboxes)
    if (c.carburant.length > 0 && !c.carburant.includes(v.carburant)) return false; // Supposant que 'carburant' existe

    // Boite (Checkboxes)
    if (c.boite.length > 0 && !c.boite.includes(v.boite)) return false; // Supposant que 'boite' existe

    return true;
  });
  
  // Appliquer le tri selon le type sélectionné
  switch (etatApplication.tri) {
    case 'price-asc': 
      tableauVehicules.sort((a, b) => a.prix - b.prix); 
      break;
    case 'price-desc': 
      tableauVehicules.sort((a, b) => b.prix - a.prix); 
      break;
    case 'year-desc': 
      tableauVehicules.sort((a, b) => b.annee - a.annee); 
      break;
    case 'year-asc': 
      tableauVehicules.sort((a, b) => a.annee - b.annee); 
      break;
    default: 
      // Tri par défaut : plus récents en premier
      tableauVehicules.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  }
  etatApplication.filtres = tableauVehicules;
}

// Fonction asynchrone pour récupérer la liste des véhicules depuis l'API
async function recupererVehicules() {
  const elementListe = selecteur('#vehicle-list');
  const elementVide = selecteur('#empty-state');
  if (!elementListe || !elementVide) return;
  
  try {
    definirChargement(elementListe, true);
    const url = new URL(URL_API, window.location.href);
    
    const reponse = await fetch(url, { 
      headers: { 'Accept': 'application/json' } 
    });
    
    if (!reponse.ok) throw new Error('Erreur serveur');
    
    const donnees = await reponse.json();
    etatApplication.vehicules = Array.isArray(donnees) ? donnees : [];
    
    // Mettre à jour les options de marque dynamiquement si nécessaire
    mettreAJourFiltresMarques();

    appliquerFiltresEtTri();
    afficherListe(elementListe, elementVide);
  } catch (erreur) {
    console.error(erreur);
    const boiteMessage = selecteur('.form__messages');
    afficherMessage(boiteMessage, 'Impossible de charger la liste.', 'err');
  } finally {
    definirChargement(elementListe, false);
  }
}

function mettreAJourFiltresMarques() {
    const selectMarque = selecteur('select[name="marque"]');
    if (!selectMarque) return;

    // Garder la sélection actuelle
    const selectionActuelle = selectMarque.value;

    // Récupérer les marques uniques
    const marques = [...new Set(etatApplication.vehicules.map(v => v.marque))].sort();

    // Reconstruire les options
    selectMarque.innerHTML = '<option value="all">Toutes les marques</option>';
    marques.forEach(m => {
        const option = document.createElement('option');
        option.value = m;
        option.textContent = m;
        selectMarque.appendChild(option);
    });

    // Restaurer la sélection si elle existe toujours
    if (marques.includes(selectionActuelle)) {
        selectMarque.value = selectionActuelle;
    }
}

// Fonction pour lire les valeurs du formulaire de filtres
function lireFiltres() {
    const form = selecteur('#filters-form');
    if (!form) return;

    const formData = new FormData(form);
    
    etatApplication.criteres.type = formData.get('type') || 'all';
    etatApplication.criteres.marque = formData.get('marque') || 'all';
    etatApplication.criteres.anneeMin = formData.get('annee_min') ? Number(formData.get('annee_min')) : null;
    etatApplication.criteres.anneeMax = formData.get('annee_max') ? Number(formData.get('annee_max')) : null;
    etatApplication.criteres.prixMin = formData.get('prix_min') ? Number(formData.get('prix_min')) : null;
    etatApplication.criteres.prixMax = formData.get('prix_max') ? Number(formData.get('prix_max')) : null;
    
    // Checkboxes
    etatApplication.criteres.carburant = formData.getAll('carburant');
    etatApplication.criteres.boite = formData.getAll('boite');

    appliquerFiltresEtTri();
    afficherListe(selecteur('#vehicle-list'), selecteur('#empty-state'));
}

// Fonction asynchrone pour gérer la soumission du formulaire d'ajout de véhicule
async function gererSoumissionFormulaire(evenement) {
  evenement.preventDefault();
  const formulaire = evenement.currentTarget;
  const boiteMessage = selecteur('.form__messages');
  const boutonSoumettre = selecteur('#submit');
  
  // Vider les messages précédents
  if (boiteMessage) boiteMessage.innerHTML = '';

  // Utilisation de FormData pour supporter l'upload de fichiers
  const formData = new FormData(formulaire);
  
  try {
    if (boutonSoumettre) boutonSoumettre.disabled = true;
    
    // Note: Ne pas définir 'Content-Type' manuellement avec FormData, 
    // le navigateur le fera automatiquement avec le boundary correct.
    const reponse = await fetch(URL_API, { 
      method: 'POST', 
      headers: { 
        'Accept': 'application/json' 
      }, 
      body: formData 
    });
    
    const donneesReponse = await reponse.json();
    if (!reponse.ok) throw new Error(donneesReponse?.error || 'Erreur inconnue');
    
    afficherMessage(boiteMessage, 'Véhicule ajouté avec succès.');
    formulaire.reset();
    
    // Réinitialiser la prévisualisation de l'image si elle existe
    const previewContainer = document.getElementById('preview-container');
    const uploadArea = document.querySelector('.upload-area');
    if (previewContainer) previewContainer.hidden = true;
    if (uploadArea) uploadArea.hidden = false;

    // Si on est sur la page galerie, recharger la liste
    if (selecteur('#vehicle-list')) await recupererVehicules();
    
    // Redirection vers la galerie après un court délai
    setTimeout(() => {
        window.location.href = 'galerie';
    }, 1500);

  } catch (erreur) { 
    afficherMessage(boiteMessage, erreur.message, 'err'); 
  } finally { 
    if (boutonSoumettre) boutonSoumettre.disabled = false; 
  }
}

// Fonction asynchrone pour récupérer les informations de l'utilisateur connecté
async function recupererUtilisateur() {
  try {
    const baliseMetaApi = document.querySelector('meta[name="api-base"]');
    const urlAuth = (baliseMetaApi ? baliseMetaApi.getAttribute('content') : './api') + '/auth.php?action=me';
    const reponse = await fetch(urlAuth, { 
      headers: { 'Accept': 'application/json' } 
    });
    const donnees = await reponse.json();
    
    if (reponse.ok && donnees.user) { 
      etatApplication.utilisateur = donnees.user; 
    } else { 
      etatApplication.utilisateur = null; 
    }
  } catch { 
    etatApplication.utilisateur = null; 
  }
}

// Gestion des accordéons de filtres
function initAccordions() {
    const headers = selecteurTous('.filter-header');
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const content = header.nextElementSibling;
            const icon = header.querySelector('.filter-icon');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.style.transform = 'rotate(90deg)';
            } else {
                content.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        });
    });
}

// Fonction pour initialiser l'interface utilisateur et ses gestionnaires d'événements
function initialiserInterface() {
  // Mettre à jour l'année dans le footer
  const elementAnnee = selecteur('#year'); 
  if (elementAnnee) elementAnnee.textContent = String(new Date().getFullYear());
  
  // Gestionnaire pour le formulaire de filtres
  const formFiltres = selecteur('#filters-form');
  if (formFiltres) {
      // Mise à jour en temps réel sur changement
      formFiltres.addEventListener('change', lireFiltres);
      formFiltres.addEventListener('submit', (e) => {
          e.preventDefault();
          lireFiltres();
      });
  }

  // Gestionnaire pour le sélecteur de tri
  const selecteurTri = selecteur('#sort-select'); // ID mis à jour
  if (selecteurTri) {
    selecteurTri.addEventListener('change', () => { 
      etatApplication.tri = selecteurTri.value; 
      appliquerFiltresEtTri(); 
      afficherListe(selecteur('#vehicle-list'), selecteur('#empty-state')); 
    });
  }
  
  // Gestionnaire pour le formulaire d'ajout de véhicule
  const formulaireVehicule = selecteur('#vehicle-form');
  if (formulaireVehicule) {
    formulaireVehicule.addEventListener('submit', gererSoumissionFormulaire);
  }

  initAccordions();
}

// Événement déclenché quand le DOM est entièrement chargé
window.addEventListener('DOMContentLoaded', async () => {
  // Initialiser l'interface utilisateur
  initialiserInterface();
  
  // Récupérer les informations de l'utilisateur connecté
  await recupererUtilisateur();
  
  // Récupérer les favoris (si connecté)
  await recupererFavoris();
  
  // Si l'utilisateur n'est pas connecté, désactiver le formulaire d'ajout
  const formulaireVehicule = selecteur('#vehicle-form');
  if (formulaireVehicule && !etatApplication.utilisateur) {
    [...formulaireVehicule.elements].forEach(element => { 
      if (element.tagName !== 'BUTTON') element.disabled = true; 
    });
    const boiteMessage = selecteur('.form__messages');
    afficherMessage(boiteMessage, 'Connectez-vous pour ajouter votre annonce.', 'err');
  }
  
  // Charger la liste des véhicules si l'élément existe
  if (selecteur('#vehicle-list')) await recupererVehicules();
});

