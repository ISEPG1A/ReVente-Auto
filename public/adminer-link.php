<?php
// Génère un lien auto-login vers le service Adminer externe (service séparé sur Railway)
// Utilise les variables d'environnement DB_* ou leurs fallback MYSQL*

$host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: '';
$port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: '';
$db   = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: '';
$user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: '';
$pass = getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';

// URL du service Adminer déployé séparément (mettre l'URL réelle Railway de ton service Adminer)
$adminerBase = getenv('ADMINER_SERVICE_URL') ?: 'https://TON-SERVICE-ADMINER.up.railway.app';

// Construction des paramètres GET reconnus par Adminer pour pré-remplir
// (ne soumet pas automatiquement le mot de passe pour raisons de sécurité; clic manuel possible)
$params = [
    'auth[driver]' => 'server',
    'auth[server]' => $port && $host ? ($host . ':' . $port) : $host,
    'auth[username]' => $user,
    'auth[db]' => $db,
    // NOTE: auth[password] peut être inclus; ici on le met seulement si variable ADMINER_INCLUDE_PASSWORD=1
];
if (getenv('ADMINER_INCLUDE_PASSWORD') === '1' && $pass !== '') {
    $params['auth[password]'] = $pass;
}
$query = http_build_query($params);
$link = rtrim($adminerBase, '/') . '/?' . $query;
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<title>Lien rapide Adminer</title>
<style>
body{font-family:system-ui,Segoe UI,Arial,sans-serif;margin:32px;line-height:1.4;background:#f7f7f8;color:#222}
code{background:#eee;padding:2px 4px;border-radius:4px;font-size:90%}
.container{max-width:720px;margin:0 auto}
a.button{display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:10px 16px;border-radius:6px;font-weight:600}
a.button:hover{background:#1d4ed8}
pre{background:#1e1e1e;color:#ddd;padding:12px;border-radius:6px;overflow:auto;font-size:13px}
.alert{padding:12px 16px;border-radius:6px;background:#fff8e1;border:1px solid #f0d067;margin-bottom:18px}
</style>
</head>
<body>
<div class="container">
  <h1>Accès Adminer rapide</h1>
  <p>Ce lien pré-remplit les champs de connexion à la base MySQL fournie par Railway.</p>
  <?php if(!$host || !$db || !$user): ?>
    <div class="alert">Variables manquantes. Assure-toi que le service <strong>ReVente-Auto</strong> reçoit bien les variables (<code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code> ou leurs équivalents <code>MYSQL*</code>).</div>
  <?php endif; ?>
  <p><a class="button" href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener">Ouvrir Adminer avec pré-remplissage</a></p>
  <h2>Paramètres utilisés</h2>
  <pre><?php
  echo 'SERVER=' . ($host ?: '(vide)') . "\n";
  echo 'PORT=' . ($port ?: '(vide)') . "\n";
  echo 'DB=' . ($db ?: '(vide)') . "\n";
  echo 'USER=' . ($user ?: '(vide)') . "\n";
  echo 'PASSWORD=' . (getenv('ADMINER_INCLUDE_PASSWORD')==='1' && $pass ? '*** (inclus)' : 'non inclus') . "\n";
  echo 'Adminer URL=' . $adminerBase . "\n";
  ?></pre>
  <h2>Pour configurer le service Adminer (Docker)</h2>
  <p>Dans Railway, sur le service <strong>Adminer</strong> (image Docker officielle), ajoute les variables suivantes pour auto-préremplir le champ serveur :</p>
  <pre>ADMINER_DEFAULT_SERVER=<?= htmlspecialchars($host) ?><?= $port?':'.htmlspecialchars($port):'' ?>
# (Optionnel) ADMINER_DESIGN=pepa-linha
# (Optionnel) ADMINER_PLUGINS=table-filter</pre>
  <p>Ensuite tu peux utiliser ce lien ci-dessus pour que le formulaire ait déjà serveur, utilisateur et base; il restera juste à valider le mot de passe (ou l'inclure en activant <code>ADMINER_INCLUDE_PASSWORD=1</code> sur le service ReVente-Auto).</p>
  <h2>Sécurité</h2>
  <ul>
    <li>Ne publie pas un mot de passe en clair dans une URL si le site est public.</li>
    <li>Préférez garder <code>ADMINER_INCLUDE_PASSWORD</code> à 0 et retaper le mot de passe manuellement.</li>
    <li>Limite l'accès au service Adminer (whitelist IP ou auth basique) si possible.</li>
  </ul>
</div>
</body>
</html>
