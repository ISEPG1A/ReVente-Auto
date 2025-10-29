// Navigation animée et responsive
// - Bouton hamburger (aria-expanded + hidden)
// - Indication du lien actif si besoin

function setupNav() {
  const btn = document.querySelector('.nav-toggle');
  const menu = document.getElementById('site-menu');
  if (!btn || !menu) return;

  const mq = window.matchMedia('(min-width: 801px)');
  const syncForDesktop = () => {
    if (mq.matches) {
      // Desktop: menu toujours visible
      menu.hidden = false;
      btn.setAttribute('aria-expanded', 'true');
    } else {
      // Mobile: menu replié par défaut
      menu.hidden = true;
      btn.setAttribute('aria-expanded', 'false');
    }
  };

  btn.addEventListener('click', () => {
    const open = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', String(!open));
    menu.hidden = open;
  });

  mq.addEventListener?.('change', syncForDesktop);
  syncForDesktop();
}

// Lien actif côté client (fallback au cas où le serveur ne met pas aria-current)
function markActiveLink() {
  const here = location.pathname.split('/').pop();
  document.querySelectorAll('.site-nav__link').forEach(a => {
    if (a.getAttribute('href').endsWith(here)) {
      a.setAttribute('aria-current', 'page');
    }
  });
}

window.addEventListener('DOMContentLoaded', () => {
  setupNav();
  markActiveLink();
  // User menu dropdown
  const menuBtn = document.getElementById('user-menu-btn');
  const menu = document.getElementById('user-menu');
  if(menuBtn && menu){
    const close = ()=>{ menuBtn.setAttribute('aria-expanded','false'); menu.hidden = true; };
    const open = ()=>{ menuBtn.setAttribute('aria-expanded','true'); menu.hidden = false; };
    menuBtn.addEventListener('click', (e)=>{
      e.preventDefault();
      e.stopPropagation();
      const expanded = menuBtn.getAttribute('aria-expanded') === 'true';
      expanded ? close() : open();
    });
    document.addEventListener('click', (e)=>{
      if(menu.hidden) return;
      if(!menu.contains(e.target) && e.target !== menuBtn) close();
    });
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') close(); });
  }
});
