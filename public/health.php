<?php

require __DIR__.'/db.php';

header('Content-Type: application/json');

try {
    $tasks = (int) db()->query('SELECT COUNT(*) FROM tasks')->fetchColumn();
    echo json_encode(['app' => '04-php-mysql', 'ok' => true, 'mysql' => 'connected', 'tasks' => $tasks]), "\n";
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['app' => '04-php-mysql', 'ok' => false, 'mysql' => $e->getMessage()]), "\n";
}
