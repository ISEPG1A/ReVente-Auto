<?php
/**
 * Page d'accueil - Landing page de l'application ReVente-Auto
 * 
 * Cette page présente l'application et guide les utilisateurs vers les fonctionnalités principales.
 * Elle sert de point d'entrée visuel pour les visiteurs.
 */

// Récupération et normalisation du nom du script actuel pour gérer les chemins
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Calcul du préfixe d'URL pour la navigation
// Gestion du cas où l'application est dans un sous-dossier /public/
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Section principale de la page d'accueil -->
<section class="section section--liste">
  <div class="conteneur">
    <!-- Message de bienvenue avec emoji -->
    <h2>Bienvenue 👋</h2>
    
    <!-- Description courte de l'application -->
    <p>Application de gestion et vente de véhicules d'occasion.</p>
    
    <!-- Liste des fonctionnalités principales -->
    <ul class="liste">
      <li>Consultez la <strong>galerie</strong> pour voir les véhicules disponibles</li>
      <li>Créez un compte pour ajouter vos propres annonces</li>
    </ul>
    
    <!-- Actions principales proposées à l'utilisateur -->
    <div class="actions" style="margin-top:16px">
      <!-- Bouton principal vers la galerie -->
      <a class="bouton" href="<?= $prefixeUrl ?>galerie">Voir la galerie</a>
      
      <!-- Bouton secondaire vers la page à propos -->
      <a class="bouton bouton--fantome" href="<?= $prefixeUrl ?>apropos">À propos</a>
    </div>
  </div>
</section>
