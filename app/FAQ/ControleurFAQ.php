ControleurFAQ

<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONTRÔLEUR FAQ - Gestion API des questions fréquentes
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * API REST pour la gestion administrative de la FAQ.
 * 
 * Routes disponibles :
 * - GET  /api/faq                      → Liste toutes les questions
 * - GET  /api/faq?action=categories    → Liste les catégories
 * - GET  /api/faq?id=X                 → Détail d'une question
 * - POST /api/faq?action=create        → Créer une question (ADMIN)
 * - PUT  /api/faq?action=update        → Modifier une question (ADMIN)
 * - DELETE /api/faq?id=X               → Supprimer une question (ADMIN)
 * - PUT  /api/faq?action=toggle        → Activer/Désactiver (ADMIN)
 * - PUT  /api/faq?action=reorder       → Changer l'ordre (ADMIN)
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../Commun/Utilitaires.php';
require_once __DIR__ . '/../Commun/GestionnaireSession.php';
require_once __DIR__ . '/../Commun/Securite.php';
require_once __DIR__ . '/ModeleFAQ.php';

// Démarrage de la session et ajout des en-têtes de sécurité
GestionnaireSession::demarrerSession();
Securite::ajouterEnTetes();

// Initialisation du modèle
$modeleFaq = new ModeleFAQ();

// Récupération de la méthode HTTP et de l'action
$methode = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

/**
 * Vérifie si l'utilisateur est un administrateur
 * 
 * @return bool True si l'utilisateur est admin
 */
function estAdministrateur(): bool
{
    if (!GestionnaireSession::estConnecte()) {
        return false;
    }

    $utilisateur = GestionnaireSession::obtenirUtilisateur();
    return isset($utilisateur['role']) && $utilisateur['role'] === 'admin';
}

/**
 * Envoie une erreur 403 si l'utilisateur n'est pas admin
 */
function verifierDroitsAdmin(): void
{
    if (!estAdministrateur()) {
        Utilitaires::envoyerJSON([
            'error' => 'Accès refusé. Droits administrateur requis.'
        ], 403);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ROUTE GET - Récupération des données
// ═══════════════════════════════════════════════════════════════════════════

if ($methode === 'GET') {
    
    // GET /api/faq?action=categories - Liste des catégories
    if ($action === 'categories') {
        try {
            $categories = $modeleFaq->obtenirCategories();
            Utilitaires::envoyerJSON([
                'ok' => true,
                'categories' => $categories
            ]);
        } catch (Exception $e) {
            Utilitaires::envoyerJSON([
                'error' => 'Erreur lors de la récupération des catégories'
            ], 500);
        }
    }
    
    // GET /api/faq?id=X - Détail d'une question
    elseif ($id !== null) {
        try {
            $question = $modeleFaq->obtenirParId($id);
            
            if ($question === null) {
                Utilitaires::envoyerJSON([
                    'error' => 'Question introuvable'
                ], 404);
            }

            // Si non admin et question inactive, refuser l'accès
            if (!$question['is_active'] && !estAdministrateur()) {
                Utilitaires::envoyerJSON([
                    'error' => 'Question introuvable'
                ], 404);
            }

            Utilitaires::envoyerJSON([
                'ok' => true,
                'faq' => $question
            ]);
        } catch (Exception $e) {
            Utilitaires::envoyerJSON([
                'error' => 'Erreur lors de la récupération de la question'
            ], 500);
        }
    }
    
    // GET /api/faq - Liste toutes les questions
    else {
        try {
            $categorie = $_GET['category'] ?? null;
            
            // Les non-admins ne voient que les questions actives
            $seulementActives = !estAdministrateur();
            
            $questions = $modeleFaq->obtenirTous($seulementActives, $categorie);
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'faqs' => $questions,
                'total' => count($questions)
            ]);
        } catch (Exception $e) {
            Utilitaires::envoyerJSON([
                'error' => 'Erreur lors de la récupération des questions'
            ], 500);
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ROUTE POST - Création d'une question (ADMIN uniquement)
// ═══════════════════════════════════════════════════════════════════════════

elseif ($methode === 'POST') {
    
    // Vérification des droits administrateur
    verifierDroitsAdmin();
    
    // Vérification du token CSRF
    if (!GestionnaireSession::validerTokenCSRF($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
    }

    // POST /api/faq?action=create - Créer une nouvelle question
    if ($action === 'create') {
        try {
            $donnees = Utilitaires::lireCorpsJSON();

            // Validation des données
            if (!Utilitaires::chaineValide($donnees['question'] ?? '', 10000)) {
                Utilitaires::envoyerJSON([
                    'error' => 'La question est requise et doit être valide'
                ], 400);
            }

            if (!Utilitaires::chaineValide($donnees['answer'] ?? '', 50000)) {
                Utilitaires::envoyerJSON([
                    'error' => 'La réponse est requise et doit être valide'
                ], 400);
            }

            $question = trim($donnees['question']);
            $reponse = trim($donnees['answer']);
            $categorie = trim($donnees['category'] ?? 'Général');
            $ordre = (int)($donnees['display_order'] ?? 0);
            $active = isset($donnees['is_active']) ? (bool)$donnees['is_active'] : true;

            // Création de la question
            $nouvelId = $modeleFaq->ajouter($question, $reponse, $categorie, $ordre, $active);

            // Récupération de la question créée
            $questionCreee = $modeleFaq->obtenirParId($nouvelId);

            Utilitaires::envoyerJSON([
                'ok' => true,
                'message' => 'Question créée avec succès',
                'faq' => $questionCreee
            ], 201);

        } catch (Exception $e) {
            Utilitaires::envoyerJSON([
                'error' => 'Erreur lors de la création de la question : ' . $e->getMessage()
            ], 500);
        }
    } else {
        Utilitaires::envoyerJSON(['error' => 'Action non reconnue'], 400);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ROUTE PUT - Modification d'une question (ADMIN uniquement)
// ═══════════════════════════════════════════════════════════════════════════

elseif ($methode === 'PUT') {
    
    // Vérification des droits administrateur
    verifierDroitsAdmin();
    
    // Vérification du token CSRF
    if (!GestionnaireSession::validerTokenCSRF($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
    }

    // PUT /api/faq?action=update - Modifier une question existante
    if ($action === 'update') {
        try {
            $donnees = Utilitaires::lireCorpsJSON();

            if (!isset($donnees['id']) || !is_numeric($donnees['id'])) {
                Utilitaires::envoyerJSON([
                    'error' => 'ID de la question requis'
                ], 400);
            }

            $idQuestion = (int)$donnees['id'];

            // Vérifier que la question existe
            $questionExistante = $modeleFaq->obtenirParId($idQuestion);
            if ($questionExistante === null) {
                Utilitaires::envoyerJSON([
                    'error' => 'Question introuvable'
                ], 404);
            }

            // Validation des données
            if (!Utilitaires::chaineValide($donnees['question'] ?? '', 10000)) {
                Utilitaires::envoyerJSON([
                    'error' => 'La question est requise et doit être valide'
                ], 400);
            }

            if (!Utilitaires::chaineValide($donnees['answer'] ?? '', 50000)) {
                Utilitaires::envoyerJSON([
                    'error' => 'La réponse est requise et doit être valide'
                ], 400);
            }

            $question = trim($donnees['question']);
            $reponse = trim($donnees['answer']);
            $categorie = trim($donnees['category'] ?? 'Général');
            $ordre = (int)($donnees['display_order'] ?? 0);
            $active = isset($donnees['is_active']) ? (bool)$donnees['is_active'] : true;

            // Modification de la question
            $succes = $modeleFaq->modifier($idQuestion, $question, $reponse, $categorie, $ordre, $active);

            if (!$succes) {
                Utilitaires::envoyerJSON([
                    'error' => 'Échec de la modification'
                ], 500);
            }

            // Récupération de la question modifiée
            $questionModifiee = $modeleFaq->obtenirParId($idQuestion);

            Utilitaires::envoyerJSON([
                'ok' => true,
                'message' => 'Question modifiée avec succès',
                'faq' => $questionModifiee
            ]);

        } catch (Exception $e) {
            Utilitaires::envoyerJSON([
                'error' => 'Erreur lors de la modification : ' . $e->getMessage()
            ], 500);
        }
    }
    
    // PUT /api/faq?action=toggle - Activer/Désactiver une question
    elseif ($action === 'toggle') {
        try {
            $donnees = Utilitaires::lireCorpsJSON();

            if (!isset($donnees['id']) || !is_numeric($donnees['id'])) {
                Utilitaires::envoyerJSON([
                    'error' => 'ID de la question requis'
                ], 400);
            }

            $idQuestion = (int)$donnees['id'];
            $active = isset($donnees['is_active']) ? (bool)$donnees['is_active'] : true;

            $succes = $modeleFaq->basculerActivation($idQuestion, $active);

            if (!$succes) {
                Utilitaires::envoyerJSON([
                    'error' => 'Question introuvable ou échec de la modification'
                ], 404);
            }

            Utilitaires::envoyerJSON([
                'ok' => true,
                'message' => $active ? 'Question activée' : 'Question désactivée'
            ]);

        } catch (Exception $e) {
            Utilitaires::envoyerJSON([
                'error' => 'Erreur lors du changement de statut'
            ], 500);
        }
    }
    
    // PUT /api/faq?action=reorder - Changer l'ordre d'affichage
    elseif ($action === 'reorder') {
        try {
            $donnees = Utilitaires::lireCorpsJSON();

            if (!isset($donnees['id']) || !is_numeric($donnees['id'])) {
                Utilitaires::envoyerJSON([
                    'error' => 'ID de la question requis'
                ], 400);
            }

            if (!isset($donnees['display_order']) || !is_numeric($donnees['display_order'])) {
                Utilitaires::envoyerJSON([
                    'error' => 'Ordre d\'affichage requis'
                ], 400);
            }

            $idQuestion = (int)$donnees['id'];
            $nouvelOrdre = (int)$donnees['display_order'];

            $succes = $modeleFaq->changerOrdre($idQuestion, $nouvelOrdre);

            if (!$succes) {
                Utilitaires::envoyerJSON([
                    'error' => 'Question introuvable ou échec de la modification'
                ], 404);
            }

            Utilitaires::envoyerJSON([
                'ok' => true,
                'message' => 'Ordre modifié avec succès'
            ]);

        } catch (Exception $e) {
            Utilitaires::envoyerJSON([
                'error' => 'Erreur lors du changement d\'ordre'
            ], 500);
        }
    } else {
        Utilitaires::envoyerJSON(['error' => 'Action non reconnue'], 400);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ROUTE DELETE - Suppression d'une question (ADMIN uniquement)
// ═══════════════════════════════════════════════════════════════════════════

elseif ($methode === 'DELETE') {
    
    // Vérification des droits administrateur
    verifierDroitsAdmin();
    
    // Vérification du token CSRF
    if (!GestionnaireSession::validerTokenCSRF($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
    }

    // DELETE /api/faq?id=X - Supprimer une question
    if ($id !== null) {
        try {
            // Vérifier que la question existe
            $questionExistante = $modeleFaq->obtenirParId($id);
            if ($questionExistante === null) {
                Utilitaires::envoyerJSON([
                    'error' => 'Question introuvable'
                ], 404);
            }

            $succes = $modeleFaq->supprimer($id);

            if (!$succes) {
                Utilitaires::envoyerJSON([
                    'error' => 'Échec de la suppression'
                ], 500);
            }

            Utilitaires::envoyerJSON([
                'ok' => true,
                'message' => 'Question supprimée avec succès'
            ]);

        } catch (Exception $e) {
            Utilitaires::envoyerJSON([
                'error' => 'Erreur lors de la suppression'
            ], 500);
        }
    } else {
        Utilitaires::envoyerJSON([
            'error' => 'ID de la question requis'
        ], 400);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// MÉTHODE NON SUPPORTÉE
// ═══════════════════════════════════════════════════════════════════════════

else {
    Utilitaires::envoyerJSON([
        'error' => 'Méthode HTTP non supportée'
    ], 405);
}