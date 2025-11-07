<?php
// Simple endpoint de diagnostic DB pour Railway
// URL: /health-db.php
header('Content-Type: text/plain; charset=utf-8');

function mask($v) {
    if ($v === false || $v === null || $v === '') return '(empty)';
    return '***';
}

echo "ReVente-Auto | DB Health Check\n";
echo "--------------------------------\n";

$vars = [
    'DB_HOST' => getenv('DB_HOST'),
    'DB_PORT' => getenv('DB_PORT'),
    'DB_NAME' => getenv('DB_NAME'),
    'DB_USER' => getenv('DB_USER'),
    'DB_PASS' => getenv('DB_PASS'),
];

echo "Environment variables (sanitized):\n";
echo '  DB_HOST=' . ($vars['DB_HOST'] !== false ? $vars['DB_HOST'] : '(not set)') . "\n";
echo '  DB_PORT=' . ($vars['DB_PORT'] !== false ? $vars['DB_PORT'] : '(not set)') . "\n";
echo '  DB_NAME=' . ($vars['DB_NAME'] !== false ? $vars['DB_NAME'] : '(not set)') . "\n";
echo '  DB_USER=' . ($vars['DB_USER'] !== false ? $vars['DB_USER'] : '(not set)') . "\n";
echo '  DB_PASS=' . mask($vars['DB_PASS']) . "\n\n";

require_once __DIR__ . '/../api/config.php';

try {
    $pdo = db();
    $row = $pdo->query('SELECT NOW() AS now')->fetch();
    echo "DB connection: OK\n";
    echo 'Server time: ' . ($row['now'] ?? 'unknown') . "\n";
    http_response_code(200);
} catch (Throwable $e) {
    http_response_code(500);
    echo "DB connection: ERROR\n";
    echo 'Message: ' . $e->getMessage() . "\n";
}
