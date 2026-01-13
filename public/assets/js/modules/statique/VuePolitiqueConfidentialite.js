/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VuePolitiqueConfidentialite.js - GESTION DE LA PAGE POLITIQUE DE CONFIDENTIALITÉ
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce module JavaScript gère toute la logique de la page :
 * - Chargement des sections depuis l'API
 * - Affichage des sections
 * - Interface d'administration pour les admins (CRUD)
 * - Réordonnancement des sections
 * 
 * @author  mat
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { afficherNotificationGlobale, afficherModaleConfirmation, obtenirUrlApi } from '../../application.js';

class VuePolitiqueConfidentialite {
    /**
     * Constructeur
     */
    constructor() {
        this.sections = [];
        this.isAdmin = false;
        this.csrfToken = null;
        this.modeEdition = false;
        this.sectionEnEdition = null;
        
        // Éléments DOM
        this.container = document.getElementById('pc-sections-container');
        this.loadingEl = document.getElementById('pc-loading');
        this.errorEl = document.getElementById('pc-error');
        this.btnModeEdition = document.getElementById('btn-mode-edition-pc');
        this.modaleEdition = document.getElementById('pc-modale-edition');
        
        this.init();
    }

    /**
     * Initialisation
     */
    async init() {
        await this.chargerSections();
        this.initEventListeners();
    }

    /**
     * Charge les sections depuis l'API
     */
    async chargerSections() {
        try {
            this.afficherChargement(true);
            
            const response = await fetch(obtenirUrlApi('politique-confidentialite'));
            const data = await response.json();
            
            if (data.ok) {
                this.sections = data.sections || [];
                this.isAdmin = data.isAdmin || false;
                this.csrfToken = data.csrf_token || null;
                
                this.afficherSections();
                this.gererAffichageAdmin();
            } else {
                throw new Error(data.error || 'Erreur de chargement');
            }
        } catch (error) {
            console.error('Erreur chargement Politique de Confidentialité:', error);
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
     * Affiche les sections
     */
    afficherSections() {
        if (!this.container) return;
        
        if (this.sections.length === 0) {
            this.container.innerHTML = `
                <div class="pc-vide">
                    <p>Aucune section pour le moment.</p>
                    ${this.isAdmin && this.modeEdition ? `
                        <button class="bouton bouton--primaire" data-action="ajouter">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Ajouter une section
                        </button>
                    ` : ''}
                </div>
            `;
            return;
        }
        
        let html = '';
        
        this.sections.forEach((section, index) => {
            const estPremier = index === 0;
            const estDernier = index === this.sections.length - 1;
            
            html += `
                <article class="pc-section" data-id="${section.id}">
                    <h2 class="pc-section__titre">${this.echapperHTML(section.titre)}</h2>
                    <div class="pc-section__contenu">
                        ${this.formaterTexte(section.contenu)}
                    </div>
                    ${this.isAdmin && this.modeEdition ? `
                        <div class="pc-admin-actions">
                            <button class="pc-btn pc-btn--modifier" data-action="modifier" data-id="${section.id}" title="Modifier">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Modifier
                            </button>
                            <button class="pc-btn pc-btn--supprimer" data-action="supprimer" data-id="${section.id}" title="Supprimer">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                                Supprimer
                            </button>
                            <div class="pc-btn-ordre">
                                <button class="pc-btn pc-btn--ordre" data-action="monter" data-id="${section.id}" title="Monter" ${estPremier ? 'disabled' : ''}>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="18 15 12 9 6 15"/>
                                    </svg>
                                </button>
                                <button class="pc-btn pc-btn--ordre" data-action="descendre" data-id="${section.id}" title="Descendre" ${estDernier ? 'disabled' : ''}>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    ` : ''}
                </article>
            `;
        });
        
        // Bouton ajouter en mode édition
        if (this.isAdmin && this.modeEdition) {
            html += `
                <button class="pc-btn-ajouter" data-action="ajouter">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Ajouter une section
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
                    this.supprimerSection(id);
                });
            });
            
            this.container.querySelectorAll('[data-action="monter"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = parseInt(btn.dataset.id);
                    this.deplacerSection(id, 'monter');
                });
            });
            
            this.container.querySelectorAll('[data-action="descendre"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = parseInt(btn.dataset.id);
                    this.deplacerSection(id, 'descendre');
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
     * Initialise les écouteurs d'événements globaux
     */
    initEventListeners() {
        // Bouton mode édition
        if (this.btnModeEdition) {
            this.btnModeEdition.addEventListener('click', () => {
                this.modeEdition = !this.modeEdition;
                this.btnModeEdition.classList.toggle('bouton--actif', this.modeEdition);
                this.btnModeEdition.innerHTML = this.modeEdition 
                    ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                         <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                         <circle cx="12" cy="12" r="3"/>
                       </svg>
                       Mode lecture`
                    : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                         <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                         <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                       </svg>
                       Mode édition`;
                this.afficherSections();
            });
        }
        
        // Modale
        if (this.modaleEdition) {
            // Fermer avec overlay
            this.modaleEdition.querySelector('.pc-modale__overlay')?.addEventListener('click', () => {
                this.fermerModale();
            });
            
            // Bouton fermer
            this.modaleEdition.querySelector('.pc-modale__fermer')?.addEventListener('click', () => {
                this.fermerModale();
            });
            
            // Bouton annuler
            document.getElementById('pc-form-annuler')?.addEventListener('click', () => {
                this.fermerModale();
            });
            
            // Bouton sauvegarder
            document.getElementById('pc-form-sauvegarder')?.addEventListener('click', () => {
                this.sauvegarderSection();
            });
            
            // Fermer avec Echap
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.modaleEdition.classList.contains('active')) {
                    this.fermerModale();
                }
            });
        }
    }

    /**
     * Ouvre la modale d'édition
     */
    ouvrirModale(id = null) {
        this.sectionEnEdition = id;
        const section = id ? this.sections.find(s => s.id === id) : null;
        
        // Mettre à jour le titre de la modale
        const titreModale = this.modaleEdition.querySelector('.pc-modale__titre');
        if (titreModale) {
            titreModale.textContent = section ? 'Modifier la section' : 'Nouvelle section';
        }
        
        // Remplir les champs
        const inputTitre = document.getElementById('pc-input-titre');
        const inputContenu = document.getElementById('pc-input-contenu');
        
        if (inputTitre) inputTitre.value = section?.titre || '';
        if (inputContenu) inputContenu.value = section?.contenu || '';
        
        // Afficher la modale
        this.modaleEdition.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus sur le premier champ
        setTimeout(() => inputTitre?.focus(), 100);
    }

    /**
     * Ferme la modale
     */
    fermerModale() {
        this.modaleEdition.classList.remove('active');
        document.body.style.overflow = '';
        this.sectionEnEdition = null;
    }

    /**
     * Sauvegarde une section (création ou modification)
     */
    async sauvegarderSection() {
        const inputTitre = document.getElementById('pc-input-titre');
        const inputContenu = document.getElementById('pc-input-contenu');
        
        const titre = inputTitre?.value.trim();
        const contenu = inputContenu?.value.trim();
        
        if (!titre || !contenu) {
            afficherNotificationGlobale('Veuillez remplir tous les champs', 'error');
            return;
        }
        
        const isCreation = !this.sectionEnEdition;
        const url = isCreation 
            ? obtenirUrlApi('politique-confidentialite')
            : obtenirUrlApi(`politique-confidentialite/${this.sectionEnEdition}`);
        
        try {
            const response = await fetch(url, {
                method: isCreation ? 'POST' : 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    titre,
                    contenu,
                    csrf_token: this.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.ok) {
                afficherNotificationGlobale(
                    isCreation ? 'Section créée avec succès' : 'Section modifiée avec succès',
                    'success'
                );
                this.fermerModale();
                await this.chargerSections();
            } else {
                throw new Error(data.error || 'Erreur lors de la sauvegarde');
            }
        } catch (error) {
            console.error('Erreur sauvegarde:', error);
            afficherNotificationGlobale(error.message, 'error');
        }
    }

    /**
     * Supprime une section
     */
    async supprimerSection(id) {
        const section = this.sections.find(s => s.id === id);
        if (!section) return;
        
        afficherModaleConfirmation(
            'Supprimer la section',
            `Êtes-vous sûr de vouloir supprimer cette section ?<br><br><em>"${this.echapperHTML(section.titre)}"</em>`,
            async () => {
                try {
                    const response = await fetch(obtenirUrlApi(`politique-confidentialite/${id}`), {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify({
                            csrf_token: this.csrfToken
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.ok) {
                        afficherNotificationGlobale('Section supprimée avec succès', 'success');
                        await this.chargerSections();
                    } else {
                        throw new Error(data.error || 'Erreur lors de la suppression');
                    }
                } catch (error) {
                    console.error('Erreur suppression:', error);
                    afficherNotificationGlobale(error.message, 'error');
                }
            },
            'Supprimer',
            'error'
        );
    }

    /**
     * Déplace une section
     */
    async deplacerSection(id, direction) {
        try {
            const response = await fetch(obtenirUrlApi(`politique-confidentialite/${id}/ordre`), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    direction,
                    csrf_token: this.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.ok) {
                await this.chargerSections();
            } else {
                throw new Error(data.error || 'Erreur lors du déplacement');
            }
        } catch (error) {
            console.error('Erreur déplacement:', error);
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

export default VuePolitiqueConfidentialite;
