<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PAGE POLITIQUE DE CONFIDENTIALITÉ
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette page affiche les sections de la politique de confidentialité
 * stockées en base de données.
 * 
 * Fonctionnalités :
 * - Chargement dynamique des sections via JavaScript
 * - Interface d'administration pour les admins (boutons Modifier/Ajouter/Supprimer)
 * - Réordonnancement des sections
 * 
 * @author  mat
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Hero Section -->
<section class="pc-hero">
    <div class="pc-hero__contenu">
        <h1 class="pc-hero__titre">Politique de <span style="color: white;">Confidentialité</span></h1>
        <p class="pc-hero__description">Comment nous protégeons vos données personnelles</p>
    </div>
    <div class="pc-hero__shapes">
        <div class="pc-shape pc-shape--1"></div>
        <div class="pc-shape pc-shape--2"></div>
    </div>
</section>

<!-- Section Politique de Confidentialité -->
<section class="section pc-section-main">
    <div class="conteneur">
        
        <!-- Header avec bouton mode édition (admin) -->
        <div class="pc-admin-header">
            <button id="btn-mode-edition-pc" class="bouton bouton--primaire" style="display: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Mode édition
            </button>
        </div>

        <!-- Section intro (comme FAQ) -->
        <div class="pc-intro">
            <span class="pc-badge">Vos données</span>
            <h2 class="pc-intro__titre">Transparence et protection</h2>
            <p class="pc-intro__text">
                Nous nous engageons à protéger vos informations personnelles. 
                Découvrez ci-dessous comment nous collectons, utilisons et sécurisons vos données.
            </p>
        </div>

        <!-- Chargement -->
        <div id="pc-loading" class="pc-chargement" style="display: flex;">
            <div class="chargement-spinner"></div>
            <p>Chargement de la politique de confidentialité...</p>
        </div>

        <!-- Message d'erreur -->
        <div id="pc-error" class="pc-erreur" style="display: none;">
            <p>Impossible de charger la politique de confidentialité. Veuillez réessayer plus tard.</p>
            <button onclick="window.location.reload()" class="bouton bouton--secondaire">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                </svg>
                Réessayer
            </button>
        </div>

        <!-- Container des sections -->
        <div class="pc-container" id="pc-sections-container" style="display: none;">
            <!-- Les sections seront injectées ici par JavaScript -->
        </div>

        <!-- Section CTA -->
        <div class="pc-cta">
            <div class="pc-cta__content">
                <h3 class="pc-cta__titre">Des questions sur vos données ?</h3>
                <p class="pc-cta__text">Notre équipe est à votre disposition pour répondre à toutes vos questions concernant la protection de vos données personnelles.</p>
                <a href="<?= htmlspecialchars($prefixeUrl) ?>contact" class="btn btn--principal">
                    Nous contacter
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Modale d'édition (admin) -->
<div id="pc-modale-edition" class="pc-modale">
    <div class="pc-modale__overlay"></div>
    <div class="pc-modale__contenu">
        <div class="pc-modale__header">
            <h3 class="pc-modale__titre">Nouvelle section</h3>
            <button class="pc-modale__fermer" type="button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="pc-modale__body">
            <div class="pc-form-groupe">
                <label for="pc-input-titre">Titre de la section</label>
                <input type="text" id="pc-input-titre" class="pc-input" placeholder="Ex: Collecte des données">
            </div>
            <div class="pc-form-groupe">
                <label for="pc-input-contenu">Contenu</label>
                <textarea id="pc-input-contenu" class="pc-textarea" rows="10" placeholder="Saisissez le contenu de la section..."></textarea>
            </div>
        </div>
        <div class="pc-modale__footer">
            <button id="pc-form-annuler" class="bouton bouton--secondaire" type="button">Annuler</button>
            <button id="pc-form-sauvegarder" class="bouton bouton--primaire" type="button">
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
    import VuePolitiqueConfidentialite from './assets/js/modules/statique/VuePolitiqueConfidentialite.js';
    new VuePolitiqueConfidentialite();
</script>
