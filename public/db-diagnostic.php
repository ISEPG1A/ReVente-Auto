<?php
// Diagnostic détaillé de connexion MySQL pour Railway
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../api/config.php';

echo "ReVente-Auto | Diagnostic avancé DB\n";
echo "--------------------------------\n";

$vars = [
  'DB_HOST' => getenv('DB_HOST'),
  'DB_PORT' => getenv('DB_PORT'),
  'DB_NAME' => getenv('DB_NAME'),
  'DB_USER' => getenv('DB_USER'),
  'DB_PASS' => getenv('DB_PASS'),
  'MYSQLHOST' => getenv('MYSQLHOST'),
  'MYSQLPORT' => getenv('MYSQLPORT'),
  'MYSQLDATABASE' => getenv('MYSQLDATABASE'),
  'MYSQLUSER' => getenv('MYSQLUSER'),
  'MYSQLPASSWORD' => getenv('MYSQLPASSWORD'),
];
foreach ($vars as $k => $v) {
  $show = ($k === 'DB_PASS' || $k === 'MYSQLPASSWORD') ? ($v ? '***' : '(empty)') : ($v ?: '(not set)');
  echo str_pad($k, 15) . "= $show\n";
}

echo "\nConstantes utilisées par config.php:\n";
echo 'DB_HOST=' . DB_HOST . "\n";
echo 'DB_PORT=' . DB_PORT . "\n";
echo 'DB_NAME=' . DB_NAME . "\n";
echo 'DB_USER=' . DB_USER . "\n";
echo 'DB_PASS=' . (DB_PASS ? '***' : '(empty)') . "\n";

// Test de connexion + info serveur
try {
  $pdo = db();
  echo "\nConnexion PDO: OK\n";
  // Version MySQL
  $version = $pdo->query('SELECT VERSION() v')->fetch()['v'] ?? 'inconnue';
  $now = $pdo->query('SELECT NOW() n')->fetch()['n'] ?? 'inconnue';
  echo "Version serveur: $version\n";
  echo "Heure serveur:  $now\n";
  // Liste bases visibles
  $schemas = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
  echo "Bases visibles: " . implode(', ', $schemas) . "\n";
  // Vérifier si la base souhaitée existe
  $target = DB_NAME;
  echo "Base cible (DB_NAME): $target\n";
  echo in_array($target, $schemas, true) ? "=> Présente\n" : "=> ABSENTE\n";
  // Essayer compter tables
  $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
  echo "Tables dans la base actuelle (si USE automatique): " . ($tables ? implode(', ', $tables) : '(aucune / base non sélectionnée)') . "\n";
} catch (Throwable $e) {
  echo "\nConnexion PDO: ERREUR\n";
  echo 'Message: ' . $e->getMessage() . "\n";
  echo 'Trace: ' . $e->getFile() . ':' . $e->getLine() . "\n";
}

echo "\nActions possibles si base absente:\n";
echo "1. Importer le schéma: utiliser le fichier database/schema.sql via Adminer ou un client mysql.\n";
echo "2. Vérifier que DB_NAME=railway correspond à la base créée (sinon créer CREATE DATABASE railway;).\n";
echo "3. Confirmer que le service ReVente-Auto a bien ces variables dans Railway (pas uniquement dans le service MySQL).\n";
echo "4. Redéployer après modification de variables pour qu'elles soient prises en compte.\n";
