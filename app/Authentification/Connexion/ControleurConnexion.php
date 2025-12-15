<?php

if (session_status() === PHP_SESSION_NONE) session_start();

class ControleurConnexion {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleUtilisateur();
    }

    public function traiterRequete() {
        $action = $_GET['action'] ?? 'login';
        $methode = $_SERVER['REQUEST_METHOD'];

        try {
            if ($methode === 'POST' && $action === 'login') {
                $this->login();
            } elseif ($methode === 'POST' && $action === 'logout') {
                $this->logout();
            } elseif ($methode === 'GET' && $action === 'me') {
                $this->me();
            } else {
                Utilitaires::envoyerJSON(['error' => 'Action non supportée'], 400);
            }
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['error' => $e->getMessage()], 500);
        }
    }

    private function login() {
        // Vérification du Rate Limiting (Limitation de tentatives)
        if (!GestionnaireLimiteTaux::verifierTentative('login')) {
            Utilitaires::envoyerJSON(['error' => 'Trop de tentatives. Veuillez réessayer dans 15 minutes.'], 429);
        }

        $donnees = Utilitaires::lireCorpsJSON();
        $email = trim((string)($donnees['email'] ?? ''));
        $motDePasse = (string)($donnees['password'] ?? '');

        if (!$email || !$motDePasse) {
            Utilitaires::envoyerJSON(['error' => 'Identifiants requis.'], 422);
        }

        $utilisateur = $this->modele->trouverParEmail($email);

        if (!$utilisateur || !ServiceChiffrement::verifierMotDePasse($motDePasse, $utilisateur['password_hash'])) {
            // Enregistrer l'échec
            $restant = GestionnaireLimiteTaux::ajouterTentative('login');
            Utilitaires::envoyerJSON(['error' => "Email ou mot de passe incorrect. ($restant essais restants)"], 401);
        }

        // Succès : Réinitialiser le compteur de tentatives
        GestionnaireLimiteTaux::reinitialiser('login');

        $_SESSION['user'] = [
            'id' => (int)$utilisateur['id'],
            'first_name' => $utilisateur['first_name'],
            'email' => $utilisateur['email'],
            'avatar_path' => $utilisateur['avatar_path'] ?? null,
            'email_verified_at' => $utilisateur['email_verified_at'] ?? null,
            'phone_verified_at' => $utilisateur['phone_verified_at'] ?? null,
            'role' => $utilisateur['role'] ?? 'user',
        ];
        
        // Initialiser le timestamp d'activité pour le timeout
        $_SESSION['derniere_activite'] = time();

        Utilitaires::envoyerJSON(['ok' => true, 'user' => $_SESSION['user']]);
    }

    private function logout() {
        GestionnaireSession::detruireSession();
        Utilitaires::envoyerJSON(['ok' => true]);
    }

    private function me() {
        if (empty($_SESSION['user'])) {
            Utilitaires::envoyerJSON(['error' => 'Non authentifié.'], 401);
        }
        
        $utilisateur = $this->modele->trouverParId((int)$_SESSION['user']['id']);
        if (!$utilisateur) {
            Utilitaires::envoyerJSON(['error' => 'Utilisateur introuvable'], 404);
        }
        
        Utilitaires::envoyerJSON(['ok' => true, 'user' => $utilisateur]);
    }
}

$controleur = new ControleurConnexion();
$controleur->traiterRequete();
