<?php
/**
 * API d'authentification et gestion des utilisateurs
 * 
 * Actions disponibles :
 * - register : Inscription d'un nouvel utilisateur
 * - login : Connexion utilisateur
 * - logout : Déconnexion
 * - forgot : Demande de réinitialisation du mot de passe
 * - reset : Réinitialisation du mot de passe
 * - me : Récupérer les informations de l'utilisateur connecté
 * - update_profile : Mettre à jour le profil
 * - delete_account : Supprimer le compte
 * - request_email_verification : Demander un lien de vérification email
 * - verify_email : Vérifier l'email avec un token
 * - request_phone_code : Demander un code de vérification téléphone
 * - verify_phone : Vérifier le téléphone avec le code
 */

require __DIR__ . '/config.php';
require __DIR__ . '/CryptoService.php';

// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Récupérer l'action demandée et la méthode HTTP
$action = $_GET['action'] ?? '';
$methodeHTTP = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/**
 * Envoyer une réponse de succès en JSON
 * 
 * @param array $donnees Données à ajouter à la réponse
 * @return void
 */
function reponseSucces($donnees) { 
    envoyerJSON(array_merge(['ok' => true], $donnees)); 
}

/**
 * Envoyer une réponse d'erreur en JSON
 * 
 * @param string $message Message d'erreur
 * @param int $codeStatut Code HTTP (400 par défaut)
 * @return void
 */
function reponseErreur($message, $codeStatut = 400) { 
    envoyerJSON(['error' => $message], $codeStatut); 
}

/**
 * Valider une adresse email
 * 
 * @param mixed $email Email à valider
 * @return bool True si l'email est valide
 */
function emailValide($email) { 
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; 
}

/**
 * Valider un numéro de téléphone
 * 
 * @param mixed $telephone Téléphone à valider
 * @return bool True si le téléphone est valide
 */
function telephoneValide($telephone) { 
    return preg_match('/^[0-9 +().-]{6,}$/', (string)$telephone); 
}

/**
 * Vérifier qu'un mot de passe est suffisamment fort
 * Critères : 8 caractères minimum, au moins 1 minuscule, 1 majuscule, 1 chiffre
 * 
 * @param mixed $motDePasse Mot de passe à valider
 * @return bool True si le mot de passe est fort
 */
function motDePasseFort($motDePasse) { 
    return is_string($motDePasse) 
        && strlen($motDePasse) >= 8 
        && preg_match('/[a-z]/', $motDePasse) 
        && preg_match('/[A-Z]/', $motDePasse) 
        && preg_match('/\d/', $motDePasse); 
}

/**
 * Générer un token aléatoire sécurisé
 * 
 * @param int $longueur Longueur du token (48 par défaut)
 * @return string Token généré
 */
function genererToken($longueur = 48) { 
    return rtrim(strtr(base64_encode(random_bytes($longueur)), '+/', '-_'), '='); 
}

/**
 * Générer un code numérique aléatoire
 * 
 * @param int $nombreChiffres Nombre de chiffres (6 par défaut)
 * @return string Code généré
 */
function genererCode($nombreChiffres = 6) { 
    $minimum = 10 ** ($nombreChiffres - 1); 
    $maximum = (10 ** $nombreChiffres) - 1; 
    return (string)random_int($minimum, $maximum); 
}

try {
    $connexionBDD = obtenirConnexionBDD();

    // ============================================
    // ACTION : register (Inscription)
    // ============================================
    if($methodeHTTP === 'POST' && $action === 'register'){
        // Récupérer les données du formulaire
        $prenom = trim((string)($_POST['first_name'] ?? ''));
        $nom = trim((string)($_POST['last_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $telephone = trim((string)($_POST['phone'] ?? ''));
        $motDePasse = (string)($_POST['password'] ?? '');

        // Validation des données
        if(!chaineValide($prenom, 60) || !chaineValide($nom, 60)) reponseErreur('Nom ou prénom invalide.', 422);
        if(!emailValide($email)) reponseErreur('Email invalide.', 422);
        if(!telephoneValide($telephone)) reponseErreur('Téléphone invalide.', 422);
        if(!motDePasseFort($motDePasse)) reponseErreur('Mot de passe trop faible.', 422);

        // Gestion de l'upload d'avatar (optionnel)
        $cheminAvatar = null;
        if(isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])){
            $fichier = $_FILES['avatar'];
            if($fichier['error'] === UPLOAD_ERR_OK){
                $typeMIME = @mime_content_type($fichier['tmp_name']);
                if(!$typeMIME) reponseErreur('Impossible de détecter le type MIME.', 415);
                if(!in_array($typeMIME, ['image/png','image/jpeg','image/jpg'])) reponseErreur('Type d\'image non supporté.', 422);
                if($fichier['size'] > 2*1024*1024) reponseErreur('Image trop lourde (max 2 Mo).', 422);
                $extension = $typeMIME === 'image/png' ? 'png' : 'jpg';
                $nomFichier = 'avatar_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
                $dossierDestination = defined('UPLOADS_DIR') ? UPLOADS_DIR : (realpath(__DIR__ . '/../public/uploads') ?: __DIR__ . '/../public/uploads');
                if(!is_dir($dossierDestination)) {
                    if(!@mkdir($dossierDestination, 0755, true)) reponseErreur('Impossible de créer le dossier uploads.', 500);
                }
                if(!is_writable($dossierDestination)) reponseErreur('Dossier uploads non inscriptible: ' . $dossierDestination, 500);
                $cheminComplet = rtrim($dossierDestination, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nomFichier;
                if(!@move_uploaded_file($fichier['tmp_name'], $cheminComplet)) reponseErreur('Échec déplacement fichier (permissions?).', 500);
                $cheminAvatar = './uploads/' . $nomFichier;
            } else {
                reponseErreur('Erreur upload (code=' . $fichier['error'] . ').', 400);
            }
        }

        // Hasher le mot de passe et insérer l'utilisateur
        $motDePasseHache = password_hash($motDePasse, PASSWORD_BCRYPT);
        
        // Générer paire de clés RSA pour la messagerie
        $cles = CryptoService::genererPaireCles();
        
        $requetePreparee = $connexionBDD->prepare('INSERT INTO users (first_name,last_name,email,phone,password_hash,avatar_path,public_key,private_key,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())');
        try {
            $requetePreparee->execute([$prenom,$nom,$email,$telephone,$motDePasseHache,$cheminAvatar,$cles['public'],$cles['private']]);
        } catch (Throwable $erreur){
            if($erreur instanceof PDOException && $erreur->getCode()==='23000'){ reponseErreur('Email déjà utilisé.', 409); }
            throw $erreur;
        }
        $idUtilisateur = (int)$connexionBDD->lastInsertId();
        $_SESSION['user'] = ['id'=>$idUtilisateur,'first_name'=>$prenom,'email'=>$email,'avatar_path'=>$cheminAvatar,'role'=>'user'];
        reponseSucces(['user'=>$_SESSION['user']]);
    }

    // ============================================
    // ACTION : login (Connexion)
    // ============================================
    if($methodeHTTP === 'POST' && $action === 'login'){
        $donnees = lireCorpsJSON();
        $email = trim((string)($donnees['email'] ?? ''));
        $motDePasse = (string)($donnees['password'] ?? '');
        if(!$email || !$motDePasse) reponseErreur('Identifiants requis.', 422);
        
        // Rechercher l'utilisateur dans la base de données
        $requetePreparee = $connexionBDD->prepare('SELECT id, first_name, email, avatar_path, password_hash, email_verified_at, phone_verified_at, role FROM users WHERE email = ? LIMIT 1');
        $requetePreparee->execute([$email]);
        $utilisateur = $requetePreparee->fetch();
        
        // Vérifier le mot de passe
        if(!$utilisateur || !password_verify($motDePasse, $utilisateur['password_hash'])) {
            reponseErreur('Email ou mot de passe incorrect.', 401);
        }
        
        // Créer la session utilisateur
        $_SESSION['user'] = [
            'id'=>(int)$utilisateur['id'],
            'first_name'=>$utilisateur['first_name'],
            'email'=>$utilisateur['email'],
            'avatar_path'=>$utilisateur['avatar_path'] ?? null,
            'email_verified_at'=>$utilisateur['email_verified_at'] ?? null,
            'phone_verified_at'=>$utilisateur['phone_verified_at'] ?? null,
            'role'=>$utilisateur['role'] ?? 'user',
        ];
        reponseSucces(['user'=>$_SESSION['user']]);
    }

    // ============================================
    // ACTION : logout (Déconnexion)
    // ============================================
    if($methodeHTTP === 'POST' && $action === 'logout'){
        // Vider la session
        $_SESSION = [];
        
        // Supprimer le cookie de session si activé
        if (ini_get('session.use_cookies')) {
            $parametresCookie = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $parametresCookie['path'], $parametresCookie['domain'],
                $parametresCookie['secure'], $parametresCookie['httponly']
            );
        }
        
        // Détruire la session
        session_destroy();
        reponseSucces([]);
    }

    // ============================================
    // ACTION : forgot (Mot de passe oublié)
    // ============================================
    if($methodeHTTP === 'POST' && $action === 'forgot'){
        $donnees = lireCorpsJSON();
        $email = trim((string)($donnees['email'] ?? ''));
        if(!$email) reponseErreur('Email requis.', 422);
        
        // Rechercher l'utilisateur
        $requetePreparee = $connexionBDD->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $requetePreparee->execute([$email]);
        $utilisateur = $requetePreparee->fetch();
        
        // Répondre toujours "ok" pour éviter l'énumération des utilisateurs
        if(!$utilisateur){ reponseSucces(['message'=>'Si un compte existe, un lien a été généré.']); }
        
        // Générer un token de réinitialisation valide 1 heure
        $jeton = genererToken(24);
        $requetePreparee = $connexionBDD->prepare('INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())');
        $requetePreparee->execute([(int)$utilisateur['id'], $jeton]);
        
        // Construire le lien de réinitialisation
        $lienReinitialisation = (isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['REQUEST_URI'] ?? '/') . '/../connexion?reset=' . urlencode($jeton);
        reponseSucces(['message'=>'Lien généré','reset_link'=>$lienReinitialisation]);
    }

    // ============================================
    // ACTION : reset (Réinitialiser le mot de passe)
    // ============================================
    if($methodeHTTP === 'POST' && $action === 'reset'){
        $donnees = lireCorpsJSON();
        $jeton = trim((string)($donnees['token'] ?? ''));
        $motDePasse = (string)($donnees['password'] ?? '');
        
        if(!$jeton || !motDePasseFort($motDePasse)) reponseErreur('Données invalides.', 422);
        
        // Vérifier que le token existe et n'est pas expiré
        $requetePreparee = $connexionBDD->prepare('SELECT pr.id, pr.user_id FROM password_resets pr WHERE pr.token = ? AND pr.used_at IS NULL AND pr.expires_at > NOW() LIMIT 1');
        $requetePreparee->execute([$jeton]);
        $ligneReset = $requetePreparee->fetch();
        
        if(!$ligneReset) reponseErreur('Lien invalide ou expiré.', 400);
        
        // Mettre à jour le mot de passe dans une transaction
        $motDePasseHache = password_hash($motDePasse, PASSWORD_BCRYPT);
        $connexionBDD->beginTransaction();
        try{
            $connexionBDD->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')->execute([$motDePasseHache, (int)$ligneReset['user_id']]);
            $connexionBDD->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([(int)$ligneReset['id']]);
            $connexionBDD->commit();
        }catch(Throwable $erreur){ 
            $connexionBDD->rollBack(); 
            throw $erreur; 
        }
        reponseSucces(['message'=>'Mot de passe mis à jour.']);
    }

    // ============================================
    // ACTION : me (Récupérer mes informations)
    // ============================================
    if($methodeHTTP === 'GET' && $action === 'me'){
        if(empty($_SESSION['user'])) reponseErreur('Non authentifié.', 401);
        
        $idUtilisateur = (int)$_SESSION['user']['id'];
        $requetePreparee = $connexionBDD->prepare('SELECT id, first_name, last_name, email, phone, avatar_path, email_verified_at, phone_verified_at, role FROM users WHERE id = ?');
        $requetePreparee->execute([$idUtilisateur]);
        $mesInfos = $requetePreparee->fetch();
        
        if(!$mesInfos) reponseErreur('Utilisateur introuvable', 404);
        reponseSucces(['user'=>$mesInfos]);
    }

    // ============================================
    // ACTION : update_profile (Mettre à jour le profil)
    // ============================================
    if($methodeHTTP === 'POST' && $action === 'update_profile'){
        if(empty($_SESSION['user'])) reponseErreur('Non authentifié.', 401);
        
        $idUtilisateur = (int)$_SESSION['user']['id'];
        $prenom = trim((string)($_POST['first_name'] ?? ''));
        $nom = trim((string)($_POST['last_name'] ?? ''));
        $telephone = trim((string)($_POST['phone'] ?? ''));
        
        // Validation
        if(!chaineValide($prenom,60) || !chaineValide($nom,60)) reponseErreur('Nom/prénom invalides.', 422);
        if(!telephoneValide($telephone)) reponseErreur('Téléphone invalide.', 422);
        
        // Gestion de l'upload d'avatar (optionnel)
        $cheminAvatar = null;
        if(isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])){
            $fichier = $_FILES['avatar'];
            if($fichier['error'] === UPLOAD_ERR_OK){
                $typeMIME = @mime_content_type($fichier['tmp_name']);
                if(!$typeMIME) reponseErreur('Impossible de détecter le type MIME.', 415);
                if(!in_array($typeMIME, ['image/png','image/jpeg','image/jpg'])) reponseErreur('Type d\'image non supporté.', 422);
                if($fichier['size'] > 2*1024*1024) reponseErreur('Image trop lourde (max 2 Mo).', 422);
                $extension = $typeMIME === 'image/png' ? 'png' : 'jpg';
                $nomFichier = 'avatar_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
                $dossierDestination = defined('UPLOADS_DIR') ? UPLOADS_DIR : (realpath(__DIR__ . '/../public/uploads') ?: __DIR__ . '/../public/uploads');
                if(!is_dir($dossierDestination)) {
                    if(!@mkdir($dossierDestination, 0755, true)) reponseErreur('Impossible de créer le dossier uploads.', 500);
                }
                if(!is_writable($dossierDestination)) reponseErreur('Dossier uploads non inscriptible: ' . $dossierDestination, 500);
                $cheminComplet = rtrim($dossierDestination, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nomFichier;
                if(!@move_uploaded_file($fichier['tmp_name'], $cheminComplet)) reponseErreur('Échec déplacement fichier (permissions?).', 500);
                $cheminAvatar = './uploads/' . $nomFichier;
            } else {
                reponseErreur('Erreur upload (code=' . $fichier['error'] . ').', 400);
            }
        }
        
        // Mettre à jour le profil dans la base de données
        if($cheminAvatar){
            $requetePreparee = $connexionBDD->prepare('UPDATE users SET first_name=?, last_name=?, phone=?, avatar_path=?, updated_at=NOW() WHERE id=?');
            $requetePreparee->execute([$prenom,$nom,$telephone,$cheminAvatar,$idUtilisateur]);
            $_SESSION['user']['avatar_path'] = $cheminAvatar;
        } else {
            $requetePreparee = $connexionBDD->prepare('UPDATE users SET first_name=?, last_name=?, phone=?, updated_at=NOW() WHERE id=?');
            $requetePreparee->execute([$prenom,$nom,$telephone,$idUtilisateur]);
        }
        $_SESSION['user']['first_name'] = $prenom;
        reponseSucces(['user'=>$_SESSION['user']]);
    }

    // ============================================
    // ACTION : delete_account (Supprimer le compte)
    // ============================================
    if($methodeHTTP === 'POST' && $action === 'delete_account'){
        if(empty($_SESSION['user'])) reponseErreur('Non authentifié.', 401);
        
        $idUtilisateur = (int)$_SESSION['user']['id'];
        
        // Supprimer l'utilisateur de la base de données
        $connexionBDD->prepare('DELETE FROM users WHERE id = ?')->execute([$idUtilisateur]);
        
        // Détruire la session
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parametresCookie = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $parametresCookie['path'], $parametresCookie['domain'],
                $parametresCookie['secure'], $parametresCookie['httponly']
            );
        }
        session_destroy();
        reponseSucces(['deleted'=>true]);
    }

    // ============================================
    // ACTION : request_email_verification (Demander vérification email)
    // ============================================
    if($methodeHTTP === 'POST' && $action === 'request_email_verification'){
        if(empty($_SESSION['user'])) reponseErreur('Non authentifié.', 401);
        
        $idUtilisateur = (int)$_SESSION['user']['id'];
        
        // Générer un token valide 24 heures
        $jeton = genererToken(24);
        $connexionBDD->prepare('INSERT INTO email_verifications (user_id, token, expires_at, created_at) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())')->execute([$idUtilisateur, $jeton]);
        
        // Construire le lien de vérification
        $lienVerification = (isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['REQUEST_URI'] ?? '/') . '/auth.php?action=verify_email&token=' . urlencode($jeton);
        reponseSucces(['verification_link'=>$lienVerification]);
    }

    // ============================================
    // ACTION : verify_email (Vérifier l'email avec token)
    // ============================================
    if($methodeHTTP === 'GET' && $action === 'verify_email'){
        $jeton = trim((string)($_GET['token'] ?? ''));
        if(!$jeton) reponseErreur('Token manquant', 422);
        
        // Vérifier que le token existe et n'est pas expiré
        $requetePreparee = $connexionBDD->prepare('SELECT id, user_id FROM email_verifications WHERE token = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
        $requetePreparee->execute([$jeton]);
        $ligneVerification = $requetePreparee->fetch();
        
        if(!$ligneVerification) reponseErreur('Lien invalide ou expiré.', 400);
        
        // Marquer l'email comme vérifié dans une transaction
        $connexionBDD->beginTransaction();
        try{
            $connexionBDD->prepare('UPDATE users SET email_verified_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([(int)$ligneVerification['user_id']]);
            $connexionBDD->prepare('UPDATE email_verifications SET used_at = NOW() WHERE id = ?')->execute([(int)$ligneVerification['id']]);
            $connexionBDD->commit();
        } catch (Throwable $erreur){ 
            $connexionBDD->rollBack(); 
            throw $erreur; 
        }
        reponseSucces(['message'=>'Email vérifié']);
    }

    // ============================================
    // ACTION : request_phone_code (Demander code téléphone)
    // ============================================
    if($methodeHTTP === 'POST' && $action === 'request_phone_code'){
        if(empty($_SESSION['user'])) reponseErreur('Non authentifié.', 401);
        
        $idUtilisateur = (int)$_SESSION['user']['id'];
        
        // Générer un code à 6 chiffres valide 10 minutes
        $codeVerification = genererCode(6);
        $connexionBDD->prepare('UPDATE users SET phone_code = ?, phone_code_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE), updated_at = NOW() WHERE id = ?')->execute([$codeVerification, $idUtilisateur]);
        
        // En production, ce code serait envoyé par SMS
        // Ici, on le retourne directement pour la démo
        reponseSucces(['code'=>$codeVerification]);
    }

    // ============================================
    // ACTION : verify_phone (Vérifier le téléphone avec code)
    // ============================================
    if($methodeHTTP === 'POST' && $action === 'verify_phone'){
        if(empty($_SESSION['user'])) reponseErreur('Non authentifié.', 401);
        
        $idUtilisateur = (int)$_SESSION['user']['id'];
        $donnees = lireCorpsJSON();
        $codeSaisi = trim((string)($donnees['code'] ?? ''));
        
        if(!$codeSaisi) reponseErreur('Code manquant', 422);
        
        // Récupérer le code stocké et vérifier qu'il correspond
        $requetePreparee = $connexionBDD->prepare('SELECT phone_code, phone_code_expires_at FROM users WHERE id = ?');
        $requetePreparee->execute([$idUtilisateur]);
        $utilisateur = $requetePreparee->fetch();
        
        // Vérifier le code et sa date d'expiration
        if(!$utilisateur || !$utilisateur['phone_code'] || $utilisateur['phone_code'] !== $codeSaisi || strtotime((string)$utilisateur['phone_code_expires_at']) < time()){ 
            reponseErreur('Code invalide ou expiré.', 400); 
        }
        
        // Marquer le téléphone comme vérifié
        $connexionBDD->prepare('UPDATE users SET phone_verified_at = NOW(), phone_code = NULL, phone_code_expires_at = NULL, updated_at = NOW() WHERE id = ?')->execute([$idUtilisateur]);
        reponseSucces(['message'=>'Téléphone vérifié']);
    }

    // Action inconnue
    reponseErreur('Action inconnue', 404);

} catch (Throwable $erreur){
    reponseErreur('Erreur serveur: ' . $erreur->getMessage(), 500);
}
