<?php
/**
 * View Reservation / Printable Booking Voucher
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash_message('danger', 'Invalid reservation ID specified.');
    header('Location: ' . url('admin/reservations/index.php'));
    exit;
}

try {
    // Comprehensive JOIN to get all booking details
    $stmt = $pdo->prepare('
        SELECT r.*,
               c.customer_code, c.full_name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, c.city, c.country AS customer_country,
               p.package_code, p.package_name, p.price, p.maximum_capacity, p.duration, p.travel_date AS package_travel_date,
               d.destination_name, d.country AS destination_country
        FROM reservations r
        INNER JOIN customers c ON r.customer_id = c.customer_id
        INNER JOIN packages p ON r.package_id = p.package_id
        INNER JOIN destinations d ON p.destination_id = d.destination_id
        WHERE r.reservation_id = :id
    ');
    $stmt->execute([':id' => $id]);
    $res = $stmt->fetch();

    if (!$res) {
        set_flash_message('danger', 'The requested reservation does not exist.');
        header('Location: ' . url('admin/reservations/index.php'));
        exit;
    }

    // Fetch capacity usage for this specific package to show Availability Snapshot
    $stmtCap = $pdo->prepare('
        SELECT COALESCE(SUM(number_of_travelers), 0) AS booked
        FROM reservations
        WHERE package_id = :pkg_id AND status IN ("confirmed", "pending")
    ');
    $stmtCap->execute([':pkg_id' => $res['package_id']]);
    $bookedOverall = (int)$stmtCap->fetchColumn();
    $capacity = (int)$res['maximum_capacity'];
    $remaining = max(0, $capacity - $bookedOverall);

} catch (PDOException $e) {
    set_flash_message('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . url('admin/reservations/index.php'));
    exit;
}

$pageTitle = 'Booking Voucher - ' . $res['booking_number'];
$activePage = 'reservations';
$navTitle = 'Reservation Details';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<!-- Custom CSS for Printable Voucher -->
<style>
    @media print {
        body { background-color: #fff !important; }
        .sidebar, .navbar, .no-print { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        .card { border: 2px solid #ddd !important; box-shadow: none !important; margin-bottom: 20px; page-break-inside: avoid; }
        .badge { border: 1px solid #000 !important; color: #000 !important; background: transparent !important; }
        .voucher-header { border-bottom: 2px solid #000 !important; padding-bottom: 15px; margin-bottom: 20px; }
    }
</style>

<div class="container-fluid p-0">
    <!-- Non-printable Actions -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom no-print">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('admin/reservations/index.php') ?>" class="text-decoration-none">Reservations</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($res['booking_number'], ENT_QUOTES, 'UTF-8') ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">Booking Details</h3>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary shadow-sm">
                <i class="bi bi-printer me-1"></i> Print Voucher
            </button>
            <a href="<?= url('admin/reservations/edit.php?id=' . $id) ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil-square me-1"></i> Edit
            </a>
            <a href="<?= url('admin/reservations/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <!-- Printable Area -->
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 900px;" id="printableVoucher">
        <div class="card-body p-4 p-md-5">
            
            <!-- Voucher Header -->
            <div class="d-flex justify-content-between align-items-center voucher-header">
                <div>
                    <h2 class="fw-bold text-primary mb-0"><i class="bi bi-globe-americas me-2"></i>PATEL TRAVELS</h2>
                    <p class="text-muted small mb-0 fw-semibold">Head Office: SBR, Ahmedabad (AHM)</p>
                    <p class="text-muted small mb-0">Official Booking Voucher</p>
                </div>
                <div class="text-end">
                    <?php
                        $badgeClass = 'bg-secondary';
                        if ($res['status'] === 'confirmed') $badgeClass = 'bg-success';
                        if ($res['status'] === 'pending') $badgeClass = 'bg-warning text-dark';
                        if ($res['status'] === 'cancelled') $badgeClass = 'bg-danger';
                    ?>
                    <h4 class="mb-1 font-monospace fw-bold text-dark"><?= htmlspecialchars($res['booking_number'], ENT_QUOTES, 'UTF-8') ?></h4>
                    <span class="badge <?= $badgeClass ?> fs-6 text-uppercase px-3 py-2"><?= htmlspecialchars($res['status'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <!-- Customer Information -->
                <div class="col-12 col-md-6">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-person-badge text-primary me-2"></i>Customer Information</h6>
                    <table class="table table-sm table-borderless small">
                        <tr>
                            <td class="text-muted w-25">Code:</td>
                            <td class="fw-bold font-monospace text-secondary"><?= htmlspecialchars($res['customer_code'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Name:</td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($res['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email:</td>
                            <td><?= htmlspecialchars($res['customer_email'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Phone:</td>
                            <td><?= htmlspecialchars($res['customer_phone'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Location:</td>
                            <td><?= htmlspecialchars($res['city'] . ', ' . $res['customer_country'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Package Information -->
                <div class="col-12 col-md-6">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-box-seam text-info me-2"></i>Tour Package Details</h6>
                    <table class="table table-sm table-borderless small">
                        <tr>
                            <td class="text-muted w-25">Package:</td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($res['package_name'], ENT_QUOTES, 'UTF-8') ?> <span class="font-monospace text-muted small">(<?= htmlspecialchars($res['package_code'], ENT_QUOTES, 'UTF-8') ?>)</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Destination:</td>
                            <td><?= htmlspecialchars($res['destination_name'] . ', ' . $res['destination_country'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Duration:</td>
                            <td><?= htmlspecialchars($res['duration'], ENT_QUOTES, 'UTF-8') ?> Days</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Travel Date:</td>
                            <td class="fw-bold text-primary"><?= format_date($res['travel_date']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Base Price:</td>
                            <td><?= format_currency($res['price']) ?> <span class="text-muted small">/ person</span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <hr class="my-4">

            <!-- Booking Summary -->
            <div class="row g-4">
                <div class="col-12">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-calculator text-success me-2"></i>Financial & Booking Summary</h6>
                    
                    <div class="bg-light p-3 rounded">
                        <div class="row text-center mb-3">
                            <div class="col">
                                <div class="text-muted small">Travelers</div>
                                <div class="fs-4 fw-bold font-monospace text-dark"><?= (int)$res['number_of_travelers'] ?></div>
                            </div>
                            <div class="col d-flex align-items-center justify-content-center">
                                <i class="bi bi-x fs-4 text-muted"></i>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Price / Person</div>
                                <div class="fs-4 fw-bold font-monospace text-dark"><?= format_currency($res['price']) ?></div>
                            </div>
                            <div class="col d-flex align-items-center justify-content-center">
                                <i class="bi bi-pause text-muted" style="transform: rotate(90deg); font-size: 1.5rem;"></i>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Total Amount</div>
                                <div class="fs-3 fw-bold font-monospace text-primary"><?= format_currency($res['total_amount']) ?></div>
                            </div>
                        </div>

                        <?php if (!empty($res['notes'])): ?>
                            <div class="border-top pt-3 mt-3">
                                <strong class="small text-muted d-block mb-1">Special Notes / Requests:</strong>
                                <p class="mb-0 small fst-italic text-dark">"<?= nl2br(htmlspecialchars($res['notes'], ENT_QUOTES, 'UTF-8')) ?>"</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Meta & Availability Snapshot -->
            <div class="row">
                <div class="col-6">
                    <div class="text-muted small">
                        <strong>Reservation Date:</strong> <?= format_date($res['reservation_date'], 'F j, Y, g:i a') ?><br>
                        <strong>Last Updated:</strong> <?= format_date($res['updated_at'], 'F j, Y, g:i a') ?>
                    </div>
                </div>
                <div class="col-6 text-end">
                    <div class="border d-inline-block px-3 py-2 rounded bg-light text-start text-muted small">
                        <strong class="d-block text-dark border-bottom pb-1 mb-1"><i class="bi bi-pie-chart-fill me-1 text-info"></i>Package Snapshot</strong>
                        Capacity: <?= $capacity ?><br>
                        Currently Booked: <?= $bookedOverall ?><br>
                        Remaining: <strong><?= $remaining ?></strong>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
