<!-- 
Page d'authentification - Gestion de la connexion, inscription et récupération de mot de passe
Cette page contient le conteneur principal où les composants MVC injecteront leur HTML
-->

<!-- Section principale de l'authentification -->
<section class="section">
  <div class="conteneur" style="max-width: 480px;">
    
    <!-- Vue Connexion -->
    <div id="vue-connexion" hidden>
        <form id="formulaire-connexion" class="formulaire" novalidate>
            <h2 class="texte-centre">Connexion</h2>
            <div class="grille" style="grid-template-columns: 1fr;">
                <div class="champ">
                    <label class="etiquette" for="connexion-email">Email</label>
                    <input id="connexion-email" name="email" class="saisie" type="email" autocomplete="email" required>
                </div>
                <div class="champ">
                    <label class="etiquette" for="connexion-password">Mot de passe</label>
                    <input id="connexion-password" name="password" class="saisie" type="password" autocomplete="current-password" required>
                    <div style="text-align: right; margin-top: 4px;">
                        <a href="#" id="lien-oubli" class="texte-petit">Mot de passe oublié ?</a>
                    </div>
                </div>
            </div>
            <div class="actions" style="margin-top: 20px;">
                <button class="bouton" type="submit" style="width: 100%;">Se connecter</button>
            </div>
            <div style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
                Nouveau compte ? <a href="#" id="lien-inscription">Inscris-toi ici</a>
            </div>
            <div class="messages-formulaire" aria-live="polite" role="status"></div>
        </form>
    </div>

    <!-- Vue Inscription -->
    <div id="vue-inscription" hidden>
        <form id="formulaire-inscription" class="formulaire" novalidate enctype="multipart/form-data">
            <h2 class="texte-centre">Inscription</h2>
            <div class="grille" style="grid-template-columns: 1fr;">
                <div class="champ">
                    <label class="etiquette" for="inscription-prenom">Prénom</label>
                    <input id="inscription-prenom" name="first_name" class="saisie" type="text" required maxlength="60">
                </div>
                <div class="champ">
                    <label class="etiquette" for="inscription-nom">Nom</label>
                    <input id="inscription-nom" name="last_name" class="saisie" type="text" required maxlength="60">
                </div>
                <div class="champ">
                    <label class="etiquette" for="inscription-email">Email</label>
                    <input id="inscription-email" name="email" class="saisie" type="email" required>
                </div>
                <div class="champ">
                    <label class="etiquette" for="inscription-telephone">Téléphone</label>
                    <input id="inscription-telephone" name="phone" class="saisie" type="tel" pattern="^[0-9 +().-]{6,}$" required>
                    <p class="aide">Ex: 06 12 34 56 78</p>
                </div>
                <div class="champ">
                    <label class="etiquette" for="inscription-avatar">Photo de profil</label>
                    <input id="inscription-avatar" name="avatar" class="saisie" type="file" accept="image/*">
                    <p class="aide">PNG/JPG, max 2 Mo</p>
                </div>
                <div class="champ">
                    <label class="etiquette" for="inscription-password">Mot de passe</label>
                    <input id="inscription-password" name="password" class="saisie" type="password" required>
                    <p class="aide">Min 8 caractères, avec majuscule, minuscule et chiffre</p>
                </div>
            </div>
            <div class="actions" style="margin-top: 20px;">
                <button class="bouton" type="submit" style="width: 100%;">Créer le compte</button>
            </div>
            <div style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
                Déjà un compte ? <a href="#" id="lien-connexion-inscription">Connecte-toi</a>
            </div>
            <div class="messages-formulaire" aria-live="polite" role="status"></div>
        </form>
    </div>

    <!-- Vue Mot de passe oublié -->
    <div id="vue-oubli" hidden>
        <form id="formulaire-oubli" class="formulaire" novalidate>
            <h2 class="texte-centre">Mot de passe oublié</h2>
            <p style="text-align: center; color: var(--texte-attenue); margin-bottom: 20px;">Entrez votre email pour recevoir un lien de réinitialisation.</p>
            <div class="grille" style="grid-template-columns: 1fr;">
                <div class="champ">
                    <label class="etiquette" for="oubli-email">Email</label>
                    <input id="oubli-email" name="email" class="saisie" type="email" required>
                </div>
            </div>
            <div class="actions" style="margin-top: 20px;">
                <button class="bouton" type="submit" style="width: 100%;">Envoyer le lien</button>
            </div>
            <div style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
                <a href="#" id="lien-connexion-oubli">Retour à la connexion</a>
            </div>
            <div class="messages-formulaire" aria-live="polite" role="status"></div>
        </form>
    </div>

    <!-- Vue Réinitialisation Mot de passe -->
    <div id="vue-reinitialisation" hidden>
        <form id="formulaire-reinitialisation" class="formulaire" novalidate>
            <h2 class="texte-centre">Réinitialisation</h2>
            <div class="grille">
                <div class="champ">
                    <label class="etiquette" for="reinitialisation-password">Nouveau mot de passe</label>
                    <input id="reinitialisation-password" name="password" class="saisie" type="password" required>
                </div>
            </div>
            <div class="actions">
                <button class="bouton" type="submit">Mettre à jour le mot de passe</button>
            </div>
            <div class="messages-formulaire" aria-live="polite" role="status"></div>
        </form>
    </div>

  </div>
</section>

<script type="module">
    import VueConnexion from './assets/js/Authentification/Connexion/VueConnexion.js';
    import VueInscription from './assets/js/Authentification/Inscription/VueInscription.js';
    import VueMotDePasseOublie from './assets/js/Authentification/MotDePasseOublie/VueMotDePasseOublie.js';

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
            oubli: new VueMotDePasseOublie()
        };

        // Initialiser toutes les vues (attacher les événements)
        Object.values(vues).forEach(vue => vue.initialiser());

        function naviguer(nomVue) {
            masquerTout();
            
            // Gestion spéciale pour la vue mot de passe oublié qui a deux modes
            if (nomVue === 'oubli' && new URLSearchParams(window.location.search).get('reset')) {
                document.getElementById('vue-reinitialisation').hidden = false;
                // vues.oubli gère à la fois la logique d'oubli et de réinitialisation
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
        } else {
            naviguer('connexion');
        }
    });
</script>
