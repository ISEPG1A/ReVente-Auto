<?php
/**
 * Page Équipe - Présentation de l'équipe ReVente-Auto
 * 
 * Cette vue affiche la liste des membres de l'équipe ayant le rôle admin.
 * Les données sont fournies par le contrôleur via la variable $membres.
 * Effet flip sur les cartes pour révéler le rôle de chaque membre.
 */
?>

<script>
/**
 * Gère l'effet de retournement des cartes membres
 * Au clic, la carte bascule pour afficher le rôle
 */
function initialiserFlipCartes() {
    document.querySelectorAll('.membre').forEach(function(carte) {
        carte.addEventListener('click', function() {
            carte.classList.toggle('retournee');
        });
    });
}
document.addEventListener('DOMContentLoaded', initialiserFlipCartes);
</script>

<!-- Hero Section Équipe -->
<section class="equipe-hero">   
    <div class="equipe-hero__contenu"> 
        <h1 class="equipe-hero__titre">
            Notre <span>équipe</span>
        </h1>
        <p class="equipe-hero__description">
            Découvrez les membres passionnés de ReVente Auto. Notre équipe est composée de professionnels engagés, prêts à vous accompagner dans toutes les étapes de votre projet automobile.  
        </p>
    </div>
</section>

<section class="section">
    <div class="conteneur equipe-membres">
        <?php if (!empty($membres)) : ?>
            <?php foreach ($membres as $membre) : ?>
                <div class="membre">
                    <div class="carte-flip">
                        <div class="recto">
                            <img src="<?= Securite::echapper($membre['avatar_path'] ?? 'assets/images/equipe/default.jpeg') ?>" 
                                 alt="<?= Securite::echapper($membre['first_name'] . ' ' . $membre['last_name']) ?>" 
                                 class="membre-photo">
                            <h2><?= Securite::echapper($membre['first_name'] . ' ' . $membre['last_name']) ?></h2>
                            <span class="membre-indication">Cliquer pour voir son rôle</span>
                        </div>
                        <div class="verso">
                            <p class="membre-role">Rôle : <?= Securite::echapper($membre['poste'] ?? 'Membre de l\'équipe') ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p class="equipe-vide">Aucun membre trouvé.</p>
        <?php endif; ?>
    </div>
</section>
