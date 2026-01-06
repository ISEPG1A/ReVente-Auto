/**
 * ═══════════════════════════════════════════════════════════════════════════
 * APPLICATION.JS - MODULE PRINCIPAL DE L'APPLICATION REVENTE-AUTO
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce module centralise les fonctions utilitaires partagées dans toute 
 * l'application JavaScript. Il est importé par tous les autres modules
 * pour garantir la cohérence et éviter la duplication de code.
 * 
 * Exports disponibles :
 * - selecteur() / selecteurTous() : Raccourcis pour querySelector/All
 * - formaterMonnaie() : Formatage en euros français
 * - debouncer() : Limitation de fréquence d'exécution
 * - definirChargement() : Gestion de l'état aria-busy
 * - afficherMessage() : Affichage de messages utilisateur
 * - echapperHTML() : Protection contre les injections XSS
 * - obtenirUrlApi() : Construction d'URLs API sécurisées
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @since   2024
 * ═══════════════════════════════════════════════════════════════════════════
 */

// ═══════════════════════════════════════════════════════════════════════════
// SÉLECTEURS DOM SIMPLIFIÉS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Sélectionne un élément DOM unique (raccourci pour querySelector)
 * 
 * @param {string} selecteurCSS - Sélecteur CSS à rechercher
 * @param {Element} element - Élément parent (document par défaut)
 * @returns {Element|null} Élément trouvé ou null
 * @example const bouton = selecteur('.btn-principal');
 */
export const selecteur = (selecteurCSS, element = document) => element.querySelector(selecteurCSS);

/**
 * Sélectionne tous les éléments DOM correspondants (raccourci pour querySelectorAll)
 * 
 * @param {string} selecteurCSS - Sélecteur CSS à rechercher
 * @param {Element} element - Élément parent (document par défaut)
 * @returns {NodeList} Liste des éléments trouvés
 * @example const cartes = selecteurTous('.carte-vehicule');
 */
export const selecteurTous = (selecteurCSS, element = document) => element.querySelectorAll(selecteurCSS);

// ═══════════════════════════════════════════════════════════════════════════
// FORMATAGE ET AFFICHAGE
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Formate un nombre en devise française (euros)
 * 
 * Utilise l'API Intl.NumberFormat pour un formatage localisé.
 * 
 * @param {number} nombre - Montant à formater
 * @returns {string} Montant formaté (ex: "12 500,00 €")
 * @example formaterMonnaie(12500) // "12 500,00 €"
 */
export const formaterMonnaie = (nombre) => new Intl.NumberFormat('fr-FR', { 
    style: 'currency', 
    currency: 'EUR' 
}).format(nombre);

/**
 * Crée une fonction "debounced" qui limite la fréquence d'exécution
 * 
 * Utile pour les événements fréquents (scroll, resize, input).
 * La fonction ne s'exécute qu'après un délai sans nouvel appel.
 * 
 * @param {Function} fonction - Fonction à exécuter
 * @param {number} delai - Délai en millisecondes (250ms par défaut)
 * @returns {Function} Fonction avec délai
 * @example const rechercherDebounce = debouncer(rechercher, 300);
 */
export const debouncer = (fonction, delai = 250) => {
    let minuteur;
    return (...arguments_) => {
        clearTimeout(minuteur);
        minuteur = setTimeout(() => fonction(...arguments_), delai);
    };
};

/**
 * Définit l'état de chargement d'un élément (attribut aria-busy)
 * 
 * @param {Element} element - Élément à modifier
 * @param {boolean} enChargement - True si en cours de chargement
 */
export function definirChargement(element, enChargement) {
    element && element.setAttribute('aria-busy', String(enChargement));
}

/**
 * Affiche un message dans un conteneur avec style OK/Erreur
 * 
 * @param {Element} conteneur - Conteneur où afficher le message
 * @param {string} texte - Texte du message
 * @param {string} type - Type de message : 'ok' ou 'erreur'
 */
export function afficherMessage(conteneur, texte, type = 'ok') {
    if (conteneur) {
        const classeCSS = type === 'ok' ? 'msg--ok' : 'msg--err';
        conteneur.innerHTML = `<div class="msg ${classeCSS}">${texte}</div>`;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SÉCURITÉ
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Échappe les caractères HTML pour prévenir les injections XSS
 * 
 * IMPORTANT : Toujours utiliser cette fonction avant d'insérer
 * du contenu utilisateur dans le DOM via innerHTML.
 * 
 * @param {string} chaine - Chaîne à sécuriser
 * @returns {string} Chaîne avec caractères HTML échappés
 * @example element.innerHTML = echapperHTML(contenuUtilisateur);
 */
export function echapperHTML(chaine) {
    return String(chaine).replace(/[&<>"]+/g, (caractere) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;'
    }[caractere]));
}

// ═══════════════════════════════════════════════════════════════════════════
// COMMUNICATION API
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Construit l'URL complète de l'API à partir d'un chemin relatif
 * 
 * Récupère la base de l'API depuis la balise meta 'api-base'
 * définie dans le layout principal.
 * 
 * @param {string} chemin - Chemin relatif vers le endpoint (ex: '/messagerie')
 * @returns {string} URL complète de l'API
 * @example const url = obtenirUrlApi('/vehicule/details'); // "./api/vehicule/details"
 */
export function obtenirUrlApi(chemin) {
    // Récupérer la base de l'API depuis la meta tag
    const metaApiBase = document.querySelector('meta[name="api-base"]');
    const baseApi = metaApiBase ? metaApiBase.getAttribute('content') : './api';
    
    // Nettoyer le chemin d'entrée pour éviter les doubles slashs
    const cheminNettoye = chemin.startsWith('/') ? chemin : '/' + chemin;
    
    // Construire et retourner l'URL complète
    return baseApi.replace(/\/$/, '') + cheminNettoye;
}

// ═══════════════════════════════════════════════════════════════════════════
// FONCTIONNALITÉS GLOBALES
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Met à jour le badge de messages non lus dans la navigation
 * 
 * Effectue une requête silencieuse vers l'API pour compter
 * les messages non lus. Ignore les erreurs (utilisateur non connecté).
 * 
 * @private
 * @async
 */
async function mettreAJourBadgeMessages() {
    try {
        const url = obtenirUrlApi('/messagerie?action=compter_non_lus');
        const reponse = await fetch(url);
        
        // Si erreur (non connecté, etc.), on ignore silencieusement
        if (!reponse.ok) return;
        
        const donnees = await reponse.json();
        const badge = document.getElementById('badge-msg-nav');
        
        if (badge) {
            if (donnees.compte > 0) {
                badge.textContent = donnees.compte;
                badge.hidden = false;
            } else {
                badge.hidden = true;
            }
        }
    } catch (erreur) {
        // Ignorer les erreurs de réseau ou d'authentification
        // C'est un comportement attendu si l'utilisateur n'est pas connecté
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// INITIALISATION AU CHARGEMENT DU DOM
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Initialise les fonctionnalités globales de l'application
 * - Mise à jour de l'année dans le pied de page
 * - Polling du badge de messages non lus (toutes les 10 secondes)
 */
window.addEventListener('DOMContentLoaded', async () => {
    // Mettre à jour automatiquement l'année dans le pied de page
    const elementAnnee = selecteur('#annee-pied-page');
    if (elementAnnee) {
        elementAnnee.textContent = String(new Date().getFullYear());
    }

    // Initialiser et maintenir le badge de messages non lus
    mettreAJourBadgeMessages();
    
    // Vérifier les nouveaux messages toutes les 10 secondes
    const INTERVALLE_VERIFICATION_MESSAGES = 10000; // 10 secondes
    setInterval(mettreAJourBadgeMessages, INTERVALLE_VERIFICATION_MESSAGES);
});