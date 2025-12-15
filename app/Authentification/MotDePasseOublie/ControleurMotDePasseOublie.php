<?php

if (session_status() === PHP_SESSION_NONE) session_start();

class ControleurMotDePasseOublie {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleUtilisateur();
    }

    public function traiterRequete() {
        $action = $_GET['action'] ?? '';
        $methode = $_SERVER['REQUEST_METHOD'];

        if ($methode === 'POST' && $action === 'forgot') {
            $this->forgot();
        } elseif ($methode === 'POST' && $action === 'reset') {
            $this->reset();
        } else {
            Utilitaires::envoyerJSON(['error' => 'Action non supportée'], 400);
        }
    }

    private function forgot() {
        $donnees = Utilitaires::lireCorpsJSON();
        $email = trim((string)($donnees['email'] ?? ''));
        
        if (!$email) Utilitaires::envoyerJSON(['error' => 'Email requis.'], 422);

        $utilisateur = $this->modele->trouverParEmail($email);

        if (!$utilisateur) {
            // Sécurité : ne pas révéler si l'email existe
            Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Si un compte existe, un lien a été généré.']);
        }

        $token = ServiceChiffrement::genererToken(24);
        $this->modele->creerTokenReset($utilisateur['id'], $token);

        // Construction du lien (à adapter selon votre routing)
        $lien = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . 
                ($_SERVER['HTTP_HOST'] ?? 'localhost') . 
                dirname($_SERVER['REQUEST_URI'] ?? '/') . '/../../../connexion?reset=' . urlencode($token);

        Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Lien généré', 'reset_link' => $lien]);
    }

    private function reset() {
        $donnees = Utilitaires::lireCorpsJSON();
        $token = trim((string)($donnees['token'] ?? ''));
        $motDePasse = (string)($donnees['password'] ?? '');

        if (!$token || strlen($motDePasse) < 8) {
            Utilitaires::envoyerJSON(['error' => 'Données invalides.'], 422);
        }

        $resetInfo = $this->modele->verifierTokenReset($token);

        if (!$resetInfo) {
            Utilitaires::envoyerJSON(['error' => 'Lien invalide ou expiré.'], 400);
        }

        try {
            $this->modele->mettreAJourMotDePasse($resetInfo['user_id'], $motDePasse, $resetInfo['id']);
            Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Mot de passe mis à jour.']);
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['error' => 'Erreur serveur'], 500);
        }
    }
}

$controleur = new ControleurMotDePasseOublie();
$controleur->traiterRequete();
