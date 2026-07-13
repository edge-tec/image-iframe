<?php
/**
 * Image Frame Generator — Generated Images Gallery
 */
require_once __DIR__ . '/includes/admin-header.php';

// Handle Deletion
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf()) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        $stmt = $pdo->prepare("SELECT output_image FROM generated_images WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetch();
        
        if ($img) {
            $path = BASE_PATH . '/' . $img['output_image'];
            if (file_exists($path)) {
                @unlink($path);
            }
            $pdo->prepare("DELETE FROM generated_images WHERE id = ?")->execute([$id]);
            $message = '<div class="alert alert-success">Image deleted.</div>';
        }
    }
}

// Pagination setup
$page = (int)($_GET['page'] ?? 1);
$perPage = 12;
$offset = ($page - 1) * $perPage;

$totalStmt = $pdo->query("SELECT COUNT(*) FROM generated_images");
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("
    SELECT g.*, f.name as frame_name 
    FROM generated_images g 
    LEFT JOIN frames f ON g.frame_id = f.id 
    ORDER BY g.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$images = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="font-outfit fw-bold mb-0">Generated Outputs <span class="badge bg-secondary fs-6 ms-2"><?= $total ?></span></h3>
</div>

<?= $message ?>

<div class="glass-card p-4">
    <?php if (empty($images)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-images fs-1 mb-3"></i>
            <p>No images have been generated yet.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($images as $img): ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card bg-dark border-secondary-subtle h-100 overflow-hidden">
                        <a href="<?= $baseUrl ?>/<?= e($img['output_image']) ?>" target="_blank">
                            <img src="<?= $baseUrl ?>/<?= e($img['output_image']) ?>" class="card-img-top" alt="Output" style="object-fit: cover; aspect-ratio: 1;">
                        </a>
                        <div class="card-body p-3">
                            <h6 class="card-title font-outfit text-truncate mb-1" title="<?= e($img['frame_name']) ?>">
                                <?= e($img['frame_name'] ?: 'Unknown Frame') ?>
                            </h6>
                            <div class="small text-muted mb-2">
                                <i class="fa-regular fa-calendar me-1"></i> <?= date('d M Y, h:i A', strtotime($img['created_at'])) ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="badge bg-secondary"><?= strtoupper(e($img['format'])) ?></span>
                                <span class="small text-muted"><?= $img['file_size'] ? format_bytes($img['file_size']) : 'N/A' ?></span>
                                
                                <form method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this generated image?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $img['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1"><i class="fa-solid fa-trash-can"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center pagination-dark">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
