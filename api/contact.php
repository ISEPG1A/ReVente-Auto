<?php
/**
 * API Contact – réception et traitement des messages envoyés par le formulaire.
 * Méthode: POST
 * Retour: JSON { ok: true, sent: bool, logged: bool }
 */
require __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($m !== 'POST') {
    envoyerJSON(['error' => 'Méthode non autorisée'], 405);
}

// Accepter multipart/form-data (FormData) ou JSON
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $data = lireCorpsJSON();
} else {
    $data = $_POST; // FormData
}

// Honeypot anti-spam
if (!empty($data['website'])) envoyerJSON(['error' => 'Spam détecté'], 422);

// CSRF token
$csrfSession = $_SESSION['contact_csrf'] ?? null;
$csrfRecu = $data['csrf'] ?? '';
if (!$csrfSession || !hash_equals($csrfSession, (string)$csrfRecu)) {
    envoyerJSON(['error' => 'Jeton CSRF invalide'], 403);
}

// Collecte / validation
$nom     = trim((string)($data['name'] ?? ''));
$email   = trim((string)($data['email'] ?? ''));
$sujet   = trim((string)($data['subject'] ?? ''));
$message = trim((string)($data['message'] ?? ''));

$erreurs = [];
if ($nom === '' || mb_strlen($nom) > 100) $erreurs[] = 'Nom invalide';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Email invalide';
if ($sujet === '' || mb_strlen($sujet) > 150) $erreurs[] = 'Sujet invalide';
if ($message === '' || mb_strlen($message) < 10) $erreurs[] = 'Message trop court';
if ($erreurs) envoyerJSON(['error' => implode('. ', $erreurs)], 422);

// Construction du courriel
$destinataire = 'contact@revente-auto.example'; // À adapter en production
$entetes = [];
$entetes[] = 'From: "' . str_replace(['"'], '', $nom) . '" <' . $email . '>';
$entetes[] = 'Reply-To: ' . $email;
$entetes[] = 'Content-Type: text/plain; charset=utf-8';
$corps = "Message reçu via formulaire Contact:\n" .
         "Nom: $nom\n" .
         "Email: $email\n" .
         "Sujet: $sujet\n" .
         "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'inconnue') . "\n" .
         "-----------------------------\n\n" . $message . "\n";

$sent = false; $logged = false;
// Tentative d'envoi
try {
    // La fonction mail peut être désactivée sous XAMPP; on ne bloque pas si échec.
    $sent = @mail($destinataire, $sujet, $corps, implode("\r\n", $entetes));
} catch (Throwable $e) {
    $sent = false;
}

// Fallback log si non envoyé ou pour traçabilité
$logDir = realpath(__DIR__ . '/../logs') ?: (__DIR__ . '/../logs');
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
$logFile = rtrim($logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'contact.log';
$ligneLog = '[' . date('c') . '] ' . json_encode([
    'name' => $nom,
    'email' => $email,
    'subject' => $sujet,
    'sent' => $sent,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? null
], JSON_UNESCAPED_UNICODE) . "\n";
if (@file_put_contents($logFile, $ligneLog, FILE_APPEND)) $logged = true;

envoyerJSON(['ok' => true, 'sent' => $sent, 'logged' => $logged]);