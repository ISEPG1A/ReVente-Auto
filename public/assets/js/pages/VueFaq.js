/**
 * VueFaq.js - Gestion de la page FAQ
 * 
 * Ce module gère les interactions de la page FAQ :
 * - Accordéon des questions/réponses
 * - Animations d'ouverture/fermeture
 * - Accessibilité (ARIA)
 */

class VueFaq {
  constructor() {
    this.faqItems = null;
    this.init();
  }

  /**
   * Initialisation du module
   */
  init() {
    // Attendre que le DOM soit chargé
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.setup());
    } else {
      this.setup();
    }
  }

  /**
   * Configuration initiale
   */
  setup() {
    this.faqItems = document.querySelectorAll('.faq-item');
    
    if (this.faqItems.length === 0) {
      console.warn('Aucun élément FAQ trouvé sur la page');
      return;
    }

    this.setupEventListeners();
    this.setupKeyboardNavigation();
    this.checkURLHash();
  }

  /**
   * Configuration des écouteurs d'événements
   */
  setupEventListeners() {
    this.faqItems.forEach((item, index) => {
      const question = item.querySelector('.faq-question');
      
      if (question) {
        // Ajouter un ID unique pour l'accessibilité
        question.setAttribute('id', `faq-question-${index}`);
        const answer = item.querySelector('.faq-answer');
        
        if (answer) {
          answer.setAttribute('id', `faq-answer-${index}`);
          question.setAttribute('aria-controls', `faq-answer-${index}`);
          answer.setAttribute('aria-labelledby', `faq-question-${index}`);
        }

        // Écouteur de clic
        question.addEventListener('click', (e) => {
          e.preventDefault();
          this.toggleFaq(item);
        });
      }
    });
  }

  /**
   * Configuration de la navigation au clavier
   */
  setupKeyboardNavigation() {
    this.faqItems.forEach((item) => {
      const question = item.querySelector('.faq-question');
      
      if (question) {
        question.addEventListener('keydown', (e) => {
          // Enter ou Espace pour ouvrir/fermer
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.toggleFaq(item);
          }
          // Flèche vers le bas pour aller à la question suivante
          else if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.focusNextQuestion(item);
          }
          // Flèche vers le haut pour aller à la question précédente
          else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.focusPreviousQuestion(item);
          }
          // Home pour aller à la première question
          else if (e.key === 'Home') {
            e.preventDefault();
            this.focusFirstQuestion();
          }
          // End pour aller à la dernière question
          else if (e.key === 'End') {
            e.preventDefault();
            this.focusLastQuestion();
          }
        });
      }
    });
  }

  /**
   * Toggle (ouvrir/fermer) une FAQ
   * @param {HTMLElement} item - L'élément FAQ à toggle
   */
  toggleFaq(item) {
    const isActive = item.classList.contains('active');
    const question = item.querySelector('.faq-question');
    const answer = item.querySelector('.faq-answer');

    if (isActive) {
      // Fermer la FAQ
      this.closeFaq(item);
    } else {
      // Fermer toutes les autres FAQ (accordéon simple)
      this.closeAllFaqs();
      
      // Ouvrir cette FAQ
      this.openFaq(item);
    }

    // Scroll vers l'élément si nécessaire
    if (!isActive) {
      setTimeout(() => {
        const rect = item.getBoundingClientRect();
        const isVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;
        
        if (!isVisible) {
          item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }, 300);
    }
  }

  /**
   * Ouvrir une FAQ spécifique
   * @param {HTMLElement} item - L'élément FAQ à ouvrir
   */
  openFaq(item) {
    const question = item.querySelector('.faq-question');
    const answer = item.querySelector('.faq-answer');

    item.classList.add('active');
    
    if (question) {
      question.setAttribute('aria-expanded', 'true');
    }
    
    if (answer) {
      // Calculer la hauteur pour l'animation
      const content = answer.querySelector('.faq-answer__content');
      if (content) {
        answer.style.maxHeight = content.scrollHeight + 'px';
      }
    }

    // Émettre un événement personnalisé
    item.dispatchEvent(new CustomEvent('faq:opened', { 
      bubbles: true,
      detail: { item } 
    }));
  }

  /**
   * Fermer une FAQ spécifique
   * @param {HTMLElement} item - L'élément FAQ à fermer
   */
  closeFaq(item) {
    const question = item.querySelector('.faq-question');
    const answer = item.querySelector('.faq-answer');

    item.classList.remove('active');
    
    if (question) {
      question.setAttribute('aria-expanded', 'false');
    }
    
    if (answer) {
      answer.style.maxHeight = '0';
    }

    // Émettre un événement personnalisé
    item.dispatchEvent(new CustomEvent('faq:closed', { 
      bubbles: true,
      detail: { item } 
    }));
  }

  /**
   * Fermer toutes les FAQs
   */
  closeAllFaqs() {
    this.faqItems.forEach(item => {
      if (item.classList.contains('active')) {
        this.closeFaq(item);
      }
    });
  }

  /**
   * Ouvrir toutes les FAQs
   */
  openAllFaqs() {
    this.faqItems.forEach(item => {
      if (!item.classList.contains('active')) {
        this.openFaq(item);
      }
    });
  }

  /**
   * Donner le focus à la question suivante
   * @param {HTMLElement} currentItem - L'élément courant
   */
  focusNextQuestion(currentItem) {
    const itemsArray = Array.from(this.faqItems);
    const currentIndex = itemsArray.indexOf(currentItem);
    const nextIndex = (currentIndex + 1) % itemsArray.length;
    const nextQuestion = itemsArray[nextIndex].querySelector('.faq-question');
    
    if (nextQuestion) {
      nextQuestion.focus();
    }
  }

  /**
   * Donner le focus à la question précédente
   * @param {HTMLElement} currentItem - L'élément courant
   */
  focusPreviousQuestion(currentItem) {
    const itemsArray = Array.from(this.faqItems);
    const currentIndex = itemsArray.indexOf(currentItem);
    const previousIndex = currentIndex === 0 ? itemsArray.length - 1 : currentIndex - 1;
    const previousQuestion = itemsArray[previousIndex].querySelector('.faq-question');
    
    if (previousQuestion) {
      previousQuestion.focus();
    }
  }

  /**
   * Donner le focus à la première question
   */
  focusFirstQuestion() {
    const firstQuestion = this.faqItems[0].querySelector('.faq-question');
    if (firstQuestion) {
      firstQuestion.focus();
    }
  }

  /**
   * Donner le focus à la dernière question
   */
  focusLastQuestion() {
    const lastItem = this.faqItems[this.faqItems.length - 1];
    const lastQuestion = lastItem.querySelector('.faq-question');
    if (lastQuestion) {
      lastQuestion.focus();
    }
  }

  /**
   * Vérifier si un hash dans l'URL correspond à une FAQ
   */
  checkURLHash() {
    const hash = window.location.hash;
    
    if (hash) {
      // Chercher une FAQ correspondante
      const targetId = hash.substring(1);
      const targetItem = document.getElementById(targetId)?.closest('.faq-item');
      
      if (targetItem) {
        // Ouvrir cette FAQ après un court délai
        setTimeout(() => {
          this.openFaq(targetItem);
          targetItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 300);
      }
    }
  }

  /**
   * Rechercher dans les FAQs
   * @param {string} searchTerm - Terme de recherche
   * @returns {Array} - Tableau des FAQs correspondantes
   */
  searchFaqs(searchTerm) {
    const results = [];
    const term = searchTerm.toLowerCase();

    this.faqItems.forEach(item => {
      const question = item.querySelector('.faq-question__text');
      const answer = item.querySelector('.faq-answer__content');
      
      if (question && answer) {
        const questionText = question.textContent.toLowerCase();
        const answerText = answer.textContent.toLowerCase();
        
        if (questionText.includes(term) || answerText.includes(term)) {
          results.push(item);
        }
      }
    });

    return results;
  }

  /**
   * Filtrer les FAQs visibles
   * @param {string} searchTerm - Terme de recherche
   */
  filterFaqs(searchTerm) {
    if (!searchTerm) {
      // Tout afficher si pas de terme de recherche
      this.faqItems.forEach(item => {
        item.style.display = '';
      });
      return;
    }

    const results = this.searchFaqs(searchTerm);
    
    this.faqItems.forEach(item => {
      if (results.includes(item)) {
        item.style.display = '';
      } else {
        item.style.display = 'none';
      }
    });
  }
}

// Initialiser automatiquement quand le module est chargé
const vueFaq = new VueFaq();

// Exporter pour utilisation externe si nécessaire
export default VueFaq;
