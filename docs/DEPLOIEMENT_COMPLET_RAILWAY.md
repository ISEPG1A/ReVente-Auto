# 🚂 Déploiement Complet sur Railway (MySQL + phpMyAdmin + Application)

## 🎯 Objectif
Héberger TOUT sur Railway :
- ✅ Base de données MySQL
- ✅ phpMyAdmin (interface web)
- ✅ Votre application PHP ReVente-Auto

**Aucun outil local nécessaire** - tout fonctionne dans le cloud !

---

## 📋 Étape 1 : Ajouter MySQL sur Railway

### 1.1 Créer le service MySQL

1. Allez sur https://railway.app
2. Ouvrez votre projet **ReVente-Auto**
3. Cliquez sur **+ New** → **Database** → **Add MySQL**
4. Railway va créer automatiquement :
   - Un serveur MySQL 8.0
   - Une base de données nommée `railway`
   - Les credentials (host, port, user, password)

### 1.2 Récupérer les identifiants MySQL

1. Cliquez sur le service **MySQL**
2. Allez dans l'onglet **Variables**
3. Vous verrez :
   ```
   MYSQLHOST=xxxxx.railway.app
   MYSQLPORT=3306
   MYSQLDATABASE=railway
   MYSQLUSER=root
   MYSQLPASSWORD=xxxxxxxxxxxxx
   ```

**⚠️ GARDEZ CES IDENTIFIANTS** - vous en aurez besoin pour les étapes suivantes !

---

## 📋 Étape 2 : Configurer l'application PHP

### 2.1 Ajouter les variables d'environnement

1. Cliquez sur votre service **Web** (ReVente-Auto)
2. Allez dans l'onglet **Variables**
3. Ajoutez les variables suivantes (avec les valeurs de votre MySQL) :

```bash
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_NAME=${{MySQL.MYSQLDATABASE}}
DB_USER=${{MySQL.MYSQLUSER}}
DB_PASS=${{MySQL.MYSQLPASSWORD}}
```

**💡 Astuce** : Railway permet de référencer automatiquement les variables du service MySQL avec la syntaxe `${{MySQL.NOM_VARIABLE}}`

### 2.2 Vérifier le fichier de configuration

Votre fichier `api/config.php` doit utiliser ces variables d'environnement :

```php
<?php
// Configuration de la base de données
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'revente_auto');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
```

---

## 📋 Étape 3 : Déployer phpMyAdmin sur Railway

### 3.1 Créer un nouveau service phpMyAdmin

Railway ne propose pas phpMyAdmin par défaut, mais on peut le déployer facilement !

**Méthode : Utiliser l'image Docker officielle**

1. Dans votre projet Railway, cliquez sur **+ New**
2. Sélectionnez **Empty Service**
3. Nommez-le **phpMyAdmin**
4. Allez dans l'onglet **Settings**
5. Dans **Source**, sélectionnez **Docker Image**
6. Entrez : `phpmyadmin/phpmyadmin:latest`

### 3.2 Configurer les variables d'environnement phpMyAdmin

Dans l'onglet **Variables** du service phpMyAdmin, ajoutez :

```bash
PMA_HOST=${{MySQL.MYSQLHOST}}
PMA_PORT=${{MySQL.MYSQLPORT}}
PMA_USER=${{MySQL.MYSQLUSER}}
PMA_PASSWORD=${{MySQL.MYSQLPASSWORD}}
```

### 3.3 Générer un domaine public

1. Restez dans le service **phpMyAdmin**
2. Allez dans **Settings** → **Networking**
3. Cliquez sur **Generate Domain**
4. Railway va générer une URL comme : `phpmyadmin-production-xxxx.up.railway.app`

**🎉 phpMyAdmin est maintenant accessible en ligne !**

---

## 📋 Étape 4 : Importer le schéma SQL

### 4.1 Accéder à phpMyAdmin

1. Ouvrez l'URL générée : `https://phpmyadmin-production-xxxx.up.railway.app`
2. Connectez-vous avec :
   - **Utilisateur** : `root` (ou la valeur de `MYSQLUSER`)
   - **Mot de passe** : la valeur de `MYSQLPASSWORD`

### 4.2 Importer database/schema.sql

1. Dans phpMyAdmin, sélectionnez la base **railway** (colonne de gauche)
2. Cliquez sur l'onglet **Importer**
3. Cliquez sur **Parcourir** et sélectionnez `database/schema.sql`
4. Faites défiler vers le bas et cliquez sur **Exécuter**

✅ Votre base de données est maintenant créée avec toutes les tables !

### 4.3 Vérifier les tables

Dans phpMyAdmin, vous devriez voir 4 tables :
- `users`
- `vehicles`
- `password_resets`
- `email_verifications`

---

## 📋 Étape 5 : Redéployer l'application

### 5.1 Redéploiement automatique

Railway redéploie automatiquement votre application à chaque commit Git. Pour forcer un redéploiement :

1. Cliquez sur le service **Web** (ReVente-Auto)
2. Allez dans l'onglet **Deployments**
3. Cliquez sur **Deploy** → **Redeploy**

### 5.2 Vérifier les logs

1. Dans l'onglet **Deployments**, cliquez sur le dernier déploiement
2. Regardez les logs :
   - **Build Logs** : compilation du Docker
   - **Deploy Logs** : démarrage d'Apache

**Recherchez les erreurs** (en rouge) comme :
- Erreurs PHP
- Erreurs de connexion à la base de données
- Problèmes de permissions

### 5.3 Tester l'application

1. Ouvrez l'URL de votre application : `https://revente-auto-production-xxxx.up.railway.app`
2. Testez les pages :
   - Accueil : `/`
   - Galerie : `/galerie`
   - Connexion : `/connexion`

---

## 🐛 Dépannage : "Application failed to respond"

### Problème 1 : PHP ne démarre pas

**Vérification** : Regardez les Deploy Logs

**Solutions possibles** :

```dockerfile
# Dans Dockerfile, vérifiez que vous avez :
EXPOSE 80
CMD ["apache2-foreground"]
```

### Problème 2 : Port incorrect

Railway attend que l'application écoute sur le port défini dans la variable `PORT`.

**Solution** : Modifiez le Dockerfile pour lire la variable PORT :

```dockerfile
# Remplacez EXPOSE 80 par :
EXPOSE ${PORT:-80}

# Ajoutez avant CMD :
RUN sed -i 's/Listen 80/Listen ${PORT:-80}/' /etc/apache2/ports.conf
```

### Problème 3 : Chemin DocumentRoot incorrect

**Vérification** : Les fichiers sont-ils dans `/var/www/html/public` ?

**Solution** : Dans les logs, vérifiez :
```
AH00112: Warning: DocumentRoot [/var/www/html/public] does not exist
```

Si présent, vérifiez votre COPY dans le Dockerfile :
```dockerfile
COPY . /var/www/html
```

### Problème 4 : Connexion MySQL échoue

**Symptômes** : Page blanche, erreur 500

**Solutions** :

1. Vérifiez les variables d'environnement dans Railway (Web service → Variables)
2. Testez la connexion dans `api/config.php` :

```php
<?php
// Ajoutez temporairement pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = getenv('DB_HOST');
echo "Connexion à : $host\n";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_NAME'),
        getenv('DB_USER'),
        getenv('DB_PASS')
    );
    echo "✅ Connexion réussie !";
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
```

---

## 📊 Architecture finale sur Railway

```
┌─────────────────────────────────────────────────────┐
│                   RAILWAY PROJECT                    │
│                                                      │
│  ┌─────────────────┐  ┌──────────────────┐         │
│  │   MySQL 8.0     │  │   phpMyAdmin     │         │
│  │                 │  │                  │         │
│  │  Port: 3306     │◄─┤  Image Docker    │         │
│  │  DB: railway    │  │  phpmyadmin:     │         │
│  │                 │  │  latest          │         │
│  └────────▲────────┘  └──────────────────┘         │
│           │                      ▲                  │
│           │                      │                  │
│           │           https://phpmyadmin-xxxx       │
│           │                                         │
│  ┌────────┴────────────────────┐                   │
│  │   ReVente-Auto (Web)        │                   │
│  │                              │                   │
│  │   - PHP 8.2 + Apache        │                   │
│  │   - Dockerfile custom       │                   │
│  │   - Variables env liées     │                   │
│  │     à MySQL                 │                   │
│  └─────────────────────────────┘                   │
│             ▲                                       │
│             │                                       │
│   https://revente-auto-xxxx                        │
│                                                     │
└─────────────────────────────────────────────────────┘
         ▲
         │
      VOUS (navigateur web)
```

---

## ✅ Checklist finale

- [ ] Service MySQL créé sur Railway
- [ ] Variables d'environnement DB_* configurées dans le service Web
- [ ] Service phpMyAdmin déployé avec l'image Docker
- [ ] Variables PMA_* configurées dans phpMyAdmin
- [ ] Domaine public généré pour phpMyAdmin
- [ ] Connexion à phpMyAdmin réussie
- [ ] Schéma SQL importé (4 tables visibles)
- [ ] Application redéployée
- [ ] Logs vérifiés (aucune erreur rouge)
- [ ] Site accessible via l'URL Railway
- [ ] Pages /home, /galerie, /connexion fonctionnelles

---

## 🎓 Avantages de cette architecture

✅ **Tout dans le cloud** - pas besoin de XAMPP local
✅ **phpMyAdmin accessible partout** - gérez votre BDD depuis n'importe où
✅ **Gratuit** (dans les limites du plan gratuit Railway)
✅ **Auto-déploiement** - push Git = mise à jour automatique
✅ **Évolutif** - facile d'ajouter des services (Redis, etc.)

---

## 🆘 Besoin d'aide ?

Si vous rencontrez toujours "Application failed to respond" :

1. **Partagez les Deploy Logs** - copiez/collez les dernières lignes
2. **Vérifiez les Variables** - screenshot des variables d'environnement
3. **Testez en local d'abord** - `docker build -t test . && docker run -p 8080:80 test`

---

*Guide créé pour Railway.app - Novembre 2025*
