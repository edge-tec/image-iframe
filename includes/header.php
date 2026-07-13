<?php
/**
 * Image Frame Generator — Shared HTML Header
 * Loaded by all front-end pages.
 */
require_once __DIR__ . '/functions.php';
$baseUrl = get_base_url();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> — Create Professional Frame Posters</title>
    <meta name="description" content="Upload your photo, pick a transparent frame overlay, write headlines in Bangla or English, and download HD professional posters.">
    <meta name="author" content="Image Frame Generator">
    <meta name="theme-color" content="#0b0f19">

    <!-- Google Fonts: Outfit (UI) + Hind Siliguri + Noto Sans Bengali + Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Application CSS -->
    <link href="<?= $baseUrl ?>/assets/css/style.css" rel="stylesheet">

    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="<?= e($_SESSION['csrf_token']) ?>">
    <!-- Base URL for JS -->
    <meta name="base-url" content="<?= e($baseUrl) ?>">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-blur fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $baseUrl ?>/index.php">
            <i class="fa-solid fa-crop-simple text-primary fs-4"></i>
            <span class="font-outfit fw-bold text-gradient"><?= e(APP_NAME) ?></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link px-3" href="<?= $baseUrl ?>/index.php">
                        <i class="fa-solid fa-image me-1"></i> Editor
                    </a>
                </li>
                <?php if (is_admin()): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?= $baseUrl ?>/admin/dashboard.php">
                            <i class="fa-solid fa-gauge-high me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?= $baseUrl ?>/admin/frame-designer.php">
                            <i class="fa-solid fa-vector-square me-1"></i> Designer
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger btn-sm px-3 ms-1" href="<?= $baseUrl ?>/admin/logout.php">
                            <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-outline-primary btn-sm px-3 ms-1" href="<?= $baseUrl ?>/admin/login.php">
                            <i class="fa-solid fa-lock me-1"></i> Admin
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Theme Toggle -->
                <li class="nav-item ms-2">
                    <button class="btn-theme-toggle" id="themeToggler" aria-label="Toggle theme">
                        <i class="fa-solid fa-moon"></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="main-wrapper pt-5 mt-4">
