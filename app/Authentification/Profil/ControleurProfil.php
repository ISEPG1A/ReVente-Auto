<?php

if (session_status() === PHP_SESSION_NONE) session_start();

class ControleurProfil {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleUtilisateur();
    }

    public function traiterRequete() {
        $action = $_GET['action'] ?? '';
        $methode = $_SERVER['REQUEST_METHOD'];

        // 'me' action can be called via GET
        if ($methode === 'GET' && $action === 'me') {
            $this->me();
            return;
        }
        
        // 'verify-email' is GET (avec tiret)
        if ($methode === 'GET' && $action === 'verify-email') {
            $this->verifyEmail();
            return;
        }

        if (empty($_SESSION['user'])) {
            Utilitaires::envoyerJSON(['error' => 'Non authentifié.'], 401);
        }

        if ($methode === 'POST' && $action === 'update_profile') {
            $this->updateProfile();
        } elseif ($methode === 'POST' && $action === 'delete_account') {
            $this->deleteAccount();
        } elseif ($methode === 'POST' && $action === 'request_email_verification') {
            $this->requestEmailVerification();
        } elseif ($methode === 'POST' && $action === 'request_password_reset') {
            $this->requestPasswordReset();
        } elseif ($methode === 'POST' && $action === 'request_phone_code') {
            $this->requestPhoneCode();
        } elseif ($methode === 'POST' && $action === 'verify_phone') {
            $this->verifyPhone();
        } else {
            Utilitaires::envoyerJSON(['error' => 'Action non supportée'], 400);
        }
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

    private function updateProfile() {
        $id = (int)$_SESSION['user']['id'];
        $prenom = trim((string)($_POST['first_name'] ?? ''));
        $nom = trim((string)($_POST['last_name'] ?? ''));
        $telephone = trim((string)($_POST['phone'] ?? ''));

        if (!Utilitaires::chaineValide($prenom, 60) || !Utilitaires::chaineValide($nom, 60)) Utilitaires::envoyerJSON(['error' => 'Nom/prénom invalides.'], 422);
        if (!preg_match('/^[0-9 +().-]{6,}$/', $telephone)) Utilitaires::envoyerJSON(['error' => 'Téléphone invalide.'], 422);

        $cheminAvatar = null;
        if (isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
            // Vérifier Rate Limit
            if (!GestionnaireLimiteTaux::verifierTentative('upload')) {
                Utilitaires::envoyerJSON(['error' => 'Limite d\'upload atteinte.'], 429);
            }
            GestionnaireLimiteTaux::ajouterTentative('upload');

            $res = ServiceValidationFichier::deplacerAvatar($_FILES['avatar']);
            if ($res['valide']) {
                $cheminAvatar = $res['chemin'];
            } else {
                Utilitaires::envoyerJSON(['error' => $res['erreur']], 422);
            }
        }

        $this->modele->mettreAJourProfil($id, $prenom, $nom, $telephone, $cheminAvatar);
        
        // Mise à jour session
        $_SESSION['user']['first_name'] = $prenom;
        if ($cheminAvatar) $_SESSION['user']['avatar_path'] = $cheminAvatar;

        Utilitaires::envoyerJSON(['ok' => true, 'user' => $_SESSION['user']]);
    }

    private function deleteAccount() {
        $id = (int)$_SESSION['user']['id'];
        $this->modele->supprimerCompte($id);
        
        GestionnaireSession::detruireSession();
        Utilitaires::envoyerJSON(['ok' => true, 'deleted' => true]);
    }

    private function requestEmailVerification() {
        $id = (int)$_SESSION['user']['id'];
        $utilisateur = $this->modele->trouverParId($id);
        
        if (!$utilisateur) {
            Utilitaires::envoyerJSON(['error' => 'Utilisateur introuvable'], 404);
        }
        
        // Vérifier si l'email n'est pas déjà vérifié
        if (!empty($utilisateur['email_verified_at'])) {
            Utilitaires::envoyerJSON(['error' => 'Votre email est déjà vérifié.'], 400);
        }
        
        // Vérifier le cooldown de 30 secondes
        $dernierToken = $this->modele->obtenirDernierTokenEmail($id);
        if ($dernierToken) {
            $tempsEcoule = time() - strtotime($dernierToken['created_at']);
            if ($tempsEcoule < 30) {
                $tempsRestant = 30 - $tempsEcoule;
                Utilitaires::envoyerJSON(['error' => "Veuillez attendre {$tempsRestant} secondes avant de renvoyer un email.", 'cooldown' => $tempsRestant], 429);
            }
        }
        
        $token = ServiceChiffrement::genererToken(32);
        $this->modele->creerTokenVerificationEmail($id, $token);
        
        // Envoyer l'email de vérification
        try {
            error_log('Tentative d\'envoi email à : ' . $utilisateur['email']);
            ServiceEmail::envoyerVerificationEmail($utilisateur['email'], $utilisateur['first_name'], $token);
            error_log('Email envoyé avec succès à : ' . $utilisateur['email']);
            Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Email de vérification envoyé avec succès.']);
        } catch (Exception $e) {
            error_log('ERREUR envoi email vérification: ' . $e->getMessage());
            Utilitaires::envoyerJSON(['error' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()], 500);
        }
    }

    private function verifyEmail() {
        $token = trim((string)($_GET['token'] ?? ''));
        if (!$token) {
            $this->afficherPageVerification(false, 'Token de vérification manquant.');
            return;
        }

        $verification = $this->modele->verifierTokenEmail($token);
        
        // Token invalide, expiré ou déjà utilisé
        if (!$verification) {
            $this->afficherPageVerification(false, 'Ce lien de vérification est invalide, a expiré ou a déjà été utilisé.');
            return;
        }

        // Valider l'email
        $this->modele->validerEmail($verification['user_id'], $verification['id']);
        
        // Afficher la page de succès avec redirection automatique
        $this->afficherPageVerification(true);
    }
    
    private function afficherPageVerification($success, $errorMessage = '') {
        // Calculer le préfixe URL (nécessaire pour la vue)
        $nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $prefixeUrl = strpos($nomScript, '/public/') !== false 
            ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/public/' 
            : '/';
        
        // Définir toutes les variables AVANT de charger le layout
        // Ces variables seront utilisées par le layout ET la vue
        $view = __DIR__ . '/../../../views/pages/email_verifie.php';
        $title = $success ? 'Email vérifié - ReVente-Auto' : 'Erreur de vérification - ReVente-Auto';
        $current = '';
        
        // Les variables $success, $errorMessage, $prefixeUrl sont déjà définies
        // et seront accessibles dans la vue email_verifie.php
        
        // Charger le layout principal
        require __DIR__ . '/../../../views/layouts/principal.php';
        exit;
    }

    private function requestPhoneCode() {
        $id = (int)$_SESSION['user']['id'];
        $code = ServiceChiffrement::genererCode(6);
        $this->modele->creerCodeTelephone($id, $code);
        Utilitaires::envoyerJSON(['ok' => true, 'code' => $code]);
    }

    private function verifyPhone() {
        $id = (int)$_SESSION['user']['id'];
        $donnees = Utilitaires::lireCorpsJSON();
        $codeSaisi = trim((string)($donnees['code'] ?? ''));

        if (!$codeSaisi) Utilitaires::envoyerJSON(['error' => 'Code manquant'], 422);

        $user = $this->modele->verifierCodeTelephone($id);
        if (!$user || !$user['phone_code'] || $user['phone_code'] !== $codeSaisi || strtotime((string)$user['phone_code_expires_at']) < time()) {
            Utilitaires::envoyerJSON(['error' => 'Code invalide ou expiré.'], 400);
        }

        $this->modele->validerTelephone($id);
        Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Téléphone vérifié']);
    }
    
    private function requestPasswordReset() {
        $id = (int)$_SESSION['user']['id'];
        $utilisateur = $this->modele->trouverParId($id);
        
        if (!$utilisateur) {
            Utilitaires::envoyerJSON(['error' => 'Utilisateur introuvable'], 404);
        }
        
        // Vérifier le cooldown de 30 secondes
        $dernierToken = $this->modele->obtenirDernierTokenReset($id);
        if ($dernierToken) {
            $tempsEcoule = time() - strtotime($dernierToken['created_at']);
            if ($tempsEcoule < 30) {
                $tempsRestant = 30 - $tempsEcoule;
                Utilitaires::envoyerJSON(['error' => "Veuillez attendre {$tempsRestant} secondes avant de renvoyer un email.", 'cooldown' => $tempsRestant], 429);
            }
        }
        
        // Générer un token de réinitialisation
        $token = ServiceChiffrement::genererToken(24);
        $this->modele->creerTokenReset($id, $token);
        
        // Envoyer l'email de réinitialisation
        try {
            ServiceEmail::envoyerResetMotDePasse($utilisateur['email'], $utilisateur['first_name'], $token);
            Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Email de réinitialisation envoyé avec succès.']);
        } catch (Exception $e) {
            error_log('Erreur envoi email reset: ' . $e->getMessage());
            Utilitaires::envoyerJSON(['error' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()], 500);
        }
    }
}

$controleur = new ControleurProfil();
$controleur->traiterRequete();
