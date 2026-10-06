<?php
declare(strict_types=1);

/**
 * PDO database connection singleton.
 */

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/config.php';
        if (!empty($config['app']['timezone'])) {
            date_default_timezone_set($config['app']['timezone']);
        }
    }
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = app_config()['db'];
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['name'],
        $cfg['charset'] ?? 'utf8mb4'
    );

    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        throw new RuntimeException('Database unavailable. Please check the database configuration.');
    }

    return $pdo;
}
