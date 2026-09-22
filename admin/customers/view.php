<?php
/**
 * View Customer Profile
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash_message('danger', 'Invalid customer ID specified.');
    header('Location: ' . url('admin/customers/index.php'));
    exit;
}

try {
    // Fetch customer details
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE customer_id = :id');
    $stmt->execute([':id' => $id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        set_flash_message('danger', 'The requested customer does not exist.');
        header('Location: ' . url('admin/customers/index.php'));
        exit;
    }

    // Fetch reservation summary using aggregation
    $stmtSummary = $pdo->prepare('
        SELECT 
            COUNT(reservation_id) AS total_reservations,
            SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) AS confirmed_reservations,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_reservations,
            SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) AS cancelled_reservations,
            COALESCE(SUM(CASE WHEN status IN ("confirmed", "pending") THEN number_of_travelers ELSE 0 END), 0) AS total_travelers,
            COALESCE(SUM(CASE WHEN status = "confirmed" THEN total_amount ELSE 0 END), 0) AS total_revenue
        FROM reservations
        WHERE customer_id = :id
    ');
    $stmtSummary->execute([':id' => $id]);
    $summary = $stmtSummary->fetch();

    $totalReservations = (int)$summary['total_reservations'];
    
    // Fetch reservation history
    $stmtHistory = $pdo->prepare('
        SELECT r.reservation_id, r.booking_number, r.travel_date, r.number_of_travelers, 
               r.total_amount, r.reservation_date, r.status,
               p.package_name, p.package_code
        FROM reservations r
        INNER JOIN packages p ON r.package_id = p.package_id
        WHERE r.customer_id = :id
        ORDER BY r.reservation_date DESC
    ');
    $stmtHistory->execute([':id' => $id]);
    $reservations = $stmtHistory->fetchAll();

} catch (PDOException $e) {
    set_flash_message('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . url('admin/customers/index.php'));
    exit;
}

$pageTitle = 'Customer Profile: ' . $customer['full_name'];
$activePage = 'customers';
$navTitle = 'Customer Details';
$initials = strtoupper(substr($customer['full_name'], 0, 1));

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('admin/dashboard.php') ?>" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('admin/customers/index.php') ?>" class="text-decoration-none">Customers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Profile #<?= $id ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark d-flex align-items-center">
                <?= htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8') ?>
                <span class="badge bg-light text-muted border ms-3 font-monospace fs-6">
                    <?= htmlspecialchars($customer['customer_code'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </h3>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= url('admin/customers/edit.php?id=' . $id) ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-pencil-square me-1"></i> Edit Profile
            </a>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteCustomerModal">
                <i class="bi bi-trash3 me-1"></i> Delete
            </button>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Customer Profile Card -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4 text-center border-bottom">
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center mx-auto mb-3 fw-bold display-4" style="width: 100px; height: 100px;">
                        <?= $initials ?>
                    </div>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="text-muted mb-0"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($customer['city'] ? $customer['city'] . ', ' . $customer['country'] : $customer['country'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                
                <div class="card-body p-4">
                    <h6 class="fw-bold text-uppercase text-muted mb-3 small"><i class="bi bi-person me-2"></i>Personal Information</h6>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2 d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Gender</span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($customer['gender'], ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                        <li class="mb-2 d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Date of Birth</span>
                            <span class="fw-semibold text-dark"><?= $customer['date_of_birth'] ? format_date($customer['date_of_birth']) : 'N/A' ?></span>
                        </li>
                        <li class="mb-2 d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Registered On</span>
                            <span class="fw-semibold text-dark"><?= format_date($customer['created_at']) ?></span>
                        </li>
                    </ul>

                    <h6 class="fw-bold text-uppercase text-muted mb-3 small"><i class="bi bi-telephone me-2"></i>Contact Information</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <div class="text-muted small mb-1">Email Address</div>
                            <div class="fw-semibold text-dark"><a href="mailto:<?= htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none"><?= htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') ?></a></div>
                        </li>
                        <li class="mb-3">
                            <div class="text-muted small mb-1">Phone Number</div>
                            <div class="fw-semibold text-dark"><a href="tel:<?= htmlspecialchars($customer['phone'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none"><?= htmlspecialchars($customer['phone'], ENT_QUOTES, 'UTF-8') ?></a></div>
                        </li>
                        <li class="mb-1">
                            <div class="text-muted small mb-1">Full Address</div>
                            <div class="fw-semibold text-dark">
                                <?php if ($customer['address']): ?>
                                    <?= nl2br(htmlspecialchars($customer['address'], ENT_QUOTES, 'UTF-8')) ?><br>
                                <?php endif; ?>
                                <?= htmlspecialchars($customer['city'] ?? '', ENT_QUOTES, 'UTF-8') ?> 
                                <?= htmlspecialchars($customer['country'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Booking Stats & History -->
        <div class="col-12 col-xl-8">
            <!-- Booking Summary Widgets -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="card border-0 shadow-sm bg-primary text-white h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-white-50 small fw-semibold text-uppercase mb-1">Total Spent</div>
                                    <h4 class="fw-bold mb-0"><?= format_currency($summary['total_revenue']) ?></h4>
                                </div>
                                <div class="fs-1 text-white-50"><i class="bi bi-wallet2"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-6 col-md-4">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
                        <div class="card-body p-3">
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Reservations</div>
                            <div class="d-flex align-items-baseline gap-2">
                                <h4 class="fw-bold mb-0 text-dark"><?= (int)$summary['total_reservations'] ?></h4>
                                <span class="small text-muted">(<?= (int)$summary['confirmed_reservations'] ?> Confirmed)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                        <div class="card-body p-3">
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Total Travelers</div>
                            <div class="d-flex align-items-baseline gap-2">
                                <h4 class="fw-bold mb-0 text-dark"><?= (int)$summary['total_travelers'] ?></h4>
                                <span class="small text-muted">Pax (Active)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Status Breakdown -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3 d-flex justify-content-around text-center">
                    <div>
                        <div class="text-muted small mb-1">Confirmed</div>
                        <h5 class="fw-bold text-success mb-0"><?= (int)$summary['confirmed_reservations'] ?></h5>
                    </div>
                    <div class="border-end"></div>
                    <div>
                        <div class="text-muted small mb-1">Pending</div>
                        <h5 class="fw-bold text-warning mb-0"><?= (int)$summary['pending_reservations'] ?></h5>
                    </div>
                    <div class="border-end"></div>
                    <div>
                        <div class="text-muted small mb-1">Cancelled</div>
                        <h5 class="fw-bold text-danger mb-0"><?= (int)$summary['cancelled_reservations'] ?></h5>
                    </div>
                </div>
            </div>

            <!-- Reservation History Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Reservation History</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Booking #</th>
                                <th>Package</th>
                                <th>Travel Date</th>
                                <th class="text-center">Travelers</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reservations)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                        No reservation history for this customer.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reservations as $res): ?>
                                    <tr>
                                        <td class="font-monospace fw-bold text-primary small">
                                            <?= htmlspecialchars($res['booking_number'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($res['package_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($res['package_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div class="text-muted small font-monospace"><?= htmlspecialchars($res['package_code'], ENT_QUOTES, 'UTF-8') ?></div>
                                        </td>
                                        <td class="small">
                                            <?= format_date($res['travel_date']) ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border"><?= (int)$res['number_of_travelers'] ?></span>
                                        </td>
                                        <td class="fw-bold">
                                            <?= format_currency($res['total_amount']) ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = 'bg-secondary';
                                            if ($res['status'] === 'confirmed') $statusClass = 'bg-success';
                                            if ($res['status'] === 'pending') $statusClass = 'bg-warning text-dark';
                                            if ($res['status'] === 'cancelled') $statusClass = 'bg-danger';
                                            ?>
                                            <span class="badge <?= $statusClass ?> text-uppercase" style="font-size: 0.7rem;">
                                                <?= htmlspecialchars($res['status'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
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
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-shield-exclamation text-warning me-2"></i>Delete Customer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-2">
                    You are about to delete: <strong><?= htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    (<code class="text-primary"><?= htmlspecialchars($customer['customer_code'], ENT_QUOTES, 'UTF-8') ?></code>)
                </p>

                <?php if ($totalReservations > 0): ?>
                    <div class="alert alert-warning py-2 px-3 small my-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Reservation Dependency:</strong> This customer has <strong><?= $totalReservations ?></strong> reservation(s).
                        Deleting this customer is prevented to maintain accurate booking history and financial records.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info py-2 px-3 small my-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        This customer has no reservations and can be safely deleted.
                    </div>
                <?php endif; ?>

                <form action="<?= url('admin/customers/delete.php') ?>" method="POST"
                      onsubmit="return confirm('Are you sure you want to permanently delete this customer? This action cannot be undone.');">
                    <input type="hidden" name="customer_id" value="<?= $id ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger w-100 py-2 mt-2" <?= $totalReservations > 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-trash3 me-1"></i> <?= $totalReservations > 0 ? 'Cannot Delete Customer' : 'Permanently Delete Customer' ?>
                    </button>
                </form>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
