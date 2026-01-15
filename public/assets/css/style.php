<?php
/**
 * Génère dynamiquement le CSS avec cache busting automatique
 */
header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$v = time();
?>
/* =========================================
   STYLE PRINCIPAL - Généré dynamiquement
   ========================================= */

/* 1. Base */
@import 'base/variables.css?v=<?= $v ?>';
@import 'base/reinitialisation.css?v=<?= $v ?>';
@import 'base/typographie.css?v=<?= $v ?>';
@import 'base/animations.css?v=<?= $v ?>';

/* 2. Mises en page */
@import 'mises_en_page/grille.css?v=<?= $v ?>';
@import 'mises_en_page/entete.css?v=<?= $v ?>';
@import 'mises_en_page/pied-de-page.css?v=<?= $v ?>';

/* 3. Composants */
@import 'composants/boutons.css?v=<?= $v ?>';
@import 'composants/formulaires.css?v=<?= $v ?>';
@import 'composants/cartes.css?v=<?= $v ?>';
@import 'composants/alertes.css?v=<?= $v ?>';
@import 'composants/barre-outils.css?v=<?= $v ?>';
@import 'composants/hero.css?v=<?= $v ?>';
@import 'composants/localisation.css?v=<?= $v ?>';
@import 'composants/notification.css?v=<?= $v ?>';
@import 'composants/resultat-page.css?v=<?= $v ?>';
@import 'composants/suppression.css?v=<?= $v ?>';

/* 4. Pages */
@import 'pages/accueil.css?v=<?= $v ?>';
@import 'pages/vehicule-form.css?v=<?= $v ?>';
@import 'pages/details.css?v=<?= $v ?>';
@import 'pages/estimation.css?v=<?= $v ?>';
@import 'pages/messagerie.css?v=<?= $v ?>';
@import 'pages/galerie.css?v=<?= $v ?>';
@import 'pages/contact.css?v=<?= $v ?>';
@import 'pages/apropos.css?v=<?= $v ?>';
@import 'pages/authentification.css?v=<?= $v ?>';
@import 'pages/parametres.css?v=<?= $v ?>';
@import 'pages/favoris.css?v=<?= $v ?>';
@import 'pages/faq.css?v=<?= $v ?>';
@import 'pages/cgu.css?v=<?= $v ?>';
@import 'pages/politique-confidentialite.css?v=<?= $v ?>';
@import 'pages/mentions-legales.css?v=<?= $v ?>';
@import 'pages/equipe.css?v=<?= $v ?>';
@import 'pages/email-verification.css?v=<?= $v ?>';
@import 'pages/reset-mot-de-passe.css?v=<?= $v ?>';
@import 'pages/mes-annonces.css?v=<?= $v ?>';
@import 'pages/admin.css?v=<?= $v ?>';
@import 'pages/erreur-404.css?v=<?= $v ?>';
