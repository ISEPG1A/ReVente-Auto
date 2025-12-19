<?php

return [
    // Variables d'environnement prioritaires, sinon valeurs locales par défaut
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_port' => getenv('DB_PORT') ?: 3307,
    'db_name' => getenv('DB_NAME') ?: 'revente_auto',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    'app_secret_key' => getenv('APP_SECRET_KEY') ?: 'd0a7e3b2f1c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0',
    'openai_api_key' => getenv('OPENAI_API_KEY') ?: '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',
];
