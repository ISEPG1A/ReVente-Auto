<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PAGE CGU (CONDITIONS GÉNÉRALES D'UTILISATION)
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette page affiche les articles des CGU stockés en base de données.
 * 
 * Fonctionnalités :
 * - Affichage en accordéon (comme la FAQ) : seuls les titres sont visibles
 * - Chargement dynamique des articles via JavaScript
 * - Interface d'administration pour les admins (boutons Modifier/Ajouter/Supprimer)
 * - Réordonnancement des articles, sections et points
 * 
 * @author  mat
 * @version 2.0
 * ═══════════════════════════════════════════════════════════════════════════
 */
?>

<!-- Section Hero -->
<section class="cgu-hero">
    <div class="cgu-hero__contenu">
        <h1 class="cgu-hero__titre">Conditions Générales <span>d'Utilisation</span></h1>
        <p class="cgu-hero__description">Les règles qui encadrent l'utilisation de notre plateforme</p>
    </div>
    <div class="cgu-hero__shapes">
        <div class="cgu-shape cgu-shape--1"></div>
        <div class="cgu-shape cgu-shape--2"></div>
    </div>
</section>

<!-- Section principale de la page CGU -->
<section class="section cgu-section-main">
    <div class="conteneur">
        
        <!-- Bouton mode édition (visible uniquement pour les administrateurs) -->
        <div class="cgu-admin-header">
            <button id="btn-mode-edition" class="bouton bouton--primaire" style="display: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Mode édition
            </button>
        </div>

        <!-- Section intro -->
        <div class="cgu-intro">
            <span class="cgu-badge">Cadre légal</span>
            <h2 class="cgu-intro__titre">Nos engagements mutuels</h2>
            <p class="cgu-intro__text">
                En utilisant ReVente-Auto, vous acceptez les conditions ci-dessous. 
                Ces règles garantissent une expérience sécurisée pour tous les utilisateurs.
            </p>
        </div>

        <!-- Conteneur des articles CGU en accordéon -->
        <div class="cgu-container" id="cgu-articles-container">
            <!-- Les articles seront injectés ici par JavaScript -->
        </div>

        <!-- Message de chargement -->
        <div id="cgu-loading" class="cgu-loading">
            <div class="chargement-spinner"></div>
            <p>Chargement des articles...</p>
        </div>

        <!-- Message d'erreur -->
        <div id="cgu-error" class="cgu-erreur" style="display: none;">
            <p>Impossible de charger les articles CGU. Veuillez réessayer plus tard.</p>
            <button onclick="window.location.reload()" class="bouton bouton--secondaire">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                </svg>
                Réessayer
            </button>
        </div>

        <!-- Section Versions archivées -->
        <div class="cgu-versions-section">
            <h3 class="cgu-versions-titre">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                Versions archivées
            </h3>
            <p class="cgu-versions-description">
                Consultez les versions précédentes de nos Conditions Générales d'Utilisation.
            </p>
            <div id="cgu-versions-liste" class="cgu-versions-liste">
                <!-- Les versions seront injectées ici par JavaScript -->
                <p class="cgu-versions-loading">Chargement des versions...</p>
            </div>
        </div>

    </div>
</section>

<!-- Modale pour l'édition/ajout (pour les admins) -->
<div id="modal-edition-cgu" class="modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-contenu">
        <div class="modal-header">
            <h2 id="modal-titre">Modifier</h2>
            <button class="modal-close" aria-label="Fermer">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body" id="modal-body">
            <!-- Formulaire injecté dynamiquement -->
        </div>
        <div class="modal-footer">
            <button type="button" class="bouton bouton--secondaire modal-close">Annuler</button>
            <button type="button" id="btn-sauvegarder-modale" class="bouton bouton--primaire">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                Enregistrer
            </button>
        </div>
    </div>
</div>

<script type="module">
    import VueCGU from './assets/js/modules/statique/VueCGU.js?v=<?= time() ?>';
    new VueCGU();
</script>
