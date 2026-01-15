<!-- Page de confirmation de changement d'email -->
<!-- Cette page s'affiche après qu'un utilisateur clique sur le lien de confirmation -->

<section class="section verification-section">
  <div class="conteneur">
    <div class="verification-conteneur">
      <div class="verification-carte">
        <?php if ($success): ?>
          <!-- Succès : Email changé -->
          <div class="verification-icone verification-icone--succes">
            <i class="fas fa-check"></i>
          </div>
          <h1>Email modifié !</h1>
          <p>Votre adresse email a été changée avec succès.</p>
          <?php if (!empty($newEmail)): ?>
            <p><strong>Nouvelle adresse :</strong> <?= htmlspecialchars($newEmail) ?></p>
          <?php endif; ?>
          
          <div class="verification-compte-rebours">
            Redirection automatique dans <strong id="countdown">5</strong> secondes...
          </div>
          
          <!-- Bouton de retour manuel -->
          <div class="verification-actions">
            <a href="<?= htmlspecialchars($prefixeUrl) ?>parametres" class="bouton bouton--lg">
              <i class="fas fa-cog"></i> Mes paramètres
            </a>
          </div>
          
        <?php else: ?>
          <!-- Erreur : Lien invalide ou expiré -->
          <div class="verification-icone verification-icone--erreur">
            <i class="fas fa-times"></i>
          </div>
          <h1>Lien invalide</h1>
          <p><?= htmlspecialchars($errorMessage) ?></p>
          
          <!-- Actions disponibles -->
          <div class="verification-actions">
            <a href="<?= htmlspecialchars($prefixeUrl) ?>accueil" class="bouton bouton--lg">
              <i class="fas fa-home"></i> Retour à l'accueil
            </a>
            <a href="<?= htmlspecialchars($prefixeUrl) ?>parametres" class="bouton bouton--fantome">
              <i class="fas fa-cog"></i> Mes paramètres
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php if ($success): ?>
<!-- Script de redirection automatique -->
<script type="module">
    import VueVerificationEmail from '<?= htmlspecialchars($prefixeUrl ?? '/', ENT_QUOTES, 'UTF-8') ?>assets/js/modules/auth/VueVerificationEmail.js?v=<?= time() ?>';
    new VueVerificationEmail('parametres');
</script>
<?php endif; ?>
