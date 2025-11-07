/**
 * Gestion de la navigation responsive et du menu utilisateur
 * 
 * Ce module JavaScript gère :
 * - Le menu hamburger sur mobile (toggle du menu)
 * - Le marquage du lien actif dans la navigation
 * - Le menu déroulant utilisateur avec avatar
 */

/**
 * Configurer la navigation responsive
 * Gère l'ouverture/fermeture du menu mobile et sa synchronisation avec la taille d'écran
 */
function configurerNavigation() {
  const boutonMenu = document.querySelector('.nav-toggle');
  const menu = document.getElementById('site-menu');
  
  if (!boutonMenu || !menu) return;

  // Media query pour détecter le mode desktop (>800px)
  const requeteMedia = window.matchMedia('(min-width: 801px)');
  
  /**
   * Synchroniser l'état du menu avec la taille d'écran
   * En desktop : menu toujours visible
   * En mobile : menu caché par défaut
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

  // Gérer le clic sur le bouton hamburger
  boutonMenu.addEventListener('click', () => {
    const estOuvert = boutonMenu.getAttribute('aria-expanded') === 'true';
    boutonMenu.setAttribute('aria-expanded', String(!estOuvert));
    menu.hidden = estOuvert;
  });

  // Écouter les changements de taille d'écran
  requeteMedia.addEventListener?.('change', synchroniser);
  
  // Synchroniser l'état initial
  synchroniser();
}

/**
 * Marquer le lien actif dans la navigation
 * Compare l'URL actuelle avec les liens du menu et ajoute aria-current="page"
 */
function marquerLienActif() {
  // Récupérer le dernier segment de l'URL (ex: "galerie" dans "/test/ReVente-Auto/galerie")
  const pageActuelle = location.pathname.split('/').pop();
  
  // Parcourir tous les liens de navigation
  document.querySelectorAll('.site-nav__link').forEach(lien => {
    const hrefLien = lien.getAttribute('href');
    
    // Si le href du lien se termine par la page actuelle, le marquer comme actif
    if (hrefLien && hrefLien.endsWith(pageActuelle)) {
      lien.setAttribute('aria-current', 'page');
    }
  });
}

// ============================================
// Initialisation au chargement du DOM
// ============================================
window.addEventListener('DOMContentLoaded', () => {
  configurerNavigation();
  marquerLienActif();
  
  // ============================================
  // Gestion du menu utilisateur déroulant
  // ============================================
  const boutonMenuUtilisateur = document.getElementById('user-menu-btn');
  const menuUtilisateur = document.getElementById('user-menu');
  
  if (boutonMenuUtilisateur && menuUtilisateur) {
    /**
     * Fermer le menu utilisateur
     */
    const fermer = () => { 
      boutonMenuUtilisateur.setAttribute('aria-expanded', 'false'); 
      menuUtilisateur.hidden = true; 
    };
    
    /**
     * Ouvrir le menu utilisateur
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
    
    // Fermer le menu si on clique ailleurs sur la page
    document.addEventListener('click', (evenement) => {
      if (!menuUtilisateur.hidden && 
          !menuUtilisateur.contains(evenement.target) && 
          evenement.target !== boutonMenuUtilisateur) {
        fermer();
      }
    });
    
    // Fermer le menu avec la touche Échap
    document.addEventListener('keydown', (evenement) => { 
      if (evenement.key === 'Escape') fermer(); 
    });
  }
});
