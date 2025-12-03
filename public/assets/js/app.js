// Gestion des véhicules (galerie)

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

/**
 * Construit l'URL de l'API de manière robuste
 * @param {string} chemin - Le chemin relatif vers le endpoint (ex: '/messagerie')
 * @returns {string} L'URL complète
 */
export function obtenirUrlApi(chemin) {
    const metaApiBase = document.querySelector('meta[name="api-base"]');
    const apiBase = metaApiBase ? metaApiBase.getAttribute('content') : './api';
    
    // Nettoyer le chemin d'entrée pour éviter les doubles slashs
    const cheminNettoye = chemin.startsWith('/') ? chemin : '/' + chemin;
    
    return apiBase.replace(/\/$/, '') + cheminNettoye;
}

// Fonction pour mettre à jour le badge de messages non lus
async function mettreAJourBadgeMessages() {
    // Vérifier si l'utilisateur est connecté via une vérification rapide ou un état global si disponible
    // Ici on tente simplement la requête, si 401/403 ça échouera silencieusement
    
    try {
        const url = obtenirUrlApi('/messagerie?action=compter_non_lus');
        
        const res = await fetch(url);
        if (!res.ok) return;
        
        const donnees = await res.json();
        
        const badge = document.getElementById('badge-msg-nav');
        if (badge) {
            if (donnees.compte > 0) {
                badge.textContent = donnees.compte;
                badge.hidden = false;
            } else {
                badge.hidden = true;
            }
        }
    } catch (e) {
        // Ignorer les erreurs de réseau ou d'auth pour le badge
    }
}

// Événement déclenché quand le DOM est entièrement chargé
window.addEventListener('DOMContentLoaded', async () => {
  // Mettre à jour l'année dans le footer
  const elementAnnee = selecteur('#annee-pied-page'); 
  if (elementAnnee) elementAnnee.textContent = String(new Date().getFullYear());

  // Lancer le polling du badge
  mettreAJourBadgeMessages();
  setInterval(mettreAJourBadgeMessages, 10000); // Vérifier toutes les 10s
});

