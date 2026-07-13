<?php
/**
 * Image Frame Generator — Sample Configuration
 * Copy this file to config.php and update the credentials.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Dhaka');

// ─── UPDATE THESE CREDENTIALS ───────────────────────────────────
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'image_frame_generator');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

define('APP_NAME', 'Image Frame Generator');
define('APP_VERSION', '2.0.0');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);
define('ALLOWED_MIMES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_EXTS', ['jpg', 'jpeg', 'png', 'webp']);

define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('FONT_PATH', BASE_PATH . '/assets/fonts');

define('CANVAS_WIDTH', 1080);
define('CANVAS_HEIGHT', 1080);
