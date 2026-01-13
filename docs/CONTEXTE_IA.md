# 🤖 CONTEXTE IA - ReVente-Auto

> **Ce fichier est destiné aux assistants IA** (Claude, ChatGPT, Gemini, etc.)
> Il contient TOUTES les informations nécessaires pour comprendre, modifier et étendre ce projet sans créer de duplications ou d'incohérences.

## ⚠️ INSTRUCTIONS IMPÉRATIVES POUR L'IA

**AVANT de créer, modifier ou supprimer QUOI QUE CE SOIT :**

1. ✅ **LIRE INTÉGRALEMENT** ce fichier ligne par ligne
2. ✅ **VÉRIFIER** la structure des dossiers détaillée ci-dessous
3. ✅ **CHERCHER** si le fichier/fonction/classe existe déjà avec `grep_search` ou `file_search`
4. ✅ **CONSULTER** la section "Checklist Anti-Duplication" avant toute action
5. ✅ **RESPECTER** les conventions de nommage françaises (variables, fonctions, classes)
6. ✅ **UTILISER** les classes utilitaires existantes (`Utilitaires`, `GestionnaireSession`, etc.)
7. ✅ **NE JAMAIS** créer de doublons de fichiers CSS ou JavaScript
8. ✅ **NE JAMAIS** recréer des fonctions qui existent déjà dans `application.js`
9. ✅ **UTILISER** `Utilitaires::versionAsset()` pour le cache busting des CSS/JS

**Si vous ne suivez pas ces instructions, vous créerez des duplications et des incohérences.**

---

## 📋 TABLE DES MATIÈRES

1. [Présentation du Projet](#-présentation-du-projet)
2. [Conventions de Codage](#-conventions-de-codage-obligatoires)
3. [Architecture Globale](#-architecture-globale)
4. [Structure des Dossiers](#-structure-des-dossiers-détaillée)
5. [Fichiers de Configuration](#-fichiers-de-configuration)
6. [Backend PHP - Détail Complet](#-backend-php---détail-complet)
7. [Frontend - CSS et JavaScript](#-frontend---css-et-javascript)
8. [Base de Données](#-base-de-données)
9. [Sécurité Implémentée](#-sécurité-implémentée)
10. [Intégration IA (OpenAI)](#-intégration-ia-openai)
11. [Guide de Modification](#-guide-de-modification)
12. [Checklist Anti-Duplication](#-checklist-anti-duplication)

---

## 🚗 PRÉSENTATION DU PROJET

**ReVente-Auto** est une plateforme de vente de véhicules d'occasion développée en PHP vanilla (sans framework).

### Fonctionnalités Principales
- 📋 **Galerie de véhicules** avec filtres avancés (type, marque, prix, km, carburant, etc.)
- 🗺️ **Recherche par proximité** géographique avec latitude/longitude
- ➕ **Ajout/Modification d'annonces** avec upload multi-images
- 🔐 **Authentification complète** (inscription, connexion, réinitialisation mot de passe, vérification email)
- 💬 **Messagerie chiffrée E2E** entre acheteurs et vendeurs
- ❤️ **Système de favoris**
- 🤖 **Estimation IA** du prix via OpenAI
- 📊 **Score IA** (0-100) pour évaluer si une annonce est une bonne affaire
- 💰 **Propositions de prix** avec statuts (pending, accepted, declined, expired)
- 👨‍💼 **Panel Admin** pour modération des annonces et utilisateurs

### Stack Technique
- **Backend** : PHP 8+ (vanilla, sans framework)
- **Frontend** : HTML5, CSS3 (variables CSS), JavaScript ES6 (modules)
- **Base de données** : MySQL/MariaDB
- **API externe** : OpenAI GPT-4o-mini
- **Email** : SMTP (Gmail)
- **Timezone** : Europe/Paris

---

## 📐 CONVENTIONS DE CODAGE (OBLIGATOIRES)

### ⚠️ RÈGLES IMPÉRATIVES

| Élément | Convention | Exemple |
|---------|------------|---------|
| **Noms de variables** | Français, camelCase | `$utilisateur`, `$prixMinimum`, `$idVehicule` |
| **Noms de fonctions** | Français, camelCase | `obtenirTous()`, `validerTokenCSRF()`, `envoyerJSON()` |
| **Noms de classes** | Français, PascalCase | `ModeleVehicule`, `ControleurConnexion`, `GestionnaireSession` |
| **Noms de fichiers PHP** | Français, PascalCase | `ModeleVehicule.php`, `ControleurGalerie.php` |
| **Noms de fichiers JS** | Français, PascalCase | `VueGalerie.js`, `VueConnexion.js` |
| **Noms de fichiers CSS** | Français, kebab-case | `barre-outils.css`, `pied-de-page.css` |
| **Commentaires** | Français | `// Vérifier si l'utilisateur est connecté` |
| **Messages d'erreur** | Français | `'Authentification requise'`, `'Token CSRF invalide'` |
| **Clés JSON API** | Anglais (standard) | `'error'`, `'ok'`, `'user'`, `'vehicle'` |
| **Colonnes BDD** | Anglais (standard) | `user_id`, `created_at`, `image_path` |

### Réponses API Standardisées

```php
// Succès
Utilitaires::envoyerJSON(['ok' => true, 'data' => $donnees]);
Utilitaires::envoyerJSON(['ok' => true, 'user' => $utilisateur]);
Utilitaires::envoyerJSON(['ok' => true, 'vehicle' => $vehicule], 201);

// Erreurs
Utilitaires::envoyerJSON(['error' => 'Message d\'erreur en français'], 400);
Utilitaires::envoyerJSON(['error' => 'Authentification requise'], 401);
Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
Utilitaires::envoyerJSON(['error' => 'Ressource introuvable'], 404);
Utilitaires::envoyerJSON(['error' => 'Trop de tentatives'], 429);
```

---

## 🏗️ ARCHITECTURE GLOBALE

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              ARCHITECTURE MVC                               │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│   [Navigateur]                                                              │
│        │                                                                    │
│        ▼                                                                    │
│   ┌─────────────────┐                                                       │
│   │ public/index.php│  ◄── Point d'entrée unique (routeur)                  │
│   └────────┬────────┘                                                       │
│            │                                                                │
│    ┌───────┴───────┐                                                        │
│    │               │                                                        │
│    ▼               ▼                                                        │
│ [API REST]    [Vues HTML]                                                   │
│    │               │                                                        │
│    ▼               ▼                                                        │
│ ┌──────────┐  ┌─────────────────────┐                                       │
│ │Contrôleur│  │views/layouts/       │                                       │
│ │ (app/)   │  │  principal.php      │ ◄── Layout HTML commun                │
│ └────┬─────┘  │    └─► views/pages/ │                                       │
│      │        │         └─► *.php   │ ◄── Contenu spécifique                │
│      ▼        └─────────────────────┘                                       │
│ ┌──────────┐                                                                │
│ │ Modèle   │ ◄── Accès base de données                                      │
│ │ (app/)   │                                                                │
│ └────┬─────┘                                                                │
│      │                                                                      │
│      ▼                                                                      │
│ ┌──────────────────┐                                                        │
│ │ BaseDeDonnees.php│ ◄── Singleton PDO                                      │
│ └──────────────────┘                                                        │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 📁 STRUCTURE DES DOSSIERS DÉTAILLÉE

```
ReVente-Auto/
│
├── 📄 .env                          # Variables d'environnement (JAMAIS sur Git)
├── 📄 .gitignore                    # Fichiers ignorés par Git
├── 📄 .htaccess                     # Redirection vers public/
├── 📄 config.php                    # Charge .env et retourne tableau de config
│
├── 📁 app/                          # ══════ BACKEND PHP ══════
│   │
│   ├── 📄 autochargement.php        # Autoloader PSR-0 (parcours récursif)
│   │
│   ├── 📁 Controleurs/              # ══════ CONTRÔLEURS MVC ══════
│   │   │
│   │   ├── 📄 ControleurBase.php    # Classe abstraite pour tous les contrôleurs de pages
│   │   │
│   │   ├── 📁 Api/                  # Contrôleurs API REST (JSON) - 16 fichiers
│   │   │   ├── 📄 ControleurAdmin.php
│   │   │   ├── 📄 ControleurAjoutVehicule.php
│   │   │   ├── 📄 ControleurConnexion.php
│   │   │   ├── 📄 ControleurContact.php
│   │   │   ├── 📄 ControleurDetailsVehicule.php
│   │   │   ├── 📄 ControleurEstimation.php
│   │   │   ├── 📄 ControleurFavoris.php
│   │   │   ├── 📄 ControleurGalerieVehicule.php
│   │   │   ├── 📄 ControleurInscription.php
│   │   │   ├── 📄 ControleurLocalisation.php
│   │   │   ├── 📄 ControleurMesAnnonces.php
│   │   │   ├── 📄 ControleurMessagerie.php
│   │   │   ├── 📄 ControleurModificationVehicule.php
│   │   │   ├── 📄 ControleurMotDePasseOublie.php
│   │   │   ├── 📄 ControleurProfil.php
│   │   │   └── 📄 ControleurScoreIA.php
│   │   │
│   │   └── 📁 Pages/                # Contrôleurs de pages (HTML) - 18 fichiers
│   │       ├── 📄 ControleurAccueil.php
│   │       ├── 📄 ControleurAdminPage.php
│   │       ├── 📄 ControleurAjoutPage.php
│   │       ├── 📄 ControleurApropos.php
│   │       ├── 📄 ControleurCgu.php
│   │       ├── 📄 ControleurConnexionPage.php
│   │       ├── 📄 ControleurContactPage.php
│   │       ├── 📄 ControleurDetailsPage.php
│   │       ├── 📄 ControleurEquipe.php
│   │       ├── 📄 ControleurErreur.php
│   │       ├── 📄 ControleurEstimationPage.php
│   │       ├── 📄 ControleurFaq.php
│   │       ├── 📄 ControleurFavorisPage.php
│   │       ├── 📄 ControleurGalerie.php
│   │       ├── 📄 ControleurMesAnnoncesPage.php
│   │       ├── 📄 ControleurMessageriePage.php
│   │       ├── 📄 ControleurModificationPage.php
│   │       └── 📄 ControleurParametres.php
│   │
│   ├── 📁 Modeles/                  # ══════ MODÈLES (accès BDD) ══════ - 9 fichiers
│   │   ├── 📄 ModeleAdmin.php
│   │   ├── 📄 ModeleContact.php
│   │   ├── 📄 ModeleEstimation.php
│   │   ├── 📄 ModeleFavoris.php
│   │   ├── 📄 ModeleLocalisation.php
│   │   ├── 📄 ModeleMessagerie.php
│   │   ├── 📄 ModeleScoreIA.php
│   │   ├── 📄 ModeleUtilisateur.php
│   │   └── 📄 ModeleVehicule.php
│   │
│   └── 📁 Services/                 # ══════ SERVICES (utilitaires) ══════ - 11 fichiers
│       ├── 📄 AideCSRF.php          # Génération balises HTML CSRF
│       ├── 📄 BaseDeDonnees.php     # Singleton connexion PDO
│       ├── 📄 GestionnaireLimiteTaux.php    # Rate limiting (BDD)
│       ├── 📄 GestionnaireSession.php       # Sessions sécurisées, timeout
│       ├── 📄 GestionnaireVues.php          # Compteur de vues véhicules
│       ├── 📄 Securite.php          # En-têtes HTTP sécurité
│       ├── 📄 ServiceChiffrement.php        # RSA+AES, hachage, tokens
│       ├── 📄 ServiceEmail.php              # Envoi emails SMTP
│       ├── 📄 ServiceValidationFichier.php  # Validation uploads
│       ├── 📄 Utilitaires.php       # envoyerJSON, lireCorpsJSON, validations
│       └── 📄 ValidateurVehicule.php        # Validation centralisée véhicules
│
├── 📁 database/
│   └── 📄 schema_complet.sql        # Schéma SQL complet (15 tables)
│
├── 📁 docs/
│   ├── 📄 CONTEXTE_IA.md            # Documentation complète pour IA (CE FICHIER)
│   └── 📄 GUIDE_TESTS_SECURITE.md   # Documentation tests sécurité
│
├── 📁 donnees/                      # Fichiers privés (hors public, hors Git)
│
├── 📁 public/                       # ══════ POINT D'ENTRÉE WEB ══════
│   │
│   ├── 📄 .htaccess                 # Config Apache (sécurité uploads)
│   ├── 📄 favicon.ico
│   ├── 📄 index.php                 # ROUTEUR CENTRAL (API + Pages)
│   │
│   ├── 📁 assets/
│   │   │
│   │   ├── 📁 css/
│   │   │   ├── 📄 style.css         # Import principal (importe tous les autres)
│   │   │   │
│   │   │   ├── 📁 base/             # 4 fichiers
│   │   │   │   ├── 📄 animations.css        # Keyframes centralisées ⚠️
│   │   │   │   ├── 📄 reinitialisation.css  # Reset CSS
│   │   │   │   ├── 📄 typographie.css       # Polices, tailles
│   │   │   │   └── 📄 variables.css         # Variables CSS (couleurs, thèmes)
│   │   │   │
│   │   │   ├── 📁 composants/       # Composants réutilisables - 10 fichiers
│   │   │   │   ├── 📄 alertes.css
│   │   │   │   ├── 📄 barre-outils.css
│   │   │   │   ├── 📄 boutons.css
│   │   │   │   ├── 📄 cartes.css
│   │   │   │   ├── 📄 formulaires.css
│   │   │   │   ├── 📄 hero.css
│   │   │   │   ├── 📄 localisation.css
│   │   │   │   ├── 📄 notification.css
│   │   │   │   ├── 📄 resultat-page.css
│   │   │   │   └── 📄 suppression.css
│   │   │   │
│   │   │   ├── 📁 mises_en_page/    # Layouts - 3 fichiers
│   │   │   │   ├── 📄 entete.css
│   │   │   │   ├── 📄 grille.css
│   │   │   │   └── 📄 pied-de-page.css
│   │   │   │
│   │   │   └── 📁 pages/            # Styles spécifiques par page - 18 fichiers
│   │   │       ├── 📄 accueil.css
│   │   │       ├── 📄 admin.css
│   │   │       ├── 📄 apropos.css
│   │   │       ├── 📄 authentification.css
│   │   │       ├── 📄 cgu.css
│   │   │       ├── 📄 contact.css
│   │   │       ├── 📄 details.css
│   │   │       ├── 📄 email-verification.css
│   │   │       ├── 📄 equipe.css
│   │   │       ├── 📄 estimation.css
│   │   │       ├── 📄 faq.css
│   │   │       ├── 📄 favoris.css
│   │   │       ├── 📄 galerie.css
│   │   │       ├── 📄 mes-annonces.css
│   │   │       ├── 📄 messagerie.css
│   │   │       ├── 📄 parametres.css
│   │   │       ├── 📄 reset-mot-de-passe.css
│   │   │       └── 📄 vehicule-form.css
│   │   │
│   │   ├── 📁 images/
│   │   │   ├── 📁 equipe/           # Photos équipe
│   │   │   └── 📁 logo/             # Logos clair/sombre
│   │   │
│   │   └── 📁 js/
│   │       ├── 📄 application.js    # MODULE PRINCIPAL (utilitaires partagés)
│   │       ├── 📄 GestionnaireInactivite.js  # Timeout session côté client
│   │       ├── 📄 navigation.js     # Menu mobile, navigation
│   │       │
│   │       └── 📁 modules/          # Modules JS organisés par domaine
│   │           │
│   │           ├── 📁 admin/        # 1 fichier
│   │           │   └── 📄 VueAdmin.js
│   │           │
│   │           ├── 📁 auth/         # 5 fichiers
│   │           │   ├── 📄 VueConnexion.js
│   │           │   ├── 📄 VueInscription.js
│   │           │   ├── 📄 VueProfil.js
│   │           │   ├── 📄 VueResetMotDePasse.js
│   │           │   └── 📄 VueVerificationEmail.js
│   │           │
│   │           ├── 📁 commun/       # 4 fichiers
│   │           │   ├── 📄 GestionnaireSuppression.js
│   │           │   ├── 📄 protection-csrf.js
│   │           │   ├── 📄 utilitaires.js
│   │           │   └── 📄 VueFaq.js
│   │           │
│   │           ├── 📁 outils/       # 5 fichiers
│   │           │   ├── 📄 GestionnaireCodePostal.js
│   │           │   ├── 📄 VueContact.js
│   │           │   ├── 📄 VueEstimation.js
│   │           │   ├── 📄 VueLocalisation.js
│   │           │   └── 📄 VueScoreIA.js
│   │           │
│   │           ├── 📁 utilisateur/  # 3 fichiers
│   │           │   ├── 📄 VueFavoris.js
│   │           │   ├── 📄 VueMesAnnonces.js
│   │           │   └── 📄 VueMessagerie.js
│   │           │
│   │           └── 📁 vehicule/     # 6 fichiers
│   │               ├── 📄 utilitaires-vehicule.js
│   │               ├── 📄 verificationEmailAjout.js
│   │               ├── 📄 VueAjoutVehicule.js
│   │               ├── 📄 VueDetails.js
│   │               ├── 📄 VueGalerie.js
│   │               └── 📄 VueModificationVehicule.js
│   │
│   └── 📁 uploads/                  # Fichiers uploadés par les utilisateurs
│       ├── 📄 .htaccess             # Protection sécurité (pas d'exécution PHP)
│       ├── 📁 avatars/              # Avatars utilisateurs
│       └── 📁 vehicules/            # Images véhicules (par ID)
│
└── 📁 views/                        # ══════ VUES PHP ══════
    │
    ├── 📁 layouts/
    │   └── 📄 principal.php         # Layout HTML commun (head, header, footer)
    │
    ├── 📁 pages/
    │   ├── 📄 accueil.php           # Page d'accueil
    │   │
    │   ├── 📁 admin/                # 1 fichier
    │   │   └── 📄 dashboard.php
    │   │
    │   ├── 📁 auth/                 # 4 fichiers
    │   │   ├── 📄 connexion.php
    │   │   ├── 📄 email_change_confirme.php
    │   │   ├── 📄 email_verifie.php
    │   │   └── 📄 reset_mot_de_passe.php
    │   │
    │   ├── 📁 erreur/               # 1 fichier
    │   │   └── 📄 404.php
    │   │
    │   ├── 📁 outils/               # 2 fichiers
    │   │   ├── 📄 contact.php
    │   │   └── 📄 estimation.php
    │   │
    │   ├── 📁 statique/             # 4 fichiers
    │   │   ├── 📄 apropos.php
    │   │   ├── 📄 cgu.php
    │   │   ├── 📄 equipe.php
    │   │   └── 📄 faq.php
    │   │
    │   ├── 📁 utilisateur/          # 4 fichiers
    │   │   ├── 📄 favoris.php
    │   │   ├── 📄 mes_annonces.php
    │   │   ├── 📄 messagerie.php
    │   │   └── 📄 parametres.php
    │   │
    │   └── 📁 vehicule/             # 4 fichiers
    │       ├── 📄 ajout.php
    │       ├── 📄 details.php
    │       ├── 📄 galerie.php
    │       └── 📄 modification.php
    │
    └── 📁 partials/                 # 2 fichiers
        ├── 📄 navigation.php        # Menu de navigation
        └── 📄 pied_de_page.php      # Pied de page
```

---

## ⚙️ FICHIERS DE CONFIGURATION

### `.env` (Variables d'environnement)
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=revente_auto
DB_USER=root
DB_PASS=motdepasse
APP_SECRET_KEY=64_caracteres_hexadecimaux
OPENAI_API_KEY=sk-proj-...

# Configuration SMTP
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=email@gmail.com
SMTP_PASS=app_password
SMTP_FROM_EMAIL=noreply@revente-auto.com
SMTP_FROM_NAME=ReVente-Auto
```

### `config.php` (Chargement configuration)
```php
<?php
// Charge automatiquement les variables depuis .env
// Retourne un tableau associatif avec toutes les configs
// Configure le fuseau horaire Europe/Paris

return [
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_port' => getenv('DB_PORT') ?: '3306',
    'db_name' => getenv('DB_NAME') ?: '',
    'db_user' => getenv('DB_USER') ?: '',
    'db_pass' => getenv('DB_PASS') ?: '',
    'app_secret_key' => getenv('APP_SECRET_KEY') ?: '',
    'openai_api_key' => getenv('OPENAI_API_KEY') ?: '',
    'smtp_host' => getenv('SMTP_HOST') ?: '',
    'smtp_port' => getenv('SMTP_PORT') ?: 587,
    'smtp_user' => getenv('SMTP_USER') ?: '',
    'smtp_pass' => getenv('SMTP_PASS') ?: '',
];
```

---

## 🔧 BACKEND PHP - DÉTAIL COMPLET

### Routeur Central (`public/index.php`)

Le fichier analyse l'URI et route soit vers une API, soit vers une vue.

**Routes API disponibles :**
| Route | Contrôleur | Description |
|-------|------------|-------------|
| `/api/connexion` | `ControleurConnexion` | login, logout, me |
| `/api/inscription` | `ControleurInscription` | register |
| `/api/profil` | `ControleurProfil` | GET/PUT profil, verify-email |
| `/api/auth/reset-password` | `ControleurMotDePasseOublie` | request, reset |
| `/api/vehicule/galerie` | `ControleurGalerieVehicule` | GET liste filtrée |
| `/api/vehicule/details` | `ControleurDetailsVehicule` | GET véhicule par ID |
| `/api/vehicule/ajout` | `ControleurAjoutVehicule` | POST création |
| `/api/vehicule/modification` | `ControleurModificationVehicule` | PUT/DELETE |
| `/api/favoris` | `ControleurFavoris` | GET/POST/DELETE |
| `/api/messagerie` | `ControleurMessagerie` | conversations, messages, offres |
| `/api/estimation` | `ControleurEstimation` | POST estimation prix |
| `/api/score-ia` | `ControleurScoreIA` | GET/POST score |
| `/api/contact` | `ControleurContact` | POST formulaire |
| `/api/localisation` | `ControleurLocalisation` | GET coordonnées, GET villes |
| `/api/mes-annonces` | `ControleurMesAnnonces` | GET/PUT statut annonces |
| `/api/admin` | `ControleurAdmin` | Gestion admin |

**Routes Pages :**
| Route | Contrôleur | Vue |
|-------|------------|-----|
| `/`, `/accueil` | `ControleurAccueil` | `accueil.php` |
| `/galerie` | `ControleurGalerie` | `vehicule/galerie.php` |
| `/vehicule` | `ControleurDetailsPage` | `vehicule/details.php` |
| `/ajout_vehicule` | `ControleurAjoutPage` | `vehicule/ajout.php` |
| `/modification_vehicule` | `ControleurModificationPage` | `vehicule/modification.php` |
| `/connexion` | `ControleurConnexionPage` | `auth/connexion.php` |
| `/parametres` | `ControleurParametres` | `utilisateur/parametres.php` |
| `/messagerie` | `ControleurMessageriePage` | `utilisateur/messagerie.php` |
| `/favoris` | `ControleurFavorisPage` | `utilisateur/favoris.php` |
| `/mes-annonces` | `ControleurMesAnnoncesPage` | `utilisateur/mes_annonces.php` |
| `/estimation` | `ControleurEstimationPage` | `outils/estimation.php` |
| `/contact` | `ControleurContactPage` | `outils/contact.php` |
| `/apropos` | `ControleurApropos` | `statique/apropos.php` |
| `/faq` | `ControleurFaq` | `statique/faq.php` |
| `/cgu` | `ControleurCgu` | `statique/cgu.php` |
| `/equipe` | `ControleurEquipe` | `statique/equipe.php` |
| `/admin` | `ControleurAdminPage` | `admin/dashboard.php` |

### Classes Utilitaires (`app/Services/`)

#### `Utilitaires.php`
```php
Utilitaires::envoyerJSON($donnees, $code);   // Envoie JSON et exit
Utilitaires::lireCorpsJSON();                 // Lit body JSON en tableau
Utilitaires::chaineValide($str, $maxLen);     // Valide chaîne non vide
Utilitaires::entierEntre($n, $min, $max);     // Valide entier dans plage
Utilitaires::nombreMinimum($n, $min);         // Valide float >= min
Utilitaires::emailValide($email);             // Valide format email
Utilitaires::telephoneValide($tel);           // Valide format téléphone
Utilitaires::motDePasseFort($mdp);            // Valide complexité MDP
Utilitaires::obtenirIpClient();               // IP réelle du client
Utilitaires::versionAsset($chemin);           // Cache busting automatique
```

#### `GestionnaireSession.php`
```php
GestionnaireSession::demarrerSession();       // Démarre session sécurisée
GestionnaireSession::detruireSession();       // Détruit session proprement
GestionnaireSession::estConnecte();           // Retourne bool
GestionnaireSession::obtenirUtilisateur();    // Retourne $_SESSION['user']
GestionnaireSession::genererTokenCSRF();      // Génère/retourne token
GestionnaireSession::validerTokenCSRF($t);    // Valide token (bool)
GestionnaireSession::verifierLimiteAnnonces($userId);  // Rate limit annonces
GestionnaireSession::incrementerCompteurAnnonces($userId);
GestionnaireSession::detruireToutesSessions($userId);  // Déconnexion globale
GestionnaireSession::validerTokenSession();   // Valide token en BDD
```

**Constantes :**
- `DUREE_INACTIVITE` : 1200 secondes (20 min)
- `DUREE_AVANT_AVERTISSEMENT` : 1020 secondes (17 min)
- `DUREE_AVERTISSEMENT` : 180 secondes (3 min)
- `LIMITE_ANNONCES_PAR_JOUR` : 30

#### `ServiceChiffrement.php`
```php
ServiceChiffrement::hacherMotDePasse($mdp);              // BCRYPT
ServiceChiffrement::verifierMotDePasse($mdp, $hash);     // Vérifie BCRYPT
ServiceChiffrement::genererToken($longueur);             // Token aléatoire
ServiceChiffrement::genererPaireCles();                  // Clés RSA pub/priv
ServiceChiffrement::chiffrerDonnee($data);               // AES-256-CBC
ServiceChiffrement::dechiffrerDonnee($data);             // Déchiffre AES
ServiceChiffrement::chiffrerMessagePourDeux($msg, $pubDest, $pubExp);  // E2E
ServiceChiffrement::dechiffrerMessage($msg, $iv, $key, $privKey);      // E2E
```

#### `GestionnaireLimiteTaux.php` (Version BDD)
```php
GestionnaireLimiteTaux::verifierTentative($action, $identifiant);
GestionnaireLimiteTaux::ajouterTentative($action, $identifiant, $nombre);
GestionnaireLimiteTaux::reinitialiser($action, $identifiant);
```

**Configuration Rate Limiting :**
| Action | Limite | Durée |
|--------|--------|-------|
| `login` | 5 tentatives | 15 min |
| `upload` | 50 uploads | 1 heure |
| `password_reset` | 1 demande | 30 sec |
| `email_verification` | 1 demande | 30 sec |
| `email_change` | 1 demande | 60 sec |
| `vehicle_creation` | 10 annonces | 1 heure |

#### `ServiceEmail.php`
```php
ServiceEmail::envoyer($destinataire, $sujet, $corpsHTML, $corpsTexte);
ServiceEmail::envoyerVerificationEmail($email, $prenom, $token);
ServiceEmail::envoyerResetMotDePasse($email, $prenom, $token);
ServiceEmail::envoyerConfirmationChangementEmail($email, $prenom, $token);
```

#### `Securite.php`
```php
Securite::ajouterEnTetes();   // Ajoute tous les headers sécurité
Securite::echapper($str);      // htmlspecialchars() pour XSS
```

---

## 🎨 FRONTEND - CSS ET JAVASCRIPT

### Architecture CSS

**Point d'entrée :** `public/assets/css/style.css`

```css
/* style.css importe tous les autres fichiers */
@import 'base/variables.css';
@import 'base/reinitialisation.css';
@import 'base/typographie.css';
@import 'base/animations.css';
/* ... etc */
```

**⚠️ IMPORTANT - Animations CSS centralisées (`base/animations.css`) :**

Toutes les animations `@keyframes` sont centralisées dans ce fichier.
**NE JAMAIS créer de @keyframes dans un autre fichier CSS.**

Animations disponibles : `spin`, `pulse`, `pulseSubtle`, `fadeIn`, `fadeInUp`, `scaleIn`, `float`, `shake`, `slideInRight`, `slideInUp`, `slideOutRight`, `bounce`, `pulseRing`, `heroFloat`

### Architecture JavaScript

**Module principal :** `public/assets/js/application.js`

```javascript
// Exports disponibles :
export const selecteur = (sel, el) => el.querySelector(sel);
export const selecteurTous = (sel, el) => el.querySelectorAll(sel);
export const formaterMonnaie = (n) => new Intl.NumberFormat('fr-FR', {...});
export const debouncer = (fn, delai) => { ... };
export function definirChargement(el, bool) { ... }
export function afficherMessage(conteneur, texte, type) { ... }
export function afficherNotificationGlobale(msg, type, duree) { ... }
export function afficherModaleAvertissement(titre, msg, lienTexte, lienUrl) { ... }
export function afficherModaleConfirmation(titre, msg, onConfirm, texteBouton, couleur) { ... }
export function echapperHTML(str) { ... }
export function obtenirUrlApi(chemin) { ... }
```

### Cache Busting Automatique

Utiliser `Utilitaires::versionAsset()` pour tous les CSS/JS :

```php
<link rel="stylesheet" href="assets/css/style.css?v=<?= Utilitaires::versionAsset('assets/css/style.css') ?>">
<script src="assets/js/navigation.js?v=<?= Utilitaires::versionAsset('assets/js/navigation.js') ?>"></script>
```

### Protection CSRF côté client

Le fichier `protection-csrf.js` intercepte automatiquement TOUTES les requêtes `fetch()` et ajoute le token CSRF via le header `X-CSRF-Token`.

---

## 🗄️ BASE DE DONNÉES

### Tables (15 au total)

| Table | Description |
|-------|-------------|
| `users` | Utilisateurs (id, first_name, last_name, email, password_hash, public_key, private_key, session_token, role, banned_at...) |
| `vehicles` | Véhicules (id, type_vehicule, marque, modele, annee, prix, km, ville, code_postal, latitude, longitude, score_ia, status...) |
| `vehicle_images` | Images véhicules (id, vehicle_id, image_path) |
| `conversations` | Conversations messagerie |
| `messages` | Messages chiffrés E2E |
| `favorites` | Favoris (user_id, vehicle_id) |
| `offers` | Propositions de prix |
| `password_resets` | Tokens reset MDP (expire 1h) |
| `email_verifications` | Tokens verif email (expire 24h) |
| `changements_email` | Demandes changement email |
| `admin_logs` | Historique des actions admin |
| `contacts` | Messages formulaire contact |
| `rate_limits` | Limitation de tentatives |

### Types / Enums

- **type_vehicule** : `'voiture', 'moto', 'camion'`
- **etat** : `'neuf', 'bon', 'moyen', 'mauvais'`
- **status (vehicles)** : `'public', 'prive', 'en_attente', 'refuse'`
- **status (offers)** : `'pending', 'accepted', 'declined', 'expired', 'cancelled', 'paid'`
- **role (users)** : `'user', 'admin'`

---

## 🔒 SÉCURITÉ IMPLÉMENTÉE

| Mesure | Implémentation |
|--------|----------------|
| **CSRF** | Token session + header X-CSRF-Token (protection-csrf.js) |
| **XSS** | `htmlspecialchars()` + CSP headers |
| **SQL Injection** | PDO requêtes préparées uniquement |
| **Session Fixation** | `session_regenerate_id()` à l'initialisation |
| **Session Hijacking** | Validation IP + User-Agent |
| **Session Token** | Token unique en BDD pour déconnexion globale |
| **Brute Force** | Rate limiting en BDD par IP+action |
| **Timeout Session** | 20 min inactivité (JS + PHP) |
| **Mots de passe** | BCRYPT (cost 12) |
| **Messagerie** | Chiffrement RSA+AES E2E |
| **Headers HTTP** | HSTS, X-Frame-Options, CSP, etc. |
| **Uploads** | Validation MIME, extension, taille + .htaccess |

---

## 🤖 INTÉGRATION IA (OPENAI)

### Estimation de Prix (`ModeleEstimation.php`)

Appelle GPT-4o-mini pour estimer la valeur d'un véhicule.

### Score IA (`ModeleScoreIA.php`)

Calcule un score 0-100 basé sur le ratio prix demandé / valeur estimée.

**Barème :**
- 80-100 : Excellente affaire
- 60-79 : Bonne affaire
- 45-59 : Prix correct
- 30-44 : Légèrement cher
- 0-29 : Cher/Très cher

---

## 📝 GUIDE DE MODIFICATION

### Ajouter une Nouvelle Page

1. **Créer le contrôleur** : `app/Controleurs/Pages/ControleurMaPage.php`
2. **Créer la vue** : `views/pages/categorie/ma_page.php`
3. **Ajouter la route** dans `public/index.php` dans `$routesPages`
4. **Créer le CSS** (si besoin) et l'ajouter dans `style.css`
5. **Créer le JS** (si besoin) : `public/assets/js/modules/categorie/VueMaPage.js`

### Ajouter une Nouvelle API

1. **Créer le modèle** : `app/Modeles/ModeleMonModule.php`
2. **Créer le contrôleur** : `app/Controleurs/Api/ControleurMonModule.php`
3. **Ajouter la route API** dans `public/index.php` dans `$routesApi`

---

## ✅ CHECKLIST ANTI-DUPLICATION

### Classes Existantes - NE PAS RECRÉER
- `Utilitaires` → fonctions JSON/validation
- `GestionnaireSession` → sessions/CSRF
- `ServiceChiffrement` → crypto
- `ServiceEmail` → envoi emails
- `Securite` → headers/échappement
- `ValidateurVehicule` → validation véhicules
- `GestionnaireLimiteTaux` → rate limiting

### Fonctions Existantes
| Besoin | Utiliser |
|--------|----------|
| Envoyer JSON | `Utilitaires::envoyerJSON()` |
| Lire body JSON | `Utilitaires::lireCorpsJSON()` |
| Valider email | `Utilitaires::emailValide()` |
| Vérifier connexion | `GestionnaireSession::estConnecte()` |
| Token CSRF | `GestionnaireSession::genererTokenCSRF()` |
| Hacher MDP | `ServiceChiffrement::hacherMotDePasse()` |
| Cache bust | `Utilitaires::versionAsset()` |

### Fichiers CSS Existants
- Variables → `variables.css`
- Animations → `base/animations.css` ⚠️ **TOUTES les @keyframes ici**
- Boutons → `boutons.css`
- Formulaires → `formulaires.css`
- Cartes → `cartes.css`
- Hero → `hero.css`

---

## 🚨 POINTS D'ATTENTION

1. **Ne jamais** créer de nouvelle connexion PDO → utiliser `BaseDeDonnees::obtenirConnexion()`
2. **Toujours** utiliser les requêtes préparées PDO
3. **Toujours** valider le token CSRF pour les actions POST/PUT/DELETE
4. **Toujours** vérifier `GestionnaireSession::estConnecte()` pour les actions authentifiées
5. **Noms en français** sauf colonnes BDD et clés JSON API
6. **Jamais de `echo`** direct dans les contrôleurs API → utiliser `Utilitaires::envoyerJSON()`
7. **Ne jamais** créer de `@keyframes` en dehors de `base/animations.css`
8. **Toujours** utiliser `Utilitaires::versionAsset()` pour le cache busting

---

*Dernière mise à jour : Janvier 2025*
*Version du projet : 2.1*
