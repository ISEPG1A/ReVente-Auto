# 🚂 Déploiement sur Railway

Ce guide vous explique comment déployer votre application ReVente-Auto sur Railway.

## 📋 Prérequis

- Un compte GitHub avec votre dépôt ReVente-Auto
- Un compte Railway (gratuit) : [railway.app](https://railway.app)

## 🚀 Étapes de déploiement

### 1. Créer un nouveau projet

1. Connectez-vous sur [railway.app](https://railway.app)
2. Cliquez sur **"New Project"**
3. Sélectionnez **"Deploy from GitHub repo"**
4. Choisissez votre dépôt **ReVente-Auto**
5. Railway détectera automatiquement le `Dockerfile`

### 2. Ajouter une base de données MySQL

1. Dans votre projet Railway, cliquez sur **"+ New"**
2. Sélectionnez **"Database"** → **"Add MySQL"**
3. Railway créera automatiquement une instance MySQL

### 3. Configurer les variables d'environnement

Railway génère automatiquement des variables pour MySQL. Vous devez les mapper à celles attendues par votre application.

**Dans les Settings de votre service web, section "Variables", ajoutez :**

```bash
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_NAME=${{MySQL.MYSQLDATABASE}}
DB_USER=${{MySQL.MYSQLUSER}}
DB_PASS=${{MySQL.MYSQLPASSWORD}}
```

> 💡 **Astuce** : Railway remplacera automatiquement ces références par les valeurs réelles de votre base MySQL.

### 4. Importer le schéma de base de données

Railway ne fournit pas d'interface web pour MySQL. Vous devez utiliser un client MySQL :

#### Option A : Via Railway CLI (Recommandé)

```bash
# Installer Railway CLI
npm i -g @railway/cli

# Se connecter
railway login

# Se connecter à votre projet
railway link

# Ouvrir une connexion à MySQL
railway connect MySQL
```

Puis exécutez le contenu de `database/schema.sql` :

```sql
source /chemin/vers/database/schema.sql
```

#### Option B : Via MySQL Workbench / TablePlus / DBeaver

1. Dans Railway, allez dans votre service **MySQL**
2. Onglet **"Connect"** → copiez les credentials :
   - Host
   - Port
   - Database
   - Username
   - Password

3. Ouvrez votre client MySQL préféré
4. Créez une nouvelle connexion avec ces credentials
5. Exécutez le fichier `database/schema.sql`

#### Option C : Via le script d'import automatique (voir ci-dessous)

### 5. Vérifier le déploiement

1. Railway générera automatiquement une URL (ex: `your-app.up.railway.app`)
2. Accédez à cette URL pour tester votre application
3. Vérifiez que :
   - ✅ La page d'accueil s'affiche
   - ✅ L'API répond (`/api/api.php`)
   - ✅ La connexion à la base de données fonctionne

### 6. Configurer un domaine personnalisé (Optionnel)

1. Dans votre service web, allez dans **"Settings"**
2. Section **"Domains"**
3. Cliquez sur **"Generate Domain"** ou ajoutez votre propre domaine

## 🔧 Configuration avancée

### Variables d'environnement supplémentaires

Vous pouvez ajouter d'autres variables selon vos besoins :

```bash
# Environnement
APP_ENV=production

# Timezone
TZ=Europe/Paris

# Limites PHP (optionnel)
PHP_MEMORY_LIMIT=256M
PHP_UPLOAD_MAX_FILESIZE=10M
```

### Activer les logs

Railway affiche automatiquement les logs de votre application dans l'onglet **"Deployments"** → sélectionnez un déploiement → **"View Logs"**.

## 🐛 Dépannage

### Erreur de connexion à la base de données

1. Vérifiez que les variables d'environnement sont bien configurées
2. Vérifiez que le service MySQL est démarré
3. Consultez les logs : `railway logs`

### Le site affiche une erreur 500

1. Vérifiez les logs Railway
2. Vérifiez que le schéma SQL a été importé
3. Vérifiez les permissions du dossier `uploads/`

### Les images ne s'affichent pas

Le dossier `uploads/` doit être persistant. Railway utilise des volumes éphémères par défaut.

**Solution** : Utilisez un service de stockage externe (AWS S3, Cloudinary, etc.) ou un volume Railway persistent.

## 📚 Ressources

- [Documentation Railway](https://docs.railway.app)
- [Railway CLI](https://docs.railway.app/develop/cli)
- [Railway MySQL](https://docs.railway.app/databases/mysql)

## 🎉 Déploiement réussi !

Votre application est maintenant en ligne ! 🚀

N'oubliez pas de :
- ⚠️ Changer les credentials par défaut
- 🔒 Activer HTTPS (Railway le fait automatiquement)
- 📧 Configurer l'envoi d'emails si nécessaire
- 💾 Mettre en place des sauvegardes de la base de données
