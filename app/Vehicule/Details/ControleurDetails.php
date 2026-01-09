<?php

class ControleurDetails {
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
                Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
        }
    }

    private function gererGet() {
        if (isset($_GET['id'])) {
            $vehiculeId = (int)$_GET['id'];
            $vehicule = $this->modele->obtenirParId($vehiculeId);
            
            if (!$vehicule) {
                Utilitaires::envoyerJSON(['error' => 'Véhicule introuvable'], 404);
                return;
            }
            
            // Vérifier la visibilité (public/privé)
            $userId = null;
            $isAdmin = false;
            
            if (GestionnaireSession::estConnecte()) {
                $user = GestionnaireSession::obtenirUtilisateur();
                $userId = (int)$user['id'];
                $isAdmin = ($user['role'] ?? '') === 'admin';
            }
            
            // Si privé, vérifier l'accès
            if (($vehicule['status'] ?? 'public') === 'prive') {
                $isOwner = $userId && $vehicule['user_id'] == $userId;
                if (!$isOwner && !$isAdmin) {
                    Utilitaires::envoyerJSON(['error' => 'Véhicule introuvable'], 404);
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
            
            Utilitaires::envoyerJSON($vehicule);
        } else {
            Utilitaires::envoyerJSON(['error' => 'ID manquant'], 400);
        }
    }

    private function gererDelete() {
        if (!GestionnaireSession::estConnecte()) {
            Utilitaires::envoyerJSON(['error' => 'Authentification requise'], 401);
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            Utilitaires::envoyerJSON(['error' => 'ID invalide'], 422);
        }

        $user = GestionnaireSession::obtenirUtilisateur();
        $userId = (int)$user['id'];
        $isAdmin = ($user['role'] ?? '') === 'admin';

        if ($this->modele->supprimer($id, $userId, $isAdmin)) {
            Utilitaires::envoyerJSON(['ok' => true]);
        } else {
            Utilitaires::envoyerJSON(['error' => 'Véhicule introuvable ou suppression impossible'], 404);
        }
    }
}

$controleur = new ControleurDetails();
$controleur->traiterRequete();
