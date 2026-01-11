-- ═══════════════════════════════════════════════════════════════════════════
-- SCHÉMA BASE DE DONNÉES - SYSTÈME CGU (Conditions Générales d'Utilisation)
-- ═══════════════════════════════════════════════════════════════════════════
-- 
-- Ce fichier contient la structure de la table pour gérer les articles des CGU
-- de manière dynamique depuis la base de données.
-- 
-- Fonctionnalités :
-- - Articles numérotés avec titre et contenu
-- - Ordre personnalisable (pour drag-and-drop futur)
-- - Statut brouillon/publié
-- - Horodatage automatique des modifications
-- 
-- @author  mat
-- @version 1.0
-- ═══════════════════════════════════════════════════════════════════════════

-- Table des articles CGU
CREATE TABLE IF NOT EXISTS cgu_articles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  numero_article INT NOT NULL, -- est ce que le numéro d'article est utile ? 
  titre VARCHAR(255) NOT NULL,
  contenu TEXT NOT NULL,
  ordre INT NOT NULL DEFAULT 0, -- est ce que le ordre est utile avec num article ?
  statut ENUM('brouillon', 'publie') NOT NULL DEFAULT 'publie',
  visible BOOLEAN NOT NULL DEFAULT TRUE, -- est ce que c'est utile ?
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Index pour optimiser les requêtes
  INDEX idx_ordre (ordre),
  INDEX idx_statut_visible (statut, visible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- DONNÉES D'EXEMPLE (à supprimer ou adapter selon vos besoins)
-- ═══════════════════════════════════════════════════════════════════════════

-- Insertion de quelques articles d'exemple pour démarrer
INSERT INTO cgu_articles (numero_article, titre, contenu, ordre, statut) VALUES
(1, 'Objet et Définitions', 
'<h3>1.1 - Objet</h3>
<p>Les présentes Conditions Générales d''Utilisation (ci-après « <strong>CGU</strong> ») régissent les conditions d''accès et d''utilisation de la plateforme web fictive de simulation de revente automobile (ci-après le « <strong>Site</strong> »). Le Site est une plateforme pédagogique ayant pour but de simuler le fonctionnement d''un portail de petites annonces automobiles, sans transaction commerciale réelle.</p>

<h3>1.2 - Définitions</h3>
<ul>
    <li><strong>Site</strong> : la plateforme web accessible à l''adresse [https://exemple-revente-auto.edu]</li>
    <li><strong>Utilisateur</strong> : toute personne accédant au Site et acceptant les présentes CGU</li>
    <li><strong>Contenu</strong> : l''ensemble des informations, annonces automobiles simulées, textes, images, vidéos et autres éléments présents sur le Site</li>
    <li><strong>Services</strong> : les fonctionnalités proposées par le Site, notamment la consultation des annonces et la création de comptes utilisateur fictifs</li>
    <li><strong>Annonces</strong> : les listings automobiles fictifs présentés sur le Site</li>
</ul>', 
1, 'publie'),

(2, 'Acceptation des CGU', 
'<h3>2.1 - Consentement</h3>
<p>L''accès au Site et l''utilisation de ses Services impliquent l''acceptation intégrale et sans réserve des présentes CGU. Toute personne qui n''accepte pas les présentes conditions s''abstient de consulter et d''utiliser le Site.</p>

<h3>2.2 - Modifications des CGU</h3>
<p>L''équipe étudiante responsable du Site se réserve le droit de modifier les présentes CGU à tout moment, sans préavis. L''utilisateur est invité à consulter régulièrement ces conditions. La continuation de l''utilisation du Site après modification vaut acceptation des nouvelles conditions.</p>', 
2, 'publie'),

(3, 'Caractère Fictif et Pédagogique du Contenu', 
'<h3>3.1 - Nature fictive du contenu</h3>
<p>L''utilisateur reconnaît et accepte que <strong>tous les contenus présents sur le Site sont fictifs</strong> et produits à des fins éducatives. Cela inclut, sans limitation :</p>
<ul>
    <li>Les annonces automobiles et descriptions de véhicules</li>
    <li>Les photographies, images et visuels associés</li>
    <li>Les tarifs, prix et conditions tarifaires affichés</li>
    <li>Les données techniques, historiques de révision, kilométrage indiqué</li>
    <li>Les profils des vendeurs simulés</li>
    <li>Tous les autres éléments informatifs ou promotionnels</li>
</ul>

<h3>3.2 - Absence de transaction réelle</h3>
<p>L''utilisateur est explicitement informé qu''<strong>aucune transaction commerciale, aucun achat, aucune vente et aucun contrat</strong> ne peuvent être conclus via ce Site. Le Site ne constitue en aucun cas une plateforme de commerce électronique opérationnelle.</p>

<h3>3.3 - Finalité éducative</h3>
<p>Le Site est conçu <strong>exclusivement à titre pédagogique</strong> dans le cadre d''un projet scolaire. Son objectif est de permettre aux apprenants de se familiariser avec le fonctionnement et les usages des plateformes de petites annonces automobiles.</p>', 
3, 'publie');
