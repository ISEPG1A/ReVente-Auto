<?php
// ReVente-Auto — Configuration de la base MySQL
// Utilise les variables d'environnement Railway en production, XAMPP en local

// Valeurs par défaut pour développement local (XAMPP) + fallback Railway (MYSQL*)
$envHost = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: '127.0.0.1';
$envPort = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: 3307;
$envName = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'revente_auto';
$envUser = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: 'root';
$envPass = getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';

define('DB_HOST', $envHost);
define('DB_PORT', $envPort);
define('DB_NAME', $envName);
define('DB_USER', $envUser);
define('DB_PASS', $envPass);

date_default_timezone_set('Europe/Paris');

// ------------------------------------------------------------
// Chargement facultatif d'un fichier .env en local (XAMPP)
// ------------------------------------------------------------
function _load_local_env(string $path): void {
    if (!is_file($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $ln) {
        if (str_starts_with(trim($ln), '#')) continue;
        $parts = explode('=', $ln, 2);
        if (count($parts) !== 2) continue;
        [$k, $v] = $parts;
        $k = trim($k); $v = trim($v, " \"'\r\n");
        if ($k !== '' && getenv($k) === false) { putenv("$k=$v"); }
    }
}

// Charger .env local si présent (ne surcharge pas ce que Railway fournit)
_load_local_env(__DIR__ . '/../.env');

// ------------------------------------------------------------
// Détection environnement (APP_ENV=production|local)
// ------------------------------------------------------------
$appEnv = getenv('APP_ENV') ?: (getenv('RAILWAY_ENVIRONMENT') ? 'production' : 'local');
define('APP_ENV', $appEnv);
define('IS_PROD', APP_ENV === 'production');

// ------------------------------------------------------------
// Configuration base de données multi-environnements
// Priorité: variables explicites DB_* puis fallback Railway MYSQL*
// Local (XAMPP) : defaults 127.0.0.1:3307 base revente_auto
// ------------------------------------------------------------
$envHost = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: '127.0.0.1';
$defaultLocalPort = '3307'; // adapter si ton XAMPP écoute sur 3306
$envPort = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: ($appEnv === 'local' ? $defaultLocalPort : '3306');
$envName = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'revente_auto';
$envUser = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: 'root';
$envPass = getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';

define('DB_HOST', $envHost);
define('DB_PORT', $envPort);
define('DB_NAME', $envName);
define('DB_USER', $envUser);
define('DB_PASS', $envPass);

// ------------------------------------------------------------
// Dossier des uploads (volume en production / dossier local)
// Railway: monter un volume sur /var/www/html/public/uploads
// Local: simplement le dossier ./public/uploads
// Personnalisable via UPLOADS_DIR
// ------------------------------------------------------------
$uploadsDir = getenv('UPLOADS_DIR') ?: __DIR__ . '/../public/uploads';
if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0755, true); }
if (!is_writable($uploadsDir)) { @chmod($uploadsDir, 0755); }
define('UPLOADS_DIR', realpath($uploadsDir) ?: $uploadsDir);

// ------------------------------------------------------------
// Affichage des erreurs (désactivé en production)
// ------------------------------------------------------------
if (IS_PROD) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '1');
    ini_set('error_reporting', (string)(E_ALL));
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // Les variables d'environnement sont déjà définies dans les constantes
        $host = DB_HOST;
        $name = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;
        $port = (int)DB_PORT;

        $pdo = _db_try_connect($host, $port, $name, $user, $pass);

        if ($pdo === null) {
            throw new RuntimeException('Impossible de se connecter à la base de données. Vérifie hôte/port/utilisateur/mot de passe.');
        }
    }
    return $pdo;
}

function _db_try_connect(string $host, int $port, string $name, string $user, string $pass): ?PDO {
    try {
        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, $user, $pass, $opts);
    } catch (Throwable $e) {
        return null;
    }
}

function json($data, int $status = 200): void {
    // Purger tout output parasite (warnings, BOM) pour éviter JSON.parse errors côté client
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_end_clean(); }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function str_ok(?string $s, int $max = 50): bool {
    return is_string($s) && $s !== '' && mb_strlen($s) <= $max;
}

function int_between($n, int $min, int $max): bool {
    return filter_var($n, FILTER_VALIDATE_INT) !== false && $n >= $min && $n <= $max;
}

function num_min($n, float $min): bool {
    return filter_var($n, FILTER_VALIDATE_FLOAT) !== false && $n >= $min;
}
