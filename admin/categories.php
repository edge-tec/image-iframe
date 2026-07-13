<?php
/**
 * Image Frame Generator — Categories Management
 */
require_once __DIR__ . '/includes/admin-header.php';

$message = '';

// Handle Create / Update / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf()) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-folder');
        $slug = generate_slug($name);
        
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)");
            try {
                $stmt->execute([$name, $slug, $icon]);
                $message = '<div class="alert alert-success">Category created successfully.</div>';
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Error: Category already exists.</div>';
            }
        }
    } 
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        $message = '<div class="alert alert-success">Category deleted.</div>';
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, id DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="font-outfit fw-bold mb-0">Manage Categories</h3>
</div>

<?= $message ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass-card">
            <h5 class="font-outfit fw-bold mb-3 border-bottom border-secondary-subtle pb-2">Add Category</h5>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                
                <div class="mb-3">
                    <label class="form-label small text-muted">Category Name</label>
                    <input type="text" name="name" class="form-control bg-dark text-white border-secondary-subtle" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label small text-muted">FontAwesome Icon Class</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary-subtle"><i class="fa-solid fa-icons text-muted"></i></span>
                        <input type="text" name="icon" class="form-control bg-dark text-white border-secondary-subtle" value="fa-folder" placeholder="fa-star">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 font-outfit shadow-sm">Add Category</button>
            </form>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="glass-card">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Icon</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No categories found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><i class="fa-solid <?= e($cat['icon']) ?> text-primary"></i></td>
                                <td class="fw-bold font-outfit"><?= e($cat['name']) ?></td>
                                <td><span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary-subtle"><?= e($cat['slug']) ?></span></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
