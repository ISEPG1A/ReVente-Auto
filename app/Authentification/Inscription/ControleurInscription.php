<?php

if (session_status() === PHP_SESSION_NONE) session_start();

class ControleurInscription {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleUtilisateur();
    }

    public function traiterRequete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Utils::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
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
        if (!Utils::chaineValide($prenom, 60) || !Utils::chaineValide($nom, 60)) Utils::envoyerJSON(['error' => 'Nom ou prénom invalide.'], 422);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) Utils::envoyerJSON(['error' => 'Email invalide.'], 422);
        if (!preg_match('/^[0-9 +().-]{6,}$/', $telephone)) Utils::envoyerJSON(['error' => 'Téléphone invalide.'], 422);
        
        // Validation mot de passe fort
        if (strlen($motDePasse) < 8 || !preg_match('/[a-z]/', $motDePasse) || !preg_match('/[A-Z]/', $motDePasse) || !preg_match('/\d/', $motDePasse)) {
            Utils::envoyerJSON(['error' => 'Mot de passe trop faible.'], 422);
        }

        // Upload Avatar
        $cheminAvatar = null;
        if (isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
            $fichier = $_FILES['avatar'];
            if ($fichier['error'] === UPLOAD_ERR_OK) {
                $typeMIME = @mime_content_type($fichier['tmp_name']);
                if (in_array($typeMIME, ['image/png', 'image/jpeg', 'image/jpg'])) {
                    $extension = $typeMIME === 'image/png' ? 'png' : 'jpg';
                    $nomFichier = 'avatar_' . time() . '_' . CryptoService::genererToken(6) . '.' . $extension;
                    $dossier = __DIR__ . '/../../../uploads/';
                    if (!is_dir($dossier)) mkdir($dossier, 0755, true);
                    
                    if (move_uploaded_file($fichier['tmp_name'], $dossier . $nomFichier)) {
                        $cheminAvatar = './uploads/' . $nomFichier;
                    }
                }
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
            
            Utils::envoyerJSON(['ok' => true, 'user' => $_SESSION['user']]);
        } catch (Exception $e) {
            Utils::envoyerJSON(['error' => $e->getMessage()], 409);
        }
    }
}

$controleur = new ControleurInscription();
$controleur->traiterRequete();
