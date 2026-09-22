<?php
/**
 * Global Topbar / Navbar Component
 * Tourism Management System (College Project)
 */
require_once __DIR__ . '/auth.php';

$currentUser = get_logged_in_user();
$adminName = $currentUser['full_name'] ?? 'Administrator';
$adminUser = $currentUser['username'] ?? 'admin';
$navTitle = $navTitle ?? 'Tourism Management Portal';
?>
<!-- Content Wrapper -->
<div id="content-wrapper">
    <!-- Topbar -->
    <header class="topbar">
        <div class="d-flex align-items-center">
            <button class="topbar-toggle me-3" id="sidebarToggle" title="Toggle Navigation Sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h5 class="mb-0 fw-bold text-dark d-none d-sm-block"><?= htmlspecialchars($navTitle, ENT_QUOTES, 'UTF-8') ?></h5>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Current Date Badge -->
            <div class="d-none d-md-flex align-items-center text-muted small bg-light px-3 py-1 rounded-pill border">
                <i class="bi bi-calendar3 me-2 text-primary"></i>
                <span><?= date('l, M d, Y') ?></span>
            </div>

            <!-- Database Status Indicator -->
            <div class="d-none d-lg-flex align-items-center small">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                    <i class="bi bi-database-check me-1"></i> MySQL Connected
                </span>
            </div>

            <!-- Admin Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light d-flex align-items-center gap-2 border px-3 py-1 rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                        <?= strtoupper(substr($adminName, 0, 1)) ?>
                    </div>
                    <div class="text-start d-none d-sm-block leading-tight">
                        <div class="fw-semibold small text-dark"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted" style="font-size: 0.72rem;">@<?= htmlspecialchars($adminUser, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <i class="bi bi-chevron-down small text-muted ms-1"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-2">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold small"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($currentUser['email'] ?? 'admin@tourism.local', ENT_QUOTES, 'UTF-8') ?></div>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="<?= url('admin/dashboard.php') ?>">
                            <i class="bi bi-speedometer2 me-2 text-primary"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="<?= url('index.php') ?>" target="_blank">
                            <i class="bi bi-box-arrow-up-right me-2 text-muted"></i> Public View
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item py-2 text-danger" href="<?= url('auth/logout.php') ?>" data-confirm="Are you sure you want to log out?">
                            <i class="bi bi-box-arrow-right me-2"></i> Log Out
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>
    <!-- /Topbar -->

    <!-- Main Content Container -->
    <main class="main-content">
        <?php render_flash_message(); ?>
