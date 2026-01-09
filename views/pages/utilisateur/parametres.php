<!-- 
Page des paramètres - Gestion du profil utilisateur
Design moderne avec sections organisées
-->

<?php
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Hero Section Paramètres -->
<section class="faq-hero">
    <div class="faq-hero__contenu">
        <div style="margin-bottom: 1rem;">
            <div class="parametres-hero__icon" style="width: 80px; height: 80px; background: rgba(0, 0, 0, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                <i class="fas fa-cog" style="font-size: 2.2rem; color: #1a1a1a;"></i>
            </div>
        </div>
        <h1 class="faq-hero__titre">Mes <span style="color: white;">Paramètres</span></h1>
        <p class="faq-hero__description">Gérez votre profil et vos préférences</p>
    </div>
    <div class="faq-hero__shapes">
        <div class="faq-shape faq-shape--1"></div>
        <div class="faq-shape faq-shape--2"></div>
    </div>
</section>

<!-- Contenu Principal -->
<section class="parametres-section">
    <div class="conteneur">
        
        <!-- Indicateur de chargement -->
        <div id="chargement-profil" class="parametres-loading">
            <div class="parametres-loading__spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Chargement de votre profil...</p>
        </div>

        <!-- Contenu si non connecté -->
        <div id="profil-connexion-requise" class="parametres-not-logged" hidden>
            <div class="parametres-not-logged__icon">
                <i class="fas fa-lock"></i>
            </div>
            <h2>Connexion requise</h2>
            <p>Vous devez être connecté pour accéder à vos paramètres.</p>
            <a class="parametres-btn parametres-btn--primary" href="connexion">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </a>
        </div>

        <!-- Contenu du profil -->
        <div id="contenu-profil" class="parametres-content" hidden>
            
            <div class="parametres-grid">
                
                <!-- Colonne gauche - Navigation -->
                <aside class="parametres-sidebar">
                    <nav class="parametres-nav">
                        <a href="#section-profil" class="parametres-nav__item active" data-section="profil">
                            <i class="fas fa-user"></i>
                            <span>Mon Profil</span>
                        </a>
                        <a href="#section-verification" class="parametres-nav__item" data-section="verification">
                            <i class="fas fa-shield-alt"></i>
                            <span>Vérifications</span>
                        </a>
                        <a href="#section-securite" class="parametres-nav__item" data-section="securite">
                            <i class="fas fa-key"></i>
                            <span>Sécurité</span>
                        </a>
                        <a href="#section-danger" class="parametres-nav__item parametres-nav__item--danger" data-section="danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Zone danger</span>
                        </a>
                    </nav>
                </aside>
                
                <!-- Colonne droite - Contenu -->
                <main class="parametres-main">
                    
                    <!-- Section Profil -->
                    <div id="section-profil" class="parametres-card">
                        <div class="parametres-card__header">
                            <div class="parametres-card__icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <div>
                                <h2 class="parametres-card__title">Informations personnelles</h2>
                                <p class="parametres-card__description">Mettez à jour vos informations de profil</p>
                            </div>
                        </div>
                        
                        <form id="formulaire-parametres" class="parametres-form" enctype="multipart/form-data">
                            <div class="parametres-form__avatar-section">
                                <div class="parametres-avatar">
                                    <div class="parametres-avatar__preview" id="avatar-preview">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <label class="parametres-avatar__upload" for="avatar">
                                        <i class="fas fa-camera"></i>
                                        <span>Changer</span>
                                        <input id="avatar" name="avatar" type="file" accept="image/*" hidden>
                                    </label>
                                </div>
                                <div class="parametres-avatar__info">
                                    <p>Photo de profil</p>
                                    <span>JPG, PNG. Max 2 Mo</span>
                                </div>
                            </div>
                            
                            <div class="parametres-form__row">
                                <div class="parametres-form__field">
                                    <label class="parametres-form__label" for="prenom">
                                        <i class="fas fa-user"></i> Prénom
                                    </label>
                                    <input id="prenom" name="first_name" class="parametres-form__input" type="text" required maxlength="60">
                                </div>
                                <div class="parametres-form__field">
                                    <label class="parametres-form__label" for="nom">
                                        <i class="fas fa-user"></i> Nom
                                    </label>
                                    <input id="nom" name="last_name" class="parametres-form__input" type="text" required maxlength="60">
                                </div>
                            </div>
                            
                            <div class="parametres-form__field">
                                <label class="parametres-form__label" for="telephone">
                                    <i class="fas fa-phone"></i> Téléphone
                                </label>
                                <input id="telephone" name="phone" class="parametres-form__input" type="tel" pattern="^[0-9 +().-]{6,}$" required placeholder="06 12 34 56 78">
                            </div>
                            
                            <div class="parametres-form__actions">
                                <button class="parametres-btn parametres-btn--primary" type="submit">
                                    <i class="fas fa-save"></i> Enregistrer les modifications
                                </button>
                            </div>
                            
                            <div class="messages-formulaire" aria-live="polite" role="status"></div>
                        </form>
                    </div>
                    
                    <!-- Section Vérifications -->
                    <div id="section-verification" class="parametres-card">
                        <div class="parametres-card__header">
                            <div class="parametres-card__icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <h2 class="parametres-card__title">Vérifications</h2>
                                <p class="parametres-card__description">Vérifiez votre email pour sécuriser votre compte</p>
                            </div>
                        </div>
                        
                        <div class="parametres-verifications">
                            <!-- Vérification Email -->
                            <div class="parametres-verification-item">
                                <div class="parametres-verification-item__icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="parametres-verification-item__content">
                                    <h3>Adresse email</h3>
                                    <p id="statut-email" class="parametres-verification-item__status">
                                        <i class="fas fa-circle"></i> Statut: inconnu
                                    </p>
                                </div>
                                <div class="parametres-verification-item__action">
                                    <button id="bouton-verifier-email" class="parametres-btn parametres-btn--outline" type="button">
                                        <i class="fas fa-paper-plane"></i> Vérifier
                                    </button>
                                </div>
                            </div>
                            <div id="message-verification-email" class="messages-formulaire" aria-live="polite" role="status"></div>
                        </div>
                    </div>
                    
                    <!-- Section Sécurité -->
                    <div id="section-securite" class="parametres-card">
                        <div class="parametres-card__header">
                            <div class="parametres-card__icon">
                                <i class="fas fa-key"></i>
                            </div>
                            <div>
                                <h2 class="parametres-card__title">Sécurité du compte</h2>
                                <p class="parametres-card__description">Gérez la sécurité de votre compte</p>
                            </div>
                        </div>
                        
                        <div class="parametres-security-info">
                            <div class="parametres-security-item">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <h4>Session active</h4>
                                    <p>Votre session expire après 20 minutes d'inactivité</p>
                                </div>
                            </div>
                            <div class="parametres-security-item">
                                <i class="fas fa-lock"></i>
                                <div>
                                    <h4>Mot de passe</h4>
                                    <p>Changez votre mot de passe pour sécuriser votre compte</p>
                                </div>
                                <div class="parametres-verification-item__action">
                                    <button type="button" id="bouton-changer-password" class="parametres-btn parametres-btn--outline">
                                        <i class="fas fa-key"></i> Changer
                                    </button>
                                </div>
                            </div>
                            <div id="message-reset-password" class="messages-formulaire" aria-live="polite" role="status"></div>
                        </div>
                    </div>
                    
                    <!-- Section Danger -->
                    <div id="section-danger" class="parametres-card parametres-card--danger">
                        <div class="parametres-card__header">
                            <div class="parametres-card__icon parametres-card__icon--danger">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <h2 class="parametres-card__title">Zone de danger</h2>
                                <p class="parametres-card__description">Actions irréversibles sur votre compte</p>
                            </div>
                        </div>
                        
                        <div class="parametres-danger-zone">
                            <div class="parametres-danger-item">
                                <div class="parametres-danger-item__content">
                                    <h3><i class="fas fa-trash-alt"></i> Supprimer mon compte</h3>
                                    <p>Cette action supprimera définitivement votre compte, vos annonces et toutes vos données. Cette action est irréversible.</p>
                                </div>
                                <button id="supprimer-compte" class="parametres-btn parametres-btn--danger" type="button">
                                    <i class="fas fa-trash-alt"></i> Supprimer mon compte
                                </button>
                            </div>
                        </div>
                    </div>
                    
                </main>
            </div>
        </div>
    </div>
</section>

<script type="module">
    import VueProfil from './assets/js/modules/auth/VueProfil.js';
    
    document.addEventListener('DOMContentLoaded', () => {
        new VueProfil();
    });
</script>
