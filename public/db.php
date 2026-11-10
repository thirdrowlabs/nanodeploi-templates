<?php

// Connects with the DB_* values NanoDeploi writes into the site's .env
// (the MySQL container is reachable as host "mysql").
function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }

    static $file = null;
    if ($file === null) {
        $path = dirname(__DIR__).'/.env';
        $file = is_file($path) ? (parse_ini_file($path, false, INI_SCANNER_RAW) ?: []) : [];
    }

    return isset($file[$key]) ? trim((string) $file[$key], "\"'") : $default;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        env_value('DB_HOST', 'mysql'),
        env_value('DB_PORT', '3306'),
        env_value('DB_DATABASE', 'app'),
    );

    $pdo = new PDO($dsn, env_value('DB_USERNAME', 'app'), env_value('DB_PASSWORD'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 3,
    ]);
    $pdo->exec('CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        notes TEXT NULL,
        done TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) CHARACTER SET utf8mb4');

    return $pdo;
}
