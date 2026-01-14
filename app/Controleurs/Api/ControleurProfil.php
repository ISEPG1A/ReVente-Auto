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
        
        // 'confirm-email-change' is GET - confirmation du changement d'email
        if ($methode === 'GET' && $action === 'confirm-email-change') {
            $this->confirmEmailChange();
            return;
        }

        if (empty($_SESSION['user'])) {
            Utilitaires::envoyerJSON(['erreur' => 'Non authentifié.'], 401);
            return;
        }

        if ($methode === 'POST' && $action === 'update_profile') {
            $this->updateProfile();
        } elseif ($methode === 'POST' && $action === 'delete_account') {
            $this->deleteAccount();
        } elseif ($methode === 'POST' && $action === 'request_email_verification') {
            $this->requestEmailVerification();
        } elseif ($methode === 'POST' && $action === 'request_password_reset') {
            $this->requestPasswordReset();
        } elseif ($methode === 'POST' && $action === 'request_email_change') {
            $this->requestEmailChange();
        } elseif ($methode === 'POST' && $action === 'toggle_hide_phone') {
            $this->toggleHidePhone();
        } else {
            Utilitaires::envoyerJSON(['erreur' => 'Action non supportée'], 400);
            return;
        }
    }

    private function me() {
        if (empty($_SESSION['user'])) {
            Utilitaires::envoyerJSON(['erreur' => 'Non authentifié.'], 401);
            return;
        }
        
        $utilisateur = $this->modele->trouverParId((int)$_SESSION['user']['id']);
        if (!$utilisateur) {
            Utilitaires::envoyerJSON(['erreur' => 'Utilisateur introuvable'], 404);
            return;
        }
        
        // Construire l'URL complète de l'avatar si disponible
        if (!empty($utilisateur['avatar_path'])) {
            $utilisateur['avatar_url'] = $utilisateur['avatar_path'];
        }
        
        Utilitaires::envoyerJSON(['ok' => true, 'user' => $utilisateur]);
    }

    private function updateProfile() {
        $id = (int)$_SESSION['user']['id'];
        $prenom = trim((string)($_POST['first_name'] ?? ''));
        $nom = trim((string)($_POST['last_name'] ?? ''));
        $telephone = trim((string)($_POST['phone'] ?? ''));

        if (!Utilitaires::chaineValide($prenom, 60) || !Utilitaires::chaineValide($nom, 60)) {
            Utilitaires::envoyerJSON(['erreur' => 'Nom/prénom invalides.'], 422);
            return;
        }
        if (!preg_match('/^[0-9 +().-]{6,}$/', $telephone)) {
            Utilitaires::envoyerJSON(['erreur' => 'Téléphone invalide.'], 422);
            return;
        }

        $cheminAvatar = null;
        if (isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
            // Vérifier Rate Limit
            if (!GestionnaireLimiteTaux::verifierTentative('upload')) {
                Utilitaires::envoyerJSON(['erreur' => 'Limite d\'upload atteinte.'], 429);
                return;
            }
            GestionnaireLimiteTaux::ajouterTentative('upload');

            // 🗑️ Supprimer l'ancien avatar avant d'uploader le nouveau
            // Récupérer depuis la BDD pour être sûr d'avoir la dernière valeur
            $utilisateur = $this->modele->trouverParId($id);
            
            if (!empty($utilisateur['avatar_path'])) {
                $racineProjet = dirname(__DIR__, 3);  // Profil -> Authentification -> app -> racine
                $ancienAvatar = $racineProjet . '/public/' . $utilisateur['avatar_path'];
                
                if (file_exists($ancienAvatar)) {
                    unlink($ancienAvatar);
                }
            }

            $userId = $_SESSION['user']['id'];
            $res = ServiceValidationFichier::deplacerAvatar($_FILES['avatar'], $userId);
            if ($res['valide']) {
                $cheminAvatar = $res['chemin'];
                error_log("📸 Nouveau avatar uploadé: " . $cheminAvatar);
            } else {
                Utilitaires::envoyerJSON(['erreur' => $res['erreur']], 422);
                return;
            }
        }

        $this->modele->mettreAJourProfil($id, $prenom, $nom, $telephone, $cheminAvatar);
        
        // Log modification profil
        try {
            $modeleAdmin = new ModeleAdmin();
            $modeleAdmin->ajouterLog('profil', 'Modification profil', [
                'prenom' => $prenom,
                'nom' => $nom,
                'avatar_modifie' => $cheminAvatar !== null
            ], $id, null, null);
        } catch (Exception $logError) {
            error_log('Erreur log profil: ' . $logError->getMessage());
        }
        
        // Mise à jour session
        $_SESSION['user']['first_name'] = $prenom;
        if ($cheminAvatar) $_SESSION['user']['avatar_path'] = $cheminAvatar;

        Utilitaires::envoyerJSON(['ok' => true, 'user' => $_SESSION['user']]);
        return;
    }

    private function deleteAccount() {
        $id = (int)$_SESSION['user']['id'];
        
        try {
            $this->modele->supprimerCompte($id);
            GestionnaireSession::detruireSession();
            Utilitaires::envoyerJSON(['ok' => true, 'deleted' => true]);
            return;
        } catch (Exception $e) {
            error_log('Erreur suppression compte: ' . $e->getMessage());
            Utilitaires::envoyerJSON(['erreur' => 'Erreur lors de la suppression du compte: ' . $e->getMessage()], 500);
            return;
        }
    }

    private function requestEmailVerification() {
        $id = (int)$_SESSION['user']['id'];
        $utilisateur = $this->modele->trouverParId($id);
        
        if (!$utilisateur) {
            Utilitaires::envoyerJSON(['erreur' => 'Utilisateur introuvable'], 404);
            return;
        }
        
        // Vérifier si l'email n'est pas déjà vérifié
        if (!empty($utilisateur['email_verified_at'])) {
            Utilitaires::envoyerJSON(['erreur' => 'Votre email est déjà vérifié.'], 400);
            return;
        }
        
        // Vérifier le rate limiting (30 secondes)
        if (!GestionnaireLimiteTaux::verifierTentative('email_verification')) {
            $tempsRestant = GestionnaireLimiteTaux::obtenirTempsRestant('email_verification');
            Utilitaires::envoyerJSON(['erreur' => "Veuillez attendre {$tempsRestant} secondes avant de renvoyer un email.", 'cooldown' => $tempsRestant], 429);
            return;
        }
        
        $token = ServiceChiffrement::genererToken(32);
        
        // Ajouter tentative au rate limiter
        GestionnaireLimiteTaux::ajouterTentative('email_verification');
        
        // Créer le token en BDD
        $this->modele->creerTokenVerificationEmail($id, $token);
        
        // Envoyer l'email de vérification
        try {
            ServiceEmail::envoyerVerificationEmail($utilisateur['email'], $utilisateur['first_name'], $token);
            Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Email de vérification envoyé avec succès.']);
            return;
        } catch (Exception $e) {
            error_log('ERREUR envoi email vérification: ' . $e->getMessage());
            Utilitaires::envoyerJSON(['erreur' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()], 500);
            return;
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
        
        // Mettre à jour la session si l'utilisateur est connecté et que c'est son compte
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $verification['user_id']) {
            $_SESSION['email_verified'] = true;
        }
        
        // Afficher la page de succès avec redirection automatique
        $this->afficherPageVerification(true);
    }
    
    private function afficherPageVerification($success, $errorMessage = '') {
        // Calculer le préfixe URL (nécessaire pour la vue)
        $nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $prefixeUrl = strpos($nomScript, '/public/') !== false 
            ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
            : '/';
        
        // Définir toutes les variables AVANT de charger le layout
        // Ces variables seront utilisées par le layout ET la vue
        $view = __DIR__ . '/../../../views/pages/auth/email_verifie.php';
        $title = $success ? 'Email vérifié - ReVente-Auto' : 'Erreur de vérification - ReVente-Auto';
        $current = '';
        
        // Les variables $success, $errorMessage, $prefixeUrl sont déjà définies
        // et seront accessibles dans la vue email_verifie.php
        
        // Charger le layout principal
        require __DIR__ . '/../../../views/layouts/principal.php';
        exit;
    }
    
    private function requestPasswordReset() {
        $id = (int)$_SESSION['user']['id'];
        $utilisateur = $this->modele->trouverParId($id);
        
        if (!$utilisateur) {
            Utilitaires::envoyerJSON(['erreur' => 'Utilisateur introuvable'], 404);
            return;
        }
        
        // Vérifier le rate limiting (30 secondes)
        if (!GestionnaireLimiteTaux::verifierTentative('password_reset')) {
            $tempsRestant = GestionnaireLimiteTaux::obtenirTempsRestant('password_reset');
            Utilitaires::envoyerJSON(['erreur' => "Veuillez attendre {$tempsRestant} secondes avant de renvoyer un email.", 'cooldown' => $tempsRestant], 429);
            return;
        }
        
        // Générer un token de réinitialisation
        $token = ServiceChiffrement::genererToken(24);
        $this->modele->creerTokenReset($id, $token);
        
        // Ajouter tentative au rate limiter
        GestionnaireLimiteTaux::ajouterTentative('password_reset');
        
        // Envoyer l'email de réinitialisation
        try {
            ServiceEmail::envoyerResetMotDePasse($utilisateur['email'], $utilisateur['first_name'], $token);
            
            // Log demande reset mot de passe
            try {
                $modeleAdmin = new ModeleAdmin();
                $modeleAdmin->ajouterLog('securite', 'Demande réinitialisation mot de passe', [
                    'email' => $utilisateur['email']
                ], $id, null, null);
            } catch (Exception $logError) {
                error_log('Erreur log reset password: ' . $logError->getMessage());
            }
            
            Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Email de réinitialisation envoyé avec succès.']);
            return;
        } catch (Exception $e) {
            error_log('Erreur envoi email reset: ' . $e->getMessage());
            Utilitaires::envoyerJSON(['erreur' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()], 500);
            return;
        }
    }

    // =============================================
    // CHANGEMENT D'ADRESSE EMAIL
    // =============================================

    /**
     * Demande de changement d'email - envoie un email de confirmation au nouvel email
     */
    private function requestEmailChange() {
        $id = (int)$_SESSION['user']['id'];
        $utilisateur = $this->modele->trouverParId($id);
        
        if (!$utilisateur) {
            Utilitaires::envoyerJSON(['erreur' => 'Utilisateur introuvable'], 404);
            return;
        }
        
        // Récupérer le nouvel email depuis le corps de la requête
        $donnees = Utilitaires::lireCorpsJSON();
        $nouvelEmail = trim((string)($donnees['new_email'] ?? ''));
        
        // Validation de l'email
        if (!Utilitaires::emailValide($nouvelEmail)) {
            Utilitaires::envoyerJSON(['erreur' => 'Adresse email invalide.'], 422);
            return;
        }
        
        // Vérifier que ce n'est pas le même email
        if (strtolower($nouvelEmail) === strtolower($utilisateur['email'])) {
            Utilitaires::envoyerJSON(['erreur' => 'Cette adresse est déjà votre email actuel.'], 400);
            return;
        }
        
        // Vérifier que l'email n'est pas déjà utilisé par un autre compte
        if ($this->modele->emailDejaUtilise($nouvelEmail, $id)) {
            Utilitaires::envoyerJSON(['erreur' => 'Cette adresse email est déjà utilisée par un autre compte.'], 409);
            return;
        }
        
        // Vérifier le rate limiting (60 secondes)
        if (!GestionnaireLimiteTaux::verifierTentative('email_change')) {
            $tempsRestant = GestionnaireLimiteTaux::obtenirTempsRestant('email_change');
            Utilitaires::envoyerJSON(['erreur' => "Veuillez attendre {$tempsRestant} secondes avant de renvoyer un email.", 'cooldown' => $tempsRestant], 429);
            return;
        }
        
        // Générer un token de changement d'email
        $token = ServiceChiffrement::genererToken(32);
        $this->modele->creerTokenChangementEmail($id, $nouvelEmail, $token);
        
        // Ajouter tentative au rate limiter
        GestionnaireLimiteTaux::ajouterTentative('email_change');
        
        // Envoyer l'email de confirmation au NOUVEL email UNIQUEMENT
        try {
            ServiceEmail::envoyerConfirmationChangementEmail($nouvelEmail, $utilisateur['first_name'], $token);
            
            // Log demande changement email
            $modeleAdmin = new ModeleAdmin();
            $modeleAdmin->ajouterLog('securite', 'Demande changement email', [
                'ancien_email' => $utilisateur['email'],
                'nouvel_email' => $nouvelEmail
            ], $id, null, null);
            
            Utilitaires::envoyerJSON([
                'ok' => true, 
                'message' => 'Un email de confirmation a été envoyé à ' . $nouvelEmail . '. Veuillez cliquer sur le lien pour valider le changement.'
            ]);
            return;
        } catch (Exception $e) {
            error_log('Erreur envoi email changement: ' . $e->getMessage());
            Utilitaires::envoyerJSON(['erreur' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()], 500);
            return;
        }
    }

    /**
     * Confirmation du changement d'email (clic sur le lien dans l'email)
     */
    private function confirmEmailChange() {
        $token = trim((string)($_GET['token'] ?? ''));
        
        if (!$token) {
            $this->afficherPageChangementEmail(false, 'Token de confirmation manquant.');
            return;
        }

        $changement = $this->modele->verifierTokenChangementEmail($token);
        
        // Token invalide, expiré ou déjà utilisé
        if (!$changement) {
            $this->afficherPageChangementEmail(false, 'Ce lien de confirmation est invalide, a expiré ou a déjà été utilisé.');
            return;
        }

        // Vérifier une dernière fois que l'email n'est pas pris
        if ($this->modele->emailDejaUtilise($changement['nouvel_email'])) {
            $this->afficherPageChangementEmail(false, 'Cette adresse email est maintenant utilisée par un autre compte.');
            return;
        }

        // Appliquer le changement
        try {
            $this->modele->appliquerChangementEmail($changement['id_utilisateur'], $changement['nouvel_email'], $changement['id']);
            
            // Log changement email confirmé
            try {
                $modeleAdmin = new ModeleAdmin();
                $modeleAdmin->ajouterLog('securite', 'Email changé', [
                    'nouvel_email' => $changement['nouvel_email']
                ], $changement['id_utilisateur'], null, null);
            } catch (Exception $logError) {
                error_log('Erreur log changement email: ' . $logError->getMessage());
            }
            
            // Mettre à jour la session si l'utilisateur est connecté
            if (!empty($_SESSION['user']) && $_SESSION['user']['id'] == $changement['id_utilisateur']) {
                $_SESSION['user']['email'] = $changement['nouvel_email'];
                // Mettre à jour email_verified_at pour que l'utilisateur garde ses droits
                $_SESSION['user']['email_verified_at'] = date('Y-m-d H:i:s');
            }
            
            $this->afficherPageChangementEmail(true, '', $changement['nouvel_email']);
        } catch (Exception $e) {
            error_log('Erreur changement email: ' . $e->getMessage());
            $this->afficherPageChangementEmail(false, 'Une erreur est survenue lors du changement d\'email.');
        }
    }

    /**
     * Affiche la page de résultat du changement d'email
     */
    private function afficherPageChangementEmail($success, $errorMessage = '', $newEmail = '') {
        // Calculer le préfixe URL
        $nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $prefixeUrl = strpos($nomScript, '/public/') !== false 
            ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
            : '/';
        
        $view = __DIR__ . '/../../../views/pages/auth/email_change_confirme.php';
        $title = $success ? 'Email modifié - ReVente-Auto' : 'Erreur - ReVente-Auto';
        $current = '';
        
        require __DIR__ . '/../../../views/layouts/principal.php';
        exit;
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // MASQUAGE DU NUMÉRO DE TÉLÉPHONE
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Active ou désactive le masquage du numéro de téléphone sur les annonces
     */
    private function toggleHidePhone(): void {
        // Vérifier la connexion
        if (empty($_SESSION['user']['id'])) {
            Utilitaires::envoyerJSON(['error' => 'Non authentifié'], 401);
            return;
        }
        
        $id = $_SESSION['user']['id'];
        
        // Récupérer les données
        $input = json_decode(file_get_contents('php://input'), true);
        $hidePhone = isset($input['hide_phone']) ? (int)$input['hide_phone'] : 0;
        
        // Validation
        if ($hidePhone !== 0 && $hidePhone !== 1) {
            Utilitaires::envoyerJSON(['error' => 'Valeur invalide'], 400);
            return;
        }
        
        try {
            // Mettre à jour en base de données
            $resultat = $this->modele->mettreAJourMasquageTelephone($id, $hidePhone);
            
            if (!$resultat) {
                throw new Exception('Erreur lors de la mise à jour');
            }
            
            // Mettre à jour la session
            $_SESSION['user']['hide_phone'] = $hidePhone;
            
            Utilitaires::envoyerJSON([
                'success' => true,
                'message' => $hidePhone 
                    ? 'Numéro de téléphone masqué sur vos annonces' 
                    : 'Numéro de téléphone visible sur vos annonces',
                'hide_phone' => $hidePhone
            ]);
            
        } catch (Exception $e) {
            error_log('Erreur toggle_hide_phone: ' . $e->getMessage());
            Utilitaires::envoyerJSON(['error' => 'Une erreur est survenue'], 500);
        }
    }
}

$controleur = new ControleurProfil();
$controleur->traiterRequete();
