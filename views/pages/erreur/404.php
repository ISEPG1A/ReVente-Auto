<?php
/**
 * Page d'erreur 404 - Page non trouvée
 * Design moderne avec illustration animée
 * 
 * CSS: public/assets/css/pages/erreur-404.css
 */

// Calcul du préfixe d'URL pour les liens
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
// 🔒 SÉCURITÉ : Échapper le préfixe d'URL
$prefixeUrlSafe = htmlspecialchars($prefixeUrl, ENT_QUOTES, 'UTF-8');
?>

<!-- Particules décoratives -->
<div class="erreur-404__particules" aria-hidden="true">
  <span class="erreur-404__particule"></span>
  <span class="erreur-404__particule"></span>
  <span class="erreur-404__particule"></span>
  <span class="erreur-404__particule"></span>
</div>

<section class="page-erreur-404">
  <div class="erreur-404__conteneur">
    
    <!-- Illustration animée d'une voiture perdue -->
    <div class="erreur-404__illustration" aria-hidden="true">
      <svg class="erreur-404__svg" viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg">
        <!-- Dégradé orange-violet pour la route -->
        <defs>
          <linearGradient id="degradeOrangeViolet" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#f59e0b"/>
            <stop offset="100%" style="stop-color:#8b5cf6"/>
          </linearGradient>
          <linearGradient id="degradeVoiture" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#f59e0b"/>
            <stop offset="100%" style="stop-color:#d97706"/>
          </linearGradient>
        </defs>
        
        <!-- Route avec dégradé -->
        <path class="erreur-404__route" d="M0 160 Q100 140, 200 160 T400 160" fill="none" stroke="url(#degradeOrangeViolet)" stroke-width="8" stroke-dasharray="20,10"/>
        
        <!-- Panneau de signalisation violet -->
        <g class="erreur-404__panneau">
          <rect x="300" y="60" width="80" height="50" rx="8" fill="#8b5cf6"/>
          <text x="340" y="92" fill="white" font-size="24" font-weight="bold" text-anchor="middle">404</text>
          <rect x="336" y="110" width="8" height="40" fill="#6b7280"/>
        </g>
        
        <!-- Voiture orange -->
        <g class="erreur-404__voiture">
          <ellipse cx="120" cy="158" rx="25" ry="4" fill="rgba(0,0,0,0.3)"/>
          <!-- Corps orange -->
          <path d="M60 130 Q70 110, 95 110 L145 110 Q170 110, 180 130 L180 145 Q180 150, 175 150 L65 150 Q60 150, 60 145 Z" fill="url(#degradeVoiture)"/>
          <!-- Toit plus foncé -->
          <path d="M80 110 Q85 95, 110 95 L130 95 Q155 95, 160 110 Z" fill="#b45309"/>
          <!-- Vitres violettes -->
          <path d="M88 108 Q92 98, 108 98 L115 98 L115 108 Z" fill="#a78bfa" opacity="0.7"/>
          <path d="M120 98 L132 98 Q148 98, 152 108 L120 108 Z" fill="#a78bfa" opacity="0.7"/>
          <!-- Roues -->
          <circle cx="85" cy="150" r="14" fill="#1e293b"/>
          <circle cx="155" cy="150" r="14" fill="#1e293b"/>
          <circle cx="85" cy="150" r="8" fill="#374151"/>
          <circle cx="155" cy="150" r="8" fill="#374151"/>
          <circle cx="85" cy="150" r="3" fill="#6b7280"/>
          <circle cx="155" cy="150" r="3" fill="#6b7280"/>
          <!-- Phares orange -->
          <ellipse cx="178" cy="138" rx="4" ry="6" fill="#fbbf24"/>
          <ellipse cx="62" cy="138" rx="3" ry="5" fill="#ef4444"/>
        </g>
        
        <!-- Point d'interrogation violet -->
        <text x="120" y="75" fill="#8b5cf6" font-size="32" font-weight="bold" text-anchor="middle" class="erreur-404__question">?</text>
        
        <!-- Nuages décoratifs -->
        <g class="erreur-404__nuages" opacity="0.2">
          <ellipse cx="50" cy="40" rx="30" ry="15" fill="#94a3b8"/>
          <ellipse cx="75" cy="35" rx="25" ry="12" fill="#94a3b8"/>
          <ellipse cx="320" cy="30" rx="35" ry="18" fill="#94a3b8"/>
          <ellipse cx="350" cy="25" rx="25" ry="12" fill="#94a3b8"/>
        </g>
      </svg>
    </div>

    <!-- Contenu texte -->
    <div class="erreur-404__contenu">
      <h1 class="erreur-404__titre">
        <span class="erreur-404__code">404</span>
        <span class="erreur-404__texte">Page introuvable</span>
      </h1>
      
      <p class="erreur-404__description">
        Oups ! On dirait que cette route ne mène nulle part.<br>
        La page que vous cherchez a peut-être été déplacée ou n'existe plus.
      </p>

      <!-- Suggestions de navigation -->
      <div class="erreur-404__suggestions">
        <p class="erreur-404__suggestions-titre">Voici quelques destinations populaires :</p>
        <ul class="erreur-404__liens-rapides">
          <li>
            <a href="<?= $prefixeUrlSafe ?>galerie" class="erreur-404__lien">
              <i class="fas fa-car" aria-hidden="true"></i>
              <span>Parcourir les véhicules</span>
            </a>
          </li>
          <li>
            <a href="<?= $prefixeUrlSafe ?>estimation" class="erreur-404__lien">
              <i class="fas fa-calculator" aria-hidden="true"></i>
              <span>Estimer un véhicule</span>
            </a>
          </li>
          <li>
            <a href="<?= $prefixeUrlSafe ?>contact" class="erreur-404__lien">
              <i class="fas fa-envelope" aria-hidden="true"></i>
              <span>Nous contacter</span>
            </a>
          </li>
        </ul>
      </div>

      <!-- Actions principales -->
      <div class="erreur-404__actions">
        <a class="bouton bouton--primaire" href="<?= $prefixeUrlSafe ?>accueil">
          <i class="fas fa-home" aria-hidden="true"></i>
          Retour à l'accueil
        </a>
        <button class="bouton bouton--fantome" onclick="history.back()" type="button">
          <i class="fas fa-arrow-left" aria-hidden="true"></i>
          Page précédente
        </button>
      </div>
    </div>

  </div>
</section>
