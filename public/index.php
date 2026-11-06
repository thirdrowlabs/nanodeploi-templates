<?php

// Shows what the PHP container sees for this request, including the headers
// Traefik adds (real client IP, original scheme). Checks HTTPS and proxying.
$https = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || ! empty($_SERVER['HTTPS']);

$rows = [
    'PHP version' => PHP_VERSION,
    'Host' => $_SERVER['HTTP_HOST'] ?? '-',
    'Scheme seen by browser' => $https ? 'https' : 'http',
    'Client IP (X-Forwarded-For)' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '-',
    'Server time (UTC)' => gmdate('Y-m-d H:i:s'),
    'Container hostname' => gethostname(),
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo 03 · PHP page</title>
</head>
<body style="font-family: system-ui, sans-serif; max-width: 40rem; margin: 4rem auto; padding: 0 1rem">
    <h1>PHP is running</h1>
    <table cellpadding="6">
        <?php foreach ($rows as $label => $value) { ?>
            <tr><th align="left"><?= htmlspecialchars($label) ?></th><td><?= htmlspecialchars((string) $value) ?></td></tr>
        <?php } ?>
    </table>
    <p><a href="/health.php">health.php</a></p>
</body>
</html>
