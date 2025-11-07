<?php
// Page d'accueil - Landing page de l'application ReVente-Auto
// Cette page présente l'application et guide les utilisateurs vers les fonctionnalités principales

// Récupération et normalisation du nom du script actuel
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Calcul du préfixe d'URL pour la navigation
// Gestion du cas où l'application est dans un sous-dossier /public/
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Section principale de la page d'accueil -->
<section class="section section--list">
  <div class="container">
    <!-- Message de bienvenue avec emoji -->
    <h2>Bienvenue 👋</h2>
    
    <!-- Description courte de l'application -->
    <p>Application de gestion et vente de véhicules d'occasion.</p>
    
    <!-- Liste des fonctionnalités principales -->
    <ul class="list">
      <li>Consultez la <strong>galerie</strong> pour voir les véhicules disponibles</li>
      <li>Créez un compte pour ajouter vos propres annonces</li>
    </ul>
    
    <!-- Actions principales proposées à l'utilisateur -->
    <div class="actions" style="margin-top:16px">
      <!-- Bouton principal vers la galerie -->
      <a class="button" href="<?= $prefixeUrl ?>galerie">Voir la galerie</a>
      
      <!-- Bouton secondaire vers la page à propos -->
      <a class="button button--ghost" href="<?= $prefixeUrl ?>apropos">À propos</a>
    </div>
  </div>
</section>
