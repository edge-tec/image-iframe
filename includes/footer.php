</div> <!-- /main-wrapper -->

<footer class="site-footer py-4 mt-auto">
    <div class="container text-center">
        <p class="mb-0 text-secondary small">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</p>
        <small class="text-muted">PHP 8.2 &bull; Fabric.js &bull; GD Engine &bull; Bootstrap 5</small>
    </div>
</footer>

<!-- jQuery 3.7 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5.3 Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Fabric.js 5.3 Canvas Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>

<!-- Theme Toggle Controller -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const html = document.documentElement;
    const btn  = document.getElementById('themeToggler');
    if (!btn) return;

    // Read saved preference or system default
    const saved  = localStorage.getItem('ifg_theme');
    const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const theme  = saved || system;

    html.setAttribute('data-bs-theme', theme);
    updateIcon(theme);

    btn.addEventListener('click', function() {
        const current = html.getAttribute('data-bs-theme');
        const next    = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', next);
        localStorage.setItem('ifg_theme', next);
        updateIcon(next);
    });

    function updateIcon(t) {
        const i = btn.querySelector('i');
        if (!i) return;
        i.className = t === 'dark'
            ? 'fa-solid fa-sun text-warning'
            : 'fa-solid fa-moon text-primary';
    }
});
</script>

<!-- Main Application Script (only on user-facing pages) -->
<?php
$scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
$baseUrl    = get_base_url();
if ($scriptName === 'index.php' && strpos($_SERVER['SCRIPT_NAME'], '/admin/') === false): ?>
    <script src="<?= $baseUrl ?>/assets/js/app.js?v=<?= time() ?>"></script>
<?php endif; ?>

</body>
</html>
