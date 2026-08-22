<?php
declare(strict_types=1);

/**
 * SpaceShare — global configuration.
 * Defaults target XAMPP. To override without editing this file,
 * create config/local.php (git-ignored) and define constants there.
 */

if (file_exists(__DIR__ . '/local.php')) {
    require_once __DIR__ . '/local.php';
}

defined('DB_HOST') or define('DB_HOST', getenv('SPACESHARE_DB_HOST') ?: '127.0.0.1');
defined('DB_NAME') or define('DB_NAME', getenv('SPACESHARE_DB_NAME') ?: 'spaceshare');
defined('DB_USER') or define('DB_USER', getenv('SPACESHARE_DB_USER') ?: 'root');
defined('DB_PASS') or define('DB_PASS', getenv('SPACESHARE_DB_PASS') ?: '');
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8mb4');

defined('APP_NAME') or define('APP_NAME', 'SpaceShare');
defined('APP_URL') or define('APP_URL', getenv('SPACESHARE_APP_URL') ?: 'http://localhost/spaceshare');
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));

defined('UPLOAD_DIR') or define('UPLOAD_DIR', BASE_PATH . '/assets/uploads');
defined('MAX_UPLOAD_BYTES') or define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
defined('ALLOWED_IMAGE_TYPES') or define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Razorpay — leave blank to run on Cash / Pay-Later only (not a demo blocker).
defined('RAZORPAY_KEY_ID') or define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: '');
defined('RAZORPAY_KEY_SECRET') or define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: '');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    ]);
    session_start();
}
