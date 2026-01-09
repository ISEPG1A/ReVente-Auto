<!-- 
Page Mes Annonces - Liste des véhicules publiés par l'utilisateur
Gestion des annonces avec statistiques de performance
-->

<?php
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Hero Section Mes Annonces -->
<section class="hero hero--center">
    <div class="hero__background">
        <div class="hero__shapes">
            <div class="hero__shape hero__shape--1"></div>
            <div class="hero__shape hero__shape--2"></div>
            <div class="hero__shape hero__shape--3"></div>
        </div>
    </div>

    <div class="hero__content">
        <div class="annonces-hero__icon">
            <i class="fas fa-bullhorn"></i>
        </div>
        <h1 class="hero__title">Mes <span class="hero__highlight">Annonces</span></h1>
        <p class="hero__description">Gérez vos véhicules mis en vente et suivez leurs performances</p>
        <div class="annonces-hero__stats">
            <div class="annonces-stat">
                <span id="annonces-count" class="annonces-stat__number">0</span>
                <span class="annonces-stat__label">annonce(s) publiée(s)</span>
            </div>
            <div class="annonces-stat">
                <span id="annonces-public-count" class="annonces-stat__number">0</span>
                <span class="annonces-stat__label">publique(s)</span>
            </div>
            <div class="annonces-stat">
                <span id="annonces-prive-count" class="annonces-stat__number">0</span>
                <span class="annonces-stat__label">privée(s)</span>
            </div>
        </div>
    </div>
</section>

<!-- Section Liste des Annonces -->
<section class="annonces-section">
    <div class="conteneur">
        
        <!-- Barre d'actions -->
        <div class="annonces-toolbar">
            <div class="annonces-toolbar__left">
                <h2 class="annonces-toolbar__title">
                    <i class="fas fa-car"></i> Mes véhicules
                </h2>
            </div>
            <div class="annonces-toolbar__right">
                <a href="<?= $prefixeUrl ?>ajout_vehicule" class="annonces-btn annonces-btn--primary">
                    <i class="fas fa-plus"></i> Nouvelle annonce
                </a>
            </div>
        </div>
        
        <!-- Grille des annonces -->
        <div id="liste-annonces" class="annonces-grid" aria-live="polite" aria-busy="false"></div>
        
        <!-- État vide -->
        <div id="annonces-vide" class="annonces-empty" hidden>
            <div class="annonces-empty__icon">
                <i class="fas fa-car-alt"></i>
            </div>
            <h3 class="annonces-empty__title">Aucune annonce pour le moment</h3>
            <p class="annonces-empty__description">
                Vous n'avez pas encore publié de véhicule. Créez votre première annonce pour commencer à vendre !
            </p>
            <a href="<?= $prefixeUrl ?>ajout_vehicule" class="annonces-btn annonces-btn--primary">
                <i class="fas fa-plus"></i> Créer une annonce
            </a>
        </div>
        
        <!-- État de chargement -->
        <div id="annonces-loading" class="annonces-loading">
            <div class="annonces-loading__spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Chargement de vos annonces...</p>
        </div>
        
        <!-- Erreur -->
        <div id="annonces-erreur" class="annonces-erreur" hidden>
            <div class="annonces-erreur__icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="annonces-erreur__title">Erreur de chargement</h3>
            <p id="annonces-erreur-message" class="annonces-erreur__description"></p>
            <button id="annonces-retry" class="annonces-btn annonces-btn--outline">
                <i class="fas fa-redo"></i> Réessayer
            </button>
        </div>

    </div>
</section>

<!-- Modal de confirmation de suppression -->
<div id="modal-suppression" class="modal" hidden>
    <div class="modal__overlay"></div>
    <div class="modal__content">
        <div class="modal__header">
            <h3 class="modal__title">
                <i class="fas fa-exclamation-triangle"></i> Confirmer la suppression
            </h3>
            <button class="modal__close" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal__body">
            <p>Êtes-vous sûr de vouloir supprimer cette annonce ?</p>
            <p class="modal__warning">Cette action est irréversible. Toutes les images et messages associés seront également supprimés.</p>
            <div class="modal__vehicle-info">
                <strong id="modal-vehicle-name"></strong>
            </div>
        </div>
        <div class="modal__footer">
            <button class="annonces-btn annonces-btn--outline modal__cancel">
                <i class="fas fa-times"></i> Annuler
            </button>
            <button id="modal-confirm-delete" class="annonces-btn annonces-btn--danger">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </div>
    </div>
</div>

<input type="hidden" id="csrf-token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

<script type="module" src="<?= $prefixeUrl ?>public/assets/js/MesAnnonces/VueMesAnnonces.js"></script>
