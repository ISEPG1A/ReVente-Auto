<?php
/**
 * Page Équipe - Présentation de l'équipe ReVente-Auto
 * 
 * Cette vue affiche la liste des membres de l'équipe ayant le rôle admin.
 * Les données sont fournies par le contrôleur via la variable $membres.
 */
?>

<script>
function retourner() {
    document.querySelectorAll('.membre').forEach(function(carte) {
        carte.addEventListener('click', function(e) {
            carte.classList.toggle('retournee');
        });
    });
}
document.addEventListener('DOMContentLoaded', retourner);
</script>


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
        <?php if (!empty($membres)) : ?>
            <?php foreach ($membres as $membre) : ?>
                <div class="membre">
                    <div class="carte-flip">
                        <div class="recto">
                            <img src="./<?php echo htmlspecialchars($membre['avatar_path'] ?? 'default.jpeg'); ?>" alt="<?php echo htmlspecialchars($membre['first_name'] . ' ' . $membre['last_name']); ?>" class="membre-photo">
                            <h2><?php echo htmlspecialchars($membre['first_name'] . ' ' . $membre['last_name']); ?></h2>
                            <span>Cliquer pour voir son rôle</span>
                        </div>
                        <div class="verso">
                            <p>Rôle : <?php echo htmlspecialchars($membre['poste']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>Aucun membre trouvé.</p>
        <?php endif; ?>
    </div>
</section>

