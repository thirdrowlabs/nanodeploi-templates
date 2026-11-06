<?php

// Runs once per call. NanoDeploi's "cron" process calls it on a schedule
// (every minute) and it records the time, keeping the last 20 runs.
$dir = __DIR__.'/storage';
@mkdir($dir, 0775, true);
$file = $dir.'/cron-runs.json';

$runs = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
$runs[] = time();
$runs = array_slice($runs, -20);

file_put_contents($file, json_encode($runs), LOCK_EX);
fwrite(STDOUT, 'cron ran at '.gmdate('c')."\n");
