# 🚗 ReVente-Auto

**Plateforme de vente et d'achat de véhicules d'occasion entre particuliers**

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php)](https://php.net)
[![MariaDB](https://img.shields.io/badge/MariaDB-11.8%2B-003545?logo=mariadb&logoColor=white)](https://mariadb.org)
[![Tests](https://img.shields.io/badge/Tests-532%2F532%20(100%25)-success)](tests/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> **Version 3.2** | Dernière mise à jour : 15 janvier 2026

---

## 📋 Table des matières

- [Présentation](#-présentation)
- [Fonctionnalités](#-fonctionnalités)
- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Structure du projet](#-structure-du-projet)
- [API REST](#-api-rest)
- [Sécurité](#-sécurité)
- [Tests](#-tests)
- [Déploiement](#-déploiement)
- [Contribution](#-contribution)

---

## 🎯 Présentation

ReVente-Auto est une application web moderne permettant aux particuliers de publier et rechercher des annonces de véhicules d'occasion. Elle propose une estimation de prix basée sur l'IA et une expérience utilisateur optimisée.

### Points forts

- 🤖 **Estimation IA** - Évaluation automatique basée sur OpenAI GPT-4o-mini
- 📊 **Score IA** - Notation 0-100 pour évaluer si une annonce est une bonne affaire
- 🗺️ **Géolocalisation** - Recherche par proximité avec API Nominatim/geo.api.gouv.fr
- 💬 **Messagerie chiffrée E2E** - Communication sécurisée RSA 2048 + AES-256-CBC
- 💰 **Propositions de prix** - Système d'offres avec statuts (pending, accepted, declined)
- 🌙 **Thème sombre/clair** - Basculement automatique ou manuel
- ♿ **Accessibilité WCAG 2.1 AA** - Navigation au clavier, ARIA, contraste
- 🔒 **Sécurité renforcée** - CSRF, XSS, SQLi, Rate Limiting, CSP

---

## ✨ Fonctionnalités

### Pour les utilisateurs
- 📝 Création et gestion d'annonces (photos multiples, description détaillée)
- 🔍 Recherche avancée avec filtres (marque, prix, kilométrage, localisation, carburant, boîte)
- ❤️ Système de favoris avec notifications email
- 💰 Estimation automatique de la valeur du véhicule via IA
- 📊 Score IA (0-100) pour évaluer les bonnes affaires
- 💬 Propositions de prix aux vendeurs
- 📧 Messagerie interne chiffrée de bout en bout (RSA + AES)
- 📱 Interface responsive (mobile, tablette, desktop)
- 🌙 Mode sombre/clair

### Pour les administrateurs
- 🛡️ Modération des annonces (approuver/refuser)
- 👥 Gestion des utilisateurs (bannissement)
- 📈 Statistiques et logs d'activité
- 📋 Gestion dynamique des CGU avec versioning PDF automatique
- ❓ Gestion de la FAQ
- 📄 Gestion de la politique de confidentialité

---

## 📦 Prérequis

- **PHP** 8.0 ou supérieur
- **MariaDB** 10.6+ ou **MySQL** 8.0+
- **Apache** avec `mod_rewrite` et `mod_headers`
- **Extensions PHP** : `pdo_mysql`, `mbstring`, `gd`, `openssl`, `curl`
- **Compte Gmail** pour l'envoi d'emails SMTP (ou autre serveur SMTP)
- **Clé API OpenAI** (optionnel, pour l'estimation IA)

---

## 🚀 Installation

### 1. Cloner le repository

```bash
git clone https://github.com/votre-username/ReVente-Auto.git
cd ReVente-Auto
```

### 2. Configurer la base de données

```bash
# Créer la base de données
mysql -u root -p -e "CREATE DATABASE revente_auto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importer le schéma
mysql -u root -p revente_auto < database/schema_complet.sql
```

### 3. Configurer l'application

Créez le fichier `.env` à la racine du projet :

```bash
cp .env.example .env
```

Éditez `.env` avec vos paramètres :

```env
# Base de données
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=revente_auto
DB_USER=root
DB_PASS=votre_mot_de_passe

# Sécurité (clé secrète de 32+ caractères)
APP_SECRET_KEY=votre_cle_secrete_64_caracteres_hexadecimaux

# OpenAI (optionnel, pour estimation IA)
OPENAI_API_KEY=sk-proj-...

# SMTP (Gmail recommandé)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=votre@gmail.com
SMTP_PASS=votre_mot_de_passe_application
SMTP_FROM_EMAIL=noreply@revente-auto.fr
SMTP_FROM_NAME=ReVente-Auto
```

### 4. Configurer le serveur web

#### Apache (`.htaccess` inclus)

Assurez-vous que `mod_rewrite` est activé :
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx

```nginx
server {
    listen 80;
    server_name revente-auto.local;
    root /var/www/ReVente-Auto/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. Permissions

```bash
# Dossier d'uploads accessible en écriture
chmod -R 755 public/uploads
chown -R www-data:www-data public/uploads
```

---

## ⚙️ Configuration

### Variables d'environnement (.env)

| Variable | Description | Requis |
|----------|-------------|--------|
| `DB_HOST` | Hôte de la base de données | ✅ |
| `DB_PORT` | Port (défaut: 3306) | ❌ |
| `DB_NAME` | Nom de la base de données | ✅ |
| `DB_USER` | Utilisateur BDD | ✅ |
| `DB_PASS` | Mot de passe BDD | ✅ |
| `APP_SECRET_KEY` | Clé de chiffrement (64 hex) | ✅ |
| `OPENAI_API_KEY` | Clé API OpenAI | ❌ |
| `SMTP_HOST` | Serveur SMTP | ✅ |
| `SMTP_PORT` | Port SMTP (587 ou 465) | ✅ |
| `SMTP_USER` | Utilisateur SMTP | ✅ |
| `SMTP_PASS` | Mot de passe SMTP | ✅ |
| `SMTP_FROM_EMAIL` | Email expéditeur | ✅ |
| `SMTP_FROM_NAME` | Nom expéditeur | ✅ |

### Configuration SMTP Gmail

1. Activez l'authentification à 2 facteurs sur votre compte Google
2. Allez dans *Paramètres* > *Sécurité* > *Mots de passe d'application*
3. Générez un mot de passe pour "Messagerie"
4. Utilisez ce mot de passe dans `SMTP_PASS`

---

## 📁 Structure du projet

```
ReVente-Auto/
├── app/                        # Code source PHP (backend)
│   ├── Controleurs/           # Contrôleurs MVC
│   │   ├── Api/              # 19 contrôleurs API REST (JSON)
│   │   └── Pages/            # 20 contrôleurs de pages HTML
│   ├── Modeles/              # 15 modèles (accès BDD)
│   ├── Services/             # 11 services utilitaires
│   │   ├── BaseDeDonnees.php    # Singleton PDO
│   │   ├── GestionnaireSession.php
│   │   ├── ServiceChiffrement.php
│   │   ├── ServiceEmail.php
│   │   ├── Securite.php
│   │   └── ...
│   ├── Templates/            # Templates externalisés
│   │   └── Email/           # 10 templates d'emails HTML
│   └── Libs/                 # Bibliothèques tierces
│       └── FPDF/            # Génération PDF (CGU)
├── database/                  # Scripts SQL
│   └── schema_complet.sql   # 19 tables
├── docs/                      # Documentation
│   ├── CONTEXTE_IA.md       # Doc complète pour IA
│   └── GUIDE_TESTS_SECURITE.md
├── public/                    # Racine web (document root)
│   ├── index.php            # Point d'entrée unique (routeur)
│   ├── .htaccess            # Config Apache
│   ├── assets/              # Ressources statiques
│   │   ├── css/            # 37 fichiers CSS organisés
│   │   ├── js/             # 27 modules JavaScript ES6
│   │   └── images/         # Logo, avatars, équipe
│   └── uploads/             # Fichiers utilisateurs
│       ├── avatars/        # Photos de profil
│       ├── vehicules/      # Images des annonces
│       └── cgu_versions/   # Archives PDF des CGU
├── views/                     # Vues PHP (frontend)
│   ├── layouts/             # Template principal
│   ├── pages/               # 22 pages organisées
│   └── partials/            # Navigation, footer
├── tests/                     # Suite de tests automatisés
│   └── run_all_tests.php    # 532 tests (7 catégories)
├── .env                       # Variables d'environnement
├── .env.example              # Template .env
├── config.php                # Chargeur de configuration
└── README.md
```

---

## 🔌 API REST

L'API suit les conventions REST avec des réponses JSON standardisées.

### Format des réponses

```json
// Succès
{ "ok": true, "data": { ... } }

// Erreur
{ "error": "Message d'erreur en français" }
```

### Authentification

```http
POST /api/connexion
Content-Type: application/json
X-CSRF-Token: {token}

{
    "email": "user@example.com",
    "password": "motdepasse"
}
```

### Routes API principales (20 endpoints)

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `POST` | `/api/connexion` | Connexion | ❌ |
| `POST` | `/api/inscription` | Inscription | ❌ |
| `GET` | `/api/vehicule/galerie` | Liste véhicules + filtres | ❌ |
| `GET` | `/api/vehicule/details?id={id}` | Détails véhicule | ❌ |
| `POST` | `/api/vehicule/ajout` | Créer annonce | ✅ |
| `PUT` | `/api/vehicule/modification` | Modifier annonce | ✅ |
| `DELETE` | `/api/vehicule/modification` | Supprimer annonce | ✅ |
| `GET/POST/DELETE` | `/api/favoris` | Gestion favoris | ✅ |
| `GET/POST` | `/api/messagerie` | Conversations & messages | ✅ |
| `POST` | `/api/estimation` | Estimation prix IA | ❌ |
| `GET/POST` | `/api/score-ia` | Score IA (0-100) | ❌ |
| `POST` | `/api/contact` | Formulaire contact | ❌ |
| `GET` | `/api/localisation` | Géolocalisation | ❌ |
| `GET/PUT` | `/api/profil` | Profil utilisateur | ✅ |
| `GET` | `/api/faq` | FAQ | ❌ |
| `GET` | `/api/cgu` | CGU | ❌ |
| `*` | `/api/admin` | Panel admin | ✅ (admin) |

---

## 🔒 Sécurité

### Mesures implémentées (Score: 118/118 tests)

- ✅ **Protection CSRF** - Tokens de session + header X-CSRF-Token automatique
- ✅ **XSS** - Échappement systématique + Content Security Policy strict
- ✅ **SQL Injection** - Requêtes préparées PDO exclusivement
- ✅ **Rate Limiting** - Limite par IP en base de données
- ✅ **Session Fixation** - Régénération ID après connexion
- ✅ **Session Hijacking** - Validation IP + User-Agent
- ✅ **Chiffrement E2E** - Messages chiffrés RSA 2048 + AES-256-CBC
- ✅ **Mots de passe** - Bcrypt (password_hash/verify)
- ✅ **Tokens sécurisés** - 48 bytes cryptographiquement sûrs
- ✅ **Upload sécurisé** - Validation MIME, extension, taille
- ✅ **Path Traversal** - Protection contre ../
- ✅ **Mass Assignment** - Liste blanche des champs

### En-têtes HTTP de sécurité

```
Strict-Transport-Security: max-age=31536000; includeSubDomains
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; ...
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(self), microphone=(), camera=(), payment=()
```

---

## 🧪 Tests

Le projet inclut une suite complète de **532 tests automatisés** répartis en 7 catégories.

### Exécution des tests

```bash
cd tests
php run_all_tests.php
```

### Catégories de tests

| Catégorie | Tests | Description |
|-----------|-------|-------------|
| **Sécurité** | 118 | CSRF, XSS, SQLi, sessions, headers, uploads |
| **API** | 72 | Endpoints, codes HTTP, pagination, validation |
| **Validation** | 73 | Email, mot de passe, téléphone, fichiers |
| **Performance** | 47 | Temps de réponse, cache, compression |
| **Base de données** | 140 | Structure, FK, index, encodage, intégrité |
| **Accessibilité** | 72 | HTML sémantique, ARIA, formulaires, contraste |
| **Emails** | 10 | Envoi de tous les types d'emails |

### Rapport de tests

Les résultats sont sauvegardés dans `tests/rapport_final.txt` avec le détail de chaque test.

### Tests manuels

Consultez [docs/GUIDE_TESTS_SECURITE.md](docs/GUIDE_TESTS_SECURITE.md) pour les scénarios de test manuels.

---

## 🚀 Déploiement

### Production checklist

1. **Configurer les variables d'environnement**
   ```bash
   # Créer .env avec les vraies valeurs de production
   cp .env.example .env
   nano .env
   ```

2. **Sécuriser les fichiers**
   ```bash
   chmod 600 .env
   chmod 755 public/uploads
   chown -R www-data:www-data public/uploads
   ```

3. **Activer HTTPS**
   - Installer un certificat SSL (Let's Encrypt recommandé)
   - Forcer la redirection HTTPS dans Apache

4. **Configurer Apache**
   ```apache
   <VirtualHost *:443>
       ServerName revente-auto.fr
       DocumentRoot /var/www/ReVente-Auto/public
       
       <Directory /var/www/ReVente-Auto/public>
           AllowOverride All
           Require all granted
       </Directory>
       
       SSLEngine on
       SSLCertificateFile /etc/letsencrypt/live/revente-auto.fr/fullchain.pem
       SSLCertificateKeyFile /etc/letsencrypt/live/revente-auto.fr/privkey.pem
   </VirtualHost>
   ```

5. **Optimiser PHP**
   ```ini
   ; php.ini production
   display_errors = Off
   log_errors = On
   error_log = /var/log/php/revente-auto.log
   opcache.enable = 1
   ```

6. **Sauvegardes automatiques**
   ```bash
   # Crontab
   0 2 * * * mysqldump -u user -p revente_auto > /backups/db_$(date +\%Y\%m\%d).sql
   ```

---

## 🤝 Contribution

Les contributions sont bienvenues ! Veuillez suivre ces étapes :

1. Fork le projet
2. Créez une branche (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Committez vos changements (`git commit -m 'Ajout: nouvelle fonctionnalité'`)
4. Pushez sur la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Ouvrez une Pull Request

### Standards de code

- **PHP** : Noms en français (variables, fonctions, classes), PSR-12
- **JavaScript** : ES6+ modules, noms en français
- **CSS** : BEM pour les classes, kebab-case pour les fichiers
- **Commits** : Format conventionnel (feat:, fix:, docs:)
- **Documentation** : Consultez [docs/CONTEXTE_IA.md](docs/CONTEXTE_IA.md) avant de modifier

### Conventions de nommage

| Élément | Convention | Exemple |
|---------|------------|---------|
| Variables PHP | camelCase français | `$utilisateur`, `$prixMinimum` |
| Fonctions PHP | camelCase français | `obtenirTous()`, `validerToken()` |
| Classes PHP | PascalCase français | `ModeleVehicule`, `GestionnaireSession` |
| Fichiers CSS | kebab-case | `barre-outils.css` |
| Modules JS | PascalCase | `VueGalerie.js` |

---

## 📄 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 📞 Support

- **Documentation IA** : [docs/CONTEXTE_IA.md](docs/CONTEXTE_IA.md)
- **Guide tests** : [docs/GUIDE_TESTS_SECURITE.md](docs/GUIDE_TESTS_SECURITE.md)
- **Issues** : [GitHub Issues](https://github.com/votre-username/ReVente-Auto/issues)

---

<p align="center">
  <b>ReVente-Auto v3.2</b> — Développé avec ❤️
  <br>
  <sub>532 tests ✅ | 100% de réussite | PHP 8+ | MariaDB 11+</sub>
</p>
