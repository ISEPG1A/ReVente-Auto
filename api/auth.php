<?php
require __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function ok($data){ json(array_merge(['ok'=>true], $data)); }
function bad($msg, $code=400){ json(['error'=>$msg], $code); }

function email_valid($e){ return filter_var($e, FILTER_VALIDATE_EMAIL) !== false; }
function phone_valid($p){ return preg_match('/^[0-9 +().-]{6,}$/', (string)$p); }
function password_strong($p){ return is_string($p) && strlen($p) >= 8 && preg_match('/[a-z]/',$p) && preg_match('/[A-Z]/',$p) && preg_match('/\d/',$p); }
function rand_token($len=48){ return rtrim(strtr(base64_encode(random_bytes($len)), '+/','-_'), '='); }
function rand_code($n=6){ $min = 10**($n-1); $max = (10**$n)-1; return (string)random_int($min, $max); }

try{
    $pdo = db();
    if($method === 'POST' && $action === 'register'){
        // Multipart form: first_name, last_name, email, phone, password, avatar
        $first = trim((string)($_POST['first_name'] ?? ''));
        $last  = trim((string)($_POST['last_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');

        if(!str_ok($first, 60) || !str_ok($last, 60)) bad('Nom ou prénom invalide.', 422);
        if(!email_valid($email)) bad('Email invalide.', 422);
        if(!phone_valid($phone)) bad('Téléphone invalide.', 422);
        if(!password_strong($pass)) bad('Mot de passe trop faible.', 422);

        // Handle avatar upload (optional)
        $avatarPath = null;
        if(isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])){
            $f = $_FILES['avatar'];
            if($f['error'] === UPLOAD_ERR_OK){
                $type = mime_content_type($f['tmp_name']);
                if(!in_array($type, ['image/png','image/jpeg','image/jpg'])) bad('Type d\'image non supporté.', 422);
                if($f['size'] > 2*1024*1024) bad('Image trop lourde (max 2 Mo).', 422);
                $ext = $type === 'image/png' ? 'png' : 'jpg';
                $name = 'avatar_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $destDir = realpath(__DIR__ . '/../public/uploads');
                if(!$destDir){ $destDir = __DIR__ . '/../public/uploads'; }
                $dest = $destDir . DIRECTORY_SEPARATOR . $name;
                if(!move_uploaded_file($f['tmp_name'], $dest)) bad('Upload échoué.', 500);
                $avatarPath = './assets/../uploads/' . $name; // relative path from public
                $avatarPath = './uploads/' . $name;
            } else {
                bad('Erreur upload.', 400);
            }
        }

        // Insert user
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (first_name,last_name,email,phone,password_hash,avatar_path,created_at,updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())');
        try {
            $stmt->execute([$first,$last,$email,$phone,$hash,$avatarPath]);
        } catch (Throwable $e){
            if($e instanceof PDOException && $e->getCode()==='23000'){ bad('Email déjà utilisé.', 409); }
            throw $e;
        }
        $id = (int)$pdo->lastInsertId();
    $_SESSION['user'] = ['id'=>$id,'first_name'=>$first,'email'=>$email,'avatar_path'=>$avatarPath,'role'=>'user'];
        ok(['user'=>$_SESSION['user']]);
    }

    if($method === 'POST' && $action === 'login'){
        $data = read_json_body();
        $email = trim((string)($data['email'] ?? ''));
        $pass  = (string)($data['password'] ?? '');
        if(!$email || !$pass) bad('Identifiants requis.', 422);
        $stmt = $pdo->prepare('SELECT id, first_name, email, avatar_path, password_hash, email_verified_at, phone_verified_at FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if(!$u || !password_verify($pass, $u['password_hash'])) bad('Email ou mot de passe incorrect.', 401);
        $_SESSION['user'] = [
            'id'=>(int)$u['id'],
            'first_name'=>$u['first_name'],
            'email'=>$u['email'],
            'avatar_path'=>$u['avatar_path'] ?? null,
            'email_verified_at'=>$u['email_verified_at'] ?? null,
            'phone_verified_at'=>$u['phone_verified_at'] ?? null,
            'role'=>$u['role'] ?? 'user',
        ];
        ok(['user'=>$_SESSION['user']]);
    }

    if($method === 'POST' && $action === 'logout'){
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        ok([]);
    }

    if($method === 'POST' && $action === 'forgot'){
        $data = read_json_body();
        $email = trim((string)($data['email'] ?? ''));
        if(!$email) bad('Email requis.', 422);
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        // Always respond ok even if not found to avoid user enumeration
        if(!$u){ ok(['message'=>'Si un compte existe, un lien a été généré.']); }
        $token = rand_token(24);
        $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())');
        $stmt->execute([(int)$u['id'], $token]);
        $resetLink = (isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['REQUEST_URI'] ?? '/') . '/../connexion?reset=' . urlencode($token);
        ok(['message'=>'Lien généré','reset_link'=>$resetLink]);
    }

    if($method === 'POST' && $action === 'reset'){
        $data = read_json_body();
        $token = trim((string)($data['token'] ?? ''));
        $pass  = (string)($data['password'] ?? '');
        if(!$token || !password_strong($pass)) bad('Données invalides.', 422);
        $stmt = $pdo->prepare('SELECT pr.id, pr.user_id FROM password_resets pr WHERE pr.token = ? AND pr.used_at IS NULL AND pr.expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        if(!$row) bad('Lien invalide ou expiré.', 400);
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $pdo->beginTransaction();
        try{
            $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')->execute([$hash, (int)$row['user_id']]);
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([(int)$row['id']]);
            $pdo->commit();
        }catch(Throwable $e){ $pdo->rollBack(); throw $e; }
        ok(['message'=>'Mot de passe mis à jour.']);
    }

    if($method === 'GET' && $action === 'me'){
        if(empty($_SESSION['user'])) bad('Non authentifié.', 401);
        $id = (int)$_SESSION['user']['id'];
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, email, phone, avatar_path, email_verified_at, phone_verified_at, role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $me = $stmt->fetch();
        if(!$me) bad('Utilisateur introuvable', 404);
        ok(['user'=>$me]);
    }

    if($method === 'POST' && $action === 'update_profile'){
        if(empty($_SESSION['user'])) bad('Non authentifié.', 401);
        $id = (int)$_SESSION['user']['id'];
        $first = trim((string)($_POST['first_name'] ?? ''));
        $last  = trim((string)($_POST['last_name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        if(!str_ok($first,60) || !str_ok($last,60)) bad('Nom/prénom invalides.', 422);
        if(!phone_valid($phone)) bad('Téléphone invalide.', 422);
        $avatarPath = null;
        if(isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])){
            $f = $_FILES['avatar'];
            if($f['error'] === UPLOAD_ERR_OK){
                $type = mime_content_type($f['tmp_name']);
                if(!in_array($type, ['image/png','image/jpeg','image/jpg'])) bad('Type d\'image non supporté.', 422);
                if($f['size'] > 2*1024*1024) bad('Image trop lourde (max 2 Mo).', 422);
                $ext = $type === 'image/png' ? 'png' : 'jpg';
                $name = 'avatar_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $destDir = realpath(__DIR__ . '/../public/uploads');
                if(!$destDir){ $destDir = __DIR__ . '/../public/uploads'; }
                $dest = $destDir . DIRECTORY_SEPARATOR . $name;
                if(!move_uploaded_file($f['tmp_name'], $dest)) bad('Upload échoué.', 500);
                $avatarPath = './uploads/' . $name;
            } else {
                bad('Erreur upload.', 400);
            }
        }
        if($avatarPath){
            $stmt = $pdo->prepare('UPDATE users SET first_name=?, last_name=?, phone=?, avatar_path=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$first,$last,$phone,$avatarPath,$id]);
            $_SESSION['user']['avatar_path'] = $avatarPath;
        } else {
            $stmt = $pdo->prepare('UPDATE users SET first_name=?, last_name=?, phone=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$first,$last,$phone,$id]);
        }
        $_SESSION['user']['first_name'] = $first;
        ok(['user'=>$_SESSION['user']]);
    }

    if($method === 'POST' && $action === 'delete_account'){
        if(empty($_SESSION['user'])) bad('Non authentifié.', 401);
        $id = (int)$_SESSION['user']['id'];
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        // logout
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        ok(['deleted'=>true]);
    }

    // Email verification flow
    if($method === 'POST' && $action === 'request_email_verification'){
        if(empty($_SESSION['user'])) bad('Non authentifié.', 401);
        $id = (int)$_SESSION['user']['id'];
        // Generate token valid for 24h
        $token = rand_token(24);
        $pdo->prepare('INSERT INTO email_verifications (user_id, token, expires_at, created_at) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())')->execute([$id, $token]);
        $link = (isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['REQUEST_URI'] ?? '/') . '/auth.php?action=verify_email&token=' . urlencode($token);
        ok(['verification_link'=>$link]);
    }

    if($method === 'GET' && $action === 'verify_email'){
        $token = trim((string)($_GET['token'] ?? ''));
        if(!$token) bad('Token manquant', 422);
        $stmt = $pdo->prepare('SELECT id, user_id FROM email_verifications WHERE token = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        if(!$row) bad('Lien invalide ou expiré.', 400);
        $pdo->beginTransaction();
        try{
            $pdo->prepare('UPDATE users SET email_verified_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([(int)$row['user_id']]);
            $pdo->prepare('UPDATE email_verifications SET used_at = NOW() WHERE id = ?')->execute([(int)$row['id']]);
            $pdo->commit();
        } catch (Throwable $e){ $pdo->rollBack(); throw $e; }
        ok(['message'=>'Email vérifié']);
    }

    // Phone verification flow (simulate SMS by returning code)
    if($method === 'POST' && $action === 'request_phone_code'){
        if(empty($_SESSION['user'])) bad('Non authentifié.', 401);
        $id = (int)$_SESSION['user']['id'];
        $code = rand_code(6);
        $pdo->prepare('UPDATE users SET phone_code = ?, phone_code_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE), updated_at = NOW() WHERE id = ?')->execute([$code, $id]);
        ok(['code'=>$code]);
    }

    if($method === 'POST' && $action === 'verify_phone'){
        if(empty($_SESSION['user'])) bad('Non authentifié.', 401);
        $id = (int)$_SESSION['user']['id'];
        $data = read_json_body();
        $code = trim((string)($data['code'] ?? ''));
        if(!$code) bad('Code manquant', 422);
        $stmt = $pdo->prepare('SELECT phone_code, phone_code_expires_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if(!$u || !$u['phone_code'] || $u['phone_code'] !== $code || strtotime((string)$u['phone_code_expires_at']) < time()){ bad('Code invalide ou expiré.', 400); }
        $pdo->prepare('UPDATE users SET phone_verified_at = NOW(), phone_code = NULL, phone_code_expires_at = NULL, updated_at = NOW() WHERE id = ?')->execute([$id]);
        ok(['message'=>'Téléphone vérifié']);
    }

    // Not found
    bad('Action inconnue', 404);

} catch (Throwable $e){
    bad('Erreur serveur: ' . $e->getMessage(), 500);
}
