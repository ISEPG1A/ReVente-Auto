<?php

if (session_status() === PHP_SESSION_NONE) session_start();

class ControleurInscription {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleUtilisateur();
    }

    public function traiterRequete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
        }

        $this->register();
    }

    private function register() {
        $prenom = trim((string)($_POST['first_name'] ?? ''));
        $nom = trim((string)($_POST['last_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $telephone = trim((string)($_POST['phone'] ?? ''));
        $motDePasse = (string)($_POST['password'] ?? '');

        // Validation
        if (!Utilitaires::chaineValide($prenom, 60) || !Utilitaires::chaineValide($nom, 60)) Utilitaires::envoyerJSON(['error' => 'Nom ou prénom invalide.'], 422);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) Utilitaires::envoyerJSON(['error' => 'Email invalide.'], 422);
        if (!preg_match('/^[0-9 +().-]{6,}$/', $telephone)) Utilitaires::envoyerJSON(['error' => 'Téléphone invalide.'], 422);
        
        // Validation mot de passe fort
        if (strlen($motDePasse) < 8 || !preg_match('/[a-z]/', $motDePasse) || !preg_match('/[A-Z]/', $motDePasse) || !preg_match('/\d/', $motDePasse)) {
            Utilitaires::envoyerJSON(['error' => 'Mot de passe trop faible.'], 422);
        }

        // Upload Avatar
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
                // On peut choisir d'ignorer l'erreur d'avatar ou de bloquer l'inscription
                // Ici on ignore mais on pourrait loguer l'erreur
            }
        }

        try {
            $id = $this->modele->creer($prenom, $nom, $email, $telephone, $motDePasse, $cheminAvatar);
            
            $_SESSION['user'] = [
                'id' => $id,
                'first_name' => $prenom,
                'email' => $email,
                'avatar_path' => $cheminAvatar,
                'role' => 'user'
            ];
            
            Utilitaires::envoyerJSON(['ok' => true, 'user' => $_SESSION['user']]);
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['error' => $e->getMessage()], 409);
        }
    }
}

$controleur = new ControleurInscription();
$controleur->traiterRequete();
