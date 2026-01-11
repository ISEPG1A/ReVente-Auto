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
 * - Les admins voient tous les articles (y compris brouillons)
 * - Les utilisateurs normaux ne voient que les articles publiés
 * 
 * @author  mat
 * @version 2.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Récupération et normalisation du nom du script actuel
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Calcul du préfixe d'URL pour les liens
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Section Hero - Avertissement -->
<section class="faq-hero">
    <div class="faq-hero__contenu">
        <h1 class="faq-hero__titre">Conditions Générales <span>d'Utilisation</span></h1>
        <p class="faq-hero__description">Veuillez lire attentivement nos conditions d'utilisation</p>
    </div>
    <div class="faq-hero__shapes">
        <div class="faq-shape faq-shape--1"></div>
        <div class="faq-shape faq-shape--2"></div>
    </div>
</section>

<!-- Section principale de la page CGU -->
<section class="section faq-section">
  <div class="conteneur">
    
    <!-- AVERTISSEMENT : Caractère fictif du projet -->
    <div class="warning-box" style="background-color: #fff3cd; border-left: 5px solid #ffc107; padding: 20px; margin-bottom: 30px; border-radius: 5px;">
        <h2 style="color: #856404; margin-top: 0;">IMPORTANT - À LIRE ATTENTIVEMENT</h2>
        <p style="color: #856404;"><strong>Ce site web et l'intégralité de son contenu constituent un projet pédagogique fictif développé dans un cadre scolaire.</strong></p>
        <p style="color: #856404;"><strong>L'utilisateur est expressément informé que :</strong></p>
        <ul style="color: #856404;">
            <li>Tous les véhicules présentés sur la plateforme sont <strong>fictifs</strong> et n'existent pas dans la réalité</li>
            <li>Toutes les annonces, tarifs et descriptions techniques sont <strong>générés à titre illustratif</strong></li>
            <li><strong>Aucune transaction commerciale réelle</strong> ne peut être effectuée via cette plateforme</li>
            <li>Ce site ne constitue en aucun cas un site de vente ou de commerce électronique opérationnel</li>
        </ul>
        <p style="margin-top: 15px; color: #856404;"><strong>Cette plateforme est destinée exclusivement à des fins pédagogiques et de démonstration.</strong></p>
    </div>

    <!-- Introduction CGU -->
    <div class="faq-intro">
        <span class="faq-badge">Legal</span>
        <h2 class="faq-intro__titre">Nos Conditions Générales d'Utilisation</h2>
        <p class="faq-intro__text">
            En utilisant notre site, vous acceptez les présentes conditions générales d'utilisation. 
            Veuillez cliquer sur chaque article pour lire son contenu détaillé.
        </p>
    </div>

    <!-- Conteneur des articles CGU en accordéon -->
    <div class="faq-container" id="cgu-articles-container">
        <!-- Les articles seront injectés ici par JavaScript -->
    </div>

    <!-- Message de chargement -->
    <div id="cgu-loading" class="cgu-loading" style="text-align: center; padding: 40px;">
        <p>Chargement des articles...</p>
    </div>

    <!-- Message d'erreur -->
    <div id="cgu-error" class="cgu-error" style="display: none; text-align: center; padding: 40px; color: #d32f2f;">
        <p>❌ Impossible de charger les articles CGU. Veuillez réessayer plus tard.</p>
    </div>

    <!-- Bouton "Ajouter un article" pour les administrateurs -->
    <div id="cgu-admin-add-container" class="cgu-admin-add-container" style="display: none; text-align: center; margin-top: 30px;">
        <button id="btn-ajouter-article" class="bouton bouton--primaire" style="padding: 12px 30px;">
            ➕ Ajouter un nouvel article
        </button>
    </div>

    <!-- Footer information -->
    <div style="background-color: #f0f0f0; padding: 20px; border-radius: 6px; margin-top: 40px; text-align: center;">
        <p><strong>Dernière mise à jour :</strong> <span id="cgu-date-maj">Chargement...</span></p>
        <p><strong>Version :</strong> 2.0 (Base de données)</p>
    </div>

    <!-- Confirmation Box -->
    <div class="confirmation-box" style="background-color: #e8f5e9; border-left: 5px solid #4caf50; padding: 20px; margin-top: 30px; border-radius: 5px;">
        ✓ En accédant et en utilisant ce Site, vous confirmez avoir lu, compris et accepté intégralement les présentes Conditions Générales d'Utilisation, notamment le caractère fictif et pédagogique de l'ensemble des contenus proposés.
    </div>

  </div>
</section>

<!-- Modale pour l'édition/ajout d'articles (pour les admins) -->
<div id="modal-edition-cgu" class="modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-contenu">
        <div class="modal-header">
            <h2 id="modal-titre">Modifier l'article</h2>
            <button class="modal-close" aria-label="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <form id="form-edition-cgu">
                <input type="hidden" id="article-id" name="article_id">
                
                <div class="form-groupe">
                    <label for="article-numero">Numéro d'article *</label>
                    <input type="number" id="article-numero" name="numero_article" 
                           min="1" required class="form-control"
                           placeholder="Ex: 1">
                    <small>Numéro affiché devant le titre (Article 1, Article 2, etc.)</small>
                </div>

                <div class="form-groupe">
                    <label for="article-titre">Titre de l'article *</label>
                    <input type="text" id="article-titre" name="titre" 
                           required class="form-control" maxlength="255"
                           placeholder="Ex: Objet et Définitions">
                    <small>Maximum 255 caractères</small>
                </div>

                <div class="form-groupe">
                    <label for="article-contenu">Contenu de l'article *</label>
                    <textarea id="article-contenu" name="contenu" 
                              required class="form-control" rows="15"
                              placeholder="Vous pouvez utiliser du HTML pour la mise en forme : <h3>, <p>, <ul>, <li>, <strong>, etc."></textarea>
                    <small>
                        Vous pouvez utiliser du HTML simple pour structurer le contenu :
                        <code>&lt;h3&gt;</code> pour les sous-titres, 
                        <code>&lt;p&gt;</code> pour les paragraphes,
                        <code>&lt;ul&gt;</code>/<code>&lt;li&gt;</code> pour les listes,
                        <code>&lt;strong&gt;</code> pour le gras.
                    </small>
                </div>

                <div class="form-groupe">
                    <label for="article-ordre">Ordre d'affichage</label>
                    <input type="number" id="article-ordre" name="ordre" 
                           min="0" class="form-control" placeholder="0">
                    <small>Plus le nombre est petit, plus l'article apparaît en haut.</small>
                </div>

                <div class="form-groupe">
                    <label for="article-statut">Statut *</label>
                    <select id="article-statut" name="statut" required class="form-control">
                        <option value="publie">Publié (visible par tous)</option>
                        <option value="brouillon">Brouillon (visible uniquement par les admins)</option>
                    </select>
                </div>

                <div class="form-groupe">
                    <label>
                        <input type="checkbox" id="article-visible" name="visible" checked>
                        Article visible
                    </label>
                    <small>Décochez pour masquer temporairement l'article</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="bouton bouton--secondaire modal-close">Annuler</button>
                    <button type="submit" class="bouton bouton--primaire">
                        <span id="btn-submit-text">Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="module">
    // Configuration globale pour le module VueCGU
    window.APP_CONFIG = {
        baseUrl: '<?= htmlspecialchars($prefixeUrl) ?>'
    };
    
    import VueCGU from '<?= htmlspecialchars($prefixeUrl) ?>public/assets/js/pages/VueCGU.js';
    new VueCGU();
</script>