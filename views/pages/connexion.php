<!-- 
Page d'authentification - Gestion de la connexion, inscription et récupération de mot de passe
Design moderne avec split-screen et glassmorphism
-->

<?php
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Container principal authentification -->
<div class="auth-container">
    
    <!-- Panneau gauche - Branding -->
    <div class="auth-branding">
        <div class="auth-branding__content">
            <h1 class="auth-branding__titre">Bienvenue sur <span>ReVente-Auto</span></h1>
            <p class="auth-branding__description">La plateforme de confiance pour acheter et vendre des véhicules d'occasion.</p>
            
            <div class="auth-branding__features">
                <div class="auth-feature">
                    <div class="auth-feature__icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="auth-feature__text">
                        <h3>Transactions sécurisées</h3>
                        <p>Vos données sont protégées</p>
                    </div>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature__icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="auth-feature__text">
                        <h3>+500 véhicules</h3>
                        <p>Un large choix disponible</p>
                    </div>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature__icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="auth-feature__text">
                        <h3>Messagerie intégrée</h3>
                        <p>Communiquez facilement</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Formes décoratives -->
        <div class="auth-branding__shapes">
            <div class="auth-shape auth-shape--1"></div>
            <div class="auth-shape auth-shape--2"></div>
            <div class="auth-shape auth-shape--3"></div>
        </div>
    </div>
    
    <!-- Panneau droit - Formulaires -->
    <div class="auth-forms">
        <div class="auth-forms__wrapper">
            
            <!-- Vue Connexion -->
            <div id="vue-connexion" class="auth-view" hidden>
                <div class="auth-header">
                    <h2 class="auth-header__titre">Connexion</h2>
                    <p class="auth-header__description">Ravi de vous revoir ! Connectez-vous à votre compte.</p>
                </div>
                
                <form id="formulaire-connexion" class="auth-form" novalidate>
                    <div class="auth-form__field">
                        <label class="auth-form__label" for="connexion-email">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input id="connexion-email" name="email" class="auth-form__input" type="email" placeholder="votre@email.com" autocomplete="email" required>
                    </div>
                    
                    <div class="auth-form__field">
                        <label class="auth-form__label" for="connexion-password">
                            <i class="fas fa-lock"></i> Mot de passe
                        </label>
                        <div class="auth-form__password-wrapper">
                            <input id="connexion-password" name="password" class="auth-form__input" type="password" placeholder="••••••••" autocomplete="current-password" required>
                            <button type="button" class="auth-form__toggle-password" aria-label="Afficher le mot de passe">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                        <div class="auth-form__forgot">
                            <a href="#" id="lien-oubli">Mot de passe oublié ?</a>
                        </div>
                    </div>
                    
                    <button class="auth-form__submit" type="submit">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </button>
                    
                    <div class="messages-formulaire" aria-live="polite" role="status"></div>
                </form>
                
                <div class="auth-footer">
                    <p>Pas encore de compte ? <a href="#" id="lien-inscription">Créer un compte</a></p>
                </div>
            </div>

            <!-- Vue Inscription -->
            <div id="vue-inscription" class="auth-view" hidden>
                <div class="auth-header">
                    <h2 class="auth-header__titre">Créer un compte</h2>
                    <p class="auth-header__description">Rejoignez notre communauté en quelques clics.</p>
                </div>
                
                <form id="formulaire-inscription" class="auth-form" novalidate enctype="multipart/form-data">
                    <div class="auth-form__row">
                        <div class="auth-form__field">
                            <label class="auth-form__label" for="inscription-prenom">
                                <i class="fas fa-user"></i> Prénom
                            </label>
                            <input id="inscription-prenom" name="first_name" class="auth-form__input" type="text" placeholder="Jean" required maxlength="60">
                        </div>
                        <div class="auth-form__field">
                            <label class="auth-form__label" for="inscription-nom">
                                <i class="fas fa-user"></i> Nom
                            </label>
                            <input id="inscription-nom" name="last_name" class="auth-form__input" type="text" placeholder="Dupont" required maxlength="60">
                        </div>
                    </div>
                    
                    <div class="auth-form__field">
                        <label class="auth-form__label" for="inscription-email">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input id="inscription-email" name="email" class="auth-form__input" type="email" placeholder="votre@email.com" required>
                    </div>
                    
                    <div class="auth-form__field">
                        <label class="auth-form__label" for="inscription-telephone">
                            <i class="fas fa-phone"></i> Téléphone
                        </label>
                        <input id="inscription-telephone" name="phone" class="auth-form__input" type="tel" placeholder="06 12 34 56 78" pattern="^[0-9 +().-]{6,}$" required>
                    </div>
                    
                    <div class="auth-form__field">
                        <label class="auth-form__label" for="inscription-avatar">
                            <i class="fas fa-camera"></i> Photo de profil <span class="auth-form__optional">(optionnel)</span>
                        </label>
                        <div class="auth-form__file-wrapper">
                            <input id="inscription-avatar" name="avatar" class="auth-form__file" type="file" accept="image/*">
                            <div class="auth-form__file-display">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Choisir une image</span>
                            </div>
                        </div>
                        <p class="auth-form__hint">PNG/JPG, max 2 Mo</p>
                    </div>
                    
                    <div class="auth-form__field">
                        <label class="auth-form__label" for="inscription-password">
                            <i class="fas fa-lock"></i> Mot de passe
                        </label>
                        <div class="auth-form__password-wrapper">
                            <input id="inscription-password" name="password" class="auth-form__input" type="password" placeholder="••••••••" required>
                            <button type="button" class="auth-form__toggle-password" aria-label="Afficher le mot de passe">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                        <p class="auth-form__hint">Min 8 caractères, majuscule, minuscule et chiffre</p>
                    </div>
                    
                    <button class="auth-form__submit" type="submit">
                        <i class="fas fa-user-plus"></i> Créer mon compte
                    </button>
                    
                    <div class="messages-formulaire" aria-live="polite" role="status"></div>
                </form>
                
                <div class="auth-footer">
                    <p>Déjà un compte ? <a href="#" id="lien-connexion-inscription">Se connecter</a></p>
                </div>
            </div>

            <!-- Vue Mot de passe oublié -->
            <div id="vue-oubli" class="auth-view" hidden>
                <div class="auth-header">
                    <div class="auth-header__icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <h2 class="auth-header__titre">Mot de passe oublié ?</h2>
                    <p class="auth-header__description">Pas de panique ! Entrez votre email et nous vous enverrons un lien de réinitialisation.</p>
                </div>
                
                <form id="formulaire-oubli" class="auth-form" novalidate>
                    <div class="auth-form__field">
                        <label class="auth-form__label" for="oubli-email">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input id="oubli-email" name="email" class="auth-form__input" type="email" placeholder="votre@email.com" required>
                    </div>
                    
                    <button class="auth-form__submit" type="submit">
                        <i class="fas fa-paper-plane"></i> Envoyer le lien
                    </button>
                    
                    <div class="messages-formulaire" aria-live="polite" role="status"></div>
                </form>
                
                <div class="auth-footer">
                    <p><a href="#" id="lien-connexion-oubli"><i class="fas fa-arrow-left"></i> Retour à la connexion</a></p>
                </div>
            </div>

            <!-- Vue Réinitialisation Mot de passe -->
            <div id="vue-reinitialisation" class="auth-view" hidden>
                <div class="auth-header">
                    <div class="auth-header__icon auth-header__icon--success">
                        <i class="fas fa-unlock-alt"></i>
                    </div>
                    <h2 class="auth-header__titre">Nouveau mot de passe</h2>
                    <p class="auth-header__description">Choisissez un nouveau mot de passe sécurisé.</p>
                </div>
                
                <form id="formulaire-reinitialisation" class="auth-form" novalidate>
                    <div class="auth-form__field">
                        <label class="auth-form__label" for="reinitialisation-password">
                            <i class="fas fa-lock"></i> Nouveau mot de passe
                        </label>
                        <div class="auth-form__password-wrapper">
                            <input id="reinitialisation-password" name="password" class="auth-form__input" type="password" placeholder="••••••••" required>
                            <button type="button" class="auth-form__toggle-password" aria-label="Afficher le mot de passe">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                        <p class="auth-form__hint">Min 8 caractères, majuscule, minuscule et chiffre</p>
                    </div>
                    
                    <button class="auth-form__submit" type="submit">
                        <i class="fas fa-check"></i> Mettre à jour
                    </button>
                    
                    <div class="messages-formulaire" aria-live="polite" role="status"></div>
                </form>
            </div>

        </div>
    </div>
</div>

<script type="module">
    import VueConnexion from './assets/js/Authentification/Connexion/VueConnexion.js';
    import VueInscription from './assets/js/Authentification/Inscription/VueInscription.js';
    import VueResetMotDePasse from './assets/js/Authentification/MotDePasseOublie/VueResetMotDePasse.js';

    document.addEventListener('DOMContentLoaded', () => {
        // Fonction pour masquer toutes les vues
        const masquerTout = () => {
            ['vue-connexion', 'vue-inscription', 'vue-oubli', 'vue-reinitialisation'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.hidden = true;
            });
        };

        const vues = {
            connexion: new VueConnexion(),
            inscription: new VueInscription(),
            oubli: new VueResetMotDePasse()
        };

        // Initialiser toutes les vues (attacher les événements)
        Object.values(vues).forEach(vue => vue.initialiser());

        function naviguer(nomVue) {
            masquerTout();
            
            // Gestion spéciale pour la vue mot de passe oublié qui a deux modes
            if (nomVue === 'oubli' && new URLSearchParams(window.location.search).get('reset')) {
                document.getElementById('vue-reinitialisation').hidden = false;
            } else if (nomVue === 'oubli') {
                document.getElementById('vue-oubli').hidden = false;
            } else if (nomVue === 'connexion') {
                document.getElementById('vue-connexion').hidden = false;
            } else if (nomVue === 'inscription') {
                document.getElementById('vue-inscription').hidden = false;
            }
        }

        // Écouter les événements de navigation des composants
        document.addEventListener('navigate', (e) => {
            naviguer(e.detail);
        });

        // Routage initial
        const parametresUrl = new URLSearchParams(window.location.search);
        if (parametresUrl.get('reset')) {
            naviguer('oubli');
        } else if (parametresUrl.get('mode') === 'inscription') {
            naviguer('inscription');
        } else {
            naviguer('connexion');
        }
    });
</script>
