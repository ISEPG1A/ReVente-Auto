/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VueFAQ.js - GESTION DE LA PAGE FAQ (Questions Fréquentes)
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce module JavaScript gère toute la logique de la page FAQ :
 * - Chargement des questions depuis l'API /api/faq
 * - Affichage en accordéon
 * - Interface d'administration pour les admins (CRUD)
 * - Réordonnancement des questions
 * 
 * @author  mat
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { afficherNotificationGlobale, afficherModaleConfirmation, obtenirUrlApi } from '../../application.js';

class VueFAQ {
    /**
     * Constructeur
     */
    constructor() {
        this.questions = [];
        this.isAdmin = false;
        this.csrfToken = null;
        this.modeEdition = false;
        this.questionsOuvertes = new Set();
        
        // Éléments DOM
        this.container = document.getElementById('faq-questions-container');
        this.loadingEl = document.getElementById('faq-loading');
        this.errorEl = document.getElementById('faq-error');
        this.btnModeEdition = document.getElementById('btn-mode-edition-faq');
        this.modaleEdition = document.getElementById('faq-modale-edition');
        
        this.init();
    }

    /**
     * Initialisation
     */
    async init() {
        await this.chargerQuestions();
        this.initEventListeners();
    }

    /**
     * Charge les questions depuis l'API
     */
    async chargerQuestions() {
        try {
            this.afficherChargement(true);
            
            const response = await fetch(obtenirUrlApi('faq'));
            const data = await response.json();
            
            if (data.ok) {
                this.questions = data.questions || [];
                this.isAdmin = data.isAdmin || false;
                this.csrfToken = data.csrf_token || null;
                
                this.afficherQuestions();
                this.gererAffichageAdmin();
            } else {
                throw new Error(data.error || 'Erreur de chargement');
            }
        } catch (error) {
            console.error('Erreur chargement FAQ:', error);
            this.afficherErreur(true);
        } finally {
            this.afficherChargement(false);
        }
    }

    /**
     * Affiche/masque le chargement
     */
    afficherChargement(afficher) {
        if (this.loadingEl) {
            this.loadingEl.style.display = afficher ? 'flex' : 'none';
        }
        if (this.container) {
            this.container.style.display = afficher ? 'none' : 'block';
        }
    }

    /**
     * Affiche/masque l'erreur
     */
    afficherErreur(afficher) {
        if (this.errorEl) {
            this.errorEl.style.display = afficher ? 'block' : 'none';
        }
    }

    /**
     * Gère l'affichage des éléments admin
     */
    gererAffichageAdmin() {
        if (this.btnModeEdition) {
            this.btnModeEdition.style.display = this.isAdmin ? 'flex' : 'none';
        }
    }

    /**
     * Affiche les questions dans l'accordéon
     */
    afficherQuestions() {
        if (!this.container) return;
        
        if (this.questions.length === 0) {
            this.container.innerHTML = `
                <div class="faq-vide">
                    <p>Aucune question pour le moment.</p>
                    ${this.isAdmin && this.modeEdition ? `
                        <button class="bouton bouton--primaire" data-action="ajouter">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Ajouter une question
                        </button>
                    ` : ''}
                </div>
            `;
            return;
        }
        
        let html = '';
        
        this.questions.forEach((q, index) => {
            const estOuvert = this.questionsOuvertes.has(q.id);
            const estPremier = index === 0;
            const estDernier = index === this.questions.length - 1;
            
            html += `
                <div class="faq-item ${estOuvert ? 'active' : ''}" data-id="${q.id}">
                    <button class="faq-question" aria-expanded="${estOuvert}" data-action="toggle" data-id="${q.id}">
                        <span class="faq-question__text">${this.echapperHTML(q.question)}</span>
                        <span class="faq-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer__content">
                            ${this.formaterTexte(q.reponse)}
                        </div>
                        ${this.isAdmin && this.modeEdition ? `
                            <div class="faq-admin-actions">
                                <button class="faq-btn faq-btn--modifier" data-action="modifier" data-id="${q.id}" title="Modifier">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                    Modifier
                                </button>
                                <button class="faq-btn faq-btn--supprimer" data-action="supprimer" data-id="${q.id}" title="Supprimer">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    </svg>
                                    Supprimer
                                </button>
                                <div class="faq-btn-ordre">
                                    <button class="faq-btn faq-btn--ordre" data-action="monter" data-id="${q.id}" title="Monter" ${estPremier ? 'disabled' : ''}>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="18 15 12 9 6 15"/>
                                        </svg>
                                    </button>
                                    <button class="faq-btn faq-btn--ordre" data-action="descendre" data-id="${q.id}" title="Descendre" ${estDernier ? 'disabled' : ''}>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6 9 12 15 18 9"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        // Bouton ajouter en mode édition
        if (this.isAdmin && this.modeEdition) {
            html += `
                <button class="faq-btn-ajouter" data-action="ajouter">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Ajouter une question
                </button>
            `;
        }
        
        this.container.innerHTML = html;
        this.attacherEvenements();
    }

    /**
     * Attache les événements aux éléments
     */
    attacherEvenements() {
        if (!this.container) return;
        
        // Toggle accordéon
        this.container.querySelectorAll('[data-action="toggle"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                this.toggleQuestion(id);
            });
        });
        
        // Actions admin
        if (this.isAdmin && this.modeEdition) {
            this.container.querySelectorAll('[data-action="modifier"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = parseInt(btn.dataset.id);
                    this.ouvrirModale(id);
                });
            });
            
            this.container.querySelectorAll('[data-action="supprimer"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = parseInt(btn.dataset.id);
                    this.supprimerQuestion(id);
                });
            });
            
            this.container.querySelectorAll('[data-action="monter"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = parseInt(btn.dataset.id);
                    this.deplacerQuestion(id, 'monter');
                });
            });
            
            this.container.querySelectorAll('[data-action="descendre"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = parseInt(btn.dataset.id);
                    this.deplacerQuestion(id, 'descendre');
                });
            });
            
            this.container.querySelectorAll('[data-action="ajouter"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    this.ouvrirModale(null);
                });
            });
        }
    }

    /**
     * Toggle l'ouverture/fermeture d'une question
     */
    toggleQuestion(id) {
        const item = this.container.querySelector(`.faq-item[data-id="${id}"]`);
        const btn = item?.querySelector('.faq-question');
        
        if (!item || !btn) return;
        
        const estOuvert = item.classList.contains('active');
        
        if (estOuvert) {
            item.classList.remove('active');
            btn.setAttribute('aria-expanded', 'false');
            this.questionsOuvertes.delete(id);
        } else {
            item.classList.add('active');
            btn.setAttribute('aria-expanded', 'true');
            this.questionsOuvertes.add(id);
        }
    }

    /**
     * Initialise les écouteurs d'événements globaux
     */
    initEventListeners() {
        // Bouton mode édition
        if (this.btnModeEdition) {
            this.btnModeEdition.addEventListener('click', () => {
                this.modeEdition = !this.modeEdition;
                this.btnModeEdition.classList.toggle('actif', this.modeEdition);
                this.btnModeEdition.innerHTML = this.modeEdition ? `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    Mode lecture
                ` : `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Mode édition
                `;
                this.afficherQuestions();
            });
        }
        
        // Fermeture modale
        if (this.modaleEdition) {
            this.modaleEdition.querySelector('.faq-modale__fermer')?.addEventListener('click', () => {
                this.fermerModale();
            });
            
            this.modaleEdition.querySelector('.faq-modale__overlay')?.addEventListener('click', () => {
                this.fermerModale();
            });
            
            this.modaleEdition.querySelector('#faq-form-annuler')?.addEventListener('click', () => {
                this.fermerModale();
            });
            
            this.modaleEdition.querySelector('#faq-form-sauvegarder')?.addEventListener('click', () => {
                this.sauvegarderQuestion();
            });
        }
    }

    /**
     * Ouvre la modale d'édition
     */
    ouvrirModale(id = null) {
        if (!this.modaleEdition) return;
        
        const titreModale = this.modaleEdition.querySelector('.faq-modale__titre');
        const inputQuestion = this.modaleEdition.querySelector('#faq-input-question');
        const inputReponse = this.modaleEdition.querySelector('#faq-input-reponse');
        
        if (id) {
            // Modification
            const question = this.questions.find(q => q.id === id);
            if (question) {
                titreModale.textContent = 'Modifier la question';
                inputQuestion.value = question.question;
                inputReponse.value = question.reponse;
                this.modaleEdition.dataset.id = id;
            }
        } else {
            // Création
            titreModale.textContent = 'Nouvelle question';
            inputQuestion.value = '';
            inputReponse.value = '';
            delete this.modaleEdition.dataset.id;
        }
        
        this.modaleEdition.classList.add('active');
        inputQuestion.focus();
    }

    /**
     * Ferme la modale
     */
    fermerModale() {
        if (this.modaleEdition) {
            this.modaleEdition.classList.remove('active');
        }
    }

    /**
     * Sauvegarde la question (création ou modification)
     */
    async sauvegarderQuestion() {
        const inputQuestion = this.modaleEdition.querySelector('#faq-input-question');
        const inputReponse = this.modaleEdition.querySelector('#faq-input-reponse');
        const id = this.modaleEdition.dataset.id;
        
        const question = inputQuestion.value.trim();
        const reponse = inputReponse.value.trim();
        
        if (!question) {
            afficherNotificationGlobale('La question est obligatoire', 'error');
            return;
        }
        if (!reponse) {
            afficherNotificationGlobale('La réponse est obligatoire', 'error');
            return;
        }
        
        try {
            const url = id ? obtenirUrlApi(`faq/${id}`) : obtenirUrlApi('faq');
            const method = id ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    question: question,
                    reponse: reponse,
                    csrf_token: this.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.ok) {
                afficherNotificationGlobale(id ? 'Question modifiée' : 'Question créée', 'success');
                this.fermerModale();
                await this.chargerQuestions();
            } else {
                throw new Error(data.error || 'Erreur lors de la sauvegarde');
            }
        } catch (error) {
            console.error('Erreur:', error);
            afficherNotificationGlobale(error.message, 'error');
        }
    }

    /**
     * Supprime une question
     */
    supprimerQuestion(id) {
        const question = this.questions.find(q => q.id === id);
        if (!question) return;
        
        afficherModaleConfirmation(
            'Supprimer la question',
            `Êtes-vous sûr de vouloir supprimer cette question ?<br><br><em>"${this.echapperHTML(question.question.substring(0, 100))}..."</em>`,
            async () => {
                try {
                    const response = await fetch(obtenirUrlApi(`faq/${id}`), {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify({ csrf_token: this.csrfToken })
                    });
                    
                    const data = await response.json();
                    
                    if (data.ok) {
                        afficherNotificationGlobale('Question supprimée', 'success');
                        this.questionsOuvertes.delete(id);
                        await this.chargerQuestions();
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

    /**
     * Déplace une question (monter/descendre)
     */
    async deplacerQuestion(id, direction) {
        try {
            const response = await fetch(obtenirUrlApi(`faq/${id}/ordre`), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    direction: direction,
                    csrf_token: this.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.ok) {
                afficherNotificationGlobale('Ordre modifié', 'success');
                await this.chargerQuestions();
            } else {
                throw new Error(data.error || 'Erreur lors du déplacement');
            }
        } catch (error) {
            console.error('Erreur:', error);
            afficherNotificationGlobale(error.message, 'error');
        }
    }

    /**
     * Échappe le HTML pour éviter les XSS
     */
    echapperHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * Formate le texte brut pour l'affichage HTML
     * - Échappe le HTML
     * - Convertit les sauts de ligne en <br>
     * - Convertit les tirets en début de ligne en puces
     */
    formaterTexte(str) {
        if (!str) return '';
        
        // Échapper le HTML d'abord
        let texte = this.echapperHTML(str);
        
        // Convertir les sauts de ligne en <br>
        texte = texte.replace(/\n/g, '<br>');
        
        return texte;
    }
}

export default VueFAQ;
