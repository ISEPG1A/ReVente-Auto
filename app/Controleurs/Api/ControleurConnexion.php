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
                Utilitaires::envoyerJSON(['erreur' => 'Action non supportée'], 400);
            }
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['erreur' => $e->getMessage()], 500);
        }
    }

    private function login() {
        $donnees = Utilitaires::lireCorpsJSON();
        $email = trim((string)($donnees['email'] ?? ''));
        $motDePasse = (string)($donnees['password'] ?? '');

        if (!$email || !$motDePasse) {
            Utilitaires::envoyerJSON(['erreur' => 'Identifiants requis.'], 422);
        }

        $utilisateur = $this->modele->trouverParEmail($email);

        // Si l'utilisateur n'existe pas, ne pas compter comme tentative (évite d'énumérer les comptes)
        if (!$utilisateur) {
            Utilitaires::envoyerJSON(['erreur' => 'Email ou mot de passe incorrect.'], 401);
            return;
        }

        // Vérification du Rate Limiting uniquement pour les comptes existants
        if (!GestionnaireLimiteTaux::verifierTentative('login', $email)) {
            $tempsRestant = GestionnaireLimiteTaux::obtenirTempsRestant('login', $email);
            $minutes = ceil($tempsRestant / 60);
            Utilitaires::envoyerJSON(['erreur' => "Trop de tentatives échouées sur ce compte. Veuillez réessayer dans {$minutes} minute(s)."], 429);
            return;
        }

        // Vérifier le mot de passe
        if (!ServiceChiffrement::verifierMotDePasse($motDePasse, $utilisateur['password_hash'])) {
            // Enregistrer l'échec pour ce compte spécifique
            $restant = GestionnaireLimiteTaux::ajouterTentative('login', $email);
            Utilitaires::envoyerJSON(['erreur' => "Mot de passe incorrect. Il vous reste {$restant} tentative(s)."], 401);
            return;
        }

        // Vérifier si l'utilisateur est banni
        if (!empty($utilisateur['banned_at'])) {
            $raison = $utilisateur['ban_reason'] ?? 'Violation des conditions d\'utilisation';
            Utilitaires::envoyerJSON([
                'erreur' => 'Votre compte a été suspendu.',
                'banni' => true,
                'raison' => $raison
            ], 403);
            return;
        }

        // Succès : Réinitialiser le compteur de tentatives pour ce compte
        GestionnaireLimiteTaux::reinitialiser('login', $email);

        $_SESSION['user'] = [
            'id' => (int)$utilisateur['id'],
            'first_name' => $utilisateur['first_name'],
            'email' => $utilisateur['email'],
            'avatar_path' => $utilisateur['avatar_path'] ?? null,
            'email_verified_at' => $utilisateur['email_verified_at'] ?? null,
            'role' => $utilisateur['role'] ?? 'user',
        ];
        
        // 🔒 SÉCURITÉ : Stocker le token de session pour validation future
        // Si l'utilisateur n'a pas de token (migration non appliquée), en générer un
        $db = BaseDeDonnees::obtenirConnexion();
        if (empty($utilisateur['session_token'])) {
            $nouveauToken = bin2hex(random_bytes(32));
            $db->prepare("UPDATE users SET session_token = ?, last_login_at = NOW() WHERE id = ?")->execute([$nouveauToken, $utilisateur['id']]);
            $_SESSION['session_token'] = $nouveauToken;
        } else {
            // Mettre à jour la date de dernière connexion
            $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?")->execute([$utilisateur['id']]);
            $_SESSION['session_token'] = $utilisateur['session_token'];
        }
        
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
            Utilitaires::envoyerJSON(['erreur' => 'Non authentifié.'], 401);
        }
        
        $utilisateur = $this->modele->trouverParId((int)$_SESSION['user']['id']);
        if (!$utilisateur) {
            Utilitaires::envoyerJSON(['erreur' => 'Utilisateur introuvable'], 404);
        }
        
        Utilitaires::envoyerJSON(['ok' => true, 'user' => $utilisateur]);
    }
}

$controleur = new ControleurConnexion();
$controleur->traiterRequete();
