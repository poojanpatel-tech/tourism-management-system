<?php
/**
 * View Tour Package Details & Booking Overview
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash_message('danger', 'Invalid package ID specified.');
    header('Location: ' . url('admin/packages/index.php'));
    exit;
}

try {
    // 1. Fetch package with destination info
    $stmt = $pdo->prepare('
        SELECT p.*, d.destination_name, d.country, d.status AS dest_status
        FROM packages p
        INNER JOIN destinations d ON p.destination_id = d.destination_id
        WHERE p.package_id = :id
    ');
    $stmt->execute([':id' => $id]);
    $package = $stmt->fetch();

    if (!$package) {
        set_flash_message('danger', 'The requested tour package does not exist.');
        header('Location: ' . url('admin/packages/index.php'));
        exit;
    }

    // 2. Calculate real-time booking capacity from reservations
    $stmtBookings = $pdo->prepare('
        SELECT
            COALESCE(SUM(CASE WHEN status = "confirmed" THEN number_of_travelers ELSE 0 END), 0) AS confirmed_count,
            COALESCE(SUM(CASE WHEN status = "pending" THEN number_of_travelers ELSE 0 END), 0) AS pending_count,
            COALESCE(SUM(CASE WHEN status = "cancelled" THEN number_of_travelers ELSE 0 END), 0) AS cancelled_count
        FROM reservations
        WHERE package_id = :id
    ');
    $stmtBookings->execute([':id' => $id]);
    $bookingStats = $stmtBookings->fetch();

    $confirmedCount = (int)$bookingStats['confirmed_count'];
    $pendingCount = (int)$bookingStats['pending_count'];
    $cancelledCount = (int)$bookingStats['cancelled_count'];
    $currentBookings = $confirmedCount + $pendingCount;
    $capacity = (int)$package['maximum_capacity'];
    $available = max(0, $capacity - $currentBookings);
    $fillRate = $capacity > 0 ? min(100, round(($currentBookings / $capacity) * 100)) : 0;

    // Availability label
    if ($available <= 0) {
        $availLabel = 'Sold Out';
        $availClass = 'bg-danger';
    } elseif ($fillRate >= 50) {
        $availLabel = 'Limited';
        $availClass = 'bg-warning text-dark';
    } else {
        $availLabel = 'Available';
        $availClass = 'bg-success';
    }

    // 3. Fetch recent reservations for this package
    $stmtRes = $pdo->prepare('
        SELECT r.reservation_id, r.booking_number, r.travel_date, r.number_of_travelers,
               r.total_amount, r.reservation_date, r.status, r.notes,
               c.full_name AS customer_name, c.email AS customer_email
        FROM reservations r
        INNER JOIN customers c ON r.customer_id = c.customer_id
        WHERE r.package_id = :id
        ORDER BY r.reservation_date DESC
    ');
    $stmtRes->execute([':id' => $id]);
    $reservations = $stmtRes->fetchAll();
    $totalReservations = count($reservations);

} catch (PDOException $e) {
    set_flash_message('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . url('admin/packages/index.php'));
    exit;
}

$pageTitle = 'Package: ' . $package['package_name'];
$activePage = 'packages';
$navTitle = 'Package Profile';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('admin/dashboard.php') ?>" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('admin/packages/index.php') ?>" class="text-decoration-none">Tour Packages</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($package['package_code'], ENT_QUOTES, 'UTF-8') ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($package['package_name'], ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= url('admin/packages/edit.php?id=' . $id) ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-pencil-square me-1"></i> Edit Package
            </a>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="bi bi-trash3 me-1"></i> Delete / Deactivate
            </button>
            <a href="<?= url('admin/packages/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Capacity Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Total Capacity</div>
                        <div class="stat-number mt-1"><?= $capacity ?></div>
                    </div>
                    <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Confirmed</div>
                        <div class="stat-number mt-1 text-success"><?= $confirmedCount ?></div>
                    </div>
                    <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-check-circle"></i></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Pending</div>
                        <div class="stat-number mt-1 text-warning-emphasis"><?= $pendingCount ?></div>
                    </div>
                    <div class="stat-icon bg-warning-subtle text-warning-emphasis"><i class="bi bi-clock-history"></i></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Available Seats</div>
                        <div class="stat-number mt-1 <?= ($available <= 0 ? 'text-danger' : '') ?>"><?= $available ?></div>
                    </div>
                    <div class="stat-icon <?= ($available <= 0 ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info') ?>">
                        <i class="bi bi-<?= ($available <= 0 ? 'x-circle' : 'ticket-detailed') ?>"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Package Info Card -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-box2-heart-fill text-primary me-2"></i>Package Details</h5>
                    <span class="badge bg-light text-muted border font-monospace">ID #<?= (int)$package['package_id'] ?></span>
                </div>
                <div class="card-body p-4">
                    <!-- Package Code -->
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Package Code</label>
                        <div class="mt-1">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2 font-monospace">
                                <?= htmlspecialchars($package['package_code'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Package Name -->
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Package Name</label>
                        <h5 class="fw-bold text-dark mt-1"><?= htmlspecialchars($package['package_name'], ENT_QUOTES, 'UTF-8') ?></h5>
                    </div>

                    <!-- Destination & Country -->
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Destination</label>
                        <div class="mt-1">
                            <span class="badge bg-light text-dark border fs-6 px-3 py-2">
                                <i class="bi bi-geo-alt-fill text-danger me-2"></i><?= htmlspecialchars($package['destination_name'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="badge bg-light text-muted border px-2 py-2 ms-1">
                                <i class="bi bi-flag me-1 text-primary"></i><?= htmlspecialchars($package['country'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="text-muted small fw-semibold text-uppercase">Duration</label>
                            <div class="mt-1 fw-semibold">
                                <i class="bi bi-clock text-primary me-1"></i><?= htmlspecialchars($package['duration'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small fw-semibold text-uppercase">Price</label>
                            <div class="mt-1 fw-bold fs-5 text-dark"><?= format_currency($package['price']) ?></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="text-muted small fw-semibold text-uppercase">Travel Date</label>
                            <div class="mt-1 fw-semibold"><?= format_date($package['travel_date']) ?></div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small fw-semibold text-uppercase">Status</label>
                            <div class="mt-1"><?= get_status_badge($package['status']) ?></div>
                        </div>
                    </div>

                    <!-- Availability -->
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Availability</label>
                        <div class="mt-1">
                            <span class="badge <?= $availClass ?> px-3 py-2"><?= $availLabel ?></span>
                            <span class="ms-2 small text-muted font-monospace"><?= $currentBookings ?>/<?= $capacity ?> booked (<?= $available ?> free)</span>
                        </div>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar <?= ($fillRate >= 80 ? 'bg-danger' : ($fillRate >= 50 ? 'bg-warning' : 'bg-success')) ?>"
                                 role="progressbar" style="width: <?= $fillRate ?>%;">
                            </div>
                        </div>
                    </div>

                    <!-- Image -->
                    <?php
                    $imagePath = '';
                    $imageExists = false;
                    if (!empty($package['image'])) {
                        $fullPath = __DIR__ . '/../../assets/images/' . $package['image'];
                        if (file_exists($fullPath)) {
                            $imagePath = url('assets/images/' . $package['image']);
                            $imageExists = true;
                        }
                    }
                    ?>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Package Image</label>
                        <div class="mt-2">
                            <?php if ($imageExists): ?>
                                <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>"
                                     alt="<?= htmlspecialchars($package['package_name'], ENT_QUOTES, 'UTF-8') ?>"
                                     class="img-fluid rounded-3 border shadow-sm" style="max-height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center text-muted" style="height: 120px;">
                                    <div class="text-center">
                                        <i class="bi bi-image fs-2 d-block mb-1"></i>
                                        <small><?= !empty($package['image']) ? htmlspecialchars($package['image'], ENT_QUOTES, 'UTF-8') . ' (not found)' : 'No image uploaded' ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Timestamps -->
                    <div class="small text-muted">
                        <div class="d-flex justify-content-between py-1">
                            <span>Created:</span>
                            <span class="fw-semibold text-dark"><?= format_date($package['created_at'], 'M d, Y H:i') ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span>Last Updated:</span>
                            <span class="fw-semibold text-dark"><?= format_date($package['updated_at'] ?? $package['created_at'], 'M d, Y H:i') ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span>Total Reservations:</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">
                                <?= $totalReservations ?> Booking(s)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Description + Services + Reservations -->
        <div class="col-12 col-lg-7">
            <!-- Description & Services -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-card-text text-info me-2"></i>Description & Services</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Overview</label>
                        <div class="p-3 bg-light rounded-3 text-muted small mt-1 border" style="line-height: 1.7;">
                            <?= !empty($package['description']) ? nl2br(htmlspecialchars($package['description'], ENT_QUOTES, 'UTF-8')) : '<em>No description provided.</em>' ?>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="text-muted small fw-semibold text-uppercase"><i class="bi bi-check-circle text-success me-1"></i>Included Services</label>
                            <div class="p-3 bg-success-subtle rounded-3 small mt-1 border border-success-subtle" style="line-height: 1.7;">
                                <?= !empty($package['included_services']) ? nl2br(htmlspecialchars($package['included_services'], ENT_QUOTES, 'UTF-8')) : '<em class="text-muted">Not specified</em>' ?>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small fw-semibold text-uppercase"><i class="bi bi-x-circle text-danger me-1"></i>Excluded Services</label>
                            <div class="p-3 bg-danger-subtle rounded-3 small mt-1 border border-danger-subtle" style="line-height: 1.7;">
                                <?= !empty($package['excluded_services']) ? nl2br(htmlspecialchars($package['excluded_services'], ENT_QUOTES, 'UTF-8')) : '<em class="text-muted">Not specified</em>' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Associated Reservations -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar-check-fill text-warning me-2"></i>Reservations</h5>
                        <small class="text-muted">Bookings for <?= htmlspecialchars($package['package_code'], ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                    <span class="badge bg-primary px-3 py-2 rounded-pill font-monospace"><?= $totalReservations ?> Booking(s)</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($reservations)): ?>
                        <div class="text-center py-5 px-3">
                            <div class="mb-3 text-muted">
                                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                                <h6 class="fw-bold text-dark">No Reservations Yet</h6>
                                <p class="small text-muted col-md-8 mx-auto">
                                    No customers have booked this package yet. This package can be safely deleted if needed.
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Booking #</th>
                                        <th>Customer</th>
                                        <th>Travel Date</th>
                                        <th class="text-center">Travelers</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservations as $res): ?>
                                        <tr>
                                            <td class="font-monospace fw-bold small text-primary">
                                                <?= htmlspecialchars($res['booking_number'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?= htmlspecialchars($res['customer_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($res['customer_email'], ENT_QUOTES, 'UTF-8') ?></div>
                                            </td>
                                            <td class="small"><?= format_date($res['travel_date']) ?></td>
                                            <td class="text-center fw-semibold"><?= (int)$res['number_of_travelers'] ?></td>
                                            <td class="fw-bold"><?= format_currency($res['total_amount']) ?></td>
                                            <td><?= get_status_badge($res['status']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete / Deactivate Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="deleteModalLabel">
                    <i class="bi bi-shield-exclamation text-warning me-2"></i>Package Actions
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-2">
                    Managing package: <strong><?= htmlspecialchars($package['package_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    (<code><?= htmlspecialchars($package['package_code'], ENT_QUOTES, 'UTF-8') ?></code>)
                </p>

                <?php if ($totalReservations > 0): ?>
                    <div class="alert alert-warning py-2 px-3 small my-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Reservation Dependency:</strong> This package has <strong><?= $totalReservations ?> reservation(s)</strong>.
                        Deleting it would break customer booking records. We recommend deactivating it instead.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info py-2 px-3 small my-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        This package has 0 reservations and can be safely deleted or deactivated.
                    </div>
                <?php endif; ?>

                <div class="d-flex flex-column gap-2 mt-3">
                    <!-- Deactivate -->
                    <form action="<?= url('admin/packages/delete.php') ?>" method="POST">
                        <input type="hidden" name="package_id" value="<?= (int)$id ?>">
                        <input type="hidden" name="action" value="deactivate">
                        <button type="submit" class="btn btn-outline-warning text-dark w-100 py-2 text-start d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold"><i class="bi bi-pause-circle me-1"></i> Mark as Inactive</div>
                                <div class="small text-muted">Hides from new bookings while preserving all reservation records.</div>
                            </div>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>

                    <!-- Hard Delete -->
                    <form action="<?= url('admin/packages/delete.php') ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this package? This action cannot be undone.');">
                        <input type="hidden" name="package_id" value="<?= (int)$id ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-outline-danger w-100 py-2 text-start d-flex align-items-center justify-content-between mt-2" <?= ($totalReservations > 0 ? 'disabled' : '') ?>>
                            <div>
                                <div class="fw-bold"><i class="bi bi-trash3 me-1"></i> Permanently Delete</div>
                                <div class="small text-muted"><?= ($totalReservations > 0 ? 'Disabled because ' . $totalReservations . ' reservation(s) reference this package.' : 'Completely removes this package from MySQL.') ?></div>
                            </div>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
