<?php

class ControleurDetailsVehicule {
    private $modele;

    public function __construct() {
        // S'assurer que la session est démarrée via le gestionnaire
        GestionnaireSession::demarrerSession();
        $this->modele = new ModeleVehicule();
    }

    public function traiterRequete() {
        $methode = $_SERVER['REQUEST_METHOD'];

        switch ($methode) {
            case 'GET':
                $this->gererGet();
                break;
            case 'DELETE':
                $this->gererDelete();
                break;
            default:
                Utilitaires::envoyerJSON(['erreur' => 'Méthode non autorisée'], 405);
        }
    }

    private function gererGet() {
        if (isset($_GET['id'])) {
            $vehiculeId = (int)$_GET['id'];
            $vehicule = $this->modele->obtenirParId($vehiculeId);
            
            if (!$vehicule) {
                Utilitaires::envoyerJSON(['erreur' => 'Véhicule introuvable'], 404);
                return;
            }
            
            // Vérifier la visibilité (public/privé/en_attente/refuse)
            $userId = null;
            $isAdmin = false;
            
            if (GestionnaireSession::estConnecte()) {
                $user = GestionnaireSession::obtenirUtilisateur();
                $userId = (int)$user['id'];
                $isAdmin = ($user['role'] ?? '') === 'admin';
            }
            
            $status = $vehicule['status'] ?? 'public';
            $isOwner = $userId && $vehicule['user_id'] == $userId;
            
            // Si en_attente ou refuse, seul le propriétaire ou l'admin peut voir
            if (in_array($status, ['en_attente', 'refuse'])) {
                if (!$isOwner && !$isAdmin) {
                    Utilitaires::envoyerJSON(['erreur' => 'Véhicule introuvable'], 404);
                    return;
                }
            }
            
            // Si privé, vérifier l'accès
            if ($status === 'prive') {
                if (!$isOwner && !$isAdmin) {
                    Utilitaires::envoyerJSON(['erreur' => 'Véhicule introuvable'], 404);
                    return;
                }
            }
            
            // Incrémenter les vues de manière intelligente
            // - Ne compte pas les vues du propriétaire
            // - Ne compte qu'une fois par session
            // - Cooldown de 24h via cookie
            if (GestionnaireVues::doitCompterVue($vehiculeId, $userId, $vehicule['user_id'])) {
                $this->modele->incrementerVues($vehiculeId);
                GestionnaireVues::marquerCommeVu($vehiculeId);
            }
            
            // Ajouter les informations de l'utilisateur connecté pour gérer l'affichage côté client
            $vehicule['utilisateur_connecte'] = GestionnaireSession::estConnecte();
            $vehicule['email_verifie'] = false;
            
            if (GestionnaireSession::estConnecte()) {
                $user = GestionnaireSession::obtenirUtilisateur();
                $vehicule['email_verifie'] = !empty($user['email_verified_at']);
            }
            
            Utilitaires::envoyerJSON($vehicule);
        } else {
            Utilitaires::envoyerJSON(['erreur' => 'ID manquant'], 400);
        }
    }

    private function gererDelete() {
        if (!GestionnaireSession::estConnecte()) {
            Utilitaires::envoyerJSON(['erreur' => 'Authentification requise'], 401);
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            Utilitaires::envoyerJSON(['erreur' => 'ID invalide'], 422);
        }

        $user = GestionnaireSession::obtenirUtilisateur();
        $userId = (int)$user['id'];
        $isAdmin = ($user['role'] ?? '') === 'admin';

        if ($this->modele->supprimer($id, $userId, $isAdmin)) {
            Utilitaires::envoyerJSON(['ok' => true]);
        } else {
            Utilitaires::envoyerJSON(['erreur' => 'Véhicule introuvable ou suppression impossible'], 404);
        }
    }
}

$controleur = new ControleurDetailsVehicule();
$controleur->traiterRequete();
