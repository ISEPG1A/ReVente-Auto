# 📋 INSTALLATION MODULE FAQ ADMIN - INSTRUCTIONS

## ✅ Fichiers créés

1. ✅ `database/faq_migration.sql` - Schéma SQL pour la table FAQ et rôle admin
2. ✅ `database/creer_admin.php` - Script de création du compte admin
3. ✅ `app/FAQ/ModeleFAQ.php` - Modèle pour accéder aux données FAQ
4. ✅ `app/FAQ/ControleurFAQ.php` - Contrôleur API REST pour la FAQ
5. ✅ `views/pages/admin_faq.php` - Interface d'administration FAQ
6. ✅ `public/assets/css/pages/admin_faq.css` - Styles de l'interface admin
7. ✅ `public/assets/js/Admin/VueAdminFAQ.js` - JavaScript modulaire pour l'admin
8. ✅ `public/index.php` - Routes ajoutées automatiquement

## 🚀 ÉTAPES D'INSTALLATION

### 1️⃣ Créer la table FAQ et ajouter le rôle admin

Exécutez le fichier SQL dans phpMyAdmin ou via MySQL :

```bash
mysql -u root -p revente_auto < database/faq_migration.sql
```

Ou allez sur phpMyAdmin → Base `revente_auto` → SQL → Copier/coller le contenu de `faq_migration.sql`

### 2️⃣ Créer le compte administrateur
automatiquement :
- ✅ Ajouter la colonne `role` à la table `users` si elle n'existe pas
- ✅ Créer le compte admin avec l'email `antoine.perez@eleve.isep.fr`
- ✅ Définir le mot de passe : `Admin123!`
- ✅ Attribuer le rôle `admin`
- ✅ Générer les clés RSA pour la messagerie

**⚠️ IMPORTANT** : Les routes ont déjà été ajoutées automatiquement dans `index.php` !

### 3️⃣ Se connecter avec le compte admin

Allez sur : **http://localhost/PROJET/ReVente-Auto/connexion**

Connectez-vous avec :
- **Email** : `antoine.perez@eleve.isep.fr`
- **Mot de passe** : `Admin123!`

### 4️⃣ (Optionnel) Ajouter le lien dans le menu

Si vous voulez un lien direct dans le menu, modifiez `views/partials/navigation.php`
### 4️⃣ Ajouter le lien dans le menu admin

Dans `views/partials/navigation.php`, ajoutez un lien "Admin FAQ" dans le menu déroulant de l'utilisateur connecté (si admin) :

```php
<?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
    <li>
        <a href="/admin/faq">
            🔧 Admin FAQ
        </a>
    </li>
<?php endif; ?>
```

### 5️⃣ Accéder à l'administration FAQ

Une fois connecté, allez sur : **http://localhost/PROJET/ReVente-Auto/admin/faq**

Vous verrez l'interface d'administration avec les 8 questions par défaut. Vous pouvez :
- ➕ **Créer** une nouvelle question
- ✏️ **Modifier** une question existante  
- 👁️ **Activer/Désactiver** une question
- 🗑️ **Supprimer** une question
- 🔢 **Changer l'ordre** d'affichage

## 🔐 Sécurité

- ✅ Seuls les utilisateurs avec `role = 'admin'` peuvent accéder à l'API d'administration
- ✅ Protection CSRF sur toutes les requêtes POST/PUT/DELETE
- ✅ Validation des données côté serveur
- ✅ Les utilisateurs normaux ne voient que les questions actives

## 📝 Routes API disponibles

| Méthode | Route | Description | Admin requis |
|---------|-------|-------------|--------------|
| GET | `/api/faq` | Liste toutes les questions | Non* |
| GET | `/api/faq?id=X` | Détail d'une question | Non* |
| GET | `/api/faq?action=categories` | Liste des catégories | Non |
| POST | `/api/faq?action=create` | Créer une question | Oui |
| PUT | `/api/faq?action=update` | Modifier une question | Oui |
| PUT | `/api/faq?action=toggle` | Activer/Désactiver | Oui |
| PUT | `/api/faq?action=reorder` | Changer l'ordre | Oui |
| DELETE | `/api/faq?id=X` | Supprimer une question | Oui |

*Les non-admins ne voient que les questions actives

## ⚠️ SÉCURITÉ - APRÈS INSTALLATION

1. 🔐 **Changez le mot de passe** après la première connexion (via Paramètres)
2. 🗑️ **Supprimez le fichier** `database/creer_admin.php` pour la sécurité
3. ✅ **Testez toutes les fonctionnalités** de l'interface admin

## 🎯 RÉSUMÉ RAPIDE

```bash
# 1. Créer le compte admin
http://localhost/PROJET/ReVente-Auto/database/creer_admin.php

# 2. Se connecter
http://localhost/PROJET/ReVente-Auto/connexion
Email: antoine.perez@eleve.isep.fr
MDP: Admin123!

# 3. Accéder à l'admin FAQ
http://localhost/PROJET/ReVente-Auto/admin/faq
```

---

**✅ Votre système d'administration FAQ est maintenant opérationnel !** 🎉
