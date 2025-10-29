<section class="section">
  <div class="container">
    <h2>Connexion / Inscription</h2>

    <div id="auth-tabs" class="actions" style="margin: 12px 0 20px;">
      <button id="tab-login" class="button" type="button">Connexion</button>
      <button id="tab-register" class="button button--ghost" type="button">Inscription</button>
      <button id="tab-forgot" class="button button--ghost" type="button">Mot de passe oublié</button>
    </div>

    <div id="auth-forms">
      <!-- Login form -->
      <form id="form-login" class="form" novalidate hidden>
        <div class="grid">
          <div class="field">
            <label class="label" for="login-email">Email</label>
            <input id="login-email" name="email" class="input" type="email" autocomplete="email" required>
          </div>
          <div class="field">
            <label class="label" for="login-password">Mot de passe</label>
            <input id="login-password" name="password" class="input" type="password" autocomplete="current-password" required>
          </div>
        </div>
        <div class="actions">
          <button class="button" type="submit">Se connecter</button>
        </div>
        <div class="form__messages" aria-live="polite" role="status"></div>
      </form>

      <!-- Register form -->
      <form id="form-register" class="form" novalidate hidden enctype="multipart/form-data">
        <div class="grid">
          <div class="field">
            <label class="label" for="reg-first">Prénom</label>
            <input id="reg-first" name="first_name" class="input" type="text" required maxlength="60">
          </div>
          <div class="field">
            <label class="label" for="reg-last">Nom</label>
            <input id="reg-last" name="last_name" class="input" type="text" required maxlength="60">
          </div>
          <div class="field">
            <label class="label" for="reg-email">Email</label>
            <input id="reg-email" name="email" class="input" type="email" required>
          </div>
          <div class="field">
            <label class="label" for="reg-phone">Téléphone</label>
            <input id="reg-phone" name="phone" class="input" type="tel" pattern="^[0-9 +().-]{6,}$" required>
            <p class="help">Ex: 06 12 34 56 78</p>
          </div>
          <div class="field">
            <label class="label" for="reg-avatar">Photo de profil</label>
            <input id="reg-avatar" name="avatar" class="input" type="file" accept="image/*">
            <p class="help">PNG/JPG, max 2 Mo</p>
          </div>
          <div class="field">
            <label class="label" for="reg-password">Mot de passe</label>
            <input id="reg-password" name="password" class="input" type="password" required>
            <p class="help">Min 8 caractères, avec majuscule, minuscule et chiffre</p>
          </div>
        </div>
        <div class="actions">
          <button class="button" type="submit">Créer le compte</button>
        </div>
        <div class="form__messages" aria-live="polite" role="status"></div>
      </form>

      <!-- Forgot form -->
      <form id="form-forgot" class="form" novalidate hidden>
        <div class="grid">
          <div class="field">
            <label class="label" for="forgot-email">Email</label>
            <input id="forgot-email" name="email" class="input" type="email" required>
          </div>
        </div>
        <div class="actions">
          <button class="button" type="submit">Envoyer le lien de réinitialisation</button>
        </div>
        <div class="form__messages" aria-live="polite" role="status"></div>
      </form>

      <!-- Reset form (via token) -->
      <form id="form-reset" class="form" novalidate hidden>
        <div class="grid">
          <div class="field">
            <label class="label" for="reset-password">Nouveau mot de passe</label>
            <input id="reset-password" name="password" class="input" type="password" required>
          </div>
        </div>
        <div class="actions">
          <button class="button" type="submit">Mettre à jour le mot de passe</button>
        </div>
        <div class="form__messages" aria-live="polite" role="status"></div>
      </form>
    </div>
  </div>
</section>
