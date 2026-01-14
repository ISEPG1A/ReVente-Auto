<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONTRÔLEUR FAQ - API REST POUR LA GESTION DE LA FAQ
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Routes gérées :
 * - GET    /api/faq                : Liste toutes les questions
 * - POST   /api/faq                : Crée une question (admin)
 * - PUT    /api/faq/:id            : Modifie une question (admin)
 * - DELETE /api/faq/:id            : Supprime une question (admin)
 * - PUT    /api/faq/:id/ordre      : Déplace une question (admin)
 */

$modeleFAQ = new ModeleFAQ();
$methode = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// ═══════════════════════════════════════════════════════════════════════════
// FONCTIONS UTILITAIRES
// ═══════════════════════════════════════════════════════════════════════════

function estAdminFAQ(): bool {
    if (!GestionnaireSession::estConnecte()) {
        return false;
    }
    $utilisateur = GestionnaireSession::obtenirUtilisateur();
    return isset($utilisateur['role']) && $utilisateur['role'] === 'admin';
}

function verifierAdminFAQ(): void {
    if (!estAdminFAQ()) {
        Utilitaires::envoyerJSON(['error' => 'Accès refusé. Droits administrateur requis.'], 403);
    }
}

function validerCSRFFAQ(array $donnees): bool {
    $token = $donnees['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    return GestionnaireSession::validerTokenCSRF($token ?? '');
}

function extraireRouteFAQ(string $uri): array {
    $uri = parse_url($uri, PHP_URL_PATH);
    
    // Route pour déplacer (ordre)
    if (preg_match('#/api/faq/(\d+)/ordre$#', $uri, $m)) {
        return ['action' => 'ordre', 'id' => (int)$m[1]];
    }
    // Route pour un item spécifique
    if (preg_match('#/api/faq/(\d+)$#', $uri, $m)) {
        return ['action' => 'item', 'id' => (int)$m[1]];
    }
    // Route liste/création
    if (preg_match('#/api/faq$#', $uri)) {
        return ['action' => 'collection', 'id' => null];
    }
    
    return ['action' => 'unknown', 'id' => null];
}

// ═══════════════════════════════════════════════════════════════════════════
// ROUTAGE PRINCIPAL
// ═══════════════════════════════════════════════════════════════════════════

$route = extraireRouteFAQ($uri);

switch ($methode) {
    // ═══════════════════════════════════════════════════════════════════════
    // GET - Lecture
    // ═══════════════════════════════════════════════════════════════════════
    case 'GET':
        if ($route['action'] === 'collection' || $route['action'] === 'item' && $route['id'] === null) {
            // Liste toutes les questions
            $isAdmin = estAdminFAQ();
            $questions = $modeleFAQ->obtenirTout(!$isAdmin);
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'questions' => $questions,
                'isAdmin' => $isAdmin,
                'csrf_token' => $isAdmin ? GestionnaireSession::genererTokenCSRF() : null
            ]);
        } elseif ($route['action'] === 'item' && $route['id']) {
            // Une question spécifique
            $question = $modeleFAQ->obtenirParId($route['id']);
            if ($question) {
                Utilitaires::envoyerJSON(['ok' => true, 'question' => $question]);
            } else {
                Utilitaires::envoyerJSON(['error' => 'Question non trouvée'], 404);
            }
        } else {
            Utilitaires::envoyerJSON(['error' => 'Route non trouvée'], 404);
        }
        break;
        
    // ═══════════════════════════════════════════════════════════════════════
    // POST - Création
    // ═══════════════════════════════════════════════════════════════════════
    case 'POST':
        verifierAdminFAQ();
        
        $donnees = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (!validerCSRFFAQ($donnees)) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
        }
        
        $question = trim($donnees['question'] ?? '');
        $reponse = trim($donnees['reponse'] ?? '');
        $statut = $donnees['statut'] ?? 'publie';
        
        if (empty($question)) {
            Utilitaires::envoyerJSON(['error' => 'La question est obligatoire'], 400);
        }
        if (empty($reponse)) {
            Utilitaires::envoyerJSON(['error' => 'La réponse est obligatoire'], 400);
        }
        
        $id = $modeleFAQ->creer($question, $reponse, $statut);
        
        if ($id) {
            // Log création question FAQ
            $modeleAdmin = new ModeleAdmin();
            $adminId = $_SESSION['user']['id'] ?? null;
            $modeleAdmin->ajouterLog('faq', 'Question FAQ créée', [
                'question_id' => $id,
                'question' => mb_substr($question, 0, 100),
                'statut' => $statut
            ], null, null, $adminId);
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'id' => $id,
                'message' => 'Question créée avec succès'
            ]);
        } else {
            Utilitaires::envoyerJSON(['error' => 'Erreur lors de la création'], 500);
        }
        break;
        
    // ═══════════════════════════════════════════════════════════════════════
    // PUT - Modification
    // ═══════════════════════════════════════════════════════════════════════
    case 'PUT':
        verifierAdminFAQ();
        
        $donnees = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (!validerCSRFFAQ($donnees)) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
        }
        
        // Déplacement (ordre)
        if ($route['action'] === 'ordre' && $route['id']) {
            $direction = $donnees['direction'] ?? '';
            
            if (!in_array($direction, ['monter', 'descendre'])) {
                Utilitaires::envoyerJSON(['error' => 'Direction invalide'], 400);
            }
            
            $resultat = $modeleFAQ->deplacer($route['id'], $direction);
            
            if ($resultat === true) {
                Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Question déplacée']);
            } elseif ($resultat === 'extreme') {
                Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Position extrême atteinte']);
            } else {
                Utilitaires::envoyerJSON(['error' => 'Erreur lors du déplacement'], 500);
            }
        }
        // Modification standard
        elseif ($route['action'] === 'item' && $route['id']) {
            $updateData = [];
            
            if (isset($donnees['question'])) {
                $updateData['question'] = trim($donnees['question']);
            }
            if (isset($donnees['reponse'])) {
                $updateData['reponse'] = trim($donnees['reponse']);
            }
            if (isset($donnees['statut'])) {
                $updateData['statut'] = $donnees['statut'];
            }
            
            if (empty($updateData)) {
                Utilitaires::envoyerJSON(['error' => 'Aucune donnée à modifier'], 400);
            }
            
            if ($modeleFAQ->modifier($route['id'], $updateData)) {
                // Log modification question FAQ
                $modeleAdmin = new ModeleAdmin();
                $adminId = $_SESSION['user']['id'] ?? null;
                $modeleAdmin->ajouterLog('faq', 'Question FAQ modifiée', [
                    'question_id' => $route['id'],
                    'modifications' => array_keys($updateData)
                ], null, null, $adminId);
                
                Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Question modifiée']);
            } else {
                Utilitaires::envoyerJSON(['error' => 'Erreur lors de la modification'], 500);
            }
        } else {
            Utilitaires::envoyerJSON(['error' => 'Route non trouvée'], 404);
        }
        break;
        
    // ═══════════════════════════════════════════════════════════════════════
    // DELETE - Suppression
    // ═══════════════════════════════════════════════════════════════════════
    case 'DELETE':
        verifierAdminFAQ();
        
        if ($route['action'] !== 'item' || !$route['id']) {
            Utilitaires::envoyerJSON(['error' => 'ID requis pour la suppression'], 400);
        }
        
        $donnees = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (!validerCSRFFAQ($donnees)) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
        }
        
        // Récupérer info avant suppression pour le log
        $questionInfo = $modeleFAQ->obtenirParId($route['id']);
        
        if ($modeleFAQ->supprimer($route['id'])) {
            // Log suppression question FAQ
            $modeleAdmin = new ModeleAdmin();
            $adminId = $_SESSION['user']['id'] ?? null;
            $modeleAdmin->ajouterLog('faq', 'Question FAQ supprimée', [
                'question_id' => $route['id'],
                'question' => mb_substr($questionInfo['question'] ?? 'N/A', 0, 100)
            ], null, null, $adminId);
            
            Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Question supprimée']);
        } else {
            Utilitaires::envoyerJSON(['error' => 'Erreur lors de la suppression'], 500);
        }
        break;
        
    default:
        Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
}
