<!-- 
Page des favoris - Liste des véhicules enregistrés par l'utilisateur
Design moderne avec hero et grille de cartes
-->

<?php
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Hero Section Favoris -->
<section class="hero hero--center">
    <div class="hero__background">
        <div class="hero__shapes">
            <div class="hero__shape hero__shape--1"></div>
            <div class="hero__shape hero__shape--2"></div>
            <div class="hero__shape hero__shape--3"></div>
        </div>
    </div>

    <div class="hero__content">
        <div class="favoris-hero__icon">
            <i class="fas fa-heart"></i>
        </div>
        <h1 class="hero__title">Mes <span class="hero__highlight">Favoris</span></h1>
        <p class="hero__description">Retrouvez tous les véhicules que vous avez sauvegardés</p>
        <div class="favoris-hero__stats">
            <div class="favoris-stat">
                <span id="favoris-count" class="favoris-stat__number">0</span>
                <span class="favoris-stat__label">véhicule(s) sauvegardé(s)</span>
            </div>
        </div>
    </div>
</section>

<!-- Section Liste des Favoris -->
<section class="favoris-section">
    <div class="conteneur">
        
        <!-- Barre d'actions -->
        <div class="favoris-toolbar">
            <div class="favoris-toolbar__left">
                <h2 class="favoris-toolbar__title">
                    <i class="fas fa-list"></i> Liste des favoris
                </h2>
            </div>
            <div class="favoris-toolbar__right">
                <a href="<?= $prefixeUrl ?>galerie" class="favoris-btn favoris-btn--outline">
                    <i class="fas fa-plus"></i> Ajouter des véhicules
                </a>
            </div>
        </div>
        
        <!-- Grille des favoris -->
        <div id="liste-favoris" class="favoris-grid" aria-live="polite" aria-busy="false"></div>
        
        <!-- État vide -->
        <div id="favoris-vide" class="favoris-empty" hidden>
            <div class="favoris-empty__icon">
                <i class="far fa-heart"></i>
            </div>
            <h3 class="favoris-empty__title">Aucun favori pour le moment</h3>
            <p class="favoris-empty__description">
                Parcourez notre galerie et cliquez sur le <i class="fas fa-heart"></i> pour sauvegarder vos véhicules préférés.
            </p>
            <a href="<?= $prefixeUrl ?>galerie" class="favoris-btn favoris-btn--primary">
                <i class="fas fa-car"></i> Parcourir la galerie
            </a>
        </div>
        
        <!-- État de chargement -->
        <div id="favoris-loading" class="favoris-loading">
            <div class="favoris-loading__spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Chargement de vos favoris...</p>
        </div>
        
    </div>
</section>

<script type="module">
    import { VueFavoris } from './assets/js/Favoris/VueFavoris.js';

    const vue = new VueFavoris();
    vue.initialiser();
</script>
