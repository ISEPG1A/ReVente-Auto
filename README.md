# 🚗 ReVente-Auto# Ultra (déploiement)



Application web de gestion et vente de véhicules d'occasion. Développée avec PHP, MySQL et JavaScript vanilla.Ce projet PHP (DocumentRoot `public/`) inclut un `Dockerfile` pour être déployé facilement sur **DigitalOcean App Platform**.



![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php)- Voir `DEPLOY_DIGITALOCEAN.md` pour le guide pas-à-pas.

![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)- Pour un déploiement sur VPS (Droplet), suivez une pile LAMP et pointez le VirtualHost vers `public/`.

![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat&logo=docker&logoColor=white)
![Railway](https://img.shields.io/badge/Deploy-Railway-0B0D0E?style=flat&logo=railway)

## ✨ Fonctionnalités

- 🏠 **Page d'accueil** attractive avec présentation du service
- 🚙 **Galerie de véhicules** avec système de filtres et recherche
- 👤 **Système d'authentification** complet (inscription, connexion, gestion de profil)
- 🔐 **Gestion des sessions** sécurisée
- 📱 **Design responsive** adapté mobile, tablette et desktop
- 🎨 **Interface moderne** avec animations et effets visuels
- 📸 **Upload de photos** pour les profils utilisateurs
- ⚙️ **Page paramètres** pour gérer son compte

## 🛠️ Technologies utilisées

### Backend
- **PHP 8.2** - Langage serveur
- **MySQL 8.0** - Base de données relationnelle
- **Apache 2.4** - Serveur web
- **PDO** - Interface d'accès aux données

### Frontend
- **HTML5** - Structure
- **CSS3** - Styles avec variables CSS et animations
- **JavaScript ES6+** - Interactivité (modules natifs)

### DevOps
- **Docker** - Conteneurisation
- **Railway** - Plateforme de déploiement
- **Git** - Gestion de version

## 📁 Structure du projet

```
ReVente-Auto/
├── api/                    # API REST
│   ├── api.php            # Points d'entrée API (véhicules)
│   ├── auth.php           # Authentification (login, register)
│   └── config.php         # Configuration BDD et helpers
├── database/              # Schéma de base de données
│   └── schema.sql         # Tables et données initiales
├── docs/                  # Documentation
# 🚗 ReVente-Auto (version locale XAMPP)
│   └── TROUBLESHOOTING.md # Résolution de problèmes
├── public/                # Dossier public (DocumentRoot)
│   ├── index.php          # Routeur central
│   ├── .htaccess          # Règles de réécriture Apache
│   ├── assets/
│   │   ├── css/          # Feuilles de style
│   │   └── js/           # Scripts JavaScript
│   └── uploads/          # Fichiers uploadés (avatars)
├── views/                 # Templates et vues
├── .env.example          # Template de configuration
├── .gitignore            # Fichiers exclus de Git
```

## 🚀 Installation locale

### Prérequis

- **PHP 8.2+** avec extensions : `pdo`, `pdo_mysql`, `mysqli`
- **MySQL 8.0+** ou **MariaDB 10.5+**
- **Apache** ou **Nginx** avec mod_rewrite
- **Composer** (optionnel)

### Étapes

1. **Cloner le dépôt**
```bash
git clone https://github.com/ISEPG1A/ReVente-Auto.git
cd ReVente-Auto
```

2. **Configurer la base de données**
```bash
# Créer la base de données
mysql -u root -p -e "CREATE DATABASE revente_auto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importer le schéma
mysql -u root -p revente_auto < database/schema.sql
```

3. **Configurer l'environnement**
```bash
nano .env
```

4. **Configurer le serveur web**

**Apache (recommandé)**
```apache
<VirtualHost *:80>
    ServerName revente-auto.local
    DocumentRoot /chemin/vers/ReVente-Auto/public
    
    <Directory /chemin/vers/ReVente-Auto/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

5. **Accéder à l'application**
```
http://revente-auto.local
```

## 🐳 Installation avec Docker

```bash
# Build l'image
docker build -t revente-auto .

# Lancer le conteneur
docker run -d -p 80:80 \
  -e DB_HOST=your-mysql-host \
  -e DB_NAME=revente_auto \
  -e DB_USER=root \
  -e DB_PASS=your-password \
  --name revente-auto \
  revente-auto
```

## ☁️ Déploiement sur Railway

Railway est la plateforme recommandée pour déployer cette application.

### 📋 Guide rapide

1. **Créer un compte** sur [railway.app](https://railway.app)
2. **Nouveau projet** → "Deploy from GitHub repo"
3. **Ajouter MySQL** : "+ New" → "Database" → "MySQL"
4. **Configurer les variables d'environnement** :
```bash
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_NAME=${{MySQL.MYSQLDATABASE}}
DB_USER=${{MySQL.MYSQLUSER}}
DB_PASS=${{MySQL.MYSQLPASSWORD}}
```
5. **Importer le schéma** : `.\import-db-railway.ps1`
6. **Générer un domaine** : Settings → Domains → "Generate Domain"

📚 **Guide complet** : Voir [docs/DEPLOY_RAILWAY.md](docs/DEPLOY_RAILWAY.md)

## 📖 Documentation

- **[Guide de déploiement Railway](docs/DEPLOY_RAILWAY.md)** - Instructions détaillées
- **[Résolution de problèmes](docs/TROUBLESHOOTING.md)** - Solutions aux problèmes courants

## 🔧 Configuration

### Variables d'environnement

| Variable | Description | Exemple |
|----------|-------------|---------|
| `DB_HOST` | Hôte de la base de données | `127.0.0.1` |
| `DB_PORT` | Port MySQL | `3306` |
| `DB_NAME` | Nom de la base | `revente_auto` |
| `DB_USER` | Utilisateur MySQL | `root` |
| `DB_PASS` | Mot de passe MySQL | `secret` |

## 🗄️ Base de données

### Schéma

- **`users`** - Utilisateurs et authentification
- **`vehicles`** - Véhicules en vente
- **`password_resets`** - Tokens de réinitialisation
- **`email_verifications`** - Tokens de vérification email

## 🎨 Personnalisation

### Ajouter des routes

Modifiez `public/index.php` pour ajouter de nouvelles routes :

```php
$routes = [
    // ... routes existantes
    '/nouvelle-page' => [
        'view' => __DIR__ . '/../views/pages/nouvelle-page.php',
        'title' => 'Nouvelle Page',
        'current' => 'nouvelle'
    ],
];
```

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 👥 Auteurs

- **ISEPG1A** - *Développement initial* - [GitHub](https://github.com/ISEPG1A)

---

**Fait avec ❤️ et PHP**
