<?php
/**
 * Wrapper sécurisé pour Adminer.
 * Ne versionnez PAS le fichier adminer.php original dans Git.
 * Téléchargez la dernière version depuis https://www.adminer.org (adminer.php)
 * Placez-la par exemple dans vendor/adminer/adminer.php
 *
 * Protection: Authentification HTTP Basic.
 * Configurez deux variables d'environnement sur Railway:
 *   ADMINER_USER  (ex: adminer)
 *   ADMINER_PASS  (mot de passe fort)
 *
 * Désactivez l'accès en production si non nécessaire (renommez/retirez la route).
 */

// Récupération des identifiants d'accès au wrapper
$adminerUser = getenv('ADMINER_USER') ?: 'adminer';
$adminerPass = getenv('ADMINER_PASS');

// Détection des variables de base de données (Railway expose parfois MYSQLHOST/MYSQLUSER ...)
$dbHost = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: '';
$dbPort = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: '';
$dbName = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: '';
$dbUser = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: '';
$dbPass = getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';

if (!$adminerPass) {
    http_response_code(503);
    echo "ADMINER_PASS non défini. Accès désactivé.\n";
    echo "Ajoutez ADMINER_PASS dans les variables d'environnement du service pour activer l'accès.";
    exit;
}

// Vérification HTTP Basic
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Adminer"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentification requise';
    exit;
}
if ($_SERVER['PHP_AUTH_USER'] !== $adminerUser || $_SERVER['PHP_AUTH_PW'] !== $adminerPass) {
    header('WWW-Authenticate: Basic realm="Adminer"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Accès refusé';
    exit;
}

// Mode debug: /adminer?debug=1 pour visualiser les variables d'environnement détectées
if (isset($_GET['debug'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Adminer wrapper debug\n";
    echo "ADMINER_USER=$adminerUser\n";
    echo "DB_HOST=$dbHost\nDB_PORT=$dbPort\nDB_NAME=$dbName\nDB_USER=$dbUser\nDB_PASS=" . ($dbPass ? '***' : '(vide)') . "\n";
    echo "Chemin fichier Adminer: $adminerFile\n";
    echo is_file($adminerFile) ? "Fichier présent\n" : "Fichier ABSENT\n";
    exit;
}

// Auto-préremplissage du formulaire d'authentification Adminer (si ?auto et variables disponibles)
if ($dbHost && $dbName && $dbUser && isset($_GET['auto'])) {
    $server = $dbPort ? ($dbHost . ':' . $dbPort) : $dbHost;
    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Auto login Adminer</title></head><body>'
        . '<form id="f" method="post" action="">'
        . '<input type="hidden" name="auth[driver]" value="server">'
        . '<input type="hidden" name="auth[server]" value="'.htmlspecialchars($server, ENT_QUOTES).'">'
        . '<input type="hidden" name="auth[username]" value="'.htmlspecialchars($dbUser, ENT_QUOTES).'">'
        . '<input type="hidden" name="auth[password]" value="'.htmlspecialchars($dbPass, ENT_QUOTES).'">'
        . '<input type="hidden" name="auth[db]" value="'.htmlspecialchars($dbName, ENT_QUOTES).'">'
        . '</form><script>document.getElementById("f").submit();</script></body></html>';
    echo $html; exit;
}

// Chemin vers le fichier adminer original (à ajuster si besoin)
$adminerFile = __DIR__ . '/../vendor/adminer/adminer.php';
if (!is_file($adminerFile)) {
    http_response_code(500);
    echo "Fichier Adminer manquant.\n";
    echo "1. Télécharger https://www.adminer.org/adminer.php\n";
    echo "2. Créer dossier vendor/adminer/\n";
    echo "3. Placer le fichier sous vendor/adminer/adminer.php\n";
    echo "4. Recharger /adminer\n";
    exit;
}

require $adminerFile;
