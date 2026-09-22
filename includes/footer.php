<?php
/**
 * Global Footer Component
 * Tourism Management System (College Project)
 */
require_once __DIR__ . '/auth.php';
?>
    </main>
    <!-- /Main Content Container -->

    <!-- Global App Footer -->
    <footer class="app-footer">
        <div class="container-fluid d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
            <div>
                <strong>PATEL TRAVELS</strong> &copy; <?= date('Y') ?> &bull; Head Office: SBR, Ahmedabad (AHM) &bull; Tourism Management System
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-light text-muted border">PHP 8 + MySQL PDO</span>
                <span class="badge bg-light text-muted border">Bootstrap 5</span>
            </div>
        </div>
    </footer>
</div>
<!-- /Content Wrapper -->
</div>
<!-- /Wrapper -->

<!-- Bootstrap 5.3.3 Bundle JS with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Main JS -->
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
