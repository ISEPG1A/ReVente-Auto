/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VueCGU.js - GESTION DE LA PAGE CGU (Conditions Générales d'Utilisation)
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce module JavaScript gère toute la logique de la page CGU :
 * - Chargement des articles depuis l'API /api/cgu
 * - Affichage en accordéon (comme la page FAQ)
 * - Interface d'administration pour les admins (CRUD)
 * - Formulaires de création/modification d'articles
 * - Suppression d'articles avec confirmation
 * 
 * Architecture :
 * - Utilise des classes ES6
 * - Pattern Module (export default)
 * - Gestion événementielle (addEventListener)
 * - Fetch API pour les requêtes HTTP
 * 
 * @author  mat
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

class VueCGU {
  /**
   * Constructeur : initialise la classe
   * Lance automatiquement l'initialisation au chargement du DOM
   */
  constructor() {
    // État de l'application : liste des articles et statut admin
    this.articles = [];
    this.isAdmin = false;
    
    // Configuration : récupérer le chemin de base de l'application
    this.baseUrl = (window.APP_CONFIG && window.APP_CONFIG.baseUrl) || '/';
    // Construire l'URL de l'API
    this.apiUrl = this.baseUrl + 'api/cgu';
    
    // Références aux éléments du DOM (Document Object Model)
    this.container = null;           // Conteneur des articles
    this.loadingEl = null;           // Message de chargement
    this.errorEl = null;             // Message d'erreur
    this.adminAddContainer = null;   // Bouton "Ajouter un article"
    
    // Références à la modale d'édition
    this.modal = null;
    this.modalTitre = null;
    this.form = null;
    
    // ID de l'article en cours d'édition (null si création)
    this.articleEnEdition = null;
    
    // Lancer l'initialisation
    this.init();
  }

  /**
   * Initialisation du module
   * Attend que le DOM soit chargé avant d'exécuter setup()
   */
  init() {
    if (document.readyState === 'loading') {
      // Si le DOM n'est pas encore chargé, attendre l'événement DOMContentLoaded
      document.addEventListener('DOMContentLoaded', () => this.setup());
    } else {
      // Si le DOM est déjà chargé, exécuter immédiatement
      this.setup();
    }
  }

  /**
   * Configuration initiale
   * 1. Récupère les références aux éléments HTML
   * 2. Configure les écouteurs d'événements
   * 3. Charge les articles depuis l'API
   */
  setup() {
    // Récupération des éléments HTML par leurs IDs
    this.container = document.getElementById('cgu-articles-container');
    this.loadingEl = document.getElementById('cgu-loading');
    this.errorEl = document.getElementById('cgu-error');
    this.adminAddContainer = document.getElementById('cgu-admin-add-container');
    
    this.modal = document.getElementById('modal-edition-cgu');
    this.modalTitre = document.getElementById('modal-titre');
    this.form = document.getElementById('form-edition-cgu');
    
    // Vérification : si les éléments essentiels n'existent pas, arrêter
    if (!this.container) {
      console.error('Conteneur CGU introuvable');
      return;
    }
    
    // Configuration des événements
    this.setupEventListeners();
    
    // Chargement des articles via l'API
    this.chargerArticles();
  }

  /**
   * Configuration des écouteurs d'événements
   * - Bouton "Ajouter un article" (admin)
   * - Fermeture de la modale
   * - Soumission du formulaire
   */
  setupEventListeners() {
    // Bouton "Ajouter un article"
    const btnAjouter = document.getElementById('btn-ajouter-article');
    if (btnAjouter) {
      btnAjouter.addEventListener('click', () => this.ouvrirModalAjout());
    }
    
    // Boutons de fermeture de la modale (X et bouton Annuler)
    if (this.modal) {
      const btnsFermer = this.modal.querySelectorAll('.modal-close');
      btnsFermer.forEach(btn => {
        btn.addEventListener('click', () => this.fermerModal());
      });
      
      // Clic sur l'overlay (fond sombre) pour fermer la modale
      const overlay = this.modal.querySelector('.modal-overlay');
      if (overlay) {
        overlay.addEventListener('click', () => this.fermerModal());
      }
    }
    
    // Soumission du formulaire d'édition
    if (this.form) {
      this.form.addEventListener('submit', (e) => this.soumettreFormulaire(e));
    }
  }

  /**
   * Charge les articles CGU depuis l'API
   * Effectue une requête GET vers /api/cgu
   * 
   * Flux :
   * 1. Affiche le message de chargement
   * 2. Fait une requête fetch() vers l'API
   * 3. Si succès : affiche les articles
   * 4. Si échec : affiche un message d'erreur
   */
  async chargerArticles() {
    try {
      // Afficher le loader, masquer l'erreur
      this.loadingEl.style.display = 'block';
      this.errorEl.style.display = 'none';
      
      // Requête GET vers l'API
      // fetch() retourne une Promise
      const response = await fetch(this.apiUrl, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json'
        }
      });
      
      // Vérifier si la requête a réussi (code 200-299)
      if (!response.ok) {
        throw new Error(`Erreur HTTP ${response.status}`);
      }
      
      // Parser la réponse JSON
      const data = await response.json();
      
      // Vérifier la structure de la réponse
      if (!data.ok || !Array.isArray(data.articles)) {
        throw new Error('Format de réponse invalide');
      }
      
      // Stocker les articles et le statut admin
      this.articles = data.articles;
      this.isAdmin = data.isAdmin || false;
      
      // Afficher les articles dans le DOM
      this.afficherArticles();
      
      // Si admin, afficher le bouton "Ajouter"
      if (this.isAdmin && this.adminAddContainer) {
        this.adminAddContainer.style.display = 'block';
      }
      
    } catch (error) {
      // En cas d'erreur, afficher le message d'erreur
      console.error('Erreur lors du chargement des CGU:', error);
      this.errorEl.style.display = 'block';
    } finally {
      // Dans tous les cas, masquer le loader
      this.loadingEl.style.display = 'none';
    }
  }

  /**
   * Affiche les articles dans le conteneur
   * Génère le HTML pour chaque article et l'insère dans le DOM
   */
  afficherArticles() {
    // Vider le conteneur
    this.container.innerHTML = '';
    
    // Si aucun article, afficher un message
    if (this.articles.length === 0) {
      this.container.innerHTML = '<p style="text-align: center; padding: 40px;">Aucun article disponible pour le moment.</p>';
      return;
    }
    
    // Pour chaque article, créer un élément HTML
    this.articles.forEach((article, index) => {
      const articleEl = this.creerElementArticle(article, index);
      this.container.appendChild(articleEl);
    });
    
    // Mettre à jour la date de dernière modification
    this.mettreAJourDateModification();
  }

  /**
   * Crée un élément HTML pour un article (accordéon FAQ-style)
   * 
   * @param {Object} article - Données de l'article
   * @param {Number} index - Index de l'article dans la liste
   * @returns {HTMLElement} Élément DOM créé
   * 
   * Structure HTML générée :
   * <div class="faq-item">
   *   <button class="faq-question">Titre</button>
   *   <div class="faq-answer">
   *     Contenu + boutons admin
   *   </div>
   * </div>
   */
  creerElementArticle(article, index) {
    // Créer le conteneur principal de l'article
    const itemDiv = document.createElement('div');
    itemDiv.className = 'faq-item';
    itemDiv.setAttribute('data-article-id', article.id);
    
    // Créer le bouton titre (cliquable pour ouvrir/fermer)
    const boutonQuestion = document.createElement('button');
    boutonQuestion.className = 'faq-question';
    boutonQuestion.setAttribute('aria-expanded', 'false');
    boutonQuestion.setAttribute('id', `cgu-question-${index}`);
    
    // Span pour le texte du titre
    const spanTexte = document.createElement('span');
    spanTexte.className = 'faq-question__text';
    
    // Numéro de l'article
    const spanNumero = document.createElement('span');
    spanNumero.className = 'article-number';
    spanNumero.textContent = article.numero_article;
    
    // Titre de l'article
    const spanTitre = document.createTextNode(` ${article.titre}`);
    
    // Badge "Brouillon" si l'article n'est pas publié
    if (article.statut === 'brouillon') {
      const badge = document.createElement('span');
      badge.className = 'badge-brouillon';
      badge.textContent = 'Brouillon';
      badge.style.cssText = 'background: #ff9800; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-left: 10px;';
      spanTexte.appendChild(spanNumero);
      spanTexte.appendChild(spanTitre);
      spanTexte.appendChild(badge);
    } else {
      spanTexte.appendChild(spanNumero);
      spanTexte.appendChild(spanTitre);
    }
    
    boutonQuestion.appendChild(spanTexte);
    
    // Icône +/- pour l'accordéon
    const spanIcone = document.createElement('span');
    spanIcone.className = 'faq-icon';
    spanIcone.innerHTML = `
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
    `;
    boutonQuestion.appendChild(spanIcone);
    
    // Créer le conteneur de réponse (contenu de l'article)
    const divReponse = document.createElement('div');
    divReponse.className = 'faq-answer';
    divReponse.setAttribute('id', `cgu-answer-${index}`);
    divReponse.setAttribute('aria-labelledby', `cgu-question-${index}`);
    
    // Contenu de l'article
    const divContenu = document.createElement('div');
    divContenu.className = 'faq-answer__content';
    // innerHTML permet d'interpréter le HTML contenu dans article.contenu
    divContenu.innerHTML = article.contenu;
    divReponse.appendChild(divContenu);
    
    // Si admin, ajouter les boutons Modifier/Supprimer
    if (this.isAdmin) {
      const divActions = document.createElement('div');
      divActions.className = 'cgu-admin-actions';
      divActions.style.cssText = 'margin-top: 20px; padding-top: 20px; border-top: 2px solid #e0e0e0; display: flex; gap: 10px;';
      
      // Bouton Modifier
      const btnModifier = document.createElement('button');
      btnModifier.className = 'bouton bouton--secondaire';
      btnModifier.textContent = '✏️ Modifier';
      btnModifier.style.cssText = 'padding: 8px 16px;';
      btnModifier.addEventListener('click', (e) => {
        e.stopPropagation(); // Empêcher la fermeture de l'accordéon
        this.ouvrirModalModification(article);
      });
      
      // Bouton Supprimer
      const btnSupprimer = document.createElement('button');
      btnSupprimer.className = 'bouton bouton--danger';
      btnSupprimer.textContent = '🗑️ Supprimer';
      btnSupprimer.style.cssText = 'padding: 8px 16px; background-color: #d32f2f; color: white;';
      btnSupprimer.addEventListener('click', (e) => {
        e.stopPropagation();
        this.supprimerArticle(article);
      });
      
      divActions.appendChild(btnModifier);
      divActions.appendChild(btnSupprimer);
      divReponse.appendChild(divActions);
    }
    
    // Assembler l'article
    itemDiv.appendChild(boutonQuestion);
    itemDiv.appendChild(divReponse);
    
    // Ajouter l'écouteur de clic pour l'accordéon
    boutonQuestion.addEventListener('click', () => this.toggleAccordeon(itemDiv));
    
    return itemDiv;
  }

  /**
   * Ouvre/Ferme un article en accordéon
   * 
   * @param {HTMLElement} itemDiv - Élément de l'article
   */
  toggleAccordeon(itemDiv) {
    const isActive = itemDiv.classList.contains('active');
    const question = itemDiv.querySelector('.faq-question');
    
    if (isActive) {
      // Fermer l'article
      itemDiv.classList.remove('active');
      question.setAttribute('aria-expanded', 'false');
    } else {
      // Fermer tous les autres articles (accordéon simple)
      const tousLesItems = this.container.querySelectorAll('.faq-item');
      tousLesItems.forEach(item => {
        item.classList.remove('active');
        item.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
      });
      
      // Ouvrir cet article
      itemDiv.classList.add('active');
      question.setAttribute('aria-expanded', 'true');
      
      // Scroll vers l'élément si nécessaire
      setTimeout(() => {
        const rect = itemDiv.getBoundingClientRect();
        const isVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;
        if (!isVisible) {
          itemDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }, 300);
    }
  }

  /**
   * Ouvre la modale pour ajouter un nouvel article
   */
  ouvrirModalAjout() {
    this.articleEnEdition = null;
    this.modalTitre.textContent = 'Ajouter un nouvel article';
    
    // Réinitialiser le formulaire
    this.form.reset();
    document.getElementById('article-id').value = '';
    
    // Définir des valeurs par défaut
    document.getElementById('article-numero').value = this.articles.length + 1;
    document.getElementById('article-ordre').value = this.articles.length + 1;
    document.getElementById('article-statut').value = 'publie';
    document.getElementById('article-visible').checked = true;
    
    // Afficher la modale
    this.modal.style.display = 'flex';
  }

  /**
   * Ouvre la modale pour modifier un article existant
   * 
   * @param {Object} article - Données de l'article à modifier
   */
  ouvrirModalModification(article) {
    this.articleEnEdition = article.id;
    this.modalTitre.textContent = 'Modifier l\'article';
    
    // Remplir le formulaire avec les données de l'article
    document.getElementById('article-id').value = article.id;
    document.getElementById('article-numero').value = article.numero_article;
    document.getElementById('article-titre').value = article.titre;
    document.getElementById('article-contenu').value = article.contenu;
    document.getElementById('article-ordre').value = article.ordre;
    document.getElementById('article-statut').value = article.statut;
    document.getElementById('article-visible').checked = article.visible == 1;
    
    // Afficher la modale
    this.modal.style.display = 'flex';
  }

  /**
   * Ferme la modale d'édition
   */
  fermerModal() {
    this.modal.style.display = 'none';
    this.form.reset();
    this.articleEnEdition = null;
  }

  /**
   * Soumet le formulaire de création/modification
   * 
   * @param {Event} e - Événement de soumission du formulaire
   */
  async soumettreFormulaire(e) {
    e.preventDefault(); // Empêcher le rechargement de la page
    
    // Récupérer les données du formulaire
    const formData = new FormData(this.form);
    
    // Convertir FormData en objet JavaScript
    const donnees = {
      numero_article: parseInt(formData.get('numero_article')),
      titre: formData.get('titre'),
      contenu: formData.get('contenu'),
      ordre: parseInt(formData.get('ordre')) || 0,
      statut: formData.get('statut'),
      visible: document.getElementById('article-visible').checked
    };
    
    // Ajouter le token CSRF
    // Le token est généré automatiquement par le script protection-csrf.js
    donnees.csrf_token = await this.obtenirTokenCSRF();
    
    try {
      // Désactiver le bouton de soumission pendant l'envoi
      const btnSubmit = this.form.querySelector('button[type="submit"]');
      const btnSubmitText = btnSubmit.querySelector('#btn-submit-text');
      const textOriginal = btnSubmitText.textContent;
      btnSubmit.disabled = true;
      btnSubmitText.textContent = 'Enregistrement...';
      
      let response;
      
      if (this.articleEnEdition) {
        // Mode MODIFICATION : requête PUT
        response = await fetch(`${this.apiUrl}/${this.articleEnEdition}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(donnees)
        });
      } else {
        // Mode CRÉATION : requête POST
        response = await fetch(this.apiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(donnees)
        });
      }
      
      // Vérifier la réponse
      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.error || 'Erreur lors de l\'enregistrement');
      }
      
      // Succès : fermer la modale et recharger les articles
      this.fermerModal();
      this.chargerArticles();
      
      // Afficher un message de succès (vous pouvez ajouter une notification ici)
      alert(data.message || 'Article enregistré avec succès !');
      
      // Réactiver le bouton et restaurer le texte
      btnSubmit.disabled = false;
      btnSubmitText.textContent = textOriginal;
      
    } catch (error) {
      console.error('Erreur:', error);
      alert(`Erreur : ${error.message}`);
      
      // Réactiver le bouton en cas d'erreur
      const btnSubmit = this.form.querySelector('button[type="submit"]');
      if (btnSubmit) {
        btnSubmit.disabled = false;
        const btnSubmitText = btnSubmit.querySelector('#btn-submit-text');
        if (btnSubmitText) {
          btnSubmitText.textContent = 'Enregistrer';
        }
      }
    }
  }

  /**
   * Supprime un article après confirmation
   * 
   * @param {Object} article - Article à supprimer
   */
  async supprimerArticle(article) {
    // Demander confirmation
    const confirmation = confirm(
      `Êtes-vous sûr de vouloir supprimer l'article ${article.numero_article} : "${article.titre}" ?\n\n` +
      `Cette action est irréversible.`
    );
    
    if (!confirmation) {
      return; // L'utilisateur a annulé
    }
    
    try {
      // Obtenir le token CSRF
      const csrfToken = await this.obtenirTokenCSRF();
      
      // Requête DELETE vers l'API
      const response = await fetch(`${this.apiUrl}/${article.id}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          csrf_token: csrfToken
        })
      });
      
      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.error || 'Erreur lors de la suppression');
      }
      
      // Succès : recharger les articles
      this.chargerArticles();
      alert(data.message || 'Article supprimé avec succès !');
      
    } catch (error) {
      console.error('Erreur:', error);
      alert(`Erreur : ${error.message}`);
    }
  }

  /**
   * Obtient le token CSRF depuis le meta tag ou le cookie
   * 
   * @returns {string} Token CSRF
   */
  async obtenirTokenCSRF() {
    // Vérifier si un token existe déjà dans un meta tag
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) {
      return metaToken.content;
    }
    
    // Sinon, essayer de récupérer depuis les cookies
    const cookies = document.cookie.split(';');
    for (let cookie of cookies) {
      const [nom, valeur] = cookie.trim().split('=');
      if (nom === 'csrf_token') {
        return valeur;
      }
    }
    
    // Si aucun token trouvé, en générer un via l'API
    // (à adapter selon votre système de gestion CSRF)
    return '';
  }

  /**
   * Met à jour la date de dernière modification affichée en bas de page
   */
  mettreAJourDateModification() {
    const dateEl = document.getElementById('cgu-date-maj');
    if (!dateEl) return;
    
    // Trouver la date la plus récente parmi tous les articles
    if (this.articles.length === 0) {
      dateEl.textContent = 'Aucune modification';
      return;
    }
    
    // Trouver la date updated_at la plus récente
    const derniereDate = this.articles.reduce((max, article) => {
      const dateArticle = new Date(article.updated_at);
      return dateArticle > max ? dateArticle : max;
    }, new Date(0));
    
    // Formater la date en français
    const options = { 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    };
    dateEl.textContent = derniereDate.toLocaleDateString('fr-FR', options);
  }
}

// Export de la classe pour utilisation en tant que module
export default VueCGU;