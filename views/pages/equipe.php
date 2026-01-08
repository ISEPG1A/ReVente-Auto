<?php
/**
 * Page "À propos" - Présentation de l'application ReVente-Auto
 */
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
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
        <div class="membre">
            <div class="carte-flip">
                <div class="recto">
                    <img src="assets/images/equipe/Marouane.jpg" alt="Marouane ARNAUD EL MAGHNOUJI" class="membre-photo">
                    <h2>Marouane ARNAUD EL MAGHNOUJI </h2>
                    <span> Cliquer pour voir son rôle</span>
                </div>
                <div class="verso">
                    <p>Rôle : Responsable du développement et de l'intégration des fonctionnalités du site </p>
                </div>
            </div>
        </div>
        <div class="membre">
            <div class="carte-flip">
                <div class="recto">
                    <img src="assets/images/equipe/Antoine.jpg" alt="Antoine PEREZ" class="membre-photo">
                    <h2>Antoine PEREZ</h2>
                    <span> Cliquer pour voir son rôle</span>
                </div>
                <div class="verso">
                    <p>Rôle : Responsable du développement et de l'intégration des fonctionnalités du site</p>
                </div>
            </div>
        </div>
        <div class="membre">
            <div class="carte-flip">
                <div class="recto">
                    <img src="assets/images/equipe/Chloe.jpg" alt="Chloé REN" class="membre-photo">
                    <h2>Chloe REN</h2>
                    <span> Cliquer pour voir son rôle</span>
                </div>
                <div class="verso">
                    <p>Rôle : Responsable du développement et de l'intégration des fonctionnalités du site</p>
                </div>
            </div>
        </div>
        <div class="membre">
            <div class="carte-flip">
                <div class="recto">
                    <img src="assets/images/equipe/Thibault.jpg" alt="Thibault HOUEMABE" class="membre-photo">
                    <h2>Thibault HOUEMABE</h2>
                    <span> Cliquer pour voir son rôle</span>
                </div>
                <div class="verso">
                    <p>Rôle : Responsable du développement et de l'intégration des fonctionnalités du site</p>
                </div>
            </div>
        </div>
        <div class="membre">
            <div class="carte-flip">
                <div class="recto">
                    <img src="assets/images/equipe/Matheo.jpg" alt="Matheo CHEN" class="membre-photo">
                    <h2>Matheo CHEN</h2>
                    <span> Cliquer pour voir son rôle</span>
                </div>
                <div class="verso">
                    <p>Rôle : Responsable du développement et de l'intégration des fonctionnalités du site</p>
                </div>
            </div>
        </div>
        <div class="membre">
            <div class="carte-flip">
                <div class="recto">
                    <img src="assets/images/equipe/Taher.jpg" alt="Taher ZOUARI" class="membre-photo">
                    <h2>Taher ZOUARI</h2>
                    <span> Cliquer pour voir son rôle</span>
                </div>
                <div class="verso">
                    <p>Rôle : Responsable du développement et de l'intégration des fonctionnalités du site</p>
                </div>
            </div>
        </div>
    </div>

<!--
     <?php if (!empty($membres)) : ?>
        <?php foreach ($membres as $membre) : ?>
            <div class="membre">
                <div class="carte-flip">
                    <div class="recto">
                        <img src="assets/images/equipe/<?php echo htmlspecialchars($membre['avatar_path'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($membre['firstname'] . ' ' . $membre['last_name']); ?>" class="membre-photo">
                            <h2><?php echo htmlspecialchars($membre['firstname'] . ' ' . $membre['last_name']); ?></h2>
                                <span class="info-bas">Cliquer pour voir son role</span>
                                        </div>
                                        <div class="verso">
                                            <p><?php echo htmlspecialchars($membre['poste']); ?></p>
                                        </div>
                                    </div>
                                </div>
        <?php endforeach; ?>
        <?php else : ?>
            <p>Aucun membre trouvé.</p>
        <?php endif; ?>
-->
</section>

