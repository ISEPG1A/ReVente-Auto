<?php
/**
 * Page Équipe - Présentation de l'équipe ReVente-Auto
 * 
 * Cette vue affiche la liste des membres de l'équipe ayant le rôle admin.
 * Les données sont fournies par le contrôleur via les variables :
 * - $membres : liste des administrateurs
 * - $utilisateurConnecte : données de l'utilisateur en session
 * - $estAdmin : booléen indiquant si l'utilisateur est admin
 * - $equipeConfig : configuration pour JavaScript
 * 
 * Effet flip sur les cartes pour révéler le rôle de chaque membre.
 */
?>

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
<!-- Modal Détail Membre -->
<?php if ($estAdmin && $utilisateurConnecte): ?>
<div id="modal-detail-overlay" class="modal-detail-overlay">
    <div class="modal-detail">
        <div class="modal-detail__header">
            <h2 class="modal-detail__title">Modifier un profil</h2>
            <button type="button" class="modal-detail__close" id="btn-close-modal" aria-label="Fermer">×</button>
        </div>
        <div class="modal-detail__content">
            <div class="modal-detail__avatar-section">
                <img id="avatar-preview" src="assets/images/avatar-default.svg" alt="Avatar" class="modal-detail__avatar">
                <h3 class="modal-detail__member-name" id="member-name">-</h3>
                <p class="modal-detail__member-role" id="member-role">-</p>
            </div>

            <form id="form-detail-membre" class="modal-detail__form" enctype="multipart/form-data">
                <div class="modal-detail__form-group">
                    <label class="modal-detail__label">Photo de profil</label>
                    <input type="file" name="avatar" class="modal-detail__input" accept="image/*">
                </div>

                <div class="modal-detail__form-group">
                    <label class="modal-detail__label">Prénom *</label>
                    <input type="text" name="first_name" class="modal-detail__input" required>
                </div>

                <div class="modal-detail__form-group">
                    <label class="modal-detail__label">Nom *</label>
                    <input type="text" name="last_name" class="modal-detail__input" required>
                </div>

                <div class="modal-detail__form-group">
                    <label class="modal-detail__label">Email *</label>
                    <input type="email" name="email" class="modal-detail__input" required>
                </div>

                <div class="modal-detail__form-group">
                    <label class="modal-detail__label">Téléphone *</label>
                    <input type="tel" name="phone" class="modal-detail__input" placeholder="06 12 34 56 78" required>
                </div>

                <div class="modal-detail__form-group">
                    <label class="modal-detail__label">Poste</label>
                    <input type="text" name="poste" class="modal-detail__input" placeholder="Directeur, Développeur...">
                </div>

                <div id="status-message" class="modal-detail__status"></div>

                <div class="modal-detail__actions">
                    <button type="button" id="btn-cancel-modal" class="bouton bouton--fantome">Annuler</button>
                    <button type="submit" class="bouton bouton--primaire">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>


<script>
    window.EQUIPE_CONFIG = <?= json_encode($equipeConfig) ?>;
</script>

<script type="module" src="<?= htmlspecialchars($equipeConfig['baseUrl'], ENT_QUOTES, 'UTF-8') ?>assets/js/modules/statique/VueEquipe.js?v=<?= time() ?>"></script>


<!-- Section Stelyx -->
<section class="section equipe-stelyx">
    <div class="conteneur">
        <div class="equipe-stelyx__contenu">
            <div class="equipe-stelyx__logo">
                <img src="assets/images/logo/stelyx.png" alt="Logo Stelyx" class="equipe-stelyx__image">
            </div>
            <div class="equipe-stelyx__texte">
                <span class="equipe-stelyx__badge">Équipe de développement</span>
                <h2 class="equipe-stelyx__titre">Propulsé par <span>Stelyx</span></h2>
                <p class="equipe-stelyx__description">
                    ReVente-Auto est fièrement développé par <strong>Stelyx</strong>, une équipe d'étudiants passionnés par l'innovation et le développement web. 
                    Notre mission : créer des solutions numériques modernes, sécurisées et accessibles pour faciliter vos projets au quotidien.
                </p>
                <p class="equipe-stelyx__description">
                    Avec un engagement fort pour la qualité et l'expérience utilisateur, nous mettons tout en œuvre pour vous offrir une plateforme fiable et intuitive.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Section Membres -->
<section class="section equipe-membres-section">
    <div class="conteneur">
        <div class="equipe-intro">
            <span class="equipe-badge">Les visages derrière le projet</span>
            <h2 class="equipe-intro__titre">Rencontrez notre équipe</h2>
            <p class="equipe-intro__text">
                Chaque membre apporte son expertise et sa passion pour faire de ReVente-Auto la meilleure plateforme automobile.
            </p>
        </div>
        
        <div class="equipe-membres">
        <?php if (!empty($membres)) : ?>
            <?php foreach ($membres as $membre) : ?>
                <div class="membres-carte">
                    <?php if ($estAdmin): ?>
                        <div class="equipe-hero__actions">
                            <button type="button"
                                    class="btn-edit-membre bouton bouton--primaire"
                                    data-member-id="<?= (int)$membre['id'] ?>"
                                    aria-label="Modifier ce profil">
                                    Modifier
                            </button>
                        </div>           
                    <?php endif; ?>
                    <div class="membre">
                        <div class="carte-flip">  
                            <div class="recto">
                                
                                <img src="<?= Securite::echapper($membre['avatar_path'] ?: 'assets/images/avatar-default.svg') ?>" 
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
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p class="equipe-vide">Aucun membre trouvé.</p>
        <?php endif; ?>
        </div>
    </div>
</section>
