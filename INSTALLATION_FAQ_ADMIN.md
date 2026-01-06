# 📋 INSTALLATION MODULE FAQ ADMIN - INSTRUCTIONS

## ✅ Fichiers créés

1. ✅ `database/faq_migration.sql` - Schéma SQL pour la table FAQ et rôle admin
2. ✅ `database/creer_admin.php` - Script de création du compte admin
3. ✅ `app/FAQ/ModeleFAQ.php` - Modèle pour accéder aux données FAQ
4. ✅ `app/FAQ/ControleurFAQ.php` - Contrôleur API REST pour la FAQ
5. ✅ `views/pages/admin_faq.php` - Interface d'administration FAQ

## 🚀 ÉTAPES D'INSTALLATION

### 1️⃣ Créer la table FAQ et ajouter le rôle admin

Exécutez le fichier SQL dans phpMyAdmin ou via MySQL :

```bash
mysql -u root -p revente_auto < database/faq_migration.sql
```

Ou allez sur phpMyAdmin → Base `revente_auto` → SQL → Copier/coller le contenu de `faq_migration.sql`

### 2️⃣ Créer le compte administrateur

Allez sur : **http://localhost/PROJET/ReVente-Auto/database/creer_admin.php**

Le script va :
- Créer le compte admin avec l'email `admin@revente-auto.fr`
- Mot de passe : `Admin123!`
- Ajouter la colonne `role` si elle n'existe pas

### 3️⃣ Ajouter les routes dans `public/index.php`

Ouvrez `public/index.php` et ajoutez :

#### A) Route API (dans la section $apiRoutes) :

```php
// API FAQ
$apiRoutes['/api/faq'] = __DIR__ . '/../app/FAQ/ControleurFAQ.php';
```

#### B) Route page admin (dans la section $tableRoutage) :

```php
// Administration FAQ (réservé aux admins)
$tableRoutage['/admin/faq'] = [
    'view' => 'admin_faq.php',
    'title' => 'Administration FAQ',
    'current' => 'admin_faq'
];
```

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

### 5️⃣ Tester l'installation

1. Connectez-vous avec : `admin@revente-auto.fr` / `Admin123!`
2. Allez sur : **http://localhost/PROJET/ReVente-Auto/admin/faq**
3. Vous devriez voir l'interface d'administration avec les 8 questions par défaut
4. Testez :
   - ➕ Créer une nouvelle question
   - ✏️ Modifier une question existante
   - 👁️ Activer/Désactiver une question
   - 🗑️ Supprimer une question

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

## ⚠️ APRÈS INSTALLATION

1. Changez le mot de passe admin après la première connexion
2. Supprimez le fichier `database/creer_admin.php` pour la sécurité
3. Testez toutes les fonctionnalités

---

Voilà ! Votre système d'administration FAQ est prêt ! 🎉
