<?php
// Page d'erreur 404 - Page non trouvée
// Cette page s'affiche quand l'utilisateur accède à une URL qui n'existe pas

// Récupération et normalisation du nom du script actuel
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Calcul du préfixe d'URL pour les liens
// Si le script est dans le dossier /public/, on remonte d'un niveau
// Sinon, on utilise la racine
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>
<!-- Section principale de la page d'erreur 404 -->
<section class="section">
  <div class="conteneur">
    <h2>Page introuvable (404)</h2>
    <p>La page que vous cherchez n'existe pas.</p>
    
    <!-- Actions disponibles pour l'utilisateur -->
    <p>
      <!-- Bouton principal pour retourner à l'accueil -->
      <a class="bouton" href="<?= $prefixeUrl ?>accueil">Retour à l'accueil</a>
      
      <!-- Bouton secondaire pour voir la galerie -->
      <a class="bouton bouton--fantome" href="<?= $prefixeUrl ?>galerie">Voir la galerie</a>
    </p>
  </div>
</section>
