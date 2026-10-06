<?php
declare(strict_types=1);

/**
 * Reset the default admin password to admin1234.
 * Run once after importing sql/database.sql, then delete this file on production.
 */

require dirname(__DIR__) . '/includes/db.php';

$username = 'admin';
$newPassword = 'admin1234';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = db()->prepare('UPDATE admins SET password_hash = ? WHERE username = ?');
$stmt->execute([$hash, $username]);

if ($stmt->rowCount() < 1) {
    $ins = db()->prepare('INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, NOW())');
    $ins->execute([$username, $hash]);
}

echo "Password updated for user '{$username}'. Delete tools/set_admin_password.php when finished.\n";
