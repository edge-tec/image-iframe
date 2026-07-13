        </div> <!-- /col-lg-10 -->
    </div> <!-- /row -->
</div> <!-- /container-fluid -->

<!-- jQuery 3.7 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5.3 Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Fabric.js 5.3 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php
$scriptName = basename($_SERVER['SCRIPT_NAME']);
$baseUrl = get_base_url();

if ($scriptName === 'frame-designer.php'): ?>
    <script src="<?= $baseUrl ?>/assets/js/frame-designer.js?v=<?= time() ?>"></script>
<?php else: ?>
    <script src="<?= $baseUrl ?>/assets/js/admin.js?v=<?= time() ?>"></script>
<?php endif; ?>

</body>
</html>
