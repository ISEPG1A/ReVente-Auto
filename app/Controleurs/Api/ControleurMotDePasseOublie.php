<?php

class ControleurMotDePasseOublie {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleUtilisateur();
    }

    public function traiterRequete() {
        $action = $_GET['action'] ?? '';
        $methode = $_SERVER['REQUEST_METHOD'];

        // Action GET pour afficher la page de reset (avec ou sans action)
        if ($methode === 'GET' && (isset($_GET['token']) || $action === 'reset-password')) {
            $this->afficherPageReset();
            return;
        }

        if ($methode === 'POST' && $action === 'forgot') {
            $this->forgot();
        } elseif ($methode === 'POST' && $action === 'reset') {
            $this->reset();
        } else {
            Utilitaires::envoyerJSON(['erreur' => 'Action non supportée'], 400);
        }
    }

    private function forgot() {
        // 🔒 SÉCURITÉ : Rate limiting 30 secondes entre chaque demande
        if (!GestionnaireLimiteTaux::verifierTentative('password_reset')) {
            Utilitaires::envoyerJSON(['erreur' => 'Veuillez patienter 30 secondes avant de renvoyer une demande.'], 429);
            return;
        }
        
        $donnees = Utilitaires::lireCorpsJSON();
        $email = trim((string)($donnees['email'] ?? ''));
        
        if (!$email) {
            Utilitaires::envoyerJSON(['erreur' => 'Email requis.'], 422);
            return;
        }
        
        $utilisateur = $this->modele->trouverParEmail($email);

        if (!$utilisateur) {
            // Sécurité : ne pas révéler si l'email existe (pas de rate limiting pour les emails inexistants)
            Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Si un compte existe, un email de réinitialisation a été envoyé.']);
            return;
        }

        // Enregistrer la tentative SEULEMENT si on va envoyer un email
        GestionnaireLimiteTaux::ajouterTentative('password_reset');

        $token = ServiceChiffrement::genererToken(24);
        $this->modele->creerTokenReset($utilisateur['id'], $token);

        // Envoyer l'email de réinitialisation
        try {
            ServiceEmail::envoyerResetMotDePasse($email, $utilisateur['first_name'], $token);
            Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Si un compte existe, un email de réinitialisation a été envoyé.']);
        } catch (Exception $e) {
            error_log('Erreur envoi email reset: ' . $e->getMessage());
            Utilitaires::envoyerJSON(['erreur' => 'Erreur lors de l\'envoi de l\'email.'], 500);
        }
    }

    private function reset() {
        $donnees = Utilitaires::lireCorpsJSON();
        
        $token = trim((string)($donnees['token'] ?? ''));
        $motDePasse = (string)($donnees['password'] ?? '');
        $motDePasseConfirm = (string)($donnees['password_confirm'] ?? '');

        // Validation du token
        if (!$token) {
            Utilitaires::envoyerJSON(['erreur' => 'Token manquant.'], 422);
        }
        
        // Validation de la longueur minimale
        if (strlen($motDePasse) < 8) {
            Utilitaires::envoyerJSON(['erreur' => 'Le mot de passe doit contenir au moins 8 caractères.'], 422);
        }
        
        // Validation des critères de sécurité (comme dans ControleurInscription)
        if (!preg_match('/[a-z]/', $motDePasse) || !preg_match('/[A-Z]/', $motDePasse) || !preg_match('/\d/', $motDePasse)) {
            Utilitaires::envoyerJSON(['erreur' => 'Le mot de passe doit contenir des majuscules, des minuscules et des chiffres.'], 422);
        }
        
        // Vérifier que les deux mots de passe correspondent
        if ($motDePasse !== $motDePasseConfirm) {
            Utilitaires::envoyerJSON(['erreur' => 'Les mots de passe ne correspondent pas.'], 422);
        }

        $resetInfo = $this->modele->verifierTokenReset($token);

        if (!$resetInfo) {
            Utilitaires::envoyerJSON(['erreur' => 'Lien invalide ou expiré.'], 400);
        }
        
        // Récupérer l'utilisateur pour vérifier l'ancien mot de passe
        $utilisateur = $this->modele->trouverParId($resetInfo['user_id']);
        
        if (!$utilisateur) {
            Utilitaires::envoyerJSON(['erreur' => 'Utilisateur introuvable.'], 404);
        }
        
        // Vérifier que le nouveau mot de passe est différent de l'ancien
        if (password_verify($motDePasse, $utilisateur['password_hash'])) {
            Utilitaires::envoyerJSON(['erreur' => 'Le nouveau mot de passe doit être différent de l\'ancien.'], 422);
        }

        try {
            $this->modele->mettreAJourMotDePasse($resetInfo['user_id'], $motDePasse, $resetInfo['id']);
            
            // 🔒 SÉCURITÉ : Déconnexion de toutes les sessions après changement de mot de passe
            GestionnaireSession::detruireToutesSessions($resetInfo['user_id']);
            
            Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Mot de passe mis à jour avec succès.']);
        } catch (Exception $e) {
            error_log('Erreur mise à jour mot de passe: ' . $e->getMessage());
            Utilitaires::envoyerJSON(['erreur' => 'Erreur serveur'], 500);
        }
    }
    
    private function afficherPageReset() {
        $token = trim((string)($_GET['token'] ?? ''));
        
        if (!$token) {
            $this->afficherResultatReset(false, 'Token de réinitialisation manquant.');
            return;
        }
        
        // Vérifier que le token est valide
        $resetInfo = $this->modele->verifierTokenReset($token);
        
        if (!$resetInfo) {
            $this->afficherResultatReset(false, 'Ce lien de réinitialisation est invalide, a expiré ou a déjà été utilisé.');
            return;
        }
        
        // Afficher le formulaire de reset avec le token valide
        $this->afficherFormulaireReset($token);
    }
    
    private function afficherFormulaireReset($token) {
        // Calculer le préfixe URL
        $nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $prefixeUrl = strpos($nomScript, '/public/') !== false 
            ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
            : '/';
        
        // Définir les variables pour la vue
        $view = __DIR__ . '/../../../views/pages/auth/reset_mot_de_passe.php';
        $title = 'Réinitialisation du mot de passe - ReVente-Auto';
        $current = '';
        $tokenReset = $token;
        $showResult = false; // Afficher le formulaire
        
        // Charger le layout principal
        require __DIR__ . '/../../../views/layouts/principal.php';
        exit;
    }
    
    private function afficherResultatReset($success, $errorMessage = '') {
        // Calculer le préfixe URL
        $nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $prefixeUrl = strpos($nomScript, '/public/') !== false 
            ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
            : '/';
        
        // Définir les variables pour la vue
        $view = __DIR__ . '/../../../views/pages/auth/reset_mot_de_passe.php';
        $title = $success ? 'Mot de passe modifié - ReVente-Auto' : 'Erreur - ReVente-Auto';
        $current = '';
        $showResult = true; // Afficher le résultat
        
        // Charger le layout principal
        require __DIR__ . '/../../../views/layouts/principal.php';
        exit;
    }
}

$controleur = new ControleurMotDePasseOublie();
$controleur->traiterRequete();
