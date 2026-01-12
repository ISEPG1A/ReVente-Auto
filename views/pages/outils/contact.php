<?php
// Page Contact – affiche les infos de l'entreprise et un formulaire d'envoi
// Session déjà démarrée par le routeur; génération d'un token CSRF spécifique contact.
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(16));
}
$jetonCsrf = $_SESSION['contact_csrf'];
?>

<!-- Hero Section Contact -->
<section class="faq-hero">
    <div class="faq-hero__contenu">
        <h1 class="faq-hero__titre">Contactez-<span style="color: white;">nous</span></h1>
        <p class="faq-hero__description">Une question, une suggestion ou besoin d'aide ? Notre équipe est là pour vous répondre</p>
    </div>
    <div class="faq-hero__shapes">
        <div class="faq-shape faq-shape--1"></div>
        <div class="faq-shape faq-shape--2"></div>
    </div>
</section>

<section class="section contact-section">
    <div class="conteneur">
        <div class="contact-grid">
            <!-- Colonne Informations -->
            <div class="contact-info">
                <h2 class="contact-info__titre">Informations</h2>
                <p class="contact-info__description">N'hésitez pas à nous contacter par le moyen qui vous convient le mieux.</p>
                
                <div class="contact-info__items">
                    <div class="contact-info__item">
                        <div class="contact-info__icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-info__content">
                            <h3>Adresse</h3>
                            <p>12 Avenue des Véhicules<br>75000 Paris, France</p>
                        </div>
                    </div>
                    
                    <div class="contact-info__item">
                        <div class="contact-info__icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-info__content">
                            <h3>Téléphone</h3>
                            <p><a href="tel:+33123456789">+33 1 23 45 67 89</a></p>
                        </div>
                    </div>
                    
                    <div class="contact-info__item">
                        <div class="contact-info__icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-info__content">
                            <h3>Email</h3>
                            <p><a href="mailto:contact@revente-auto.fr">contact@revente-auto.fr</a></p>
                        </div>
                    </div>
                    
                    <div class="contact-info__item">
                        <div class="contact-info__icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-info__content">
                            <h3>Horaires</h3>
                            <p>Lun - Ven : 9h00 - 18h00<br>Sam : 10h00 - 16h00</p>
                        </div>
                    </div>
                </div>
                
                <!-- Réseaux sociaux -->
                <div class="contact-social">
                    <h3>Suivez-nous</h3>
                    <div class="contact-social__links">
                        <a href="#" class="contact-social__link" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="contact-social__link" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="contact-social__link" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="contact-social__link" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Colonne Formulaire -->
            <div class="contact-form-wrapper">
                <h2 class="contact-form__titre">Envoyez-nous un message</h2>
                <form id="formulaire-contact" class="contact-form" novalidate>
                    <div class="contact-form__row">
                        <div class="contact-form__field">
                            <label class="contact-form__label" for="c-nom">Nom complet</label>
                            <input id="c-nom" name="nom" class="contact-form__input" type="text" required maxlength="40" autocomplete="name" placeholder="Votre nom">
                        </div>
                        <div class="contact-form__field">
                            <label class="contact-form__label" for="c-email">Email</label>
                            <input id="c-email" name="email" class="contact-form__input" type="email" required maxlength="60" autocomplete="email" placeholder="votre@email.com">
                        </div>
                    </div>
                    <div class="contact-form__field">
                        <label class="contact-form__label" for="c-sujet">Sujet</label>
                        <input id="c-sujet" name="sujet" class="contact-form__input" type="text" required maxlength="50" placeholder="Objet de votre message">
                    </div>
                    <div class="contact-form__field">
                        <label class="contact-form__label" for="c-message">Message</label>
                        <textarea id="c-message" name="message" class="contact-form__textarea" rows="6" required minlength="10" maxlength="1000" placeholder="Décrivez votre demande..." style="resize: vertical; max-height: 300px;"></textarea>
                        <div class="contact-form__char-count" style="display: flex; justify-content: flex-end; margin-top: 8px; font-size: 0.85rem; color: #9ca3af;">
                            <span><span id="char-count">0</span> / 1000 caractères</span>
                        </div>
                    </div>
                    <input type="text" name="site_web" id="site_web" hidden autocomplete="off" tabindex="-1">
                    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jetonCsrf) ?>">
                    
                    <div class="contact-form__actions">
                        <button class="bouton contact-form__submit" type="submit" id="bouton-envoi">
                            <i class="fas fa-paper-plane"></i> Envoyer le message
                        </button>
                    </div>
                    <div class="messages-formulaire" aria-live="polite" role="status"></div>
                </form>
            </div>
        </div>
    </div>
</section>

<script type="module">
  import { VueContact } from './assets/js/modules/outils/VueContact.js';

  const vue = new VueContact();
  vue.initialiser();
</script>