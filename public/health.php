<?php

require __DIR__.'/db.php';

header('Content-Type: application/json');

try {
    $count = (int) db()->query('SELECT COUNT(*) FROM visits')->fetchColumn();
    echo json_encode(['app' => '04-php-mysql', 'ok' => true, 'mysql' => 'connected', 'visits' => $count]), "\n";
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['app' => '04-php-mysql', 'ok' => false, 'mysql' => $e->getMessage()]), "\n";
}
