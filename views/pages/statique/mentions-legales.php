<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PAGE MENTIONS LÉGALES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Page statique affichant les mentions légales obligatoires.
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
<section class="ml-hero">
    <div class="ml-hero__contenu">
        <h1 class="ml-hero__titre">Mentions <span>Légales</span></h1>
        <p class="ml-hero__description">Informations légales relatives au site ReVente-Auto</p>
    </div>
    <div class="ml-hero__shapes">
        <div class="ml-shape ml-shape--1"></div>
        <div class="ml-shape ml-shape--2"></div>
    </div>
</section>

<!-- Section Mentions Légales -->
<section class="section ml-section-main">
    <div class="conteneur">
        
        <!-- Intro -->
        <div class="ml-intro">
            <span class="ml-badge">Informations</span>
            <h2 class="ml-intro__titre">Conformité légale</h2>
            <p class="ml-intro__text">
                Conformément aux dispositions de la loi n° 2004-575 du 21 juin 2004 pour la confiance en l'économie numérique, 
                voici les informations légales du site.
            </p>
        </div>

        <!-- Container des sections -->
        <div class="ml-container">
            
            <!-- Section 1 : Éditeur du site -->
            <article class="ml-section">
                <h2 class="ml-section__titre">Éditeur du site</h2>
                <div class="ml-section__contenu">
                    <p>Le site <strong>ReVente-Auto</strong> est une plateforme de petites annonces automobiles.</p>
                    <ul class="ml-liste">
                        <li><strong>Nom du site :</strong> ReVente-Auto</li>
                        <li><strong>URL :</strong> https://revente-auto.hangar.garageisep.com</li>
                        <li><strong>Nature :</strong> Plateforme de petites annonces automobiles</li>
                        <li><strong>Responsable de la publication :</strong> Stelyx</li>
                        <li><strong>Adresse :</strong> 10 Rue de Vanves, 92130 Issy-les-Moulineaux, France</li>
                        <li><strong>Email :</strong> reventeauto.service@gmail.com</li>
                        <li><strong>Téléphone :</strong> +33 1 23 45 67 89</li>
                    </ul>
                </div>
            </article>

            <!-- Section 2 : Hébergement -->
            <article class="ml-section">
                <h2 class="ml-section__titre">Hébergement</h2>
                <div class="ml-section__contenu">
                    <p>Le site est hébergé par :</p>
                    <ul class="ml-liste">
                        <li><strong>Hébergeur :</strong> Hangar</li>
                        <li><strong>Organisation :</strong> GarageIsep</li>
                        <li><strong>Adresse :</strong> 10 Rue de Vanves, 92130 Issy-les-Moulineaux, France</li>
                        <li><strong>Site web :</strong> <a href="https://hangar.garageisep.com" target="_blank" rel="noopener" class="ml-link">https://hangar.garageisep.com</a></li>
                    </ul>
                </div>
            </article>

            <!-- Section 3 : Propriété intellectuelle -->
            <article class="ml-section">
                <h2 class="ml-section__titre">Propriété intellectuelle</h2>
                <div class="ml-section__contenu">
                    <p>
                        L'ensemble du contenu du site ReVente-Auto (structure, textes, logos, images, éléments graphiques, 
                        logiciels, base de données, etc.) est protégé par les dispositions du Code de la Propriété Intellectuelle.
                    </p>
                    <p>
                        Toute reproduction, représentation, modification, publication ou adaptation de tout ou partie des éléments 
                        du site, quel que soit le moyen ou le procédé utilisé, est interdite sans l'autorisation écrite préalable 
                        de l'équipe ReVente-Auto.
                    </p>
                    <p>
                        Les marques, logos et noms de produits cités sur le site sont la propriété de leurs détenteurs respectifs.
                    </p>
                </div>
            </article>

            <!-- Section 4 : Responsabilité -->
            <article class="ml-section">
                <h2 class="ml-section__titre">Limitation de responsabilité</h2>
                <div class="ml-section__contenu">
                    <p>
                        ReVente-Auto s'efforce d'assurer au mieux l'exactitude et la mise à jour des informations diffusées 
                        sur ce site. Toutefois, nous ne pouvons garantir l'exactitude, la précision ou l'exhaustivité des 
                        informations mises à disposition.
                    </p>
                    <p><strong>Annonces publiées par les utilisateurs :</strong></p>
                    <ul class="ml-liste">
                        <li>ReVente-Auto agit en tant qu'intermédiaire et n'est pas partie aux transactions entre utilisateurs.</li>
                        <li>Nous ne sommes pas responsables du contenu des annonces publiées par les utilisateurs.</li>
                        <li>Chaque utilisateur est seul responsable des informations qu'il publie.</li>
                        <li>Nous nous réservons le droit de supprimer tout contenu inapproprié ou illicite.</li>
                    </ul>
                    <p><strong>Transactions :</strong></p>
                    <p>
                        ReVente-Auto ne peut être tenu responsable des litiges pouvant survenir lors de transactions 
                        entre utilisateurs. Nous recommandons à chaque utilisateur de prendre toutes les précautions 
                        nécessaires lors de l'achat ou de la vente d'un véhicule.
                    </p>
                </div>
            </article>

            <!-- Section 5 : Données personnelles -->
            <article class="ml-section">
                <h2 class="ml-section__titre">Protection des données personnelles</h2>
                <div class="ml-section__contenu">
                    <p>
                        Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique 
                        et Libertés, vous disposez d'un droit d'accès, de rectification, de suppression et d'opposition 
                        concernant vos données personnelles.
                    </p>
                    <p>
                        Pour plus d'informations sur la collecte et le traitement de vos données, veuillez consulter 
                        notre <a href="<?= htmlspecialchars($prefixeUrl) ?>politique-confidentialite" class="ml-link">Politique de Confidentialité</a>.
                    </p>
                    <p>
                        Pour exercer vos droits, vous pouvez nous contacter via le 
                        <a href="<?= htmlspecialchars($prefixeUrl) ?>contact" class="ml-link">formulaire de contact</a>.
                    </p>
                </div>
            </article>

            <!-- Section 6 : Cookies -->
            <article class="ml-section">
                <h2 class="ml-section__titre">Utilisation des cookies</h2>
                <div class="ml-section__contenu">
                    <p>
                        Le site ReVente-Auto utilise des cookies essentiels au bon fonctionnement du service :
                    </p>
                    <ul class="ml-liste">
                        <li><strong>PHPSESSID :</strong> Cookie de session nécessaire à l'authentification et à la navigation sécurisée.</li>
                        <li><strong>vehicle_views_tracking :</strong> Cookie permettant de comptabiliser les vues uniques des annonces.</li>
                    </ul>
                    <p>
                        Aucun cookie publicitaire ou de traçage tiers n'est utilisé sur notre site.
                    </p>
                </div>
            </article>

            <!-- Section 7 : Liens hypertextes -->
            <article class="ml-section">
                <h2 class="ml-section__titre">Liens hypertextes</h2>
                <div class="ml-section__contenu">
                    <p>
                        Le site ReVente-Auto peut contenir des liens vers d'autres sites internet. 
                        Nous n'exerçons aucun contrôle sur ces sites et déclinons toute responsabilité 
                        quant à leur contenu.
                    </p>
                    <p>
                        La mise en place de liens hypertextes vers notre site est autorisée sans demande 
                        préalable, à condition que ces liens n'aient pas un caractère commercial ou publicitaire.
                    </p>
                </div>
            </article>

            <!-- Section 8 : Droit applicable -->
            <article class="ml-section">
                <h2 class="ml-section__titre">Droit applicable</h2>
                <div class="ml-section__contenu">
                    <p>
                        Les présentes mentions légales sont régies par le droit français. 
                        En cas de litige, et après échec de toute tentative de recherche d'une solution amiable, 
                        les tribunaux français seront seuls compétents.
                    </p>
                </div>
            </article>

            <!-- Section 9 : Crédits -->
            <article class="ml-section">
                <h2 class="ml-section__titre">Crédits</h2>
                <div class="ml-section__contenu">
                    <p><strong>Conception et développement :</strong></p>
                    <ul class="ml-liste">
                        <li>Équipe ReVente-Auto</li>
                    </ul>
                    <p><strong>Technologies utilisées :</strong></p>
                    <ul class="ml-liste">
                        <li>PHP 8.x</li>
                        <li>MySQL / MariaDB</li>
                        <li>JavaScript ES6+</li>
                        <li>HTML5 / CSS3</li>
                    </ul>
                </div>
            </article>

        </div>

        <!-- Section CTA -->
        <div class="ml-cta">
            <div class="ml-cta__content">
                <h3 class="ml-cta__titre">Une question ?</h3>
                <p class="ml-cta__text">N'hésitez pas à nous contacter pour toute question relative aux mentions légales.</p>
                <a href="<?= htmlspecialchars($prefixeUrl) ?>contact" class="btn btn--principal">
                    Nous contacter
                </a>
            </div>
        </div>

    </div>
</section>
