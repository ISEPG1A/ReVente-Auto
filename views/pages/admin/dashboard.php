<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * DASHBOARD ADMINISTRATION - Interface de gestion
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Fonctionnalités :
 * - Statistiques globales en temps réel
 * - Gestion des utilisateurs (liste, recherche, suppression)
 * - Gestion des annonces (liste, recherche, masquage, suppression)
 * - Activité récente de la plateforme
 * - Statistiques de popularité et graphiques
 * 
 * @version 2.0 - Refonte complète
 * ═══════════════════════════════════════════════════════════════════════════
 */

// $utilisateur est passé par le contrôleur via rendu()
?>

<div class="admin-container">
    <!-- En-tête -->
    <header class="admin-header">
        <div class="admin-header__content">
            <h1><i class="fas fa-shield-alt"></i> Dashboard Administration</h1>
            <p>Bienvenue, <?= htmlspecialchars($utilisateur['first_name']) ?> !</p>
        </div>
        <button id="btn-refresh" class="bouton bouton--primaire">
            <i class="fas fa-sync-alt"></i> Actualiser
        </button>
    </header>

    <!-- Statistiques globales -->
    <section class="admin-stats">
        <div class="stat-card stat-card--primary">
            <div class="stat-card__icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-card__content">
                <h3 class="stat-card__title">Utilisateurs</h3>
                <div class="stat-card__value" id="stat-users-total">-</div>
                <p class="stat-card__detail">
                    <span id="stat-users-new">-</span> nouveaux (7j)
                </p>
            </div>
        </div>

        <div class="stat-card stat-card--success">
            <div class="stat-card__icon">
                <i class="fas fa-car"></i>
            </div>
            <div class="stat-card__content">
                <h3 class="stat-card__title">Annonces</h3>
                <div class="stat-card__value" id="stat-vehicles-total">-</div>
                <p class="stat-card__detail">
                    <span id="stat-vehicles-new">-</span> nouvelles (7j)
                </p>
            </div>
        </div>

        <div class="stat-card stat-card--info">
            <div class="stat-card__icon">
                <i class="fas fa-comments"></i>
            </div>
            <div class="stat-card__content">
                <h3 class="stat-card__title">Conversations</h3>
                <div class="stat-card__value" id="stat-conversations-total">-</div>
                <p class="stat-card__detail">
                    <span id="stat-conversations-active">-</span> actives (7j)
                </p>
            </div>
        </div>

        <div class="stat-card stat-card--warning">
            <div class="stat-card__icon">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="stat-card__content">
                <h3 class="stat-card__title">Prix moyen</h3>
                <div class="stat-card__value" id="stat-price-avg">-</div>
                <p class="stat-card__detail">des annonces</p>
            </div>
        </div>
    </section>

    <!-- Navigation par onglets -->
    <nav class="admin-tabs">
        <button class="admin-tab admin-tab--active" data-tab="users">
            <i class="fas fa-users"></i> Utilisateurs
        </button>
        <button class="admin-tab" data-tab="vehicles">
            <i class="fas fa-car"></i> Annonces
        </button>
        <button class="admin-tab" data-tab="moderation">
            <i class="fas fa-gavel"></i> Vérification
            <span id="badge-moderation" class="admin-tab__badge" style="display: none;">0</span>
        </button>
        <button class="admin-tab" data-tab="contacts">
            <i class="fas fa-envelope"></i> Contacts
            <span id="badge-contacts" class="admin-tab__badge" style="display: none;">0</span>
        </button>
        <button class="admin-tab" data-tab="activity">
            <i class="fas fa-chart-line"></i> Activité
        </button>
        <button class="admin-tab" data-tab="stats">
            <i class="fas fa-chart-pie"></i> Statistiques
        </button>
        <button class="admin-tab" data-tab="legal">
            <i class="fas fa-balance-scale"></i> Légal
        </button>
        <button class="admin-tab" data-tab="equipe">
            <i class="fas fa-user-tie"></i> Équipe
        </button>
    </nav>

    <!-- Contenu des onglets -->
    <div class="admin-content">
        
        <!-- ONGLET: UTILISATEURS -->
        <div class="admin-panel admin-panel--active" data-panel="users">
            <div class="admin-panel__header">
                <h2><i class="fas fa-users"></i> Gestion des utilisateurs</h2>
                <div class="admin-panel__actions">
                    <input 
                        type="text" 
                        id="search-users" 
                        class="admin-search" 
                        placeholder="Rechercher un utilisateur..."
                    >
                    <select id="filter-users" class="admin-filter">
                        <option value="all">Tous les utilisateurs</option>
                        <option value="verified">Email vérifié</option>
                        <option value="unverified">Email non vérifié</option>
                        <option value="admin">Administrateurs</option>
                        <option value="banned">Bannis</option>
                    </select>
                </div>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Statut</th>
                            <th>Annonces</th>
                            <th>Inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="table-users-body">
                        <tr>
                            <td colspan="8" class="admin-table__loading">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="pagination-users" class="admin-pagination"></div>
        </div>

        <!-- ONGLET: VÉHICULES -->
        <div class="admin-panel" data-panel="vehicles">
            <div class="admin-panel__header">
                <h2><i class="fas fa-car"></i> Gestion des annonces</h2>
                <div class="admin-panel__actions">
                    <input 
                        type="text" 
                        id="search-vehicles" 
                        class="admin-search" 
                        placeholder="Rechercher une annonce..."
                    >
                    <select id="filter-vehicles" class="admin-filter">
                        <option value="all">Toutes les annonces</option>
                        <option value="public">Publiques</option>
                        <option value="prive">Privées</option>
                        <option value="en_attente">En attente</option>
                        <option value="refuse">Refusées</option>
                    </select>
                </div>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Véhicule</th>
                            <th>Propriétaire</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Vues</th>
                            <th>Favoris</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="table-vehicles-body">
                        <tr>
                            <td colspan="9" class="admin-table__loading">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="pagination-vehicles" class="admin-pagination"></div>
        </div>

        <!-- ONGLET: VÉRIFICATION -->
        <div class="admin-panel" data-panel="moderation">
            <div class="admin-panel__header">
                <h2><i class="fas fa-gavel"></i> Vérification des annonces</h2>
                <p class="admin-panel__subtitle">Les nouvelles annonces doivent être approuvées avant publication</p>
            </div>

            <div id="moderation-content">
                <div class="moderation-grid" id="moderation-list">
                    <div class="admin-table__loading">
                        <i class="fas fa-spinner fa-spin"></i> Chargement des annonces en attente...
                    </div>
                </div>
            </div>

            <div id="pagination-moderation" class="admin-pagination"></div>
        </div>

        <!-- ONGLET: CONTACTS -->
        <div class="admin-panel" data-panel="contacts">
            <div class="admin-panel__header">
                <h2><i class="fas fa-envelope"></i> Messages de contact</h2>
                <div class="admin-panel__actions">
                    <select id="filter-contacts" class="admin-filter">
                        <option value="all">Tous les messages</option>
                        <option value="nouveau">Nouveaux</option>
                        <option value="lu">Lus</option>
                        <option value="traite">Traités</option>
                        <option value="archive">Archivés</option>
                    </select>
                    <input type="date" id="filter-contacts-date-debut" class="admin-filter" placeholder="Date début">
                    <input type="date" id="filter-contacts-date-fin" class="admin-filter" placeholder="Date fin">
                    <button id="btn-reset-date-contacts" class="btn-reset-date" title="Réinitialiser les filtres de date" style="display: none;">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Sujet</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="table-contacts-body">
                        <tr>
                            <td colspan="7" class="admin-table__loading">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="pagination-contacts" class="admin-pagination"></div>
        </div>

        <!-- ONGLET: ACTIVITÉ -->
        <div class="admin-panel" data-panel="activity">
            <div class="admin-panel__header">
                <h2><i class="fas fa-chart-line"></i> Activité récente</h2>
                <div class="admin-panel__actions">
                    <select id="filtre-type-activite" class="admin-filter">
                        <option value="">Tous les types</option>
                        <optgroup label="Compte">
                            <option value="inscription">Inscriptions</option>
                            <option value="connexion">Connexions</option>
                            <option value="deconnexion">Déconnexions</option>
                            <option value="profil">Modifications profil</option>
                            <option value="securite">Sécurité (email/mdp)</option>
                            <option value="suppression_compte">Suppressions compte</option>
                        </optgroup>
                        <optgroup label="Annonces">
                            <option value="annonce_creation">Créations annonce</option>
                            <option value="annonce_modification">Modifications annonce</option>
                            <option value="annonce_statut">Changements statut</option>
                            <option value="annonce_suppression">Suppressions annonce</option>
                        </optgroup>
                        <optgroup label="Interactions">
                            <option value="favori">Favoris</option>
                            <option value="message">Messages</option>
                            <option value="contact">Contact</option>
                            <option value="estimation">Estimations</option>
                        </optgroup>
                        <optgroup label="Contenu légal">
                            <option value="cgu">CGU</option>
                            <option value="faq">FAQ</option>
                            <option value="politique">Politique Confidentialité</option>
                        </optgroup>
                        <optgroup label="Administration">
                            <option value="moderation">Modération</option>
                            <option value="utilisateur">Gestion utilisateurs</option>
                        </optgroup>
                    </select>
                    <input 
                        type="date" 
                        id="date-debut" 
                        class="admin-filter" 
                        placeholder="Date début"
                    >
                    <input 
                        type="date" 
                        id="date-fin" 
                        class="admin-filter" 
                        placeholder="Date fin"
                    >
                    <button id="btn-reset-date-activite" class="btn-reset-date" title="Réinitialiser les filtres" style="display: none;">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>

            <div class="activity-timeline" id="activity-timeline">
                <div class="activity-item activity-item--loading">
                    <i class="fas fa-spinner fa-spin"></i> Chargement de l'activité...
                </div>
            </div>
            
            <div id="pagination-activite" class="admin-pagination"></div>
        </div>

        <!-- ONGLET: STATISTIQUES -->
        <div class="admin-panel" data-panel="stats">
            <div class="admin-panel__header">
                <h2><i class="fas fa-chart-pie"></i> Statistiques détaillées</h2>
                <p class="admin-panel__subtitle">Analyse complète de la plateforme</p>
            </div>

            <!-- Cartes KPI principales -->
            <div class="stats-kpi-grid">
                <div class="stats-kpi-card stats-kpi-card--vues">
                    <div class="stats-kpi-card__icon"><i class="fas fa-eye"></i></div>
                    <div class="stats-kpi-card__content">
                        <span class="stats-kpi-card__value" id="kpi-total-vues">-</span>
                        <span class="stats-kpi-card__label">Vues totales</span>
                    </div>
                </div>
                <div class="stats-kpi-card stats-kpi-card--favoris">
                    <div class="stats-kpi-card__icon"><i class="fas fa-heart"></i></div>
                    <div class="stats-kpi-card__content">
                        <span class="stats-kpi-card__value" id="kpi-total-favoris">-</span>
                        <span class="stats-kpi-card__label">Favoris totaux</span>
                    </div>
                </div>
                <div class="stats-kpi-card stats-kpi-card--contacts">
                    <div class="stats-kpi-card__icon"><i class="fas fa-envelope"></i></div>
                    <div class="stats-kpi-card__content">
                        <span class="stats-kpi-card__value" id="kpi-total-contacts">-</span>
                        <span class="stats-kpi-card__label">Contacts envoyés</span>
                    </div>
                </div>
                <div class="stats-kpi-card stats-kpi-card--score">
                    <div class="stats-kpi-card__icon"><i class="fas fa-brain"></i></div>
                    <div class="stats-kpi-card__content">
                        <span class="stats-kpi-card__value" id="kpi-score-ia">-</span>
                        <span class="stats-kpi-card__label">Score IA moyen</span>
                    </div>
                </div>
            </div>

            <!-- Grille statistiques -->
            <div class="stats-grid-advanced">
                <!-- Ligne 1 : Marques + Types -->
                <div class="stats-card">
                    <h3><i class="fas fa-trophy"></i> Top 10 des marques</h3>
                    <div id="chart-top-brands" class="chart-container chart-container--loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Chargement...</span>
                    </div>
                </div>

                <div class="stats-card">
                    <h3><i class="fas fa-car-side"></i> Répartition par type</h3>
                    <div id="chart-vehicle-types" class="chart-container chart-container--loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Chargement...</span>
                    </div>
                </div>

                <!-- Ligne 2 : Prix par type + Distribution prix -->
                <div class="stats-card">
                    <h3><i class="fas fa-euro-sign"></i> Prix moyen par type</h3>
                    <div id="chart-prix-type" class="chart-container chart-container--loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Chargement...</span>
                    </div>
                </div>

                <div class="stats-card">
                    <h3><i class="fas fa-tags"></i> Distribution des prix</h3>
                    <div id="chart-distribution-prix" class="chart-container chart-container--loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Chargement...</span>
                    </div>
                </div>

                <!-- Ligne 3 : Carburants + Années -->
                <div class="stats-card">
                    <h3><i class="fas fa-gas-pump"></i> Carburants</h3>
                    <div id="chart-carburants" class="chart-container chart-container--loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Chargement...</span>
                    </div>
                </div>

                <div class="stats-card">
                    <h3><i class="fas fa-calendar-alt"></i> Distribution par année</h3>
                    <div id="chart-annees" class="chart-container chart-container--loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Chargement...</span>
                    </div>
                </div>

                <!-- Ligne 4 : Top annonces + Score IA -->
                <div class="stats-card">
                    <h3><i class="fas fa-fire"></i> Annonces les plus populaires</h3>
                    <div id="chart-top-annonces" class="chart-container chart-container--loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Chargement...</span>
                    </div>
                </div>

                <div class="stats-card">
                    <h3><i class="fas fa-brain"></i> Distribution Score IA</h3>
                    <div id="chart-score-ia" class="chart-container chart-container--loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Chargement...</span>
                    </div>
                </div>

                <!-- Ligne 5 : Géographie + Taux conversion -->
                <div class="stats-card">
                    <h3><i class="fas fa-map-marker-alt"></i> Top départements</h3>
                    <div id="chart-geo" class="chart-container chart-container--loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Chargement...</span>
                    </div>
                </div>

                <div class="stats-card">
                    <h3><i class="fas fa-funnel-dollar"></i> Taux de conversion</h3>
                    <div id="chart-conversion" class="chart-container chart-container--loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Chargement...</span>
                    </div>
                </div>

                <!-- Ligne 6 : Évolution sur 7 jours (large) -->
                <div class="stats-card stats-card--wide">
                    <h3><i class="fas fa-chart-area"></i> Évolution sur 7 jours</h3>
                    <div id="chart-evolution" class="chart-container chart-container--evolution chart-container--loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Chargement...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ONGLET: LÉGAL -->
        <div class="admin-panel" data-panel="legal">
            <div class="admin-panel__header">
                <h2><i class="fas fa-balance-scale"></i> Gestion des pages légales</h2>
                <p class="admin-panel__subtitle">Modifiez le contenu des pages légales de la plateforme</p>
            </div>

            <div class="legal-grid">
                <!-- Bloc FAQ -->
                <a href="faq" class="legal-card">
                    <div class="legal-card__icon legal-card__icon--faq">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="legal-card__content">
                        <h3>FAQ</h3>
                        <p>Gérer les questions fréquemment posées</p>
                    </div>
                    <div class="legal-card__arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>

                <!-- Bloc CGU -->
                <a href="cgu" class="legal-card">
                    <div class="legal-card__icon legal-card__icon--cgu">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="legal-card__content">
                        <h3>Conditions Générales d'Utilisation</h3>
                        <p>Modifier les CGU de la plateforme</p>
                    </div>
                    <div class="legal-card__arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>

                <!-- Bloc Politique de Confidentialité -->
                <a href="politique-confidentialite" class="legal-card">
                    <div class="legal-card__icon legal-card__icon--privacy">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="legal-card__content">
                        <h3>Politique de Confidentialité</h3>
                        <p>Gérer la politique de protection des données</p>
                    </div>
                    <div class="legal-card__arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>
            </div>
        </div>

        <!-- ONGLET: ÉQUIPE -->
        <div class="admin-panel" data-panel="equipe">
            <div class="admin-panel__header">
                <h2><i class="fas fa-user-tie"></i> Gestion de l'équipe</h2>
                <p class="admin-panel__subtitle">Modifiez les postes des membres de l'équipe (administrateurs)</p>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Membre</th>
                            <th>Email</th>
                            <th>Poste actuel</th>
                            <th>Membre depuis</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="table-equipe-body">
                        <tr>
                            <td colspan="5" class="admin-table__loading">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="pagination-equipe" class="admin-pagination"></div>
        </div>
    </div>
</div>

<!-- Modale de confirmation -->
<div id="modal-confirm" class="modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Confirmer l'action</h3>
            <button class="modal-close" id="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p id="modal-message">Êtes-vous sûr de vouloir effectuer cette action ?</p>
        </div>
        <div class="modal-footer">
            <button class="bouton bouton--secondaire" id="modal-cancel">Annuler</button>
            <button class="bouton bouton--danger" id="modal-confirm-btn">Confirmer</button>
        </div>
    </div>
</div>

<!-- Modale Contact -->
<div id="modal-contact" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="window.admin.fermerModaleContact()"></div>
    <div class="modal-content modal-content--large" style="border-radius: 16px; overflow: hidden;">
        <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 20px 25px;">
            <h3 id="modal-contact-title" style="color: white; margin: 0;"><i class="fas fa-envelope" style="margin-right: 10px;"></i> Message de contact</h3>
            <button class="modal-close" onclick="window.admin.fermerModaleContact()" style="color: white; background: rgba(255,255,255,0.2); border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" style="padding: 25px; overflow-x: hidden;">
            <div class="contact-details">
                <div class="contact-info" style="background: var(--fond-secondaire); padding: 15px; border-radius: 12px; margin-bottom: 20px; word-wrap: break-word; overflow-wrap: break-word;">
                    <p style="margin: 5px 0; word-wrap: break-word; overflow-wrap: break-word;">
                        <strong><i class="fas fa-user" style="width: 20px; color: #3b82f6;"></i> Nom :</strong> 
                        <span id="contact-nom" style="word-wrap: break-word; overflow-wrap: break-word;"></span>
                    </p>
                    <p style="margin: 5px 0; word-wrap: break-word; overflow-wrap: break-word;">
                        <strong><i class="fas fa-envelope" style="width: 20px; color: #3b82f6;"></i> Email :</strong> 
                        <span id="contact-email" style="word-wrap: break-word; overflow-wrap: break-word;"></span>
                    </p>
                    <p style="margin: 5px 0;"><strong><i class="fas fa-clock" style="width: 20px; color: #3b82f6;"></i> Date :</strong> <span id="contact-date"></span></p>
                    <p style="margin: 5px 0; word-wrap: break-word; overflow-wrap: break-word;"><strong><i class="fas fa-tag" style="width: 20px; color: #3b82f6;"></i> Sujet :</strong> <span id="contact-sujet" style="word-wrap: break-word; overflow-wrap: break-word; display: inline-block; max-width: 100%;"></span></p>
                </div>
                <div class="contact-message" style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 10px; color: var(--texte-primaire);"><i class="fas fa-comment-alt" style="margin-right: 8px; color: #3b82f6;"></i> Message</h4>
                    <div id="contact-message-content" class="message-box" style="background: var(--fond-secondaire); padding: 15px; border-radius: 12px; border-left: 4px solid #3b82f6; white-space: pre-wrap; min-height: 100px; max-height: 350px; overflow-y: auto; line-height: 1.6; word-wrap: break-word; overflow-wrap: break-word;"></div>
                </div>
                <div id="contact-reponse-container" class="contact-reponse">
                    <label for="contact-reponse" style="display: block; margin-bottom: 10px; font-weight: 600;"><i class="fas fa-reply" style="margin-right: 8px; color: #22c55e;"></i> Votre réponse :</label>
                    <textarea id="contact-reponse" class="form-textarea" rows="4" style="width: 100%; padding: 15px; border: 2px solid var(--bordure); border-radius: 12px; background: var(--fond-carte); color: var(--texte-primaire); font-size: 1rem; resize: vertical;" placeholder="Tapez votre réponse ici..."></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="padding: 20px 25px; background: var(--fond-secondaire); border-top: 1px solid var(--bordure); display: flex; gap: 12px; justify-content: flex-end;">
            <button id="btn-archiver-contact" class="bouton bouton--secondaire" onclick="window.admin.archiverContactDirect()" style="border-radius: 10px;">
                <i class="fas fa-archive"></i> Archiver
            </button>
            <button id="btn-repondre-contact" class="bouton bouton--primaire" onclick="window.admin.envoyerReponseContact()" style="border-radius: 10px;">
                <i class="fas fa-paper-plane"></i> Répondre
            </button>
        </div>
    </div>
</div>

<!-- Modale Vérification -->
<div id="modal-moderation" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="window.admin.fermerModaleModeration()"></div>
    <div class="modal-content modal-content--xlarge">
        <div class="modal-header">
            <h3 id="modal-moderation-title">Vérification de l'annonce</h3>
            <button class="modal-close" onclick="window.admin.fermerModaleModeration()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="moderation-details">
                <div class="moderation-images" id="moderation-images">
                    <!-- Images du véhicule -->
                </div>
                <div class="moderation-info">
                    <h4 id="moderation-vehicule-titre"></h4>
                    
                    <div class="info-grid info-grid--moderation">
                        <div class="info-item">
                            <i class="fas fa-euro-sign"></i>
                            <div>
                                <span class="info-label">Prix</span>
                                <span class="info-value" id="moderation-prix"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-calendar"></i>
                            <div>
                                <span class="info-label">Année</span>
                                <span class="info-value" id="moderation-annee"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-tachometer-alt"></i>
                            <div>
                                <span class="info-label">Kilométrage</span>
                                <span class="info-value" id="moderation-km"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-gas-pump"></i>
                            <div>
                                <span class="info-label">Carburant</span>
                                <span class="info-value" id="moderation-carburant"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-cogs"></i>
                            <div>
                                <span class="info-label">Boîte</span>
                                <span class="info-value" id="moderation-transmission"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-car"></i>
                            <div>
                                <span class="info-label">État</span>
                                <span class="info-value" id="moderation-etat"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-palette"></i>
                            <div>
                                <span class="info-label">Couleur</span>
                                <span class="info-value" id="moderation-couleur"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-horse-head"></i>
                            <div>
                                <span class="info-label">Puissance</span>
                                <span class="info-value" id="moderation-puissance"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-door-open"></i>
                            <div>
                                <span class="info-label">Portes</span>
                                <span class="info-value" id="moderation-portes"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-user-friends"></i>
                            <div>
                                <span class="info-label">Places</span>
                                <span class="info-value" id="moderation-places"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-leaf"></i>
                            <div>
                                <span class="info-label">Crit'Air</span>
                                <span class="info-value" id="moderation-critair"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clipboard-check"></i>
                            <div>
                                <span class="info-label">Contrôle Tech.</span>
                                <span class="info-value" id="moderation-ct"></span>
                            </div>
                        </div>
                        <div class="info-item info-item--full">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <span class="info-label">Localisation</span>
                                <span class="info-value" id="moderation-localisation"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-globe"></i>
                            <div>
                                <span class="info-label">Provenance</span>
                                <span class="info-value" id="moderation-provenance"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-certificate"></i>
                            <div>
                                <span class="info-label">Norme Euro</span>
                                <span class="info-value" id="moderation-norme-euro"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-fire"></i>
                            <div>
                                <span class="info-label">Consommation</span>
                                <span class="info-value" id="moderation-consommation"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-cloud"></i>
                            <div>
                                <span class="info-label">Émissions CO2</span>
                                <span class="info-value" id="moderation-emission"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-truck"></i>
                            <div>
                                <span class="info-label">Type</span>
                                <span class="info-value" id="moderation-type-vehicule"></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-star"></i>
                            <div>
                                <span class="info-label">Score IA</span>
                                <span class="info-value" id="moderation-score-ia"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="description-box">
                        <h5><i class="fas fa-align-left"></i> Description</h5>
                        <p id="moderation-description"></p>
                    </div>
                    
                    <div class="vendeur-info">
                        <h5><i class="fas fa-user"></i> Vendeur</h5>
                        <div class="vendeur-details">
                            <p><strong>Nom :</strong> <span id="moderation-vendeur-nom"></span></p>
                            <p><strong>Email :</strong> <span id="moderation-vendeur-email"></span></p>
                            <p><strong>Téléphone :</strong> <span id="moderation-vendeur-tel"></span></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="moderation-raison" id="moderation-raison-container" style="display: none;">
                <label for="moderation-raison"><i class="fas fa-comment-slash"></i> Raison du refus :</label>
                <textarea id="moderation-raison" rows="3" placeholder="Expliquer pourquoi l'annonce est refusée (photos floues, informations incorrectes, prix incohérent...)"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="bouton bouton--danger" onclick="window.admin.afficherRaisonRefus()">
                <i class="fas fa-times"></i> Refuser
            </button>
            <button class="bouton bouton--success" onclick="window.admin.approuverAnnonce()">
                <i class="fas fa-check"></i> Approuver
            </button>
        </div>
    </div>
</div>

<!-- Modale de Refus (Belle modale) -->
<div id="modal-refus" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="window.admin.fermerModaleRefus()"></div>
    <div class="modal-content" style="border-radius: 16px; overflow: hidden; max-width: 500px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 0; padding: 20px 25px;">
            <h3 style="color: white; margin: 0; font-size: 1.3rem;"><i class="fas fa-times-circle" style="margin-right: 10px;"></i> Refuser l'annonce</h3>
            <button class="modal-close" onclick="window.admin.fermerModaleRefus()" style="color: white; background: rgba(255,255,255,0.2); border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" style="padding: 25px;">
            <p style="margin-bottom: 20px; color: var(--texte-secondaire); line-height: 1.5;">
                Veuillez indiquer la raison du refus. Cette information sera envoyée au propriétaire de l'annonce par email.
            </p>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="raison-refus-input" style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--texte-primaire);">
                    <i class="fas fa-comment-slash" style="color: #ef4444; margin-right: 8px;"></i> Raison du refus
                </label>
                <textarea id="raison-refus-input" rows="4" 
                    style="width: 100%; padding: 15px; border: 2px solid var(--bordure); border-radius: 12px; background: var(--fond-carte); color: var(--texte-primaire); font-size: 1rem; resize: vertical; min-height: 100px; max-height: 250px; transition: border-color 0.2s;"
                    placeholder="Ex: Photos de mauvaise qualité, informations incomplètes, prix incorrect..."></textarea>
            </div>
        </div>
        <div class="modal-footer" style="padding: 20px 25px; background: var(--fond-secondaire); border-top: 1px solid var(--bordure); display: flex; gap: 12px; justify-content: flex-end;">
            <button class="bouton bouton--secondaire" onclick="window.admin.fermerModaleRefus()" style="border-radius: 10px; padding: 12px 20px;">
                <i class="fas fa-arrow-left"></i> Annuler
            </button>
            <button class="bouton bouton--danger" onclick="window.admin.confirmerRefus()" style="border-radius: 10px; padding: 12px 20px;">
                <i class="fas fa-times"></i> Confirmer le refus
            </button>
        </div>
    </div>
</div>

<!-- Modale Modification Poste -->
<div id="modal-poste" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="window.admin.fermerModalePoste()"></div>
    <div class="modal-content" style="border-radius: 16px; overflow: hidden; max-width: 500px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border-radius: 0; padding: 20px 25px;">
            <h3 style="color: white; margin: 0; font-size: 1.3rem;"><i class="fas fa-user-edit" style="margin-right: 10px;"></i> Modifier le poste</h3>
            <button class="modal-close" onclick="window.admin.fermerModalePoste()" style="color: white; background: rgba(255,255,255,0.2); border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" style="padding: 25px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding: 15px; background: var(--fond-secondaire); border-radius: 12px;">
                <div id="modal-poste-avatar" style="width: 50px; height: 50px; border-radius: 50%; background: var(--arriere-plan); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; overflow: hidden;">
                    <img src="assets/images/avatar-default.svg" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <div>
                    <p id="modal-poste-nom" style="margin: 0; font-weight: 600; font-size: 1.1rem;"></p>
                    <p id="modal-poste-email" style="margin: 4px 0 0; color: var(--texte-secondaire); font-size: 0.9rem;"></p>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="input-poste" style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--texte-primaire);">
                    <i class="fas fa-briefcase" style="color: #8b5cf6; margin-right: 8px;"></i> Poste / Fonction
                </label>
                <input type="text" id="input-poste" maxlength="60"
                    style="width: 100%; padding: 15px; border: 2px solid var(--bordure); border-radius: 12px; background: var(--fond-carte); color: var(--texte-primaire); font-size: 1rem; transition: border-color 0.2s;"
                    placeholder="Ex: Développeur Full Stack, Chef de projet, etc.">
                <input type="hidden" id="input-poste-user-id">
                <p style="margin: 10px 0 0; font-size: 0.85rem; color: var(--texte-secondaire);">
                    Ce poste sera affiché sur la page Équipe du site.
                </p>
            </div>
        </div>
        <div class="modal-footer" style="padding: 20px 25px; background: var(--fond-secondaire); border-top: 1px solid var(--bordure); display: flex; gap: 12px; justify-content: flex-end;">
            <button class="bouton bouton--secondaire" onclick="window.admin.fermerModalePoste()" style="border-radius: 10px; padding: 12px 20px;">
                <i class="fas fa-times"></i> Annuler
            </button>
            <button class="bouton bouton--primaire" onclick="window.admin.sauvegarderPoste()" style="border-radius: 10px; padding: 12px 20px;">
                <i class="fas fa-save"></i> Enregistrer
            </button>
        </div>
    </div>
</div>

<script type="module">
    import VueAdmin from './assets/js/modules/admin/VueAdmin.js';
    
    document.addEventListener('DOMContentLoaded', () => {
        try {
            const admin = new VueAdmin();
            admin.initialiser();
        } catch (error) {
            console.error('Erreur initialisation admin:', error);
        }
    });
</script>
