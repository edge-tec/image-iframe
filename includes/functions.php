<?php
/**
 * Image Frame Generator — Core Utility Functions
 * Security, file handling, settings, image helpers.
 */

// ═══════════════════════════════════════════════════════════════
//  SECURITY HELPERS
// ═══════════════════════════════════════════════════════════════

/** Escape HTML output to prevent XSS */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Generate a hidden CSRF input field */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

/** Validate CSRF token from POST requests */
function validate_csrf(): bool {
    return isset($_POST['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/** Validate CSRF token for AJAX requests (header or POST) */
function validate_csrf_ajax(): void {
    $token = $_POST['csrf_token']
          ?? $_SERVER['HTTP_X_CSRF_TOKEN']
          ?? '';

    if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'CSRF validation failed. Refresh the page.']);
        exit;
    }
}

/** Send a JSON response and exit */
function json_response(string $status, string $message, array $data = []): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data),
                     JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  AUTHENTICATION HELPERS
// ═══════════════════════════════════════════════════════════════

/** Check if the current session belongs to an admin */
function is_admin(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/** Redirect to login if not authenticated */
function require_admin(): void {
    if (!is_admin()) {
        $login = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? 'login.php' : 'admin/login.php';
        header("Location: {$login}");
        exit;
    }
}

// ═══════════════════════════════════════════════════════════════
//  DATE / TIME HELPERS
// ═══════════════════════════════════════════════════════════════

/** Get formatted server date and time (Asia/Dhaka timezone) */
function get_current_datetime(): array {
    $now = new DateTime('now');
    return [
        'formatted' => $now->format('d-m-Y h:i A'),
        'date'      => $now->format('d-m-Y'),
        'time'      => $now->format('h:i A'),
        'mysql'     => $now->format('Y-m-d H:i:s'),
    ];
}

// ═══════════════════════════════════════════════════════════════
//  SETTINGS HELPERS
// ═══════════════════════════════════════════════════════════════

/** Get a single setting value from the database */
function get_setting(PDO $pdo, string $key, ?string $default = null): ?string {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];

    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    $result = ($val !== false) ? $val : $default;
    $cache[$key] = $result;
    return $result;
}

/** Update a setting value */
function set_setting(PDO $pdo, string $key, string $value): void {
    $stmt = $pdo->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?');
    $stmt->execute([$value, $key]);
}

/** Get all settings as key => value array, optionally filtered by group */
function get_all_settings(PDO $pdo, ?string $group = null): array {
    if ($group) {
        $stmt = $pdo->prepare('SELECT setting_key, setting_value FROM settings WHERE setting_group = ?');
        $stmt->execute([$group]);
    } else {
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
    }
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

// ═══════════════════════════════════════════════════════════════
//  FILE UPLOAD HANDLER
// ═══════════════════════════════════════════════════════════════

/**
 * Securely upload an image file.
 *
 * @param  array  $file      The $_FILES entry
 * @param  string $subFolder Sub-directory inside uploads/ ('images', 'frames', 'logos')
 * @return array  ['status' => 'success'|'error', ...]
 */
function upload_image(array $file, string $subFolder): array {
    // Validate upload error code
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['status' => 'error', 'message' => 'Invalid file upload parameters.'];
    }
    switch ($file['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['status' => 'error', 'message' => 'File exceeds maximum upload size.'];
        case UPLOAD_ERR_NO_FILE:
            return ['status' => 'error', 'message' => 'No file was uploaded.'];
        default:
            return ['status' => 'error', 'message' => 'Unknown upload error occurred.'];
    }

    // Size limit
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['status' => 'error', 'message' => 'File exceeds the ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB limit.'];
    }

    // MIME type check via Fileinfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_MIMES, true)) {
        return ['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, and WEBP allowed.'];
    }

    // Extension check
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTS, true)) {
        return ['status' => 'error', 'message' => 'Invalid file extension.'];
    }

    // GD image verification
    if (@getimagesize($file['tmp_name']) === false) {
        return ['status' => 'error', 'message' => 'Uploaded file is not a valid image.'];
    }

    // Ensure target directory
    $targetDir = UPLOAD_PATH . '/' . $subFolder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Unique filename
    $uniqueName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
    $targetPath = $targetDir . '/' . $uniqueName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [
            'status'        => 'success',
            'filename'      => $uniqueName,
            'relative_path' => 'uploads/' . $subFolder . '/' . $uniqueName,
            'absolute_path' => $targetPath,
        ];
    }

    return ['status' => 'error', 'message' => 'Failed to save file. Check directory permissions.'];
}

// ═══════════════════════════════════════════════════════════════
//  SLUG GENERATOR
// ═══════════════════════════════════════════════════════════════

/** Generate a URL-safe slug from a string */
function generate_slug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// ═══════════════════════════════════════════════════════════════
//  IMAGE PROCESSING HELPERS (GD Library)
// ═══════════════════════════════════════════════════════════════

/** Create a GD image resource from any supported file path */
function gd_create_from_file(string $path) {
    $mime = mime_content_type($path);
    return match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($path),
        'image/png'  => imagecreatefrompng($path),
        'image/webp' => imagecreatefromwebp($path),
        default      => false,
    };
}

/** Convert hex color string to GD color allocation */
function hex_to_gd(GdImage $image, string $hex, int $alpha = 0): int {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $r = hexdec(str_repeat($hex[0], 2));
        $g = hexdec(str_repeat($hex[1], 2));
        $b = hexdec(str_repeat($hex[2], 2));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return ($alpha > 0)
        ? imagecolorallocatealpha($image, $r, $g, $b, $alpha)
        : imagecolorallocate($image, $r, $g, $b);
}

/**
 * Draw text with word wrapping and alignment using GD.
 * Supports Unicode (Bangla/English).
 */
function draw_wrapped_text(
    GdImage $image,
    string  $text,
    string  $fontFile,
    int     $fontSizePx,
    string  $colorHex,
    int     $centerX,
    int     $topY,
    int     $maxWidth    = 800,
    string  $align       = 'center',
    float   $lineSpacing = 1.4,
    bool    $hasShadow   = false,
    string  $shadowColor = '#000000'
): void {
    $color     = hex_to_gd($image, $colorHex);
    $sizePt    = $fontSizePx * 0.75;

    // Split text into words preserving whitespace
    $words = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    $lines       = [];
    $currentLine = '';

    foreach ($words as $word) {
        $testLine = $currentLine . $word;
        $bbox = @imagettfbbox($sizePt, 0, $fontFile, $testLine);
        if ($bbox === false) {
            $lines[] = $text;
            $currentLine = '';
            break;
        }
        $width = abs($bbox[2] - $bbox[0]);

        if ($width > $maxWidth && !empty(trim($currentLine))) {
            $lines[] = trim($currentLine);
            $currentLine = $word;
        } else {
            $currentLine = $testLine;
        }
    }
    if (!empty(trim($currentLine))) {
        $lines[] = trim($currentLine);
    }

    $y = $topY;
    foreach ($lines as $line) {
        $bbox       = imagettfbbox($sizePt, 0, $fontFile, $line);
        $lineWidth  = abs($bbox[2] - $bbox[0]);
        $lineHeight = abs($bbox[5] - $bbox[1]);

        $x = match ($align) {
            'center' => $centerX - ($lineWidth / 2),
            'right'  => $centerX + ($maxWidth / 2) - $lineWidth,
            default  => $centerX - ($maxWidth / 2),
        };

        // Optional text shadow
        if ($hasShadow) {
            $shadow = hex_to_gd($image, $shadowColor, 40);
            imagettftext($image, $sizePt, 0, (int)$x + 2, (int)($y + $lineHeight + 2), $shadow, $fontFile, $line);
        }

        imagettftext($image, $sizePt, 0, (int)$x, (int)($y + $lineHeight), $color, $fontFile, $line);

        $y += (int)($lineSpacing * ($sizePt * 1.33));
    }
}

/**
 * Add a watermark text to the bottom-right of an image.
 */
function add_watermark(GdImage $image, string $text, int $opacity = 30): void {
    $width  = imagesx($image);
    $height = imagesy($image);

    $fontFile = FONT_PATH . '/Roboto-Regular.ttf';
    if (!file_exists($fontFile)) return;

    $sizePt = 12;
    $alpha  = (int)(127 - ($opacity / 100 * 127));
    $color  = imagecolorallocatealpha($image, 255, 255, 255, $alpha);

    $bbox      = imagettfbbox($sizePt, 0, $fontFile, $text);
    $textWidth = abs($bbox[2] - $bbox[0]);
    $x         = $width - $textWidth - 20;
    $y         = $height - 20;

    imagettftext($image, $sizePt, 0, $x, $y, $color, $fontFile, $text);
}

/**
 * Resolve the correct font file path for GD rendering.
 */
function resolve_font_path(string $fontFamily, bool $bold = false): string {
    $suffix = $bold ? '-Bold.ttf' : '-Regular.ttf';
    $map = [
        'HindSiliguri'     => 'HindSiliguri',
        'Hind Siliguri'    => 'HindSiliguri',
        'Roboto'           => 'Roboto',
        'Noto Sans Bengali'=> 'NotoSansBengali',
    ];

    $base = $map[$fontFamily] ?? 'HindSiliguri';
    $path = FONT_PATH . '/' . $base . $suffix;

    // Fallback chain
    if (!file_exists($path)) {
        $path = FONT_PATH . '/' . $base . '-Regular.ttf';
    }
    if (!file_exists($path)) {
        $path = FONT_PATH . '/HindSiliguri-Regular.ttf';
    }
    return $path;
}

/**
 * Get the base URL path dynamically.
 */
function get_base_url(): string {
    $script  = $_SERVER['SCRIPT_NAME'] ?? '';
    $baseDir = dirname($script);
    // If we're in admin/, go up one level
    if (str_contains($baseDir, '/admin')) {
        $baseDir = dirname($baseDir);
    }
    return rtrim($baseDir, '/');
}

/**
 * Format file size for display.
 */
function format_bytes(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
