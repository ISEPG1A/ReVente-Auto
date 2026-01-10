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
        <button class="admin-tab" data-tab="activity">
            <i class="fas fa-chart-line"></i> Activité
        </button>
        <button class="admin-tab" data-tab="stats">
            <i class="fas fa-chart-pie"></i> Statistiques
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

        <!-- ONGLET: ACTIVITÉ -->
        <div class="admin-panel" data-panel="activity">
            <div class="admin-panel__header">
                <h2><i class="fas fa-chart-line"></i> Activité récente</h2>
                <div class="admin-panel__actions">
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
                    <select id="limite-activite" class="admin-filter">
                        <option value="10">10 dernières</option>
                        <option value="20" selected>20 dernières</option>
                        <option value="50">50 dernières</option>
                        <option value="100">100 dernières</option>
                    </select>
                    <button id="btn-filtrer-activite" class="bouton bouton--secondaire">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                </div>
            </div>

            <div class="activity-timeline" id="activity-timeline">
                <div class="activity-item activity-item--loading">
                    <i class="fas fa-spinner fa-spin"></i> Chargement de l'activité...
                </div>
            </div>
        </div>

        <!-- ONGLET: STATISTIQUES -->
        <div class="admin-panel" data-panel="stats">
            <div class="admin-panel__header">
                <h2><i class="fas fa-chart-pie"></i> Statistiques détaillées</h2>
            </div>

            <div class="stats-grid">
                <div class="stats-card">
                    <h3><i class="fas fa-trophy"></i> Top 10 des marques</h3>
                    <div id="chart-top-brands" class="chart-container">
                        <i class="fas fa-spinner fa-spin"></i> Chargement...
                    </div>
                </div>

                <div class="stats-card">
                    <h3><i class="fas fa-car-side"></i> Répartition par type</h3>
                    <div id="chart-vehicle-types" class="chart-container">
                        <i class="fas fa-spinner fa-spin"></i> Chargement...
                    </div>
                </div>

                <div class="stats-card stats-card--wide">
                    <h3><i class="fas fa-chart-area"></i> Évolution sur 7 jours</h3>
                    <div id="chart-evolution" class="chart-container">
                        <i class="fas fa-spinner fa-spin"></i> Chargement...
                    </div>
                </div>
            </div>
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

<script type="module">
    import VueAdmin from './assets/js/modules/admin/VueAdmin.js';
    
    document.addEventListener('DOMContentLoaded', () => {
        const admin = new VueAdmin();
        admin.initialiser();
    });
</script>
