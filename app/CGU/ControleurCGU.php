<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONTRÔLEUR CGU - API REST POUR LA GESTION DES ARTICLES CGU
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce contrôleur expose une API REST pour gérer les articles CGU.
 * Il traite les requêtes HTTP (GET, POST, PUT, DELETE) et applique
 * les règles de sécurité appropriées.
 * 
 * Routes gérées :
 * - GET    /api/cgu          : Liste tous les articles (publics ou tous si admin)
 * - GET    /api/cgu/:id      : Récupère un article spécifique
 * - POST   /api/cgu          : Crée un nouvel article (admin uniquement)
 * - PUT    /api/cgu/:id      : Modifie un article existant (admin uniquement)
 * - DELETE /api/cgu/:id      : Supprime un article (admin uniquement)
 * 
 * Sécurité :
 * - Vérification du rôle admin pour les opérations de modification
 * - Protection CSRF pour POST/PUT/DELETE
 * - Validation des données d'entrée
 * - Rate limiting (via GestionnaireLimiteTaux)
 * 
 * @author  mat
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Instanciation du modèle CGU pour accéder aux données
$modeleCGU = new ModeleCGU();

// Instanciation du modèle utilisateur pour vérifier les permissions
$modeleUtilisateur = new ModeleUtilisateur();

// Récupération de la méthode HTTP de la requête (GET, POST, PUT, DELETE)
$methode = $_SERVER['REQUEST_METHOD'];

// Récupération de l'URI demandée pour extraire l'ID si présent
$uri = $_SERVER['REQUEST_URI'];

// ═══════════════════════════════════════════════════════════════════════════
// FONCTION UTILITAIRE : Vérifier si l'utilisateur est administrateur
// ═══════════════════════════════════════════════════════════════════════════
/**
 * Vérifie si l'utilisateur connecté a le rôle 'admin'
 * 
 * @return bool True si l'utilisateur est admin, false sinon
 */
function estAdmin(): bool {
    // Vérifie si une session utilisateur existe
    if (!isset($_SESSION['user'])) {
        return false;
    }
    
    // Vérifie si le rôle est 'admin' (stocké dans $_SESSION['user']['role'])
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Vérifie que l'utilisateur est admin, sinon retourne une erreur 403
 * 
 * @return void Termine le script si non autorisé
 */
function verifierAdmin(): void {
    if (!estAdmin()) {
        Utilitaires::envoyerJSON([
            'error' => 'Accès refusé. Cette action requiert les droits administrateur.'
        ], 403);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// FONCTION UTILITAIRE : Extraire l'ID de l'URI
// ═══════════════════════════════════════════════════════════════════════════
/**
 * Extrait l'ID d'un article depuis l'URI
 * 
 * Exemples :
 * - /api/cgu/5      → retourne 5
 * - /api/cgu        → retourne null
 * 
 * @param string $uri URI de la requête
 * @return int|null ID de l'article ou null si absent
 */
function extraireIdDepuisUri(string $uri): ?int {
    // Pattern regex pour capturer un nombre après /api/cgu/
    if (preg_match('#/api/cgu/(\d+)#', $uri, $matches)) {
        return (int) $matches[1]; // le 1 correspond au premier groupe capturé. 0 est la chaîne complète
    }
    return null;
}

// ═══════════════════════════════════════════════════════════════════════════
// ROUTAGE DES REQUÊTES
// ═══════════════════════════════════════════════════════════════════════════

switch ($methode) {
    
    // ─────────────────────────────────────────────────────────────────────
    // GET : Récupération des articles
    // ─────────────────────────────────────────────────────────────────────
    case 'GET':
        // Extraire l'ID si présent dans l'URI
        $id = extraireIdDepuisUri($uri);
        
        if ($id !== null) {
            // ─── GET /api/cgu/:id ───
            // Récupère un article spécifique
            
            $article = $modeleCGU->obtenirParId($id);
            
            if ($article) {
                // Si l'article est trouvé, le retourner
                Utilitaires::envoyerJSON([
                    'ok' => true,
                    'article' => $article
                ]);
            } else {
                // Si l'article n'existe pas
                Utilitaires::envoyerJSON([
                    'error' => 'Article CGU introuvable'
                ], 404);
            }
            
        } else {
            // ─── GET /api/cgu ───
            // Récupère tous les articles
            
            // Si l'utilisateur est admin, on récupère tous les articles (y compris brouillons)
            // Sinon, on ne récupère que les articles publiés
            $uniquementPublies = !estAdmin();
            
            $articles = $modeleCGU->obtenirTous($uniquementPublies);
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'articles' => $articles,
                'isAdmin' => estAdmin() // Indique au frontend si l'utilisateur est admin
            ]);
        }
        break;

    // ─────────────────────────────────────────────────────────────────────
    // POST : Création d'un nouvel article
    // ─────────────────────────────────────────────────────────────────────
    case 'POST':
        // Vérification : seuls les admins peuvent créer des articles
        verifierAdmin();
        
        // Lecture des données JSON envoyées
        $donnees = Utilitaires::lireCorpsJSON();
        
        // Protection CSRF : vérifier le token
        if (!GestionnaireSession::validerTokenCSRF($donnees['csrf_token'] ?? '')) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
        }
        
        // Rate limiting : limite le nombre de créations par IP
        if (!GestionnaireLimiteTaux::verifierTentative('default')) {
            Utilitaires::envoyerJSON(['error' => 'Trop de tentatives. Veuillez réessayer plus tard.'], 429);
        }
        GestionnaireLimiteTaux::ajouterTentative('default');
        
        // Validation des données requises
        if (empty($donnees['titre']) || empty($donnees['contenu'])) {
            Utilitaires::envoyerJSON([
                'error' => 'Le titre et le contenu sont obligatoires'
            ], 400);
        }
        
        // Si le numéro d'article n'est pas fourni, générer le prochain
        if (!isset($donnees['numero_article'])) {
            $donnees['numero_article'] = $modeleCGU->obtenirProchainNumero();
        }
        
        // Si l'ordre n'est pas fourni, utiliser le numéro d'article
        if (!isset($donnees['ordre'])) {
            $donnees['ordre'] = $donnees['numero_article'];
        }
        
        // Création de l'article en base de données
        $nouvelId = $modeleCGU->creer($donnees);
        
        if ($nouvelId) {
            // Succès : retourner l'article créé
            $articleCree = $modeleCGU->obtenirParId($nouvelId);
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'message' => 'Article créé avec succès',
                'article' => $articleCree
            ], 201); // Code 201 = Created
        } else {
            // Échec de la création
            Utilitaires::envoyerJSON([
                'error' => 'Échec de la création de l\'article'
            ], 500);
        }
        break;

    // ─────────────────────────────────────────────────────────────────────
    // PUT : Modification d'un article existant
    // ─────────────────────────────────────────────────────────────────────
    case 'PUT':
        // Vérification : seuls les admins peuvent modifier des articles
        verifierAdmin();
        
        // Extraction de l'ID depuis l'URI
        $id = extraireIdDepuisUri($uri);
        
        if ($id === null) {
            Utilitaires::envoyerJSON([
                'error' => 'ID de l\'article manquant dans l\'URL'
            ], 400);
        }
        
        // Lecture des données JSON
        $donnees = Utilitaires::lireCorpsJSON();
        
        // Protection CSRF
        if (!GestionnaireSession::validerTokenCSRF($donnees['csrf_token'] ?? '')) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
        }
        
        // Vérifier que l'article existe
        $articleExistant = $modeleCGU->obtenirParId($id);
        if (!$articleExistant) {
            Utilitaires::envoyerJSON([
                'error' => 'Article introuvable'
            ], 404);
        }
        
        // Validation : au moins un champ à modifier
        $champsModifiables = ['numero_article', 'titre', 'contenu', 'ordre', 'statut', 'visible'];
        $donneesAModifier = array_intersect_key($donnees, array_flip($champsModifiables));
        
        if (empty($donneesAModifier)) {
            Utilitaires::envoyerJSON([
                'error' => 'Aucune donnée valide à modifier'
            ], 400);
        }
        
        // Modification de l'article
        $succes = $modeleCGU->modifier($id, $donneesAModifier);
        
        if ($succes) {
            // Récupérer l'article modifié pour le retourner
            $articleModifie = $modeleCGU->obtenirParId($id);
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'message' => 'Article modifié avec succès',
                'article' => $articleModifie
            ]);
        } else {
            Utilitaires::envoyerJSON([
                'error' => 'Échec de la modification de l\'article'
            ], 500);
        }
        break;

    // ─────────────────────────────────────────────────────────────────────
    // DELETE : Suppression d'un article
    // ─────────────────────────────────────────────────────────────────────
    case 'DELETE':
        // Vérification : seuls les admins peuvent supprimer des articles
        verifierAdmin();
        
        // Extraction de l'ID depuis l'URI
        $id = extraireIdDepuisUri($uri);
        
        if ($id === null) {
            Utilitaires::envoyerJSON([
                'error' => 'ID de l\'article manquant dans l\'URL'
            ], 400);
        }
        
        // Lecture des données pour vérifier le token CSRF
        $donnees = Utilitaires::lireCorpsJSON();
        
        // Protection CSRF
        if (!GestionnaireSession::validerTokenCSRF($donnees['csrf_token'] ?? '')) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
        }
        
        // Vérifier que l'article existe
        $articleExistant = $modeleCGU->obtenirParId($id);
        if (!$articleExistant) {
            Utilitaires::envoyerJSON([
                'error' => 'Article introuvable'
            ], 404);
        }
        
        // Suppression de l'article
        $succes = $modeleCGU->supprimer($id);
        
        if ($succes) {
            Utilitaires::envoyerJSON([
                'ok' => true,
                'message' => 'Article supprimé avec succès'
            ]);
        } else {
            Utilitaires::envoyerJSON([
                'error' => 'Échec de la suppression de l\'article'
            ], 500);
        }
        break;

    // ─────────────────────────────────────────────────────────────────────
    // Méthode HTTP non supportée
    // ─────────────────────────────────────────────────────────────────────
    default:
        Utilitaires::envoyerJSON([
            'error' => 'Méthode HTTP non autorisée'
        ], 405);
        break;
}
