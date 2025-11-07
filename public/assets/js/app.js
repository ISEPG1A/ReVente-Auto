// Gestion des véhicules (galerie)

const apiBaseMeta = document.querySelector('meta[name="api-base"]');
const API_URL = (apiBaseMeta ? apiBaseMeta.getAttribute('content') : './api') + '/api.php';

const qs = (s, el = document) => el.querySelector(s);
const money = (n) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(n);
const debounce = (fn, delay = 250) => {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), delay);
  };
};

const state = { vehicles: [], filtered: [], query: '', sort: 'recent', me: null };

function setBusy(el, busy) {
  el && el.setAttribute('aria-busy', String(busy));
}

function showMessage(container, text, type = 'ok') {
  if (container) container.innerHTML = `<div class="msg msg--${type === 'ok' ? 'ok' : 'err'}">${text}</div>`;
}

function escapeHTML(s) {
  return String(s).replace(/[&<>"]+/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
}

function renderList(listEl, emptyEl) {
  if (!listEl || !emptyEl) return;
  listEl.innerHTML = '';
  const items = state.filtered;
  if (!items.length) { emptyEl.hidden = false; return; }
  emptyEl.hidden = true;
  const frag = document.createDocumentFragment();
  for (const v of items) {
    const li = document.createElement('li');
    li.className = 'card';
    const isOwner = state.me && v.seller_id && Number(v.seller_id) === Number(state.me.id);
    const isAdmin = state.me && state.me.role === 'admin';
    const sellerLine = (v.seller_first_name || v.seller_last_name || v.seller_email || v.seller_phone)
      ? `<p class="card__meta">Vendeur: ${escapeHTML((v.seller_first_name||'') + ' ' + (v.seller_last_name||''))} • ${escapeHTML(v.seller_email||'')} ${v.seller_phone? '• '+escapeHTML(v.seller_phone): ''}</p>`
      : '';
    li.innerHTML = `
      <span class="card__badge">${v.annee}</span>
      <div>
        <h3 class="card__title">${escapeHTML(v.marque)} ${escapeHTML(v.modele)}</h3>
        <p class="card__meta">#${v.id} • Ajouté le ${new Date(v.created_at).toLocaleDateString('fr-FR')}</p>
        ${sellerLine}
      </div>
      <div class="card__price">${money(v.prix)}</div>
    `;
    if (isOwner || isAdmin) {
      const btn = document.createElement('button');
      btn.textContent = 'Supprimer';
      btn.className = 'button button--danger';
      btn.style.marginLeft = '8px';
      btn.addEventListener('click', async () => {
        if (!confirm('Supprimer cette annonce ?')) return;
        try {
          const url = new URL(API_URL, window.location.href);
          url.searchParams.set('id', v.id);
          const res = await fetch(url, { method: 'DELETE', headers: { 'Accept': 'application/json' } });
          const payload = await res.json().catch(() => ({}));
          if (!res.ok) throw new Error(payload?.error || 'Suppression impossible');
          await fetchVehicles();
        } catch (err) {
          alert(err.message || 'Erreur');
        }
      });
      li.appendChild(btn);
    }
    frag.appendChild(li);
  }
  listEl.appendChild(frag);
}

function applyFiltersAndSort() {
  const q = state.query.trim().toLowerCase();
  let arr = [...state.vehicles];
  if (q) arr = arr.filter(v => `${v.marque} ${v.modele}`.toLowerCase().includes(q));
  switch (state.sort) {
    case 'price-asc': arr.sort((a, b) => a.prix - b.prix); break;
    case 'price-desc': arr.sort((a, b) => b.prix - a.prix); break;
    case 'year-desc': arr.sort((a, b) => b.annee - a.annee); break;
    case 'year-asc': arr.sort((a, b) => a.annee - b.annee); break;
    default: arr.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  }
  state.filtered = arr;
}

async function fetchVehicles() {
  const listEl = qs('#vehicle-list');
  const emptyEl = qs('#empty-state');
  if (!listEl || !emptyEl) return;
  try {
    setBusy(listEl, true);
    const url = new URL(API_URL, window.location.href);
    if (state.query) url.searchParams.set('q', state.query);
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error('Erreur serveur');
    const data = await res.json();
    state.vehicles = Array.isArray(data) ? data : [];
    applyFiltersAndSort();
    renderList(listEl, emptyEl);
  } catch (err) {
    console.error(err);
    const box = qs('.form__messages');
    showMessage(box, 'Impossible de charger la liste.', 'err');
  } finally {
    setBusy(listEl, false);
  }
}

function validateForm(form) {
  const marque = form.marque.value.trim();
  const modele = form.modele.value.trim();
  const annee = Number(form.annee.value);
  const prix = Number(form.prix.value);
  const currentYear = new Date().getFullYear() + 1;
  const errors = [];
  if (!marque || marque.length > 50) errors.push('Marque invalide.');
  if (!modele || modele.length > 50) errors.push('Modèle invalide.');
  if (!Number.isInteger(annee) || annee < 1900 || annee > currentYear) errors.push('Année invalide.');
  if (!Number.isFinite(prix) || prix < 0) errors.push('Prix invalide.');
  return { ok: errors.length === 0, errors, data: { marque, modele, annee, prix } };
}

async function handleSubmit(e) {
  e.preventDefault();
  const form = e.currentTarget;
  const box = qs('.form__messages');
  const btn = qs('#submit');
  if (box) box.innerHTML = '';

  const { ok, errors, data } = validateForm(form);
  if (!ok) { showMessage(box, errors.join(' '), 'err'); return; }

  try {
    if (btn) btn.disabled = true;
    const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(data) });
    const payload = await res.json();
    if (!res.ok) throw new Error(payload?.error || 'Erreur inconnue');
    showMessage(box, 'Véhicule ajouté avec succès.');
    if (typeof form.reset === 'function') { form.reset(); } else { HTMLFormElement.prototype.reset.call(form); }
    await fetchVehicles();
  } catch (err) { showMessage(box, err.message, 'err'); }
  finally { if (btn) btn.disabled = false; }
}

async function fetchMe() {
  try {
    const meta = document.querySelector('meta[name="api-base"]');
    const url = (meta ? meta.getAttribute('content') : './api') + '/auth.php?action=me';
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    if (res.ok && data.user) { state.me = data.user; }
    else { state.me = null; }
  } catch { state.me = null; }
}

function initUI() {
  const yearEl = qs('#year'); if (yearEl) yearEl.textContent = String(new Date().getFullYear());
  const search = qs('#search');
  if (search) search.addEventListener('input', debounce(() => { state.query = search.value; applyFiltersAndSort(); renderList(qs('#vehicle-list'), qs('#empty-state')); fetchVehicles(); }, 300));
  const sort = qs('#sort');
  if (sort) sort.addEventListener('change', () => { state.sort = sort.value; applyFiltersAndSort(); renderList(qs('#vehicle-list'), qs('#empty-state')); });
  const form = qs('#vehicle-form');
  if (form) {
    form.addEventListener('submit', handleSubmit);
  }
}

window.addEventListener('DOMContentLoaded', async () => {
  initUI();
  await fetchMe();
  // If not logged in, disable add form and prompt
  const form = qs('#vehicle-form');
  if (form && !state.me) {
    [...form.elements].forEach(el => { if (el.tagName !== 'BUTTON') el.disabled = true; });
    const msg = qs('.form__messages');
    showMessage(msg, 'Connectez-vous pour ajouter votre annonce.', 'err');
  }
  if (qs('#vehicle-list')) await fetchVehicles();
});
