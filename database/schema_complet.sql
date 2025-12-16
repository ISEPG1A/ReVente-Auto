-- ReVente-Auto — Schéma Complet (Structure + Données)
-- Généré le 26 novembre 2025

-- 1. Création et sélection de la base
CREATE DATABASE IF NOT EXISTS `revente_auto` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `revente_auto`;

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
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar_path` VARCHAR(255) NULL,
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
  `email_verified_at` DATETIME NULL,
  `phone_verified_at` DATETIME NULL,
  `phone_code` VARCHAR(10) NULL,
  `phone_code_expires_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `public_key` TEXT NULL,
  `private_key` TEXT NULL,
  PRIMARY KEY (`id`)
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
  PRIMARY KEY (`id`),
  INDEX `idx_type_vehicule` (`type_vehicule`),
  INDEX `idx_etat` (`etat`),
  INDEX `idx_marque` (`marque`),
  INDEX `idx_modele` (`modele`),
  INDEX `idx_annee` (`annee`),
  INDEX `idx_user_id` (`user_id`),
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
  `buyer_id` INT UNSIGNED NOT NULL,
  `seller_id` INT UNSIGNED NOT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `seller_id` (`seller_id`),
  CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Table Messages
CREATE TABLE `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED NOT NULL,
  `sender_id` INT UNSIGNED NOT NULL,
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
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
  `sender_id` INT UNSIGNED NOT NULL,
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
  CONSTRAINT `fk_offer_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;
