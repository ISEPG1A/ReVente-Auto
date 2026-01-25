CREATE DATABASE IF NOT EXISTS `hangardb_maae62929` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hangardb_maae62929`;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `admin_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` enum('inscription','connexion','deconnexion','annonce_creation','annonce_modification','annonce_suppression','annonce_statut','suppression_compte','moderation','contact','utilisateur','profil','securite','favori','message','cgu','faq','politique','estimation','autre') NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `vehicle_id` int(10) UNSIGNED DEFAULT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cgu_articles` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero` int(10) UNSIGNED NOT NULL COMMENT 'Numero = ordre affichage (1, 2, 3...)',
  `titre` varchar(255) NOT NULL,
  `statut` enum('brouillon','publie') NOT NULL DEFAULT 'publie',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cgu_points` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `numero` int(10) UNSIGNED NOT NULL COMMENT 'Numero dans section (1, 2, 3... pour 1.1.1, 1.1.2)',
  `titre` varchar(255) DEFAULT NULL,
  `contenu` text NOT NULL COMMENT 'Contenu texte obligatoire',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cgu_sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `article_id` int(10) UNSIGNED NOT NULL,
  `numero` int(10) UNSIGNED NOT NULL COMMENT 'Numero dans article (1, 2, 3... pour 1.1, 1.2, 1.3)',
  `titre` varchar(255) NOT NULL,
  `contenu` text DEFAULT NULL COMMENT 'Contenu texte OU null si a des points',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cgu_versions` (
  `id` int(11) NOT NULL,
  `date_creation` datetime NOT NULL COMMENT 'Date de création de cette version',
  `hash_contenu` varchar(64) NOT NULL COMMENT 'Hash SHA256 du contenu pour détecter les changements',
  `nom_fichier` varchar(255) NOT NULL COMMENT 'Nom du fichier PDF archivé',
  `taille_fichier` int(10) UNSIGNED DEFAULT 0 COMMENT 'Taille du fichier en bytes',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `changements_email` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `nouvel_email` varchar(190) NOT NULL,
  `jeton` varchar(191) NOT NULL,
  `expire_le` datetime NOT NULL,
  `utilise_le` datetime DEFAULT NULL,
  `cree_le` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contacts` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `sujet` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('nouveau','lu','traite','archive') NOT NULL DEFAULT 'nouveau',
  `reponse` text DEFAULT NULL,
  `repondu_par` int(10) UNSIGNED DEFAULT NULL,
  `repondu_le` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `conversations` (
  `id` int(10) UNSIGNED NOT NULL,
  `vehicle_id` int(10) UNSIGNED DEFAULT NULL,
  `buyer_id` int(10) UNSIGNED DEFAULT NULL,
  `seller_id` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `buyer_deleted_at` datetime DEFAULT NULL COMMENT 'Date de suppression du compte acheteur',
  `seller_deleted_at` datetime DEFAULT NULL COMMENT 'Date de suppression du compte vendeur'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `email_verifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(191) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `faq` (
  `id` int(11) NOT NULL,
  `question` varchar(500) NOT NULL COMMENT 'Question de la FAQ',
  `reponse` text NOT NULL COMMENT 'Réponse en HTML',
  `ordre` int(11) NOT NULL DEFAULT 0 COMMENT 'Ordre d affichage',
  `statut` enum('brouillon','publie') NOT NULL DEFAULT 'publie',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `favorites` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `vehicle_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Peut être NULL si compte supprimé',
  `content` text NOT NULL,
  `iv` text NOT NULL,
  `encrypted_key` text NOT NULL,
  `encrypted_key_sender` text DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `offers` (
  `id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Peut être NULL si compte supprimé',
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','accepted','declined','expired','cancelled','paid') DEFAULT 'pending',
  `expires_at` datetime NOT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `payment_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(191) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `politique_confidentialite` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL COMMENT 'Titre de la section',
  `contenu` text NOT NULL COMMENT 'Contenu de la section',
  `ordre` int(11) NOT NULL DEFAULT 0 COMMENT 'Ordre d affichage',
  `statut` enum('brouillon','publie') NOT NULL DEFAULT 'publie',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rate_limits` (
  `id` int(10) UNSIGNED NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'Type action: login, upload, password_reset, etc.',
  `identifier` varchar(255) NOT NULL COMMENT 'Clé unique: hash de IP ou IP+email',
  `ip_address` varchar(45) NOT NULL COMMENT 'Adresse IP pour référence',
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Nombre de tentatives',
  `expires_at` datetime NOT NULL COMMENT 'Date expiration du blocage',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `hide_phone` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si 1, le numéro de téléphone sera masqué sur les annonces',
  `password_hash` varchar(255) NOT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `banned_at` datetime DEFAULT NULL,
  `ban_reason` text DEFAULT NULL,
  `banned_by` int(10) UNSIGNED DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login_at` datetime DEFAULT NULL,
  `public_key` text DEFAULT NULL,
  `private_key` text DEFAULT NULL,
  `session_token` varchar(64) DEFAULT NULL,
  `poste` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vehicles` (
  `id` int(10) UNSIGNED NOT NULL,
  `type_vehicule` enum('voiture','moto','camion') NOT NULL DEFAULT 'voiture',
  `marque` varchar(50) NOT NULL,
  `modele` varchar(50) NOT NULL,
  `annee` int(11) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `km` int(10) UNSIGNED DEFAULT NULL,
  `carburant` varchar(20) DEFAULT NULL,
  `boite` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `code_postal` varchar(5) DEFAULT NULL COMMENT 'Code postal français (5 chiffres)',
  `ville` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `etat` enum('neuf','bon','moyen','mauvais') DEFAULT NULL,
  `crit_air` enum('0','1','2','3','4','5') DEFAULT NULL,
  `provenance` varchar(100) DEFAULT NULL,
  `controle_technique` enum('oui','non','non_requis') DEFAULT 'non_requis',
  `couleur` varchar(50) DEFAULT NULL,
  `nb_portes` tinyint(3) UNSIGNED DEFAULT NULL,
  `nb_places` tinyint(3) UNSIGNED DEFAULT NULL,
  `longueur` decimal(5,2) DEFAULT NULL COMMENT 'en mètres',
  `largeur` decimal(5,2) DEFAULT NULL COMMENT 'en mètres',
  `hauteur` decimal(5,2) DEFAULT NULL COMMENT 'en mètres',
  `taille_coffre` enum('petit','moyen','grand') DEFAULT NULL,
  `puissance_cv` smallint(5) UNSIGNED DEFAULT NULL,
  `norme_euro` enum('Euro 1','Euro 2','Euro 3','Euro 4','Euro 5','Euro 6','Euro 6d') DEFAULT NULL COMMENT 'Optionnel - peut être laissé vide',
  `consommation` decimal(4,1) DEFAULT NULL COMMENT 'Consommation principale (L/100km ou kWh/100km)',
  `consommation_secondaire` decimal(4,1) DEFAULT NULL COMMENT 'Consommation secondaire pour hybrides (L/100km ou kWh/100km)',
  `type_hybride` enum('essence_electrique','diesel_electrique','essence_electrique_rechargeable','diesel_electrique_rechargeable','gpl_essence') DEFAULT NULL COMMENT 'Type d hybride si carburant = Hybride',
  `emission_co2` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'g/km',
  `autonomie` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'km (pour véhicules électriques/hybrides)',
  `score_ia` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Score IA de 0 (mauvaise affaire) à 100 (excellente affaire)',
  `status` enum('public','prive','en_attente','refuse') NOT NULL DEFAULT 'en_attente',
  `raison_refus` text DEFAULT NULL,
  `views_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `contacts_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `favorites_count` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vehicle_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `vehicle_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`);

ALTER TABLE `cgu_articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_numero` (`numero`);

ALTER TABLE `cgu_points`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_section_numero` (`section_id`,`numero`);

ALTER TABLE `cgu_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_article_numero` (`article_id`,`numero`);

ALTER TABLE `cgu_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date_creation` (`date_creation`),
  ADD KEY `idx_hash` (`hash_contenu`);

ALTER TABLE `changements_email`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jeton` (`jeton`),
  ADD KEY `fk_ce_utilisateur` (`id_utilisateur`);

ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`);

ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_ev_user` (`user_id`);

ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ordre` (`ordre`),
  ADD KEY `idx_statut` (`statut`);

ALTER TABLE `favorites`
  ADD PRIMARY KEY (`user_id`,`vehicle_id`),
  ADD KEY `fk_fav_vehicle` (`vehicle_id`);

ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `idx_messages_created_at` (`created_at`);

ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation_status` (`conversation_id`,`status`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `fk_offer_sender` (`sender_id`);

ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_pr_user` (`user_id`);

ALTER TABLE `politique_confidentialite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ordre` (`ordre`),
  ADD KEY `idx_statut` (`statut`);

ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_action_identifier` (`action`,`identifier`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_ip_address` (`ip_address`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_hide_phone` (`hide_phone`),
  ADD KEY `idx_banned` (`banned_at`),
  ADD KEY `idx_last_login` (`last_login_at`);

ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type_vehicule` (`type_vehicule`),
  ADD KEY `idx_etat` (`etat`),
  ADD KEY `idx_marque` (`marque`),
  ADD KEY `idx_modele` (`modele`),
  ADD KEY `idx_annee` (`annee`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_score_ia` (`score_ia`),
  ADD KEY `idx_code_postal` (`code_postal`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_coordinates` (`latitude`,`longitude`),
  ADD KEY `idx_vehicles_prix` (`prix`),
  ADD KEY `idx_vehicles_status` (`status`),
  ADD KEY `idx_vehicles_statut` (`status`);

ALTER TABLE `vehicle_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vi_vehicle` (`vehicle_id`);


ALTER TABLE `admin_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `cgu_articles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `cgu_points`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `cgu_sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `cgu_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `changements_email`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `contacts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `conversations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `email_verifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `offers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `politique_confidentialite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `rate_limits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `vehicles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `vehicle_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;


ALTER TABLE `cgu_points`
  ADD CONSTRAINT `cgu_points_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `cgu_sections` (`id`) ON DELETE CASCADE;

ALTER TABLE `cgu_sections`
  ADD CONSTRAINT `cgu_sections_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `cgu_articles` (`id`) ON DELETE CASCADE;

ALTER TABLE `changements_email`
  ADD CONSTRAINT `fk_ce_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `conversations_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `email_verifications`
  ADD CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fav_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `offers`
  ADD CONSTRAINT `fk_offer_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_offer_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `vehicles`
  ADD CONSTRAINT `fk_vehicle_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `vehicle_images`
  ADD CONSTRAINT `fk_vi_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
