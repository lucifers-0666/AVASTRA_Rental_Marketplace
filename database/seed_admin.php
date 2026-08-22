<?php
declare(strict_types=1);

/**
 * CLI-only: create or reset the admin account.
 *   php database/seed_admin.php              -> password Admin@123
 *   php database/seed_admin.php 'MyPass!23'  -> custom password
 */
if (PHP_SAPI !== 'cli') {
    exit('Run from the command line: php database/seed_admin.php');
}

require_once __DIR__ . '/../config/database.php';

$email    = 'admin@spaceshare.local';
$name     = 'SpaceShare Admin';
$password = $argv[1] ?? 'Admin@123';
$hash     = password_hash($password, PASSWORD_DEFAULT);

Database::run(
    'INSERT INTO users (role_id, name, email, password_hash, status, email_verified_at)
     SELECT r.id, :name, :email, :hash, "active", NOW() FROM roles r WHERE r.name = "admin"
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), status = "active"',
    ['name' => $name, 'email' => $email, 'hash' => $hash]
);

echo "Admin ready — login: {$email}  password: {$password}" . PHP_EOL;
