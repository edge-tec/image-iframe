<?php
/**
 * Image Frame Generator — PDO Database Connection
 * Auto-redirects to install.php if config.php is missing.
 */

if (!file_exists(__DIR__ . '/config.php')) {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $prefix = (strpos($script, '/admin/') !== false) ? '../' : '';
    header("Location: {$prefix}install.php");
    exit;
}

require_once __DIR__ . '/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    die('Database connection failed. Check config.php credentials.');
}
