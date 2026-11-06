<?php

require __DIR__.'/runs.php';

$runs = array_reverse(cron_runs());
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="30">
    <title>Demo 06 · Scheduled job</title>
</head>
<body style="font-family: system-ui, sans-serif; max-width: 40rem; margin: 4rem auto; padding: 0 1rem">
    <h1>Scheduled job (cron)</h1>
    <?php if ($runs === []) { ?>
        <p>No runs yet. The first one happens within a minute of the deploy finishing.</p>
    <?php } else { ?>
        <p>Runs should appear about one minute apart:</p>
        <ul>
            <?php foreach ($runs as $time) { ?>
                <li><?= gmdate('Y-m-d H:i:s', (int) $time) ?> UTC</li>
            <?php } ?>
        </ul>
    <?php } ?>
</body>
</html>
