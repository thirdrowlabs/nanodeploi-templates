<?php

require __DIR__.'/status.php';

$beat = heartbeat();
$alive = $beat !== null && $beat['age'] <= 30;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="5">
    <title>Demo 05 · Background worker</title>
</head>
<body style="font-family: system-ui, sans-serif; max-width: 40rem; margin: 4rem auto; padding: 0 1rem">
    <h1>Background worker: <?= $alive ? 'running' : 'not running' ?></h1>
    <?php if ($beat === null) { ?>
        <p>No heartbeat yet. Check that the worker process is added and the deploy finished.</p>
    <?php } else { ?>
        <p>Last heartbeat <?= (int) $beat['age'] ?>s ago (beat #<?= (int) $beat['beats'] ?>, worker container <?= htmlspecialchars($beat['host']) ?>, started <?= htmlspecialchars($beat['started_at']) ?>).</p>
        <p>After a redeploy, "started" should change: the worker was restarted with the new code.</p>
    <?php } ?>
</body>
</html>
