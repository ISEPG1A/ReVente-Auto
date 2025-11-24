<?php
// Page Contact – affiche les infos de l'entreprise et un formulaire d'envoi
// Session déjà démarrée par le routeur; génération d'un token CSRF spécifique contact.
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['contact_csrf'];
?>
<section class="section">
  <div class="container">
    <h2>Contact</h2>
    <p>Une question sur une annonce, un partenariat ou un support technique ? Écrivez‑nous.</p>

    <div class="grid" style="margin-top:24px">
      <div class="field" style="grid-column: span 2;">
        <h3>Coordonnées</h3>
        <ul class="list">
          <li><strong>Téléphone :</strong> <a href="tel:+33123456789">+33 1 23 45 67 89</a></li>
          <li><strong>Email :</strong> <a href="mailto:contact@revente-auto.example">contact@revente-auto.example</a></li>
          <li><strong>Adresse :</strong> 12 Avenue des Véhicules, 75000 Paris, France</li>
          <li><strong>Horaires :</strong> Lun–Ven 9h00–18h00</li>
        </ul>
      </div>
    </div>

    <h3 style="margin-top:32px">Formulaire de contact</h3>
    <form id="contact-form" class="form" novalidate>
      <div class="grid">
        <div class="field">
          <label class="label" for="c-name">Nom</label>
          <input id="c-name" name="name" class="input" type="text" required maxlength="100" autocomplete="name">
        </div>
        <div class="field">
          <label class="label" for="c-email">Email</label>
          <input id="c-email" name="email" class="input" type="email" required autocomplete="email">
        </div>
        <div class="field" style="grid-column: span 2;">
          <label class="label" for="c-subject">Sujet</label>
          <input id="c-subject" name="subject" class="input" type="text" required maxlength="150">
        </div>
        <div class="field" style="grid-column: span 2;">
          <label class="label" for="c-message">Message</label>
          <textarea id="c-message" name="message" class="input" rows="6" required minlength="10" maxlength="5000" style="resize:vertical"></textarea>
          <p class="help">Expliquez votre demande (minimum 10 caractères).</p>
        </div>
        <!-- Honeypot anti-spam (doit rester vide) -->
        <input type="text" name="website" id="website" hidden autocomplete="off" tabindex="-1">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken) ?>">
      </div>
      <div class="actions">
        <button class="button" type="submit" id="contact-submit">Envoyer</button>
        <button class="button button--ghost" type="reset">Réinitialiser</button>
      </div>
      <div class="form__messages" aria-live="polite" role="status"></div>
    </form>
  </div>
</section>

<script>
// JS inline pour gestion du formulaire de contact
(function(){
  const form = document.getElementById('contact-form');
  if(!form) return;
  const messagesBox = form.querySelector('.form__messages');
  const submitBtn = document.getElementById('contact-submit');
  const apiBaseMeta = document.querySelector('meta[name="api-base"]');
  const API_BASE = (apiBaseMeta ? apiBaseMeta.getAttribute('content') : './api');
  const ENDPOINT = API_BASE + '/contact.php';

  const showMsg = (text, ok = true) => {
    messagesBox.innerHTML = `<div class="msg msg--${ok?'ok':'err'}">${text}</div>`;
  };

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    messagesBox.innerHTML='';
    if(submitBtn) submitBtn.disabled = true;
    try {
      const fd = new FormData(form);
      // Client-side validation simple
      const name = fd.get('name')?.toString().trim();
      const email = fd.get('email')?.toString().trim();
      const subject = fd.get('subject')?.toString().trim();
      const message = fd.get('message')?.toString().trim();
      const csrf = fd.get('csrf');
      if(fd.get('website')) { throw new Error('Spam détecté.'); }
      if(!name || !email || !subject || !message) { throw new Error('Tous les champs sont requis.'); }
      if(message.length < 10) { throw new Error('Message trop court.'); }
      const res = await fetch(ENDPOINT, {
        method: 'POST',
        body: fd,
        headers: { 'Accept':'application/json' }
      });
      const data = await res.json().catch(()=>({}));
      if(!res.ok) throw new Error(data?.error || 'Erreur envoi');
      showMsg('Message envoyé. Merci !');
      form.reset();
    } catch(err) {
      showMsg(err.message || 'Erreur', false);
    } finally {
      if(submitBtn) submitBtn.disabled = false;
    }
  });
})();
</script>