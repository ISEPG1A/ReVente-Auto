<?php
// Page Contact – affiche les infos de l'entreprise et un formulaire d'envoi
// Session déjà démarrée par le routeur; génération d'un token CSRF spécifique contact.
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(16));
}
$jetonCsrf = $_SESSION['contact_csrf'];
?>

<section class="section">
  <div class="conteneur">
    <h2>Contact</h2>
    <p>Une question sur une annonce, un partenariat ou un support technique ? Écrivez‑nous.</p>

    <h3 style="margin-top:32px">Formulaire de contact</h3>
    <form id="formulaire-contact" class="formulaire" novalidate>
        <div class="grille">
            <div class="champ">
                <label class="etiquette" for="c-nom">Nom</label>
                <input id="c-nom" name="nom" class="saisie" type="text" required maxlength="100" autocomplete="name">
            </div>
            <div class="champ">
                <label class="etiquette" for="c-email">Email</label>
                <input id="c-email" name="email" class="saisie" type="email" required autocomplete="email">
            </div>
            <div class="champ" style="grid-column: span 2;">
                <label class="etiquette" for="c-sujet">Sujet</label>
                <input id="c-sujet" name="sujet" class="saisie" type="text" required maxlength="150">
            </div>
            <div class="champ" style="grid-column: span 2;">
                <label class="etiquette" for="c-message">Message</label>
                <textarea id="c-message" name="message" class="saisie" rows="6" required minlength="10" maxlength="5000" style="resize:vertical"></textarea>
                <p class="aide">Expliquez votre demande (minimum 10 caractères).</p>
            </div>
            <input type="text" name="site_web" id="site_web" hidden autocomplete="off" tabindex="-1">
            <input type="hidden" name="jeton" value="<?= htmlspecialchars($jetonCsrf) ?>">
        </div>
        <div class="actions">
            <button class="bouton" type="submit" id="bouton-envoi">Envoyer</button>
            <button class="bouton bouton--fantome" type="reset">Réinitialiser</button>
        </div>
        <div class="messages-formulaire" aria-live="polite" role="status"></div>
    </form>

    <div class="grille" style="margin-top:24px">
      <div class="champ" style="grid-column: span 2;">
        <h3>Coordonnées</h3>
        <ul class="liste">
          <li><strong>Téléphone :</strong> <a href="tel:+33123456789">+33 1 23 45 67 89</a></li>
          <li><strong>Email :</strong> <a href="mailto:contact@revente-auto.example">contact@revente-auto.example</a></li>
          <li><strong>Adresse :</strong> 12 Avenue des Véhicules, 75000 Paris, France</li>
          <li><strong>Horaires :</strong> Lun–Ven 9h00–18h00</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<script type="module">
  import { VueContact } from './assets/js/Contact/VueContact.js';

  const vue = new VueContact();
  vue.initialiser();
</script>