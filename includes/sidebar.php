<?php
/**
 * Global Sidebar Navigation Component
 * Tourism Management System (College Project)
 */
require_once __DIR__ . '/auth.php';

$activePage = $activePage ?? 'dashboard';
?>
<!-- Sidebar -->
<aside id="sidebar">
    <a href="<?= url('admin/dashboard.php') ?>" class="sidebar-brand">
        <i class="bi bi-compass-fill"></i>
        <span>PATEL TRAVELS</span>
    </a>

    <div class="sidebar-nav">
        <div class="sidebar-heading">Main Navigation</div>

        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link <?= ($activePage === 'dashboard') ? 'active' : '' ?>" href="<?= url('admin/dashboard.php') ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-heading">Tourism Management</div>

        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link <?= ($activePage === 'destinations') ? 'active' : '' ?>" href="<?= url('admin/destinations/index.php') ?>">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>Destinations</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($activePage === 'packages') ? 'active' : '' ?>" href="<?= url('admin/packages/index.php') ?>">
                    <i class="bi bi-box2-heart-fill"></i>
                    <span>Tour Packages</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($activePage === 'customers') ? 'active' : '' ?>" href="<?= url('admin/customers/index.php') ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Customers</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($activePage === 'reservations') ? 'active' : '' ?>" href="<?= url('admin/reservations/index.php') ?>">
                    <i class="bi bi-calendar-check-fill"></i>
                    <span>Reservations</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-heading">Analytics & Reports</div>

        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link <?= ($activePage === 'reports') ? 'active' : '' ?>" href="<?= url('admin/reports/index.php') ?>">
                    <i class="bi bi-bar-chart-fill"></i>
                    <span>Reports & Summary</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-center">
            <a href="<?= url('auth/logout.php') ?>" class="btn btn-sm btn-danger text-nowrap w-100" data-confirm="Are you sure you want to log out?">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</aside>
<!-- /Sidebar -->
