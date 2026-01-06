-- ════════════════════════════════════════════════════════════════════════════
-- MODIFICATION TABLE USERS - Ajout du rôle administrateur
-- ════════════════════════════════════════════════════════════════════════════

ALTER TABLE users 
ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' NOT NULL AFTER email_verified;

-- ════════════════════════════════════════════════════════════════════════════
-- TABLE FAQ - Gestion des questions fréquemment posées
-- ════════════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS faq (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100) DEFAULT 'Général' NOT NULL,
    display_order INT UNSIGNED DEFAULT 0 NOT NULL,
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_order (display_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════════════════════
-- DONNÉES D'EXEMPLE - Questions FAQ par défaut
-- ════════════════════════════════════════════════════════════════════════════

INSERT INTO faq (question, answer, category, display_order, is_active) VALUES
('Comment publier une annonce ?', 'Pour publier une annonce, connectez-vous à votre compte, cliquez sur "Vendre mon véhicule" dans le menu, remplissez le formulaire avec les informations de votre véhicule et ajoutez des photos. Votre annonce sera publiée immédiatement.', 'Vente', 1, TRUE),
('Comment contacter un vendeur ?', 'Sur la page de détail d\'un véhicule, cliquez sur le bouton "Contacter le vendeur". Vous pourrez envoyer un message privé et faire des propositions de prix directement via notre messagerie sécurisée.', 'Achat', 2, TRUE),
('Combien coûte la publication d\'une annonce ?', 'La publication d\'annonces sur ReVente-Auto est totalement gratuite. Aucun frais d\'inscription ni de commission sur les ventes.', 'Tarifs', 3, TRUE),
('Comment fonctionne l\'estimation IA ?', 'Notre outil d\'estimation utilise l\'intelligence artificielle pour analyser les caractéristiques de votre véhicule (marque, modèle, année, kilométrage, état) et compare avec les prix du marché pour vous donner une estimation précise.', 'Estimation', 4, TRUE),
('Qu\'est-ce que le Score IA ?', 'Le Score IA (0-100) évalue si une annonce représente une bonne affaire en comparant le prix demandé à la valeur estimée du marché. Plus le score est élevé, meilleure est l\'opportunité.', 'Estimation', 5, TRUE),
('Mes messages sont-ils sécurisés ?', 'Oui, tous les messages échangés sur ReVente-Auto sont chiffrés de bout en bout (E2E). Seuls vous et votre interlocuteur pouvez lire vos conversations.', 'Sécurité', 6, TRUE),
('Comment modifier mon annonce ?', 'Connectez-vous, accédez à "Mes Paramètres", puis "Mes Annonces". Cliquez sur l\'annonce que vous souhaitez modifier et cliquez sur le bouton "Modifier".', 'Vente', 7, TRUE),
('Comment supprimer mon compte ?', 'Dans vos paramètres de profil, descendez jusqu\'à la section "Zone de Danger" et cliquez sur "Supprimer mon compte". Attention, cette action est irréversible.', 'Compte', 8, TRUE);

-- ════════════════════════════════════════════════════════════════════════════
-- CRÉER UN UTILISATEUR ADMIN PAR DÉFAUT
-- ════════════════════════════════════════════════════════════════════════════

-- ⚠️ IMPORTANT : Mot de passe par défaut = Admin123!
-- ⚠️ À CHANGER IMPÉRATIVEMENT après la première connexion !

-- Créer l'utilisateur admin
INSERT INTO users (
    first_name,
    last_name,
    email,
    password_hash,
    role,
    email_verified,
    created_at
) VALUES (
    'Antoine',
    'Perez',
    'antoine.perez@eleve.isep.fr',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Hash BCRYPT de "Admin123!"
    'admin',
    TRUE,
    CURRENT_TIMESTAMP
) ON DUPLICATE KEY UPDATE 
    role = 'admin',
    email_verified = TRUE;

-- ════════════════════════════════════════════════════════════════════════════
-- ALTERNATIVE : Si vous souhaitez promouvoir un utilisateur existant en admin
-- ════════════════════════════════════════════════════════════════════════════

-- Décommenter la ligne suivante et remplacer l'email par celui de votre utilisateur :
-- UPDATE users SET role = 'admin' WHERE email = 'votre-email@exemple.com' LIMIT 1;
