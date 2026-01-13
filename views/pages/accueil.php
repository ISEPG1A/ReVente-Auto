<?php
/**
 * Page d'accueil - Landing page de l'application ReVente-Auto
 */
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Hero Section Immersive -->
<section class="hero-accueil">
  <div class="hero-accueil__background">
    <div class="hero-accueil__gradient"></div>
    <div class="hero-accueil__shapes">
      <div class="hero-shape hero-shape--1"></div>
      <div class="hero-shape hero-shape--2"></div>
      <div class="hero-shape hero-shape--3"></div>
    </div>
  </div>
  
  <div class="conteneur">
    <div class="hero-accueil__content">
      <div class="hero-accueil__text">
        <div class="hero-accueil__badge">
          <i class="fas fa-bolt"></i>
          <span>La plateforme automobile nouvelle génération</span>
        </div>
        
        <h1 class="hero-accueil__title">
          Achetez et vendez<br>
          <span class="hero-accueil__title--gradient">en toute confiance</span>
        </h1>
        
        <p class="hero-accueil__description">
          Découvrez des milliers de véhicules vérifiés. Estimation IA, messagerie chiffrée 
          et zéro frais cachés. La nouvelle façon d'acheter et vendre.
        </p>
        
        <div class="hero-accueil__actions">
          <a class="hero-accueil__btn hero-accueil__btn--primary" href="<?= $prefixeUrl ?>galerie">
            <i class="fas fa-search"></i>
            <span>Explorer les annonces</span>
          </a>
          <a class="hero-accueil__btn hero-accueil__btn--secondary" href="<?= $prefixeUrl ?>ajout_vehicule">
            <i class="fas fa-plus-circle"></i>
            <span>Déposer une annonce</span>
          </a>
        </div>
        
        <div class="hero-accueil__trust">
          <div class="hero-accueil__trust-item">
            <i class="fas fa-shield-alt"></i>
            <span>100% Sécurisé</span>
          </div>
          <div class="hero-accueil__trust-item">
            <i class="fas fa-ban"></i>
            <span>Zéro frais</span>
          </div>
          <div class="hero-accueil__trust-item">
            <i class="fas fa-lock"></i>
            <span>Données protégées</span>
          </div>
        </div>
      </div>
      
      <div class="hero-accueil__visual">
        <div class="hero-accueil__cards">
          <div class="hero-card hero-card--main">
            <div class="hero-card__icon">
              <i class="fas fa-car"></i>
            </div>
            <div class="hero-card__content">
              <span class="hero-card__label">Voitures</span>
              <span class="hero-card__count">2 500+</span>
            </div>
          </div>
          <div class="hero-card hero-card--alt">
            <div class="hero-card__icon">
              <i class="fas fa-motorcycle"></i>
            </div>
            <div class="hero-card__content">
              <span class="hero-card__label">Motos</span>
              <span class="hero-card__count">800+</span>
            </div>
          </div>
          <div class="hero-card hero-card--alt">
            <div class="hero-card__icon">
              <i class="fas fa-truck"></i>
            </div>
            <div class="hero-card__content">
              <span class="hero-card__label">Utilitaires</span>
              <span class="hero-card__count">450+</span>
            </div>
          </div>
        </div>
        
        <div class="hero-accueil__stats-floating">
          <div class="floating-stat">
            <div class="floating-stat__icon">
              <i class="fas fa-users"></i>
            </div>
            <div class="floating-stat__content">
              <span class="floating-stat__number">1 200+</span>
              <span class="floating-stat__label">Vendeurs vérifiés</span>
            </div>
          </div>
          <div class="floating-stat floating-stat--right">
            <div class="floating-stat__icon">
              <i class="fas fa-star"></i>
            </div>
            <div class="floating-stat__content">
              <span class="floating-stat__number">98%</span>
              <span class="floating-stat__label">Satisfaction client</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Section Points Forts -->
<section class="section-avantages">
  <div class="conteneur">
    <div class="section-avantages__header">
      <span class="section-avantages__badge">
        <i class="fas fa-award"></i> Pourquoi nous choisir
      </span>
      <h2 class="section-avantages__title">Une expérience automobile repensée</h2>
      <p class="section-avantages__subtitle">Des outils puissants pour faciliter vos transactions</p>
    </div>
    
    <div class="avantages-grid">
      <div class="avantage-card">
        <div class="avantage-card__icon avantage-card__icon--orange">
          <i class="fas fa-brain"></i>
        </div>
        <h3 class="avantage-card__title">Estimation par IA</h3>
        <p class="avantage-card__text">
          Notre intelligence artificielle analyse le marché en temps réel pour vous proposer 
          le prix le plus juste pour votre véhicule.
        </p>
        <a href="<?= $prefixeUrl ?>estimation" class="avantage-card__link">
          Estimer mon véhicule <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      
      <div class="avantage-card">
        <div class="avantage-card__icon avantage-card__icon--purple">
          <i class="fas fa-lock"></i>
        </div>
        <h3 class="avantage-card__title">Messagerie chiffrée</h3>
        <p class="avantage-card__text">
          Communiquez avec les acheteurs et vendeurs via notre système de messagerie 
          chiffrée de bout en bout. Vos conversations restent privées.
        </p>
        <a href="<?= $prefixeUrl ?>messagerie" class="avantage-card__link">
          Découvrir <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      
      <div class="avantage-card">
        <div class="avantage-card__icon avantage-card__icon--green">
          <i class="fas fa-shield-alt"></i>
        </div>
        <h3 class="avantage-card__title">Sécurité maximale</h3>
        <p class="avantage-card__text">
          Protection CSRF, validation des uploads, authentification renforcée. 
          Votre sécurité est notre priorité absolue.
        </p>
        <a href="<?= $prefixeUrl ?>faq" class="avantage-card__link">
          En savoir plus <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      
      <div class="avantage-card">
        <div class="avantage-card__icon avantage-card__icon--blue">
          <i class="fas fa-heart"></i>
        </div>
        <h3 class="avantage-card__title">Favoris & Alertes</h3>
        <p class="avantage-card__text">
          Sauvegardez vos coups de cœur et suivez leur évolution. 
          Ne manquez plus jamais une bonne affaire.
        </p>
        <a href="<?= $prefixeUrl ?>favoris" class="avantage-card__link">
          Mes favoris <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Section Comment ça marche -->
<section class="section-etapes">
  <div class="conteneur">
    <div class="section-etapes__header">
      <span class="section-etapes__badge">
        <i class="fas fa-rocket"></i> Simple et rapide
      </span>
      <h2 class="section-etapes__title">Vendez en 3 étapes</h2>
      <p class="section-etapes__subtitle">Créez votre annonce en quelques minutes</p>
    </div>
    
    <div class="etapes-grid">
      <div class="etape-card">
        <div class="etape-card__number">01</div>
        <div class="etape-card__content">
          <h3 class="etape-card__title">Créez votre compte</h3>
          <p class="etape-card__text">
            Inscription gratuite en 2 minutes. Aucune carte bancaire requise. 
            Commencez à vendre immédiatement.
          </p>
        </div>
        <div class="etape-card__icon">
          <i class="fas fa-user-plus"></i>
        </div>
      </div>
      
      <div class="etape-card">
        <div class="etape-card__number">02</div>
        <div class="etape-card__content">
          <h3 class="etape-card__title">Publiez votre annonce</h3>
          <p class="etape-card__text">
            Ajoutez photos et description détaillée. Notre IA vous aide à fixer 
            le bon prix et optimiser votre annonce.
          </p>
        </div>
        <div class="etape-card__icon">
          <i class="fas fa-camera"></i>
        </div>
      </div>
      
      <div class="etape-card">
        <div class="etape-card__number">03</div>
        <div class="etape-card__content">
          <h3 class="etape-card__title">Vendez en sécurité</h3>
          <p class="etape-card__text">
            Recevez des messages d'acheteurs intéressés, négociez et 
            finalisez la vente en toute tranquillité.
          </p>
        </div>
        <div class="etape-card__icon">
          <i class="fas fa-handshake"></i>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Section Chiffres clés -->
<section class="section-chiffres">
  <div class="conteneur">
    <div class="chiffres-grid">
      <div class="chiffre-item">
        <div class="chiffre-item__number">0€</div>
        <div class="chiffre-item__label">de frais</div>
        <p class="chiffre-item__text">Publication 100% gratuite, pas de commission</p>
      </div>
      <div class="chiffre-item">
        <div class="chiffre-item__number">&lt;2min</div>
        <div class="chiffre-item__label">pour publier</div>
        <p class="chiffre-item__text">Formulaire simple et guidé par étapes</p>
      </div>
      <div class="chiffre-item">
        <div class="chiffre-item__number">24/7</div>
        <div class="chiffre-item__label">disponible</div>
        <p class="chiffre-item__text">Accédez à vos annonces à tout moment</p>
      </div>
      <div class="chiffre-item">
        <div class="chiffre-item__number">100%</div>
        <div class="chiffre-item__label">responsive</div>
        <p class="chiffre-item__text">Interface optimisée mobile et desktop</p>
      </div>
    </div>
  </div>
</section>

<!-- Section CTA Finale -->
<section class="section-cta-final">
  <div class="conteneur">
    <div class="cta-final">
      <div class="cta-final__content">
        <div class="cta-final__badge">
          <i class="fas fa-fire"></i>
          <span>Commencez maintenant</span>
        </div>
        <h2 class="cta-final__title">Prêt à vendre votre véhicule ?</h2>
        <p class="cta-final__text">
          Rejoignez des milliers de vendeurs satisfaits. Créez votre annonce 
          gratuitement et touchez des acheteurs potentiels dès aujourd'hui.
        </p>
        <div class="cta-final__actions">
          <a class="cta-final__btn cta-final__btn--primary" href="<?= $prefixeUrl ?>inscription">
            <i class="fas fa-user-plus"></i>
            <span>Créer mon compte gratuit</span>
          </a>
          <a class="cta-final__btn cta-final__btn--secondary" href="<?= $prefixeUrl ?>galerie">
            <i class="fas fa-search"></i>
            <span>Parcourir les annonces</span>
          </a>
        </div>
      </div>
      <div class="cta-final__visual">
        <div class="cta-visual__circle"></div>
        <div class="cta-visual__icon">
          <i class="fas fa-car"></i>
        </div>
      </div>
    </div>
  </div>
</section>
