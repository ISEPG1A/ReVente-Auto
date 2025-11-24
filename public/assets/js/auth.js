// Gestion de l'authentification

// Fonction utilitaire pour sélectionner un élément DOM (querySelector simplifié)
const selecteur = (s, el = document) => el.querySelector(s);

// Fonction pour obtenir le chemin de base de l'application
function obtenirCheminBase() {
  const baliseMetaApi = document.querySelector('meta[name="api-base"]');
  if (baliseMetaApi) {
    const cheminApi = baliseMetaApi.getAttribute('content');
    return cheminApi.replace(/\/api$/, '');
  }
  return '/test/ReVente-Auto';
}

// Objet contenant toutes les fonctions d'authentification API
const apiAuthentification = (() => {
  const baliseMetaApi = document.querySelector('meta[name="api-base"]');
  const urlBase = (baliseMetaApi ? baliseMetaApi.getAttribute('content') : './api') + '/auth.php';
  
  return {
    // Fonction pour se connecter avec email et mot de passe
    async seConnecter(email, motDePasse) {
      const reponse = await fetch(urlBase + '?action=login', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json', 
          'Accept': 'application/json' 
        },
        body: JSON.stringify({ email, password: motDePasse })
      });
      const donnees = await reponse.json();
      if (!reponse.ok) throw new Error(donnees?.error || 'Erreur login');
      return donnees;
    },
    
    // Fonction pour s'inscrire avec les données du formulaire
    async sInscrire(donneesFormulaire) {
      const reponse = await fetch(urlBase + '?action=register', { 
        method: 'POST', 
        body: donneesFormulaire 
      });
      const donnees = await reponse.json();
      if (!reponse.ok) throw new Error(donnees?.error || 'Erreur inscription');
      return donnees;
    },
    
    // Fonction pour demander un reset de mot de passe
    async motDePasseOublie(email) {
      const reponse = await fetch(urlBase + '?action=forgot', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json', 
          'Accept': 'application/json' 
        },
        body: JSON.stringify({ email })
      });
      const donnees = await reponse.json();
      if (!reponse.ok) throw new Error(donnees?.error || 'Erreur');
      return donnees;
    },
    
    // Fonction pour réinitialiser le mot de passe avec un token
    async reinitialiserMotDePasse(jeton, nouveauMotDePasse) {
      const reponse = await fetch(urlBase + '?action=reset', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json', 
          'Accept': 'application/json' 
        },
        body: JSON.stringify({ token: jeton, password: nouveauMotDePasse })
      });
      const donnees = await reponse.json();
      if (!reponse.ok) throw new Error(donnees?.error || 'Erreur');
      return donnees;
    },
    
    // Fonction pour se déconnecter
    async seDeconnecter() {
      const reponse = await fetch(urlBase + '?action=logout', {
        method: 'POST',
        headers: { 'Accept': 'application/json' }
      });
      const donnees = await reponse.json();
      if (!reponse.ok) throw new Error(donnees?.error || 'Erreur');
      return donnees;
    }
  };
})();

// Fonction pour afficher un message dans un conteneur donné
function afficherMessage(conteneur, texte, type = 'ok') {
  if (conteneur) conteneur.innerHTML = `<div class="msg msg--${type === 'ok' ? 'ok' : 'err'}">${texte}</div>`;
}

// Fonction pour changer d'onglet dans l'interface d'authentification
function changerOnglet(nomOnglet) {
  // Dictionnaire des formulaires disponibles
  const formulaires = {
    login: selecteur('#form-login'),
    register: selecteur('#form-register'),
    forgot: selecteur('#form-forgot'),
    reset: selecteur('#form-reset'),
  };
  
  // Masquer tous les formulaires
  Object.values(formulaires).forEach(formulaire => formulaire && (formulaire.hidden = true));
  
  // Afficher le formulaire sélectionné
  if (formulaires[nomOnglet]) formulaires[nomOnglet].hidden = false;
}

// Fonction pour vérifier la robustesse d'un mot de passe
function motDePasseRobuste(motDePasse) {
  return typeof motDePasse === 'string' && 
         motDePasse.length >= 8 && 
         /[a-z]/.test(motDePasse) && 
         /[A-Z]/.test(motDePasse) && 
         /\d/.test(motDePasse);
}

// Fonction asynchrone pour configurer la page d'authentification
async function configurerPageAuthentification(){
  // Gestion des onglets - vérifier s'il y a un token de reset dans l'URL
  const urlActuelle = new URL(window.location.href);
  const jetonReset = urlActuelle.searchParams.get('reset');
  
  if(jetonReset){ 
    changerOnglet('reset'); 
  } else { 
    changerOnglet('login'); 
  }

  // Ajouter les gestionnaires d'événements pour les liens de navigation
  selecteur('#link-register')?.addEventListener('click', (e) => { e.preventDefault(); changerOnglet('register'); });
  selecteur('#link-forgot')?.addEventListener('click', (e) => { e.preventDefault(); changerOnglet('forgot'); });
  selecteur('#link-login-register')?.addEventListener('click', (e) => { e.preventDefault(); changerOnglet('login'); });
  selecteur('#link-login-forgot')?.addEventListener('click', (e) => { e.preventDefault(); changerOnglet('login'); });

  // Gestionnaire de soumission du formulaire de connexion
  selecteur('#form-login')?.addEventListener('submit', async (evenement) => {
    evenement.preventDefault();
    const boiteMessage = evenement.currentTarget.querySelector('.form__messages');
    boiteMessage.innerHTML='';
    const email = selecteur('#login-email').value.trim();
    const motDePasse = selecteur('#login-password').value;
    
    try{
      await apiAuthentification.seConnecter(email, motDePasse);
      window.location.href = obtenirCheminBase() + '/home';
    } catch(erreur) { 
      afficherMessage(boiteMessage, erreur.message || 'Impossible de se connecter', 'err'); 
    }
  });

  // Gestionnaire de soumission du formulaire d'inscription
  selecteur('#form-register')?.addEventListener('submit', async (evenement) => {
    evenement.preventDefault();
    const formulaire = evenement.currentTarget;
    const boiteMessage = formulaire.querySelector('.form__messages');
    boiteMessage.innerHTML='';
    const motDePasse = selecteur('#reg-password').value;
    
    // Vérifier la robustesse du mot de passe
    if(!motDePasseRobuste(motDePasse)) {
      return afficherMessage(boiteMessage, 'Mot de passe trop faible.', 'err');
    }
    
    const donneesFormulaire = new FormData(formulaire);
    try{
      await apiAuthentification.sInscrire(donneesFormulaire);
      window.location.href = obtenirCheminBase() + '/home';
    } catch(erreur) { 
      afficherMessage(boiteMessage, erreur.message || "Inscription impossible", 'err'); 
    }
  });

  // Gestionnaire de soumission du formulaire de mot de passe oublié
  selecteur('#form-forgot')?.addEventListener('submit', async (evenement) => {
    evenement.preventDefault();
    const boiteMessage = evenement.currentTarget.querySelector('.form__messages');
    boiteMessage.innerHTML='';
    const email = selecteur('#forgot-email').value.trim();
    
    try{
      const { reset_link } = await apiAuthentification.motDePasseOublie(email);
      afficherMessage(boiteMessage, reset_link ? `Lien de réinitialisation: <a href="${reset_link}">${reset_link}</a>` : 'Si un compte existe, un lien a été généré.', 'ok');
    } catch(erreur) { 
      afficherMessage(boiteMessage, erreur.message || 'Erreur envoi', 'err'); 
    }
  });

  // Gestionnaire de soumission du formulaire de réinitialisation de mot de passe
  selecteur('#form-reset')?.addEventListener('submit', async (evenement) => {
    evenement.preventDefault();
    const boiteMessage = evenement.currentTarget.querySelector('.form__messages');
    boiteMessage.innerHTML='';
    const jeton = new URL(window.location.href).searchParams.get('reset') || '';
    const motDePasse = selecteur('#reset-password').value;
    
    // Vérifier la robustesse du nouveau mot de passe
    if(!motDePasseRobuste(motDePasse)) {
      return afficherMessage(boiteMessage, 'Mot de passe trop faible.', 'err');
    }
    
    try{
      await apiAuthentification.reinitialiserMotDePasse(jeton, motDePasse);
      afficherMessage(boiteMessage, 'Mot de passe mis à jour. Vous pouvez vous connecter.', 'ok');
      setTimeout(() => changerOnglet('login'), 1200);
    } catch(erreur) { 
      afficherMessage(boiteMessage, erreur.message || 'Impossible de réinitialiser', 'err'); 
    }
  });
}

// Gestionnaire d'événements principal pour le DOM
document.addEventListener('DOMContentLoaded', () => {
  // Si la page contient un formulaire de connexion, configurer la page d'authentification
  if(document.body.contains(selecteur('#form-login'))){ 
    configurerPageAuthentification(); 
  }
  
  // Gestionnaire pour le bouton de déconnexion (s'il est présent)
  const boutonDeconnexion = selecteur('#logout-btn');
  if(boutonDeconnexion){
    boutonDeconnexion.addEventListener('click', async () => {
      try{ 
        await apiAuthentification.seDeconnecter(); 
        window.location.href = obtenirCheminBase() + '/home'; 
      } catch(erreur) { 
        alert('Déconnexion impossible'); 
      }
    });
  }
  
  // Configuration de la page des paramètres
  const formulaireParametres = selecteur('#settings-form');
  if(formulaireParametres){
    // Fonction asynchrone pour charger les données utilisateur
    (async () => {
      try{
        const baliseMetaApi = document.querySelector('meta[name="api-base"]');
        const urlUtilisateur = (baliseMetaApi ? baliseMetaApi.getAttribute('content') : './api') + '/auth.php?action=me';
        const reponse = await fetch(urlUtilisateur, {
          headers: {'Accept':'application/json'}
        });
        const donnees = await reponse.json();
        
        if(reponse.ok && donnees.user){
          // Pré-remplir les champs avec les données utilisateur
          selecteur('#set-first').value = donnees.user.first_name || '';
          selecteur('#set-last').value = donnees.user.last_name || '';
          selecteur('#set-phone').value = donnees.user.phone || '';
          
          // Définir les statuts de vérification
          const statutEmail = selecteur('#email-status');
          const statutTelephone = selecteur('#phone-status');
          if(statutEmail) {
            statutEmail.textContent = 'Statut email: ' + (donnees.user.email_verified_at ? 'vérifié' : 'non vérifié');
          }
          if(statutTelephone) {
            statutTelephone.textContent = 'Statut téléphone: ' + (donnees.user.phone_verified_at ? 'vérifié' : 'non vérifié');
          }
        }
      } catch(erreur) { 
        /* Ignorer les erreurs de chargement */ 
      }
    })();
    
    // Gestionnaire de soumission pour le formulaire des paramètres
    formulaireParametres.addEventListener('submit', async (evenement) => {
      evenement.preventDefault();
      const boiteMessage = formulaireParametres.querySelector('.form__messages');
      boiteMessage.innerHTML = '';
      const donneesFormulaire = new FormData(formulaireParametres);
      
      try{
        const baliseMetaApi = document.querySelector('meta[name="api-base"]');
        const urlMiseAJour = (baliseMetaApi ? baliseMetaApi.getAttribute('content') : './api') + '/auth.php?action=update_profile';
        const reponse = await fetch(urlMiseAJour, {
          method: 'POST', 
          body: donneesFormulaire
        });
        const donnees = await reponse.json();
        
        if(!reponse.ok) throw new Error(donnees?.error || 'Erreur mise à jour');
        
        boiteMessage.innerHTML = '<div class="msg msg--ok">Profil mis à jour.</div>';
        setTimeout(() => window.location.reload(), 600);
      } catch(erreur) { 
        boiteMessage.innerHTML = `<div class="msg msg--err">${erreur.message}</div>`; 
      }
    });
  }
  
  // Gestionnaire pour le bouton de suppression de compte
  const boutonSupprimerCompte = selecteur('#delete-account');
  if(boutonSupprimerCompte){
    boutonSupprimerCompte.addEventListener('click', async () => {
      if(!confirm('Supprimer votre compte ? Cette action est définitive.')) return;
      
      try{
        const baliseMetaApi = document.querySelector('meta[name="api-base"]');
        const urlSuppression = (baliseMetaApi ? baliseMetaApi.getAttribute('content') : './api') + '/auth.php?action=delete_account';
        const reponse = await fetch(urlSuppression, {
          method: 'POST', 
          headers: {'Accept': 'application/json'}
        });
        const donnees = await reponse.json();
        
        if(!reponse.ok) throw new Error(donnees?.error || 'Suppression impossible');
        window.location.href = obtenirCheminBase() + '/home';
      } catch(erreur) { 
        alert(erreur.message || 'Erreur'); 
      }
    });
  }
  
  // Gestionnaire pour la demande de vérification d'email
  const boutonVerifierEmail = selecteur('#btn-email-verify');
  if(boutonVerifierEmail){
    boutonVerifierEmail.addEventListener('click', async () => {
      const baliseMetaApi = document.querySelector('meta[name="api-base"]');
      const urlVerificationEmail = (baliseMetaApi ? baliseMetaApi.getAttribute('content') : './api') + '/auth.php?action=request_email_verification';
      const boiteMessage = selecteur('#email-verify-msg');
      boiteMessage.innerHTML = '';
      
      try{
        const reponse = await fetch(urlVerificationEmail, {
          method: 'POST', 
          headers: {'Accept': 'application/json'}
        });
        const donnees = await reponse.json();
        
        if(!reponse.ok) throw new Error(donnees?.error || 'Envoi impossible');
        
        if(donnees.verification_link){ 
          boiteMessage.innerHTML = `<div class="msg msg--ok">Lien: <a href="${donnees.verification_link}">${donnees.verification_link}</a></div>`; 
        } else { 
          boiteMessage.innerHTML = '<div class="msg msg--ok">Lien généré.</div>'; 
        }
      } catch(erreur) { 
        boiteMessage.innerHTML = `<div class="msg msg--err">${erreur.message}</div>`; 
      }
    });
  }
  
  // Gestionnaires pour la vérification du téléphone
  const boutonCodeTelephone = selecteur('#btn-phone-code');
  const boutonVerifierTelephone = selecteur('#btn-phone-verify');
  
  if(boutonCodeTelephone){
    boutonCodeTelephone.addEventListener('click', async () => {
      const baliseMetaApi = document.querySelector('meta[name="api-base"]');
      const urlCodeTelephone = (baliseMetaApi ? baliseMetaApi.getAttribute('content') : './api') + '/auth.php?action=request_phone_code';
      const boiteMessage = selecteur('#phone-verify-msg');
      boiteMessage.innerHTML = '';
      
      try{
        const reponse = await fetch(urlCodeTelephone, {
          method: 'POST', 
          headers: {'Accept': 'application/json'}
        });
        const donnees = await reponse.json();
        
        if(!reponse.ok) throw new Error(donnees?.error || 'Envoi impossible');
        boiteMessage.innerHTML = `<div class="msg msg--ok">Code (démo): ${donnees.code}</div>`;
      } catch(erreur) { 
        boiteMessage.innerHTML = `<div class="msg msg--err">${erreur.message}</div>`; 
      }
    });
  }
  
  if(boutonVerifierTelephone){
    boutonVerifierTelephone.addEventListener('click', async () => {
      const code = (selecteur('#phone-code')?.value || '').trim();
      const boiteMessage = selecteur('#phone-verify-msg');
      boiteMessage.innerHTML = '';
      
      if(!code){ 
        boiteMessage.innerHTML = '<div class="msg msg--err">Entrez un code.</div>'; 
        return; 
      }
      
      try{
        const baliseMetaApi = document.querySelector('meta[name="api-base"]');
        const urlVerificationTelephone = (baliseMetaApi ? baliseMetaApi.getAttribute('content') : './api') + '/auth.php?action=verify_phone';
        const reponse = await fetch(urlVerificationTelephone, {
          method: 'POST', 
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }, 
          body: JSON.stringify({code})
        });
        const donnees = await reponse.json();
        
        if(!reponse.ok) throw new Error(donnees?.error || 'Vérification impossible');
        boiteMessage.innerHTML = '<div class="msg msg--ok">Téléphone vérifié.</div>';
      } catch(erreur) { 
        boiteMessage.innerHTML = `<div class="msg msg--err">${erreur.message}</div>`; 
      }
    });
  }
});
