-- ReVente-Auto — Schéma MySQL pour XAMPP
-- Importer ce fichier dans phpMyAdmin après avoir sélectionné la base 'revente_auto'



-- Création et sélection de la base ReVente-Auto
CREATE DATABASE IF NOT EXISTS `revente_auto` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `revente_auto`;


-- Users table for authentication (must be created before vehicles for FK)
CREATE TABLE IF NOT EXISTS `users` (
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
  PRIMARY KEY (`id`)
)
ENGINE=InnoDB;

-- Vehicles table (references users)
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `marque` VARCHAR(50) NOT NULL,
  `modele` VARCHAR(50) NOT NULL,
  `annee` INT NOT NULL,
  `prix` DECIMAL(10,2) NOT NULL,
  `km` INT UNSIGNED NULL,
  `carburant` VARCHAR(20) NULL,
  `boite` VARCHAR(20) NULL,
  `description` TEXT NULL,
  `ville` VARCHAR(100) NULL,
  `image_path` VARCHAR(255) NULL,
  `user_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_marque` (`marque`),
  INDEX `idx_modele` (`modele`),
  INDEX `idx_annee` (`annee`),
  INDEX `idx_user_id` (`user_id`),
  CONSTRAINT `fk_vehicle_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
)
ENGINE=InnoDB;

-- Favorites table (many-to-many relationship between users and vehicles)
CREATE TABLE IF NOT EXISTS `favorites` (
  `user_id` INT UNSIGNED NOT NULL,
  `vehicle_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `vehicle_id`),
  CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO `vehicles` (marque, modele, annee, prix) VALUES
('Peugeot', '208', 2021, 14990.00),
('Renault', 'Clio', 2020, 12990.00),
('Volkswagen', 'Golf', 2022, 22990.00),
('Toyota', 'Yaris', 2019, 10990.00),
('Citroën', 'C3', 2022, 15990.00);

-- Password reset tokens
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(191) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE (`token`)
)
ENGINE=InnoDB;

-- Email verification tokens
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(191) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE (`token`)
)
ENGINE=InnoDB;
