<?php

// Long-running background process (a NanoDeploi "worker" process).
// Every 5 seconds it writes a heartbeat the web page can read, because the
// worker container and the web container share the same ./src folder.
$dir = __DIR__.'/storage';
@mkdir($dir, 0775, true);

$startedAt = gmdate('c');
$beats = 0;

while (true) {
    $beats++;
    file_put_contents($dir.'/heartbeat.json', json_encode([
        'pid' => getmypid(),
        'host' => gethostname(),
        'started_at' => $startedAt,
        'beats' => $beats,
        'last_beat' => time(),
    ]), LOCK_EX);

    fwrite(STDOUT, "heartbeat {$beats}\n");
    sleep(5);
}
