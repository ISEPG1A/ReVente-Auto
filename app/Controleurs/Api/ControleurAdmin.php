<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONTRÔLEUR ADMIN API - Gestion administrative
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Routes disponibles :
 * - GET  /api/admin?action=resume          → Données complètes du dashboard
 * - GET  /api/admin?action=utilisateurs    → Liste des utilisateurs (pagination, recherche)
 * - GET  /api/admin?action=utilisateur&id= → Détails d'un utilisateur
 * - DELETE /api/admin?action=utilisateur&id= → Supprimer un utilisateur
 * - GET  /api/admin?action=vehicules       → Liste des véhicules (pagination, recherche)
 * - PATCH /api/admin?action=vehicule&id=   → Changer statut véhicule
 * - DELETE /api/admin?action=vehicule&id=  → Supprimer un véhicule
 * - GET  /api/admin?action=statistiques    → Stats de popularité (marques, types, évolution)
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0 - Refonte complète
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Démarrage session et vérifications
GestionnaireSession::demarrerSession();

// 🔒 SÉCURITÉ : Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    Utilitaires::envoyerJSON(['error' => 'Authentification requise'], 401);
}

// 🔒 SÉCURITÉ : Vérifier que l'utilisateur est administrateur
if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    Utilitaires::envoyerJSON(['error' => 'Accès refusé - Droits administrateur requis'], 403);
}

// 🔒 SÉCURITÉ : Vérifier le token CSRF pour les actions de modification
$methode = $_SERVER['REQUEST_METHOD'];
if (in_array($methode, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    if (!AideCSRF::verifierTokenDepuisRequete()) {
        Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide'], 403);
    }
}

// Instancier le modèle
$modele = new ModeleAdmin();

// Récupérer l'action demandée
$action = $_GET['action'] ?? 'resume';

try {
    switch ($action) {
        
        // ============================================
        // RÉSUMÉ GLOBAL
        // ============================================
        case 'resume':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            // Récupérer les paramètres pour l'activité
            $limite = min(100, max(10, (int)($_GET['limite'] ?? 20)));
            $dateDebut = $_GET['date_debut'] ?? null;
            $dateFin = $_GET['date_fin'] ?? null;
            
            $data = $modele->obtenirResume($limite, $dateDebut, $dateFin);
            Utilitaires::envoyerJSON(['success' => true, 'data' => $data]);
            break;
        
        // ============================================
        // GESTION UTILISATEURS
        // ============================================
        case 'utilisateurs':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limite = min(50, max(10, (int)($_GET['limite'] ?? 20)));
            $recherche = $_GET['recherche'] ?? '';
            $filtre = $_GET['filtre'] ?? 'all';
            
            $data = $modele->obtenirUtilisateurs($page, $limite, $recherche, $filtre);
            Utilitaires::envoyerJSON(['success' => true, 'data' => $data]);
            break;
        
        case 'utilisateur':
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                Utilitaires::envoyerJSON(['error' => 'ID utilisateur invalide'], 400);
            }
            
            if ($methode === 'GET') {
                // Détails d'un utilisateur
                $user = $modele->obtenirUtilisateur($id);
                if (!$user) {
                    Utilitaires::envoyerJSON(['error' => 'Utilisateur introuvable'], 404);
                }
                Utilitaires::envoyerJSON(['success' => true, 'data' => $user]);
                
            } elseif ($methode === 'DELETE') {
                // Supprimer un utilisateur
                // 🔒 SÉCURITÉ : Empêcher la suppression de son propre compte
                if ($id == $_SESSION['user']['id']) {
                    Utilitaires::envoyerJSON(['error' => 'Impossible de supprimer votre propre compte'], 400);
                }
                
                $success = $modele->supprimerUtilisateur($id);
                if ($success) {
                    Utilitaires::envoyerJSON(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
                } else {
                    Utilitaires::envoyerJSON(['error' => 'Erreur lors de la suppression'], 500);
                }
                
            } else {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            break;
        
        // ============================================
        // GESTION VÉHICULES
        // ============================================
        case 'vehicules':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limite = min(50, max(10, (int)($_GET['limite'] ?? 20)));
            $recherche = $_GET['recherche'] ?? '';
            $filtre = $_GET['filtre'] ?? 'all';
            
            $data = $modele->obtenirVehicules($page, $limite, $recherche, $filtre);
            Utilitaires::envoyerJSON(['success' => true, 'data' => $data]);
            break;
        
        case 'vehicule':
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                Utilitaires::envoyerJSON(['error' => 'ID véhicule invalide'], 400);
            }
            
            if ($methode === 'PATCH') {
                // Changer le statut d'un véhicule
                $input = json_decode(file_get_contents('php://input'), true);
                $statut = $input['statut'] ?? '';
                
                if (!in_array($statut, ['public', 'prive'])) {
                    Utilitaires::envoyerJSON(['error' => 'Statut invalide'], 400);
                }
                
                $success = $modele->changerStatutVehicule($id, $statut);
                if ($success) {
                    Utilitaires::envoyerJSON(['success' => true, 'message' => 'Statut modifié avec succès']);
                } else {
                    Utilitaires::envoyerJSON(['error' => 'Erreur lors de la modification'], 500);
                }
                
            } elseif ($methode === 'DELETE') {
                // Supprimer un véhicule
                $success = $modele->supprimerVehicule($id);
                if ($success) {
                    Utilitaires::envoyerJSON(['success' => true, 'message' => 'Véhicule supprimé avec succès']);
                } else {
                    Utilitaires::envoyerJSON(['error' => 'Erreur lors de la suppression'], 500);
                }
                
            } else {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            break;
        
        // ============================================
        // STATISTIQUES
        // ============================================
        case 'statistiques':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $data = [
                'top_marques' => $modele->obtenirTopMarques(10),
                'repartition_types' => $modele->obtenirRepartitionTypes(),
                'evolution_inscriptions' => $modele->obtenirEvolutionInscriptions(),
                'evolution_annonces' => $modele->obtenirEvolutionAnnonces()
            ];
            
            Utilitaires::envoyerJSON(['success' => true, 'data' => $data]);
            break;
        
        // ============================================
        // ACTION INVALIDE
        // ============================================
        default:
            Utilitaires::envoyerJSON(['error' => 'Action inconnue'], 400);
    }
    
} catch (Exception $e) {
    error_log("Erreur Admin API: " . $e->getMessage());
    Utilitaires::envoyerJSON(['error' => 'Erreur serveur'], 500);
}


// Instancier le modèle
$modele = new ModeleAdmin();

// Récupérer l'action demandée
$action = $_GET['action'] ?? 'resume';
$methode = $_SERVER['REQUEST_METHOD'];

// ============================================
// Routage des actions
// ============================================

try {
    switch ($action) {
        // ----------------------------------------
        // Résumé global (dashboard principal)
        // ----------------------------------------
        case 'resume':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $resume = $modele->obtenirResume();
            Utilitaires::envoyerJSON(['ok' => true, 'data' => $resume]);
            break;
        
        // ----------------------------------------
        // Statistiques utilisateurs détaillées
        // ----------------------------------------
        case 'utilisateurs':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $parPage = min(100, max(1, (int)($_GET['par_page'] ?? 20)));
            $filtre = $_GET['filtre'] ?? 'all';
            
            // Valider le filtre
            if (!in_array($filtre, ['all', 'verified', 'unverified', 'admin'])) {
                $filtre = 'all';
            }
            
            $utilisateurs = $modele->obtenirListeUtilisateurs($page, $parPage, $filtre);
            $total = $modele->compterUtilisateurs($filtre);
            $stats = $modele->obtenirStatistiquesUtilisateurs();
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'data' => [
                    'utilisateurs' => $utilisateurs,
                    'stats' => $stats,
                    'pagination' => [
                        'page' => $page,
                        'par_page' => $parPage,
                        'total' => $total,
                        'total_pages' => ceil($total / $parPage)
                    ]
                ]
            ]);
            break;
        
        // ----------------------------------------
        // Liste des véhicules
        // ----------------------------------------
        case 'vehicules':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $limite = min(50, max(1, (int)($_GET['limite'] ?? 20)));
            
            $vehicules = $modele->obtenirDerniersVehicules($limite);
            $stats = $modele->obtenirStatistiquesVehicules();
            $marques = $modele->obtenirMarquesPopulaires(10);
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'data' => [
                    'vehicules' => $vehicules,
                    'stats' => $stats,
                    'marques_populaires' => $marques
                ]
            ]);
            break;
        
        // ----------------------------------------
        // Flux de messages
        // ----------------------------------------
        case 'messages':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $limite = min(100, max(1, (int)($_GET['limite'] ?? 30)));
            
            $messages = $modele->obtenirDerniersMessages($limite);
            $stats = $modele->obtenirStatistiquesMessagerie();
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'data' => [
                    'messages' => $messages,
                    'stats' => $stats
                ]
            ]);
            break;
        
        // ----------------------------------------
        // Offres
        // ----------------------------------------
        case 'offres':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $limite = min(50, max(1, (int)($_GET['limite'] ?? 20)));
            
            $offres = $modele->obtenirDernieresOffres($limite);
            $stats = $modele->obtenirStatistiquesOffres();
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'data' => [
                    'offres' => $offres,
                    'stats' => $stats
                ]
            ]);
            break;
        
        // ----------------------------------------
        // Favoris
        // ----------------------------------------
        case 'favoris':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $stats = $modele->obtenirStatistiquesFavoris();
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'data' => $stats
            ]);
            break;
        
        // ----------------------------------------
        // Activité récente (timeline)
        // ----------------------------------------
        case 'activite':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $limite = min(50, max(1, (int)($_GET['limite'] ?? 30)));
            
            $activite = $modele->obtenirActiviteRecente($limite);
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'data' => $activite
            ]);
            break;
        
        // ----------------------------------------
        // Données pour graphiques
        // ----------------------------------------
        case 'graphiques':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $type = $_GET['type'] ?? 'all';
            
            $data = [];
            
            if ($type === 'all' || $type === 'inscriptions') {
                $data['inscriptions'] = $modele->obtenirInscriptionsParJour();
            }
            
            if ($type === 'all' || $type === 'annonces') {
                $data['annonces'] = $modele->obtenirAnnoncesParJour();
            }
            
            if ($type === 'all' || $type === 'messages') {
                $data['messages'] = $modele->obtenirMessagesParJour();
            }
            
            Utilitaires::envoyerJSON([
                'ok' => true,
                'data' => $data
            ]);
            break;
        
        // ----------------------------------------
        // Action inconnue
        // ----------------------------------------
        default:
            Utilitaires::envoyerJSON(['error' => 'Action non reconnue'], 400);
            break;
    }
    
} catch (Exception $e) {
    error_log("Erreur ControleurAdmin : " . $e->getMessage());
    Utilitaires::envoyerJSON(['error' => 'Erreur serveur interne'], 500);
}
