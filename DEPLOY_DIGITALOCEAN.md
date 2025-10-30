# Déploiement sur DigitalOcean App Platform (PHP + Apache)

Ce dépôt contient un `Dockerfile` permettant à App Platform de détecter et exécuter votre application PHP dont le DocumentRoot est `public/`.

## Étapes côté DigitalOcean

1. Poussez ce dossier (qui contient `Dockerfile`) sur GitHub/GitLab/Bitbucket.
2. Créez un **App** dans App Platform et connectez le dépôt.
3. Si votre projet n'est pas à la racine du repo, définissez le **Source directory** sur `ultra`.
4. Pour le composant Web:
   - **Environment**: Dockerfile
   - **HTTP Port**: 80 (ou laissez 8080 et adaptez Apache si souhaité)
   - **Build & Run**: laissez par défaut (le Dockerfile gère tout)
5. Base de données:
   - Ajoutez un Managed MySQL (recommandé) ou renseignez les variables d'environnement pour une base externe.
   - Définissez les variables d'env attendues par votre code PHP (exemples):
     - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
6. Domaine & SSL:
   - Ajoutez votre domaine dans l'onglet **Domains** de l'application.
   - App Platform provisionne automatiquement le certificat Let's Encrypt.

## Notes
- Le `Dockerfile` active `mod_rewrite` et `AllowOverride All` pour que vos `.htaccess` fonctionnent.
- Si vous avez un fichier SQL (`database/schema.sql`), importez-le dans votre base (Managed DB: via le terminal ou un client MySQL), ou ajoutez une étape CI/CD séparée.
- Si votre app nécessite des extensions PHP spécifiques, ajoutez-les dans la ligne `docker-php-ext-install`.
