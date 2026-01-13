<?php

if (session_status() === PHP_SESSION_NONE) session_start();

class ControleurInscription {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleUtilisateur();
    }

    public function traiterRequete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Utilitaires::envoyerJSON(['erreur' => 'Méthode non autorisée'], 405);
        }

        $this->register();
    }

    private function register() {
        $prenom = trim((string)($_POST['first_name'] ?? ''));
        $nom = trim((string)($_POST['last_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $telephone = trim((string)($_POST['phone'] ?? ''));
        $motDePasse = (string)($_POST['password'] ?? '');
        $acceptCgu = isset($_POST['accept_cgu']) && $_POST['accept_cgu'] === 'on';
        
        // Vérification de l'acceptation des CGU (obligatoire)
        if (!$acceptCgu) {
            Utilitaires::envoyerJSON(['erreur' => 'Vous devez accepter les Conditions Générales d\'Utilisation pour créer un compte.'], 422);
            return;
        }

        // Validation détaillée
        if (!Utilitaires::chaineValide($prenom, 60)) {
            Utilitaires::envoyerJSON(['erreur' => 'Le prénom est invalide ou trop long (max 60 caractères).'], 422);
            return;
        }
        if (!Utilitaires::chaineValide($nom, 60)) {
            Utilitaires::envoyerJSON(['erreur' => 'Le nom est invalide ou trop long (max 60 caractères).'], 422);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Utilitaires::envoyerJSON(['erreur' => 'L\'adresse email n\'est pas valide. Format attendu : exemple@domaine.com'], 422);
            return;
        }
        if (!preg_match('/^[0-9 +().-]{6,}$/', $telephone)) {
            Utilitaires::envoyerJSON(['erreur' => 'Le numéro de téléphone est invalide. Utilisez uniquement des chiffres, espaces et + - ( )'], 422);
            return;
        }
        
        // Validation mot de passe fort avec messages détaillés
        if (strlen($motDePasse) < 8) {
            Utilitaires::envoyerJSON(['erreur' => 'Le mot de passe doit contenir au moins 8 caractères.'], 422);
            return;
        }
        if (!preg_match('/[a-z]/', $motDePasse)) {
            Utilitaires::envoyerJSON(['erreur' => 'Le mot de passe doit contenir au moins une lettre minuscule (a-z).'], 422);
            return;
        }
        if (!preg_match('/[A-Z]/', $motDePasse)) {
            Utilitaires::envoyerJSON(['erreur' => 'Le mot de passe doit contenir au moins une lettre majuscule (A-Z).'], 422);
            return;
        }
        if (!preg_match('/\d/', $motDePasse)) {
            Utilitaires::envoyerJSON(['erreur' => 'Le mot de passe doit contenir au moins un chiffre (0-9).'], 422);
            return;
        }

        // Upload Avatar
        $cheminAvatar = null;
        if (isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
            // Vérifier Rate Limit
            if (!GestionnaireLimiteTaux::verifierTentative('upload')) {
                Utilitaires::envoyerJSON(['erreur' => 'Limite d\'upload atteinte.'], 429);
            }
            GestionnaireLimiteTaux::ajouterTentative('upload');

            // Note: pas d'ID utilisateur car pas encore créé
            $res = ServiceValidationFichier::deplacerAvatar($_FILES['avatar']);
            if ($res['valide']) {
                $cheminAvatar = $res['chemin'];
            } else {
                // On peut choisir d'ignorer l'erreur d'avatar ou de bloquer l'inscription
                // Ici on ignore mais on pourrait loguer l'erreur
            }
        }

        try {
            $id = $this->modele->creer($prenom, $nom, $email, $telephone, $motDePasse, $cheminAvatar);
            
            // Logger l'inscription dans admin_logs
            try {
                $modeleAdmin = new ModeleAdmin();
                $modeleAdmin->ajouterLog(
                    'inscription',
                    'Nouvel utilisateur inscrit',
                    [
                        'prenom' => $prenom,
                        'nom' => $nom,
                        'email' => $email
                    ],
                    $id,
                    null,
                    null
                );
            } catch (Exception $logError) {
                error_log('Erreur log inscription: ' . $logError->getMessage());
            }
            
            // Vérifier si l'email n'est pas déjà vérifié avant d'envoyer
            $utilisateur = $this->modele->trouverParId($id);
            if (empty($utilisateur['email_verified_at'])) {
                // Générer et envoyer le token de vérification email uniquement si non vérifié
                try {
                    $tokenVerification = ServiceChiffrement::genererToken(32);
                    $this->modele->creerTokenVerificationEmail($id, $tokenVerification);
                    ServiceEmail::envoyerVerificationEmail($email, $prenom, $tokenVerification);
                } catch (Exception $e) {
                    // Continuer même si l'envoi d'email échoue
                    error_log('ERREUR envoi email vérification inscription: ' . $e->getMessage());
                }
            }
            
            $_SESSION['user'] = [
                'id' => $id,
                'first_name' => $prenom,
                'email' => $email,
                'avatar_path' => $cheminAvatar,
                'role' => 'user'
            ];
            
            Utilitaires::envoyerJSON(['ok' => true, 'user' => $_SESSION['user'], 'message' => 'Inscription réussie ! Un email de vérification vous a été envoyé.']);
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['erreur' => $e->getMessage()], 409);
        }
    }
}

$controleur = new ControleurInscription();
$controleur->traiterRequete();
