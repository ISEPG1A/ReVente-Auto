<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PAGE DASHBOARD ADMINISTRATION
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Dashboard complet pour les administrateurs avec :
 * - Statistiques globales (utilisateurs, véhicules, messages, offres)
 * - Listes détaillées avec pagination
 * - Flux d'activité en temps réel
 * - Graphiques de tendances
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Récupérer les informations de l'utilisateur admin
$utilisateur = GestionnaireSession::obtenirUtilisateur();
?>

<div class="admin-dashboard">
    <!-- En-tête du dashboard -->
    <header class="admin-header">
        <div class="admin-header__content">
            <div class="admin-header__title">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard Administration</h1>
                <p class="admin-header__subtitle">
                    Bienvenue, <?= htmlspecialchars($utilisateur['first_name'] ?? 'Admin') ?> !
                    <span class="admin-header__date"><?php
                        $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
                        $mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                        $date = getdate();
                        echo ucfirst($jours[$date['wday']]) . ' ' . $date['mday'] . ' ' . $mois[$date['mon']-1] . ' ' . $date['year'];
                    ?></span>
                </p>
            </div>
            <div class="admin-header__actions">
                <button id="btn-refresh-data" class="btn btn--primary" title="Actualiser les données">
                    <i class="fas fa-sync-alt"></i> Actualiser
                </button>
            </div>
        </div>
    </header>

    <!-- Cartes de statistiques principales -->
    <section class="admin-stats-grid" id="stats-principales">
        <!-- Utilisateurs -->
        <div class="admin-stat-card admin-stat-card--users">
            <div class="admin-stat-card__icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="admin-stat-card__content">
                <span class="admin-stat-card__value" id="stat-users-total">--</span>
                <span class="admin-stat-card__label">Utilisateurs</span>
                <span class="admin-stat-card__detail" id="stat-users-detail">
                    <span class="stat-verified">-- vérifiés</span> / 
                    <span class="stat-unverified">-- non vérifiés</span>
                </span>
            </div>
            <div class="admin-stat-card__badge" id="stat-users-new">
                <i class="fas fa-arrow-up"></i> +-- (24h)
            </div>
        </div>

        <!-- Véhicules -->
        <div class="admin-stat-card admin-stat-card--vehicles">
            <div class="admin-stat-card__icon">
                <i class="fas fa-car"></i>
            </div>
            <div class="admin-stat-card__content">
                <span class="admin-stat-card__value" id="stat-vehicles-total">--</span>
                <span class="admin-stat-card__label">Annonces</span>
                <span class="admin-stat-card__detail" id="stat-vehicles-detail">
                    Prix moyen : <strong>-- €</strong>
                </span>
            </div>
            <div class="admin-stat-card__badge" id="stat-vehicles-new">
                <i class="fas fa-arrow-up"></i> +-- (24h)
            </div>
        </div>

        <!-- Messages -->
        <div class="admin-stat-card admin-stat-card--messages">
            <div class="admin-stat-card__icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="admin-stat-card__content">
                <span class="admin-stat-card__value" id="stat-messages-total">--</span>
                <span class="admin-stat-card__label">Messages</span>
                <span class="admin-stat-card__detail" id="stat-messages-detail">
                    <span class="stat-unread">-- non lus</span>
                </span>
            </div>
            <div class="admin-stat-card__badge" id="stat-messages-new">
                <i class="fas fa-arrow-up"></i> +-- (24h)
            </div>
        </div>

        <!-- Offres -->
        <div class="admin-stat-card admin-stat-card--offers">
            <div class="admin-stat-card__icon">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div class="admin-stat-card__content">
                <span class="admin-stat-card__value" id="stat-offers-total">--</span>
                <span class="admin-stat-card__label">Offres</span>
                <span class="admin-stat-card__detail" id="stat-offers-detail">
                    Taux acceptation : <strong>--%</strong>
                </span>
            </div>
            <div class="admin-stat-card__badge" id="stat-offers-new">
                <i class="fas fa-arrow-up"></i> +-- (24h)
            </div>
        </div>

        <!-- Favoris -->
        <div class="admin-stat-card admin-stat-card--favorites">
            <div class="admin-stat-card__icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="admin-stat-card__content">
                <span class="admin-stat-card__value" id="stat-favorites-total">--</span>
                <span class="admin-stat-card__label">Favoris</span>
                <span class="admin-stat-card__detail" id="stat-favorites-detail">
                    +-- aujourd'hui
                </span>
            </div>
        </div>

        <!-- Conversations -->
        <div class="admin-stat-card admin-stat-card--conversations">
            <div class="admin-stat-card__icon">
                <i class="fas fa-comments"></i>
            </div>
            <div class="admin-stat-card__content">
                <span class="admin-stat-card__value" id="stat-conversations-total">--</span>
                <span class="admin-stat-card__label">Conversations</span>
                <span class="admin-stat-card__detail" id="stat-conversations-detail">
                    -- actives (24h)
                </span>
            </div>
        </div>
    </section>

    <!-- Conteneur principal avec sections -->
    <div class="admin-main-content">
        <!-- Colonne gauche : Activité récente -->
        <section class="admin-section admin-activity">
            <div class="admin-section__header">
                <h2><i class="fas fa-stream"></i> Activité récente</h2>
                <button class="btn btn--sm btn--ghost" id="btn-more-activity">
                    Voir plus <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="admin-section__content">
                <div class="admin-timeline" id="timeline-activite">
                    <div class="admin-loading">
                        <i class="fas fa-spinner fa-spin"></i> Chargement...
                    </div>
                </div>
            </div>
        </section>

        <!-- Colonne droite : Panneaux d'informations -->
        <div class="admin-panels">
            <!-- Derniers utilisateurs -->
            <section class="admin-section admin-panel">
                <div class="admin-section__header">
                    <h2><i class="fas fa-user-plus"></i> Nouveaux utilisateurs</h2>
                    <button class="btn btn--sm btn--ghost" id="btn-voir-utilisateurs" data-filtre="all">
                        Voir tous <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="admin-section__content">
                    <div class="admin-users-list" id="liste-utilisateurs">
                        <div class="admin-loading">
                            <i class="fas fa-spinner fa-spin"></i> Chargement...
                        </div>
                    </div>
                </div>
            </section>

            <!-- Derniers véhicules -->
            <section class="admin-section admin-panel">
                <div class="admin-section__header">
                    <h2><i class="fas fa-car-side"></i> Dernières annonces</h2>
                    <button class="btn btn--sm btn--ghost" id="btn-voir-vehicules">
                        Voir toutes <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="admin-section__content">
                    <div class="admin-vehicles-list" id="liste-vehicules">
                        <div class="admin-loading">
                            <i class="fas fa-spinner fa-spin"></i> Chargement...
                        </div>
                    </div>
                </div>
            </section>

            <!-- Statistiques rapides -->
            <section class="admin-section admin-panel admin-quick-stats">
                <div class="admin-section__header">
                    <h2><i class="fas fa-chart-pie"></i> Répartition</h2>
                </div>
                <div class="admin-section__content">
                    <!-- Types de véhicules -->
                    <div class="admin-quick-stat">
                        <h3>Types de véhicules</h3>
                        <div class="admin-stat-bars" id="stats-types-vehicules">
                            <div class="admin-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Marques populaires -->
                    <div class="admin-quick-stat">
                        <h3>Marques populaires</h3>
                        <div class="admin-stat-tags" id="stats-marques">
                            <div class="admin-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Statuts des offres -->
                    <div class="admin-quick-stat">
                        <h3>Statuts des offres</h3>
                        <div class="admin-stat-bars" id="stats-offres-statuts">
                            <div class="admin-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Section flux de messages -->
    <section class="admin-section admin-messages-section">
        <div class="admin-section__header">
            <h2><i class="fas fa-envelope-open-text"></i> Flux de messages</h2>
            <div class="admin-section__filters">
                <span class="admin-filter-label">Afficher :</span>
                <select id="filtre-messages" class="admin-select">
                    <option value="20">20 derniers</option>
                    <option value="50">50 derniers</option>
                    <option value="100">100 derniers</option>
                </select>
            </div>
        </div>
        <div class="admin-section__content">
            <div class="admin-messages-table-container">
                <table class="admin-table" id="table-messages">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Expéditeur</th>
                            <th>Véhicule concerné</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-messages">
                        <tr>
                            <td colspan="5" class="admin-loading">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Section liste complète des utilisateurs (modal/expandable) -->
    <section class="admin-section admin-users-full-section" id="section-utilisateurs-complet" hidden>
        <div class="admin-section__header">
            <h2><i class="fas fa-users-cog"></i> Gestion des utilisateurs</h2>
            <button class="btn btn--sm btn--ghost" id="btn-fermer-utilisateurs">
                <i class="fas fa-times"></i> Fermer
            </button>
        </div>
        <div class="admin-section__filters">
            <div class="admin-filter-group">
                <label>Filtrer par :</label>
                <select id="filtre-utilisateurs" class="admin-select">
                    <option value="all">Tous les utilisateurs</option>
                    <option value="verified">Email vérifié</option>
                    <option value="unverified">Email non vérifié</option>
                    <option value="admin">Administrateurs</option>
                </select>
            </div>
            <div class="admin-pagination-info" id="pagination-info-users">
                Affichage de <strong>1-20</strong> sur <strong>--</strong> utilisateurs
            </div>
        </div>
        <div class="admin-section__content">
            <div class="admin-table-container">
                <table class="admin-table admin-table--users">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Rôle</th>
                            <th>Email vérifié</th>
                            <th>Inscription</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-utilisateurs-complet">
                        <tr>
                            <td colspan="7" class="admin-loading">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="admin-pagination" id="pagination-utilisateurs">
                <!-- Pagination générée par JS -->
            </div>
        </div>
    </section>
</div>

<!-- Token CSRF pour les requêtes AJAX -->
<input type="hidden" id="csrf-token" value="<?= htmlspecialchars(GestionnaireSession::genererTokenCSRF()) ?>">

<script type="module">
    import VueAdmin from './assets/js/Admin/VueAdmin.js';
    
    document.addEventListener('DOMContentLoaded', () => {
        const admin = new VueAdmin();
        admin.initialiser();
    });
</script>
