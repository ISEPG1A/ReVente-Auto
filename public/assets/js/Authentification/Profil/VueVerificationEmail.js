/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE VÉRIFICATION EMAIL - GESTION DE LA PAGE DE VÉRIFICATION
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe gère la page qui s'affiche après qu'un utilisateur clique
 * sur le lien de vérification d'email :
 * - Compte à rebours de redirection automatique
 * - Redirection vers l'accueil après 5 secondes
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

export default class VueVerificationEmail {
    
    /**
     * Initialise la vue de vérification email
     * 
     * @param {string} urlRedirection - URL de redirection après le countdown
     */
    constructor(urlRedirection = 'accueil') {
        this.urlRedirection = urlRedirection;
        this.initialiser();
    }

    /**
     * Démarre le compte à rebours et la redirection automatique
     */
    initialiser() {
        const countdownEl = document.getElementById('countdown');
        
        if (!countdownEl) {
            return;
        }
        
        let countdown = 5;
        
        const interval = setInterval(() => {
            countdown--;
            countdownEl.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(interval);
                const urlBase = document.querySelector('base')?.href || '/';
                window.location.href = urlBase + this.urlRedirection;
            }
        }, 1000);
    }
}
