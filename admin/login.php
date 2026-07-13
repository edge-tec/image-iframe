<?php
/**
 * Image Frame Generator — Admin Login
 */
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (is_admin()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Session expired. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Login Success
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Update last login
                $pdo->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid credentials or inactive account.';
            }
        }
    }
}

$baseUrl = get_base_url();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= e(APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="glass-card" style="width: 100%; max-width: 420px;">
        <div class="text-center mb-4">
            <i class="fa-solid fa-user-shield text-primary fs-1 mb-2"></i>
            <h3 class="font-outfit fw-bold text-gradient mb-1">Admin Portal</h3>
            <p class="text-muted small">Sign in to manage frames and settings</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger small py-2"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <?= csrf_field() ?>
            
            <div class="mb-3">
                <label class="form-label text-muted small">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary-subtle text-muted"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" class="form-control bg-dark text-white border-secondary-subtle" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-muted small">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary-subtle text-muted"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control bg-dark text-white border-secondary-subtle" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold font-outfit shadow-sm">
                Secure Login <i class="fa-solid fa-arrow-right ms-2"></i>
            </button>
        </form>
        
        <div class="text-center mt-4">
            <a href="../index.php" class="text-muted small text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i> Back to Website</a>
        </div>
    </div>

</body>
</html>
