<?php
/**
 * Image Frame Generator — API Frame Save Handler
 */
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin();
validate_csrf_ajax();

$frameId = (int)($_POST['frameId'] ?? 0);
$name = trim($_POST['frameName'] ?? '');
$categoryId = (int)($_POST['frameCategory'] ?? 0);
$templateJson = $_POST['template_json'] ?? '';

if (empty($name) || empty($categoryId) || empty($templateJson)) {
    json_response('error', 'Please fill all required fields.');
}

// Handle Image Upload if provided
$imagePath = '';
if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    $result = upload_image($_FILES['image_file'], 'frames');
    if ($result['status'] === 'success') {
        $imagePath = $result['relative_path'];
    } else {
        json_response('error', 'Image Upload Failed: ' . $result['message']);
    }
}

try {
    $pdo->beginTransaction();

    if ($frameId > 0) {
        // Update existing
        $stmt = $pdo->prepare("SELECT overlay_image FROM frames WHERE id = ?");
        $stmt->execute([$frameId]);
        $existing = $stmt->fetch();
        
        $finalImagePath = $imagePath ?: $existing['overlay_image'];
        
        $updateStmt = $pdo->prepare("
            UPDATE frames 
            SET name = ?, category_id = ?, template_json = ?, overlay_image = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$name, $categoryId, $templateJson, $finalImagePath, $frameId]);
        
    } else {
        // Insert new
        if (empty($imagePath)) {
            json_response('error', 'Frame image is required for new templates.');
        }
        
        $insertStmt = $pdo->prepare("
            INSERT INTO frames (name, category_id, template_json, overlay_image)
            VALUES (?, ?, ?, ?)
        ");
        $insertStmt->execute([$name, $categoryId, $templateJson, $imagePath]);
        $frameId = $pdo->lastInsertId();
    }

    // Save version history
    $histStmt = $pdo->prepare("
        INSERT INTO frame_templates (frame_id, template_data, version, created_by)
        SELECT ?, ?, COALESCE(MAX(version), 0) + 1, ?
        FROM frame_templates WHERE frame_id = ?
    ");
    $histStmt->execute([$frameId, $templateJson, $_SESSION['admin_id'], $frameId]);

    $pdo->commit();
    json_response('success', 'Frame saved successfully.');

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Frame Save Error: ' . $e->getMessage());
    json_response('error', 'Database error occurred while saving.');
}
