# 🔧 Dépannage et résolution de problèmes

Ce document recense les problèmes courants et leurs solutions.

## 🐛 Problème : URLs affichant le chemin physique du serveur

### Symptômes
- URL affichée : `votre-app.up.railway.app/var/www/html/connexion`
- Au lieu de : `votre-app.up.railway.app/connexion`
- Page 404 s'affichant de manière étrange
- Navigation entre les pages ne fonctionnant pas

### Cause
Configuration Apache incorrecte dans le Dockerfile ou utilisation de chemins relatifs dans les liens.

### Solution
✅ **Déjà corrigé dans ce projet !**

Les corrections appliquées :
1. Configuration Apache correcte avec DocumentRoot pointant vers `/var/www/html/public`
2. Tous les liens utilisent des chemins absolus (`/home` au lieu de `./home`)
3. Fichiers `.htaccess` correctement configurés

## 🗄️ Problème : Erreur de connexion à la base de données

### Symptômes
```
RuntimeException: Impossible de se connecter à la base de données
```

### Solutions possibles

#### 1. Vérifier les variables d'environnement (Railway)
```bash
# Les variables doivent être configurées :
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_NAME=${{MySQL.MYSQLDATABASE}}
DB_USER=${{MySQL.MYSQLUSER}}
DB_PASS=${{MySQL.MYSQLPASSWORD}}
```

#### 2. Vérifier que le service MySQL est démarré
- Dans Railway, vérifiez que le service MySQL a le statut "Running"
- Attendez quelques secondes après le démarrage

#### 3. Importer le schéma de base de données
Si les tables n'existent pas, utilisez le script PowerShell :
```powershell
.\import-db-railway.ps1
```

Ou connectez-vous manuellement avec un client MySQL et exécutez `database/schema.sql`.

## 📦 Problème : CSS/JavaScript ne se charge pas

### Symptômes
- Page sans style
- Fonctionnalités JavaScript ne marchent pas
- Erreurs 404 dans la console du navigateur

### Solutions

#### 1. Vérifier les chemins dans le code
Tous les assets doivent utiliser des chemins absolus :
```html
✅ <link rel="stylesheet" href="/assets/css/style.css" />
❌ <link rel="stylesheet" href="./assets/css/style.css" />
```

#### 2. Vérifier la configuration Apache
Le Dockerfile doit inclure :
```dockerfile
RUN sed -ri -e 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!g' ...
```

#### 3. Vérifier le fichier .htaccess
Le fichier `public/.htaccess` doit permettre l'accès aux assets :
```apache
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule . - [L]
```

## 🔐 Problème : Session utilisateur ne persiste pas

### Symptômes
- Déconnexion automatique après chaque rechargement
- Messages "non connecté" même après connexion

### Cause
Sessions PHP non configurées correctement ou cookies bloqués.

### Solution

#### 1. Vérifier la configuration des sessions
Dans `api/auth.php`, assurez-vous que :
```php
session_start();
```
est appelé au début du script.

#### 2. Vérifier les cookies
- Les cookies doivent être autorisés dans le navigateur
- En production HTTPS, utilisez `secure` et `samesite` :
```php
session_set_cookie_params([
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
```

## 📁 Problème : Upload d'images ne fonctionne pas

### Symptômes
- Erreur lors de l'upload d'une photo de profil
- Images ne s'affichent pas après upload

### Solutions

#### 1. Vérifier les permissions
Le dossier `uploads/` doit être accessible en écriture :
```dockerfile
RUN chown -R www-data:www-data /var/www/html
```

#### 2. Volume persistant (Railway)
⚠️ Railway utilise des conteneurs éphémères. Les fichiers uploadés seront perdus au redéploiement.

**Solution recommandée** : Utiliser un service de stockage externe :
- AWS S3
- Cloudinary
- DigitalOcean Spaces

#### 3. Limites PHP
Vérifiez les limites dans le Dockerfile :
```dockerfile
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini
```

## 🚀 Problème : Déploiement échoue sur Railway

### Symptômes
- Build failed
- Container crashed
- Application ne démarre pas

### Solutions

#### 1. Vérifier les logs
```bash
railway logs
```

#### 2. Erreurs courantes

**"exec format error"**
- Le Dockerfile utilise une mauvaise image de base
- Solution : Utiliser `FROM php:8.2-apache`

**"Port already in use"**
- Railway essaie d'utiliser un port occupé
- Solution : Laisser Apache utiliser le port 80 par défaut

**"Cannot connect to database"**
- Les variables d'environnement ne sont pas configurées
- Solution : Voir section "Erreur de connexion BDD" ci-dessus

## 🔄 Problème : Les changements ne s'appliquent pas

### Symptômes
- Après un `git push`, rien ne change sur Railway
- L'ancienne version est toujours en ligne

### Solutions

#### 1. Forcer un redéploiement
Dans Railway :
- Allez dans "Deployments"
- Cliquez sur les 3 points → "Redeploy"

#### 2. Vérifier le cache du navigateur
```
Ctrl + F5 (Windows)
Cmd + Shift + R (Mac)
```

#### 3. Vérifier que les changements sont pushés
```bash
git status
git log --oneline -5
```

## 📞 Besoin d'aide supplémentaire ?

1. **Consultez les logs** : Railway → Deployments → View Logs
2. **Vérifiez la documentation** : `docs/DEPLOY_RAILWAY.md`
3. **Testez localement avec Docker** :
   ```bash
   docker build -t revente-auto .
   docker run -p 80:80 revente-auto
   ```

## 📚 Ressources utiles

- [Documentation Railway](https://docs.railway.app)
- [Documentation Apache](https://httpd.apache.org/docs/2.4/)
- [Documentation PHP](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
