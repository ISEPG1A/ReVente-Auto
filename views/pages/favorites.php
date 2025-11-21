<!-- 
Page des favoris - Liste des véhicules enregistrés par l'utilisateur
-->

<section id="favoris" class="section section--liste">
  <div class="container">
    <div class="section-header" style="margin-bottom: 30px;">
      <h2 class="section-title" style="color: var(--couleur-principale);">Mes Favoris</h2>
      <p class="section-subtitle" style="color: var(--texte-attenue);">Retrouvez ici tous les véhicules que vous avez sauvegardés.</p>
    </div>

    <!-- Liste des favoris (réutilise le style des cartes horizontales) -->
    <ul id="favorites-list" class="cards-horizontal" aria-live="polite" aria-busy="false">
      <!-- Les cartes seront injectées ici par JS -->
    </ul>
    
    <!-- Message vide -->
    <div id="empty-favorites" class="vide" hidden style="text-align: center; padding: 40px; background: var(--surface-2); border-radius: var(--rayon-bordure);">
      <i class="far fa-heart fa-3x" style="color: var(--texte-attenue); margin-bottom: 20px;"></i>
      <p>Vous n'avez aucun véhicule en favoris pour le moment.</p>
      <a href="galerie" class="bouton" style="margin-top: 20px; display: inline-block;">Parcourir la galerie</a>
    </div>
  </div>
</section>

<script type="module">
  // Script spécifique pour la page favoris
  import { afficherMessage, formaterMonnaie, echapperHTML } from './assets/js/app.js';

  const metaApiBase = document.querySelector('meta[name="api-base"]');
  const baseUrl = metaApiBase ? metaApiBase.getAttribute('content') : './api';
  const URL_API_FAV = baseUrl + '/favorites.php';
  
  const listEl = document.getElementById('favorites-list');
  const emptyEl = document.getElementById('empty-favorites');

  async function chargerFavoris() {
    try {
      listEl.setAttribute('aria-busy', 'true');
      const res = await fetch(URL_API_FAV);
      
      if (res.status === 401) {
        window.location.href = 'connexion';
        return;
      }
      
      const data = await res.json();
      
      if (data.error) throw new Error(data.error);
      
      afficherListeFavoris(data);
    } catch (err) {
      console.error(err);
      listEl.innerHTML = `<div class="msg msg--err">Erreur: ${err.message}</div>`;
    } finally {
      listEl.setAttribute('aria-busy', 'false');
    }
  }

  function afficherListeFavoris(vehicules) {
    listEl.innerHTML = '';
    
    if (!vehicules || vehicules.length === 0) {
      emptyEl.hidden = false;
      return;
    }
    
    emptyEl.hidden = true;
    
    vehicules.forEach(v => {
      const li = document.createElement('li');
      li.className = 'vehicle-card-horizontal';
      
      // Préparation de l'image
      let imageHtml;
      
      if (v.image_path) {
          // Gestion du chemin de l'image
          const path = v.image_path.startsWith('uploads/') ? v.image_path : `uploads/${v.image_path}`;
          // Ajout du style object-fit: cover pour que l'image remplisse bien le conteneur sans être déformée
          imageHtml = `<img src="${path}" alt="${echapperHTML(v.marque)} ${echapperHTML(v.modele)}" loading="lazy" style="width:100%; height:100%; object-fit:cover;">`;
      } else {
          // Image par défaut (icône) si aucune image n'est fournie
          imageHtml = `
            <div style="width:100%; height:100%; background: #252a35; display:flex; align-items:center; justify-content:center; color:#4a505c;">
                <i class="fas fa-car fa-3x"></i>
            </div>`;
      }
      const km = v.km || 0;
      const carburant = v.carburant || 'N/A';
      const boite = v.boite || 'N/A';
      const ville = v.ville || 'Non spécifié';

      // Vérifier si l'utilisateur est propriétaire
      // Note: Dans favorites.php, on n'a pas directement l'info de l'utilisateur connecté dans le JS
      // On va le récupérer via l'API auth
      const idVendeur = v.user_id || v.seller_id;
      
      li.innerHTML = `
        <div class="card-image-container">
          ${imageHtml}
        </div>
        <div class="card-details">
          <div class="card-header-row">
              <h3 class="card-title-h">${echapperHTML(v.marque)} ${echapperHTML(v.modele)}</h3>
              <div class="card-actions-h">
                  <button class="btn-heart active" data-id="${v.id}" title="Retirer des favoris" style="color: var(--couleur-danger); border-color: var(--couleur-danger); background: rgba(255, 107, 107, 0.1);">
                    <i class="fas fa-heart"></i>
                  </button>
              </div>
          </div>
          <div class="card-specs-row">
              <span class="spec-item"><i class="fas fa-calendar-alt"></i> ${v.annee}</span>
              <span class="spec-item"><i class="fas fa-tachometer-alt"></i> ${Number(km).toLocaleString()} km</span>
              <span class="spec-item"><i class="fas fa-gas-pump"></i> ${echapperHTML(carburant)}</span>
              <span class="spec-item"><i class="fas fa-cog"></i> ${echapperHTML(boite)}</span>
          </div>
          <div class="card-footer-row" style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
              <div style="display:flex; align-items:center; gap:10px;">
                  <span class="spec-item" title="Localisation" style="font-size:0.9rem; color:var(--texte-attenue);"><i class="fas fa-map-marker-alt"></i> ${echapperHTML(ville)}</span>
                  <button class="button button--danger btn-delete-fav" data-id="${v.id}" style="padding: 4px 8px; font-size:0.8rem; display:none;" title="Supprimer l'annonce"><i class="fas fa-trash"></i></button>
              </div>
              <div class="card-price-h" style="margin:0;">${formaterMonnaie(v.prix)}</div>
          </div>
        </div>
      `;
      
      // Gestionnaire pour le bouton supprimer (sera affiché si propriétaire)
      const btnDelete = li.querySelector('.btn-delete-fav');
      // On stocke l'ID vendeur pour le vérifier plus tard
      btnDelete.dataset.sellerId = idVendeur;

      // ... (reste du code existant pour le coeur) ...
      
      // Gestion du clic sur le coeur pour retirer
      const btnHeart = li.querySelector('.btn-heart');
      btnHeart.addEventListener('click', async (e) => {
        e.stopPropagation();
        if(!confirm('Retirer ce véhicule des favoris ?')) return;
        
        try {
          const res = await fetch(`${URL_API_FAV}?id=${v.id}`, { method: 'DELETE' });
          if(res.ok) {
            li.remove();
            if(listEl.children.length === 0) emptyEl.hidden = false;
          }
        } catch(err) {
          console.error(err);
        }
      });

      // Gestion suppression annonce
      btnDelete.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (!confirm('Supprimer cette annonce définitivement ?')) return;
            try {
                const url = baseUrl + '/api.php?id=' + v.id;
                const reponse = await fetch(url, { 
                    method: 'DELETE', 
                    headers: { 'Accept': 'application/json' } 
                });
                if (!reponse.ok) throw new Error('Suppression impossible');
                li.remove();
                if(listEl.children.length === 0) emptyEl.hidden = false;
            } catch (erreur) {
                alert(erreur.message || 'Erreur');
            }
      });

      // Clic sur la carte pour aller aux détails
      li.addEventListener('click', (e) => {
        if (e.target.closest('button')) return;
        window.location.href = `vehicule?id=${v.id}`;
      });

      listEl.appendChild(li);
    });
    
    // Vérifier les droits pour afficher les boutons supprimer
    verifierDroitsSuppression();
  }

  async function verifierDroitsSuppression() {
      try {
        const res = await fetch(baseUrl + '/auth.php?action=me');
        const data = await res.json();
        if (data.user) {
            const userId = Number(data.user.id);
            const isAdmin = data.user.role === 'admin';
            
            document.querySelectorAll('.btn-delete-fav').forEach(btn => {
                const sellerId = Number(btn.dataset.sellerId);
                if (isAdmin || (sellerId && sellerId === userId)) {
                    btn.style.display = 'inline-block';
                }
            });
        }
      } catch (e) { console.error(e); }
  }

  document.addEventListener('DOMContentLoaded', chargerFavoris);
</script>
