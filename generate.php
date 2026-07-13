<?php
/**
 * Image Frame Generator — HD Server-Side Generation Engine
 * Uses PHP GD Library to render pixel-perfect text and composite images.
 */
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response('error', 'Invalid request method.');
}

// Validate CSRF
validate_csrf_ajax();

// Fetch Data
$frameId       = (int)($_POST['frame_id'] ?? 0);
$userImagePath = $_POST['user_image_path'] ?? '';
$logoPath      = $_POST['logo_path'] ?? '';

// Dimensions & Coordinates
$imgX       = (float)($_POST['image_x'] ?? 0);
$imgY       = (float)($_POST['image_y'] ?? 0);
$imgScaleX  = (float)($_POST['image_scale_x'] ?? 1);
$imgScaleY  = (float)($_POST['image_scale_y'] ?? 1);
$imgAngle   = (float)($_POST['image_angle'] ?? 0);

$logoX       = (float)($_POST['logo_x'] ?? 0);
$logoY       = (float)($_POST['logo_y'] ?? 0);
$logoScaleX  = (float)($_POST['logo_scale_x'] ?? 1);
$logoScaleY  = (float)($_POST['logo_scale_y'] ?? 1);

// Typography
$headline   = $_POST['headline'] ?? '';
$headX      = (float)($_POST['headline_x'] ?? 0);
$headY      = (float)($_POST['headline_y'] ?? 0);
$headSize   = (int)($_POST['headline_size'] ?? 42);
$headColor  = $_POST['headline_color'] ?? '#ffffff';
$headFont   = $_POST['headline_font'] ?? 'Hind Siliguri';
$headBold   = (bool)($_POST['headline_bold'] ?? false);
$headShadow = (bool)($_POST['headline_shadow'] ?? false);
$headAlign  = $_POST['headline_align'] ?? 'center';

$format = $_POST['format'] ?? 'png';
if (!in_array($format, ['png', 'jpg'])) $format = 'png';

// ─── 1. FETCH FRAME DATA ────────────────────────────────────────

$stmt = $pdo->prepare("SELECT * FROM frames WHERE id = ?");
$stmt->execute([$frameId]);
$frame = $stmt->fetch();

if (!$frame) json_response('error', 'Frame not found.');

$template = json_decode($frame['template_json'], true) ?: [];

// Get absolute paths
$baseDir = BASE_PATH;
$frameImgAbs = $baseDir . '/' . ltrim($frame['overlay_image'], '/');
$userImgAbs  = $userImagePath ? $baseDir . '/' . ltrim($userImagePath, '/') : null;
$logoImgAbs  = $logoPath ? $baseDir . '/' . ltrim($logoPath, '/') : null;

if (!file_exists($frameImgAbs)) {
    json_response('error', 'Frame image file is missing.');
}

// ─── 2. SETUP BASE CANVAS ───────────────────────────────────────
$cw = $frame['canvas_width'] ?: CANVAS_WIDTH;
$ch = $frame['canvas_height'] ?: CANVAS_HEIGHT;

$canvas = imagecreatetruecolor($cw, $ch);
// Fill with transparent background
imagesavealpha($canvas, true);
$transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
imagefill($canvas, 0, 0, $transparent);

// ─── 3. MERGE USER IMAGE ────────────────────────────────────────
if ($userImgAbs && file_exists($userImgAbs)) {
    $uImg = gd_create_from_file($userImgAbs);
    if ($uImg !== false) {
        $origW = imagesx($uImg);
        $origH = imagesy($uImg);

        // Apply scaling
        $newW = $origW * $imgScaleX;
        $newH = $origH * $imgScaleY;

        // Apply rotation if needed
        if ($imgAngle != 0) {
            $uImg = imagerotate($uImg, -$imgAngle, imagecolorallocatealpha($uImg, 0, 0, 0, 127));
            $rotW = imagesx($uImg);
            $rotH = imagesy($uImg);
            // Fabric.js handles rotation around center origin
            $destX = $imgX - ($rotW / 2);
            $destY = $imgY - ($rotH / 2);
        } else {
            $rotW = $newW;
            $rotH = $newH;
            $destX = $imgX - ($newW / 2);
            $destY = $imgY - ($newH / 2);
        }

        imagecopyresampled($canvas, $uImg, (int)$destX, (int)$destY, 0, 0, (int)$rotW, (int)$rotH, imagesx($uImg), imagesy($uImg));
        imagedestroy($uImg);
    }
}

// ─── 4. MERGE FRAME OVERLAY ─────────────────────────────────────
$fImg = gd_create_from_file($frameImgAbs);
if ($fImg !== false) {
    imagecopyresampled($canvas, $fImg, 0, 0, 0, 0, $cw, $ch, imagesx($fImg), imagesy($fImg));
    imagedestroy($fImg);
}

// ─── 5. MERGE LOGO ──────────────────────────────────────────────
if ($logoImgAbs && file_exists($logoImgAbs)) {
    $lImg = gd_create_from_file($logoImgAbs);
    if ($lImg !== false) {
        $origW = imagesx($lImg);
        $origH = imagesy($lImg);
        
        // Logo origin in Fabric is top-left in our implementation
        $newW = $origW * $logoScaleX;
        $newH = $origH * $logoScaleY;

        imagecopyresampled($canvas, $lImg, (int)$logoX, (int)$logoY, 0, 0, (int)$newW, (int)$newH, $origW, $origH);
        imagedestroy($lImg);
    }
}

// ─── 6. RENDER TEXTS ────────────────────────────────────────────

// Date & Time
$dt = get_current_datetime();
$dtFont = resolve_font_path('Roboto', true);

if (isset($template['date'])) {
    $d = $template['date'];
    draw_wrapped_text($canvas, $dt['date'], $dtFont, (int)($d['fontSize'] ?? 24), $d['color'] ?? '#ffffff', (int)$d['x'], (int)($d['y'] - (($d['fontSize'] ?? 24) / 2)));
}

if (isset($template['time'])) {
    $t = $template['time'];
    draw_wrapped_text($canvas, $dt['time'], $dtFont, (int)($t['fontSize'] ?? 24), $t['color'] ?? '#ffffff', (int)$t['x'], (int)($t['y'] - (($t['fontSize'] ?? 24) / 2)));
}

// Headline
if (!empty($headline) && isset($template['headline'])) {
    $fontFile = resolve_font_path($headFont, $headBold);
    // Adjust y to match Fabric's top origin rendering
    $yAdj = $headY - ($headSize / 2);
    
    // Fallback max-width from template, or 80% of canvas
    $maxWidth = $template['headline']['width'] ?? ($cw * 0.8);

    draw_wrapped_text($canvas, $headline, $fontFile, $headSize, $headColor, (int)$headX, (int)$yAdj, (int)$maxWidth, $headAlign, 1.4, $headShadow, '#000000');
}

// Subheading
$subheading = $_POST['subheading'] ?? '';
if (!empty($subheading) && isset($template['subheading'])) {
    $fontFile = resolve_font_path($headFont, false);
    $sc = $template['subheading'];
    $sSize = (int)($sc['fontSize'] ?? 30);
    $sColor = $sc['color'] ?? '#3b82f6';
    $sX = (int)$sc['x'];
    $sY = (int)$sc['y'] - ($sSize / 2);
    
    draw_wrapped_text($canvas, $subheading, $fontFile, $sSize, $sColor, $sX, $sY, (int)($cw * 0.8), 'center', 1.4, false, '#000000');
}

// Reporter
$reporter = $_POST['reporter'] ?? '';
if (!empty($reporter) && isset($template['reporter'])) {
    $fontFile = resolve_font_path($headFont, false);
    $rc = $template['reporter'];
    $rSize = (int)($rc['fontSize'] ?? 30);
    $rColor = $rc['color'] ?? '#14b8a6';
    $rX = (int)$rc['x'];
    $rY = (int)$rc['y'] - ($rSize / 2);
    
    draw_wrapped_text($canvas, $reporter, $fontFile, $rSize, $rColor, $rX, $rY, (int)($cw * 0.8), 'center', 1.4, false, '#000000');
}

// ─── 7. WATERMARK ───────────────────────────────────────────────
$wmEnabled = get_setting($pdo, 'watermark_enabled', '0');
if ($wmEnabled === '1') {
    $wmText = get_setting($pdo, 'watermark_text', APP_NAME);
    $wmOpac = (int)get_setting($pdo, 'watermark_opacity', '30');
    add_watermark($canvas, $wmText, $wmOpac);
}

// ─── 8. OUTPUT AND SAVE ─────────────────────────────────────────
$outputDir = UPLOAD_PATH . '/output';
if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

$fileName = 'ifg_hd_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $format;
$filePath = $outputDir . '/' . $fileName;

if ($format === 'jpg') {
    $bg = imagecreatetruecolor($cw, $ch);
    imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
    imagecopy($bg, $canvas, 0, 0, 0, 0, $cw, $ch);
    imagejpeg($bg, $filePath, 95);
    imagedestroy($bg);
} else {
    imagepng($canvas, $filePath, 0); // Highest quality, 0 compression speed (max quality)
}

imagedestroy($canvas);
$fileSize = filesize($filePath);

// Log to Database
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$stmt = $pdo->prepare("INSERT INTO generated_images (frame_id, headline, user_image, logo_image, output_image, format, file_size, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $frameId,
    $headline,
    $userImagePath,
    $logoPath,
    'uploads/output/' . $fileName,
    $format,
    $fileSize,
    $ip
]);

$pdo->query("UPDATE frames SET download_count = download_count + 1 WHERE id = $frameId");

json_response('success', 'Image generated successfully', ['filename' => $fileName]);
