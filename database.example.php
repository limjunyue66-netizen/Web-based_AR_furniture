<?php
declare(strict_types=1);

/**
 * database.example.php — PDO connection example (documentation only).
 * The live app uses includes/db.php + config/config.php.
 *
 * Copy config/config.example.php → config/config.php and set credentials there.
 * Import schema from: sql/database.sql
 */

$example = [
    'host' => 'localhost',
    'name' => 'ar_furniture',
    'user' => 'YOUR_DB_USER',
    'pass' => 'YOUR_DB_PASSWORD',
    'charset' => 'utf8mb4',
];

// Example only — do not use this file in production.
try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $example['host'],
        $example['name'],
        $example['charset']
    );
    $pdo = new PDO($dsn, $example['user'], $example['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "Connected (example).\n";
} catch (PDOException $e) {
    echo "Connection failed — update credentials in config/config.php instead.\n";
}
