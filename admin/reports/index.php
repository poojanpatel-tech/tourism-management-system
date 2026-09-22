<?php
/**
 * System Reports Module
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();
$errors = [];

// ---------------------------------------------------------
// 1. Filter Handling & Validation
// ---------------------------------------------------------
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$resStatus = trim($_GET['res_status'] ?? '');
$packageId = (int)($_GET['package_id'] ?? 0);
$customerSearch = trim($_GET['customer_search'] ?? '');
$destCountry = trim($_GET['dest_country'] ?? '');

// Date Validation
if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $errors[] = "Invalid 'Date From' format.";
    $dateFrom = '';
}
if ($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $errors[] = "Invalid 'Date To' format.";
    $dateTo = '';
}
if ($dateFrom !== '' && $dateTo !== '' && strtotime($dateFrom) > strtotime($dateTo)) {
    $errors[] = "'Date From' cannot be later than 'Date To'. Filter ignored.";
    $dateFrom = '';
    $dateTo = '';
}

// ---------------------------------------------------------
// 2. Report Queries
// ---------------------------------------------------------

// Common WHERE clause for Reservation Date filtering
$resDateWhere = "";
$resParams = [];

if ($dateFrom !== '') {
    $resDateWhere .= " AND DATE(r.reservation_date) >= :dfrom";
    $resParams[':dfrom'] = $dateFrom;
}
if ($dateTo !== '') {
    $resDateWhere .= " AND DATE(r.reservation_date) <= :dto";
    $resParams[':dto'] = $dateTo;
}
if ($resStatus !== '' && in_array($resStatus, ['pending', 'confirmed', 'cancelled'], true)) {
    $resDateWhere .= " AND r.status = :status";
    $resParams[':status'] = $resStatus;
}
if ($packageId > 0) {
    $resDateWhere .= " AND r.package_id = :pkg_id";
    $resParams[':pkg_id'] = $packageId;
}

try {
    // A. Reservation Summary Report
    $sqlResSum = '
        SELECT 
            COUNT(r.reservation_id) AS total_reservations,
            SUM(CASE WHEN r.status = "confirmed" THEN 1 ELSE 0 END) AS confirmed_count,
            SUM(CASE WHEN r.status = "pending" THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN r.status = "cancelled" THEN 1 ELSE 0 END) AS cancelled_count,
            COALESCE(SUM(CASE WHEN r.status != "cancelled" THEN r.number_of_travelers ELSE 0 END), 0) AS total_travelers,
            COALESCE(SUM(CASE WHEN r.status = "confirmed" THEN r.total_amount ELSE 0 END), 0) AS confirmed_revenue
        FROM reservations r
        WHERE 1=1 ' . $resDateWhere;
    
    $stmtSum = $pdo->prepare($sqlResSum);
    $stmtSum->execute($resParams);
    $reportReservation = $stmtSum->fetch(PDO::FETCH_ASSOC);

    // B. Package Report
    $sqlPkg = '
        SELECT p.package_id, p.package_code, p.package_name, p.maximum_capacity, p.status,
               d.destination_name,
               COALESCE(SUM(CASE WHEN r.status IN ("confirmed", "pending") THEN r.number_of_travelers ELSE 0 END), 0) AS booked_capacity,
               COUNT(r.reservation_id) AS total_bookings,
               COALESCE(SUM(CASE WHEN r.status = "confirmed" THEN r.total_amount ELSE 0 END), 0) AS confirmed_revenue,
               COALESCE(SUM(CASE WHEN r.status != "cancelled" THEN r.number_of_travelers ELSE 0 END), 0) AS all_time_travelers
        FROM packages p
        LEFT JOIN destinations d ON p.destination_id = d.destination_id
        LEFT JOIN reservations r ON p.package_id = r.package_id
        GROUP BY p.package_id, p.package_code, p.package_name, p.maximum_capacity, p.status, d.destination_name
        ORDER BY confirmed_revenue DESC, p.package_name ASC
    ';
    $reportPackages = $pdo->query($sqlPkg)->fetchAll();

    // C. Customer Report
    $sqlCust = '
        SELECT c.customer_code, c.full_name, c.email, c.phone,
               COUNT(r.reservation_id) AS reservation_count,
               COALESCE(SUM(CASE WHEN r.status != "cancelled" THEN r.number_of_travelers ELSE 0 END), 0) AS travelers_count,
               COALESCE(SUM(CASE WHEN r.status = "confirmed" THEN r.total_amount ELSE 0 END), 0) AS confirmed_value
        FROM customers c
        LEFT JOIN reservations r ON c.customer_id = r.customer_id
        WHERE c.full_name LIKE :cust_name OR c.email LIKE :cust_email OR c.customer_code LIKE :cust_code
        GROUP BY c.customer_id, c.customer_code, c.full_name, c.email, c.phone
        ORDER BY confirmed_value DESC, c.full_name ASC
        LIMIT 50
    ';
    $stmtCust = $pdo->prepare($sqlCust);
    $stmtCust->execute([
        ':cust_name'  => '%' . $customerSearch . '%',
        ':cust_email' => '%' . $customerSearch . '%',
        ':cust_code'  => '%' . $customerSearch . '%'
    ]);
    $reportCustomers = $stmtCust->fetchAll();

    // D. Destination Report
    $sqlDest = '
        SELECT d.destination_name, d.country,
               COUNT(DISTINCT p.package_id) AS package_count,
               COUNT(r.reservation_id) AS reservation_count,
               COALESCE(SUM(CASE WHEN r.status != "cancelled" THEN r.number_of_travelers ELSE 0 END), 0) AS total_travelers,
               COALESCE(SUM(CASE WHEN r.status = "confirmed" THEN r.total_amount ELSE 0 END), 0) AS confirmed_revenue
        FROM destinations d
        LEFT JOIN packages p ON d.destination_id = p.destination_id
        LEFT JOIN reservations r ON p.package_id = r.package_id
        WHERE d.country LIKE :country
        GROUP BY d.destination_id, d.destination_name, d.country
        ORDER BY confirmed_revenue DESC, d.destination_name ASC
    ';
    $stmtDest = $pdo->prepare($sqlDest);
    $stmtDest->execute([':country' => '%' . $destCountry . '%']);
    $reportDestinations = $stmtDest->fetchAll();

    // Dropdowns data
    $allPackages = $pdo->query('SELECT package_id, package_name, package_code FROM packages ORDER BY package_name ASC')->fetchAll();

} catch (PDOException $e) {
    error_log("Database reporting error: " . $e->getMessage());
    $errors[] = "Unable to load the report. Please try again.";
    
    // Initialize empty defaults so view doesn't crash
    $reportReservation = [
        'total_reservations' => 0, 'confirmed_count' => 0, 'pending_count' => 0,
        'cancelled_count' => 0, 'total_travelers' => 0, 'confirmed_revenue' => 0
    ];
    $reportPackages = [];
    $reportCustomers = [];
    $reportDestinations = [];
}

$pageTitle = 'System Reports';
$activePage = 'reports';
$navTitle = 'Financial & Operational Reports';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<!-- Custom Print CSS -->
<style>
    @media print {
        body { background-color: #fff !important; }
        .sidebar, .navbar, .no-print { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; margin-bottom: 20px; page-break-inside: avoid; }
        .card-header { background-color: #f8f9fa !important; border-bottom: 1px solid #ddd !important; }
        .table { border-collapse: collapse !important; width: 100% !important; }
        .table th, .table td { border: 1px solid #ddd !important; padding: 6px !important; font-size: 11pt !important; }
        .badge { border: 1px solid #000 !important; color: #000 !important; background: transparent !important; }
        .print-header { display: block !important; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
    }
    .print-header { display: none; }
</style>

<div class="container-fluid p-0">
    
    <!-- Printable Document Header (Hidden on Screen) -->
    <div class="print-header">
        <h2>PATEL TRAVELS</h2>
        <p class="mb-0 text-muted">Head Office: SBR, Ahmedabad (AHM)</p>
        <p class="mb-0"><strong>Financial & Operational Report</strong></p>
        <p class="mb-0 small">Generated: <?= date('F j, Y, g:i a') ?></p>
        <p class="small text-muted">
            Filters Applied: 
            <?= $dateFrom ? "From $dateFrom " : "" ?>
            <?= $dateTo ? "To $dateTo " : "" ?>
            <?= $resStatus ? "Status: $resStatus" : "All Statuses" ?>
        </p>
    </div>

    <!-- Page Header (Screen Only) -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom no-print">
        <div>
            <h3 class="fw-bold mb-1 text-dark">System Reports</h3>
            <p class="text-muted small mb-0">Generate, view, and print operational data.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary shadow-sm">
                <i class="bi bi-printer me-1"></i> Print Full Report
            </button>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger no-print">
            <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Filter Errors:</strong>
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Master Filter Form (Screen Only) -->
    <div class="card mb-4 border-0 shadow-sm bg-light no-print">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('admin/reports/index.php') ?>" class="row g-3 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1 fw-bold">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1 fw-bold">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1 fw-bold">Res. Status</label>
                    <select name="res_status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= ($resStatus === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= ($resStatus === 'confirmed') ? 'selected' : '' ?>>Confirmed</option>
                        <option value="cancelled" <?= ($resStatus === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1 fw-bold">Package</label>
                    <select name="package_id" class="form-select form-select-sm">
                        <option value="0">All Packages</option>
                        <?php foreach ($allPackages as $pkg): ?>
                            <option value="<?= $pkg['package_id'] ?>" <?= ($packageId === (int)$pkg['package_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pkg['package_code'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1 fw-bold">Customer Search</label>
                    <input type="text" name="customer_search" class="form-control form-control-sm" placeholder="Name or email" value="<?= htmlspecialchars($customerSearch, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Apply</button>
                    <a href="<?= url('admin/reports/index.php') ?>" class="btn btn-sm btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-circle"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- A. RESERVATION SUMMARY REPORT              -->
    <!-- ========================================== -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-journal-check me-2"></i>A. Reservation Summary Report</h5>
        </div>
        <div class="card-body">
            <div class="row text-center g-4">
                <div class="col-6 col-md-2 border-end">
                    <div class="text-muted small fw-bold text-uppercase">Total Bookings</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace"><?= (int)$reportReservation['total_reservations'] ?></div>
                </div>
                <div class="col-6 col-md-2 border-end">
                    <div class="text-muted small fw-bold text-uppercase">Confirmed</div>
                    <div class="fs-4 fw-bold text-success mt-1 font-monospace"><?= (int)$reportReservation['confirmed_count'] ?></div>
                </div>
                <div class="col-6 col-md-2 border-end">
                    <div class="text-muted small fw-bold text-uppercase">Pending</div>
                    <div class="fs-4 fw-bold text-warning-emphasis mt-1 font-monospace"><?= (int)$reportReservation['pending_count'] ?></div>
                </div>
                <div class="col-6 col-md-2 border-end">
                    <div class="text-muted small fw-bold text-uppercase">Cancelled</div>
                    <div class="fs-4 fw-bold text-danger mt-1 font-monospace"><?= (int)$reportReservation['cancelled_count'] ?></div>
                </div>
                <div class="col-6 col-md-2 border-end">
                    <div class="text-muted small fw-bold text-uppercase">Total Travelers</div>
                    <div class="fs-4 fw-bold text-info mt-1 font-monospace"><?= (int)$reportReservation['total_travelers'] ?></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small fw-bold text-uppercase">Confirmed Revenue</div>
                    <div class="fs-4 fw-bold text-primary mt-1 font-monospace"><?= format_currency($reportReservation['confirmed_revenue']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- B. PACKAGE PERFORMANCE REPORT              -->
    <!-- ========================================== -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam me-2 text-info"></i>B. Package Performance Report</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Package Code & Name</th>
                        <th>Destination</th>
                        <th class="text-center">Capacity</th>
                        <th class="text-center">Booked</th>
                        <th class="text-center">Avail</th>
                        <th class="text-center">Total Bookings</th>
                        <th class="text-center">Travelers</th>
                        <th class="text-end">Confirmed Revenue</th>
                        <th class="text-center no-print">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportPackages)): ?>
                        <tr><td colspan="9" class="text-center py-3 text-muted">No packages found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportPackages as $pkg): ?>
                            <?php 
                                $cap = (int)$pkg['maximum_capacity'];
                                $booked = (int)$pkg['booked_capacity'];
                                $avail = max(0, $cap - $booked);
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($pkg['package_code'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($pkg['package_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td class="small"><?= htmlspecialchars($pkg['destination_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center fw-semibold text-muted font-monospace"><?= $cap ?></td>
                                <td class="text-center fw-semibold text-primary font-monospace"><?= $booked ?></td>
                                <td class="text-center fw-bold <?= $avail <= 0 ? 'text-danger' : 'text-success' ?> font-monospace"><?= $avail ?></td>
                                <td class="text-center fw-semibold font-monospace"><?= $pkg['total_bookings'] ?></td>
                                <td class="text-center fw-semibold font-monospace"><?= $pkg['all_time_travelers'] ?></td>
                                <td class="text-end fw-bold text-dark font-monospace"><?= format_currency($pkg['confirmed_revenue']) ?></td>
                                <td class="text-center no-print"><?= get_status_badge($pkg['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- ========================================== -->
        <!-- C. CUSTOMER REPORT                         -->
        <!-- ========================================== -->
        <div class="col-12 col-xl-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-people me-2 text-secondary"></i>C. Top Customers</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Customer</th>
                                <th class="text-center">Bookings</th>
                                <th class="text-center">Pax</th>
                                <th class="text-end">Confirmed Spend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reportCustomers)): ?>
                                <tr><td colspan="4" class="text-center py-3 text-muted">No customers found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reportCustomers as $cust): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($cust['full_name'], ENT_QUOTES, 'UTF-8') ?> <span class="text-muted small fw-normal font-monospace">(<?= $cust['customer_code'] ?>)</span></div>
                                            <div class="small text-muted text-truncate" style="max-width: 150px;"><?= htmlspecialchars($cust['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                        </td>
                                        <td class="text-center fw-semibold font-monospace"><?= $cust['reservation_count'] ?></td>
                                        <td class="text-center fw-semibold font-monospace"><?= $cust['travelers_count'] ?></td>
                                        <td class="text-end fw-bold text-success font-monospace"><?= format_currency($cust['confirmed_value']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- D. DESTINATION REPORT                      -->
        <!-- ========================================== -->
        <div class="col-12 col-xl-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-geo-alt me-2 text-danger"></i>D. Destination Overview</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Destination & Country</th>
                                <th class="text-center">Pkgs</th>
                                <th class="text-center">Bookings</th>
                                <th class="text-center">Pax</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reportDestinations)): ?>
                                <tr><td colspan="5" class="text-center py-3 text-muted">No destinations found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reportDestinations as $dest): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($dest['destination_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($dest['country'], ENT_QUOTES, 'UTF-8') ?></div>
                                        </td>
                                        <td class="text-center fw-semibold font-monospace"><?= $dest['package_count'] ?></td>
                                        <td class="text-center fw-semibold font-monospace"><?= $dest['reservation_count'] ?></td>
                                        <td class="text-center fw-semibold font-monospace"><?= $dest['total_travelers'] ?></td>
                                        <td class="text-end fw-bold text-success font-monospace"><?= format_currency($dest['confirmed_revenue']) ?></td>
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

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
