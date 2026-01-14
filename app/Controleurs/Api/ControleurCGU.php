<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONTRÔLEUR CGU - API REST POUR LA GESTION DES CGU
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Routes gérées :
 * 
 * ARTICLES (niveau 1) :
 * - GET    /api/cgu                    : Liste tous les articles avec sections/points
 * - POST   /api/cgu/articles           : Crée un article
 * - PUT    /api/cgu/articles/:id       : Modifie un article
 * - DELETE /api/cgu/articles/:id       : Supprime un article
 * - PUT    /api/cgu/articles/reorder   : Réordonne les articles
 * 
 * SECTIONS (niveau 2) :
 * - POST   /api/cgu/sections           : Crée une section
 * - PUT    /api/cgu/sections/:id       : Modifie une section
 * - DELETE /api/cgu/sections/:id       : Supprime une section
 * - PUT    /api/cgu/sections/reorder   : Réordonne les sections
 * 
 * POINTS (niveau 3) :
 * - POST   /api/cgu/points             : Crée un point
 * - PUT    /api/cgu/points/:id         : Modifie un point
 * - DELETE /api/cgu/points/:id         : Supprime un point
 * - PUT    /api/cgu/points/reorder     : Réordonne les points
 */

$modeleCGU = new ModeleCGU();
$methode = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// ═══════════════════════════════════════════════════════════════════════════
// FONCTIONS UTILITAIRES
// ═══════════════════════════════════════════════════════════════════════════

function estAdminCGU(): bool {
    if (!GestionnaireSession::estConnecte()) {
        return false;
    }
    $utilisateur = GestionnaireSession::obtenirUtilisateur();
    return isset($utilisateur['role']) && $utilisateur['role'] === 'admin';
}

function verifierAdminCGU(): void {
    if (!estAdminCGU()) {
        Utilitaires::envoyerJSON(['error' => 'Accès refusé. Droits administrateur requis.'], 403);
    }
}

function validerCSRF(array $donnees): bool {
    $token = $donnees['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    return GestionnaireSession::validerTokenCSRF($token ?? '');
}

function extraireRoute(string $uri): array {
    // Nettoyer l'URI
    $uri = parse_url($uri, PHP_URL_PATH);
    
    // Patterns pour les différentes routes
    if (preg_match('#/api/cgu/articles/reorder$#', $uri)) {
        return ['type' => 'articles', 'action' => 'reorder', 'id' => null];
    }
    if (preg_match('#/api/cgu/sections/reorder$#', $uri)) {
        return ['type' => 'sections', 'action' => 'reorder', 'id' => null];
    }
    if (preg_match('#/api/cgu/points/reorder$#', $uri)) {
        return ['type' => 'points', 'action' => 'reorder', 'id' => null];
    }
    // Routes pour déplacer un élément (monter/descendre)
    if (preg_match('#/api/cgu/articles/(\d+)/ordre$#', $uri, $m)) {
        return ['type' => 'articles', 'action' => 'move', 'id' => (int)$m[1]];
    }
    if (preg_match('#/api/cgu/sections/(\d+)/ordre$#', $uri, $m)) {
        return ['type' => 'sections', 'action' => 'move', 'id' => (int)$m[1]];
    }
    if (preg_match('#/api/cgu/points/(\d+)/ordre$#', $uri, $m)) {
        return ['type' => 'points', 'action' => 'move', 'id' => (int)$m[1]];
    }
    if (preg_match('#/api/cgu/articles/(\d+)$#', $uri, $m)) {
        return ['type' => 'articles', 'action' => 'item', 'id' => (int)$m[1]];
    }
    if (preg_match('#/api/cgu/sections/(\d+)$#', $uri, $m)) {
        return ['type' => 'sections', 'action' => 'item', 'id' => (int)$m[1]];
    }
    if (preg_match('#/api/cgu/points/(\d+)$#', $uri, $m)) {
        return ['type' => 'points', 'action' => 'item', 'id' => (int)$m[1]];
    }
    if (preg_match('#/api/cgu/articles$#', $uri)) {
        return ['type' => 'articles', 'action' => 'collection', 'id' => null];
    }
    if (preg_match('#/api/cgu/sections$#', $uri)) {
        return ['type' => 'sections', 'action' => 'collection', 'id' => null];
    }
    if (preg_match('#/api/cgu/points$#', $uri)) {
        return ['type' => 'points', 'action' => 'collection', 'id' => null];
    }
    if (preg_match('#/api/cgu$#', $uri)) {
        return ['type' => 'all', 'action' => 'list', 'id' => null];
    }
    // Routes pour les versions
    if (preg_match('#/api/cgu/versions$#', $uri)) {
        return ['type' => 'versions', 'action' => 'collection', 'id' => null];
    }
    if (preg_match('#/api/cgu/versions/check$#', $uri)) {
        return ['type' => 'versions', 'action' => 'check', 'id' => null];
    }
    if (preg_match('#/api/cgu/versions/(\d+)$#', $uri, $m)) {
        return ['type' => 'versions', 'action' => 'item', 'id' => (int)$m[1]];
    }
    
    return ['type' => null, 'action' => null, 'id' => null];
}

// ═══════════════════════════════════════════════════════════════════════════
// ROUTAGE
// ═══════════════════════════════════════════════════════════════════════════

$route = extraireRoute($uri);

switch ($methode) {
    
    // ─────────────────────────────────────────────────────────────────────
    // GET : Récupération des données
    // ─────────────────────────────────────────────────────────────────────
    case 'GET':
        if ($route['type'] === 'all') {
            // Liste complète des CGU
            $uniquementPublies = !estAdminCGU();
            $articles = $modeleCGU->obtenirTousArticles($uniquementPublies);
            $csrfToken = estAdminCGU() ? GestionnaireSession::genererTokenCSRF() : null;
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'articles' => $articles,
                'isAdmin' => estAdminCGU(),
                'csrfToken' => $csrfToken
            ]);
        } elseif ($route['type'] === 'versions') {
            // Liste des versions archivées
            $modeleVersion = new ModeleVersionCGU();
            $versions = $modeleVersion->obtenirToutesVersions();
            
            // Formater les tailles de fichiers
            foreach ($versions as &$v) {
                $v['taille_formatee'] = ModeleVersionCGU::formaterTaille($v['taille_fichier'] ?? 0);
                $v['date_formatee'] = (new DateTime($v['date_creation']))->format('d/m/Y à H:i');
            }
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'versions' => $versions
            ]);
        } else {
            Utilitaires::envoyerJSON(['error' => 'Route non trouvée'], 404);
        }
        break;

    // ─────────────────────────────────────────────────────────────────────
    // POST : Création
    // ─────────────────────────────────────────────────────────────────────
    case 'POST':
        verifierAdminCGU();
        $donnees = Utilitaires::lireCorpsJSON();
        
        if (!validerCSRF($donnees)) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
        }
        
        // Préparation pour les logs
        $modeleAdmin = new ModeleAdmin();
        $adminId = $_SESSION['user']['id'] ?? null;
        
        switch ($route['type']) {
            case 'articles':
                if (empty($donnees['titre'])) {
                    Utilitaires::envoyerJSON(['error' => 'Le titre est obligatoire'], 400);
                }
                $statut = $donnees['statut'] ?? 'publie';
                $id = $modeleCGU->creerArticle($donnees['titre'], $statut);
                
                if ($id) {
                    $article = $modeleCGU->obtenirArticleParId($id);
                    
                    // Log création article CGU
                    $modeleAdmin->ajouterLog('cgu', 'Article CGU créé', [
                        'article_id' => $id,
                        'titre' => $donnees['titre'],
                        'statut' => $statut
                    ], null, null, $adminId);
                    
                    Utilitaires::envoyerJSON(['ok' => true, 'article' => $article], 201);
                } else {
                    Utilitaires::envoyerJSON(['error' => 'Erreur lors de la création'], 500);
                }
                break;
                
            case 'sections':
                if (empty($donnees['article_id']) || empty($donnees['titre'])) {
                    Utilitaires::envoyerJSON(['error' => 'article_id et titre sont obligatoires'], 400);
                }
                $contenu = $donnees['contenu'] ?? null;
                $id = $modeleCGU->creerSection((int)$donnees['article_id'], $donnees['titre'], $contenu);
                
                if ($id) {
                    $section = $modeleCGU->obtenirSectionParId($id);
                    
                    // Log création section CGU
                    $modeleAdmin->ajouterLog('cgu', 'Section CGU créée', [
                        'section_id' => $id,
                        'article_id' => $donnees['article_id'],
                        'titre' => $donnees['titre']
                    ], null, null, $adminId);
                    
                    Utilitaires::envoyerJSON(['ok' => true, 'section' => $section], 201);
                } else {
                    Utilitaires::envoyerJSON(['error' => 'Erreur lors de la création'], 500);
                }
                break;
                
            case 'points':
                if (empty($donnees['section_id']) || empty($donnees['titre']) || empty($donnees['contenu'])) {
                    Utilitaires::envoyerJSON(['error' => 'section_id, titre et contenu sont obligatoires'], 400);
                }
                $id = $modeleCGU->creerPoint((int)$donnees['section_id'], $donnees['contenu'], $donnees['titre']);
                
                if ($id) {
                    $point = $modeleCGU->obtenirPointParId($id);
                    
                    // Log création point CGU
                    $modeleAdmin->ajouterLog('cgu', 'Point CGU créé', [
                        'point_id' => $id,
                        'section_id' => $donnees['section_id'],
                        'titre' => $donnees['titre']
                    ], null, null, $adminId);
                    
                    Utilitaires::envoyerJSON(['ok' => true, 'point' => $point], 201);
                } else {
                    Utilitaires::envoyerJSON(['error' => 'Erreur lors de la création'], 500);
                }
                break;
                
            default:
                Utilitaires::envoyerJSON(['error' => 'Route non trouvée'], 404);
        }
        break;

    // ─────────────────────────────────────────────────────────────────────
    // PUT : Modification
    // ─────────────────────────────────────────────────────────────────────
    case 'PUT':
        verifierAdminCGU();
        $donnees = Utilitaires::lireCorpsJSON();
        
        if (!validerCSRF($donnees)) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
        }
        
        // Préparation pour les logs
        $modeleAdmin = new ModeleAdmin();
        $adminId = $_SESSION['user']['id'] ?? null;
        
        // Déplacement simple (monter/descendre)
        if ($route['action'] === 'move' && $route['id']) {
            $direction = $donnees['direction'] ?? null;
            if (!in_array($direction, ['monter', 'descendre'])) {
                Utilitaires::envoyerJSON(['error' => 'Direction invalide (monter ou descendre)'], 400);
            }
            
            $resultat = null;
            
            switch ($route['type']) {
                case 'articles':
                    $resultat = $modeleCGU->deplacerArticle($route['id'], $direction);
                    break;
                case 'sections':
                    $resultat = $modeleCGU->deplacerSection($route['id'], $direction);
                    break;
                case 'points':
                    $resultat = $modeleCGU->deplacerPoint($route['id'], $direction);
                    break;
            }
            
            // Le modèle retourne: true = déplacé, false = erreur, 'extreme' = déjà en position extrême
            if ($resultat === true) {
                Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Ordre mis à jour']);
            } elseif ($resultat === 'extreme') {
                // Position extrême - pas une vraie erreur, juste ignorer silencieusement
                Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Déjà en position extrême']);
            } else {
                Utilitaires::envoyerJSON(['error' => 'Erreur lors du déplacement'], 500);
            }
            break;
        }
        
        // Réordonnancement
        if ($route['action'] === 'reorder') {
            if (empty($donnees['ordre']) || !is_array($donnees['ordre'])) {
                Utilitaires::envoyerJSON(['error' => 'ordre (tableau d\'IDs) est obligatoire'], 400);
            }
            
            $succes = false;
            switch ($route['type']) {
                case 'articles':
                    $succes = $modeleCGU->reordonnerArticles($donnees['ordre']);
                    break;
                case 'sections':
                    if (empty($donnees['article_id'])) {
                        Utilitaires::envoyerJSON(['error' => 'article_id est obligatoire'], 400);
                    }
                    $succes = $modeleCGU->reordonnerSections((int)$donnees['article_id'], $donnees['ordre']);
                    break;
                case 'points':
                    if (empty($donnees['section_id'])) {
                        Utilitaires::envoyerJSON(['error' => 'section_id est obligatoire'], 400);
                    }
                    $succes = $modeleCGU->reordonnerPoints((int)$donnees['section_id'], $donnees['ordre']);
                    break;
            }
            
            if ($succes) {
                Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Ordre mis à jour']);
            } else {
                Utilitaires::envoyerJSON(['error' => 'Erreur lors du réordonnancement'], 500);
            }
            break;
        }
        
        // Modification d'un élément
        if ($route['action'] === 'item' && $route['id']) {
            $succes = false;
            $element = null;
            
            switch ($route['type']) {
                case 'articles':
                    $succes = $modeleCGU->modifierArticle($route['id'], $donnees);
                    $element = $modeleCGU->obtenirArticleParId($route['id']);
                    
                    // Log modification article CGU
                    if ($succes) {
                        $modeleAdmin->ajouterLog('cgu', 'Article CGU modifié', [
                            'article_id' => $route['id'],
                            'modifications' => array_keys($donnees)
                        ], null, null, $adminId);
                    }
                    break;
                case 'sections':
                    $succes = $modeleCGU->modifierSection($route['id'], $donnees);
                    $element = $modeleCGU->obtenirSectionParId($route['id']);
                    
                    // Log modification section CGU
                    if ($succes) {
                        $modeleAdmin->ajouterLog('cgu', 'Section CGU modifiée', [
                            'section_id' => $route['id'],
                            'modifications' => array_keys($donnees)
                        ], null, null, $adminId);
                    }
                    break;
                case 'points':
                    // Valider que le titre n'est pas vide lors de la modification
                    if (isset($donnees['titre']) && trim($donnees['titre']) === '') {
                        Utilitaires::envoyerJSON(['error' => 'Le titre est obligatoire'], 400);
                    }
                    $succes = $modeleCGU->modifierPoint($route['id'], $donnees);
                    $element = $modeleCGU->obtenirPointParId($route['id']);
                    
                    // Log modification point CGU
                    if ($succes) {
                        $modeleAdmin->ajouterLog('cgu', 'Point CGU modifié', [
                            'point_id' => $route['id'],
                            'modifications' => array_keys($donnees)
                        ], null, null, $adminId);
                    }
                    break;
            }
            
            if ($succes) {
                Utilitaires::envoyerJSON(['ok' => true, $route['type'] => $element]);
            } else {
                Utilitaires::envoyerJSON(['error' => 'Erreur lors de la modification'], 500);
            }
            break;
        }
        
        Utilitaires::envoyerJSON(['error' => 'Route non trouvée'], 404);
        break;

    // ─────────────────────────────────────────────────────────────────────
    // DELETE : Suppression
    // ─────────────────────────────────────────────────────────────────────
    case 'DELETE':
        verifierAdminCGU();
        $donnees = Utilitaires::lireCorpsJSON();
        
        if (!validerCSRF($donnees)) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
        }
        
        if ($route['action'] !== 'item' || !$route['id']) {
            Utilitaires::envoyerJSON(['error' => 'ID manquant'], 400);
        }
        
        // Préparation pour les logs
        $modeleAdmin = new ModeleAdmin();
        $adminId = $_SESSION['user']['id'] ?? null;
        
        $succes = false;
        $elementType = $route['type'];
        
        switch ($route['type']) {
            case 'articles':
                // Récupérer info avant suppression pour le log
                $articleInfo = $modeleCGU->obtenirArticleParId($route['id']);
                $succes = $modeleCGU->supprimerArticle($route['id']);
                if ($succes && $articleInfo) {
                    $modeleAdmin->ajouterLog('cgu', 'Article CGU supprimé', [
                        'article_id' => $route['id'],
                        'titre' => $articleInfo['titre'] ?? 'N/A'
                    ], null, null, $adminId);
                }
                break;
            case 'sections':
                $sectionInfo = $modeleCGU->obtenirSectionParId($route['id']);
                $succes = $modeleCGU->supprimerSection($route['id']);
                if ($succes && $sectionInfo) {
                    $modeleAdmin->ajouterLog('cgu', 'Section CGU supprimée', [
                        'section_id' => $route['id'],
                        'titre' => $sectionInfo['titre'] ?? 'N/A'
                    ], null, null, $adminId);
                }
                break;
            case 'points':
                $pointInfo = $modeleCGU->obtenirPointParId($route['id']);
                $succes = $modeleCGU->supprimerPoint($route['id']);
                if ($succes && $pointInfo) {
                    $modeleAdmin->ajouterLog('cgu', 'Point CGU supprimé', [
                        'point_id' => $route['id'],
                        'titre' => $pointInfo['titre'] ?? 'N/A'
                    ], null, null, $adminId);
                }
                break;
            case 'versions':
                $modeleVersion = new ModeleVersionCGU();
                $succes = $modeleVersion->supprimerVersion($route['id']);
                if ($succes) {
                    $modeleAdmin->ajouterLog('cgu', 'Version CGU archivée supprimée', [
                        'version_id' => $route['id']
                    ], null, null, $adminId);
                }
                break;
        }
        
        if ($succes) {
            Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Élément supprimé']);
        } else {
            Utilitaires::envoyerJSON(['error' => 'Erreur lors de la suppression'], 500);
        }
        break;

    default:
        Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
}
