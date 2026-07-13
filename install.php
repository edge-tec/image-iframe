<?php
/**
 * Image Frame Generator — Installer Wizard
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle Force Reinstall
if (isset($_GET['force'])) {
    @unlink(__DIR__ . '/includes/config.php');
    header('Location: install.php');
    exit;
}

// Prevent re-installation
if (file_exists(__DIR__ . '/includes/config.php')) {
    die("Installation already complete. To reinstall, delete includes/config.php and drop the database. <br><br> <a href='?force=1' style='color:red;'>Click here to Force Reinstall (Overwrites Database)</a>");
}

$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Single Step Installation
    $host = $_POST['db_host'] ?? '127.0.0.1';
    $user = $_POST['db_user'] ?? 'root';
    $pass = $_POST['db_pass'] ?? '';
    $name = $_POST['db_name'] ?? 'image_frame_generator';

    try {
        // Connect and create database
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$name`");

        // Execute SQL commands
        $sql = file_get_contents(__DIR__ . '/database.sql');
        if ($sql === false) {
            throw new Exception("database.sql is missing.");
        }
        $pdo->exec($sql);

        // Write config.php
        $sample = file_get_contents(__DIR__ . '/includes/config.sample.php');
        if ($sample === false) {
            throw new Exception("config.sample.php is missing.");
        }

        $config = str_replace(
            ["define('DB_HOST', '127.0.0.1');", "define('DB_USER', 'your_db_username');", "define('DB_PASS', 'your_db_password');", "define('DB_NAME', 'image_frame_generator');"],
            ["define('DB_HOST', '$host');", "define('DB_USER', '$user');", "define('DB_PASS', '$pass');", "define('DB_NAME', '$name');"],
            $sample
        );

        if (file_put_contents(__DIR__ . '/includes/config.php', $config) === false) {
            throw new Exception("Could not write to includes/config.php. Check directory permissions.");
        }

        // Create upload directories
        $dirs = ['uploads/images', 'uploads/logos', 'uploads/frames', 'uploads/output'];
        foreach ($dirs as $dir) {
            if (!is_dir(__DIR__ . '/' . $dir)) {
                mkdir(__DIR__ . '/' . $dir, 0777, true);
            }
        }

        // Redirect to success step
        header('Location: install.php?step=3');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
        // Clean up config if it fails midway
        @unlink(__DIR__ . '/includes/config.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer — Image Frame Generator</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="glass-card" style="width: 100%; max-width: 500px;">
        <div class="text-center mb-4">
            <i class="fa-solid fa-wand-magic-sparkles text-primary fs-1 mb-2"></i>
            <h3 class="font-outfit fw-bold text-gradient mb-1">Installation Wizard</h3>
            <p class="text-muted small">Step <?= $step === 3 ? 2 : $step ?> of 2</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger small py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form method="POST">
                <h6 class="font-outfit text-white mb-3">Database Credentials</h6>
                
                <div class="mb-3">
                    <label class="form-label text-muted small">Host</label>
                    <input type="text" name="db_host" class="form-control bg-dark text-white border-secondary-subtle" value="127.0.0.1" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small">Database Name</label>
                    <input type="text" name="db_name" class="form-control bg-dark text-white border-secondary-subtle" value="image_frame_generator" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small">Username</label>
                    <input type="text" name="db_user" class="form-control bg-dark text-white border-secondary-subtle" value="root" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label text-muted small">Password</label>
                    <input type="password" name="db_pass" class="form-control bg-dark text-white border-secondary-subtle">
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold font-outfit">Connect & Continue</button>
            </form>

        <?php elseif ($step === 3): ?>
            <div class="text-center py-4">
                <i class="fa-solid fa-circle-check text-success fs-1 mb-3"></i>
                <h4 class="text-white font-outfit mb-2">Installation Complete!</h4>
                <p class="text-muted small mb-4">The application has been successfully installed.</p>
                
                <div class="alert alert-dark border-secondary-subtle text-start small mb-4">
                    <strong>Default Admin Credentials:</strong><br>
                    Username: <span class="text-primary">admin</span><br>
                    Password: <span class="text-primary">admin123</span>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-outline-secondary w-50 py-2 font-outfit">View Site</a>
                    <a href="admin/login.php" class="btn btn-primary w-50 py-2 font-outfit">Admin Login</a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
