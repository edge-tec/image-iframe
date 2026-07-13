<?php
/**
 * Image Frame Generator — Admin Header
 */
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin();

$baseUrl = get_base_url();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — <?= e(APP_NAME) ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/css/style.css" rel="stylesheet">
    
    <meta name="csrf-token" content="<?= e($_SESSION['csrf_token']) ?>">
    <meta name="base-url" content="<?= e($baseUrl) ?>">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-blur sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
            <i class="fa-solid fa-shield-halved text-primary"></i>
            <span class="font-outfit fw-bold"><?= e(APP_NAME) ?> Admin</span>
        </a>
        
        <div class="d-flex align-items-center gap-3">
            <a href="../index.php" class="btn btn-outline-secondary btn-sm" target="_blank">
                <i class="fa-solid fa-eye me-1"></i> View Site
            </a>
            
            <div class="dropdown">
                <button class="btn btn-dark border-secondary-subtle dropdown-toggle font-outfit" type="button" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-user-circle me-1 text-primary"></i> <?= e($_SESSION['admin_username']) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header">Role: <?= e(ucfirst($_SESSION['admin_role'])) ?></h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 py-4">
    <div class="row g-4">
        
        <!-- Sidebar -->
        <div class="col-lg-2">
            <div class="admin-sidebar glass-card p-3">
                <div class="d-flex flex-column gap-1">
                    
                    <a href="dashboard.php" class="admin-nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-gauge-high"></i> Dashboard
                    </a>
                    
                    <a href="frame-designer.php" class="admin-nav-link <?= $currentPage === 'frame-designer.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-vector-square text-primary"></i> Frame Designer
                    </a>
                    
                    <a href="categories.php" class="admin-nav-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-tags"></i> Categories
                    </a>
                    
                    <a href="generated-images.php" class="admin-nav-link <?= $currentPage === 'generated-images.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-images"></i> Generated Output
                    </a>
                    
                    <?php if ($_SESSION['admin_role'] === 'super_admin'): ?>
                    <a href="settings.php" class="admin-nav-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-gear"></i> Settings
                    </a>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="col-lg-10">
