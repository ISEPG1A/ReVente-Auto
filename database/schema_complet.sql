-- ReVente-Auto — Schéma Complet (Structure + Données)
-- Généré le 26 novembre 2025

-- 1. Création et sélection de la base
CREATE DATABASE IF NOT EXISTS `hangardb_maae62929` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hangardb_maae62929`;

-- ==========================================
-- NETTOYAGE (Suppression des tables existantes)
-- Ordre inverse des dépendances pour éviter l'erreur #1451
-- ==========================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `offers`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `conversations`;
DROP TABLE IF EXISTS `vehicle_images`;
DROP TABLE IF EXISTS `favorites`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `email_verifications`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================
-- CRÉATION DES TABLES
-- ==========================================

-- 2. Table Users
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(60) NOT NULL,
  `last_name` VARCHAR(60) NOT NULL,
  `email` VARCHAR(190) NOT NULL UNIQUE,
  `phone` VARCHAR(30) NOT NULL,
  `hide_phone` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Si 1, le numéro de téléphone sera masqué sur les annonces',
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar_path` VARCHAR(255) NULL,
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
  `banned_at` DATETIME NULL COMMENT 'Date de bannissement, NULL si non banni',
  `ban_reason` TEXT NULL COMMENT 'Raison du bannissement',
  `banned_by` INT UNSIGNED NULL COMMENT 'ID de l admin qui a banni',
  `email_verified_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` DATETIME NULL COMMENT 'Date de dernière connexion',
  `public_key` TEXT NULL,
  `private_key` TEXT NULL,
  `session_token` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Token unique pour invalider toutes les sessions après changement de mot de passe',
  PRIMARY KEY (`id`),
  INDEX `idx_session_token` (`session_token`),
  INDEX `idx_hide_phone` (`hide_phone`),
  INDEX `idx_banned` (`banned_at`),
  INDEX `idx_last_login` (`last_login_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table Vehicles
CREATE TABLE `vehicles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type_vehicule` ENUM('voiture', 'moto', 'camion') NOT NULL DEFAULT 'voiture',
  `marque` VARCHAR(50) NOT NULL,
  `modele` VARCHAR(50) NOT NULL,
  `annee` INT NOT NULL,
  `prix` DECIMAL(10,2) NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `km` INT UNSIGNED NULL,
  `carburant` VARCHAR(20) NULL,
  `boite` VARCHAR(20) NULL,
  `description` TEXT NULL,
  `code_postal` VARCHAR(5) NULL COMMENT 'Code postal français (5 chiffres)',
  `ville` VARCHAR(100) NULL,
  `image_path` VARCHAR(255) NULL,
  -- Nouveaux champs
  `etat` ENUM('neuf', 'bon', 'moyen', 'mauvais') NULL,
  `crit_air` ENUM('0', '1', '2', '3', '4', '5') NULL,
  `provenance` VARCHAR(100) NULL,
  `controle_technique` ENUM('oui', 'non', 'non_requis') NULL DEFAULT 'non_requis',
  `couleur` VARCHAR(50) NULL,
  `nb_portes` TINYINT UNSIGNED NULL,
  `nb_places` TINYINT UNSIGNED NULL,
  `longueur` DECIMAL(5,2) NULL COMMENT 'en mètres',
  `largeur` DECIMAL(5,2) NULL COMMENT 'en mètres',
  `hauteur` DECIMAL(5,2) NULL COMMENT 'en mètres',
  `taille_coffre` ENUM('petit', 'moyen', 'grand') NULL,
  `puissance_cv` SMALLINT UNSIGNED NULL,
  `norme_euro` ENUM('Euro 1', 'Euro 2', 'Euro 3', 'Euro 4', 'Euro 5', 'Euro 6', 'Euro 6d') NULL COMMENT 'Optionnel - peut être laissé vide',
  `consommation` DECIMAL(4,1) NULL COMMENT 'Consommation principale (L/100km ou kWh/100km)',
  `consommation_secondaire` DECIMAL(4,1) NULL COMMENT 'Consommation secondaire pour hybrides (L/100km ou kWh/100km)',
  `type_hybride` ENUM('essence_electrique', 'diesel_electrique', 'essence_electrique_rechargeable', 'diesel_electrique_rechargeable', 'gpl_essence') NULL COMMENT 'Type d hybride si carburant = Hybride',
  `emission_co2` SMALLINT UNSIGNED NULL COMMENT 'g/km',
  `autonomie` SMALLINT UNSIGNED NULL COMMENT 'km (pour véhicules électriques/hybrides)',
  `score_ia` TINYINT UNSIGNED NULL COMMENT 'Score IA de 0 (mauvaise affaire) à 100 (excellente affaire)',
  `status` ENUM('public', 'prive', 'en_attente', 'refuse') NOT NULL DEFAULT 'en_attente' COMMENT 'Statut de l annonce: en_attente = modération requise',
  `raison_refus` TEXT NULL COMMENT 'Raison du refus par l administrateur',
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Nombre de consultations',
  `contacts_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Nombre de contacts reçus',
  `favorites_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Nombre d ajouts aux favoris',
  `latitude` DECIMAL(10,8) NULL COMMENT 'Latitude GPS pour recherche par proximité',
  `longitude` DECIMAL(11,8) NULL COMMENT 'Longitude GPS pour recherche par proximité',
  PRIMARY KEY (`id`),
  INDEX `idx_type_vehicule` (`type_vehicule`),
  INDEX `idx_etat` (`etat`),
  INDEX `idx_marque` (`marque`),
  INDEX `idx_modele` (`modele`),
  INDEX `idx_annee` (`annee`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_score_ia` (`score_ia`),
  INDEX `idx_status` (`status`),
  INDEX `idx_user_status` (`user_id`, `status`),
  INDEX `idx_coordinates` (`latitude`, `longitude`),
  CONSTRAINT `fk_vehicle_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table Vehicle Images (NOUVELLE TABLE)
CREATE TABLE `vehicle_images` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `vehicle_id` INT UNSIGNED NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `fk_vi_vehicle` (`vehicle_id`),
    CONSTRAINT `fk_vi_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table Conversations
CREATE TABLE `conversations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vehicle_id` INT UNSIGNED DEFAULT NULL,
  `buyer_id` INT UNSIGNED DEFAULT NULL COMMENT 'Peut être NULL si compte supprimé',
  `seller_id` INT UNSIGNED DEFAULT NULL COMMENT 'Peut être NULL si compte supprimé',
  `buyer_deleted_at` DATETIME DEFAULT NULL COMMENT 'Date de suppression du compte acheteur',
  `seller_deleted_at` DATETIME DEFAULT NULL COMMENT 'Date de suppression du compte vendeur',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `seller_id` (`seller_id`),
  CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conversations_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Table Messages
CREATE TABLE `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED NOT NULL,
  `sender_id` INT UNSIGNED DEFAULT NULL COMMENT 'Peut être NULL si compte supprimé',
  `content` TEXT NOT NULL,
  `iv` TEXT NOT NULL,
  `encrypted_key` TEXT NOT NULL,
  `encrypted_key_sender` TEXT DEFAULT NULL,
  `is_read` TINYINT(4) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. Table Favorites
CREATE TABLE `favorites` (
  `user_id` INT UNSIGNED NOT NULL,
  `vehicle_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `vehicle_id`),
  KEY `fk_fav_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table Password Resets
CREATE TABLE `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(191) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `fk_pr_user` (`user_id`),
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Table Email Verifications
CREATE TABLE `email_verifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(191) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `fk_ev_user` (`user_id`),
  CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Table des propositions de prix
CREATE TABLE `offers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `conversation_id` INT UNSIGNED NOT NULL,
  `sender_id` INT UNSIGNED DEFAULT NULL COMMENT 'Peut être NULL si compte supprimé',
  `amount` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('pending', 'accepted', 'declined', 'expired', 'cancelled', 'paid') DEFAULT 'pending',
  `expires_at` DATETIME NOT NULL,
  `accepted_at` DATETIME DEFAULT NULL,
  `payment_expires_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_conversation_status` (`conversation_id`, `status`),
  INDEX `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_offer_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_offer_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Table des demandes de changement d'email
CREATE TABLE `changements_email` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_utilisateur` INT UNSIGNED NOT NULL,
  `nouvel_email` VARCHAR(190) NOT NULL,
  `jeton` VARCHAR(191) NOT NULL,
  `expire_le` DATETIME NOT NULL,
  `utilise_le` DATETIME NULL,
  `cree_le` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jeton` (`jeton`),
  KEY `fk_ce_utilisateur` (`id_utilisateur`),
  CONSTRAINT `fk_ce_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 12. Table admin_logs - Historique permanent des activités
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('inscription', 'annonce', 'suppression_compte', 'suppression_annonce', 'moderation', 'contact', 'autre') NOT NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'Description courte de l action',
  `details` JSON NULL COMMENT 'Détails au format JSON (nom, email, marque, modele, etc.)',
  `user_id` INT UNSIGNED NULL COMMENT 'ID utilisateur concerné (peut être NULL si supprimé)',
  `vehicle_id` INT UNSIGNED NULL COMMENT 'ID véhicule concerné (peut être NULL si supprimé)',
  `admin_id` INT UNSIGNED NULL COMMENT 'ID admin qui a effectué l action (si applicable)',
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_vehicle_id` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Table contacts - Messages de la page contact
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `sujet` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `user_id` INT UNSIGNED NULL COMMENT 'ID si l utilisateur est connecté',
  `status` ENUM('nouveau', 'lu', 'traite', 'archive') NOT NULL DEFAULT 'nouveau',
  `reponse` TEXT NULL COMMENT 'Réponse de l admin',
  `repondu_par` INT UNSIGNED NULL COMMENT 'ID admin qui a répondu',
  `repondu_le` DATETIME NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Table contenu_statique - FAQ, CGU, Confidentialité
CREATE TABLE IF NOT EXISTS `contenu_statique` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('faq', 'cgu', 'confidentialite') NOT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `contenu` TEXT NOT NULL,
  `ordre` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Ordre d affichage pour FAQ',
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `modifie_par` INT UNSIGNED NULL COMMENT 'Dernier admin à avoir modifié',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_type_actif` (`type`, `actif`),
  INDEX `idx_ordre` (`ordre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Table rate_limits - Limitation de tentatives (anti brute-force)
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` VARCHAR(50) NOT NULL COMMENT 'Type d action: login, upload, password_reset',
  `identifier` VARCHAR(64) NOT NULL COMMENT 'Hash MD5 de IP+email ou IP seul',
  `ip_address` VARCHAR(45) NOT NULL COMMENT 'Adresse IP originale',
  `attempts` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Nombre de tentatives',
  `expires_at` DATETIME NOT NULL COMMENT 'Date d expiration du blocage',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_action_identifier` (`action`, `identifier`),
  INDEX `idx_expires_at` (`expires_at`),
  INDEX `idx_ip_action` (`ip_address`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stocke les tentatives pour le rate limiting';

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;
