<?php
// 404 entrypoint
http_response_code(404);
$title = 'Page introuvable';
$current = '';
$view = __DIR__ . '/../views/pages/404.php';
include __DIR__ . '/../views/layouts/main.php';
