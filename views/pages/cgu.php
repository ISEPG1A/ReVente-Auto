<?php
// Page des conditions générales d'utilisation

// Récupération et normalisation du nom du script actuel
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Calcul du préfixe d'URL pour les liens
// Si le script est dans le dossier /public/, on remonte d'un niveau
// Sinon, on utilise la racine
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Section principale de la page d'erreur 404 -->
<section class="section">
  <div class="conteneur">
    <!-- MENTION DE CARACTÈRE FICTIF -->
    <div class="warning-box">
        <h2>IMPORTANT - À LIRE ATTENTIVEMENT</h2>
        <p><strong>Ce site web et son intégralité du contenu constituent un projet pédagogique fictif développé dans un cadre scolaire</strong></p>
        <p><strong>L'utilisateur est expressément informé que :</strong></p>
        <ul>
            <li>Tous les véhicules présentés sur la plateforme sont <strong>fictifs</strong> et n'existent pas dans la réalité</li>
            <li>Tous les annonces, les tarifs, les descriptions techniques et les photographies sont <strong>générés à titre illustratif</strong> et ne correspondent à aucune transaction réelle</li>
            <li><strong>Aucune transaction commerciale réelle</strong> ne peut être effectuée via cette plateforme</li>
            <li>Ce site ne constitue en aucun cas un site de vente ou de commerce électronique opérationnel</li>
            <li>Aucun paiement, aucune livraison, aucun transfert de propriété n'est possible</li>
        </ul>
        <p style="margin-top: 15px;"><strong>Cette plateforme est destinée exclusivement à des fins pédagogiques et de démonstration.</strong></p>
    </div>

    <!-- TABLE DES MATIÈRES -->
    <div class="table-des-matieres">
        <h3>📋 Table des Matières</h3>
        <a href="#article1">Article 1 - Objet et Définitions</a>
        <a href="#article2">Article 2 - Acceptation des CGU</a>
        <a href="#article3">Article 3 - Caractère Fictif et Pédagogique</a>
        <a href="#article4">Article 4 - Conditions d'Accès</a>
        <a href="#article5">Article 5 - Création de Compte</a>
        <a href="#article6">Article 6 - Droits de Propriété Intellectuelle</a>
        <a href="#article7">Article 7 - Utilisation Interdite</a>
        <a href="#article8">Article 8 - Responsabilité</a>
        <a href="#article9">Article 9 - Protection des Données</a>
        <a href="#article10">Article 10 - Cookies</a>
        <a href="#article11">Article 11 - Liens Externes</a>
        <a href="#article12">Article 12 - Modération</a>
        <a href="#article13">Article 13 - Conformité Légale</a>
        <a href="#article14">Article 14 - Contact et Support</a>
        <a href="#article15">Article 15 - Dispositions Finales</a>
        <a href="#article16">Article 16 - Durée de Validité</a>
    </div>

    <!-- ARTICLE 1 -->
    <div class="section" id="article1">
        <h2><span class="article-number">1</span>Objet et Définitions</h2>
        
        <h3>1.1 - Objet</h3>
        <p>Les présentes Conditions Générales d'Utilisation (ci-après « <strong>CGU</strong> ») régissent les conditions d'accès et d'utilisation de la plateforme web fictive de simulation de revente automobile (ci-après le « <strong>Site</strong> »). Le Site est une plateforme pédagogique ayant pour but de simuler le fonctionnement d'un portail de petites annonces automobiles, sans transaction commerciale réelle.</p>

        <h3>1.2 - Définitions</h3>
        <ul>
            <li><strong>Site</strong> : la plateforme web accessible à l'adresse [https://exemple-revente-auto.edu]</li>
            <li><strong>Utilisateur</strong> : toute personne accédant au Site et acceptant les présentes CGU</li>
            <li><strong>Contenu</strong> : l'ensemble des informations, annonces automobiles simulées, textes, images, vidéos et autres éléments présents sur le Site</li>
            <li><strong>Services</strong> : les fonctionnalités proposées par le Site, notamment la consultation des annonces et la création de comptes utilisateur fictifs</li>
            <li><strong>Annonces</strong> : les listings automobiles fictifs présentés sur le Site</li>
        </ul>
    </div>

    <!-- ARTICLE 2 -->
    <div class="section" id="article2">
        <h2><span class="article-number">2</span>Acceptation des CGU</h2>
        
        <h3>2.1 - Consentement</h3>
        <p>L'accès au Site et l'utilisation de ses Services impliquent l'acceptation intégrale et sans réserve des présentes CGU. Toute personne qui n'accepte pas les présentes conditions s'abstient de consulter et d'utiliser le Site.</p>

        <h3>2.2 - Modifications des CGU</h3>
        <p>L'équipe étudiante responsable du Site se réserve le droit de modifier les présentes CGU à tout moment, sans préavis. L'utilisateur est invité à consulter régulièrement ces conditions. La continuation de l'utilisation du Site après modification vaut acceptation des nouvelles conditions.</p>
    </div>

    <!-- ARTICLE 3 -->
    <div class="section" id="article3">
        <h2><span class="article-number">3</span>Caractère Fictif et Pédagogique du Contenu</h2>
        
        <h3>3.1 - Nature fictive du contenu</h3>
        <p>L'utilisateur reconnaît et accepte que <strong>tous les contenus présents sur le Site sont fictifs</strong> et produits à des fins éducatives. Cela inclut, sans limitation :</p>
        <ul>
            <li>Les annonces automobiles et descriptions de véhicules</li>
            <li>Les photographies, images et visuels associés</li>
            <li>Les tarifs, prix et conditions tarifaires affichés</li>
            <li>Les données techniques, historiques de révision, kilométrage indiqué</li>
            <li>Les profils des vendeurs simulés</li>
            <li>Tous les autres éléments informatifs ou promotionnels</li>
        </ul>

        <h3>3.2 - Absence de transaction réelle</h3>
        <p>L'utilisateur est explicitement informé qu'<strong>aucune transaction commerciale, aucun achat, aucune vente et aucun contrat</strong> ne peuvent être conclus via ce Site. Le Site ne constitue en aucun cas une plateforme de commerce électronique opérationnelle.</p>

        <h3>3.3 - Finalité éducative</h3>
        <p>Le Site est conçu <strong>exclusivement à titre pédagogique</strong> dans le cadre d'un projet scolaire. Son objectif est de permettre aux apprenants de se familiariser avec le fonctionnement et les usages des plateformes de petites annonces automobiles.</p>
    </div>

    <!-- ARTICLE 4 -->
    <div class="section" id="article4">
        <h2><span class="article-number">4</span>Conditions d'Accès au Site</h2>
        
        <h3>4.1 - Accès libre</h3>
        <p>L'accès au Site est libre et gratuit pour toute personne disposant d'une connexion internet. Aucune inscription n'est obligatoire pour consulter le contenu public du Site, sauf pour accéder à certaines fonctionnalités spécifiques.</p>

        <h3>4.2 - Restrictions d'accès</h3>
        <p>L'équipe étudiante responsable se réserve le droit de restreindre ou d'interdire l'accès au Site à :</p>
        <ul>
            <li>Toute personne ne respectant pas les présentes CGU</li>
            <li>Toute personne utilisant le Site à des fins contraires à sa nature pédagogique</li>
            <li>Toute personne entreprenant des actions susceptibles de perturber le fonctionnement normal du Site (spam, hacking, etc.)</li>
        </ul>

        <h3>4.3 - Disponibilité du Site</h3>
        <p>Le Site est fourni « en l'état » sans garantie de disponibilité permanente. L'équipe étudiante responsable ne peut être tenu responsable des interruptions, dysfonctionnements ou indisponibilités du Site.</p>
    </div>

    <!-- ARTICLE 5 -->
    <div class="section" id="article5">
        <h2><span class="article-number">5</span>Création de Compte Utilisateur</h2>
        
        <h3>5.1 - Conditions de création</h3>
        <p>La création d'un compte utilisateur sur le Site est facultative pour consulter les annonces, mais peut être requise pour accéder à certaines fonctionnalités (par exemple, sauvegarder des favoris, utiliser la messagerie...).</p>

        <h3>5.2 - Informations fournies</h3>
        <p>Lors de la création d'un compte, l'utilisateur s'engage à fournir des informations complètes. L'utilisation de fausses identités ou de données trompeuses dans un cadre autre que celui prévu pédagogiquement est interdite.</p>

        <h3>5.3 - Responsabilité du compte</h3>
        <p>L'utilisateur est responsable du maintien de la confidentialité de ses identifiants de connexion (identifiant et mot de passe). Toute utilisation du compte est présumée être le fait de l'utilisateur titulaire du compte.</p>

        <h3>5.4 - Suppression de compte</h3>
        <p>L'utilisateur peut demander la suppression de son compte à tout moment en contactant l'équipe étudiante responsable ou directement dans les paramètres du compte. Les données associées au compte seront supprimées conformément à la politique de confidentialité applicable.</p>
    </div>

    <!-- ARTICLE 6 -->
    <div class="section" id="article6">
        <h2><span class="article-number">6</span>Droits de Propriété Intellectuelle</h2>
        
        <h3>6.1 - Propriété du contenu</h3>
        <p>Tous les contenus présents sur le Site, y compris les textes, images, graphiques, logos et mises en page, demeurent la propriété exclusive de l'équipe étudiante responsable ou de ses partenaires pédagogiques, sauf mention contraire explicite.</p>

        <h3>6.2 - License d'utilisation</h3>
        <p>L'utilisateur est autorisé à consulter et télécharger les contenus du Site pour un usage personnel et non commercial uniquement. Toute reproduction, distribution, modification ou utilisation commerciale est strictement interdite sans autorisation préalable écrite.</p>

        <h3>6.3 - Droits d'auteur</h3>
        <p>Le respect des droits d'auteur et de la propriété intellectuelle est exigé. Toute violation pourra donner lieu à des poursuites judiciaires.</p>
    </div>

    <!-- ARTICLE 7 -->
    <div class="section" id="article7">
        <h2><span class="article-number">7</span>Utilisation Interdite du Site</h2>
        <p>L'utilisateur s'engage à ne pas :</p>
        <ul>
            <li>Utiliser le Site à des fins illégales ou contraires à l'ordre public</li>
            <li>Accéder au Site ou à ses serveurs par des moyens non autorisés (hacking, injection, etc.)</li>
            <li>Reproduire, dupliquer, copier ou vendre les contenus du Site sans autorisation</li>
            <li>Spam, harcèlement ou comportement abusif envers d'autres utilisateurs</li>
            <li>Introduire des logiciels malveillants, des virus ou tout code malveillant</li>
            <li>Utiliser des robots, scrapers ou autres outils automatisés pour accéder massivement aux contenus</li>
            <li>Présenter le contenu fictif du Site comme étant réel ou véritable</li>
            <li>Utiliser le Site à des fins commerciales sans autorisation préalable</li>
        </ul>
    </div>

    <!-- ARTICLE 8 -->
    <div class="section" id="article8">
        <h2><span class="article-number">8</span>Responsabilité et Limitation de Responsabilité</h2>
        
        <h3>8.1 - Limitation de responsabilité</h3>
        <p><strong>L'équipe étudiante responsable et l'ensemble des contributeurs au projet scolaire ne peuvent en aucun cas être tenus responsables :</strong></p>
        <ul>
            <li>Des erreurs, imprécisions ou omissions dans les contenus du Site</li>
            <li>De tout préjudice direct ou indirect résultant de l'accès ou de l'utilisation du Site</li>
            <li>De toute interruption, dysfonctionnement ou indisponibilité du Site</li>
            <li>De l'utilisation abusive du Site par un utilisateur ou un tiers</li>
            <li>De tout dommage causé à l'équipement informatique de l'utilisateur</li>
        </ul>

        <h3>8.2 - Absence de garantie</h3>
        <p>Le Site est fourni « en l'état » sans aucune garantie explicite ou implicite. L'équipe étudiante responsable ne garantit pas l'exactitude, la complétude, la pertinence ou la disponibilité des contenus.</p>

        <h3>8.3 - Utilisation à titre gracieux</h3>
        <p>L'accès au Site est fourni gratuitement et à titre gracieux à titre pédagogique. Aucune obligation ne peut être imposée à l'équipe étudiante responsable au titre de cet accès gratuit.</p>
    </div>

    <!-- ARTICLE 9 -->
    <div class="section" id="article9">
        <h2><span class="article-number">9</span>Politique de Confidentialité et Protection des Données</h2>
        
        <h3>9.1 - Collecte des données</h3>
        <p>Le Site peut collecter des données personnelles limitées pour des fins pédagogiques et de suivi d'utilisation, notamment :</p>
        <ul>
            <li>L'adresse de courrier électronique (si inscription)</li>
            <li>Le nom d'utilisateur</li>
            <li>Les logs d'accès et données de navigation</li>
            <li>Toute autre donnée volontairement fournie par l'utilisateur</li>
        </ul>

        <h3>9.2 - Utilisation des données</h3>
        <p>Les données collectées sont utilisées <strong>exclusivement</strong> pour :</p>
        <ul>
            <li>Assurer le fonctionnement du Site</li>
            <li>Analyser l'utilisation pédagogique du Site</li>
            <li>Améliorer l'expérience utilisateur</li>
            <li>Respecter les obligations légales</li>
        </ul>
        <p>Les données ne seront <strong>jamais</strong> vendues, louées ou communiquées à des tiers commerciaux.</p>

        <h3>9.3 - Droits de l'utilisateur</h3>
        <p>Conformément aux réglementations applicables (notamment le RGPD en Europe), l'utilisateur dispose du droit :</p>
        <ul>
            <li>D'accéder à ses données personnelles</li>
            <li>De rectifier ou mettre à jour ses données</li>
            <li>De demander la suppression de ses données</li>
            <li>De s'opposer au traitement de ses données</li>
        </ul>
        <p>Pour exercer ces droits, l'utilisateur peut contacter l'équipe étudiante responsable via les coordonnées indiquées à l'Article 14.</p>

        <h3>9.4 - Sécurité des données</h3>
        <p>L'équipe étudiante responsable met en œuvre les mesures de sécurité raisonnables pour protéger les données personnelles contre l'accès non autorisé. Cependant, aucune transmission de données sur internet n'est totalement sécurisée.</p>
    </div>

    <!-- ARTICLE 10 -->
    <div class="section" id="article10">
        <h2><span class="article-number">10</span>Cookies et Technologies de Suivi</h2>
        
        <h3>10.1 - Utilisation de cookies</h3>
        <p>Le Site peut utiliser des cookies et autres technologies de suivi pour :</p>
        <ul>
            <li>Mémoriser les préférences de l'utilisateur</li>
            <li>Analyser l'utilisation du Site</li>
            <li>Améliorer les fonctionnalités pédagogiques</li>
        </ul>

        <h3>10.2 - Consentement</h3>
        <p>L'utilisateur peut configurer son navigateur pour refuser les cookies. Cependant, cela pourrait affecter certaines fonctionnalités du Site.</p>
    </div>

    <!-- ARTICLE 11 -->
    <div class="section" id="article11">
        <h2><span class="article-number">11</span>Liens Externes</h2>
        
        <h3>11.1 - Liens externes</h3>
        <p>Le Site peut contenir des liens vers des sites externes. L'équipe étudiante responsable n'est pas responsable du contenu, de la disponibilité ou de la politique de confidentialité de ces sites externes.</p>

        <h3>11.2 - Absence de partenariat</h3>
        <p>La présence d'un lien vers un site externe ne constitue pas un partenariat, une recommandation ou une approbation de ce site.</p>
    </div>

    <!-- ARTICLE 12 -->
    <div class="section" id="article12">
        <h2><span class="article-number">12</span>Modération et Contenu Utilisateur</h2>
        
        <h3>12.1 - Commentaires et contributions</h3>
        <p>Si le Site permet aux utilisateurs de poster des commentaires ou contributions, ces derniers doivent respecter les présentes CGU et être appropriés à un contexte scolaire.</p>

        <h3>12.2 - Modération</h3>
        <p>L'équipe étudiante responsable se réserve le droit de modérer, de modifier ou de supprimer tout contenu utilisateur qui :</p>
        <ul>
            <li>Viole les présentes CGU</li>
            <li>Est offensant, diffamatoire ou illégal</li>
            <li>Est hors sujet ou inapproprié</li>
            <li>Compromet la sécurité ou le bon fonctionnement du Site</li>
        </ul>

        <h3>12.3 - Pas de modération systématique</h3>
        <p>Cependant, l'équipe étudiante responsable ne s'engage pas à contrôler systématiquement tous les contenus utilisateurs. Les utilisateurs sont encouragés à signaler les contenus problématiques.</p>
    </div>

    <!-- ARTICLE 13 -->
    <div class="section" id="article13">
        <h2><span class="article-number">13</span>Conformité Légale et Juridiction</h2>
        
        <h3>13.1 - Loi applicable</h3>
        <p>Les présentes CGU sont régies par la loi française.</p>

        <h3>13.2 - Juridiction compétente</h3>
        <p>Les litiges éventuels seront de la compétence exclusive des tribunaux français.</p>

        <h3>13.3 - Conformité réglementaire</h3>
        <p>Le Site est conçu conformément aux réglementations applicables en matière d'accessibilité numérique, de protection des données (RGPD) et de droits des utilisateurs.</p>
    </div>

    <!-- ARTICLE 14 -->
    <div class="section" id="article14">
        <h2><span class="article-number">14</span>Contact et Support</h2>
        <p>Pour toute question, réclamation ou demande concernant le Site ou les présentes CGU, l'utilisateur peut contacter l'équipe étudiante responsable à :</p>
        
        <div class="contact-info">
            <p><strong>Établissement :</strong> ISEP</p>
            <p><strong>Adresse : 28 Rue Notre-Dame-des-Champs, 75006 Paris</strong> </p>
            <p><strong>Téléphone :</strong> ...</p>
            <p><strong>Courrier électronique :</strong> contact@Revent-auto.fr</p>
            <p><strong>Responsable du projet :</strong> ...</p>
        </div>
    </div>

    <!-- ARTICLE 15 -->
    <div class="section" id="article15">
        <h2><span class="article-number">15</span>Dispositions Finales</h2>
        
        <h3>15.1 - Intégralité de l'accord</h3>
        <p>Les présentes CGU constituent l'intégralité de l'accord entre l'utilisateur et l'équipe étudiante responsable concernant l'utilisation du Site et remplacent tous les accords antérieurs.</p>

        <h3>15.2 - Nullité partielle</h3>
        <p>Si une disposition des présentes CGU est jugée nulle, illégale ou non applicable, cette disposition sera supprimée, mais les autres dispositions resteront en vigueur.</p>

        <h3>15.3 - Absence de renonciation</h3>
        <p>L'absence d'application de l'équipe étudiante responsable concernant une violation ne constitue pas une renonciation à faire respecter cette disposition ultérieurement.</p>

        <h3>15.4 - Cession de droits</h3>
        <p>L'utilisateur ne peut pas céder ses droits ou obligations découlant des présentes CGU sans consentement préalable écrit de l'équipe étudiante responsable.</p>
    </div>

    <!-- ARTICLE 16 -->
    <div class="section" id="article16">
        <h2><span class="article-number">16</span>Durée de Validité</h2>
        <p>Les présentes CGU s'appliquent à compter de leur publication et restent valables aussi longtemps que le Site est disponible à titre pédagogique. L'équipe étudiante responsable se réserve le droit de fermer ou de modifier le Site à tout moment dans le cadre de son projet scolaire.</p>
    </div>

    <!-- FOOTER INFORMATION -->
    <div style="background-color: #f0f0f0; padding: 20px; border-radius: 6px; margin-top: 40px; text-align: center;">
        <p><strong>Dernière mise à jour :</strong> <span id="date">09/12/2025</span></p>
        <p><strong>Version :</strong> 1.0</p>
    </div>

    <!-- CONFIRMATION BOX -->
    <div class="confirmation-box">
        ✓ En accédant et en utilisant ce Site, vous confirmez avoir lu, compris et accepté intégralement les présentes Conditions Générales d'Utilisation, notamment le caractère fictif et pédagogique de l'ensemble des contenus proposés.
    </div>

  </div>
</section>