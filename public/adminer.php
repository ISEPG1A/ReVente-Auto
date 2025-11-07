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

$adminerUser = getenv('ADMINER_USER') ?: 'adminer';
$adminerPass = getenv('ADMINER_PASS');

if (!$adminerPass) {
    http_response_code(503);
    echo "ADMINER_PASS non défini. Accès désactivé.";
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

// Chemin vers le fichier adminer original (à ajuster si besoin)
$adminerFile = __DIR__ . '/../vendor/adminer/adminer.php';
if (!is_file($adminerFile)) {
    echo "Fichier Adminer manquant. Téléchargez adminer.php depuis https://www.adminer.org et placez-le dans vendor/adminer/adminer.php";
    exit;
}

require $adminerFile;
