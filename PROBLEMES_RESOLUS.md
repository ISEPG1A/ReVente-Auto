# 🔧 Problèmes résolus

## Problème : URL et routing cassés sur Railway

### 🐛 Symptômes observés

1. **URL affichant le chemin physique du serveur** : 
   - URL affichée : `revente-auto-production.up.railway.app/var/www/html/connexion`
   - Au lieu de : `revente-auto-production.up.railway.app/connexion`

2. **Page 404 s'affichant de manière étrange**

3. **Navigation entre les pages ne fonctionnant pas correctement**

### 🔍 Causes identifiées

#### 1. Configuration Apache incorrecte dans le Dockerfile

**Problème** : La variable d'environnement `${APACHE_DOCUMENT_ROOT}` n'était pas correctement interprétée par `sed` dans le Dockerfile.

```dockerfile
# ❌ Avant (incorrect)
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
```

**Solution** : Utiliser des chemins en dur au lieu de variables d'environnement dans `sed`.

```dockerfile
# ✅ Après (correct)
RUN sed -ri -e 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!<Directory /var/www/>!<Directory /var/www/html/public/>!g' /etc/apache2/apache2.conf
```

#### 2. Liens relatifs dans la navigation et les pages

**Problème** : Tous les liens utilisaient des chemins relatifs (`./home`, `./galerie`, etc.) qui ne fonctionnent pas correctement avec le routing Apache.

```html
<!-- ❌ Avant (incorrect) -->
<a href="./home">Accueil</a>
<a href="./connexion">Connexion</a>
```

**Solution** : Utiliser des chemins absolus depuis la racine.

```html
<!-- ✅ Après (correct) -->
<a href="/home">Accueil</a>
<a href="/connexion">Connexion</a>
```

### ✅ Fichiers modifiés

1. **`Dockerfile`**
   - Correction de la configuration Apache DocumentRoot
   - Ajout de `Options Indexes FollowSymLinks`
   - Configuration correcte des répertoires

2. **`views/partials/nav.php`**
   - Conversion de tous les liens `./` vers `/`

3. **`views/layouts/main.php`**
   - CSS : `./assets/css/style.css` → `/assets/css/style.css`
   - JS : `./assets/js/*.js` → `/assets/js/*.js`

4. **`views/pages/404.php`**
   - Liens vers home et galerie corrigés

5. **`views/pages/about.php`**
   - Lien vers galerie corrigé

6. **`views/pages/home.php`**
   - Liens vers galerie et apropos corrigés

7. **`views/pages/settings.php`**
   - Lien vers connexion corrigé

### 🚀 Résultat

Après redéploiement sur Railway :

- ✅ URLs propres : `/home`, `/galerie`, `/connexion`, etc.
- ✅ Navigation fonctionnelle entre toutes les pages
- ✅ CSS et JavaScript chargés correctement
- ✅ Page 404 s'affiche correctement
- ✅ Règles de réécriture `.htaccess` fonctionnent

### 📋 Checklist pour vérifier le bon fonctionnement

Après le déploiement, testez :

- [ ] Page d'accueil : `https://votre-app.up.railway.app/`
- [ ] Navigation : `/home`, `/galerie`, `/apropos`, `/connexion`
- [ ] URL propres sans `/var/www/html/` visible
- [ ] CSS chargé (inspectez la page, onglet Network)
- [ ] JavaScript fonctionnel (menu mobile, authentification)
- [ ] Page 404 pour URL inexistantes

### 💡 Bonnes pratiques appliquées

1. **Chemins absolus depuis la racine** : `/page` au lieu de `./page`
2. **Configuration Apache explicite** : Pas de variables d'environnement dans `sed`
3. **DocumentRoot correct** : Pointe vers `/var/www/html/public`
4. **AllowOverride All** : Permet aux `.htaccess` de fonctionner
5. **Options FollowSymLinks** : Active le suivi des liens symboliques

### 🔗 Références

- [Apache DocumentRoot](https://httpd.apache.org/docs/2.4/mod/core.html#documentroot)
- [mod_rewrite](https://httpd.apache.org/docs/2.4/mod/mod_rewrite.html)
- [Railway Dockerfile](https://docs.railway.app/deploy/dockerfiles)
