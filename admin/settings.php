<?php
/**
 * Image Frame Generator — System Settings
 */
require_once __DIR__ . '/includes/admin-header.php';

if ($_SESSION['admin_role'] !== 'super_admin') {
    die('<div class="alert alert-danger m-4">Access Denied. Super Admin only.</div>');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf()) {
    $settings = $_POST['settings'] ?? [];
    foreach ($settings as $key => $val) {
        set_setting($pdo, $key, $val);
    }
    $message = '<div class="alert alert-success">Settings updated successfully.</div>';
}

$allSettings = get_all_settings($pdo);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="font-outfit fw-bold mb-0">System Settings</h3>
</div>

<?= $message ?>

<form method="POST">
    <?= csrf_field() ?>
    
    <div class="row g-4">
        <!-- General Settings -->
        <div class="col-md-6">
            <div class="glass-card h-100">
                <h5 class="font-outfit fw-bold mb-3 border-bottom border-secondary-subtle pb-2 text-primary">
                    <i class="fa-solid fa-globe me-2"></i>General Information
                </h5>
                
                <div class="mb-3">
                    <label class="form-label small text-muted">Site Name</label>
                    <input type="text" name="settings[site_name]" class="form-control bg-dark text-white border-secondary-subtle" value="<?= e($allSettings['site_name'] ?? APP_NAME) ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label small text-muted">Site Description (SEO)</label>
                    <textarea name="settings[site_description]" class="form-control bg-dark text-white border-secondary-subtle" rows="2"><?= e($allSettings['site_description'] ?? '') ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small text-muted">Timezone</label>
                    <input type="text" name="settings[timezone]" class="form-control bg-dark text-white border-secondary-subtle" value="<?= e($allSettings['timezone'] ?? 'Asia/Dhaka') ?>">
                    <small class="text-muted">Must be a valid PHP timezone string (e.g. Asia/Dhaka)</small>
                </div>
            </div>
        </div>

        <!-- Watermark Settings -->
        <div class="col-md-6">
            <div class="glass-card h-100">
                <h5 class="font-outfit fw-bold mb-3 border-bottom border-secondary-subtle pb-2 text-info">
                    <i class="fa-solid fa-copyright me-2"></i>Watermark Protection
                </h5>
                
                <div class="mb-3">
                    <label class="form-label small text-muted">Enable Watermark on HD Generation?</label>
                    <select name="settings[watermark_enabled]" class="form-select bg-dark text-white border-secondary-subtle">
                        <option value="0" <?= ($allSettings['watermark_enabled'] ?? '0') === '0' ? 'selected' : '' ?>>Disabled</option>
                        <option value="1" <?= ($allSettings['watermark_enabled'] ?? '0') === '1' ? 'selected' : '' ?>>Enabled</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small text-muted">Watermark Text</label>
                    <input type="text" name="settings[watermark_text]" class="form-control bg-dark text-white border-secondary-subtle" value="<?= e($allSettings['watermark_text'] ?? APP_NAME) ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label small text-muted">Watermark Opacity (0-100)</label>
                    <input type="number" name="settings[watermark_opacity]" class="form-control bg-dark text-white border-secondary-subtle" min="0" max="100" value="<?= e($allSettings['watermark_opacity'] ?? '30') ?>">
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="glass-card text-end">
                <button type="submit" class="btn btn-primary px-5 py-2 font-outfit shadow-sm fw-bold">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings
                </button>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
