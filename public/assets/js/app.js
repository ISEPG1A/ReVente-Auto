// Gestion des véhicules (galerie)

// Récupération de l'URL de base de l'API depuis les métadonnées
const metaApiBase = document.querySelector('meta[name="api-base"]');
const URL_API = (metaApiBase ? metaApiBase.getAttribute('content') : './api') + '/api.php';

// Fonction utilitaire pour sélectionner un élément DOM (querySelector simplifié)
const selecteur = (s, el = document) => el.querySelector(s);

// Fonction pour formater un nombre en monnaie française (euros)
const formaterMonnaie = (n) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(n);

// Fonction de débounce pour limiter la fréquence d'exécution d'une fonction
const debouncer = (fn, delai = 250) => {
  let minuteur;
  return (...args) => {
    clearTimeout(minuteur);
    minuteur = setTimeout(() => fn(...args), delai);
  };
};

// État global de l'application contenant les données des véhicules
const etatApplication = { 
  vehicules: [],           // Liste complète des véhicules
  filtres: [],            // Liste filtrée des véhicules
  requete: '',            // Terme de recherche
  tri: 'recent',          // Type de tri appliqué
  utilisateur: null       // Utilisateur connecté
};

// Fonction pour définir l'état de chargement d'un élément
function definirChargement(element, enChargement) {
  element && element.setAttribute('aria-busy', String(enChargement));
}

// Fonction pour afficher un message dans un conteneur
function afficherMessage(conteneur, texte, type = 'ok') {
  if (conteneur) conteneur.innerHTML = `<div class="msg msg--${type === 'ok' ? 'ok' : 'err'}">${texte}</div>`;
}

// Fonction pour échapper les caractères HTML et éviter les injections XSS
function echapperHTML(s) {
  return String(s).replace(/[&<>"]+/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
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
    const elementListe = document.createElement('li');
    elementListe.className = 'card';
    
    // Vérifier si l'utilisateur est propriétaire du véhicule ou administrateur
    const estProprietaire = etatApplication.utilisateur && vehicule.seller_id && Number(vehicule.seller_id) === Number(etatApplication.utilisateur.id);
    const estAdmin = etatApplication.utilisateur && etatApplication.utilisateur.role === 'admin';
    
    // Construire la ligne d'information sur le vendeur
    const ligneVendeur = (vehicule.seller_first_name || vehicule.seller_last_name || vehicule.seller_email || vehicule.seller_phone)
      ? `<p class="card__meta">Vendeur: ${echapperHTML((vehicule.seller_first_name||'') + ' ' + (vehicule.seller_last_name||''))} • ${echapperHTML(vehicule.seller_email||'')} ${vehicule.seller_phone? '• '+echapperHTML(vehicule.seller_phone): ''}</p>`
      : '';
      
    // Construire le HTML de la carte
    elementListe.innerHTML = `
      <span class="card__badge">${vehicule.annee}</span>
      <div>
        <h3 class="card__title">${echapperHTML(vehicule.marque)} ${echapperHTML(vehicule.modele)}</h3>
        <p class="card__meta">#${vehicule.id} • Ajouté le ${new Date(vehicule.created_at).toLocaleDateString('fr-FR')}</p>
        ${ligneVendeur}
      </div>
      <div class="card__price">${formaterMonnaie(vehicule.prix)}</div>
    `;
    
    // Ajouter le bouton de suppression si l'utilisateur est autorisé
    if (estProprietaire || estAdmin) {
      const boutonSupprimer = document.createElement('button');
      boutonSupprimer.textContent = 'Supprimer';
      boutonSupprimer.className = 'button button--danger';
      boutonSupprimer.style.marginLeft = '8px';
      
      // Gestionnaire d'événement pour la suppression
      boutonSupprimer.addEventListener('click', async () => {
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
      elementListe.appendChild(boutonSupprimer);
    }
    fragment.appendChild(elementListe);
  }
  elementListe.appendChild(fragment);
}

// Fonction pour appliquer les filtres et le tri sur la liste des véhicules
function appliquerFiltresEtTri() {
  const requeteRecherche = etatApplication.requete.trim().toLowerCase();
  let tableauVehicules = [...etatApplication.vehicules];
  
  // Filtrer par terme de recherche si présent
  if (requeteRecherche) {
    tableauVehicules = tableauVehicules.filter(vehicule => 
      `${vehicule.marque} ${vehicule.modele}`.toLowerCase().includes(requeteRecherche)
    );
  }
  
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
    
    // Ajouter le paramètre de recherche si présent
    if (etatApplication.requete) {
      url.searchParams.set('q', etatApplication.requete);
    }
    
    const reponse = await fetch(url, { 
      headers: { 'Accept': 'application/json' } 
    });
    
    if (!reponse.ok) throw new Error('Erreur serveur');
    
    const donnees = await reponse.json();
    etatApplication.vehicules = Array.isArray(donnees) ? donnees : [];
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

// Fonction pour valider les données du formulaire d'ajout de véhicule
function validerFormulaire(formulaire) {
  const marque = formulaire.marque.value.trim();
  const modele = formulaire.modele.value.trim();
  const annee = Number(formulaire.annee.value);
  const prix = Number(formulaire.prix.value);
  const anneeActuelle = new Date().getFullYear() + 1;
  const erreurs = [];
  
  // Validation de la marque
  if (!marque || marque.length > 50) {
    erreurs.push('Marque invalide.');
  }
  
  // Validation du modèle
  if (!modele || modele.length > 50) {
    erreurs.push('Modèle invalide.');
  }
  
  // Validation de l'année
  if (!Number.isInteger(annee) || annee < 1900 || annee > anneeActuelle) {
    erreurs.push('Année invalide.');
  }
  
  // Validation du prix
  if (!Number.isFinite(prix) || prix < 0) {
    erreurs.push('Prix invalide.');
  }
  
  return { 
    valide: erreurs.length === 0, 
    erreurs, 
    donnees: { marque, modele, annee, prix } 
  };
}

// Fonction asynchrone pour gérer la soumission du formulaire d'ajout de véhicule
async function gererSoumissionFormulaire(evenement) {
  evenement.preventDefault();
  const formulaire = evenement.currentTarget;
  const boiteMessage = selecteur('.form__messages');
  const boutonSoumettre = selecteur('#submit');
  
  // Vider les messages précédents
  if (boiteMessage) boiteMessage.innerHTML = '';

  // Valider les données du formulaire
  const { valide, erreurs, donnees } = validerFormulaire(formulaire);
  if (!valide) { 
    afficherMessage(boiteMessage, erreurs.join(' '), 'err'); 
    return; 
  }

  try {
    // Désactiver le bouton pendant la soumission
    if (boutonSoumettre) boutonSoumettre.disabled = true;
    
    // Envoyer les données à l'API
    const reponse = await fetch(URL_API, { 
      method: 'POST', 
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json' 
      }, 
      body: JSON.stringify(donnees) 
    });
    
    const donneesReponse = await reponse.json();
    if (!reponse.ok) throw new Error(donneesReponse?.error || 'Erreur inconnue');
    
    // Afficher le succès et réinitialiser le formulaire
    afficherMessage(boiteMessage, 'Véhicule ajouté avec succès.');
    
    if (typeof formulaire.reset === 'function') { 
      formulaire.reset(); 
    } else { 
      HTMLFormElement.prototype.reset.call(formulaire); 
    }
    
    // Recharger la liste des véhicules
    await recupererVehicules();
  } catch (erreur) { 
    afficherMessage(boiteMessage, erreur.message, 'err'); 
  } finally { 
    // Réactiver le bouton
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

// Fonction pour initialiser l'interface utilisateur et ses gestionnaires d'événements
function initialiserInterface() {
  // Mettre à jour l'année dans le footer
  const elementAnnee = selecteur('#year'); 
  if (elementAnnee) elementAnnee.textContent = String(new Date().getFullYear());
  
  // Gestionnaire pour la barre de recherche avec debounce
  const champRecherche = selecteur('#search');
  if (champRecherche) {
    champRecherche.addEventListener('input', debouncer(() => { 
      etatApplication.requete = champRecherche.value; 
      appliquerFiltresEtTri(); 
      afficherListe(selecteur('#vehicle-list'), selecteur('#empty-state')); 
      recupererVehicules(); 
    }, 300));
  }
  
  // Gestionnaire pour le sélecteur de tri
  const selecteurTri = selecteur('#sort');
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
}

// Événement déclenché quand le DOM est entièrement chargé
window.addEventListener('DOMContentLoaded', async () => {
  // Initialiser l'interface utilisateur
  initialiserInterface();
  
  // Récupérer les informations de l'utilisateur connecté
  await recupererUtilisateur();
  
  // Si l'utilisateur n'est pas connecté, désactiver le formulaire d'ajout
  const formulaireVehicule = selecteur('#vehicle-form');
  if (formulaireVehicule && !etatApplication.utilisateur) {
    // Désactiver tous les champs du formulaire sauf les boutons
    [...formulaireVehicule.elements].forEach(element => { 
      if (element.tagName !== 'BUTTON') element.disabled = true; 
    });
    
    // Afficher un message d'information
    const boiteMessage = selecteur('.form__messages');
    afficherMessage(boiteMessage, 'Connectez-vous pour ajouter votre annonce.', 'err');
  }
  
  // Charger la liste des véhicules si l'élément existe
  if (selecteur('#vehicle-list')) await recupererVehicules();
});
