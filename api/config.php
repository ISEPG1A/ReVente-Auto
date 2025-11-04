<?php
// ReVente-Auto — Configuration de la base MySQL
// Utilise les variables d'environnement Railway en production, XAMPP en local

// Valeurs par défaut pour développement local (XAMPP)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: 3307);
define('DB_NAME', getenv('DB_NAME') ?: 'revente_auto');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

date_default_timezone_set('Europe/Paris');

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
