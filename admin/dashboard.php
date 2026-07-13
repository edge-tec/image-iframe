<?php
/**
 * Image Frame Generator — Admin Dashboard
 */
require_once __DIR__ . '/includes/admin-header.php';

// Fetch Statistics
$stats = [
    'frames' => $pdo->query("SELECT COUNT(*) FROM frames")->fetchColumn(),
    'categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'generated' => $pdo->query("SELECT COUNT(*) FROM generated_images")->fetchColumn(),
    'downloads' => $pdo->query("SELECT SUM(download_count) FROM frames")->fetchColumn() ?: 0
];

// Fetch Recent Generated
$recentImages = $pdo->query("
    SELECT g.*, f.name as frame_name 
    FROM generated_images g 
    LEFT JOIN frames f ON g.frame_id = f.id 
    ORDER BY g.created_at DESC 
    LIMIT 6
")->fetchAll();

// Chart Data (Last 7 Days)
$chartData = [];
$chartLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('M d', strtotime("-$i days"));
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM generated_images WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $chartData[] = $stmt->fetchColumn();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="font-outfit fw-bold mb-0">Dashboard Overview</h2>
    <a href="frame-designer.php" class="btn btn-primary font-outfit shadow-sm">
        <i class="fa-solid fa-plus me-2"></i>Create New Frame
    </a>
</div>

<!-- Stats Row -->
<div class="row g-4 mb-5">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-vector-square"></i>
            </div>
            <div class="stat-value"><?= number_format($stats['frames']) ?></div>
            <div class="stat-label">Total Templates</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-tags"></i>
            </div>
            <div class="stat-value"><?= number_format($stats['categories']) ?></div>
            <div class="stat-label">Categories</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-images"></i>
            </div>
            <div class="stat-value"><?= number_format($stats['generated']) ?></div>
            <div class="stat-label">Images Generated</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="fa-solid fa-download"></i>
            </div>
            <div class="stat-value"><?= number_format($stats['downloads']) ?></div>
            <div class="stat-label">Total Downloads</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Chart -->
    <div class="col-lg-8">
        <div class="glass-card h-100">
            <h5 class="font-outfit fw-bold mb-4">Generation Activity (Last 7 Days)</h5>
            <canvas id="generationChart" height="100"></canvas>
            <script>
                const chartLabels = <?= json_encode($chartLabels) ?>;
                const chartData = <?= json_encode($chartData) ?>;
            </script>
        </div>
    </div>
    
    <!-- Recent Images -->
    <div class="col-lg-4">
        <div class="glass-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="font-outfit fw-bold mb-0">Recent Outputs</h5>
                <a href="generated-images.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            
            <?php if (empty($recentImages)): ?>
                <p class="text-muted small text-center my-5">No images generated yet.</p>
            <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($recentImages as $img): ?>
                        <div class="col-4">
                            <a href="<?= $baseUrl ?>/<?= e($img['output_image']) ?>" target="_blank" title="<?= e($img['frame_name']) ?>">
                                <img src="<?= $baseUrl ?>/<?= e($img['output_image']) ?>" class="img-fluid rounded border border-secondary-subtle" alt="Output">
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
