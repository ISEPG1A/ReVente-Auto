/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VueCGU.js - GESTION DE LA PAGE CGU (Conditions Générales d'Utilisation)
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce module JavaScript gère toute la logique de la page CGU :
 * - Chargement des articles depuis l'API /api/cgu
 * - Affichage en accordéon (comme la page FAQ)
 * - Interface d'administration pour les admins (CRUD)
 * - Réordonnancement des articles, sections et points
 * 
 * @author  mat
 * @version 2.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { afficherNotificationGlobale, afficherModaleConfirmation, obtenirUrlApi } from '../../application.js';

class VueCGU {
    /**
     * Constructeur : initialise la classe
     */
    constructor() {
        this.articles = [];
        this.isAdmin = false;
        this.csrfToken = null;
        this.modeEdition = false;
        this.articlesOuverts = new Set(); // IDs des articles actuellement ouverts
        
        // Références aux éléments du DOM
        this.container = null;
        this.loadingEl = null;
        this.errorEl = null;
        this.btnModeEdition = null;
        
        // Modale
        this.modal = null;
        this.modalTitre = null;
        this.modalBody = null;
        
        // État édition
        this.modeModale = null; // 'article', 'section', 'point'
        this.itemEnEdition = null;
        this.articleEnCours = null;
        this.sectionEnCours = null;
        
        this.init();
    }

    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    setup() {
        this.container = document.getElementById('cgu-articles-container');
        this.loadingEl = document.getElementById('cgu-loading');
        this.errorEl = document.getElementById('cgu-error');
        this.btnModeEdition = document.getElementById('btn-mode-edition');
        
        this.modal = document.getElementById('modal-edition-cgu');
        this.modalTitre = document.getElementById('modal-titre');
        this.modalBody = document.getElementById('modal-body');
        
        if (!this.container) {
            console.error('Conteneur CGU introuvable');
            return;
        }
        
        this.setupEventListeners();
        this.chargerArticles();
    }

    setupEventListeners() {
        // Bouton "Mode édition"
        this.btnModeEdition?.addEventListener('click', () => {
            this.toggleModeEdition();
        });
        
        // Modale - fermeture
        if (this.modal) {
            this.modal.querySelector('.modal-overlay')?.addEventListener('click', () => this.fermerModal());
            this.modal.querySelectorAll('.modal-close').forEach(btn => {
                btn.addEventListener('click', () => this.fermerModal());
            });
        }
        
        // Bouton sauvegarder modale
        document.getElementById('btn-sauvegarder-modale')?.addEventListener('click', () => {
            this.sauvegarder();
        });
        
        // Escape pour fermer la modale
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal?.style.display !== 'none') {
                this.fermerModal();
            }
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MODE ÉDITION
    // ═══════════════════════════════════════════════════════════════════════

    toggleModeEdition() {
        this.modeEdition = !this.modeEdition;
        
        if (this.btnModeEdition) {
            if (this.modeEdition) {
                this.btnModeEdition.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    Mode lecture
                `;
                this.btnModeEdition.classList.add('bouton--actif');
            } else {
                this.btnModeEdition.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Mode édition
                `;
                this.btnModeEdition.classList.remove('bouton--actif');
            }
        }
        
        // Réafficher les articles avec/sans les contrôles d'édition
        this.afficherArticles();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CHARGEMENT DES DONNÉES
    // ═══════════════════════════════════════════════════════════════════════

    async chargerArticles() {
        try {
            if (this.loadingEl) this.loadingEl.style.display = 'block';
            if (this.errorEl) this.errorEl.style.display = 'none';
            
            const response = await fetch(obtenirUrlApi('cgu'));
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.ok || !Array.isArray(data.articles)) {
                throw new Error('Format de réponse invalide');
            }
            
            this.articles = data.articles;
            this.isAdmin = data.isAdmin || false;
            this.csrfToken = data.csrfToken || null;
            
            this.afficherArticles();
            
            // Afficher le bouton mode édition si admin
            if (this.isAdmin && this.btnModeEdition) {
                this.btnModeEdition.style.display = 'inline-flex';
            }
            
            // Charger les versions archivées
            this.chargerVersions();
            
        } catch (error) {
            console.error('Erreur lors du chargement des CGU:', error);
            if (this.errorEl) this.errorEl.style.display = 'block';
        } finally {
            if (this.loadingEl) this.loadingEl.style.display = 'none';
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AFFICHAGE DES ARTICLES
    // ═══════════════════════════════════════════════════════════════════════

    afficherArticles() {
        // Sauvegarder les articles ouverts avant de vider le conteneur
        this.container.querySelectorAll('.faq-item.active').forEach(item => {
            const id = item.getAttribute('data-article-id');
            if (id) this.articlesOuverts.add(id);
        });
        
        this.container.innerHTML = '';
        
        // Filtrer les articles publiés pour les non-admins
        const articlesAfficher = this.isAdmin 
            ? this.articles 
            : this.articles.filter(a => a.statut === 'publie');
        
        if (articlesAfficher.length === 0) {
            this.container.innerHTML = '<p class="cgu-vide">Aucun article disponible pour le moment.</p>';
            return;
        }
        
        articlesAfficher.forEach((article, index) => {
            const articleEl = this.creerElementArticle(article, index);
            
            // Restaurer l'état ouvert si l'article était ouvert
            if (this.articlesOuverts.has(String(article.id))) {
                articleEl.classList.add('active');
                articleEl.querySelector('.faq-question')?.setAttribute('aria-expanded', 'true');
            }
            
            this.container.appendChild(articleEl);
        });
        
        // Ajouter le bouton "Ajouter un article" en mode édition
        if (this.isAdmin && this.modeEdition) {
            const btnAjouterArticle = document.createElement('button');
            btnAjouterArticle.className = 'bouton bouton--primaire cgu-btn-ajouter-article';
            btnAjouterArticle.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Ajouter un article
            `;
            btnAjouterArticle.addEventListener('click', () => this.ouvrirModale('article'));
            this.container.appendChild(btnAjouterArticle);
        }
        
        this.mettreAJourDateModification();
    }

    creerElementArticle(article, index) {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'faq-item';
        itemDiv.setAttribute('data-article-id', article.id);
        
        // Bouton question (cliquable pour accordéon)
        const boutonQuestion = document.createElement('button');
        boutonQuestion.className = 'faq-question';
        boutonQuestion.setAttribute('aria-expanded', 'false');
        boutonQuestion.setAttribute('id', `cgu-question-${index}`);
        
        // Texte du titre
        const spanTexte = document.createElement('span');
        spanTexte.className = 'faq-question__text';
        
        // Numéro de l'article
        const spanNumero = document.createElement('span');
        spanNumero.className = 'article-number';
        spanNumero.textContent = `Article ${article.numero}`;
        
        spanTexte.appendChild(spanNumero);
        spanTexte.appendChild(document.createTextNode(` ${article.titre}`));
        
        // Badge brouillon
        if (article.statut === 'brouillon') {
            const badge = document.createElement('span');
            badge.className = 'cgu-badge cgu-badge--brouillon';
            badge.textContent = 'Brouillon';
            spanTexte.appendChild(badge);
        }
        
        boutonQuestion.appendChild(spanTexte);
        
        // Icône +/- pour l'accordéon (SVG)
        const spanIcone = document.createElement('span');
        spanIcone.className = 'faq-icon';
        spanIcone.innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
        `;
        boutonQuestion.appendChild(spanIcone);
        
        // Conteneur de réponse
        const divReponse = document.createElement('div');
        divReponse.className = 'faq-answer';
        divReponse.setAttribute('id', `cgu-answer-${index}`);
        
        const divContenu = document.createElement('div');
        divContenu.className = 'faq-answer__content';
        
        // Afficher les sections
        if (article.sections && article.sections.length > 0) {
            article.sections.forEach(section => {
                divContenu.appendChild(this.creerElementSection(article, section));
            });
        } else {
            divContenu.innerHTML = '<p class="cgu-section-vide">Aucun contenu pour cet article.</p>';
        }
        
        // Actions admin en mode édition
        if (this.isAdmin && this.modeEdition) {
            divContenu.appendChild(this.creerActionsArticle(article, index));
        }
        
        divReponse.appendChild(divContenu);
        
        itemDiv.appendChild(boutonQuestion);
        itemDiv.appendChild(divReponse);
        
        // Événement accordéon
        boutonQuestion.addEventListener('click', () => this.toggleAccordeon(itemDiv));
        
        return itemDiv;
    }

    creerElementSection(article, section) {
        const div = document.createElement('div');
        div.className = 'cgu-section-accord';
        div.setAttribute('data-section-id', section.id);
        
        // En-tête section
        const header = document.createElement('div');
        header.className = 'cgu-section-accord__header';
        
        const titre = document.createElement('h4');
        titre.className = 'cgu-section-accord__titre';
        titre.innerHTML = `<span class="cgu-section-numero">${article.numero}.${section.numero}</span> ${this.escapeHtml(section.titre)}`;
        header.appendChild(titre);
        
        // Boutons d'action section (mode édition)
        if (this.isAdmin && this.modeEdition) {
            header.appendChild(this.creerActionsSection(article, section));
        }
        
        div.appendChild(header);
        
        // Points ou contenu
        if (section.points && section.points.length > 0) {
            const liste = document.createElement('ul');
            liste.className = 'cgu-points-liste';
            
            section.points.forEach((point, idx) => {
                liste.appendChild(this.creerElementPoint(article, section, point, idx));
            });
            
            div.appendChild(liste);
        } else if (section.contenu) {
            const contenu = document.createElement('p');
            contenu.className = 'cgu-section-accord__contenu';
            contenu.textContent = section.contenu;
            div.appendChild(contenu);
        }
        
        return div;
    }

    creerElementPoint(article, section, point, index) {
        const li = document.createElement('li');
        li.className = 'cgu-point-item';
        li.setAttribute('data-point-id', point.id);
        
        const infoDiv = document.createElement('div');
        infoDiv.className = 'cgu-point-info';
        
        // Structure : header (numéro + titre) puis contenu avec barre grise
        infoDiv.innerHTML = `
            <div class="cgu-point-header">
                <span class="cgu-point-numero">${article.numero}.${section.numero}.${point.numero}</span>
                ${point.titre ? `<strong class="cgu-point-titre">${this.escapeHtml(point.titre)}</strong>` : ''}
            </div>
            ${point.contenu ? `<p class="cgu-point-contenu">${this.escapeHtml(point.contenu)}</p>` : ''}
        `;
        
        li.appendChild(infoDiv);
        
        // Actions point (mode édition)
        if (this.isAdmin && this.modeEdition) {
            li.appendChild(this.creerActionsPoint(article, section, point, index));
        }
        
        return li;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACTIONS ADMIN (BOUTONS)
    // ═══════════════════════════════════════════════════════════════════════

    creerActionsArticle(article, index) {
        const div = document.createElement('div');
        div.className = 'cgu-admin-actions';
        
        const totalArticles = this.articles.length;
        const isFirst = index === 0;
        const isLast = index === totalArticles - 1;
        
        div.innerHTML = `
            <div class="cgu-admin-actions__groupe">
                <button class="cgu-btn cgu-btn--ajouter-section" title="Ajouter une section">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Ajouter section
                </button>
            </div>
            <div class="cgu-admin-actions__groupe">
                <button class="cgu-btn cgu-btn--modifier" title="Modifier">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </button>
                <button class="cgu-btn cgu-btn--supprimer" title="Supprimer">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                </button>
            </div>
            <div class="cgu-admin-actions__groupe cgu-admin-actions__ordre">
                ${!isFirst ? `<button class="cgu-btn cgu-btn--monter" title="Monter">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="18 15 12 9 6 15"></polyline>
                    </svg>
                </button>` : ''}
                ${!isLast ? `<button class="cgu-btn cgu-btn--descendre" title="Descendre">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>` : ''}
            </div>
        `;
        
        // Événements
        div.querySelector('.cgu-btn--ajouter-section').addEventListener('click', (e) => {
            e.stopPropagation();
            this.articleEnCours = article;
            this.ouvrirModale('section');
        });
        
        div.querySelector('.cgu-btn--modifier').addEventListener('click', (e) => {
            e.stopPropagation();
            this.ouvrirModale('article', article);
        });
        
        div.querySelector('.cgu-btn--supprimer').addEventListener('click', (e) => {
            e.stopPropagation();
            this.supprimer('article', article);
        });
        
        const btnMonter = div.querySelector('.cgu-btn--monter');
        if (btnMonter) {
            btnMonter.addEventListener('click', (e) => {
                e.stopPropagation();
                this.deplacerArticle(article, 'monter');
            });
        }
        
        const btnDescendre = div.querySelector('.cgu-btn--descendre');
        if (btnDescendre) {
            btnDescendre.addEventListener('click', (e) => {
                e.stopPropagation();
                this.deplacerArticle(article, 'descendre');
            });
        }
        
        return div;
    }

    creerActionsSection(article, section) {
        const div = document.createElement('div');
        div.className = 'cgu-section-actions';
        
        const sections = article.sections || [];
        const index = sections.findIndex(s => String(s.id) === String(section.id));
        const isFirst = index === 0 || index === -1;
        const isLast = index === sections.length - 1 || index === -1;
        
        div.innerHTML = `
            <button class="cgu-btn-sm cgu-btn--ajouter-point" title="Ajouter un point">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>
            <button class="cgu-btn-sm cgu-btn--modifier" title="Modifier">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </button>
            <button class="cgu-btn-sm cgu-btn--supprimer" title="Supprimer">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </button>
            ${!isFirst ? `<button class="cgu-btn-sm cgu-btn--monter" title="Monter">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="18 15 12 9 6 15"></polyline>
                </svg>
            </button>` : ''}
            ${!isLast ? `<button class="cgu-btn-sm cgu-btn--descendre" title="Descendre">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>` : ''}
        `;
        
        div.querySelector('.cgu-btn--ajouter-point').addEventListener('click', (e) => {
            e.stopPropagation();
            this.articleEnCours = article;
            this.sectionEnCours = section;
            this.ouvrirModale('point');
        });
        
        div.querySelector('.cgu-btn--modifier').addEventListener('click', (e) => {
            e.stopPropagation();
            this.articleEnCours = article;
            this.ouvrirModale('section', section);
        });
        
        div.querySelector('.cgu-btn--supprimer').addEventListener('click', (e) => {
            e.stopPropagation();
            this.supprimer('section', section);
        });
        
        const btnMonter = div.querySelector('.cgu-btn--monter');
        if (btnMonter) {
            btnMonter.addEventListener('click', (e) => {
                e.stopPropagation();
                this.deplacerSection(article, section, 'monter');
            });
        }
        
        const btnDescendre = div.querySelector('.cgu-btn--descendre');
        if (btnDescendre) {
            btnDescendre.addEventListener('click', (e) => {
                e.stopPropagation();
                this.deplacerSection(article, section, 'descendre');
            });
        }
        
        return div;
    }

    creerActionsPoint(article, section, point, index) {
        const div = document.createElement('div');
        div.className = 'cgu-point-actions';
        
        const points = section.points || [];
        const isFirst = index === 0;
        const isLast = index === points.length - 1;
        
        div.innerHTML = `
            <button class="cgu-btn-sm cgu-btn--modifier" title="Modifier">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </button>
            <button class="cgu-btn-sm cgu-btn--supprimer" title="Supprimer">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </button>
            ${!isFirst ? `<button class="cgu-btn-sm cgu-btn--monter" title="Monter">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="18 15 12 9 6 15"></polyline>
                </svg>
            </button>` : ''}
            ${!isLast ? `<button class="cgu-btn-sm cgu-btn--descendre" title="Descendre">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>` : ''}
        `;
        
        div.querySelector('.cgu-btn--modifier').addEventListener('click', (e) => {
            e.stopPropagation();
            this.articleEnCours = article;
            this.sectionEnCours = section;
            this.ouvrirModale('point', point);
        });
        
        div.querySelector('.cgu-btn--supprimer').addEventListener('click', (e) => {
            e.stopPropagation();
            this.supprimer('point', point);
        });
        
        const btnMonter = div.querySelector('.cgu-btn--monter');
        if (btnMonter) {
            btnMonter.addEventListener('click', (e) => {
                e.stopPropagation();
                this.deplacerPoint(section, point, 'monter');
            });
        }
        
        const btnDescendre = div.querySelector('.cgu-btn--descendre');
        if (btnDescendre) {
            btnDescendre.addEventListener('click', (e) => {
                e.stopPropagation();
                this.deplacerPoint(section, point, 'descendre');
            });
        }
        
        return div;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCORDÉON
    // ═══════════════════════════════════════════════════════════════════════

    toggleAccordeon(itemDiv) {
        const isActive = itemDiv.classList.contains('active');
        const question = itemDiv.querySelector('.faq-question');
        const articleId = itemDiv.getAttribute('data-article-id');
        
        if (isActive) {
            itemDiv.classList.remove('active');
            question.setAttribute('aria-expanded', 'false');
            // Retirer de la liste des articles ouverts
            this.articlesOuverts.delete(articleId);
        } else {
            // Fermer tous les autres et vider la liste
            this.container.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
                item.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
            });
            this.articlesOuverts.clear();
            
            itemDiv.classList.add('active');
            question.setAttribute('aria-expanded', 'true');
            // Ajouter à la liste des articles ouverts
            this.articlesOuverts.add(articleId);
            
            // Scroll si nécessaire
            setTimeout(() => {
                const rect = itemDiv.getBoundingClientRect();
                if (rect.top < 0 || rect.bottom > window.innerHeight) {
                    itemDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }, 300);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MODALE
    // ═══════════════════════════════════════════════════════════════════════

    ouvrirModale(type, item = null) {
        this.modeModale = type;
        this.itemEnEdition = item;
        
        const isEdit = !!item;
        let titre = '';
        let formulaire = '';
        
        switch (type) {
            case 'article':
                titre = isEdit ? `Modifier l'article ${item.numero}` : 'Nouvel article';
                formulaire = `
                    <div class="form-groupe">
                        <label for="input-titre">Titre de l'article *</label>
                        <input type="text" id="input-titre" class="form-control" 
                               value="${isEdit ? this.escapeHtml(item.titre) : ''}" 
                               placeholder="Ex: Objet et Définitions" required>
                    </div>
                    <div class="form-groupe">
                        <label for="input-statut">Statut</label>
                        <select id="input-statut" class="form-control">
                            <option value="publie" ${!isEdit || item.statut === 'publie' ? 'selected' : ''}>Publié</option>
                            <option value="brouillon" ${isEdit && item.statut === 'brouillon' ? 'selected' : ''}>Brouillon</option>
                        </select>
                    </div>
                `;
                break;
                
            case 'section':
                titre = isEdit 
                    ? `Modifier la section ${this.articleEnCours.numero}.${item.numero}` 
                    : `Nouvelle section (Article ${this.articleEnCours.numero})`;
                formulaire = `
                    <div class="form-groupe">
                        <label for="input-titre">Titre de la section *</label>
                        <input type="text" id="input-titre" class="form-control" 
                               value="${isEdit ? this.escapeHtml(item.titre) : ''}" 
                               placeholder="Ex: Définitions" required>
                    </div>
                    <div class="form-groupe">
                        <label for="input-contenu">Contenu (optionnel)</label>
                        <textarea id="input-contenu" class="form-control" rows="4"
                                  placeholder="Laissez vide pour ajouter des points détaillés">${isEdit && item.contenu ? this.escapeHtml(item.contenu) : ''}</textarea>
                        <small class="form-hint">Si vide, vous pourrez ajouter des points numérotés</small>
                    </div>
                `;
                break;
                
            case 'point':
                titre = isEdit ? 'Modifier le point' : `Nouveau point (Section ${this.articleEnCours.numero}.${this.sectionEnCours.numero})`;
                formulaire = `
                    <div class="form-groupe">
                        <label for="input-titre">Titre du point (optionnel)</label>
                        <input type="text" id="input-titre" class="form-control" 
                               value="${isEdit && item.titre ? this.escapeHtml(item.titre) : ''}" 
                               placeholder="Ex: Définition de la plateforme">
                    </div>
                    <div class="form-groupe">
                        <label for="input-contenu">Contenu *</label>
                        <textarea id="input-contenu" class="form-control" rows="4"
                                  placeholder="Contenu du point..." required>${isEdit ? this.escapeHtml(item.contenu) : ''}</textarea>
                    </div>
                `;
                break;
        }
        
        this.modalTitre.textContent = titre;
        this.modalBody.innerHTML = formulaire;
        this.modal.style.display = 'flex';
        
        setTimeout(() => {
            this.modalBody.querySelector('input, textarea')?.focus();
        }, 100);
    }

    fermerModal() {
        this.modal.style.display = 'none';
        this.modeModale = null;
        this.itemEnEdition = null;
    }

    async sauvegarder() {
        const btn = document.getElementById('btn-sauvegarder-modale');
        const texteBtnOriginal = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin">
                <circle cx="12" cy="12" r="10"/>
            </svg>
            Enregistrement...
        `;
        
        try {
            let endpoint = '';
            let method = 'POST';
            let payload = { csrf_token: this.csrfToken };
            
            const titreInput = document.getElementById('input-titre');
            const contenuInput = document.getElementById('input-contenu');
            const statutInput = document.getElementById('input-statut');
            
            switch (this.modeModale) {
                case 'article':
                    if (!titreInput.value.trim()) throw new Error('Le titre est obligatoire');
                    payload.titre = titreInput.value.trim();
                    payload.statut = statutInput.value;
                    
                    if (this.itemEnEdition) {
                        endpoint = `cgu/articles/${this.itemEnEdition.id}`;
                        method = 'PUT';
                    } else {
                        endpoint = 'cgu/articles';
                    }
                    break;
                    
                case 'section':
                    if (!titreInput.value.trim()) throw new Error('Le titre est obligatoire');
                    payload.titre = titreInput.value.trim();
                    payload.contenu = contenuInput?.value.trim() || null;
                    
                    if (this.itemEnEdition) {
                        endpoint = `cgu/sections/${this.itemEnEdition.id}`;
                        method = 'PUT';
                    } else {
                        payload.article_id = this.articleEnCours.id;
                        endpoint = 'cgu/sections';
                    }
                    break;
                    
                case 'point':
                    if (!titreInput?.value.trim()) throw new Error('Le titre est obligatoire');
                    if (!contenuInput.value.trim()) throw new Error('Le contenu est obligatoire');
                    payload.titre = titreInput.value.trim();
                    payload.contenu = contenuInput.value.trim();
                    
                    if (this.itemEnEdition) {
                        endpoint = `cgu/points/${this.itemEnEdition.id}`;
                        method = 'PUT';
                    } else {
                        payload.section_id = this.sectionEnCours.id;
                        endpoint = 'cgu/points';
                    }
                    break;
            }
            
            await this.apiCall(endpoint, method, payload);
            
            afficherNotificationGlobale(this.itemEnEdition ? 'Modifié avec succès' : 'Créé avec succès', 'succes');
            this.fermerModal();
            this.chargerArticles();
            
        } catch (error) {
            console.error('Erreur:', error);
            afficherNotificationGlobale(error.message || 'Erreur lors de l\'enregistrement', 'erreur');
        } finally {
            btn.disabled = false;
            btn.innerHTML = texteBtnOriginal;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SUPPRESSION
    // ═══════════════════════════════════════════════════════════════════════

    async supprimer(type, item) {
        let message = '';
        let endpoint = '';
        
        switch (type) {
            case 'article':
                message = `Supprimer l'article ${item.numero} "${item.titre}" ?\n\nToutes les sections et points seront également supprimés.`;
                endpoint = `cgu/articles/${item.id}`;
                break;
            case 'section':
                message = `Supprimer la section "${item.titre}" ?\n\nTous les points associés seront également supprimés.`;
                endpoint = `cgu/sections/${item.id}`;
                break;
            case 'point':
                message = 'Supprimer ce point ?';
                endpoint = `cgu/points/${item.id}`;
                break;
        }
        
        afficherModaleConfirmation(
            'Confirmer la suppression',
            message.replace(/\n/g, '<br>'),
            async () => {
                try {
                    await this.apiCall(endpoint, 'DELETE', { csrf_token: this.csrfToken });
                    afficherNotificationGlobale('Supprimé avec succès', 'succes');
                    this.chargerArticles();
                } catch (error) {
                    afficherNotificationGlobale(error.message || 'Erreur lors de la suppression', 'erreur');
                }
            },
            'Supprimer',
            'error'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RÉORDONNANCEMENT
    // ═══════════════════════════════════════════════════════════════════════

    async deplacerArticle(article, direction) {
        const index = this.articles.findIndex(a => a.id === article.id);
        
        // Vérifier si déplacement impossible
        if (direction === 'monter' && index === 0) return;
        if (direction === 'descendre' && index === this.articles.length - 1) return;
        
        try {
            await this.apiCall(`cgu/articles/${article.id}/ordre`, 'PUT', {
                csrf_token: this.csrfToken,
                direction: direction
            });
            
            afficherNotificationGlobale('Ordre modifié', 'succes');
            this.chargerArticles();
        } catch (error) {
            afficherNotificationGlobale(error.message || 'Erreur lors du déplacement', 'erreur');
        }
    }

    async deplacerSection(article, section, direction) {
        const sections = article.sections || [];
        const index = sections.findIndex(s => s.id === section.id);
        
        // Vérifier si déplacement impossible
        if (direction === 'monter' && index === 0) return;
        if (direction === 'descendre' && index === sections.length - 1) return;
        
        try {
            await this.apiCall(`cgu/sections/${section.id}/ordre`, 'PUT', {
                csrf_token: this.csrfToken,
                direction: direction
            });
            
            afficherNotificationGlobale('Ordre modifié', 'succes');
            this.chargerArticles();
        } catch (error) {
            afficherNotificationGlobale(error.message || 'Erreur lors du déplacement', 'erreur');
        }
    }

    async deplacerPoint(section, point, direction) {
        const points = section.points || [];
        const index = points.findIndex(p => p.id === point.id);
        
        // Vérifier si déplacement impossible
        if (direction === 'monter' && index === 0) return;
        if (direction === 'descendre' && index === points.length - 1) return;
        
        try {
            await this.apiCall(`cgu/points/${point.id}/ordre`, 'PUT', {
                csrf_token: this.csrfToken,
                direction: direction
            });
            
            afficherNotificationGlobale('Ordre modifié', 'succes');
            this.chargerArticles();
        } catch (error) {
            afficherNotificationGlobale(error.message || 'Erreur lors du déplacement', 'erreur');
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // UTILITAIRES
    // ═══════════════════════════════════════════════════════════════════════

    async apiCall(endpoint, method, data = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.csrfToken
            }
        };
        
        if (data) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(obtenirUrlApi(endpoint), options);
        const result = await response.json();
        
        if (!result.ok) {
            throw new Error(result.error || 'Erreur inconnue');
        }
        
        return result;
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    mettreAJourDateModification() {
        const dateEl = document.getElementById('cgu-date-maj');
        if (!dateEl || this.articles.length === 0) return;
        
        let maxDate = new Date(0);
        
        const checkDate = (dateStr) => {
            if (!dateStr) return;
            const d = new Date(dateStr);
            if (d > maxDate) maxDate = d;
        };
        
        this.articles.forEach(article => {
            checkDate(article.updated_at);
            if (article.sections) {
                article.sections.forEach(section => {
                    checkDate(section.updated_at);
                    if (section.points) {
                        section.points.forEach(point => checkDate(point.updated_at));
                    }
                });
            }
        });
        
        if (maxDate.getTime() > 0) {
            dateEl.textContent = maxDate.toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // GESTION DES VERSIONS ARCHIVÉES
    // ═══════════════════════════════════════════════════════════════════════

    async chargerVersions() {
        const container = document.getElementById('cgu-versions-liste');
        if (!container) return;

        try {
            const response = await fetch(obtenirUrlApi('cgu/versions'));
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.ok) {
                throw new Error('Erreur lors du chargement des versions');
            }
            
            this.afficherVersions(data.versions || []);
            
        } catch (error) {
            console.error('Erreur lors du chargement des versions:', error);
            container.innerHTML = '<p class="cgu-versions-vide">Impossible de charger les versions archivées.</p>';
        }
    }

    afficherVersions(versions) {
        const container = document.getElementById('cgu-versions-liste');
        if (!container) return;
        
        if (versions.length === 0) {
            container.innerHTML = `
                <div class="cgu-versions-vide">
                    <p>Aucune version archivée pour le moment.</p>
                    <p style="font-size: 0.85rem; margin-top: 0.5rem;">
                        Les versions sont créées automatiquement lors des modifications des CGU.
                    </p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = versions.map((v, index) => `
            <div class="cgu-version-item" data-version-id="${v.id}">
                <div class="cgu-version-info">
                    <div class="cgu-version-date">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px; margin-right: 4px;">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        CGU du ${this.escapeHtml(v.date_formatee)}
                        ${index === 0 ? '<span class="cgu-version-badge cgu-version-badge--actuelle">Actuelle</span>' : ''}
                    </div>
                    <div class="cgu-version-taille">${this.escapeHtml(v.taille_formatee)}</div>
                </div>
                <div class="cgu-version-actions">
                    <a href="uploads/cgu_versions/${encodeURIComponent(v.nom_fichier)}" 
                       class="cgu-version-btn cgu-version-btn--telecharger" 
                       target="_blank"
                       download="${this.escapeHtml(v.nom_fichier)}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Télécharger
                    </a>
                    ${this.isAdmin ? `
                        <button class="cgu-version-btn cgu-version-btn--supprimer" 
                                data-action="supprimer-version" 
                                data-id="${v.id}"
                                title="Supprimer cette version">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
        
        // Événements pour la suppression (admin)
        if (this.isAdmin) {
            container.querySelectorAll('[data-action="supprimer-version"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    this.supprimerVersion(id);
                });
            });
        }
    }

    async supprimerVersion(id) {
        afficherModaleConfirmation(
            'Supprimer la version',
            'Êtes-vous sûr de vouloir supprimer cette version archivée ?<br>Cette action est irréversible.',
            async () => {
                try {
                    const response = await fetch(obtenirUrlApi(`cgu/versions/${id}`), {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify({ csrf_token: this.csrfToken })
                    });
                    
                    const data = await response.json();
                    
                    if (data.ok) {
                        afficherNotificationGlobale('Version supprimée avec succès', 'success');
                        this.chargerVersions();
                    } else {
                        throw new Error(data.error || 'Erreur lors de la suppression');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    afficherNotificationGlobale(error.message, 'error');
                }
            },
            'Supprimer',
            'error'
        );
    }
}

export default VueCGU;
