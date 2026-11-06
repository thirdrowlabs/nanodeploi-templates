<?php

// Each page view adds a row. The count should survive redeploys, because the
// MySQL data lives in a Docker volume, not in the app container.
require __DIR__.'/db.php';

try {
    $pdo = db();
    $pdo->exec('INSERT INTO visits (visited_at) VALUES (UTC_TIMESTAMP())');
    $count = (int) $pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn();
    $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    $message = "This page has been viewed {$count} times (MySQL {$version}).";
} catch (Throwable $e) {
    http_response_code(500);
    $message = 'Could not reach MySQL: '.$e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo 04 · PHP + MySQL</title>
</head>
<body style="font-family: system-ui, sans-serif; max-width: 40rem; margin: 4rem auto; padding: 0 1rem">
    <h1>PHP talks to MySQL</h1>
    <p><?= htmlspecialchars($message) ?></p>
    <p>Reload to count up. Redeploy, and the count should continue.</p>
</body>
</html>
