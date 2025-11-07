# 📂 Structure du projet ReVente-Auto

## Vue d'ensemble

```
ReVente-Auto/
│
├── 📁 api/                      # Backend API
│   ├── api.php                  # API véhicules (CRUD)
│   ├── auth.php                 # Authentification
│   └── config.php               # Configuration DB + helpers
│
├── 📁 database/                 # Base de données
│   └── schema.sql               # Tables: users, vehicles, etc.
│
├── 📁 public/                   # DocumentRoot (accessible web)
│   ├── index.php                # ⭐ Routeur central
│   ├── .htaccess                # Règles Apache
│   │
│   ├── 📁 api/                  # Wrappers publics
│   │   ├── api.php              # → ../../api/api.php
│   │   └── auth.php             # → ../../api/auth.php
│   │
│   ├── 📁 assets/
│   │   ├── 📁 css/
│   │   │   └── style.css        # Styles complets
│   │   └── 📁 js/
│   │       ├── app.js           # Logique galerie
│   │       ├── auth.js          # Logique authentification
│   │       └── nav.js           # Navigation responsive
│   │
│   └── 📁 uploads/
│       ├── .htaccess            # Sécurité (no exec)
│       └── [avatars...]         # Photos utilisateurs
│
├── 📁 views/                    # Templates PHP
│   ├── 📁 layouts/
│   │   └── main.php             # Layout principal (head, nav, footer)
│   │
│   ├── 📁 pages/
│   │   ├── 404.php              # Page erreur
│   │   ├── about.php            # À propos
│   │   ├── auth.php             # Connexion/Inscription
│   │   ├── gallery.php          # Liste véhicules
│   │   ├── home.php             # Accueil
│   │   └── settings.php         # Paramètres utilisateur
│   │
│   └── 📁 partials/
│       ├── footer.php           # Footer site
│       └── nav.php              # Navigation + menu user
│
├── .htaccess                    # Routing racine → public/
├── .gitignore                   # Fichiers exclus Git
├── CHANGELOG.md                 # Historique modifications
├── CLEANUP_REPORT.md            # Rapport nettoyage
├── LICENSE                      # Licence MIT
└── README.md                    # Documentation principale
```

## 🔄 Flux de requête

```
1. http://localhost/test/ReVente-Auto/galerie
                ↓
2. .htaccess (racine)
   → Redirige vers public/index.php
                ↓
3. public/index.php (routeur)
   → Parse URI: /galerie
   → Charge views/pages/gallery.php
                ↓
4. views/layouts/main.php
   → Calcule $basePath et $apiBase
   → Include nav.php, gallery.php, footer.php
   → Charge assets (CSS + JS)
                ↓
5. public/assets/js/app.js
   → Fetch données: /test/ReVente-Auto/api/api.php
                ↓
6. public/api/api.php (wrapper)
   → require ../../api/api.php
                ↓
7. api/api.php
   → Connexion DB (config.php)
   → Requête SELECT vehicles
   → Retour JSON
```

## 🗂️ Rôles des fichiers

### 🔧 Configuration
| Fichier | Rôle |
|---------|------|
| `api/config.php` | Connexion DB + helpers (json, validation) |
| `.htaccess` | Redirection vers public/ |
| `public/.htaccess` | Routing, sécurité, cache |

### 🚀 Routage
| Fichier | Rôle |
|---------|------|
| `public/index.php` | Router central (parse URI → charge vue) |
| `views/layouts/main.php` | Template global (HTML, head, scripts) |

### 🎨 Interface
| Fichier | Rôle |
|---------|------|
| `views/pages/*.php` | Contenu des pages |
| `views/partials/*.php` | Composants réutilisables |
| `public/assets/css/style.css` | Tous les styles |
| `public/assets/js/*.js` | Logique client |

### 🔌 API
| Fichier | Rôle |
|---------|------|
| `api/api.php` | CRUD véhicules |
| `api/auth.php` | Login, register, profil |
| `public/api/*.php` | Wrappers accessibles web |

## 📊 Taille des fichiers

```
api/config.php     ~  1 KB   (25 lignes)
api/auth.php       ~ 15 KB   (250 lignes)
api/api.php        ~  5 KB   (100 lignes)
public/index.php   ~  1 KB   (40 lignes)
views/layouts/main.php  ~  2 KB   (55 lignes)
public/assets/js/auth.js  ~ 10 KB  (270 lignes)
public/assets/js/app.js   ~  7 KB  (174 lignes)
database/schema.sql       ~  4 KB  (76 lignes)
```

## 🎯 Points d'entrée

### Pour l'utilisateur
```
/                    → Accueil
/home                → Accueil
/galerie             → Liste véhicules
/apropos             → À propos
/connexion           → Login/Register
/parametres          → Profil utilisateur
```

### Pour le code (API)
```
/api/api.php         → CRUD véhicules
/api/auth.php        → Authentification
```

## 🔐 Sécurité

- ✅ PDO avec prepared statements
- ✅ Validation côté serveur (auth.php, api.php)
- ✅ Upload sécurisé (MIME check, taille, no exec)
- ✅ Protection fichiers sensibles (.htaccess)
- ✅ Sessions sécurisées (httponly)

## 📦 Dépendances

**Aucune dépendance externe !**
- PHP natif (8.2+)
- MySQL natif (8.0+)
- JavaScript vanilla (ES6+)
- CSS pur (pas de framework)

---

**Structure optimisée et documentée - Novembre 2025**
