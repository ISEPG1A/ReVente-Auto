<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PAGE ADMINISTRATION FAQ
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Interface d'administration pour gérer les questions de la FAQ.
 * Réservé aux administrateurs.
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Vérifier que l'utilisateur est connecté et est admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /connexion');
    exit;
}
?>

<!-- Chargement du CSS spécifique -->
<link rel="stylesheet" href="/assets/css/pages/admin_faq.css">

<div class="admin-faq-container">
    <!-- En-tête -->
    <div class="admin-header">
        <h1>🔧 Administration FAQ</h1>
        <button type="button" class="btn btn-primary" id="btnNouvelleQuestion">
            ➕ Nouvelle Question
        </button>
    </div>

    <!-- Zone d'alerte -->
    <div id="alerteContainer"></div>

    <!-- Liste des questions -->
    <div class="faq-liste" id="faqListe">
        <div class="chargement">
            <div class="spinner"></div>
            <p>Chargement des questions...</p>
        </div>
    </div>
</div>

<!-- Modale d'édition -->
<div class="modale" id="modaleEdition">
    <div class="modale-contenu">
        <div class="modale-entete">
            <h2 id="modaleTitre">Nouvelle Question</h2>
            <button type="button" class="btn-fermer" id="btnFermerModale">&times;</button>
        </div>

        <form id="formulaireFaq" class="modale-corps">
            <input type="hidden" id="faqId" name="id">

            <div class="form-group">
                <label for="faqQuestion">Question *</label>
                <textarea 
                    id="faqQuestion" 
                    name="question" 
                    rows="3" 
                    required 
                    maxlength="1000"
                    placeholder="Ex: Comment publier une annonce ?"></textarea>
            </div>

            <div class="form-group">
                <label for="faqAnswer">Réponse *</label>
                <textarea 
                    id="faqAnswer" 
                    name="answer" 
                    rows="6" 
                    required 
                    maxlength="5000"
                    placeholder="Décrivez la réponse en détail..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="faqCategory">Catégorie</label>
                    <input 
                        type="text" 
                        id="faqCategory" 
                        name="category" 
                        maxlength="100"
                        placeholder="Ex: Vente, Achat, Sécurité...">
                </div>

                <div class="form-group">
                    <label for="faqOrder">Ordre d'affichage</label>
                    <input 
                        type="number" 
                        id="faqOrder" 
                        name="display_order" 
                        min="0" 
                        value="0">
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="faqActive" name="is_active" checked>
                    <span>Question active (visible pour tous)</span>
                </label>
            </div>

            <div class="modale-pied">
                <button type="button" class="btn btn-secondaire" id="btnAnnuler">Annuler</button>
                <button type="submit" class="btn btn-primary" id="btnEnregistrer">
                    <span class="btn-texte">Enregistrer</span>
 !-- Chargement du JavaScript -->
<script type="module">
    import VueAdminFAQ from './assets/js/Admin/VueAdminFAQ.js';

    // Initialiser l'admin FAQtton>
            </div>
        </form>
    </div>
</div>

<script type="module">
    import { obtenirUrlApi, afficherMessage, echapperHTML } from './assets/js/application.js';

    class AdminFAQ {
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
                        <button type="button" class="btn btn-primary" onclick="window.adminFaq.ouvrirModale()">
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
                        '<span class="badge badge-succes">Active</span>' : 
                        '<span class="badge badge-inactif">Inactive</span>';
                    
                    html += `
                        <div class="faq-item" data-id="${q.id}">
                            <div class="faq-item-header">
                                <div class="faq-info">
                                    <span class="faq-ordre">#${q.display_order}</span>
                                    <h4>${echapperHTML(q.question)}</h4>
                                    ${statut}
                                </div>
                                <div class="faq-actions">
                                    <button type="button" class="btn-icon" onclick="window.adminFaq.modifier(${q.id})" title="Modifier">
                                        ✏️
                                    </button>
                                    <button type="button" class="btn-icon btn-toggle" onclick="window.adminFaq.basculerStatut(${q.id}, ${!q.is_active})" title="${q.is_active ? 'Désactiver' : 'Activer'}">
                                        ${q.is_active ? '👁️' : '🚫'}
                                    </button>
                                    <button type="button" class="btn-icon btn-danger" onclick="window.adminFaq.supprimer(${q.id})" title="Supprimer">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                            <div class="faq-item-body">
                                <p>${echapperHTML(q.answer)}</p>
                                <small class="faq-date">Modifié le ${new Date(q.updated_at).toLocaleDateString('fr-FR')}</small>
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
            }

            modale.classList.add('active');
            document.body.style.overflow = 'hidden';
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

            // Afficher le chargement
            btnTexte.style.display = 'none';
            btnChargement.style.display = 'inline-flex';
            btnEnregistrer.disabled = true;

            const donnees = {
                question: document.getElementById('faqQuestion').value.trim(),
                answer: document.getElementById('faqAnswer').value.trim(),
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

            if (!confirm(`Voulez-vous vraiment supprimer la question :\n"${question.question}" ?\n\nCette action est irréversible.`)) {
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
        }

        afficherErreur(message) {
            afficherMessage(document.getElementById('alerteContainer'), message, true);
        }
    }

    window.vueAdminFaq = new VueAdminFAQ();
    document.addEventListener('DOMContentLoaded', () => {
        window.vueAdminFaq.initialiser();
    });
</script>
