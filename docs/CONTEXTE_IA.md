# 🤖 CONTEXTE IA - ReVente-Auto

> **Ce fichier est destiné aux assistants IA** (Claude, ChatGPT, Gemini, etc.)
> Il contient TOUTES les informations nécessaires pour comprendre, modifier et étendre ce projet sans créer de duplications ou d'incohérences.

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
11. [Flux de Données](#-flux-de-données)
12. [Guide de Modification](#-guide-de-modification)
13. [Checklist Anti-Duplication](#-checklist-anti-duplication)

---

## 🚗 PRÉSENTATION DU PROJET

**ReVente-Auto** est une plateforme de vente de véhicules d'occasion développée en PHP vanilla (sans framework).

### Fonctionnalités Principales
- 📋 **Galerie de véhicules** avec filtres avancés (type, marque, prix, km, carburant, etc.)
- ➕ **Ajout/Modification d'annonces** avec upload multi-images
- 🔐 **Authentification complète** (inscription, connexion, réinitialisation mot de passe)
- 💬 **Messagerie chiffrée E2E** entre acheteurs et vendeurs
- ❤️ **Système de favoris**
- 🤖 **Estimation IA** du prix via OpenAI
- 📊 **Score IA** (0-100) pour évaluer si une annonce est une bonne affaire
- 💰 **Propositions de prix** avec statuts (pending, accepted, declined, expired)
- 🗺️ **Localisation** des véhicules

### Stack Technique
- **Backend** : PHP 8+ (vanilla, sans framework)
- **Frontend** : HTML5, CSS3 (variables CSS), JavaScript ES6 (modules)
- **Base de données** : MySQL/MariaDB
- **API externe** : OpenAI GPT-4o-mini

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
| **Noms de fichiers CSS** | Français, snake_case | `barre_outils.css`, `pied_de_page.css` |
| **Commentaires** | Français | `// Vérifier si l'utilisateur est connecté` |
| **Messages d'erreur** | Français | `'Authentification requise'`, `'Token CSRF invalide'` |
| **Clés JSON API** | Anglais (standard) | `'error'`, `'ok'`, `'user'`, `'vehicle'` |
| **Colonnes BDD** | Anglais (standard) | `user_id`, `created_at`, `image_path` |

### Structure des Commentaires de Fichiers

```php
<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * NOM DU FICHIER - DESCRIPTION COURTE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Description détaillée du fichier et de son rôle.
 * 
 * @author  Équipe ReVente-Auto
 * @version X.X
 * ═══════════════════════════════════════════════════════════════════════════
 */
```

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
│                              ARCHITECTURE MVC                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│   [Navigateur]                                                               │
│        │                                                                     │
│        ▼                                                                     │
│   ┌─────────────────┐                                                        │
│   │ public/index.php│  ◄── Point d'entrée unique (routeur)                  │
│   └────────┬────────┘                                                        │
│            │                                                                 │
│    ┌───────┴───────┐                                                         │
│    │               │                                                         │
│    ▼               ▼                                                         │
│ [API REST]    [Vues HTML]                                                    │
│    │               │                                                         │
│    ▼               ▼                                                         │
│ ┌──────────┐  ┌─────────────────────┐                                        │
│ │Contrôleur│  │views/layouts/       │                                        │
│ │ (app/)   │  │  principal.php      │ ◄── Layout HTML commun                │
│ └────┬─────┘  │    └─► views/pages/ │                                        │
│      │        │         └─► *.php   │ ◄── Contenu spécifique                │
│      ▼        └─────────────────────┘                                        │
│ ┌──────────┐                                                                 │
│ │ Modèle   │ ◄── Accès base de données                                      │
│ │ (app/)   │                                                                 │
│ └────┬─────┘                                                                 │
│      │                                                                       │
│      ▼                                                                       │
│ ┌──────────────────┐                                                         │
│ │ BaseDeDonnees.php│ ◄── Singleton PDO                                      │
│ └──────────────────┘                                                         │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 📁 STRUCTURE DES DOSSIERS DÉTAILLÉE

```
ReVente-Auto/
│
├── 📄 .env                          # Variables d'environnement (JAMAIS sur Git)
├── 📄 .gitignore                    # Fichiers ignorés par Git
├── 📄 config.php                    # Charge .env et retourne tableau de config
├── 📄 CONTEXTE_IA.md                # CE FICHIER (documentation IA)
│
├── 📁 app/                          # ══════ BACKEND PHP ══════
│   │
│   ├── 📄 autochargement.php        # Autoloader PSR-0 (parcours récursif)
│   │
│   ├── 📁 Authentification/         # Module Authentification
│   │   ├── 📄 ModeleUtilisateur.php # CRUD utilisateurs, tokens, clés RSA
│   │   ├── 📁 Connexion/
│   │   │   └── 📄 ControleurConnexion.php    # POST login, logout, GET me
│   │   ├── 📁 Inscription/
│   │   │   └── 📄 ControleurInscription.php  # POST register
│   │   ├── 📁 MotDePasseOublie/
│   │   │   └── 📄 ControleurMotDePasseOublie.php # Reset password
│   │   └── 📁 Profil/
│   │       └── 📄 ControleurProfil.php       # GET/PUT profil, avatar
│   │
│   ├── 📁 Commun/                   # Classes utilitaires partagées
│   │   ├── 📄 AideCSRF.php          # Génération balises HTML CSRF
│   │   ├── 📄 BaseDeDonnees.php     # Singleton connexion PDO
│   │   ├── 📄 GestionnaireLimiteTaux.php    # Rate limiting (fichiers JSON)
│   │   ├── 📄 GestionnaireSession.php       # Sessions sécurisées, timeout
│   │   ├── 📄 Securite.php          # En-têtes HTTP sécurité
│   │   ├── 📄 ServiceChiffrement.php        # RSA+AES, hachage, tokens
│   │   ├── 📄 ServiceValidationFichier.php  # Validation uploads
│   │   ├── 📄 Utilitaires.php       # envoyerJSON, lireCorpsJSON, validations
│   │   └── 📄 ValidateurVehicule.php        # Validation centralisée véhicules
│   │
│   ├── 📁 Contact/
│   │   ├── 📄 ControleurContact.php # Formulaire contact
│   │   └── 📄 ModeleContact.php
│   │
│   ├── 📁 Estimation/
│   │   ├── 📄 ControleurEstimation.php      # API estimation prix
│   │   └── 📄 ModeleEstimation.php          # Appel OpenAI
│   │
│   ├── 📁 Favoris/
│   │   ├── 📄 ControleurFavoris.php         # CRUD favoris
│   │   └── 📄 ModeleFavoris.php
│   │
│   ├── 📁 Localisation/
│   │   ├── 📄 ControleurLocalisation.php    # Géolocalisation
│   │   └── 📄 ModeleLocalisation.php
│   │
│   ├── 📁 Messagerie/
│   │   ├── 📄 ControleurMessagerie.php      # Conversations, messages, offres
│   │   └── 📄 ModeleMessagerie.php          # CRUD chiffré E2E
│   │
│   ├── 📁 ScoreIA/
│   │   ├── 📄 ControleurScoreIA.php         # API score
│   │   └── 📄 ModeleScoreIA.php             # Calcul score 0-100 via OpenAI
│   │
│   └── 📁 Vehicule/
│       ├── 📄 ModeleVehicule.php            # CRUD véhicules, filtres, images
│       ├── 📁 Ajout/
│       │   └── 📄 ControleurAjout.php       # POST création véhicule
│       ├── 📁 Details/
│       │   └── 📄 ControleurDetails.php     # GET détail véhicule
│       ├── 📁 Galerie/
│       │   └── 📄 ControleurGalerie.php     # GET liste avec filtres
│       └── 📁 Modification/
│           └── 📄 ControleurModification.php # PUT/DELETE véhicule
│
├── 📁 database/
│   └── 📄 schema_complet.sql        # Schéma SQL complet (tables + index)
│
├── 📁 docs/
│   └── 📄 GUIDE_TESTS_SECURITE.md   # Documentation tests sécurité
│
├── 📁 public/                       # ══════ POINT D'ENTRÉE WEB ══════
│   │
│   ├── 📄 index.php                 # ROUTEUR CENTRAL (API + Vues)
│   │
│   └── 📁 assets/
│       │
│       ├── 📁 css/
│       │   ├── 📄 style.css         # Import principal (importe tous les autres)
│       │   ├── 📁 base/
│       │   │   ├── 📄 reinitialisation.css  # Reset CSS
│       │   │   ├── 📄 typographie.css       # Polices, tailles
│       │   │   └── 📄 variables.css         # Variables CSS (couleurs, thèmes)
│       │   ├── 📁 components/
│       │   │   ├── 📄 alertes.css
│       │   │   ├── 📄 barre_outils.css
│       │   │   ├── 📄 boutons.css
│       │   │   ├── 📄 cartes.css
│       │   │   ├── 📄 formulaires.css
│       │   │   ├── 📄 hero.css
│       │   │   └── 📄 localisation.css
│       │   ├── 📁 layouts/
│       │   │   ├── 📄 entete.css
│       │   │   ├── 📄 grille.css
│       │   │   └── 📄 pied_de_page.css
│       │   └── 📁 pages/
│       │       ├── 📄 accueil.css
│       │       ├── 📄 authentification.css
│       │       ├── 📄 details.css
│       │       ├── 📄 estimation.css
│       │       ├── 📄 favoris.css
│       │       ├── 📄 galerie.css
│       │       ├── 📄 messagerie.css
│       │       ├── 📄 parametres.css
│       │       └── 📄 vehicule-form.css
│       │
│       ├── 📁 images/
│       │   ├── 📁 equipe/           # Photos équipe
│       │   └── 📁 logo/             # Logos clair/sombre
│       │
│       └── 📁 js/
│           ├── 📄 application.js    # MODULE PRINCIPAL (utilitaires partagés)
│           ├── 📄 navigation.js     # Menu mobile, navigation
│           ├── 📄 GestionnaireInactivite.js  # Timeout session côté client
│           │
│           ├── 📁 Authentification/
│           │   ├── 📁 Connexion/
│           │   │   └── 📄 VueConnexion.js
│           │   ├── 📁 Inscription/
│           │   │   └── 📄 VueInscription.js
│           │   ├── 📁 MotDePasseOublie/
│           │   │   └── 📄 VueMotDePasseOublie.js
│           │   └── 📁 Profil/
│           │       └── 📄 VueProfil.js
│           │
│           ├── 📁 Commun/
│           │   ├── 📄 protection-csrf.js    # Intercept fetch/XHR pour CSRF
│           │   └── 📄 utilitaires.js
│           │
│           ├── 📁 Contact/
│           │   └── 📄 VueContact.js
│           │
│           ├── 📁 Estimation/
│           │   └── 📄 VueEstimation.js
│           │
│           ├── 📁 Favoris/
│           │   └── 📄 VueFavoris.js
│           │
│           ├── 📁 Localisation/
│           │   └── 📄 VueLocalisation.js
│           │
│           ├── 📁 Messagerie/
│           │   └── 📄 VueMessagerie.js
│           │
│           ├── 📁 pages/
│           │   └── 📄 VueFaq.js
│           │
│           ├── 📁 ScoreIA/
│           │   └── 📄 VueScoreIA.js
│           │
│           └── 📁 Vehicule/
│               ├── 📄 utilitaires-vehicule.js
│               ├── 📁 Ajout/
│               │   └── 📄 VueAjoutVehicule.js
│               ├── 📁 Details/
│               │   └── 📄 VueDetails.js
│               ├── 📁 Galerie/
│               │   └── 📄 VueGalerie.js
│               └── 📁 Modification/
│                   └── 📄 VueModificationVehicule.js
│
├── 📁 stockage/                     # Fichiers générés (hors Git)
│   └── 📁 rate_limit/               # Fichiers JSON rate limiting par IP
│
└── 📁 views/                        # ══════ VUES PHP ══════
    │
    ├── 📁 layouts/
    │   └── 📄 principal.php         # Layout HTML commun (head, header, footer)
    │
    ├── 📁 pages/
    │   ├── 📄 404.php
    │   ├── 📄 accueil.php
    │   ├── 📄 ajout_vehicule.php
    │   ├── 📄 apropos.php
    │   ├── 📄 cgu.php
    │   ├── 📄 connexion.php
    │   ├── 📄 contact.php
    │   ├── 📄 details.php
    │   ├── 📄 equipe.php
    │   ├── 📄 estimation.php
    │   ├── 📄 faq.php
    │   ├── 📄 favoris.php
    │   ├── 📄 galerie.php
    │   ├── 📄 messagerie.php
    │   ├── 📄 modification_vehicule.php
    │   └── 📄 parametres.php
    │
    └── 📁 partials/
        ├── 📄 navigation.php        # Menu de navigation
        └── 📄 pied_de_page.php      # Footer
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
```

### `config.php` (Chargement configuration)
```php
<?php
// Charge automatiquement les variables depuis .env
// Retourne un tableau associatif avec toutes les configs

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    // Parse et charge les variables
}

return [
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_port' => getenv('DB_PORT') ?: '3306',
    'db_name' => getenv('DB_NAME') ?: '',
    'db_user' => getenv('DB_USER') ?: '',
    'db_pass' => getenv('DB_PASS') ?: '',
    'app_secret_key' => getenv('APP_SECRET_KEY') ?: '',
    'openai_api_key' => getenv('OPENAI_API_KEY') ?: ''
];
```

---

## 🔧 BACKEND PHP - DÉTAIL COMPLET

### Routeur Central (`public/index.php`)

Le fichier analyse l'URI et route soit vers une API, soit vers une vue.

**Routes API disponibles :**
| Route | Contrôleur | Actions |
|-------|------------|---------|
| `/api/connexion` | `ControleurConnexion` | login, logout, me |
| `/api/inscription` | `ControleurInscription` | register |
| `/api/profil` | `ControleurProfil` | GET/PUT profil |
| `/api/auth/reset-password` | `ControleurMotDePasseOublie` | request, reset |
| `/api/vehicule/galerie` | `ControleurGalerie` | GET liste filtrée |
| `/api/vehicule/details` | `ControleurDetails` | GET véhicule par ID |
| `/api/vehicule/ajout` | `ControleurAjout` | POST création |
| `/api/vehicule/modification` | `ControleurModification` | PUT/DELETE |
| `/api/favoris` | `ControleurFavoris` | GET/POST/DELETE |
| `/api/messagerie` | `ControleurMessagerie` | conversations, messages, offres |
| `/api/estimation` | `ControleurEstimation` | POST estimation prix |
| `/api/score-ia` | `ControleurScoreIA` | GET/POST score |
| `/api/contact` | `ControleurContact` | POST formulaire |
| `/api/localisation` | `ControleurLocalisation` | recherche villes |

**Routes Vues :**
| Route | Vue | Description |
|-------|-----|-------------|
| `/`, `/accueil` | `accueil.php` | Page d'accueil |
| `/galerie` | `galerie.php` | Liste véhicules |
| `/details` | `details.php` | Détail véhicule |
| `/ajout_vehicule` | `ajout_vehicule.php` | Formulaire ajout |
| `/modification_vehicule` | `modification_vehicule.php` | Formulaire modif |
| `/connexion` | `connexion.php` | Connexion/Inscription |
| `/parametres` | `parametres.php` | Profil utilisateur |
| `/messagerie` | `messagerie.php` | Conversations |
| `/favoris` | `favoris.php` | Liste favoris |
| `/estimation` | `estimation.php` | Outil estimation |
| `/contact` | `contact.php` | Formulaire contact |
| `/apropos` | `apropos.php` | À propos |
| `/faq` | `faq.php` | FAQ |

### Classes Utilitaires (`app/Commun/`)

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
```

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

#### `GestionnaireLimiteTaux.php`
```php
GestionnaireLimiteTaux::verifierTentative($action);  // 'login', 'upload'
GestionnaireLimiteTaux::ajouterTentative($action);   // Incrémente compteur
GestionnaireLimiteTaux::reinitialiser($action);      // Reset après succès
```

**Configuration Rate Limiting :**
- `login` : 5 tentatives / 15 minutes
- `upload` : 50 uploads / 1 heure

#### `Securite.php`
```php
Securite::ajouterEnTetes();   // Ajoute tous les headers sécurité
Securite::echapper($str);      // htmlspecialchars() pour XSS
```

**En-têtes ajoutés :**
- `Strict-Transport-Security` (HSTS)
- `X-XSS-Protection`
- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy`
- `Content-Security-Policy`
- `Permissions-Policy`

### Modèles (Couche Données)

#### `ModeleVehicule.php`
```php
$modele = new ModeleVehicule();
$modele->obtenirTous($filtres);      // Liste avec filtres
$modele->obtenirParId($id);          // Détail + images
$modele->ajouter($donnees, $files, $userId);  // Création
$modele->modifier($id, $donnees, $files);     // Modification
$modele->supprimer($id);             // Suppression
$modele->obtenirParUtilisateur($userId);      // Véhicules d'un user
```

**Filtres supportés :**
`type`, `marque`, `prix_min`, `prix_max`, `annee_min`, `annee_max`, `carburant[]`, `boite[]`, `etat[]`, `crit_air[]`, `nb_portes[]`, `controle_technique[]`, `recherche`

#### `ModeleUtilisateur.php`
```php
$modele = new ModeleUtilisateur();
$modele->trouverParEmail($email);
$modele->trouverParId($id);
$modele->creer($prenom, $nom, $email, $tel, $mdp, $avatar);
$modele->mettreAJourProfil($id, $prenom, $nom, $tel, $avatar);
$modele->mettreAJourMotDePasse($userId, $nouveauMdp, $resetId);
$modele->supprimerCompte($id);
$modele->creerTokenReset($userId, $token);
$modele->verifierTokenReset($token);
$modele->creerTokenVerificationEmail($userId, $token);
$modele->verifierTokenEmail($token);
$modele->validerEmail($userId, $verificationId);
```

#### `ModeleMessagerie.php`
```php
$modele = new ModeleMessagerie();
$modele->obtenirConversations($userId);
$modele->verifierAppartenanceConversation($convId, $userId);
$modele->obtenirMessages($convId);
$modele->marquerMessagesCommeLus($convId, $userId);
$modele->enregistrerMessage($convId, $senderId, $donneesChiffrees);
$modele->creerConversation($vehicleId, $buyerId, $sellerId);
$modele->trouverConversation($vehicleId, $buyerId, $sellerId);
$modele->compterMessagesNonLus($userId);
// Offres
$modele->creerProposition($convId, $senderId, $montant);
$modele->obtenirPropositionActive($convId);
$modele->accepterProposition($offerId, $userId);
$modele->refuserProposition($offerId, $userId);
```

---

## 🎨 FRONTEND - CSS ET JAVASCRIPT

### Architecture CSS

**Point d'entrée :** `public/assets/css/style.css`

```css
/* style.css importe tous les autres fichiers */
@import 'base/reinitialisation.css';
@import 'base/variables.css';
@import 'base/typographie.css';
/* ... etc */
```

**Variables CSS (`variables.css`) :**
```css
:root {
  --couleur-primaire: #2563eb;
  --couleur-secondaire: #1e40af;
  --couleur-fond: #ffffff;
  --couleur-texte: #1f2937;
  /* ... */
}

[data-theme="dark"] {
  --couleur-fond: #111827;
  --couleur-texte: #f9fafb;
  /* ... */
}
```

### Architecture JavaScript

**Module principal :** `public/assets/js/application.js`

```javascript
// Exports disponibles :
export const selecteur = (sel, el) => el.querySelector(sel);
export const selecteurTous = (sel, el) => el.querySelectorAll(sel);
export const formaterMonnaie = (n) => new Intl.NumberFormat('fr-FR', {...});
export const debouncer = (fn, delai) => { ... };
export function definirChargement(el, bool) { ... }
export function afficherMessage(conteneur, texte, estErreur) { ... }
export function echapperHTML(str) { ... }
export function obtenirUrlApi(chemin) { ... }  // Construit URL API
```

**Pattern des Vues JavaScript :**

Chaque page a une classe `Vue*` qui gère la logique front :

```javascript
// Exemple : VueGalerie.js
import { obtenirUrlApi } from '../../application.js';

export default class VueGalerie {
    constructor() {
        this.urlApi = obtenirUrlApi('/vehicule/galerie');
        this.etat = { vehicules: [], filtres: [], ... };
    }
    
    async initialiser() {
        await this.chargerVehicules();
        this.attacherEvenements();
    }
    
    attacherEvenements() { ... }
    async chargerVehicules() { ... }
    afficherListe() { ... }
}
```

**Intégration dans les vues PHP :**
```php
<script type="module">
    import VueGalerie from './assets/js/Vehicule/Galerie/VueGalerie.js';
    document.addEventListener('DOMContentLoaded', () => {
        const galerie = new VueGalerie();
        galerie.initialiser();
    });
</script>
```

### Protection CSRF côté client

Le fichier `protection-csrf.js` intercepte automatiquement TOUTES les requêtes `fetch()` et `XMLHttpRequest` pour ajouter le token CSRF :

```javascript
// Intercepte fetch()
window.fetch = function(url, options = {}) {
    if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
        options.headers['X-CSRF-Token'] = obtenirJetonCSRF();
    }
    return fetchOriginal.call(this, url, options);
};
```

---

## 🗄️ BASE DE DONNÉES

### Tables Principales

| Table | Description | Colonnes Clés |
|-------|-------------|---------------|
| `users` | Utilisateurs | id, first_name, last_name, email, password_hash, public_key, private_key |
| `vehicles` | Véhicules | id, type_vehicule, marque, modele, annee, prix, km, user_id, score_ia |
| `vehicle_images` | Images véhicules | id, vehicle_id, image_path |
| `conversations` | Conversations | id, vehicle_id, buyer_id, seller_id |
| `messages` | Messages chiffrés | id, conversation_id, sender_id, content, iv, encrypted_key |
| `favorites` | Favoris | user_id, vehicle_id |
| `offers` | Propositions prix | id, conversation_id, sender_id, amount, status, expires_at |
| `password_resets` | Tokens reset MDP | id, user_id, token, expires_at, used_at |
| `email_verifications` | Tokens verif email | id, user_id, token, expires_at, used_at |

### Types de Véhicules
```sql
ENUM('voiture', 'moto', 'camion')
```

### États Véhicules
```sql
ENUM('neuf', 'bon', 'moyen', 'mauvais')
```

### Statuts Offres
```sql
ENUM('pending', 'accepted', 'declined', 'expired', 'cancelled', 'paid')
```

---

## 🔒 SÉCURITÉ IMPLÉMENTÉE

| Mesure | Fichier | Méthode |
|--------|---------|---------|
| **CSRF** | `AideCSRF.php`, `protection-csrf.js` | Token en session + header |
| **XSS** | `Securite.php` | `htmlspecialchars()` + CSP |
| **SQL Injection** | Tous les modèles | PDO requêtes préparées |
| **Session Fixation** | `GestionnaireSession.php` | `session_regenerate_id()` |
| **Session Hijacking** | `GestionnaireSession.php` | Validation IP + User-Agent |
| **Brute Force** | `GestionnaireLimiteTaux.php` | Rate limiting par IP |
| **Timeout Session** | `GestionnaireSession.php` | 20 min inactivité |
| **Mots de passe** | `ServiceChiffrement.php` | BCRYPT |
| **Messagerie** | `ServiceChiffrement.php` | Chiffrement RSA+AES E2E |
| **Headers HTTP** | `Securite.php` | HSTS, X-Frame, CSP, etc. |

---

## 🤖 INTÉGRATION IA (OPENAI)

### Estimation de Prix (`ModeleEstimation.php`)

Appelle GPT-4o-mini pour estimer la valeur d'un véhicule.

**Entrée :**
```php
$donnees = [
    'marque' => 'Peugeot',
    'modele' => '308',
    'annee' => 2020,
    'kilometrage' => 45000,
    'carburant' => 'Essence',
    'boite' => 'Automatique',
    'etat' => 'bon'
];
```

**Sortie :**
```php
[
    'prix' => 15000,
    'tendance' => 'stable',  // 'hausse', 'stable', 'baisse'
    'tempsVente' => '2-3 semaines'
]
```

### Score IA (`ModeleScoreIA.php`)

Calcule un score 0-100 pour évaluer si l'annonce est une bonne affaire.

**Logique :**
1. Estime la valeur marché via OpenAI
2. Compare avec le prix demandé
3. Calcule un ratio et détermine le score

**Barème :**
- 80-100 : Excellente affaire (prix < 60% valeur)
- 60-79 : Bonne affaire
- 45-59 : Prix correct
- 30-44 : Légèrement cher
- 15-29 : Cher
- 0-14 : Très cher

---

## 🔄 FLUX DE DONNÉES

### Ajout d'un Véhicule
```
1. [VueAjoutVehicule.js] Formulaire soumis
2. fetch('/api/vehicule/ajout', { method: 'POST', body: FormData })
   └── Header X-CSRF-Token ajouté automatiquement
3. [ControleurAjout.php]
   ├── Vérifie authentification
   ├── Valide token CSRF
   ├── Vérifie rate limit
   ├── Valide données via ValidateurVehicule
   └── Appelle ModeleVehicule::ajouter()
4. [ModeleVehicule.php]
   ├── Upload images sécurisé
   ├── INSERT véhicule en BDD
   └── INSERT images dans vehicle_images
5. [ModeleScoreIA.php] Calcul score IA (async)
6. Réponse JSON { ok: true, vehicle: {...} }
```

### Connexion Utilisateur
```
1. [VueConnexion.js] Formulaire soumis
2. fetch('/api/connexion?action=login', { method: 'POST', body: JSON })
3. [ControleurConnexion.php]
   ├── Vérifie rate limit
   ├── Valide identifiants
   ├── Vérifie mot de passe (BCRYPT)
   └── Crée session utilisateur
4. Réponse JSON { ok: true, user: {...} }
```

### Envoi Message Chiffré
```
1. [VueMessagerie.js] Message saisi
2. Récupère clé publique destinataire
3. Chiffre message côté client (ou serveur)
4. fetch('/api/messagerie?action=send', { method: 'POST' })
5. [ControleurMessagerie.php]
   ├── Vérifie appartenance conversation
   ├── Chiffre pour les 2 participants
   └── Sauvegarde message chiffré
6. Réponse JSON { ok: true }
```

---

## 📝 GUIDE DE MODIFICATION

### Ajouter une Nouvelle Page

1. **Créer la vue** : `views/pages/ma_page.php`
2. **Ajouter la route** dans `public/index.php` :
```php
$tableRoutage['/ma_page'] = [
    'view' => 'ma_page.php',
    'title' => 'Ma Page',
    'current' => 'ma_page'
];
```
3. **Créer le CSS** (si besoin) : `public/assets/css/pages/ma_page.css`
4. **Créer le JS** (si besoin) : `public/assets/js/MaPage/VueMaPage.js`

### Ajouter une Nouvelle API

1. **Créer le modèle** : `app/MonModule/ModeleMonModule.php`
2. **Créer le contrôleur** : `app/MonModule/ControleurMonModule.php`
3. **Ajouter la route API** dans `public/index.php` :
```php
$apiRoutes['/api/mon-module'] = __DIR__ . '/../app/MonModule/ControleurMonModule.php';
```

### Ajouter un Champ à un Véhicule

1. **Modifier le schéma SQL** : `database/schema_complet.sql`
2. **Modifier `ValidateurVehicule.php`** : ajouter validation
3. **Modifier `ModeleVehicule.php`** : ajouter dans INSERT/UPDATE
4. **Modifier `ControleurGalerie.php`** : ajouter filtre si besoin
5. **Modifier la vue formulaire** : `ajout_vehicule.php` / `modification_vehicule.php`
6. **Modifier le JS** : `VueAjoutVehicule.js` / `VueModificationVehicule.js`

### Ajouter un Nouveau Filtre Galerie

1. **Modifier `ControleurGalerie.php`** : validation du paramètre
2. **Modifier `ModeleVehicule.php`** : ajouter condition SQL dans `obtenirTous()`
3. **Modifier `galerie.php`** : ajouter le contrôle HTML
4. **Modifier `VueGalerie.js`** : gérer le filtre côté client

---

## ✅ CHECKLIST ANTI-DUPLICATION

Avant de créer/modifier quoi que ce soit, vérifier :

### Classes Existantes
- [ ] `Utilitaires` existe → ne pas recréer de fonctions JSON/validation
- [ ] `GestionnaireSession` existe → utiliser pour sessions/CSRF
- [ ] `ServiceChiffrement` existe → utiliser pour crypto
- [ ] `Securite` existe → utiliser pour headers/échappement
- [ ] `ValidateurVehicule` existe → utiliser pour validation véhicules

### Fonctions Existantes
| Besoin | Fonction Existante |
|--------|-------------------|
| Envoyer JSON | `Utilitaires::envoyerJSON()` |
| Lire body JSON | `Utilitaires::lireCorpsJSON()` |
| Valider email | `Utilitaires::emailValide()` |
| Valider téléphone | `Utilitaires::telephoneValide()` |
| Vérifier connexion | `GestionnaireSession::estConnecte()` |
| Token CSRF | `GestionnaireSession::genererTokenCSRF()` |
| Hacher mot de passe | `ServiceChiffrement::hacherMotDePasse()` |
| Échapper HTML | `Securite::echapper()` |

### Fichiers CSS Existants
- [ ] Variables → `variables.css`
- [ ] Boutons → `boutons.css`
- [ ] Formulaires → `formulaires.css`
- [ ] Cartes → `cartes.css`
- [ ] Alertes → `alertes.css`

### Patterns JavaScript
- [ ] Utiliser `obtenirUrlApi()` pour les URLs API
- [ ] Utiliser `formaterMonnaie()` pour les prix
- [ ] Utiliser `echapperHTML()` pour afficher du contenu utilisateur
- [ ] Les requêtes fetch sont automatiquement protégées CSRF

---

## 🚨 POINTS D'ATTENTION

1. **Ne jamais** créer de nouvelle connexion PDO → utiliser `BaseDeDonnees::obtenirConnexion()`
2. **Toujours** utiliser les requêtes préparées pour les données utilisateur
3. **Toujours** valider le token CSRF pour les actions POST/PUT/DELETE
4. **Toujours** vérifier `GestionnaireSession::estConnecte()` pour les actions authentifiées
5. **Noms en français** sauf colonnes BDD et clés JSON API
6. **Jamais de `echo`** direct dans les contrôleurs API → utiliser `Utilitaires::envoyerJSON()`

---

*Dernière mise à jour : Janvier 2026*
*Version du projet : 2.0*
