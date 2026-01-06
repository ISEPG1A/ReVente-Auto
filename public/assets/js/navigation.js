/**
 * ═══════════════════════════════════════════════════════════════════════════
 * NAVIGATION - GESTION DU MENU RESPONSIVE ET UTILISATEUR
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce module JavaScript gère l'ensemble de la navigation du site :
 * - Menu hamburger responsive (mobile/desktop)
 * - Marquage automatique du lien actif
 * - Menu déroulant utilisateur avec avatar
 * - Déconnexion de l'utilisateur
 * 
 * Accessibilité :
 * - Utilisation des attributs ARIA (aria-expanded, aria-current)
 * - Support de la navigation au clavier (Échap pour fermer)
 * - Fermeture automatique lors du clic extérieur
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

// ═══════════════════════════════════════════════════════════════════════════
// NAVIGATION RESPONSIVE (MENU HAMBURGER)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Configure la navigation responsive
 * 
 * Gère l'ouverture/fermeture du menu mobile et sa synchronisation
 * avec la taille d'écran via une media query.
 */
function configurerNavigation() {
  const boutonMenu = document.querySelector('.bascule-nav');
  const menu = document.getElementById('menu-site');
  
  if (!boutonMenu || !menu) return;

  // Media query pour détecter le mode desktop (>800px)
  const requeteMedia = window.matchMedia('(min-width: 801px)');
  
  /**
   * Synchronise l'état du menu avec la taille d'écran
   * - Desktop (>800px) : menu toujours visible
   * - Mobile (≤800px) : menu caché par défaut
   */
  const synchroniser = () => {
    if (requeteMedia.matches) {
      // Mode desktop : afficher le menu
      menu.hidden = false;
      boutonMenu.setAttribute('aria-expanded', 'true');
    } else {
      // Mode mobile : cacher le menu
      menu.hidden = true;
      boutonMenu.setAttribute('aria-expanded', 'false');
    }
  };

  // Gestionnaire du clic sur le bouton hamburger
  boutonMenu.addEventListener('click', () => {
    const estOuvert = boutonMenu.getAttribute('aria-expanded') === 'true';
    boutonMenu.setAttribute('aria-expanded', String(!estOuvert));
    menu.hidden = estOuvert;
  });

  // Écoute des changements de taille d'écran
  requeteMedia.addEventListener?.('change', synchroniser);
  
  // Synchronisation de l'état initial
  synchroniser();
}

// ═══════════════════════════════════════════════════════════════════════════
// MARQUAGE DU LIEN ACTIF
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Marque le lien actif dans la navigation
 * 
 * Si un lien est déjà marqué par PHP (aria-current="page"), ne fait rien.
 * Sinon, compare l'URL actuelle avec les liens du menu et marque
 * celui qui correspond.
 */
function marquerLienActif() {
  // Vérification si un lien est déjà marqué par PHP
  const lienDejaActif = document.querySelector('.lien-navigation-site[aria-current="page"]');
  if (lienDejaActif) {
    return; // Un lien est déjà marqué, ne rien faire
  }

  // Récupération du dernier segment de l'URL (ex: "galerie" dans "/test/ReVente-Auto/galerie")
  const pageActuelle = location.pathname.split('/').pop();
  
  // Parcours de tous les liens de navigation
  document.querySelectorAll('.lien-navigation-site').forEach(lien => {
    const hrefLien = lien.getAttribute('href');
    
    // Si le href du lien se termine par la page actuelle, le marquer comme actif
    if (hrefLien && hrefLien.endsWith(pageActuelle)) {
      lien.setAttribute('aria-current', 'page');
    }
  });
}

// ═══════════════════════════════════════════════════════════════════════════
// INITIALISATION AU CHARGEMENT DU DOM
// ═══════════════════════════════════════════════════════════════════════════

window.addEventListener('DOMContentLoaded', () => {
  // Configuration de la navigation responsive
  configurerNavigation();
  marquerLienActif();
  
  // ═════════════════════════════════════════════════════════════════════════
  // MENU UTILISATEUR DÉROULANT
  // ═════════════════════════════════════════════════════════════════════════
  
  const boutonMenuUtilisateur = document.getElementById('bouton-menu-utilisateur');
  const menuUtilisateur = document.getElementById('menu-utilisateur');
  
  if (boutonMenuUtilisateur && menuUtilisateur) {
    /**
     * Ferme le menu utilisateur
     */
    const fermer = () => { 
      boutonMenuUtilisateur.setAttribute('aria-expanded', 'false'); 
      menuUtilisateur.hidden = true; 
    };
    
    /**
     * Ouvre le menu utilisateur
     */
    const ouvrir = () => { 
      boutonMenuUtilisateur.setAttribute('aria-expanded', 'true'); 
      menuUtilisateur.hidden = false; 
    };
    
    // Toggle du menu au clic sur le bouton
    boutonMenuUtilisateur.addEventListener('click', (evenement) => {
      evenement.preventDefault();
      evenement.stopPropagation();
      
      const estOuvert = boutonMenuUtilisateur.getAttribute('aria-expanded') === 'true';
      estOuvert ? fermer() : ouvrir();
    });
    
    // Fermeture du menu lors du clic extérieur
    document.addEventListener('click', (evenement) => {
      if (!menuUtilisateur.hidden && 
          !menuUtilisateur.contains(evenement.target) && 
          evenement.target !== boutonMenuUtilisateur) {
        fermer();
      }
    });
    
    // Fermeture du menu avec la touche Échap
    document.addEventListener('keydown', (evenement) => { 
      if (evenement.key === 'Escape') fermer(); 
    });
  }

  // ═════════════════════════════════════════════════════════════════════════
  // GESTION DE LA DÉCONNEXION
  // ═════════════════════════════════════════════════════════════════════════
  
  const boutonDeconnexion = document.getElementById('bouton-deconnexion');
  if (boutonDeconnexion) {
    boutonDeconnexion.addEventListener('click', async () => {
      try {
        // Récupération de l'URL de base de l'API depuis la balise meta
        const metaApiBase = document.querySelector('meta[name="api-base"]');
        const apiBase = metaApiBase ? metaApiBase.getAttribute('content') : './api';
        
        // Construction de l'URL de déconnexion
        const urlDeconnexion = apiBase.replace(/\/$/, '') + '/connexion?action=logout';
        
        // Envoi de la requête de déconnexion
        const reponse = await fetch(urlDeconnexion, {
          method: 'POST',
          headers: { 'Accept': 'application/json' }
        });
        
        if (reponse.ok) {
            // Redirection vers la page d'accueil
            const urlAccueil = apiBase.replace(/\/api\/?$/, '') + '/accueil';
            window.location.href = urlAccueil;
        } else {
            throw new Error('Erreur lors de la déconnexion');
        }
      } catch (erreur) {
        console.error('Erreur de déconnexion:', erreur);
        alert('Déconnexion impossible. Veuillez réessayer.');
      }
    });
  }
});
