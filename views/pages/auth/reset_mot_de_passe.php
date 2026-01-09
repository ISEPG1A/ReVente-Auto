<!-- 
    PAGE - RÉINITIALISATION DU MOT DE PASSE
    Formulaire pour définir un nouveau mot de passe avec token
    Affiche aussi le résultat (succès ou erreur)
-->

<?php if (isset($showResult) && $showResult): ?>
    <!-- RÉSULTAT DE LA RÉINITIALISATION -->
    <section class="verification-section">
        <div class="verification-conteneur">
            <div class="verification-carte">
                <?php if ($success ?? false): ?>
                    <!-- Succès -->
                    <div class="verification-icone verification-icone--succes">
                        <i class="fas fa-check"></i>
                    </div>
                    
                    <h1>Mot de passe modifié !</h1>
                    
                    <p>Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</p>
                    
                    <div class="verification-actions">
                        <a href="<?= htmlspecialchars($prefixeUrl ?? '/', ENT_QUOTES, 'UTF-8') ?>connexion" class="bouton bouton--lg">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </a>
                    </div>
                    
                    <div class="verification-compte-rebours">
                        Redirection automatique dans <strong id="compte-rebours">5</strong> secondes...
                    </div>
                <?php else: ?>
                    <!-- Erreur -->
                    <div class="verification-icone verification-icone--erreur">
                        <i class="fas fa-times"></i>
                    </div>
                    
                    <h1>Erreur de réinitialisation</h1>
                    
                    <p><?= htmlspecialchars($errorMessage ?? 'Une erreur est survenue lors de la réinitialisation de votre mot de passe.', ENT_QUOTES, 'UTF-8') ?></p>
                    
                    <div class="verification-actions">
                        <a href="<?= htmlspecialchars($prefixeUrl ?? '/', ENT_QUOTES, 'UTF-8') ?>connexion" class="bouton bouton--secondaire">
                            <i class="fas fa-arrow-left"></i> Retour à la connexion
                        </a>
                        <a href="<?= htmlspecialchars($prefixeUrl ?? '/', ENT_QUOTES, 'UTF-8') ?>accueil" class="bouton">
                            <i class="fas fa-home"></i> Accueil
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($success ?? false): ?>
    <script type="module">
        // Compte à rebours pour la redirection automatique
        (function() {
            let secondesRestantes = 5;
            const elementCompteur = document.getElementById('compte-rebours');
            
            const interval = setInterval(() => {
                secondesRestantes--;
                if (elementCompteur) {
                    elementCompteur.textContent = secondesRestantes;
                }
                
                if (secondesRestantes <= 0) {
                    clearInterval(interval);
                    const urlBase = document.querySelector('base')?.href || '/';
                    window.location.href = urlBase + 'connexion';
                }
            }, 1000);
        })();
    </script>
    <?php endif; ?>

<?php else: ?>
    <!-- FORMULAIRE DE RÉINITIALISATION -->
    <section class="reset-section">
        <div class="reset-conteneur">
            <div class="reset-carte">
                <!-- Icône -->
                <div class="reset-icone">
                    <i class="fas fa-key"></i>
                </div>

                <!-- Titre -->
                <h1>Nouveau mot de passe</h1>
                
                <!-- Description -->
                <p>Veuillez choisir un nouveau mot de passe sécurisé pour votre compte.</p>

                <!-- Formulaire -->
                <form id="formulaire-reset-password" class="reset-form">
                <input type="hidden" name="token" value="<?= htmlspecialchars($tokenReset ?? '', ENT_QUOTES, 'UTF-8') ?>">
                
                <!-- Nouveau mot de passe -->
                <div class="reset-form__groupe">
                    <label class="reset-form__label" for="nouveau-password">
                        <i class="fas fa-lock"></i> Nouveau mot de passe
                    </label>
                    <div class="reset-form__password-wrapper">
                        <input 
                            id="nouveau-password" 
                            name="password" 
                            class="reset-form__input" 
                            type="password" 
                            placeholder="••••••••" 
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >
                        <button type="button" class="reset-form__toggle-password" aria-label="Afficher le mot de passe">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="reset-form__hint">Minimum 8 caractères, incluant majuscules, minuscules et chiffres</small>
                </div>

                <!-- Confirmation du mot de passe -->
                <div class="reset-form__groupe">
                    <label class="reset-form__label" for="confirmation-password">
                        <i class="fas fa-lock"></i> Confirmer le mot de passe
                    </label>
                    <div class="reset-form__password-wrapper">
                        <input 
                            id="confirmation-password" 
                            name="password_confirm" 
                            class="reset-form__input" 
                            type="password" 
                            placeholder="••••••••" 
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >
                        <button type="button" class="reset-form__toggle-password" aria-label="Afficher le mot de passe">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div class="reset-form__messages" id="messages-reset"></div>

                <!-- Bouton de soumission -->
                <button type="submit" class="bouton bouton--lg bouton--pleine-largeur">
                    <i class="fas fa-check"></i> Modifier mon mot de passe
                </button>
            </form>

            <!-- Lien retour connexion -->
            <div class="reset-retour">
                <a href="<?= htmlspecialchars($prefixeUrl ?? '/', ENT_QUOTES, 'UTF-8') ?>connexion" class="reset-lien">
                    <i class="fas fa-arrow-left"></i> Retour à la connexion
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Script pour gérer le formulaire -->
<script type="module">
    import VueResetMotDePasse from '<?= htmlspecialchars($prefixeUrl ?? '/', ENT_QUOTES, 'UTF-8') ?>assets/js/modules/auth/VueResetMotDePasse.js';
    new VueResetMotDePasse();
</script>
<?php endif; ?>
