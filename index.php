<?php
/**
 * Image Frame Generator — Main User Interface
 * Drag & Drop, Live Preview, and Frame Selection.
 */
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Fetch Active Categories
try {
    $catStmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Fetch Active Frames with Category Data
try {
    $frameStmt = $pdo->query("
        SELECT f.*, c.name as category_name
        FROM frames f
        LEFT JOIN categories c ON f.category_id = c.id
        WHERE f.is_active = 1
        ORDER BY f.is_featured DESC, f.id DESC
    ");
    $frames = $frameStmt->fetchAll();
} catch (PDOException $e) {
    $frames = [];
}

$datetime = get_current_datetime();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hidden server time state for JS -->
<input type="hidden" id="serverDate" value="<?= e($datetime['date']) ?>">
<input type="hidden" id="serverTime" value="<?= e($datetime['time']) ?>">

<!-- Loading Overlay -->
<div class="loading-overlay d-none" id="loadingOverlay">
    <div class="spinner-ring mb-4"></div>
    <h4 class="font-outfit fw-bold text-white mb-2">Processing Image Canvas...</h4>
    <p class="text-secondary small mb-4">Merging high-quality graphic elements.</p>
    <div class="progress w-100" style="max-width: 300px; height: 6px; border-radius: 10px; background: rgba(255,255,255,0.1);">
        <div class="progress-bar progress-bar-animated" role="progressbar" style="width: 100%"></div>
    </div>
</div>

<div class="container py-4">
    <header class="text-center mb-5 mt-2 anim-fade-up">
        <h1 class="display-5 fw-bold font-outfit text-gradient mb-3">Professional Image Frame Generator</h1>
        <p class="text-secondary max-w-2xl mx-auto">Upload your photo, select a premium overlay template, write custom headlines in Bangla or English, and export in print-ready HD resolution for social media or campaigns.</p>
    </header>

    <div class="row g-4">
        <!-- ─── LEFT SIDEBAR: CONTROLS ───────────────────────────── -->
        <div class="col-lg-5 order-2 order-lg-1 anim-fade-up" style="animation-delay: 0.1s;">

            <!-- 1. Media Uploads -->
            <div class="glass-card mb-4">
                <div class="section-label text-primary">
                    <span class="badge-num">1</span> Upload Media
                </div>
                
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="upload-zone w-100 h-100" id="photoDropZone">
                            <input type="file" id="userImageFile" accept="image/jpeg,image/png,image/webp">
                            <i class="fa-solid fa-cloud-arrow-up fs-2 text-primary mb-2"></i>
                            <div class="fw-bold font-outfit text-primary" id="photoFileName">Background Photo</div>
                            <small class="text-muted d-block mt-1">JPG/PNG (Max 10MB)</small>
                        </label>
                    </div>
                    <div class="col-sm-6">
                        <label class="upload-zone w-100 h-100" id="logoDropZone">
                            <input type="file" id="logoFile" accept="image/png,image/webp">
                            <i class="fa-solid fa-shield-halved fs-2 text-success mb-2"></i>
                            <div class="fw-bold font-outfit text-success" id="logoFileName">Brand Logo</div>
                            <small class="text-muted d-block mt-1">Transparent PNG</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 2. Select Template -->
            <div class="glass-card mb-4">
                <div class="section-label text-secondary">
                    <span class="badge-num" style="background: linear-gradient(135deg, var(--secondary), #3b82f6);">2</span> Choose Frame Overlay
                </div>
                
                <!-- Category Filter & Search -->
                <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                    <div class="search-input-wrap flex-grow-1" style="min-width: 200px;">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="frameSearch" class="form-control form-control-sm bg-dark text-white border-secondary-subtle" placeholder="Search templates...">
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle font-outfit" type="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-filter me-1"></i> <span id="currentCategoryLabel">All Categories</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><a class="dropdown-item category-filter active" href="#" data-cat="all">All Categories</a></li>
                            <li><a class="dropdown-item category-filter" href="#" data-cat="favorites"><i class="fa-solid fa-star text-warning me-2"></i>Favorites</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php foreach ($categories as $cat): ?>
                                <li>
                                    <a class="dropdown-item category-filter" href="#" data-cat="<?= $cat['id'] ?>">
                                        <i class="fa-solid <?= e($cat['icon']) ?> me-2 text-muted"></i><?= e($cat['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Frame Thumbnails -->
                <?php if (empty($frames)): ?>
                    <div class="text-center py-4 text-muted border border-dashed rounded">
                        <i class="fa-solid fa-image-slash fs-3 mb-2"></i>
                        <p class="mb-0 small">No templates available.</p>
                    </div>
                <?php else: ?>
                    <div class="frame-thumbnail-list" id="frameContainer">
                        <?php foreach ($frames as $frame): ?>
                            <!-- Frame Data stored in JSON payload to avoid messy data attributes -->
                            <div class="frame-thumbnail" 
                                 data-id="<?= $frame['id'] ?>"
                                 data-cat="<?= $frame['category_id'] ?>"
                                 data-name="<?= strtolower(e($frame['name'])) ?>"
                                 title="<?= e($frame['name']) ?>">
                                <i class="fa-solid fa-star frame-fav-btn" data-id="<?= $frame['id'] ?>" title="Toggle Favorite"></i>
                                <img src="<?= e($frame['thumbnail'] ?: $frame['overlay_image']) ?>" alt="<?= e($frame['name']) ?>" loading="lazy">
                                <script type="application/json" class="frame-data-payload">
                                    <?= $frame['template_json'] ?>
                                </script>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 3. Headline Settings -->
            <div class="glass-card mb-4">
                <div class="section-label" style="color: #a855f7;">
                    <span class="badge-num" style="background: linear-gradient(135deg, #a855f7, #ec4899);">3</span> Typography
                </div>
                
                <div class="mb-3">
                    <textarea class="form-control font-bangla bg-dark text-white border-secondary-subtle" id="headlineText" rows="2" placeholder="আপনার শিরোনাম এখানে লিখুন (Write headline here...)"></textarea>
                </div>

                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6">
                        <select class="form-select form-select-sm bg-dark text-white border-secondary-subtle" id="fontFamilySelect">
                            <option value="Hind Siliguri">Hind Siliguri (Bangla Default)</option>
                            <option value="Noto Sans Bengali">Noto Sans Bengali</option>
                            <option value="Roboto">Roboto (English)</option>
                            <option value="Outfit">Outfit (English UI)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 d-flex gap-2">
                        <input type="color" class="form-control form-control-color form-control-sm flex-shrink-0" id="fontColorPicker" value="#ffffff" title="Text Color">
                        
                        <div class="btn-group w-100" role="group">
                            <input type="checkbox" class="btn-check" id="btnBold" autocomplete="off">
                            <label class="btn btn-outline-secondary btn-sm" for="btnBold"><i class="fa-solid fa-bold"></i></label>
                            
                            <input type="checkbox" class="btn-check" id="btnShadow" autocomplete="off">
                            <label class="btn btn-outline-secondary btn-sm" for="btnShadow" title="Drop Shadow"><i class="fa-solid fa-cloud"></i></label>
                            
                            <!-- Alignment Toggle -->
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="alignBtn" title="Alignment">
                                <i class="fa-solid fa-align-center" id="alignIcon"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" style="min-width: auto;">
                                <li><a class="dropdown-item align-opt active" href="#" data-align="left"><i class="fa-solid fa-align-left me-2"></i>Left</a></li>
                                <li><a class="dropdown-item align-opt" href="#" data-align="center"><i class="fa-solid fa-align-center me-2"></i>Center</a></li>
                                <li><a class="dropdown-item align-opt" href="#" data-align="right"><i class="fa-solid fa-align-right me-2"></i>Right</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex align-items-center gap-3">
                    <i class="fa-solid fa-text-height text-muted"></i>
                    <input type="range" class="form-range flex-grow-1" id="fontSizeRange" min="16" max="120" value="42">
                    <span class="badge bg-secondary font-mono" id="fontSizeVal">42px</span>
                </div>
            </div>

            <!-- Server Time Info -->
            <div class="d-flex justify-content-between align-items-center px-2 mb-3">
                <div class="d-flex align-items-center gap-2 text-muted small">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Auto-Timestamp: <?= e($datetime['formatted']) ?></span>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle font-outfit">Synced</span>
            </div>
            
        </div>

        <!-- ─── CENTER: CANVAS PREVIEW & EXPORT ──────────────────────── -->
        <div class="col-lg-7 order-1 order-lg-2 anim-fade-up" style="animation-delay: 0.2s;">
            <div class="glass-card mb-4 p-3 p-md-4 text-center">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="font-outfit fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-wand-magic-sparkles text-primary"></i> Live Canvas
                    </div>
                    
                    <!-- History Controls -->
                    <div class="btn-group shadow-sm">
                        <button class="btn btn-dark border-secondary-subtle btn-sm" id="btnUndo" disabled title="Undo (Ctrl+Z)">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                        <button class="btn btn-dark border-secondary-subtle btn-sm" id="btnRedo" disabled title="Redo (Ctrl+Y)">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" id="btnReset" title="Reset All">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>

                <!-- Main Fabric.js Wrapper -->
                <div class="canvas-container-outer bg-dark">
                    <canvas id="editorCanvas"></canvas>
                </div>

                <!-- Context Help -->
                <div class="mt-3 text-muted small d-flex justify-content-center align-items-center gap-2">
                    <i class="fa-solid fa-circle-info text-info"></i>
                    <span>Drag and resize the photo, logo, or text directly on the canvas preview.</span>
                </div>
            </div>

            <!-- Photo Adjustment Controls -->
            <div class="glass-card mb-4 p-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6 d-flex align-items-center gap-3">
                        <i class="fa-solid fa-magnifying-glass-plus text-muted" title="Zoom"></i>
                        <input type="range" class="form-range flex-grow-1" id="zoomRange" min="10" max="300" value="100">
                        <span class="badge bg-secondary font-mono" id="zoomVal">100%</span>
                    </div>
                    <div class="col-md-6 d-flex align-items-center gap-3">
                        <i class="fa-solid fa-rotate text-muted" title="Rotate"></i>
                        <input type="range" class="form-range flex-grow-1" id="rotateRange" min="-180" max="180" value="0">
                        <span class="badge bg-secondary font-mono" id="rotateVal">0&deg;</span>
                    </div>
                </div>
            </div>

            <!-- Export Actions -->
            <div class="glass-card p-3 p-md-4 text-center" style="background: linear-gradient(145deg, var(--bg-card), rgba(79, 70, 229, 0.05)); border-color: rgba(79, 70, 229, 0.3);">
                <h5 class="font-outfit fw-bold mb-3"><i class="fa-solid fa-download text-primary me-2"></i>Export Options</h5>
                
                <div class="row g-2 mb-2">
                    <div class="col-sm-6">
                        <button class="btn btn-outline-secondary w-100 py-2 btn-download font-outfit" data-format="jpg" data-quality="sd">
                            <i class="fa-regular fa-file-image me-2"></i>Download JPG
                        </button>
                    </div>
                    <div class="col-sm-6">
                        <button class="btn btn-outline-secondary w-100 py-2 btn-download font-outfit" data-format="png" data-quality="sd">
                            <i class="fa-regular fa-file-code me-2"></i>Download PNG
                        </button>
                    </div>
                </div>
                
                <button class="btn btn-download-primary w-100 py-3 shadow-sm fs-5" data-format="png" data-quality="hd">
                    <i class="fa-solid fa-crown me-2"></i> Generate HD Quality
                </button>
                <div class="mt-2 text-muted small">Server-side rendering guarantees max resolution and perfect typography alignment.</div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
