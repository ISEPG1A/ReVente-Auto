<?php
/**
 * Page d'accueil - Landing page de l'application ReVente-Auto
 */
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Hero Section -->
<section class="hero hero--full">
  <div class="hero__background">
    <div class="hero__shapes">
      <div class="hero__shape hero__shape--1"></div>
      <div class="hero__shape hero__shape--2"></div>
      <div class="hero__shape hero__shape--3"></div>
    </div>
  </div>
  <div class="conteneur hero__content">
    <div class="hero__text">
      <span class="hero__badge">
        <i class="fas fa-star"></i> Plateforme N°1 de vente auto
      </span>
      <h1 class="hero__title">
        Trouvez le véhicule <br>
        <span class="hero__highlight">de vos rêves</span>
      </h1>
      <p class="hero__description">
        Découvrez des milliers de véhicules d'occasion vérifiés. 
        Achetez ou vendez en toute confiance avec ReVente-Auto.
      </p>
      <div class="hero__actions">
        <a class="bouton bouton--lg" href="<?= $prefixeUrl ?>galerie">
          <i class="fas fa-search"></i> Explorer les annonces
        </a>
        <a class="bouton bouton--fantome bouton--lg" href="<?= $prefixeUrl ?>ajout_vehicule">
          <i class="fas fa-plus"></i> Déposer une annonce
        </a>
      </div>
      <div class="hero__stats">
        <div class="hero__stat">
          <span class="hero__stat-number">2500+</span>
          <span class="hero__stat-label">Véhicules</span>
        </div>
        <div class="hero__stat">
          <span class="hero__stat-number">1200+</span>
          <span class="hero__stat-label">Vendeurs</span>
        </div>
        <div class="hero__stat">
          <span class="hero__stat-number">98%</span>
          <span class="hero__stat-label">Satisfaction</span>
        </div>
      </div>
    </div>
    <div class="hero__visual">
      <div class="hero__car-card">
        <div class="hero__car-image">
          <i class="fas fa-car-side"></i>
        </div>
        <div class="hero__car-badge">Populaire</div>
      </div>
    </div>
  </div>
</section>

<!-- Section Fonctionnalités -->
<section class="features">
  <div class="conteneur">
    <h2 class="features__title">Pourquoi choisir ReVente-Auto ?</h2>
    <div class="features__grid">
      <div class="feature-card">
        <div class="feature-card__icon">
          <i class="fas fa-shield-alt"></i>
        </div>
        <h3 class="feature-card__title">Sécurisé</h3>
        <p class="feature-card__text">Transactions sécurisées et messagerie chiffrée de bout en bout.</p>
      </div>
      <div class="feature-card">
        <div class="feature-card__icon">
          <i class="fas fa-calculator"></i>
        </div>
        <h3 class="feature-card__title">Estimation IA</h3>
        <p class="feature-card__text">Obtenez une estimation précise grâce à notre intelligence artificielle.</p>
      </div>
      <div class="feature-card">
        <div class="feature-card__icon">
          <i class="fas fa-heart"></i>
        </div>
        <h3 class="feature-card__title">Favoris</h3>
        <p class="feature-card__text">Sauvegardez vos coups de cœur et suivez leur évolution.</p>
      </div>
      <div class="feature-card">
        <div class="feature-card__icon">
          <i class="fas fa-comments"></i>
        </div>
        <h3 class="feature-card__title">Messagerie</h3>
        <p class="feature-card__text">Communiquez directement avec les vendeurs en toute simplicité.</p>
      </div>
    </div>
  </div>
</section>

<!-- Section CTA -->
<section class="cta-section">
  <div class="conteneur">
    <div class="cta-box">
      <div class="cta-box__content">
        <h2 class="cta-box__title">Prêt à vendre votre véhicule ?</h2>
        <p class="cta-box__text">Créez votre annonce en quelques minutes et touchez des milliers d'acheteurs potentiels.</p>
      </div>
      <a class="bouton bouton--light bouton--lg" href="<?= $prefixeUrl ?>inscription">
        Commencer gratuitement <i class="fas fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>
