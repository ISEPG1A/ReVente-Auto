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
            
            $data = $modele->obtenirResume();
            Utilitaires::envoyerJSON(['success' => true, 'data' => $data]);
            break;
        
        // ============================================
        // GESTION UTILISATEURS
        // ============================================
        case 'utilisateurs':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            try {
                $page = max(1, (int)($_GET['page'] ?? 1));
                $limite = min(50, max(10, (int)($_GET['limite'] ?? 20)));
                $recherche = $_GET['recherche'] ?? '';
                $filtre = $_GET['filtre'] ?? 'all';
                
                $data = $modele->obtenirUtilisateurs($page, $limite, $recherche, $filtre);
                Utilitaires::envoyerJSON(['success' => true, 'data' => $data]);
            } catch (Exception $e) {
                error_log("Erreur obtenirUtilisateurs: " . $e->getMessage());
                Utilitaires::envoyerJSON(['error' => 'Erreur: ' . $e->getMessage()], 500);
            }
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
                
                // Récupérer les infos de l'utilisateur avant suppression
                $userToDelete = $modele->obtenirUtilisateur($id);
                if (!$userToDelete) {
                    Utilitaires::envoyerJSON(['error' => 'Utilisateur introuvable'], 404);
                }
                
                try {
                    $success = $modele->supprimerUtilisateur($id);
                    if ($success) {
                        // Logger la suppression avec les détails complets
                        $modele->ajouterLog('suppression_compte', 'Suppression de compte utilisateur', [
                            'utilisateur_id' => $id,
                            'utilisateur_prenom' => $userToDelete['first_name'] ?? '',
                            'utilisateur_nom' => $userToDelete['last_name'] ?? '',
                            'utilisateur_email' => $userToDelete['email'] ?? '',
                            'role' => $userToDelete['role'] ?? 'user'
                        ], $id, null, $_SESSION['user']['id']);
                        
                        Utilitaires::envoyerJSON(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
                    } else {
                        Utilitaires::envoyerJSON(['error' => 'Erreur lors de la suppression'], 500);
                    }
                } catch (Exception $e) {
                    error_log("Erreur suppression utilisateur $id: " . $e->getMessage());
                    Utilitaires::envoyerJSON(['error' => 'Erreur: ' . $e->getMessage()], 500);
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
            
            try {
                $page = max(1, (int)($_GET['page'] ?? 1));
                $limite = min(50, max(10, (int)($_GET['limite'] ?? 20)));
                $recherche = $_GET['recherche'] ?? '';
                $filtre = $_GET['filtre'] ?? 'all';
                
                $data = $modele->obtenirVehicules($page, $limite, $recherche, $filtre);
                Utilitaires::envoyerJSON(['success' => true, 'data' => $data]);
            } catch (Exception $e) {
                error_log("Erreur obtenirVehicules: " . $e->getMessage());
                Utilitaires::envoyerJSON(['error' => 'Erreur: ' . $e->getMessage()], 500);
            }
            break;
        
        case 'vehicule':
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                Utilitaires::envoyerJSON(['error' => 'ID véhicule invalide'], 400);
            }
            
            if ($methode === 'PATCH') {
                // Récupérer les infos du véhicule
                $vehicule = $modele->obtenirVehiculeParId($id);
                if (!$vehicule) {
                    Utilitaires::envoyerJSON(['error' => 'Véhicule introuvable'], 404);
                }
                
                $ancienStatut = $vehicule['status'] ?? 'inconnu';
                
                // Changer le statut d'un véhicule
                $input = json_decode(file_get_contents('php://input'), true);
                $statut = $input['statut'] ?? '';
                
                if (!in_array($statut, ['public', 'prive'])) {
                    Utilitaires::envoyerJSON(['error' => 'Statut invalide'], 400);
                }
                
                $success = $modele->changerStatutVehicule($id, $statut);
                if ($success) {
                    // Logger le changement de statut
                    $modele->ajouterLog('moderation', 'Modération véhicule', [
                        'vehicule_id' => $id,
                        'marque' => $vehicule['marque'] ?? '',
                        'modele' => $vehicule['modele'] ?? '',
                        'annee' => $vehicule['annee'] ?? '',
                        'ancien_statut' => $ancienStatut,
                        'nouveau_statut' => $statut,
                        'proprietaire_prenom' => $vehicule['first_name'] ?? '',
                        'proprietaire_nom' => $vehicule['last_name'] ?? ''
                    ], null, $id, $_SESSION['user']['id']);
                    
                    Utilitaires::envoyerJSON(['success' => true, 'message' => 'Statut modifié avec succès']);
                } else {
                    Utilitaires::envoyerJSON(['error' => 'Erreur lors de la modification'], 500);
                }
                
            } elseif ($methode === 'DELETE') {
                // Récupérer les infos du véhicule avant de le supprimer
                $vehicule = $modele->obtenirVehiculeParId($id);
                if (!$vehicule) {
                    Utilitaires::envoyerJSON(['error' => 'Véhicule introuvable'], 404);
                }
                
                // Supprimer le véhicule
                $success = $modele->supprimerVehicule($id);
                if ($success) {
                    // Logger la suppression avec les détails
                    $modele->ajouterLog('annonce_suppression', 'Suppression de véhicule', [
                        'vehicule_id' => $id,
                        'marque' => $vehicule['marque'] ?? '',
                        'modele' => $vehicule['modele'] ?? '',
                        'annee' => $vehicule['annee'] ?? '',
                        'prix' => $vehicule['prix'] ?? 0,
                        'proprietaire_id' => $vehicule['user_id'] ?? null,
                        'proprietaire_prenom' => $vehicule['first_name'] ?? '',
                        'proprietaire_nom' => $vehicule['last_name'] ?? ''
                    ], null, $id, $_SESSION['user']['id']);
                    
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
                // Stats de base
                'top_marques' => $modele->obtenirTopMarques(10),
                'repartition_types' => $modele->obtenirRepartitionTypes(),
                'evolution_inscriptions' => $modele->obtenirEvolutionInscriptions(),
                'evolution_annonces' => $modele->obtenirEvolutionAnnonces(),
                
                // Nouvelles stats détaillées
                'prix_par_type' => $modele->obtenirPrixMoyenParType(),
                'distribution_prix' => $modele->obtenirDistributionPrix(),
                'top_carburants' => $modele->obtenirTopCarburants(),
                'distribution_annees' => $modele->obtenirDistributionAnnees(),
                'stats_popularite' => $modele->obtenirStatsPopularite(),
                'top_annonces_vues' => $modele->obtenirTopAnnoncesVues(5),
                'stats_score_ia' => $modele->obtenirStatsScoreIA(),
                'distribution_geo' => $modele->obtenirDistributionGeographique(10),
                'taux_conversion' => $modele->obtenirTauxConversion()
            ];
            
            Utilitaires::envoyerJSON(['success' => true, 'data' => $data]);
            break;
        
        // ============================================
        // ACTIVITÉ RÉCENTE (depuis admin_logs - permanent)
        // ============================================
        case 'activite':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limite = 20; // Toujours 20 par page
            $dateDebut = $_GET['date_debut'] ?? null;
            $dateFin = $_GET['date_fin'] ?? null;
            $typeFiltre = $_GET['type'] ?? null;
            
            $activite = $modele->obtenirActiviteRecenteLogs($page, $limite, $dateDebut, $dateFin, $typeFiltre);
            
            Utilitaires::envoyerJSON([
                'success' => true,
                'data' => $activite
            ]);
            break;
        
        // ============================================
        // GESTION DES CONTACTS
        // ============================================
        case 'contacts':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limite = min(50, max(10, (int)($_GET['limite'] ?? 20)));
            $filtre = $_GET['filtre'] ?? 'all';
            $dateDebut = $_GET['date_debut'] ?? null;
            $dateFin = $_GET['date_fin'] ?? null;
            
            $data = $modele->obtenirContacts($page, $limite, $filtre, $dateDebut, $dateFin);
            Utilitaires::envoyerJSON(['success' => true, 'data' => $data]);
            break;
        
        case 'contact':
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                Utilitaires::envoyerJSON(['error' => 'ID contact invalide'], 400);
            }
            
            if ($methode === 'GET') {
                // Détails d'un contact
                $contact = $modele->obtenirContactParId($id);
                if (!$contact) {
                    Utilitaires::envoyerJSON(['error' => 'Contact introuvable'], 404);
                }
                // Marquer comme lu seulement si c'est un nouveau message ET pas en lecture seule
                $lectureSeule = isset($_GET['lecture_seule']) && $_GET['lecture_seule'] === '1';
                if (!$lectureSeule && $contact['status'] === 'nouveau') {
                    $modele->mettreAJourContact($id, 'lu');
                }
                Utilitaires::envoyerJSON(['success' => true, 'data' => $contact]);
                
            } elseif ($methode === 'PATCH') {
                // Mettre à jour le statut/répondre
                $input = json_decode(file_get_contents('php://input'), true);
                $status = $input['status'] ?? null;
                $reponse = $input['reponse'] ?? null;
                
                if (!$status || !in_array($status, ['lu', 'traite', 'archive'])) {
                    Utilitaires::envoyerJSON(['error' => 'Statut invalide'], 400);
                }
                
                // Si une réponse est fournie, envoyer l'email
                $emailEnvoye = false;
                $erreurEmail = null;
                if ($reponse && !empty(trim($reponse))) {
                    $contact = $modele->obtenirContactParId($id);
                    if ($contact && !empty($contact['email'])) {
                        try {
                            $serviceEmail = new ServiceEmail();
                            $emailEnvoye = $serviceEmail->envoyerReponseContact(
                                $contact['email'],
                                $contact['nom'],
                                $contact['sujet'],
                                $contact['message'],
                                $reponse
                            );
                        } catch (Exception $e) {
                            $erreurEmail = $e->getMessage();
                            error_log("Erreur envoi email réponse contact: " . $e->getMessage());
                        }
                    }
                }
                
                $success = $modele->mettreAJourContact($id, $status, $reponse, $_SESSION['user']['id']);
                
                if ($success) {
                    // Logger l'action avec le bon type d'action
                    $actionLog = $status === 'archive' ? 'Contact archivé' : ($reponse ? 'Réponse envoyée au contact' : 'Contact traité');
                    $modele->ajouterLog('contact', $actionLog, [
                        'status' => $status, 
                        'email_envoye' => $emailEnvoye,
                        'contact_id' => $id
                    ], null, null, $_SESSION['user']['id']);
                    
                    if ($reponse && !empty(trim($reponse))) {
                        if ($emailEnvoye) {
                            $message = 'Réponse envoyée par email avec succès !';
                        } else {
                            $message = 'Contact mis à jour mais erreur lors de l\'envoi de l\'email' . ($erreurEmail ? ': ' . $erreurEmail : '');
                        }
                    } else {
                        $message = 'Contact mis à jour';
                    }
                    Utilitaires::envoyerJSON(['success' => true, 'message' => $message, 'email_envoye' => $emailEnvoye]);
                } else {
                    Utilitaires::envoyerJSON(['error' => 'Erreur lors de la mise à jour'], 500);
                }
                
            } else {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            break;
        
        // ============================================
        // MODÉRATION DES ANNONCES
        // ============================================
        case 'moderation':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limite = min(50, max(10, (int)($_GET['limite'] ?? 20)));
            
            $data = $modele->obtenirAnnoncesEnAttente($page, $limite);
            Utilitaires::envoyerJSON(['success' => true, 'data' => $data]);
            break;
        
        case 'moderer':
            if ($methode !== 'POST') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $decision = $input['decision'] ?? '';
            $raison = $input['raison'] ?? null;
            
            if ($id <= 0) {
                Utilitaires::envoyerJSON(['error' => 'ID annonce invalide'], 400);
            }
            
            if (!in_array($decision, ['public', 'refuse'])) {
                Utilitaires::envoyerJSON(['error' => 'Décision invalide (public ou refuse)'], 400);
            }
            
            $success = $modele->modererAnnonce($id, $decision, $_SESSION['user']['id'], $raison);
            
            if ($success) {
                $msg = $decision === 'public' ? 'Annonce approuvée' : 'Annonce refusée';
                Utilitaires::envoyerJSON(['success' => true, 'message' => $msg]);
            } else {
                Utilitaires::envoyerJSON(['error' => 'Erreur lors de la modération'], 500);
            }
            break;
        
        case 'compteurs_moderation':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $data = [
                'en_attente' => $modele->compterAnnoncesEnAttente(),
                'contacts_nouveaux' => $modele->obtenirContacts(1, 1, 'nouveau')['nouveaux'] ?? 0
            ];
            
            Utilitaires::envoyerJSON(['success' => true, 'data' => $data]);
            break;
        
        // ============================================
        // GESTION CONTENU STATIQUE (FAQ, CGU, etc.)
        // ============================================
        case 'contenu':
            $type = $_GET['type'] ?? '';
            
            if (!in_array($type, ['faq', 'cgu', 'confidentialite'])) {
                Utilitaires::envoyerJSON(['error' => 'Type de contenu invalide (faq, cgu, confidentialite)'], 400);
            }
            
            if ($methode === 'GET') {
                $data = $modele->obtenirContenuStatique($type);
                Utilitaires::envoyerJSON(['success' => true, 'data' => $data]);
                
            } elseif ($methode === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? null;
                $titre = trim($input['titre'] ?? '');
                $contenu = trim($input['contenu'] ?? '');
                $ordre = (int)($input['ordre'] ?? 0);
                
                if (empty($titre) || empty($contenu)) {
                    Utilitaires::envoyerJSON(['error' => 'Titre et contenu requis'], 400);
                }
                
                $success = $modele->sauvegarderContenuStatique($id, $type, $titre, $contenu, $ordre, $_SESSION['user']['id']);
                
                if ($success) {
                    $modele->ajouterLog('autre', 'Modification contenu ' . strtoupper($type), ['titre' => $titre, 'type_contenu' => $type], null, null, $_SESSION['user']['id']);
                    Utilitaires::envoyerJSON(['success' => true, 'message' => 'Contenu sauvegardé']);
                } else {
                    Utilitaires::envoyerJSON(['error' => 'Erreur lors de la sauvegarde'], 500);
                }
                
            } else {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            break;

        // ============================================
        // BANNISSEMENT UTILISATEUR
        // ============================================
        case 'bannir':
            if ($methode !== 'POST') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                $userId = (int)($input['user_id'] ?? 0);
                $raison = trim($input['raison'] ?? '');
                
                if ($userId <= 0) {
                    Utilitaires::envoyerJSON(['error' => 'ID utilisateur invalide'], 400);
                }
                
                if (empty($raison)) {
                    Utilitaires::envoyerJSON(['error' => 'La raison du bannissement est requise'], 400);
                }
                
                // Empêcher de se bannir soi-même
                if ($userId == $_SESSION['user']['id']) {
                    Utilitaires::envoyerJSON(['error' => 'Impossible de vous bannir vous-même'], 400);
                }
                
                // Récupérer les infos de l'utilisateur avant de le bannir
                $userToBan = $modele->obtenirUtilisateur($userId);
                if (!$userToBan) {
                    Utilitaires::envoyerJSON(['error' => 'Utilisateur introuvable'], 404);
                }
                
                // Empêcher de bannir un admin
                if ($userToBan['role'] === 'admin') {
                    Utilitaires::envoyerJSON(['error' => 'Impossible de bannir un administrateur'], 400);
                }
                
                $success = $modele->bannirUtilisateur($userId, $raison, $_SESSION['user']['id']);
                
                if ($success) {
                    // Envoyer un email au banni
                    try {
                        ServiceEmail::envoyerNotificationBannissement(
                            $userToBan['email'],
                            $userToBan['first_name'],
                            $raison
                        );
                    } catch (Exception $e) {
                        error_log("Erreur envoi email bannissement: " . $e->getMessage());
                    }
                    
                    // Logger l'action
                    $modele->ajouterLog('utilisateur', 'Bannissement', [
                        'utilisateur_prenom' => $userToBan['first_name'],
                        'utilisateur_nom' => $userToBan['last_name'],
                        'utilisateur_email' => $userToBan['email'],
                        'raison_ban' => $raison
                    ], $userId, null, $_SESSION['user']['id']);
                    
                    Utilitaires::envoyerJSON(['success' => true, 'message' => 'Utilisateur banni avec succès']);
                } else {
                    Utilitaires::envoyerJSON(['error' => 'Erreur lors du bannissement'], 500);
                }
            } catch (Exception $e) {
                error_log("Erreur bannir: " . $e->getMessage());
                Utilitaires::envoyerJSON(['error' => 'Erreur: ' . $e->getMessage()], 500);
            }
            break;

        case 'debannir':
            if ($methode !== 'POST') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = (int)($input['user_id'] ?? 0);
            
            if ($userId <= 0) {
                Utilitaires::envoyerJSON(['error' => 'ID utilisateur invalide'], 400);
            }
            
            // Récupérer les infos de l'utilisateur avant de le débannir
            $userToUnban = $modele->obtenirUtilisateur($userId);
            if (!$userToUnban) {
                Utilitaires::envoyerJSON(['error' => 'Utilisateur introuvable'], 404);
            }
            
            $success = $modele->debannirUtilisateur($userId);
            
            if ($success) {
                // Logger l'action avec les détails complets
                $modele->ajouterLog('utilisateur', 'Débannissement', [
                    'utilisateur_prenom' => $userToUnban['first_name'],
                    'utilisateur_nom' => $userToUnban['last_name'],
                    'utilisateur_email' => $userToUnban['email']
                ], $userId, null, $_SESSION['user']['id']);
                
                Utilitaires::envoyerJSON(['success' => true, 'message' => 'Utilisateur débanni avec succès']);
            } else {
                Utilitaires::envoyerJSON(['error' => 'Erreur lors du débannissement'], 500);
            }
            break;

        // ============================================
        // CHANGEMENT DE RÔLE
        // ============================================
        case 'changer_role':
            if ($methode !== 'POST') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                $userId = (int)($input['user_id'] ?? 0);
                $nouveauRole = $input['role'] ?? '';
                
                if ($userId <= 0) {
                    Utilitaires::envoyerJSON(['error' => 'ID utilisateur invalide'], 400);
                }
                
                if (!in_array($nouveauRole, ['user', 'admin'])) {
                    Utilitaires::envoyerJSON(['error' => 'Rôle invalide'], 400);
                }
                
                // Empêcher de changer son propre rôle
                if ($userId == $_SESSION['user']['id']) {
                    Utilitaires::envoyerJSON(['error' => 'Impossible de modifier votre propre rôle'], 400);
                }
                
                // Récupérer l'ancien rôle et le nom pour le log
                $utilisateur = $modele->obtenirUtilisateur($userId);
                $ancienRole = $utilisateur['role'] ?? 'user';
                
                $success = $modele->changerRole($userId, $nouveauRole);
                
                if ($success) {
                    $modele->ajouterLog('utilisateur', 'Changement rôle', [
                        'utilisateur_prenom' => $utilisateur['first_name'] ?? '',
                        'utilisateur_nom' => $utilisateur['last_name'] ?? '',
                        'ancien_role' => $ancienRole,
                        'nouveau_role' => $nouveauRole
                    ], $userId, null, $_SESSION['user']['id']);
                    Utilitaires::envoyerJSON(['success' => true, 'message' => 'Rôle modifié avec succès']);
                } else {
                    Utilitaires::envoyerJSON(['error' => 'Erreur lors du changement de rôle'], 500);
                }
            } catch (Exception $e) {
                error_log("Erreur changer_role: " . $e->getMessage());
                Utilitaires::envoyerJSON(['error' => 'Erreur: ' . $e->getMessage()], 500);
            }
            break;

        // ============================================
        // DÉTAILS UTILISATEUR COMPLET
        // ============================================
        case 'utilisateur_complet':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            try {
                $userId = (int)($_GET['id'] ?? 0);
                
                if ($userId <= 0) {
                    Utilitaires::envoyerJSON(['error' => 'ID utilisateur invalide'], 400);
                }
                
                $user = $modele->obtenirUtilisateurComplet($userId);
                
                if (!$user) {
                    Utilitaires::envoyerJSON(['error' => 'Utilisateur introuvable'], 404);
                }
                
                Utilitaires::envoyerJSON(['success' => true, 'data' => $user]);
            } catch (Exception $e) {
                error_log("Erreur utilisateur_complet: " . $e->getMessage());
                Utilitaires::envoyerJSON(['error' => 'Erreur: ' . $e->getMessage()], 500);
            }
            break;

        // ============================================
        // GESTION DE L'ÉQUIPE (POSTES DES ADMINS)
        // ============================================
        case 'equipe':
            if ($methode !== 'GET') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            try {
                $page = max(1, (int)($_GET['page'] ?? 1));
                $limite = min(50, max(10, (int)($_GET['limite'] ?? 20)));
                
                $data = $modele->obtenirMembresEquipe($page, $limite);
                Utilitaires::envoyerJSON(['success' => true, 'data' => $data]);
            } catch (Exception $e) {
                error_log("Erreur equipe: " . $e->getMessage());
                Utilitaires::envoyerJSON(['error' => 'Erreur: ' . $e->getMessage()], 500);
            }
            break;

        case 'modifier_poste':
            if ($methode !== 'POST') {
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
            }
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                $userId = (int)($input['user_id'] ?? 0);
                $poste = trim($input['poste'] ?? '');
                
                if ($userId <= 0) {
                    Utilitaires::envoyerJSON(['error' => 'ID utilisateur invalide'], 400);
                }
                
                if (mb_strlen($poste) > 60) {
                    Utilitaires::envoyerJSON(['error' => 'Le poste ne peut pas dépasser 60 caractères'], 400);
                }
                
                // Récupérer l'ancien poste et le nom de l'utilisateur pour le log
                $utilisateur = $modele->obtenirUtilisateur($userId);
                $ancienPoste = $utilisateur['poste'] ?? '';
                
                $success = $modele->modifierPoste($userId, $poste);
                
                if ($success) {
                    $modele->ajouterLog('utilisateur', 'Modification poste admin', [
                        'utilisateur_prenom' => $utilisateur['first_name'] ?? '',
                        'utilisateur_nom' => $utilisateur['last_name'] ?? '',
                        'ancien_poste' => $ancienPoste,
                        'nouveau_poste' => $poste
                    ], $userId, null, $_SESSION['user']['id']);
                    Utilitaires::envoyerJSON(['success' => true, 'message' => 'Poste modifié avec succès']);
                } else {
                    Utilitaires::envoyerJSON(['error' => 'Erreur lors de la modification du poste'], 500);
                }
            } catch (Exception $e) {
                error_log("Erreur modifier_poste: " . $e->getMessage());
                Utilitaires::envoyerJSON(['error' => 'Erreur: ' . $e->getMessage()], 500);
            }
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
