<?php
/**
 * Reservation Module - Index List
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$packageFilter = (int)($_GET['package'] ?? 0);
$dateFilter = trim($_GET['date'] ?? '');

// Fetch all active packages for dropdown filter
$stmtPkgs = $pdo->query('SELECT package_id, package_name, package_code FROM packages ORDER BY package_name ASC');
$packages = $stmtPkgs->fetchAll();

// Base query
$sql = '
    SELECT r.reservation_id, r.booking_number, r.travel_date, r.number_of_travelers, 
           r.total_amount, r.reservation_date, r.status,
           c.full_name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
           p.package_name, p.package_code,
           d.destination_name
    FROM reservations r
    INNER JOIN customers c ON r.customer_id = c.customer_id
    INNER JOIN packages p ON r.package_id = p.package_id
    INNER JOIN destinations d ON p.destination_id = d.destination_id
    WHERE 1=1
';
$params = [];

if ($search !== '') {
    $sql .= ' AND (r.booking_number LIKE :search OR c.full_name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search OR p.package_name LIKE :search OR p.package_code LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($statusFilter !== '' && in_array($statusFilter, ['pending', 'confirmed', 'cancelled'], true)) {
    $sql .= ' AND r.status = :status';
    $params[':status'] = $statusFilter;
}

if ($packageFilter > 0) {
    $sql .= ' AND r.package_id = :package';
    $params[':package'] = $packageFilter;
}

if ($dateFilter !== '') {
    // Basic date format validation to prevent SQL errors
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFilter)) {
        $sql .= ' AND r.travel_date = :date';
        $params[':date'] = $dateFilter;
    }
}

$sql .= ' ORDER BY r.reservation_date DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

$hasFilters = ($search !== '' || $statusFilter !== '' || $packageFilter > 0 || $dateFilter !== '');

$pageTitle = 'Manage Reservations';
$activePage = 'reservations';
$navTitle = 'Reservation Management';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <!-- Page Header & Actions -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Manage Reservations</h3>
            <p class="text-muted small mb-0">Track bookings, update statuses, and manage package availability.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= url('admin/reservations/create.php') ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-calendar-plus me-1"></i> New Reservation
            </a>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('admin/reservations/index.php') ?>" class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Booking #, Name, Email, Pkg..."
                               value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= ($statusFilter === 'confirmed') ? 'selected' : '' ?>>Confirmed</option>
                        <option value="cancelled" <?= ($statusFilter === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select name="package" class="form-select">
                        <option value="">All Packages</option>
                        <?php foreach ($packages as $pkg): ?>
                            <option value="<?= $pkg['package_id'] ?>" <?= ($packageFilter === (int)$pkg['package_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pkg['package_code'] . ' - ' . $pkg['package_name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($dateFilter, ENT_QUOTES, 'UTF-8') ?>" title="Travel Date">
                </div>
                <div class="col-6 col-md-2 d-flex gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary px-3 flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <?php if ($hasFilters): ?>
                        <a href="<?= url('admin/reservations/index.php') ?>" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Reservations Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Booking #</th>
                        <th>Customer</th>
                        <th>Tour Package</th>
                        <th>Travel Date</th>
                        <th class="text-center">Travelers</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th class="text-end" style="min-width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-1 d-block mb-2 text-muted"></i>
                                <h6 class="fw-bold text-dark">No Reservations Found</h6>
                                <p class="small text-muted mb-3">No bookings match your current filter criteria.</p>
                                <a href="<?= url('admin/reservations/create.php') ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-calendar-plus me-1"></i> Create Reservation
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reservations as $res): ?>
                            <?php
                            $resId = (int)$res['reservation_id'];
                            $badgeClass = 'bg-secondary';
                            if ($res['status'] === 'confirmed') $badgeClass = 'bg-success';
                            if ($res['status'] === 'pending') $badgeClass = 'bg-warning text-dark';
                            if ($res['status'] === 'cancelled') $badgeClass = 'bg-danger';
                            ?>
                            <tr>
                                <td class="font-monospace fw-bold text-primary small">
                                    <a href="<?= url('admin/reservations/view.php?id=' . $resId) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($res['booking_number'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($res['customer_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted small"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($res['customer_phone'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td>
                                    <div class="text-truncate fw-semibold" style="max-width: 180px;" title="<?= htmlspecialchars($res['package_name'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($res['package_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($res['destination_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td class="small">
                                    <?= format_date($res['travel_date']) ?>
                                    <div class="text-muted" style="font-size: 0.7rem;">Booked: <?= format_date($res['reservation_date'], 'M d') ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                        <?= (int)$res['number_of_travelers'] ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-dark">
                                    <?= format_currency($res['total_amount']) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?> text-uppercase" style="font-size: 0.7rem;">
                                        <?= htmlspecialchars($res['status'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= url('admin/reservations/view.php?id=' . $resId) ?>" class="btn btn-outline-info" title="View Booking">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= url('admin/reservations/edit.php?id=' . $resId) ?>" class="btn btn-outline-primary" title="Edit Booking">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        
                                        <!-- Actions Dropdown for Quick Status/Delete -->
                                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <?php if ($res['status'] === 'pending'): ?>
                                                <li>
                                                    <form action="<?= url('admin/reservations/status.php') ?>" method="POST">
                                                        <input type="hidden" name="reservation_id" value="<?= $resId ?>">
                                                        <input type="hidden" name="status" value="confirmed">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                                        <button type="submit" class="dropdown-item text-success">
                                                            <i class="bi bi-check-circle me-2"></i>Confirm Booking
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php if ($res['status'] !== 'cancelled'): ?>
                                                <li>
                                                    <form action="<?= url('admin/reservations/status.php') ?>" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking? This will free up the capacity.');">
                                                        <input type="hidden" name="reservation_id" value="<?= $resId ?>">
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                                        <button type="submit" class="dropdown-item text-warning">
                                                            <i class="bi bi-x-circle me-2"></i>Cancel Booking
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="<?= url('admin/reservations/delete.php') ?>" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to PERMANENTLY delete this reservation? Usually, cancelling is preferred to maintain historical records.');">
                                                    <input type="hidden" name="reservation_id" value="<?= $resId ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash3 me-2"></i>Delete Record
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
