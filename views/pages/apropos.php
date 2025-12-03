<?php
/**
 * Page "À propos" - Présentation de l'application ReVente-Auto
 */
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Hero Section À propos -->
<section class="apropos-hero">
    <div class="apropos-hero__contenu">
        <h1 class="apropos-hero__titre">À propos de <span>ReVente-Auto</span></h1>
        <p class="apropos-hero__description">Votre partenaire de confiance pour l'achat et la vente de véhicules d'occasion depuis 2020.</p>
    </div>
    <div class="apropos-hero__shapes">
        <div class="apropos-shape apropos-shape--1"></div>
        <div class="apropos-shape apropos-shape--2"></div>
    </div>
</section>

<!-- Section Mission -->
<section class="section apropos-mission">
    <div class="conteneur">
        <div class="apropos-mission__grid">
            <div class="apropos-mission__content">
                <span class="apropos-badge">Notre Mission</span>
                <h2 class="apropos-mission__titre">Simplifier l'achat et la vente de véhicules</h2>
                <p class="apropos-mission__text">
                    <strong>ReVente-Auto</strong> est né d'une idée simple : rendre le marché de l'occasion automobile plus transparent, plus sûr et plus accessible à tous.
                </p>
                <p class="apropos-mission__text">
                    Notre plateforme met en relation acheteurs et vendeurs dans un environnement de confiance, avec des outils modernes pour faciliter chaque transaction.
                </p>
            </div>
            <div class="apropos-mission__image">
                <div class="apropos-mission__icon-grid">
                    <div class="apropos-icon-box">
                        <i class="fas fa-handshake"></i>
                        <span>Confiance</span>
                    </div>
                    <div class="apropos-icon-box">
                        <i class="fas fa-shield-alt"></i>
                        <span>Sécurité</span>
                    </div>
                    <div class="apropos-icon-box">
                        <i class="fas fa-bolt"></i>
                        <span>Rapidité</span>
                    </div>
                    <div class="apropos-icon-box">
                        <i class="fas fa-heart"></i>
                        <span>Passion</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Stats -->
<section class="apropos-stats">
    <div class="conteneur">
        <div class="apropos-stats__grid">
            <div class="apropos-stat">
                <div class="apropos-stat__number">500+</div>
                <div class="apropos-stat__label">Véhicules vendus</div>
            </div>
            <div class="apropos-stat">
                <div class="apropos-stat__number">1000+</div>
                <div class="apropos-stat__label">Utilisateurs actifs</div>
            </div>
            <div class="apropos-stat">
                <div class="apropos-stat__number">98%</div>
                <div class="apropos-stat__label">Clients satisfaits</div>
            </div>
            <div class="apropos-stat">
                <div class="apropos-stat__number">24/7</div>
                <div class="apropos-stat__label">Support disponible</div>
            </div>
        </div>
    </div>
</section>

<!-- Section Fonctionnalités -->
<section class="section apropos-features">
    <div class="conteneur">
        <div class="apropos-features__header">
            <span class="apropos-badge">Fonctionnalités</span>
            <h2 class="apropos-features__titre">Ce que nous offrons</h2>
        </div>
        <div class="apropos-features__grid">
            <div class="apropos-feature">
                <div class="apropos-feature__icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3 class="apropos-feature__title">Inscription facile</h3>
                <p class="apropos-feature__text">Créez votre compte en quelques secondes et accédez à toutes les fonctionnalités.</p>
            </div>
            <div class="apropos-feature">
                <div class="apropos-feature__icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="apropos-feature__title">Recherche avancée</h3>
                <p class="apropos-feature__text">Filtrez par marque, prix, année, kilométrage et bien plus encore.</p>
            </div>
            <div class="apropos-feature">
                <div class="apropos-feature__icon">
                    <i class="fas fa-camera"></i>
                </div>
                <h3 class="apropos-feature__title">Photos HD</h3>
                <p class="apropos-feature__text">Uploadez des photos haute qualité pour mettre en valeur votre véhicule.</p>
            </div>
            <div class="apropos-feature">
                <div class="apropos-feature__icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3 class="apropos-feature__title">Messagerie intégrée</h3>
                <p class="apropos-feature__text">Communiquez directement avec les vendeurs via notre messagerie sécurisée.</p>
            </div>
            <div class="apropos-feature">
                <div class="apropos-feature__icon">
                    <i class="fas fa-calculator"></i>
                </div>
                <h3 class="apropos-feature__title">Estimation de prix</h3>
                <p class="apropos-feature__text">Obtenez une estimation du prix de votre véhicule basée sur le marché.</p>
            </div>
            <div class="apropos-feature">
                <div class="apropos-feature__icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3 class="apropos-feature__title">Favoris</h3>
                <p class="apropos-feature__text">Sauvegardez vos annonces préférées pour les retrouver facilement.</p>
            </div>
        </div>
    </div>
</section>

<!-- Section Technologies -->
<section class="section apropos-tech">
    <div class="conteneur">
        <div class="apropos-tech__header">
            <span class="apropos-badge">Technologies</span>
            <h2 class="apropos-tech__titre">Construit avec les meilleures technologies</h2>
        </div>
        <div class="apropos-tech__grid">
            <div class="apropos-tech__item">
                <i class="fab fa-php"></i>
                <span>PHP 8.2</span>
            </div>
            <div class="apropos-tech__item">
                <i class="fas fa-database"></i>
                <span>MySQL 8.0</span>
            </div>
            <div class="apropos-tech__item">
                <i class="fab fa-js"></i>
                <span>JavaScript ES6+</span>
            </div>
            <div class="apropos-tech__item">
                <i class="fab fa-css3-alt"></i>
                <span>CSS3</span>
            </div>
        </div>
    </div>
</section>

<!-- Section CTA -->
<section class="apropos-cta">
    <div class="conteneur">
        <div class="apropos-cta__content">
            <h2 class="apropos-cta__titre">Prêt à commencer ?</h2>
            <p class="apropos-cta__text">Rejoignez notre communauté et trouvez votre prochain véhicule dès aujourd'hui.</p>
            <div class="apropos-cta__buttons">
                <a class="bouton apropos-cta__btn" href="<?= $prefixeUrl ?>galerie">
                    <i class="fas fa-car"></i> Voir la galerie
                </a>
                <a class="bouton bouton--fantome apropos-cta__btn" href="<?= $prefixeUrl ?>connexion">
                    <i class="fas fa-user-plus"></i> S'inscrire
                </a>
            </div>
        </div>
    </div>
</section>
