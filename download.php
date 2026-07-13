<?php
/**
 * Image Frame Generator — Secure File Download Handler
 * Supports both Client-Side Base64 parsing and Server-Side File downloads.
 */
require_once __DIR__ . '/includes/config.php';

// If POST request, it's a client-side SD download with Base64
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $base64 = $_POST['base64_image'] ?? '';
    $filename = $_POST['filename'] ?? 'frame_sd.png';
    $mime = $_POST['mime'] ?? 'image/png';
    $csrf = $_POST['csrf_token'] ?? '';

    // Validate CSRF
    if (empty($csrf) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        die('Security token validation failed.');
    }

    if (empty($base64)) {
        die('No image data provided.');
    }

    // Browser POST converts + to spaces in Base64 string, replace it back
    $base64 = str_replace(' ', '+', $base64);

    if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
        $data = substr($base64, strpos($base64, ',') + 1);
        $data = base64_decode($data);

        if ($data === false) {
            die('Base64 decode failed.');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($data));
        
        echo $data;
        exit;
    } else {
        die('Invalid image data format.');
    }
}

// If GET request, it's a server-side HD download from the output directory
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $file = $_GET['file'] ?? '';
    
    // Path traversal protection
    if (empty($file) || strpos($file, '..') !== false || strpos($file, '/') !== false || strpos($file, '\\') !== false) {
        die('Invalid file request.');
    }

    $filepath = UPLOAD_PATH . '/output/' . $file;

    if (!file_exists($filepath)) {
        die('File not found or expired.');
    }

    $mime = mime_content_type($filepath);
    $size = filesize($filepath);

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $size);
    
    readfile($filepath);
    exit;
}

die('Invalid request.');
