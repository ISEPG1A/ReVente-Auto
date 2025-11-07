<?php 
// Page des paramètres utilisateur - Gestion du profil et des préférences
// Cette page permet à l'utilisateur de modifier ses informations personnelles
// et de gérer les vérifications de son compte

// Démarrage de la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) session_start();

// Récupération et normalisation du nom du script actuel
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Calcul du préfixe d'URL pour la navigation
// Gestion du cas où l'application est dans un sous-dossier /public/
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Section principale des paramètres -->
<section class="section">
  <div class="container">
    <h2>Paramètres</h2>
    
    <?php if (empty($_SESSION['user'])): ?>
      <!-- Message affiché si l'utilisateur n'est pas connecté -->
      <p>Vous devez être connecté.</p>
      <p>
        <a class="button" href="<?= $prefixeUrl ?>connexion">Se connecter</a>
      </p>
      
    <?php else: ?>
      <!-- Formulaire de modification du profil utilisateur -->
      <form id="settings-form" class="form" enctype="multipart/form-data">
        <div class="grid">
          <!-- Champ prénom -->
          <div class="field">
            <label class="label" for="set-first">Prénom</label>
            <input id="set-first" name="first_name" class="input" type="text" 
                   required maxlength="60">
          </div>
          
          <!-- Champ nom de famille -->
          <div class="field">
            <label class="label" for="set-last">Nom</label>
            <input id="set-last" name="last_name" class="input" type="text" 
                   required maxlength="60">
          </div>
          
          <!-- Champ téléphone avec validation -->
          <div class="field">
            <label class="label" for="set-phone">Téléphone</label>
            <input id="set-phone" name="phone" class="input" type="tel" 
                   pattern="^[0-9 +().-]{6,}$" required>
          </div>
          
          <!-- Champ upload de nouvelle photo de profil -->
          <div class="field">
            <label class="label" for="set-avatar">Photo de profil</label>
            <input id="set-avatar" name="avatar" class="input" type="file" accept="image/*">
          </div>
        </div>
        
        <!-- Actions du formulaire -->
        <div class="actions">
          <button class="button" type="submit">Enregistrer</button>
        </div>
        
        <!-- Zone d'affichage des messages -->
        <div class="form__messages" aria-live="polite" role="status"></div>
      </form>

      <!-- Séparateur visuel -->
      <!-- Séparateur visuel -->
      <hr style="border-color: var(--border); margin: 24px 0;">

      <!-- Section des vérifications de compte -->
      <div class="form">
        <h3>Vérifications</h3>
        <div class="grid">
          <!-- Colonne de vérification de l'email -->
          <div>
            <!-- Statut de vérification de l'email (mis à jour par JavaScript) -->
            <p id="email-status" class="help">Statut email: inconnu</p>
            
            <!-- Bouton pour demander la vérification d'email -->
            <button id="btn-email-verify" class="button" type="button">Vérifier email</button>
            
            <!-- Zone de messages pour la vérification d'email -->
            <div id="email-verify-msg" class="form__messages" aria-live="polite" role="status"></div>
          </div>
          
          <!-- Colonne de vérification du téléphone -->
          <div>
            <!-- Statut de vérification du téléphone (mis à jour par JavaScript) -->
            <p id="phone-status" class="help">Statut téléphone: inconnu</p>
            
            <!-- Actions pour la vérification du téléphone -->
            <div class="actions">
              <!-- Bouton pour recevoir un code de vérification -->
              <button id="btn-phone-code" class="button" type="button">Recevoir code</button>
              
              <!-- Champ pour saisir le code reçu -->
              <input id="phone-code" class="input" type="text" placeholder="Code" 
                     style="max-width:160px">
              
              <!-- Bouton pour valider le code saisi -->
              <button id="btn-phone-verify" class="button button--ghost" type="button">Vérifier</button>
            </div>
            
            <!-- Zone de messages pour la vérification de téléphone -->
            <div id="phone-verify-msg" class="form__messages" aria-live="polite" role="status"></div>
          </div>
        </div>
      </div>

      <!-- Séparateur visuel -->
      <hr style="border-color: var(--border); margin: 24px 0;">

      <!-- Section de suppression de compte -->
      <div>
        <h3>Supprimer mon compte</h3>
        <p>Cette action est définitive.</p>
        
        <!-- Bouton de suppression de compte (action dangereuse) -->
        <button id="delete-account" class="button button--danger" type="button">Supprimer</button>
      </div>
    <?php endif; ?>
  </div>
</section>
