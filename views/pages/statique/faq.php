<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PAGE FAQ (Questions Fréquentes)
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette page affiche les questions FAQ stockées en base de données.
 * 
 * Fonctionnalités :
 * - Affichage en accordéon
 * - Chargement dynamique des questions via JavaScript
 * - Interface d'administration pour les admins (boutons Modifier/Ajouter/Supprimer)
 * - Réordonnancement des questions
 * 
 * @author  mat
 * @version 2.0
 * ═══════════════════════════════════════════════════════════════════════════
 */
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Hero Section FAQ -->
<section class="faq-hero">
    <div class="faq-hero__contenu">
        <h1 class="faq-hero__titre">Questions <span style="color: white;">Fréquentes</span></h1>
        <p class="faq-hero__description">Trouvez rapidement les réponses à toutes vos questions sur ReVente Auto</p>
    </div>
    <div class="faq-hero__shapes">
        <div class="faq-shape faq-shape--1"></div>
        <div class="faq-shape faq-shape--2"></div>
    </div>
</section>

<!-- Section FAQ -->
<section class="section faq-section">
    <div class="conteneur">
        
        <!-- Header avec bouton mode édition (admin) -->
        <div class="faq-admin-header">
            <button id="btn-mode-edition-faq" class="bouton bouton--primaire" style="display: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Mode édition
            </button>
        </div>
        
        <div class="faq-intro">
            <span class="faq-badge">Notre service</span>
            <h2 class="faq-intro__titre">Tout ce que vous devez savoir</h2>
            <p class="faq-intro__text">
                Vous trouverez ci-dessous les réponses aux questions les plus fréquemment posées. 
                Si vous ne trouvez pas ce que vous cherchez, n'hésitez pas à <a href="<?= htmlspecialchars($prefixeUrl) ?>contact" class="faq-link">nous contacter</a>.
            </p>
        </div>

        <!-- Chargement -->
        <div id="faq-loading" class="faq-chargement" style="display: flex;">
            <div class="chargement-spinner"></div>
            <p>Chargement des questions...</p>
        </div>

        <!-- Message d'erreur -->
        <div id="faq-error" class="faq-erreur" style="display: none;">
            <p>Impossible de charger les questions FAQ. Veuillez réessayer plus tard.</p>
            <button onclick="window.location.reload()" class="bouton bouton--secondaire">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                </svg>
                Réessayer
            </button>
        </div>

        <!-- Container des questions FAQ -->
        <div class="faq-container" id="faq-questions-container" style="display: none;">
            <!-- Les questions seront injectées ici par JavaScript -->
        </div>

        <!-- Section CTA -->
        <div class="faq-cta">
            <div class="faq-cta__content">
                <h3 class="faq-cta__titre">Vous ne trouvez pas votre réponse ?</h3>
                <p class="faq-cta__text">Notre équipe est là pour vous aider. Contactez-nous et nous vous répondrons dans les plus brefs délais.</p>
                <a href="<?= htmlspecialchars($prefixeUrl) ?>contact" class="btn btn--principal">
                    Nous contacter
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Modale d'édition (admin) -->
<div id="faq-modale-edition" class="faq-modale">
    <div class="faq-modale__overlay"></div>
    <div class="faq-modale__contenu">
        <div class="faq-modale__header">
            <h3 class="faq-modale__titre">Nouvelle question</h3>
            <button class="faq-modale__fermer" type="button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="faq-modale__body">
            <div class="faq-form-groupe">
                <label for="faq-input-question">Question</label>
                <input type="text" id="faq-input-question" class="faq-input" placeholder="Saisissez la question...">
            </div>
            <div class="faq-form-groupe">
                <label for="faq-input-reponse">Réponse</label>
                <textarea id="faq-input-reponse" class="faq-textarea" rows="8" placeholder="Saisissez la réponse en texte simple..."></textarea>
            </div>
        </div>
        <div class="faq-modale__footer">
            <button id="faq-form-annuler" class="bouton bouton--secondaire" type="button">Annuler</button>
            <button id="faq-form-sauvegarder" class="bouton bouton--primaire" type="button">
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
    import VueFAQ from './assets/js/modules/statique/VueFAQ.js?v=<?= time() ?>';
    new VueFAQ();
</script>
