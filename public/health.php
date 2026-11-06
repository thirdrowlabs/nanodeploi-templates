<?php

header('Content-Type: application/json');

echo json_encode([
    'app' => '03-php-page',
    'ok' => true,
    'php' => PHP_VERSION,
    'https' => ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
]), "\n";
