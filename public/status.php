<?php

function heartbeat(): ?array
{
    $file = dirname(__DIR__).'/storage/heartbeat.json';
    if (! is_file($file)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($file), true);

    return is_array($data) ? $data + ['age' => time() - (int) $data['last_beat']] : null;
}
