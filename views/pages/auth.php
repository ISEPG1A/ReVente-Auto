<!-- 
Page d'authentification - Gestion de la connexion, inscription et récupération de mot de passe
Cette page contient tous les formulaires liés à l'authentification des utilisateurs
-->

<!-- Section principale de l'authentification -->
<section class="section">
  <div class="container" style="max-width: 480px;">
    
    <!-- Conteneur des différents formulaires d'authentification -->
    <div id="auth-forms">
      
      <!-- Formulaire de connexion -->
      <form id="form-login" class="form" novalidate>
        <h2 class="text-center">Connexion</h2>
        <div class="grid" style="grid-template-columns: 1fr;">
          <!-- Champ email pour la connexion -->
          <div class="field">
            <label class="label" for="login-email">Email</label>
            <input id="login-email" name="email" class="input" type="email" 
                   autocomplete="email" required>
          </div>
          
          <!-- Champ mot de passe pour la connexion -->
          <div class="field">
            <label class="label" for="login-password">Mot de passe</label>
            <input id="login-password" name="password" class="input" type="password" 
                   autocomplete="current-password" required>
            <div style="text-align: right; margin-top: 4px;">
              <a href="#" id="link-forgot" class="text-small">Mot de passe oublié ?</a>
            </div>
          </div>
        </div>
        
        <!-- Actions du formulaire de connexion -->
        <div class="actions" style="margin-top: 20px;">
          <button class="button" type="submit" style="width: 100%;">Se connecter</button>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
          Nouveau compte ? <a href="#" id="link-register">Inscris-toi ici</a>
        </div>
        
        <!-- Zone d'affichage des messages (erreurs/succès) -->
        <div class="form__messages" aria-live="polite" role="status"></div>
      </form>
      
      <!-- Formulaire d'inscription -->
      <form id="form-register" class="form" novalidate hidden enctype="multipart/form-data">
        <h2 class="text-center">Inscription</h2>
        <div class="grid" style="grid-template-columns: 1fr;">
          <!-- Champ prénom pour l'inscription -->
          <div class="field">
            <label class="label" for="reg-first">Prénom</label>
            <input id="reg-first" name="first_name" class="input" type="text" 
                   required maxlength="60">
          </div>
          
          <!-- Champ nom pour l'inscription -->
          <div class="field">
            <label class="label" for="reg-last">Nom</label>
            <input id="reg-last" name="last_name" class="input" type="text" 
                   required maxlength="60">
          </div>
          
          <!-- Champ email pour l'inscription -->
          <div class="field">
            <label class="label" for="reg-email">Email</label>
            <input id="reg-email" name="email" class="input" type="email" required>
          </div>
          
          <!-- Champ téléphone avec pattern de validation -->
          <div class="field">
            <label class="label" for="reg-phone">Téléphone</label>
            <input id="reg-phone" name="phone" class="input" type="tel" 
                   pattern="^[0-9 +().-]{6,}$" required>
            <p class="help">Ex: 06 12 34 56 78</p>
          </div>
          
          <!-- Champ upload de photo de profil (optionnel) -->
          <div class="field">
            <label class="label" for="reg-avatar">Photo de profil</label>
            <input id="reg-avatar" name="avatar" class="input" type="file" accept="image/*">
            <p class="help">PNG/JPG, max 2 Mo</p>
          </div>
          
          <!-- Champ mot de passe avec critères de sécurité -->
          <div class="field">
            <label class="label" for="reg-password">Mot de passe</label>
            <input id="reg-password" name="password" class="input" type="password" required>
            <p class="help">Min 8 caractères, avec majuscule, minuscule et chiffre</p>
          </div>
        </div>
        
        <!-- Actions du formulaire d'inscription -->
        <div class="actions" style="margin-top: 20px;">
          <button class="button" type="submit" style="width: 100%;">Créer le compte</button>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
          Déjà un compte ? <a href="#" id="link-login-register">Connecte-toi</a>
        </div>
        
        <!-- Zone d'affichage des messages -->
        <div class="form__messages" aria-live="polite" role="status"></div>
      </form>
      
      <!-- Formulaire de récupération de mot de passe oublié -->
      <form id="form-forgot" class="form" novalidate hidden>
        <h2 class="text-center">Mot de passe oublié</h2>
        <p style="text-align: center; color: var(--texte-attenue); margin-bottom: 20px;">Entrez votre email pour recevoir un lien de réinitialisation.</p>
        <div class="grid" style="grid-template-columns: 1fr;">
          <!-- Champ email pour la récupération -->
          <div class="field">
            <label class="label" for="forgot-email">Email</label>
            <input id="forgot-email" name="email" class="input" type="email" required>
          </div>
        </div>
        
        <!-- Actions du formulaire de récupération -->
        <div class="actions" style="margin-top: 20px;">
          <button class="button" type="submit" style="width: 100%;">Envoyer le lien</button>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
          <a href="#" id="link-login-forgot">Retour à la connexion</a>
        </div>
        
        <!-- Zone d'affichage des messages -->
        <div class="form__messages" aria-live="polite" role="status"></div>
      </form>

      
      <!-- Formulaire de réinitialisation de mot de passe (accessible via lien email) -->
      <form id="form-reset" class="form" novalidate hidden>
        <div class="grid">
          <!-- Champ nouveau mot de passe -->
          <div class="field">
            <label class="label" for="reset-password">Nouveau mot de passe</label>
            <input id="reset-password" name="password" class="input" type="password" required>
          </div>
        </div>
        
        <!-- Actions du formulaire de réinitialisation -->
        <div class="actions">
          <button class="button" type="submit">Mettre à jour le mot de passe</button>
        </div>
        
        <!-- Zone d'affichage des messages -->
        <div class="form__messages" aria-live="polite" role="status"></div>
      </form>
    </div>
  </div>
</section>
