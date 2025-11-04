# 🗄️ Guide : Déployer MySQL sur Railway et connecter l'application

Ce guide vous explique **étape par étape** comment déployer votre base de données MySQL sur Railway.

## 📋 Prérequis

- Avoir un projet Railway créé avec votre application
- Avoir le code déployé sur Railway
- Avoir accès au dashboard Railway

---

## 🚀 Étape 1 : Ajouter MySQL à Railway

### Dans le dashboard Railway :

1. **Ouvrez votre projet** Railway (celui avec ReVente-Auto)

2. **Cliquez sur "+ New"** (en haut à droite)

3. **Sélectionnez "Database"**

4. **Choisissez "Add MySQL"**

5. Railway va créer automatiquement :
   - Une instance MySQL 8.0
   - Des credentials sécurisés
   - Des variables d'environnement

⏱️ **Attendez 30 secondes** que le service MySQL démarre (statut "Running" ✅)

---

## 🔗 Étape 2 : Lier MySQL à votre application web

### Configurer les variables d'environnement

1. **Cliquez sur votre service WEB** (celui avec le Dockerfile)

2. **Allez dans l'onglet "Variables"**

3. **Cliquez sur "Raw Editor"**

4. **Ajoutez ces 5 variables** :

```bash
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_NAME=${{MySQL.MYSQLDATABASE}}
DB_USER=${{MySQL.MYSQLUSER}}
DB_PASS=${{MySQL.MYSQLPASSWORD}}
```

> 💡 **Important** : Railway remplacera automatiquement `${{MySQL.VARIABLE}}` par les vraies valeurs

5. **Cliquez sur "Add"** ou **"Update Variables"**

6. Railway va **redémarrer automatiquement** votre application avec les nouvelles variables

---

## 📥 Étape 3 : Importer le schéma SQL dans MySQL

Vous avez **3 méthodes** pour importer votre fichier `database/schema.sql` :

### 🟢 Méthode 1 : Script PowerShell (Le plus simple sur Windows)

**Utilisez le script que j'ai créé :**

```powershell
# Dans le dossier ReVente-Auto
.\import-db-railway.ps1
```

Si vous n'avez pas Railway CLI, installez-le :
```powershell
npm install -g @railway/cli
```

Puis liez votre projet :
```powershell
railway login
railway link
```

---

### 🔵 Méthode 2 : Via un client MySQL (Recommandé)

**Clients MySQL disponibles :**
- [MySQL Workbench](https://www.mysql.com/products/workbench/) (Officiel, gratuit)
- [TablePlus](https://tableplus.com/) (Moderne, gratuit pour usage basique)
- [DBeaver](https://dbeaver.io/) (Open source, gratuit)
- [HeidiSQL](https://www.heidisql.com/) (Windows, gratuit)

**Étapes :**

#### A. Récupérer les credentials MySQL

1. Dans Railway, **cliquez sur votre service MySQL**
2. Allez dans l'onglet **"Connect"**
3. Notez ces informations :
   ```
   MYSQLHOST     : containers-us-west-XXX.railway.app
   MYSQLPORT     : 1234
   MYSQLDATABASE : railway
   MYSQLUSER     : root
   MYSQLPASSWORD : xxxxxxxxxxxxxxxxxx
   ```

#### B. Se connecter avec votre client

**Exemple avec MySQL Workbench :**

1. **Nouvelle connexion** → "+" 
2. Remplissez :
   - **Connection Name** : Railway - ReVente-Auto
   - **Hostname** : (MYSQLHOST de Railway)
   - **Port** : (MYSQLPORT de Railway)
   - **Username** : (MYSQLUSER de Railway)
   - **Password** : (MYSQLPASSWORD de Railway) - Cliquez sur "Store in Keychain"

3. **Test Connection** → Devrait afficher "Successfully connected"

4. **OK** pour sauvegarder

#### C. Importer le schéma

1. **Ouvrez la connexion** que vous venez de créer

2. **Menu** : Server → Data Import

3. **Import from Self-Contained File** → Sélectionnez `database/schema.sql`

4. **Default Target Schema** : `railway` (ou le nom de votre base)

5. **Start Import** ▶️

6. Attendez que l'import se termine ✅

---

### 🟡 Méthode 3 : Via Railway CLI (Terminal)

```bash
# 1. Installer Railway CLI
npm install -g @railway/cli

# 2. Se connecter
railway login

# 3. Lier le projet
railway link

# 4. Se connecter à MySQL
railway connect MySQL

# 5. Dans le terminal MySQL, exécuter :
source C:/xampp/htdocs/test/ReVente-Auto/database/schema.sql

# 6. Vérifier les tables
SHOW TABLES;

# 7. Quitter
exit
```

---

## ✅ Étape 4 : Vérifier que tout fonctionne

### A. Vérifier les tables

**Avec votre client MySQL**, exécutez :

```sql
USE railway;  -- ou le nom de votre base
SHOW TABLES;
```

Vous devriez voir :
```
+-------------------+
| Tables_in_railway |
+-------------------+
| email_verifications|
| password_resets   |
| users             |
| vehicles          |
+-------------------+
```

### B. Vérifier les données de test

```sql
SELECT * FROM vehicles LIMIT 5;
```

Vous devriez voir 5 véhicules de démonstration.

### C. Tester l'application

1. **Ouvrez votre URL Railway** (ex: `revente-auto-production.up.railway.app`)

2. **Allez sur la page Galerie** : `/galerie`

3. Vous devriez voir les **5 véhicules** chargés depuis la base de données ✅

---

## 🐛 Dépannage

### ❌ Erreur : "Cannot connect to database"

**Vérifiez :**
1. Le service MySQL est bien "Running" dans Railway
2. Les variables d'environnement sont bien configurées dans le service Web
3. Le service Web a redémarré après l'ajout des variables

**Solution :**
- Allez dans Deployments → Redeploy

---

### ❌ Erreur : "Table doesn't exist"

**Cause :** Le schéma SQL n'a pas été importé

**Solution :**
- Utilisez une des 3 méthodes ci-dessus pour importer `database/schema.sql`

---

### ❌ Erreur : "Access denied for user"

**Cause :** Mauvais credentials ou variables mal configurées

**Solution :**
1. Vérifiez que les variables utilisent bien la syntaxe `${{MySQL.VARIABLE}}`
2. Redémarrez le service Web
3. Consultez les logs : Deployments → View Logs

---

### ❌ Connexion MySQL timeout

**Cause :** Le service MySQL n'est pas accessible depuis l'extérieur

**Solution :**
- Railway MySQL est accessible uniquement depuis votre réseau Railway (normal)
- Pour importer, utilisez Railway CLI ou un client MySQL avec les credentials publics

---

## 🔐 Sécurité

### ⚠️ Important

1. **Ne jamais commiter les credentials** dans Git
   - Déjà protégé par `.gitignore` ✅
   - Utilisez `.env.example` pour les templates

2. **Credentials dans Railway uniquement**
   - Railway gère automatiquement les credentials sécurisés
   - Pas besoin de les copier ailleurs

3. **Backups**
   - Railway ne fait pas de backups automatiques (plan gratuit)
   - Faites des exports réguliers :
   ```bash
   railway run mysqldump -u $MYSQLUSER -p$MYSQLPASSWORD $MYSQLDATABASE > backup.sql
   ```

---

## 📊 Récapitulatif

| Étape | Action | Statut |
|-------|--------|--------|
| 1 | Ajouter MySQL sur Railway | ⬜ |
| 2 | Configurer variables d'environnement | ⬜ |
| 3 | Importer schema.sql | ⬜ |
| 4 | Vérifier les tables | ⬜ |
| 5 | Tester l'application | ⬜ |

---

## 🎯 Commandes utiles

### Vérifier la connexion Railway CLI
```bash
railway whoami
railway status
```

### Se connecter à MySQL
```bash
railway connect MySQL
```

### Voir les variables d'environnement
```bash
railway variables
```

### Voir les logs de l'application
```bash
railway logs
```

---

## 🆘 Besoin d'aide ?

1. **Consultez** : `docs/TROUBLESHOOTING.md`
2. **Logs Railway** : Deployments → View Logs
3. **Vérifiez** que MySQL est "Running"
4. **Redémarrez** l'application si nécessaire

---

**Bonne chance avec votre déploiement ! 🚀**
