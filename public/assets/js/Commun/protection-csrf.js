/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PROTECTION CSRF - SÉCURITÉ AUTOMATIQUE DES REQUÊTES AJAX
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce module intercepte TOUTES les requêtes HTTP (fetch et XMLHttpRequest)
 * pour y ajouter automatiquement le jeton CSRF. Cela protège l'application
 * contre les attaques Cross-Site Request Forgery.
 * 
 * IMPORTANT : Ce fichier DOIT être chargé AVANT tous les autres scripts
 * qui effectuent des appels AJAX (POST, PUT, DELETE, PATCH).
 * 
 * Fonctionnement :
 * 1. Récupère le jeton CSRF depuis la balise <meta name="csrf-token">
 * 2. Intercepte les appels fetch() et XMLHttpRequest
 * 3. Ajoute automatiquement le header X-CSRF-Token et/ou le champ csrf_token
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @see     AideCSRF (PHP) Pour la génération de la balise meta
 * ═══════════════════════════════════════════════════════════════════════════
 */

(function() {
    'use strict';
    
    // ═══════════════════════════════════════════════════════════════════════
    // RÉCUPÉRATION DU JETON CSRF
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Récupère le jeton CSRF depuis la balise meta dans le <head>
     * 
     * La balise est générée par AideCSRF::baliseMetaDonnees() en PHP.
     * Format attendu : <meta name="csrf-token" content="xxx">
     * 
     * @returns {string} Jeton CSRF ou chaîne vide si non trouvé
     */
    function obtenirJetonCSRF() {
        const baliseMetaDonnees = document.querySelector('meta[name="csrf-token"]');
        return baliseMetaDonnees ? baliseMetaDonnees.getAttribute('content') : '';
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // INTERCEPTION DE FETCH
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Intercepte toutes les requêtes fetch() pour ajouter le jeton CSRF
     * 
     * Le jeton est ajouté uniquement pour les méthodes modifiantes :
     * POST, PUT, DELETE, PATCH (pas GET qui ne modifie pas de données)
     */
    const fetchOriginal = window.fetch;
    
    window.fetch = function(url, options = {}) {
        // Déterminer la méthode HTTP (GET par défaut)
        const methode = (options.method || 'GET').toUpperCase();
        
        // Ajouter le jeton CSRF uniquement pour les méthodes modifiantes
        const methodesAvecCSRF = ['POST', 'PUT', 'DELETE', 'PATCH'];
        
        if (methodesAvecCSRF.includes(methode)) {
            // Initialiser les en-têtes si non définis
            options.headers = options.headers || {};
            
            // Ajouter le header X-CSRF-Token
            if (options.headers instanceof Headers) {
                options.headers.set('X-CSRF-Token', obtenirJetonCSRF());
            } else {
                options.headers['X-CSRF-Token'] = obtenirJetonCSRF();
            }
            
            // Si le corps est un FormData, ajouter aussi le jeton dedans
            // (pour les formulaires multipart/form-data)
            if (options.body instanceof FormData) {
                options.body.append('csrf_token', obtenirJetonCSRF());
            }
        }
        
        // Appeler fetch() original avec les options modifiées
        return fetchOriginal.call(this, url, options);
    };
    
    // ═══════════════════════════════════════════════════════════════════════
    // INTERCEPTION DE XMLHTTPREQUEST (COMPATIBILITÉ)
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Intercepte XMLHttpRequest pour compatibilité avec le code legacy
     * 
     * Bien que fetch() soit préféré, certaines bibliothèques tierces
     * peuvent encore utiliser XMLHttpRequest.
     */
    const xhrOuvrirOriginal = XMLHttpRequest.prototype.open;
    const xhrEnvoyerOriginal = XMLHttpRequest.prototype.send;
    
    // Stocker la méthode lors de l'ouverture de la connexion
    XMLHttpRequest.prototype.open = function(methode, url, ...reste) {
        this._methode = methode.toUpperCase();
        this._url = url;
        return xhrOuvrirOriginal.call(this, methode, url, ...reste);
    };
    
    // Ajouter le jeton CSRF lors de l'envoi
    XMLHttpRequest.prototype.send = function(corps) {
        const methodesAvecCSRF = ['POST', 'PUT', 'DELETE', 'PATCH'];
        
        if (methodesAvecCSRF.includes(this._methode)) {
            // Ajouter le header X-CSRF-Token
            this.setRequestHeader('X-CSRF-Token', obtenirJetonCSRF());
            
            // Si le corps est un FormData, ajouter aussi le jeton dedans
            if (corps instanceof FormData) {
                corps.append('csrf_token', obtenirJetonCSRF());
            }
        }
        
        return xhrEnvoyerOriginal.call(this, corps);
    };
    
    // Message de confirmation en mode développement (commenté en production)
    // console.info('🔒 Protection CSRF activée pour toutes les requêtes AJAX');
    
})();
