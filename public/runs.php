<?php

/** @return list<int> Unix times of the recent cron runs, newest last. */
function cron_runs(): array
{
    $file = dirname(__DIR__).'/storage/cron-runs.json';

    return is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
}
