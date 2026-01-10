<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONTRÔLEUR ADMIN - API pour le dashboard administrateur
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce contrôleur gère toutes les requêtes API du dashboard admin.
 * Toutes les routes sont protégées et réservées aux administrateurs.
 * 
 * Routes disponibles :
 * - GET /api/admin?action=resume       → Résumé global
 * - GET /api/admin?action=utilisateurs → Liste utilisateurs (paginée)
 * - GET /api/admin?action=vehicules    → Derniers véhicules
 * - GET /api/admin?action=messages     → Flux de messages
 * - GET /api/admin?action=offres       → Dernières offres
 * - GET /api/admin?action=activite     → Activité récente
 * - GET /api/admin?action=graphiques   → Données pour graphiques
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Vérification de l'authentification admin
GestionnaireSession::demarrerSession();

// 🔒 SÉCURITÉ : Vérifier que l'utilisateur est connecté
if (!GestionnaireSession::estConnecte()) {
    Utilitaires::envoyerJSON(['error' => 'Authentification requise'], 401);
}

// 🔒 SÉCURITÉ : Vérifier que l'utilisateur est administrateur
if (!GestionnaireSession::estAdmin()) {
    Utilitaires::envoyerJSON(['error' => 'Accès refusé - Droits administrateur requis'], 403);
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
