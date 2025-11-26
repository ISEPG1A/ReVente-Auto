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
                Utils::envoyerJSON(['error' => 'Action non supportée'], 400);
            }
        } catch (Exception $e) {
            Utils::envoyerJSON(['error' => $e->getMessage()], 500);
        }
    }

    private function login() {
        $donnees = Utils::lireCorpsJSON();
        $email = trim((string)($donnees['email'] ?? ''));
        $motDePasse = (string)($donnees['password'] ?? '');

        if (!$email || !$motDePasse) {
            Utils::envoyerJSON(['error' => 'Identifiants requis.'], 422);
        }

        $utilisateur = $this->modele->trouverParEmail($email);

        if (!$utilisateur || !CryptoService::verifierMotDePasse($motDePasse, $utilisateur['password_hash'])) {
            Utils::envoyerJSON(['error' => 'Email ou mot de passe incorrect.'], 401);
        }

        $_SESSION['user'] = [
            'id' => (int)$utilisateur['id'],
            'first_name' => $utilisateur['first_name'],
            'email' => $utilisateur['email'],
            'avatar_path' => $utilisateur['avatar_path'] ?? null,
            'email_verified_at' => $utilisateur['email_verified_at'] ?? null,
            'phone_verified_at' => $utilisateur['phone_verified_at'] ?? null,
            'role' => $utilisateur['role'] ?? 'user',
        ];

        Utils::envoyerJSON(['ok' => true, 'user' => $_SESSION['user']]);
    }

    private function logout() {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        Utils::envoyerJSON(['ok' => true]);
    }

    private function me() {
        if (empty($_SESSION['user'])) {
            Utils::envoyerJSON(['error' => 'Non authentifié.'], 401);
        }
        
        $utilisateur = $this->modele->trouverParId((int)$_SESSION['user']['id']);
        if (!$utilisateur) {
            Utils::envoyerJSON(['error' => 'Utilisateur introuvable'], 404);
        }
        
        Utils::envoyerJSON(['ok' => true, 'user' => $utilisateur]);
    }
}

$controleur = new ControleurConnexion();
$controleur->traiterRequete();
