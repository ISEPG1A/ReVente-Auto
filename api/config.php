<?php
// Configuration XAMPP
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3307);
define('DB_NAME', 'ultra_app');
define('DB_USER', 'root');
define('DB_PASS', '');

date_default_timezone_set('Europe/Paris');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Erreur connexion base de données: ' . $e->getMessage());
        }
    }
    return $pdo;
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
