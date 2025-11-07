<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
if (strpos($scriptName, '/public/') !== false) {
    $prefix = substr($scriptName, 0, strpos($scriptName, '/public/')) . '/';
} else {
    $prefix = '/';
}
?>
<section class="section">
  <div class="container">
    <h2>Paramètres utilisateur</h2>
    <?php if (empty($_SESSION['user'])): ?>
      <p>Vous devez être connecté pour accéder à cette page.</p>
      <p><a class="button" href="<?= $prefix ?>connexion">Aller à la connexion</a></p>
    <?php else: ?>
      <form id="settings-form" class="form" enctype="multipart/form-data">
        <div class="grid">
          <div class="field">
            <label class="label" for="set-first">Prénom</label>
            <input id="set-first" name="first_name" class="input" type="text" required maxlength="60" />
          </div>
          <div class="field">
            <label class="label" for="set-last">Nom</label>
            <input id="set-last" name="last_name" class="input" type="text" required maxlength="60" />
          </div>
          <div class="field">
            <label class="label" for="set-phone">Téléphone</label>
            <input id="set-phone" name="phone" class="input" type="tel" pattern="^[0-9 +().-]{6,}$" required />
          </div>
          <div class="field">
            <label class="label" for="set-avatar">Photo de profil</label>
            <input id="set-avatar" name="avatar" class="input" type="file" accept="image/*" />
          </div>
        </div>
        <div class="actions">
          <button class="button" type="submit">Enregistrer</button>
        </div>
        <div class="form__messages" aria-live="polite" role="status"></div>
      </form>

      <div style="height:12px"></div>

      <div class="form">
        <h3 style="margin-top:0">Vérifications</h3>
        <div class="grid">
          <div>
            <p id="email-status" class="help">Statut email: inconnu</p>
            <button id="btn-email-verify" class="button" type="button">Envoyer le lien de vérification</button>
            <div id="email-verify-msg" class="form__messages" aria-live="polite" role="status"></div>
          </div>
          <div>
            <p id="phone-status" class="help">Statut téléphone: inconnu</p>
            <div class="actions">
              <button id="btn-phone-code" class="button" type="button">Recevoir le code</button>
              <input id="phone-code" class="input" type="text" placeholder="Code reçu" style="max-width:160px" />
              <button id="btn-phone-verify" class="button button--ghost" type="button">Vérifier</button>
            </div>
            <div id="phone-verify-msg" class="form__messages" aria-live="polite" role="status"></div>
          </div>
        </div>
      </div>

      <hr style="border-color: var(--border); margin: 24px 0;" />

      <div>
        <h3>Supprimer mon compte</h3>
        <p>Cette action est définitive.</p>
  <button id="delete-account" class="button button--danger" type="button">Supprimer le compte</button>
      </div>
    <?php endif; ?>
  </div>
</section>
