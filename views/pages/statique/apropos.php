<?php
/**
 * Page "À propos" - Présentation de ReVente-Auto
 */
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Hero Section À propos -->
<section class="faq-hero">
    <div class="faq-hero__contenu">
        <h1 class="faq-hero__titre">À propos de <span style="color: white;">ReVente-Auto</span></h1>
        <p class="faq-hero__description">La marketplace automobile nouvelle génération qui révolutionne l'achat et la vente de véhicules d'occasion</p>
    </div>
    <div class="faq-hero__shapes">
        <div class="faq-shape faq-shape--1"></div>
        <div class="faq-shape faq-shape--2"></div>
    </div>
</section>

<!-- Section Notre Histoire -->
<section class="apropos-histoire">
    <div class="conteneur">
        <div class="section__header">
            <span class="section__badge section__badge--gradient">Notre Parcours</span>
            <h2 class="section__title">L'histoire de ReVente-Auto</h2>
            <p class="section__description">De l'idée à la réalisation, découvrez notre aventure</p>
        </div>
        
        <div class="apropos-histoire__timeline">
            <div class="apropos-timeline-item">
                <div class="apropos-timeline-item__icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="apropos-timeline-item__content">
                    <h3 class="apropos-timeline-item__title">2024 - Le constat</h3>
                    <p class="apropos-timeline-item__text">
                        Face aux plateformes compliquées, au manque de transparence et aux frais cachés, nous avons décidé de créer quelque chose de mieux.
                    </p>
                </div>
            </div>
            
            <div class="apropos-timeline-item">
                <div class="apropos-timeline-item__icon">
                    <i class="fas fa-code"></i>
                </div>
                <div class="apropos-timeline-item__content">
                    <h3 class="apropos-timeline-item__title">2025 - Le développement</h3>
                    <p class="apropos-timeline-item__text">
                        Création d'une plateforme moderne avec IA d'estimation, messagerie chiffrée et une interface intuitive pensée pour l'utilisateur.
                    </p>
                </div>
            </div>
            
            <div class="apropos-timeline-item">
                <div class="apropos-timeline-item__icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="apropos-timeline-item__content">
                    <h3 class="apropos-timeline-item__title">2026 - Aujourd'hui</h3>
                    <p class="apropos-timeline-item__text">
                        ReVente-Auto accompagne désormais des milliers d'utilisateurs dans leurs transactions, du premier contact jusqu'à la vente finale.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Nos Chiffres -->
<section class="apropos-chiffres">
    <div class="conteneur">
        <div class="apropos-histoire__stats">
            <div class="apropos-histoire-stat">
                <div class="apropos-histoire-stat__number">2 500+</div>
                <div class="apropos-histoire-stat__label">Annonces actives</div>
            </div>
            <div class="apropos-histoire-stat">
                <div class="apropos-histoire-stat__number">1 200+</div>
                <div class="apropos-histoire-stat__label">Vendeurs vérifiés</div>
            </div>
            <div class="apropos-histoire-stat">
                <div class="apropos-histoire-stat__number">98%</div>
                <div class="apropos-histoire-stat__label">Satisfaction</div>
            </div>
            <div class="apropos-histoire-stat">
                <div class="apropos-histoire-stat__number">&lt; 48h</div>
                <div class="apropos-histoire-stat__label">Réponse moyenne</div>
            </div>
        </div>
    </div>
</section>

<!-- Section Nos Services -->
<section class="apropos-services">
    <div class="conteneur">
        <div class="section__header">
            <span class="section__badge section__badge--orange">Nos Services</span>
            <h2 class="section__title">Tout ce dont vous avez besoin pour vendre et acheter</h2>
            <p class="section__description">Une plateforme complète avec des outils puissants pour faciliter vos transactions</p>
        </div>
        
        <div class="apropos-services__grid">
            <!-- Service 1 : Annonces -->
            <div class="apropos-service-card apropos-service-card--orange">
                <div class="apropos-service-card__icon">
                    <i class="fas fa-car"></i>
                </div>
                <h3 class="apropos-service-card__title">Publication d'annonces</h3>
                <p class="apropos-service-card__text">Créez des annonces détaillées avec photos HD, description complète et caractéristiques techniques. Gérez vos annonces en temps réel.</p>
                <ul class="apropos-service-card__list">
                    <li><i class="fas fa-check-circle"></i> Photos illimitées</li>
                    <li><i class="fas fa-check-circle"></i> Modification à tout moment</li>
                    <li><i class="fas fa-check-circle"></i> Statistiques de visibilité</li>
                </ul>
                <a href="<?= $prefixeUrl ?>ajout_vehicule" class="bouton bouton--orange">
                    <i class="fas fa-plus-circle"></i> Publier une annonce
                </a>
            </div>

            <!-- Service 2 : IA Estimation -->
            <div class="apropos-service-card apropos-service-card--purple">
                <div class="apropos-service-card__icon">
                    <i class="fas fa-brain"></i>
                </div>
                <h3 class="apropos-service-card__title">Estimation par IA</h3>
                <p class="apropos-service-card__text">Notre intelligence artificielle analyse des milliers de données pour vous proposer un prix de vente optimal et réaliste.</p>
                <ul class="apropos-service-card__list">
                    <li><i class="fas fa-check-circle"></i> Prix du marché en temps réel</li>
                    <li><i class="fas fa-check-circle"></i> Score de qualité automatique</li>
                    <li><i class="fas fa-check-circle"></i> Recommandations personnalisées</li>
                </ul>
                <a href="<?= $prefixeUrl ?>estimation" class="bouton bouton--purple">
                    <i class="fas fa-calculator"></i> Estimer mon véhicule
                </a>
            </div>

            <!-- Service 3 : Messagerie -->
            <div class="apropos-service-card apropos-service-card--blue">
                <div class="apropos-service-card__icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3 class="apropos-service-card__title">Messagerie sécurisée</h3>
                <p class="apropos-service-card__text">Communiquez avec les acheteurs et vendeurs via notre système de messagerie chiffrée de bout en bout pour une confidentialité totale.</p>
                <ul class="apropos-service-card__list">
                    <li><i class="fas fa-check-circle"></i> Chiffrement end-to-end</li>
                    <li><i class="fas fa-check-circle"></i> Notifications en temps réel</li>
                    <li><i class="fas fa-check-circle"></i> Historique sécurisé</li>
                </ul>
                <a href="<?= $prefixeUrl ?>messagerie" class="bouton bouton--blue">
                    <i class="fas fa-envelope"></i> Accéder aux messages
                </a>
            </div>

            <!-- Service 4 : Galerie -->
            <div class="apropos-service-card apropos-service-card--green">
                <div class="apropos-service-card__icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="apropos-service-card__title">Recherche avancée</h3>
                <p class="apropos-service-card__text">Trouvez le véhicule idéal grâce à nos filtres puissants : marque, modèle, prix, kilométrage, localisation et bien plus encore.</p>
                <ul class="apropos-service-card__list">
                    <li><i class="fas fa-check-circle"></i> Filtres multiples</li>
                    <li><i class="fas fa-check-circle"></i> Recherche géographique</li>
                    <li><i class="fas fa-check-circle"></i> Alertes personnalisées</li>
                </ul>
                <a href="<?= $prefixeUrl ?>galerie" class="bouton bouton--green">
                    <i class="fas fa-th"></i> Voir les annonces
                </a>
            </div>

            <!-- Service 5 : Favoris -->
            <div class="apropos-service-card apropos-service-card--pink">
                <div class="apropos-service-card__icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3 class="apropos-service-card__title">Gestion des favoris</h3>
                <p class="apropos-service-card__text">Sauvegardez vos annonces préférées pour les retrouver facilement et suivez leur évolution en temps réel.</p>
                <ul class="apropos-service-card__list">
                    <li><i class="fas fa-check-circle"></i> Sauvegarde illimitée</li>
                    <li><i class="fas fa-check-circle"></i> Synchronisation multi-appareils</li>
                    <li><i class="fas fa-check-circle"></i> Alertes de modification</li>
                </ul>
                <a href="<?= $prefixeUrl ?>favoris" class="bouton bouton--pink">
                    <i class="fas fa-star"></i> Mes favoris
                </a>
            </div>

            <!-- Service 6 : Support -->
            <div class="apropos-service-card apropos-service-card--yellow">
                <div class="apropos-service-card__icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="apropos-service-card__title">Support dédié</h3>
                <p class="apropos-service-card__text">Une question ? Un problème ? Notre équipe est là pour vous aider rapidement et efficacement à chaque étape.</p>
                <ul class="apropos-service-card__list">
                    <li><i class="fas fa-check-circle"></i> Réponse sous 48h</li>
                    <li><i class="fas fa-check-circle"></i> FAQ complète</li>
                    <li><i class="fas fa-check-circle"></i> Formulaire de contact</li>
                </ul>
                <a href="<?= $prefixeUrl ?>contact" class="bouton bouton--yellow">
                    <i class="fas fa-paper-plane"></i> Nous contacter
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Section Nos Engagements -->
<section class="apropos-engagements">
    <div class="conteneur">
        <div class="section__header">
            <span class="section__badge section__badge--gradient">Nos Engagements</span>
            <h2 class="section__title">Ce qui nous rend différents</h2>
        </div>
        <div class="apropos-engagements__grid">
            <div class="apropos-engagement-card">
                <div class="apropos-engagement-card__number">01</div>
                <div class="apropos-engagement-card__icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="apropos-engagement-card__title">Sécurité maximale</h3>
                <p class="apropos-engagement-card__text">Protection CSRF, messagerie chiffrée, validation des uploads et authentification renforcée.</p>
            </div>
            
            <div class="apropos-engagement-card">
                <div class="apropos-engagement-card__number">02</div>
                <div class="apropos-engagement-card__icon">
                    <i class="fas fa-ban"></i>
                </div>
                <h3 class="apropos-engagement-card__title">Zéro frais cachés</h3>
                <p class="apropos-engagement-card__text">Publication 100% gratuite, pas de commission. Vous gardez tout le prix de vente.</p>
            </div>
            
            <div class="apropos-engagement-card">
                <div class="apropos-engagement-card__number">03</div>
                <div class="apropos-engagement-card__icon">
                    <i class="fas fa-eye-slash"></i>
                </div>
                <h3 class="apropos-engagement-card__title">Vie privée respectée</h3>
                <p class="apropos-engagement-card__text">Vos données restent confidentielles. Aucun partage avec des tiers sans consentement.</p>
            </div>
            
            <div class="apropos-engagement-card">
                <div class="apropos-engagement-card__number">04</div>
                <div class="apropos-engagement-card__icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 class="apropos-engagement-card__title">100% responsive</h3>
                <p class="apropos-engagement-card__text">Interface moderne optimisée pour mobile, tablette et desktop.</p>
            </div>
        </div>
    </div>
</section>

<!-- Section Nos Valeurs -->
<section class="apropos-valeurs">
    <div class="conteneur">
        <div class="section__header">
            <span class="section__badge section__badge--gradient">Nos Valeurs</span>
            <h2 class="section__title">Les principes qui nous guident</h2>
            <p class="section__description">Les piliers fondamentaux de notre engagement envers vous</p>
        </div>
        <div class="apropos-valeurs__grid">
            <div class="apropos-valeur-card">
                <div class="apropos-valeur-card__icon">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <h3 class="apropos-valeur-card__title">Transparence</h3>
                <p class="apropos-valeur-card__text">Tous les frais sont affichés clairement. Pas de surprises, pas de coûts cachés.</p>
                <div class="apropos-valeur-card__highlight">Ce que vous voyez = Ce que vous payez</div>
            </div>
            
            <div class="apropos-valeur-card">
                <div class="apropos-valeur-card__icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="apropos-valeur-card__title">Communauté</h3>
                <p class="apropos-valeur-card__text">Nous bâtissons un écosystème bienveillant où chacun peut échanger sereinement.</p>
                <div class="apropos-valeur-card__highlight">2500+ membres actifs</div>
            </div>
            
            <div class="apropos-valeur-card">
                <div class="apropos-valeur-card__icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h3 class="apropos-valeur-card__title">Innovation</h3>
                <p class="apropos-valeur-card__text">Technologies de pointe pour une expérience utilisateur optimale et moderne.</p>
                <div class="apropos-valeur-card__highlight">IA + Sécurité avancée</div>
            </div>
            
            <div class="apropos-valeur-card">
                <div class="apropos-valeur-card__icon">
                    <i class="fas fa-award"></i>
                </div>
                <h3 class="apropos-valeur-card__title">Excellence</h3>
                <p class="apropos-valeur-card__text">Chaque détail compte, du design au support client, pour votre satisfaction.</p>
                <div class="apropos-valeur-card__highlight">98% de satisfaction</div>
            </div>
        </div>
    </div>
</section>

<!-- Section CTA Finale -->
<section class="apropos-cta-final">
    <div class="conteneur">
        <div class="apropos-cta-final__content">
            <div class="apropos-cta-final__badge">
                <i class="fas fa-rocket"></i> Rejoignez l'aventure
            </div>
            <h2 class="apropos-cta-final__titre">Prêt à transformer votre expérience automobile ?</h2>
            <p class="apropos-cta-final__text">
                Que vous soyez acheteur ou vendeur, ReVente-Auto vous offre tous les outils pour réussir vos transactions en toute confiance.
            </p>
            <div class="apropos-cta-final__stats">
                <div class="apropos-cta-final__stat">
                    <div class="apropos-cta-final__stat-number">0€</div>
                    <div class="apropos-cta-final__stat-label">de frais</div>
                </div>
                <div class="apropos-cta-final__stat">
                    <div class="apropos-cta-final__stat-number">2min</div>
                    <div class="apropos-cta-final__stat-label">pour publier</div>
                </div>
                <div class="apropos-cta-final__stat">
                    <div class="apropos-cta-final__stat-number">24/7</div>
                    <div class="apropos-cta-final__stat-label">disponible</div>
                </div>
            </div>
            <div class="apropos-cta-final__buttons">
                <a class="bouton bouton--orange bouton--large" href="<?= $prefixeUrl ?>connexion">
                    <i class="fas fa-user-plus"></i> Créer mon compte gratuitement
                </a>
                <a class="bouton bouton--outline-white bouton--large" href="<?= $prefixeUrl ?>galerie">
                    <i class="fas fa-search"></i> Découvrir les annonces
                </a>
            </div>
        </div>
    </div>
</section>
