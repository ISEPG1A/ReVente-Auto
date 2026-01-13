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

/**
 * Affiche une notification toast globale
 * 
 * @param {string} message - Message à afficher
 * @param {string} type - Type : 'success', 'error', 'warning', 'info'
 * @param {number} duree - Durée en ms (5000 par défaut)
 */
export function afficherNotificationGlobale(message, type = 'info', duree = 5000) {
    // Créer le conteneur si n'existe pas
    let conteneur = document.getElementById('notifications-globales');
    if (!conteneur) {
        conteneur = document.createElement('div');
        conteneur.id = 'notifications-globales';
        conteneur.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 100000; display: flex; flex-direction: column; gap: 10px; max-width: 400px;';
        document.body.appendChild(conteneur);
    }
    
    // Couleurs selon le type
    const couleurs = {
        success: { bg: 'rgba(39, 174, 96, 0.95)', border: '#27ae60', icon: 'fa-check-circle' },
        error: { bg: 'rgba(231, 76, 60, 0.95)', border: '#e74c3c', icon: 'fa-times-circle' },
        warning: { bg: 'rgba(243, 156, 18, 0.95)', border: '#f39c12', icon: 'fa-exclamation-triangle' },
        info: { bg: 'rgba(52, 152, 219, 0.95)', border: '#3498db', icon: 'fa-info-circle' }
    };
    
    const config = couleurs[type] || couleurs.info;
    
    // Créer la notification
    const notification = document.createElement('div');
    notification.style.cssText = `
        background: ${config.bg};
        border-left: 4px solid ${config.border};
        color: white;
        padding: 16px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        display: flex;
        align-items: flex-start;
        gap: 12px;
        animation: slideInRight 0.3s ease;
        backdrop-filter: blur(10px);
    `;
    
    notification.innerHTML = `
        <i class="fas ${config.icon}" style="font-size: 1.25rem; margin-top: 2px;"></i>
        <div style="flex: 1;">
            <p style="margin: 0; line-height: 1.5;">${message}</p>
        </div>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; cursor: pointer; padding: 0; font-size: 1.1rem; opacity: 0.8;">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    conteneur.appendChild(notification);
    
    // Auto-suppression
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, duree);
}

/**
 * Affiche une modale d'avertissement (email non vérifié, etc.)
 * 
 * @param {string} titre - Titre de la modale
 * @param {string} message - Message principal
 * @param {string} lienTexte - Texte du bouton d'action (optionnel)
 * @param {string} lienUrl - URL du bouton d'action (optionnel)
 */
export function afficherModaleAvertissement(titre, message, lienTexte = null, lienUrl = null) {
    // Supprimer modale existante
    document.getElementById('modale-avertissement')?.remove();
    
    const modale = document.createElement('div');
    modale.id = 'modale-avertissement';
    modale.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 100000; display: flex; align-items: center; justify-content: center; padding: 1rem;';
    
    modale.innerHTML = `
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);" onclick="this.parentElement.remove()"></div>
        <div style="position: relative; background: var(--surface, #1a1a2e); border-radius: 16px; padding: 2rem; max-width: 420px; width: 100%; box-shadow: 0 25px 50px rgba(0,0,0,0.4); animation: slideInUp 0.3s ease;">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <div style="width: 70px; height: 70px; background: rgba(243, 156, 18, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fas fa-envelope-open-text" style="font-size: 2rem; color: #f39c12;"></i>
                </div>
                <h3 style="margin: 0 0 0.5rem; font-size: 1.4rem; color: var(--texte, #fff);">${titre}</h3>
            </div>
            <p style="margin: 0 0 1.5rem; text-align: center; color: var(--texte-secondaire, #a0a0a0); line-height: 1.6;">${message}</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                ${lienTexte && lienUrl ? `
                    <a href="${lienUrl}" style="background: linear-gradient(135deg, var(--couleur-principale, #fb9e0b), #e67e00); color: #0a0a0a; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-cog"></i> ${lienTexte}
                    </a>
                ` : ''}
                <button onclick="document.getElementById('modale-avertissement').remove()" style="background: var(--surface-2, #252538); color: var(--texte, #fff); padding: 12px 24px; border-radius: 8px; border: 1px solid var(--bordure, #333); cursor: pointer; font-weight: 500;">
                    Fermer
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modale);
}

/**
 * Affiche une modale de confirmation avec OK / Annuler
 * 
 * @param {string} titre - Titre de la modale
 * @param {string} message - Message principal
 * @param {Function} onConfirm - Callback appelé si l'utilisateur confirme
 * @param {string} texteBoutonOk - Texte du bouton OK (par défaut "OK")
 * @param {string} couleurBoutonOk - Couleur du bouton OK (success, warning, error, info)
 */
export function afficherModaleConfirmation(titre, message, onConfirm, texteBoutonOk = 'OK', couleurBoutonOk = 'info') {
    // Supprimer modale existante
    document.getElementById('modale-confirmation')?.remove();
    
    const couleurs = {
        success: 'linear-gradient(135deg, #27ae60, #229954)',
        warning: 'linear-gradient(135deg, #f39c12, #e67e00)',
        error: 'linear-gradient(135deg, #e74c3c, #c0392b)',
        info: 'linear-gradient(135deg, #3498db, #2980b9)'
    };
    
    const icones = {
        success: 'fa-check-circle',
        warning: 'fa-exclamation-triangle',
        error: 'fa-trash-alt',
        info: 'fa-question-circle'
    };
    
    const couleursIcon = {
        success: '#27ae60',
        warning: '#f39c12',
        error: '#e74c3c',
        info: '#3498db'
    };
    
    const modale = document.createElement('div');
    modale.id = 'modale-confirmation';
    modale.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 100000; display: flex; align-items: center; justify-content: center; padding: 1rem;';
    
    modale.innerHTML = `
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);" id="modale-confirmation-backdrop"></div>
        <div style="position: relative; background: var(--surface, #1a1a2e); border-radius: 16px; padding: 2rem; max-width: 420px; width: 100%; box-shadow: 0 25px 50px rgba(0,0,0,0.4); animation: slideInUp 0.3s ease;">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <div style="width: 70px; height: 70px; background: rgba(${couleurBoutonOk === 'success' ? '39, 174, 96' : couleurBoutonOk === 'error' ? '231, 76, 60' : couleurBoutonOk === 'warning' ? '243, 156, 18' : '52, 152, 219'}, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fas ${icones[couleurBoutonOk]}" style="font-size: 2rem; color: ${couleursIcon[couleurBoutonOk]};"></i>
                </div>
                <h3 style="margin: 0 0 0.5rem; font-size: 1.4rem; color: var(--texte, #fff);">${titre}</h3>
            </div>
            <p style="margin: 0 0 1.5rem; text-align: center; color: var(--texte-secondaire, #a0a0a0); line-height: 1.6;">${message}</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button id="modale-confirmation-ok" style="background: ${couleurs[couleurBoutonOk]}; color: white; padding: 12px 32px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.95rem;">
                    ${texteBoutonOk}
                </button>
                <button id="modale-confirmation-annuler" style="background: var(--surface-2, #252538); color: var(--texte, #fff); padding: 12px 24px; border-radius: 8px; border: 1px solid var(--bordure, #333); cursor: pointer; font-weight: 500;">
                    Annuler
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modale);
    
    // Événements
    document.getElementById('modale-confirmation-ok').addEventListener('click', () => {
        modale.remove();
        if (onConfirm) onConfirm();
    });
    
    document.getElementById('modale-confirmation-annuler').addEventListener('click', () => {
        modale.remove();
    });
    
    document.getElementById('modale-confirmation-backdrop').addEventListener('click', () => {
        modale.remove();
    });
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