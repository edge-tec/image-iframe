<?php
/**
 * Image Frame Generator — AJAX File Upload Handler
 */
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Validate CSRF token
validate_csrf_ajax();

$action = $_GET['action'] ?? '';

if ($action === 'upload') {
    $type = $_POST['type'] ?? '';
    if (!in_array($type, ['image', 'logo', 'frame'])) {
        json_response('error', 'Invalid upload type.');
    }

    if (!isset($_FILES['file'])) {
        json_response('error', 'No file provided.');
    }

    $subFolder = ($type === 'image') ? 'images' : (($type === 'logo') ? 'logos' : 'frames');
    
    $result = upload_image($_FILES['file'], $subFolder);
    
    if ($result['status'] === 'success') {
        json_response('success', 'File uploaded successfully', [
            'relative_path' => $result['relative_path']
        ]);
    } else {
        json_response('error', $result['message']);
    }
}

json_response('error', 'Invalid action.');
