# 🚀 Démarrage Rapide - ReVente-Auto sur Railway

## 🎯 Objectif : TOUT héberger sur Railway (cloud)

✅ Base de données MySQL  
✅ phpMyAdmin (gestion BDD)  
✅ Application PHP ReVente-Auto

**❌ Pas besoin de XAMPP ou autre outil local !**

---

## ⚡ 4 Étapes simples

### 1️⃣ Ajouter MySQL

1. Ouvrez votre projet Railway : https://railway.app
2. Cliquez `+ New` → `Database` → `Add MySQL`
3. Attendez que MySQL démarre (indicateur vert)

### 2️⃣ Lier l'application à MySQL

1. Cliquez sur votre service **Web** (ReVente-Auto)
2. Onglet **Variables** → Cliquez `+ New Variable`
3. Ajoutez ces variables (Railway les connecte automatiquement) :

```
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_NAME=${{MySQL.MYSQLDATABASE}}
DB_USER=${{MySQL.MYSQLUSER}}
DB_PASS=${{MySQL.MYSQLPASSWORD}}
```

### 3️⃣ Déployer phpMyAdmin

1. Dans le projet, cliquez `+ New` → `Empty Service`
2. Nommez-le **phpMyAdmin**
3. Onglet **Settings** → **Source** → Sélectionnez `Docker Image`
4. Entrez : `phpmyadmin/phpmyadmin:latest`
5. Onglet **Variables** → Ajoutez :

```
PMA_HOST=${{MySQL.MYSQLHOST}}
PMA_PORT=${{MySQL.MYSQLPORT}}
```

6. Onglet **Settings** → **Networking** → Cliquez `Generate Domain`
7. Copiez l'URL générée (ex: `phpmyadmin-production-xxxx.up.railway.app`)

### 4️⃣ Importer la base de données

1. Ouvrez l'URL phpMyAdmin
2. Connectez-vous :
   - **Utilisateur** : Copiez `MYSQLUSER` depuis Railway (onglet Variables du service MySQL)
   - **Mot de passe** : Copiez `MYSQLPASSWORD`
3. Sélectionnez la base **railway** (à gauche)
4. Onglet **Importer** → Parcourir → Sélectionnez `database/schema.sql`
5. Cliquez **Exécuter**

✅ **C'EST FAIT !** Votre application est maintenant connectée à la base de données.

---

## 🔍 Vérifier que tout fonctionne

### Tester phpMyAdmin
- Ouvrez l'URL phpMyAdmin
- Vérifiez que vous voyez 4 tables : `users`, `vehicles`, `password_resets`, `email_verifications`

### Tester l'application
1. Cliquez sur le service **Web** dans Railway
2. Copiez l'URL générée (ex: `revente-auto-production-xxxx.up.railway.app`)
3. Ouvrez cette URL dans votre navigateur
4. Vous devriez voir la page d'accueil

---

## ❌ Problème : "Application failed to respond"

Si vous voyez cette erreur :

### Solution 1 : Vérifier les logs
1. Service **Web** → Onglet **Deployments**
2. Cliquez sur le dernier déploiement
3. Lisez les **Deploy Logs** (logs rouges = erreurs)

### Solution 2 : Redéployer
1. Dans le projet Git, faites une petite modification
2. Commitez et pushez :

```bash
cd ReVente-Auto
git add .
git commit -m "Fix deployment"
git push
```

Railway va automatiquement redéployer.

### Solution 3 : Vérifier les variables d'environnement
1. Service **Web** → Onglet **Variables**
2. Vérifiez que les 5 variables DB_* sont présentes
3. Si manquantes, ajoutez-les (voir étape 2)

---

## 📚 Documentation complète

Pour plus de détails, consultez : [docs/DEPLOIEMENT_COMPLET_RAILWAY.md](docs/DEPLOIEMENT_COMPLET_RAILWAY.md)

---

## 🆘 Besoin d'aide ?

Problèmes courants :

| Problème | Solution |
|----------|----------|
| Page blanche | Vérifiez les Deploy Logs pour voir les erreurs PHP |
| Erreur 404 | Vérifiez que le `.htaccess` est présent dans `public/` |
| Erreur connexion BDD | Vérifiez les variables DB_* dans Railway |
| phpMyAdmin refuse la connexion | Vérifiez PMA_HOST et PMA_PORT |

---

*Guide créé pour Railway.app - Novembre 2025*
