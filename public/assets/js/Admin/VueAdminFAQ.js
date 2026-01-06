/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE ADMIN FAQ - Gestion de l'interface d'administration
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Interface d'administration pour gérer les questions de la FAQ.
 * Permet d'ajouter, modifier, supprimer et activer/désactiver des questions.
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi, afficherMessage, echapperHTML } from '../application.js';

export default class VueAdminFAQ {
    constructor() {
        this.urlApi = obtenirUrlApi('/faq');
        this.questions = [];
        this.questionEnEdition = null;
    }

    async initialiser() {
        await this.chargerQuestions();
        this.attacherEvenements();
    }

    attacherEvenements() {
        // Bouton nouvelle question
        document.getElementById('btnNouvelleQuestion')?.addEventListener('click', () => {
            this.ouvrirModale();
        });

        // Fermeture modale
        document.getElementById('btnFermerModale')?.addEventListener('click', () => {
            this.fermerModale();
        });

        document.getElementById('btnAnnuler')?.addEventListener('click', () => {
            this.fermerModale();
        });

        // Clic en dehors de la modale
        document.getElementById('modaleEdition')?.addEventListener('click', (e) => {
            if (e.target.id === 'modaleEdition') {
                this.fermerModale();
            }
        });

        // Échap pour fermer
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.getElementById('modaleEdition')?.classList.contains('active')) {
                this.fermerModale();
            }
        });

        // Soumission formulaire
        document.getElementById('formulaireFaq')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.enregistrerQuestion();
        });
    }

    async chargerQuestions() {
        try {
            const reponse = await fetch(this.urlApi);
            const data = await reponse.json();

            if (data.ok) {
                this.questions = data.faqs || [];
                this.afficherQuestions();
            } else {
                this.afficherErreur(data.error || 'Erreur lors du chargement');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherErreur('Impossible de charger les questions');
        }
    }

    afficherQuestions() {
        const container = document.getElementById('faqListe');
        
        if (this.questions.length === 0) {
            container.innerHTML = `
                <div class="vide">
                    <p>📭 Aucune question pour le moment</p>
                    <button type="button" class="btn btn-primary" onclick="window.vueAdminFaq.ouvrirModale()">
                        Créer la première question
                    </button>
                </div>
            `;
            return;
        }

        // Grouper par catégorie
        const parCategorie = {};
        this.questions.forEach(q => {
            if (!parCategorie[q.category]) {
                parCategorie[q.category] = [];
            }
            parCategorie[q.category].push(q);
        });

        let html = '';
        Object.keys(parCategorie).sort().forEach(categorie => {
            html += `<div class="categorie-section">
                <h3 class="categorie-titre">${echapperHTML(categorie)}</h3>`;
            
            parCategorie[categorie].forEach(q => {
                const statut = q.is_active ? 
                    '<span class="badge badge-succes">✓ Active</span>' : 
                    '<span class="badge badge-inactif">✗ Inactive</span>';
                
                html += `
                    <div class="faq-item" data-id="${q.id}">
                        <div class="faq-item-header">
                            <div class="faq-info">
                                <span class="faq-ordre">#${q.display_order}</span>
                                <h4>${echapperHTML(q.question)}</h4>
                                ${statut}
                            </div>
                            <div class="faq-actions">
                                <button type="button" class="btn-icon" onclick="window.vueAdminFaq.modifier(${q.id})" title="Modifier">
                                    ✏️
                                </button>
                                <button type="button" class="btn-icon btn-toggle" onclick="window.vueAdminFaq.basculerStatut(${q.id}, ${!q.is_active})" title="${q.is_active ? 'Désactiver' : 'Activer'}">
                                    ${q.is_active ? '👁️' : '🚫'}
                                </button>
                                <button type="button" class="btn-icon btn-danger" onclick="window.vueAdminFaq.supprimer(${q.id})" title="Supprimer">
                                    🗑️
                                </button>
                            </div>
                        </div>
                        <div class="faq-item-body">
                            <p>${echapperHTML(q.answer)}</p>
                            <small class="faq-date">Modifié le ${new Date(q.updated_at).toLocaleDateString('fr-FR', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })}</small>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
        });

        container.innerHTML = html;
    }

    ouvrirModale(question = null) {
        this.questionEnEdition = question;
        const modale = document.getElementById('modaleEdition');
        const titre = document.getElementById('modaleTitre');
        const form = document.getElementById('formulaireFaq');

        if (question) {
            titre.textContent = 'Modifier la Question';
            document.getElementById('faqId').value = question.id;
            document.getElementById('faqQuestion').value = question.question;
            document.getElementById('faqAnswer').value = question.answer;
            document.getElementById('faqCategory').value = question.category;
            document.getElementById('faqOrder').value = question.display_order;
            document.getElementById('faqActive').checked = question.is_active;
        } else {
            titre.textContent = 'Nouvelle Question';
            form.reset();
            document.getElementById('faqId').value = '';
            document.getElementById('faqActive').checked = true;
            document.getElementById('faqCategory').value = 'Général';
            document.getElementById('faqOrder').value = this.questions.length;
        }

        modale.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus sur le premier champ
        setTimeout(() => {
            document.getElementById('faqQuestion')?.focus();
        }, 100);
    }

    fermerModale() {
        const modale = document.getElementById('modaleEdition');
        modale.classList.remove('active');
        document.body.style.overflow = '';
        this.questionEnEdition = null;
    }

    async enregistrerQuestion() {
        const form = document.getElementById('formulaireFaq');
        const btnEnregistrer = document.getElementById('btnEnregistrer');
        const btnTexte = btnEnregistrer.querySelector('.btn-texte');
        const btnChargement = btnEnregistrer.querySelector('.btn-chargement');

        // Validation côté client
        const question = document.getElementById('faqQuestion').value.trim();
        const answer = document.getElementById('faqAnswer').value.trim();

        if (!question || question.length < 5) {
            this.afficherErreur('La question doit contenir au moins 5 caractères');
            return;
        }

        if (!answer || answer.length < 10) {
            this.afficherErreur('La réponse doit contenir au moins 10 caractères');
            return;
        }

        // Afficher le chargement
        btnTexte.style.display = 'none';
        btnChargement.style.display = 'inline-flex';
        btnEnregistrer.disabled = true;

        const donnees = {
            question: question,
            answer: answer,
            category: document.getElementById('faqCategory').value.trim() || 'Général',
            display_order: parseInt(document.getElementById('faqOrder').value) || 0,
            is_active: document.getElementById('faqActive').checked
        };

        try {
            const id = document.getElementById('faqId').value;
            let url, method;

            if (id) {
                // Modification
                donnees.id = parseInt(id);
                url = `${this.urlApi}?action=update`;
                method = 'PUT';
            } else {
                // Création
                url = `${this.urlApi}?action=create`;
                method = 'POST';
            }

            const reponse = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(donnees)
            });

            const data = await reponse.json();

            if (data.ok) {
                this.afficherSucces(data.message || 'Question enregistrée avec succès');
                this.fermerModale();
                await this.chargerQuestions();
            } else {
                this.afficherErreur(data.error || 'Erreur lors de l\'enregistrement');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherErreur('Impossible d\'enregistrer la question');
        } finally {
            btnTexte.style.display = 'inline';
            btnChargement.style.display = 'none';
            btnEnregistrer.disabled = false;
        }
    }

    async modifier(id) {
        const question = this.questions.find(q => q.id === id);
        if (question) {
            this.ouvrirModale(question);
        }
    }

    async basculerStatut(id, activer) {
        if (!confirm(`Voulez-vous vraiment ${activer ? 'activer' : 'désactiver'} cette question ?`)) {
            return;
        }

        try {
            const reponse = await fetch(`${this.urlApi}?action=toggle`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, is_active: activer })
            });

            const data = await reponse.json();

            if (data.ok) {
                this.afficherSucces(data.message);
                await this.chargerQuestions();
            } else {
                this.afficherErreur(data.error);
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherErreur('Erreur lors du changement de statut');
        }
    }

    async supprimer(id) {
        const question = this.questions.find(q => q.id === id);
        if (!question) return;

        if (!confirm(`Voulez-vous vraiment supprimer la question :\n\n"${question.question}"\n\nCette action est irréversible.`)) {
            return;
        }

        try {
            const reponse = await fetch(`${this.urlApi}?id=${id}`, {
                method: 'DELETE'
            });

            const data = await reponse.json();

            if (data.ok) {
                this.afficherSucces('Question supprimée avec succès');
                await this.chargerQuestions();
            } else {
                this.afficherErreur(data.error);
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.afficherErreur('Erreur lors de la suppression');
        }
    }

    afficherSucces(message) {
        afficherMessage(document.getElementById('alerteContainer'), message, false);
        // Faire défiler vers le haut pour voir le message
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    afficherErreur(message) {
        afficherMessage(document.getElementById('alerteContainer'), message, true);
        // Faire défiler vers le haut pour voir le message
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}
