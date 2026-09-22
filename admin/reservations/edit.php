<?php
/**
 * Edit Reservation
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

$id = (int)($_GET['id'] ?? $_POST['reservation_id'] ?? 0);

if ($id <= 0) {
    set_flash_message('danger', 'Invalid reservation ID specified.');
    header('Location: ' . url('admin/reservations/index.php'));
    exit;
}

// 1. Fetch existing reservation
try {
    $stmt = $pdo->prepare('SELECT * FROM reservations WHERE reservation_id = :id');
    $stmt->execute([':id' => $id]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        set_flash_message('danger', 'The requested reservation does not exist.');
        header('Location: ' . url('admin/reservations/index.php'));
        exit;
    }
} catch (PDOException $e) {
    set_flash_message('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . url('admin/reservations/index.php'));
    exit;
}

$errors = [];
$customerId = $reservation['customer_id'];
$packageId = $reservation['package_id'];
$travelDate = $reservation['travel_date'];
$travelers = $reservation['number_of_travelers'];
$status = $reservation['status'];
$notes = $reservation['notes'] ?? '';

// 2. Fetch dependencies for form dropdowns
$stmtCustomers = $pdo->query('SELECT customer_id, full_name, customer_code, email FROM customers ORDER BY full_name ASC');
$customers = $stmtCustomers->fetchAll();

// We need to fetch packages and their available capacity EXCLUDING this specific reservation
// so that editing a reservation doesn't count against itself
$stmtPackages = $pdo->prepare('
    SELECT p.package_id, p.package_code, p.package_name, p.price, p.maximum_capacity, p.travel_date,
           COALESCE(
               SUM(
                   CASE 
                       WHEN r.status IN ("confirmed", "pending") AND r.reservation_id != :exclude_id 
                       THEN r.number_of_travelers 
                       ELSE 0 
                   END
               ), 0
           ) AS booked_capacity,
           d.destination_name
    FROM packages p
    LEFT JOIN destinations d ON p.destination_id = d.destination_id
    LEFT JOIN reservations r ON p.package_id = r.package_id
    WHERE p.status = "active" OR p.package_id = :current_pkg_id
    GROUP BY p.package_id, p.package_code, p.package_name, p.price, p.maximum_capacity, p.travel_date, d.destination_name
    ORDER BY p.package_name ASC
');
$stmtPackages->execute([':exclude_id' => $id, ':current_pkg_id' => $packageId]);
$packages = $stmtPackages->fetchAll();

// Organize package data for JavaScript consumption
$packageData = [];
foreach ($packages as $pkg) {
    $capacity = (int)$pkg['maximum_capacity'];
    $booked = (int)$pkg['booked_capacity'];
    $available = max(0, $capacity - $booked);
    
    $packageData[$pkg['package_id']] = [
        'name'        => $pkg['package_name'],
        'destination' => $pkg['destination_name'],
        'price'       => (float)$pkg['price'],
        'capacity'    => $capacity,
        'booked'      => $booked,
        'available'   => $available
    ];
}

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $packageId = (int)($_POST['package_id'] ?? 0);
    $travelDate = trim($_POST['travel_date'] ?? '');
    $travelers = (int)($_POST['number_of_travelers'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';

    // Validation
    if (!verify_csrf_token($csrf)) {
        $errors[] = 'Invalid security token. Please try submitting again.';
    }
    if ($customerId <= 0) $errors[] = 'Please select a valid customer.';
    if ($packageId <= 0) $errors[] = 'Please select a valid package.';
    if ($travelDate === '') {
        $errors[] = 'Travel date is required.';
    } elseif (!strtotime($travelDate)) {
        $errors[] = 'Invalid travel date format.';
    }
    if ($travelers < 1) $errors[] = 'Number of travelers must be at least 1.';
    if (!in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
        $errors[] = 'Invalid reservation status.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Lock and Load Package Price & Capacity EXCLUDING current reservation
            $stmtLock = $pdo->prepare('
                SELECT p.price, p.maximum_capacity,
                       COALESCE(
                           SUM(
                               CASE 
                                   WHEN r.status IN ("confirmed", "pending") AND r.reservation_id != :exclude_id 
                                   THEN r.number_of_travelers 
                                   ELSE 0 
                               END
                           ), 0
                       ) AS booked_capacity
                FROM packages p
                LEFT JOIN reservations r ON p.package_id = r.package_id
                WHERE p.package_id = :pkg_id
                GROUP BY p.package_id
                FOR UPDATE
            ');
            $stmtLock->execute([':exclude_id' => $id, ':pkg_id' => $packageId]);
            $lockedPkg = $stmtLock->fetch();

            if (!$lockedPkg) {
                throw new Exception('Selected package does not exist.');
            }

            // 2. Validate Capacity if we are keeping/changing it to a consuming status
            if ($status !== 'cancelled') {
                $availableSeats = max(0, (int)$lockedPkg['maximum_capacity'] - (int)$lockedPkg['booked_capacity']);
                if ($travelers > $availableSeats) {
                    throw new Exception("Capacity exceeded. Only {$availableSeats} seats are available (excluding your current booking).");
                }
            }

            // 3. Server-side Calculation of Total Amount
            $totalAmount = (float)$lockedPkg['price'] * $travelers;

            // 4. Update Reservation
            $stmtUpdate = $pdo->prepare('
                UPDATE reservations 
                SET customer_id = :cust_id, 
                    package_id = :pkg_id, 
                    travel_date = :date, 
                    number_of_travelers = :pax, 
                    total_amount = :amount, 
                    status = :status, 
                    notes = :notes,
                    updated_at = NOW()
                WHERE reservation_id = :id
            ');
            $stmtUpdate->execute([
                ':cust_id' => $customerId,
                ':pkg_id'  => $packageId,
                ':date'    => $travelDate,
                ':pax'     => $travelers,
                ':amount'  => $totalAmount,
                ':status'  => $status,
                ':notes'   => $notes !== '' ? $notes : null,
                ':id'      => $id
            ]);

            $pdo->commit();

            set_flash_message('success', "Reservation {$reservation['booking_number']} updated successfully.");
            header('Location: ' . url('admin/reservations/index.php'));
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Update failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Reservation';
$activePage = 'reservations';
$navTitle = 'Edit Booking';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('admin/dashboard.php') ?>" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('admin/reservations/index.php') ?>" class="text-decoration-none">Reservations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit #<?= $id ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">
                Edit Booking: <span class="font-monospace text-primary"><?= htmlspecialchars($reservation['booking_number'], ENT_QUOTES, 'UTF-8') ?></span>
            </h3>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= url('admin/reservations/view.php?id=' . $id) ?>" class="btn btn-outline-info">
                <i class="bi bi-eye me-1"></i> View Booking
            </a>
            <a href="<?= url('admin/reservations/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                <strong>Update Error:</strong>
            </div>
            <ul class="mb-0 ps-4">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Form Section -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Update Booking Details</h5>
                    <?php
                        $badgeClass = 'bg-secondary';
                        if ($reservation['status'] === 'confirmed') $badgeClass = 'bg-success';
                        if ($reservation['status'] === 'pending') $badgeClass = 'bg-warning text-dark';
                        if ($reservation['status'] === 'cancelled') $badgeClass = 'bg-danger';
                    ?>
                    <span class="badge <?= $badgeClass ?> text-uppercase"><?= htmlspecialchars($reservation['status'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="card-body p-4">
                    <form action="<?= url('admin/reservations/edit.php?id=' . $id) ?>" method="POST" id="reservationForm">
                        <input type="hidden" name="reservation_id" value="<?= $id ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="customer_id" class="form-label fw-semibold small text-muted">Customer <span class="text-danger">*</span></label>
                                <select class="form-select" id="customer_id" name="customer_id" required>
                                    <?php foreach ($customers as $cust): ?>
                                        <option value="<?= $cust['customer_id'] ?>" <?= ((int)$customerId === (int)$cust['customer_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cust['customer_code'] . ' - ' . $cust['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="package_id" class="form-label fw-semibold small text-muted">Tour Package <span class="text-danger">*</span></label>
                                <select class="form-select" id="package_id" name="package_id" required>
                                    <?php foreach ($packages as $pkg): ?>
                                        <?php
                                        // Disable if no seats left (excluding current booking)
                                        $available = max(0, $pkg['maximum_capacity'] - $pkg['booked_capacity']);
                                        $disabled = ($available <= 0 && (int)$packageId !== (int)$pkg['package_id']) ? 'disabled' : '';
                                        $statusText = $available <= 0 ? ' (SOLD OUT)' : " ({$available} available)";
                                        ?>
                                        <option value="<?= $pkg['package_id'] ?>" <?= ((int)$packageId === (int)$pkg['package_id']) ? 'selected' : '' ?> <?= $disabled ?>>
                                            <?= htmlspecialchars($pkg['package_code'] . ' - ' . $pkg['package_name'] . $statusText, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="travel_date" class="form-label fw-semibold small text-muted">Travel Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="travel_date" name="travel_date"
                                       value="<?= htmlspecialchars($travelDate, ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="number_of_travelers" class="form-label fw-semibold small text-muted">Travelers <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="number_of_travelers" name="number_of_travelers"
                                       value="<?= $travelers ?>" min="1" required>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="status" class="form-label fw-semibold small text-muted">Booking Status <span class="text-danger">*</span></label>
                                <select class="form-select fw-bold" id="status" name="status" required>
                                    <option value="pending" <?= ($status === 'pending') ? 'selected' : '' ?> class="text-warning">Pending</option>
                                    <option value="confirmed" <?= ($status === 'confirmed') ? 'selected' : '' ?> class="text-success">Confirmed</option>
                                    <option value="cancelled" <?= ($status === 'cancelled') ? 'selected' : '' ?> class="text-danger">Cancelled</option>
                                </select>
                                <div class="form-text text-muted" style="font-size: 0.7rem;">Cancelling frees capacity.</div>
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label fw-semibold small text-muted">Notes / Requests</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= url('admin/reservations/index.php') ?>" class="btn btn-light border px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btnSubmit">
                                <i class="bi bi-save me-1"></i> Update Reservation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Live Preview Sidebar -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-calculator text-success me-2"></i>Live Calculation</h5>
                </div>
                <div class="card-body p-4 bg-light">
                    
                    <div id="summaryContent">
                        <div class="text-muted small mb-1"><i class="bi bi-geo-alt me-1"></i><span id="previewDestination">Destination</span></div>
                        <h6 class="fw-bold text-dark text-truncate mb-3" id="previewPackageName">Package Name</h6>
                        
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted small">Price per person</span>
                            <span class="fw-semibold font-monospace" id="previewPrice">₹0.00</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted small">Travelers</span>
                            <span class="fw-semibold font-monospace">x <span id="previewTravelers">1</span></span>
                        </div>

                        <div class="d-flex justify-content-between mb-4 pb-2 border-bottom">
                            <span class="fw-bold text-dark">Total Amount</span>
                            <span class="fw-bold text-primary fs-5 font-monospace" id="previewTotal">₹0.00</span>
                        </div>

                        <div class="alert alert-info py-2 px-3 small mb-0 d-flex justify-content-between align-items-center">
                            <span>Avail. Seats (Excl. this booking):</span>
                            <strong class="fs-6" id="previewAvailable">0</strong>
                        </div>
                        <div id="capacityWarning" class="alert alert-danger py-2 px-3 small mt-2 mb-0" style="display: none;">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Exceeds available capacity.
                        </div>
                        
                        <div id="cancelledNotice" class="alert alert-secondary py-2 px-3 small mt-2 mb-0" style="display: none;">
                            <i class="bi bi-info-circle me-1"></i> Status is Cancelled. Capacity will not be consumed.
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const packageData = <?= json_encode($packageData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;

    document.addEventListener('DOMContentLoaded', function () {
        const packageSelect = document.getElementById('package_id');
        const travelersInput = document.getElementById('number_of_travelers');
        const statusSelect = document.getElementById('status');
        const btnSubmit = document.getElementById('btnSubmit');

        function updateSummary() {
            const pkgId = packageSelect.value;
            const travelers = parseInt(travelersInput.value, 10) || 0;
            const status = statusSelect.value;

            if (pkgId && packageData[pkgId]) {
                const pkg = packageData[pkgId];
                
                document.getElementById('previewPackageName').textContent = pkg.name;
                document.getElementById('previewDestination').textContent = pkg.destination;
                
                const formatter = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' });
                document.getElementById('previewPrice').textContent = formatter.format(pkg.price);
                document.getElementById('previewTravelers').textContent = travelers;
                
                const total = pkg.price * travelers;
                document.getElementById('previewTotal').textContent = formatter.format(total);
                document.getElementById('previewAvailable').textContent = pkg.available;

                const cancelledNotice = document.getElementById('cancelledNotice');
                const capacityWarning = document.getElementById('capacityWarning');

                if (status === 'cancelled') {
                    cancelledNotice.style.display = 'block';
                    capacityWarning.style.display = 'none';
                    btnSubmit.disabled = false;
                } else {
                    cancelledNotice.style.display = 'none';
                    if (travelers > pkg.available) {
                        capacityWarning.style.display = 'block';
                        btnSubmit.disabled = true;
                    } else {
                        capacityWarning.style.display = 'none';
                        btnSubmit.disabled = false;
                    }
                }
            }
        }

        packageSelect.addEventListener('change', updateSummary);
        travelersInput.addEventListener('input', updateSummary);
        statusSelect.addEventListener('change', updateSummary);

        updateSummary();
    });
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
