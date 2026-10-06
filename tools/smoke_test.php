<?php
declare(strict_types=1);

/**
 * Quick smoke test for DB + auth hash. Run from CLI, then delete on production.
 * C:\xampp\php\php.exe tools\smoke_test.php
 */

require dirname(__DIR__) . '/includes/db.php';
require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/includes/products.php';
require dirname(__DIR__) . '/includes/categories.php';

echo "DB: OK\n";
echo 'Categories: ' . categories_count(true) . "\n";
echo 'Products: ' . products_count(true) . "\n";

$stmt = db()->prepare('SELECT password_hash FROM admins WHERE username = ?');
$stmt->execute(['admin']);
$hash = (string) $stmt->fetchColumn();
echo 'Admin password "admin1234": ' . (password_verify('admin1234', $hash) ? 'OK' : 'FAIL') . "\n";

$p = product_by_slug('modern-armchair', true);
echo 'Sample product: ' . ($p ? $p['name'] : 'MISSING') . "\n";

$glb = dirname(__DIR__) . '/models/modern-armchair.glb';
echo 'Sample GLB: ' . (is_file($glb) ? 'OK (' . filesize($glb) . ' bytes)' : 'MISSING') . "\n";
