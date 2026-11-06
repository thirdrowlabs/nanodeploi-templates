<?php

require __DIR__.'/status.php';

header('Content-Type: application/json');

$beat = heartbeat();
$ok = $beat !== null && $beat['age'] <= 30;
http_response_code($ok ? 200 : 503);

echo json_encode(['app' => '05-php-worker', 'ok' => $ok, 'worker' => $beat]), "\n";
