<?php

require __DIR__.'/runs.php';

header('Content-Type: application/json');

$runs = cron_runs();
$last = $runs === [] ? null : end($runs);
$ok = $last !== null && time() - $last <= 150;
http_response_code($ok ? 200 : 503);

echo json_encode([
    'app' => '06-php-cron',
    'ok' => $ok,
    'runs' => count($runs),
    'last_run_seconds_ago' => $last === null ? null : time() - $last,
]), "\n";
