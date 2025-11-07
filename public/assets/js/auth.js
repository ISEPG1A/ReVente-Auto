// Auth page script: toggles forms and calls auth API

const qs = (s, el=document) => el.querySelector(s);

// Helper to get base path from api-base meta tag
function getBasePath() {
  const meta = document.querySelector('meta[name="api-base"]');
  if (meta) {
    const apiBase = meta.getAttribute('content');
    // api-base is like "/test/ReVente-Auto/api", we want "/test/ReVente-Auto"
    return apiBase.replace(/\/api$/, '');
  }
  return '/test/ReVente-Auto'; // fallback
}

const apiAuth = (() => {
  const meta = document.querySelector('meta[name="api-base"]');
  const base = (meta ? meta.getAttribute('content') : './api') + '/auth.php';
  return {
    async login(email, password){
      const res = await fetch(base + '?action=login', {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify({email, password})});
      const data = await res.json();
      if(!res.ok) throw new Error(data?.error || 'Erreur login');
      return data;
    },
    async register(formData){
      const res = await fetch(base + '?action=register', {method:'POST', body: formData});
      const data = await res.json();
      if(!res.ok) throw new Error(data?.error || 'Erreur inscription');
      return data;
    },
    async forgot(email){
      const res = await fetch(base + '?action=forgot', {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify({email})});
      const data = await res.json();
      if(!res.ok) throw new Error(data?.error || 'Erreur oubli');
      return data;
    },
    async reset(token, password){
      const res = await fetch(base + '?action=reset', {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify({token, password})});
      const data = await res.json();
      if(!res.ok) throw new Error(data?.error || 'Erreur reset');
      return data;
    },
    async logout(){
      const res = await fetch(base + '?action=logout', {method:'POST', headers:{'Accept':'application/json'}});
      const data = await res.json();
      if(!res.ok) throw new Error(data?.error || 'Erreur logout');
      return data;
    }
  };
})();

function showMessage(container, text, type='ok'){ if(container) container.innerHTML = `<div class="msg msg--${type==='ok'?'ok':'err'}">${text}</div>`; }

function switchTab(name){
  const forms = {
    login: qs('#form-login'),
    register: qs('#form-register'),
    forgot: qs('#form-forgot'),
    reset: qs('#form-reset'),
  };
  Object.values(forms).forEach(f => f && (f.hidden = true));
  if(forms[name]) forms[name].hidden = false;
  // Button styles
  const btns = { login: qs('#tab-login'), register: qs('#tab-register'), forgot: qs('#tab-forgot') };
  Object.entries(btns).forEach(([k,btn])=>{
    if(!btn) return;
    if(k===name){ btn.classList.remove('button--ghost'); }
    else { btn.classList.add('button--ghost'); }
  });
}

function passwordStrong(pw){
  return typeof pw === 'string' && pw.length >= 8 && /[a-z]/.test(pw) && /[A-Z]/.test(pw) && /\d/.test(pw);
}

async function setupAuthPage(){
  // Tabs
  const url = new URL(window.location.href);
  const resetToken = url.searchParams.get('reset');
  if(resetToken){ switchTab('reset'); }
  else { switchTab('login'); }

  qs('#tab-login')?.addEventListener('click', ()=>switchTab('login'));
  qs('#tab-register')?.addEventListener('click', ()=>switchTab('register'));
  qs('#tab-forgot')?.addEventListener('click', ()=>switchTab('forgot'));

  // Login submit
  qs('#form-login')?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const box = e.currentTarget.querySelector('.form__messages');
    box.innerHTML='';
    const email = qs('#login-email').value.trim();
    const password = qs('#login-password').value;
    try{
      await apiAuth.login(email, password);
      window.location.href = getBasePath() + '/home';
    }catch(err){ showMessage(box, err.message || 'Impossible de se connecter', 'err'); }
  });

  // Register submit
  qs('#form-register')?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const form = e.currentTarget;
    const box = form.querySelector('.form__messages');
    box.innerHTML='';
    const pw = qs('#reg-password').value;
    if(!passwordStrong(pw)) return showMessage(box, 'Mot de passe trop faible.', 'err');
    const fd = new FormData(form);
    try{
      await apiAuth.register(fd);
      window.location.href = getBasePath() + '/home';
    }catch(err){ showMessage(box, err.message || "Inscription impossible", 'err'); }
  });

  // Forgot submit
  qs('#form-forgot')?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const box = e.currentTarget.querySelector('.form__messages');
    box.innerHTML='';
    const email = qs('#forgot-email').value.trim();
    try{
      const { reset_link } = await apiAuth.forgot(email);
      showMessage(box, reset_link ? `Lien de réinitialisation: <a href="${reset_link}">${reset_link}</a>` : 'Si un compte existe, un lien a été généré.', 'ok');
    }catch(err){ showMessage(box, err.message || 'Erreur envoi', 'err'); }
  });

  // Reset submit
  qs('#form-reset')?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const box = e.currentTarget.querySelector('.form__messages');
    box.innerHTML='';
    const token = new URL(window.location.href).searchParams.get('reset') || '';
    const password = qs('#reset-password').value;
    if(!passwordStrong(password)) return showMessage(box, 'Mot de passe trop faible.', 'err');
    try{
      await apiAuth.reset(token, password);
      showMessage(box, 'Mot de passe mis à jour. Vous pouvez vous connecter.', 'ok');
      setTimeout(()=>switchTab('login'), 1200);
    }catch(err){ showMessage(box, err.message || 'Impossible de réinitialiser', 'err'); }
  });
}

// Logout on nav button (if present)
document.addEventListener('DOMContentLoaded', ()=>{
  if(document.body.contains(qs('#form-login'))){ setupAuthPage(); }
  const logoutBtn = qs('#logout-btn');
  if(logoutBtn){
    logoutBtn.addEventListener('click', async ()=>{
      try{ await apiAuth.logout(); window.location.href = getBasePath() + '/home'; }catch(e){ alert('Déconnexion impossible'); }
    });
  }
  // Settings page
  const settingsForm = qs('#settings-form');
  if(settingsForm){
    (async ()=>{
      try{
        const meta = document.querySelector('meta[name="api-base"]');
        const base = (meta ? meta.getAttribute('content') : './api') + '/auth.php?action=me';
        const res = await fetch(base, {headers:{'Accept':'application/json'}});
        const data = await res.json();
        if(res.ok && data.user){
          qs('#set-first').value = data.user.first_name || '';
          qs('#set-last').value = data.user.last_name || '';
          qs('#set-phone').value = data.user.phone || '';
          // Set verification statuses
          const es = qs('#email-status');
          const ps = qs('#phone-status');
          if(es) es.textContent = 'Statut email: ' + (data.user.email_verified_at ? 'vérifié' : 'non vérifié');
          if(ps) ps.textContent = 'Statut téléphone: ' + (data.user.phone_verified_at ? 'vérifié' : 'non vérifié');
        }
      }catch(e){ /* ignore */ }
    })();
    settingsForm.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const box = settingsForm.querySelector('.form__messages');
      box.innerHTML = '';
      const fd = new FormData(settingsForm);
      try{
        const meta = document.querySelector('meta[name="api-base"]');
        const url = (meta ? meta.getAttribute('content') : './api') + '/auth.php?action=update_profile';
        const res = await fetch(url, {method:'POST', body: fd});
        const data = await res.json();
        if(!res.ok) throw new Error(data?.error || 'Erreur mise à jour');
        box.innerHTML = '<div class="msg msg--ok">Profil mis à jour.</div>';
        setTimeout(()=>window.location.reload(), 600);
      }catch(err){ box.innerHTML = `<div class="msg msg--err">${err.message}</div>`; }
    });
  }
  const delBtn = qs('#delete-account');
  if(delBtn){
    delBtn.addEventListener('click', async ()=>{
      if(!confirm('Supprimer votre compte ? Cette action est définitive.')) return;
      try{
        const meta = document.querySelector('meta[name="api-base"]');
        const url = (meta ? meta.getAttribute('content') : './api') + '/auth.php?action=delete_account';
        const res = await fetch(url, {method:'POST', headers:{'Accept':'application/json'}});
        const data = await res.json();
        if(!res.ok) throw new Error(data?.error || 'Suppression impossible');
        window.location.href = getBasePath() + '/home';
      }catch(e){ alert(e.message || 'Erreur'); }
    });
  }
  // Email verification request
  const btnEmailVerify = qs('#btn-email-verify');
  if(btnEmailVerify){
    btnEmailVerify.addEventListener('click', async ()=>{
      const meta = document.querySelector('meta[name="api-base"]');
      const url = (meta ? meta.getAttribute('content') : './api') + '/auth.php?action=request_email_verification';
      const box = qs('#email-verify-msg');
      box.innerHTML = '';
      try{
        const res = await fetch(url, {method:'POST', headers:{'Accept':'application/json'}});
        const data = await res.json();
        if(!res.ok) throw new Error(data?.error || 'Envoi impossible');
        if(data.verification_link){ box.innerHTML = `<div class="msg msg--ok">Lien: <a href="${data.verification_link}">${data.verification_link}</a></div>`; }
        else { box.innerHTML = '<div class="msg msg--ok">Lien généré.</div>'; }
      }catch(e){ box.innerHTML = `<div class="msg msg--err">${e.message}</div>`; }
    });
  }
  // Phone code request and verify
  const btnPhoneCode = qs('#btn-phone-code');
  const btnPhoneVerify = qs('#btn-phone-verify');
  if(btnPhoneCode){
    btnPhoneCode.addEventListener('click', async ()=>{
      const meta = document.querySelector('meta[name="api-base"]');
      const url = (meta ? meta.getAttribute('content') : './api') + '/auth.php?action=request_phone_code';
      const box = qs('#phone-verify-msg');
      box.innerHTML = '';
      try{
        const res = await fetch(url, {method:'POST', headers:{'Accept':'application/json'}});
        const data = await res.json();
        if(!res.ok) throw new Error(data?.error || 'Envoi impossible');
        box.innerHTML = `<div class="msg msg--ok">Code (démo): ${data.code}</div>`;
      }catch(e){ box.innerHTML = `<div class="msg msg--err">${e.message}</div>`; }
    });
  }
  if(btnPhoneVerify){
    btnPhoneVerify.addEventListener('click', async ()=>{
      const code = (qs('#phone-code')?.value || '').trim();
      const box = qs('#phone-verify-msg');
      box.innerHTML = '';
      if(!code){ box.innerHTML = '<div class="msg msg--err">Entrez un code.</div>'; return; }
      try{
        const meta = document.querySelector('meta[name="api-base"]');
        const url = (meta ? meta.getAttribute('content') : './api') + '/auth.php?action=verify_phone';
        const res = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify({code})});
        const data = await res.json();
        if(!res.ok) throw new Error(data?.error || 'Vérification impossible');
        box.innerHTML = '<div class="msg msg--ok">Téléphone vérifié.</div>';
      }catch(e){ box.innerHTML = `<div class="msg msg--err">${e.message}</div>`; }
    });
  }
});
