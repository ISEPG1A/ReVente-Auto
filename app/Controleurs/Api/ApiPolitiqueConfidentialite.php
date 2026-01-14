<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONTRÔLEUR POLITIQUE DE CONFIDENTIALITÉ - API REST
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Routes gérées :
 * - GET    /api/politique-confidentialite         : Liste toutes les sections
 * - POST   /api/politique-confidentialite         : Crée une section (admin)
 * - PUT    /api/politique-confidentialite/:id     : Modifie une section (admin)
 * - DELETE /api/politique-confidentialite/:id     : Supprime une section (admin)
 * - PUT    /api/politique-confidentialite/:id/ordre : Déplace une section (admin)
 */

$modele = new ModelePolitiqueConfidentialite();
$methode = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// ═══════════════════════════════════════════════════════════════════════════
// FONCTIONS UTILITAIRES
// ═══════════════════════════════════════════════════════════════════════════

function estAdminPC(): bool {
    if (!GestionnaireSession::estConnecte()) {
        return false;
    }
    $utilisateur = GestionnaireSession::obtenirUtilisateur();
    return isset($utilisateur['role']) && $utilisateur['role'] === 'admin';
}

function verifierAdminPC(): void {
    if (!estAdminPC()) {
        Utilitaires::envoyerJSON(['error' => 'Accès refusé. Droits administrateur requis.'], 403);
    }
}

function validerCSRFPC(array $donnees): bool {
    $token = $donnees['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    return GestionnaireSession::validerTokenCSRF($token ?? '');
}

function extraireRoutePC(string $uri): array {
    $uri = parse_url($uri, PHP_URL_PATH);
    
    // Route pour déplacer (ordre)
    if (preg_match('#/api/politique-confidentialite/(\d+)/ordre$#', $uri, $m)) {
        return ['action' => 'ordre', 'id' => (int)$m[1]];
    }
    // Route pour un item spécifique
    if (preg_match('#/api/politique-confidentialite/(\d+)$#', $uri, $m)) {
        return ['action' => 'item', 'id' => (int)$m[1]];
    }
    // Route liste/création
    if (preg_match('#/api/politique-confidentialite$#', $uri)) {
        return ['action' => 'collection', 'id' => null];
    }
    
    return ['action' => 'unknown', 'id' => null];
}

// ═══════════════════════════════════════════════════════════════════════════
// ROUTAGE PRINCIPAL
// ═══════════════════════════════════════════════════════════════════════════

$route = extraireRoutePC($uri);

switch ($methode) {
    // ═══════════════════════════════════════════════════════════════════════
    // GET - Lecture
    // ═══════════════════════════════════════════════════════════════════════
    case 'GET':
        if ($route['action'] === 'collection' || ($route['action'] === 'item' && $route['id'] === null)) {
            // Liste toutes les sections
            $isAdmin = estAdminPC();
            $sections = $modele->obtenirTout(!$isAdmin);
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'sections' => $sections,
                'isAdmin' => $isAdmin,
                'csrf_token' => $isAdmin ? GestionnaireSession::genererTokenCSRF() : null
            ]);
        } elseif ($route['action'] === 'item' && $route['id']) {
            // Une section spécifique
            $section = $modele->obtenirParId($route['id']);
            if ($section) {
                Utilitaires::envoyerJSON(['ok' => true, 'section' => $section]);
            } else {
                Utilitaires::envoyerJSON(['error' => 'Section non trouvée'], 404);
            }
        } else {
            Utilitaires::envoyerJSON(['error' => 'Route non trouvée'], 404);
        }
        break;
        
    // ═══════════════════════════════════════════════════════════════════════
    // POST - Création
    // ═══════════════════════════════════════════════════════════════════════
    case 'POST':
        verifierAdminPC();
        
        $donnees = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (!validerCSRFPC($donnees)) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
        }
        
        // Validation
        if (empty($donnees['titre']) || empty($donnees['contenu'])) {
            Utilitaires::envoyerJSON(['error' => 'Le titre et le contenu sont requis'], 400);
        }
        
        $id = $modele->creer([
            'titre' => trim($donnees['titre']),
            'contenu' => trim($donnees['contenu']),
            'statut' => $donnees['statut'] ?? 'publie'
        ]);
        
        if ($id) {
            // Log création section Politique
            $modeleAdmin = new ModeleAdmin();
            $adminId = $_SESSION['user']['id'] ?? null;
            $modeleAdmin->ajouterLog('politique', 'Section Politique Confidentialité créée', [
                'section_id' => $id,
                'titre' => trim($donnees['titre']),
                'statut' => $donnees['statut'] ?? 'publie'
            ], null, null, $adminId);
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'message' => 'Section créée avec succès',
                'id' => $id
            ]);
        } else {
            Utilitaires::envoyerJSON(['error' => 'Erreur lors de la création'], 500);
        }
        break;
        
    // ═══════════════════════════════════════════════════════════════════════
    // PUT - Modification
    // ═══════════════════════════════════════════════════════════════════════
    case 'PUT':
        verifierAdminPC();
        
        $donnees = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (!validerCSRFPC($donnees)) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
        }
        
        if (!$route['id']) {
            Utilitaires::envoyerJSON(['error' => 'ID requis'], 400);
        }
        
        // Déplacement
        if ($route['action'] === 'ordre') {
            $direction = $donnees['direction'] ?? null;
            if (!in_array($direction, ['monter', 'descendre'])) {
                Utilitaires::envoyerJSON(['error' => 'Direction invalide'], 400);
            }
            
            $success = $modele->deplacer($route['id'], $direction);
            if ($success) {
                Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Section déplacée']);
            } else {
                Utilitaires::envoyerJSON(['error' => 'Impossible de déplacer la section'], 400);
            }
            break;
        }
        
        // Modification standard
        $updates = [];
        if (isset($donnees['titre'])) {
            $updates['titre'] = trim($donnees['titre']);
        }
        if (isset($donnees['contenu'])) {
            $updates['contenu'] = trim($donnees['contenu']);
        }
        if (isset($donnees['statut'])) {
            $updates['statut'] = $donnees['statut'];
        }
        
        if (empty($updates)) {
            Utilitaires::envoyerJSON(['error' => 'Aucune modification fournie'], 400);
        }
        
        $success = $modele->modifier($route['id'], $updates);
        if ($success) {
            // Log modification section Politique
            $modeleAdmin = new ModeleAdmin();
            $adminId = $_SESSION['user']['id'] ?? null;
            $modeleAdmin->ajouterLog('politique', 'Section Politique Confidentialité modifiée', [
                'section_id' => $route['id'],
                'modifications' => array_keys($updates)
            ], null, null, $adminId);
            
            Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Section modifiée avec succès']);
        } else {
            Utilitaires::envoyerJSON(['error' => 'Erreur lors de la modification'], 500);
        }
        break;
        
    // ═══════════════════════════════════════════════════════════════════════
    // DELETE - Suppression
    // ═══════════════════════════════════════════════════════════════════════
    case 'DELETE':
        verifierAdminPC();
        
        $donnees = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (!validerCSRFPC($donnees)) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
        }
        
        if (!$route['id']) {
            Utilitaires::envoyerJSON(['error' => 'ID requis'], 400);
        }
        
        // Récupérer info avant suppression pour le log
        $sectionInfo = $modele->obtenirParId($route['id']);
        
        $success = $modele->supprimer($route['id']);
        if ($success) {
            // Log suppression section Politique
            $modeleAdmin = new ModeleAdmin();
            $adminId = $_SESSION['user']['id'] ?? null;
            $modeleAdmin->ajouterLog('politique', 'Section Politique Confidentialité supprimée', [
                'section_id' => $route['id'],
                'titre' => $sectionInfo['titre'] ?? 'N/A'
            ], null, null, $adminId);
            
            Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Section supprimée avec succès']);
        } else {
            Utilitaires::envoyerJSON(['error' => 'Erreur lors de la suppression'], 500);
        }
        break;
        
    default:
        Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
}
