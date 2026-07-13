<?php
/**
 * Image Frame Generator — Visual Frame Designer
 * Allows admins to visually place placeholders for image, logo, and texts.
 */
require_once __DIR__ . '/includes/admin-header.php';

// Fetch categories for the form
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY sort_order ASC")->fetchAll();

// If editing an existing frame
$editId = (int)($_GET['id'] ?? 0);
$frameData = null;
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM frames WHERE id = ?");
    $stmt->execute([$editId]);
    $frameData = $stmt->fetch();
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="font-outfit fw-bold mb-0">
        <i class="fa-solid fa-vector-square text-primary me-2"></i>
        <?= $frameData ? 'Edit Frame Template' : 'Visual Frame Designer' ?>
    </h3>
    <div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
    </div>
</div>

<div class="row g-4">
    <!-- LEFT: Canvas Area -->
    <div class="col-lg-8">
        <div class="glass-card p-3">
            
            <!-- Toolbar -->
            <div class="designer-toolbar mb-3 pb-2 border-bottom border-secondary-subtle justify-content-between">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-dark border-secondary-subtle btn-sm" id="btnUndo" disabled><i class="fa-solid fa-rotate-left"></i></button>
                    <button type="button" class="btn btn-dark border-secondary-subtle btn-sm" id="btnRedo" disabled><i class="fa-solid fa-rotate-right"></i></button>
                    <div class="vr mx-1 bg-secondary"></div>
                    <button type="button" class="btn btn-dark border-secondary-subtle btn-sm" id="btnSnapGrid" title="Toggle Snap to Grid">
                        <i class="fa-solid fa-border-all text-muted"></i>
                    </button>
                    <button type="button" class="btn btn-dark border-secondary-subtle btn-sm" id="btnCenterH" title="Center Horizontally">
                        <i class="fa-solid fa-arrows-left-right-to-line"></i>
                    </button>
                    <button type="button" class="btn btn-dark border-secondary-subtle btn-sm" id="btnCenterV" title="Center Vertically">
                        <i class="fa-solid fa-arrows-up-down-to-line"></i>
                    </button>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <i class="fa-solid fa-magnifying-glass-plus text-muted small"></i>
                    <input type="range" class="form-range" id="zoomSlider" min="20" max="150" value="100" style="width: 100px;">
                    <span class="badge bg-secondary font-mono" id="zoomLabel">100%</span>
                </div>
            </div>

            <!-- Canvas Wrapper -->
            <div class="designer-canvas-wrap d-flex justify-content-center align-items-center" style="min-height: 500px; overflow: hidden;">
                <canvas id="designerCanvas"></canvas>
            </div>
            
            <!-- Placeholders Legend -->
            <div class="mt-3">
                <h6 class="font-outfit fw-bold text-muted small mb-2">Available Elements (Drag onto canvas to add)</h6>
                <div class="placeholder-legend">
                    <div class="btn btn-sm btn-outline-info drag-source" data-type="image" draggable="true">
                        <i class="fa-solid fa-image me-1"></i> User Photo Area
                    </div>
                    <div class="btn btn-sm btn-outline-success drag-source" data-type="logo" draggable="true">
                        <i class="fa-solid fa-shield-halved me-1"></i> Logo Area
                    </div>
                    <div class="btn btn-sm btn-outline-primary drag-source" data-type="headline" draggable="true">
                        <i class="fa-solid fa-heading me-1"></i> Headline Text
                    </div>
                    <div class="btn btn-sm btn-outline-warning drag-source" data-type="date" draggable="true">
                        <i class="fa-solid fa-calendar me-1"></i> Date Text
                    </div>
                    <div class="btn btn-sm btn-outline-danger drag-source" data-type="time" draggable="true">
                        <i class="fa-solid fa-clock me-1"></i> Time Text
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Settings & Save -->
    <div class="col-lg-4">
        <div class="glass-card">
            
            <form id="frameForm">
                <input type="hidden" id="frameId" value="<?= $editId ?>">
                <input type="hidden" id="templateJson" name="template_json">
                
                <h5 class="font-outfit fw-bold mb-3 border-bottom border-secondary-subtle pb-2">Frame Settings</h5>
                
                <div class="mb-3">
                    <label class="form-label small text-muted">Frame Name</label>
                    <input type="text" class="form-control bg-dark text-white border-secondary-subtle" id="frameName" value="<?= $frameData ? e($frameData['name']) : '' ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small text-muted">Category</label>
                    <select class="form-select bg-dark text-white border-secondary-subtle" id="frameCategory" required>
                        <option value="">Select Category...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($frameData && $frameData['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="form-label small text-muted">Transparent Frame Image (PNG)</label>
                    <input type="file" class="form-control bg-dark text-white border-secondary-subtle" id="frameImage" accept="image/png" <?= $frameData ? '' : 'required' ?>>
                    <?php if ($frameData): ?>
                        <div class="mt-2 small text-success"><i class="fa-solid fa-check-circle me-1"></i>Current image loaded.</div>
                        <input type="hidden" id="existingImagePath" value="<?= e($frameData['overlay_image']) ?>">
                    <?php endif; ?>
                </div>
                
                <!-- Live Element Properties -->
                <h5 class="font-outfit fw-bold mb-3 border-bottom border-secondary-subtle pb-2 mt-4">Selected Element Properties</h5>
                
                <div id="noSelectionMsg" class="text-muted small py-3 text-center bg-dark rounded border border-secondary-subtle border-dashed">
                    Select an element on the canvas to edit its properties.
                </div>
                
                <div id="elementPropsPanel" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary" id="propTypeBadge">Type</span>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnDeleteObj" title="Delete Element"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1">X Pos</label>
                            <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary-subtle" id="propX">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1">Y Pos</label>
                            <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary-subtle" id="propY">
                        </div>
                        <div class="col-6 type-rect">
                            <label class="form-label small text-muted mb-1">Width</label>
                            <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary-subtle" id="propW">
                        </div>
                        <div class="col-6 type-rect">
                            <label class="form-label small text-muted mb-1">Height</label>
                            <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary-subtle" id="propH">
                        </div>
                    </div>
                    
                    <div class="type-text d-none">
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">Default Font Size</label>
                            <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary-subtle" id="propFontSize">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">Default Text Color</label>
                            <input type="color" class="form-control form-control-color form-control-sm w-100" id="propColor">
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 pt-3 border-top border-secondary-subtle">
                    <button type="submit" class="btn btn-primary w-100 font-outfit shadow-sm py-2" id="btnSaveFrame">
                        <i class="fa-solid fa-floppy-disk me-2"></i> <?= $frameData ? 'Update Template' : 'Save Template' ?>
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<?php if ($frameData): ?>
<!-- Load existing JSON payload into JS context -->
<script type="application/json" id="existingTemplateJson">
    <?= $frameData['template_json'] ?>
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
