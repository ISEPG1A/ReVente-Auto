<?php

// Charger les variables depuis le fichier .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parser la ligne KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Définir la variable d'environnement si elle n'existe pas déjà
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

return [
    'db_host' => getenv('DB_HOST'),
    'db_port' => getenv('DB_PORT'),
    'db_name' => getenv('DB_NAME'),
    'db_user' => getenv('DB_USER'),
    'db_pass' => getenv('DB_PASS'),
    'app_secret_key' => getenv('APP_SECRET_KEY'),
    'openai_api_key' => getenv('OPENAI_API_KEY')
];
