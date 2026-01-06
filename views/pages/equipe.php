<?php
/**
 * Page "À propos" - Présentation de l'application ReVente-Auto
 */
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Hero Section Équipe -->
<section class="equipe-hero">
    <div class="equipe-hero__contenu"> 
        <h1 class="equipe-hero_titre" >
             Notre équipe
            </h1>
            <p class="equipe-hero_description">
                Découvrez les membres passionnés de ReVente Auto. Notre équipe est composée de professionnels engagés, prêts à vous accompagner dans toutes les étapes de votre projet automobile.  
            </p>
    </div>
</section>
<section class="section">
    <div class="conteneur equipe-membres">
        <div class="membre">
            <img src="assets/images/equipe/Marouane.jpg" alt="Marouane ARNAUD EL MAGHNOUJI" class="membre-photo">
            <h2>Marouane ARNAUD EL MAGHNOUJI </h2>
            <p>Responsable du développement et de l'intégration des fonctionnalités du site</p>
        </div>
        <div class="membre">
            <img src="assets/images/equipe/Antoine.jpg" alt="Antoine PEREZ" class="membre-photo">
            <h2>Antoine PEREZ</h2>
            <p>Responsable du développement et de l'intégration des fonctionnalités du site</p>
        </div>
        <div class="membre">
            <img src="assets/images/equipe/Chloe.jpg" alt="Chloé REN" class="membre-photo">
            <h2>Chloé REN</h2>
            <p>Responsable du développement et de l'intégration des fonctionnalités du site</p>
        </div>

        <div class="membre">
            <img src="assets/images/equipe/Thibault.jpg" alt="Thibault HOUEMABE" class="membre-photo">
            <h2>Thibault HOUEMABE</h2>
            <p>Responsable du développement et de l'intégration des fonctionnalités du site</p>
        </div>
        <div class="membre">
            <img src="assets/images/equipe/Matheo.jpg" alt="Matheo CHEN" class="membre-photo">
            <h2>Mathéo CHEN</h2>
            <p>Responsable du développement et de l'intégration des fonctionnalités du site</p>
        </div>
        <div class="membre">
            <img src="assets/images/equipe/Taher.jpg" alt="Taher ZOUARI" class="membre-photo">
            <h2>Taher ZOUARI</h2>
            <p>Responsable du développement et de l'intégration des fonctionnalités du site</p>
        </div>  
    </div>  
</section>
