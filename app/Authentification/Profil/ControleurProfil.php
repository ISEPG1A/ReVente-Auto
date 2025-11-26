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
        
        // 'verify_email' is GET
        if ($methode === 'GET' && $action === 'verify_email') {
            $this->verifyEmail();
            return;
        }

        if (empty($_SESSION['user'])) {
            Utils::envoyerJSON(['error' => 'Non authentifié.'], 401);
        }

        if ($methode === 'POST' && $action === 'update_profile') {
            $this->updateProfile();
        } elseif ($methode === 'POST' && $action === 'delete_account') {
            $this->deleteAccount();
        } elseif ($methode === 'POST' && $action === 'request_email_verification') {
            $this->requestEmailVerification();
        } elseif ($methode === 'POST' && $action === 'request_phone_code') {
            $this->requestPhoneCode();
        } elseif ($methode === 'POST' && $action === 'verify_phone') {
            $this->verifyPhone();
        } else {
            Utils::envoyerJSON(['error' => 'Action non supportée'], 400);
        }
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

    private function updateProfile() {
        $id = (int)$_SESSION['user']['id'];
        $prenom = trim((string)($_POST['first_name'] ?? ''));
        $nom = trim((string)($_POST['last_name'] ?? ''));
        $telephone = trim((string)($_POST['phone'] ?? ''));

        if (!Utils::chaineValide($prenom, 60) || !Utils::chaineValide($nom, 60)) Utils::envoyerJSON(['error' => 'Nom/prénom invalides.'], 422);
        if (!preg_match('/^[0-9 +().-]{6,}$/', $telephone)) Utils::envoyerJSON(['error' => 'Téléphone invalide.'], 422);

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

        $this->modele->mettreAJourProfil($id, $prenom, $nom, $telephone, $cheminAvatar);
        
        // Mise à jour session
        $_SESSION['user']['first_name'] = $prenom;
        if ($cheminAvatar) $_SESSION['user']['avatar_path'] = $cheminAvatar;

        Utils::envoyerJSON(['ok' => true, 'user' => $_SESSION['user']]);
    }

    private function deleteAccount() {
        $id = (int)$_SESSION['user']['id'];
        $this->modele->supprimerCompte($id);
        
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        Utils::envoyerJSON(['ok' => true, 'deleted' => true]);
    }

    private function requestEmailVerification() {
        $id = (int)$_SESSION['user']['id'];
        $token = CryptoService::genererToken(24);
        $this->modele->creerTokenVerificationEmail($id, $token);
        
        // Construire le lien (à adapter selon votre structure d'URL)
        // On pointe vers le contrôleur directement pour la vérification
        $baseUrl = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        
        // On veut pointer vers l'API
        // Si on est dans /test/ReVente-Auto/public/index.php, on veut /test/ReVente-Auto/api/profil
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']); // /test/ReVente-Auto/public
        $apiPath = str_replace('/public', '/api', $scriptPath);
        
        $lienVerification = $baseUrl . $apiPath . '/profil?action=verify_email&token=' . urlencode($token);
        
        Utils::envoyerJSON(['ok' => true, 'verification_link' => $lienVerification]);
    }

    private function verifyEmail() {
        $token = trim((string)($_GET['token'] ?? ''));
        if (!$token) Utils::envoyerJSON(['error' => 'Token manquant'], 422);

        $verification = $this->modele->verifierTokenEmail($token);
        if (!$verification) Utils::envoyerJSON(['error' => 'Lien invalide ou expiré.'], 400);

        $this->modele->validerEmail($verification['user_id'], $verification['id']);
        
        // Redirection ou message JSON
        // Si c'est un appel API direct, JSON. Si c'est un clic lien, on devrait rediriger vers une page de succès.
        // Pour simplifier ici, on renvoie JSON, mais idéalement on redirige vers /parametres?verified=1
        echo "Email vérifié avec succès. Vous pouvez fermer cette page.";
        exit;
    }

    private function requestPhoneCode() {
        $id = (int)$_SESSION['user']['id'];
        $code = CryptoService::genererCode(6);
        $this->modele->creerCodeTelephone($id, $code);
        Utils::envoyerJSON(['ok' => true, 'code' => $code]);
    }

    private function verifyPhone() {
        $id = (int)$_SESSION['user']['id'];
        $donnees = Utils::lireCorpsJSON();
        $codeSaisi = trim((string)($donnees['code'] ?? ''));

        if (!$codeSaisi) Utils::envoyerJSON(['error' => 'Code manquant'], 422);

        $user = $this->modele->verifierCodeTelephone($id);
        if (!$user || !$user['phone_code'] || $user['phone_code'] !== $codeSaisi || strtotime((string)$user['phone_code_expires_at']) < time()) {
            Utils::envoyerJSON(['error' => 'Code invalide ou expiré.'], 400);
        }

        $this->modele->validerTelephone($id);
        Utils::envoyerJSON(['ok' => true, 'message' => 'Téléphone vérifié']);
    }
}

$controleur = new ControleurProfil();
$controleur->traiterRequete();
