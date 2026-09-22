<?php
/**
 * Create Reservation
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();
$errors = [];

// 1. Fetch active customers
$stmtCustomers = $pdo->query('SELECT customer_id, full_name, customer_code, email FROM customers ORDER BY full_name ASC');
$customers = $stmtCustomers->fetchAll();

// 2. Fetch active packages with their current available capacity
$stmtPackages = $pdo->query('
    SELECT p.package_id, p.package_code, p.package_name, p.price, p.maximum_capacity, p.travel_date,
           COALESCE(SUM(CASE WHEN r.status IN ("confirmed", "pending") THEN r.number_of_travelers ELSE 0 END), 0) AS booked_capacity,
           d.destination_name
    FROM packages p
    LEFT JOIN destinations d ON p.destination_id = d.destination_id
    LEFT JOIN reservations r ON p.package_id = r.package_id
    WHERE p.status = "active"
    GROUP BY p.package_id, p.package_code, p.package_name, p.price, p.maximum_capacity, p.travel_date, d.destination_name
    ORDER BY p.package_name ASC
');
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
        'travel_date' => $pkg['travel_date'],
        'capacity'    => $capacity,
        'booked'      => $booked,
        'available'   => $available
    ];
}

$customerId = '';
$packageId = '';
$travelDate = '';
$travelers = 1;
$notes = '';

// Generate Booking Number Sequence
function generateBookingNumber(PDO $pdo): string
{
    $prefix = 'TRV' . date('Ymd');
    $stmt = $pdo->prepare("SELECT booking_number FROM reservations WHERE booking_number LIKE :prefix ORDER BY booking_number DESC LIMIT 1");
    $stmt->execute([':prefix' => $prefix . '%']);
    $lastCode = $stmt->fetchColumn();

    if ($lastCode) {
        $lastNum = (int)substr($lastCode, strlen($prefix));
        $nextNum = $lastNum + 1;
    } else {
        $nextNum = 1;
    }

    return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $packageId = (int)($_POST['package_id'] ?? 0);
    $travelDate = trim($_POST['travel_date'] ?? '');
    $travelers = (int)($_POST['number_of_travelers'] ?? 0);
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

    if (empty($errors)) {
        // Begin Transaction for Race-Condition Safety & Integrity
        try {
            $pdo->beginTransaction();

            // 1. Lock and Load Package Price & Capacity
            $stmtLock = $pdo->prepare('
                SELECT p.price, p.maximum_capacity, p.status,
                       COALESCE(SUM(CASE WHEN r.status IN ("confirmed", "pending") THEN r.number_of_travelers ELSE 0 END), 0) AS booked_capacity
                FROM packages p
                LEFT JOIN reservations r ON p.package_id = r.package_id
                WHERE p.package_id = :pkg_id
                GROUP BY p.package_id
                FOR UPDATE
            ');
            $stmtLock->execute([':pkg_id' => $packageId]);
            $lockedPkg = $stmtLock->fetch();

            if (!$lockedPkg) {
                throw new Exception('Selected package does not exist.');
            }
            if ($lockedPkg['status'] !== 'active') {
                throw new Exception('Selected package is no longer active.');
            }

            // 2. Validate Capacity
            $availableSeats = max(0, (int)$lockedPkg['maximum_capacity'] - (int)$lockedPkg['booked_capacity']);
            if ($travelers > $availableSeats) {
                throw new Exception("Capacity exceeded. Only {$availableSeats} seats are available for this package.");
            }

            // 3. Server-side Calculation of Total Amount (Never trust browser)
            $totalAmount = (float)$lockedPkg['price'] * $travelers;

            // 4. Generate Unique Booking Number
            $bookingNumber = generateBookingNumber($pdo);

            // 5. Insert Reservation
            $stmtInsert = $pdo->prepare('
                INSERT INTO reservations (booking_number, customer_id, package_id, travel_date, number_of_travelers, total_amount, status, notes, reservation_date)
                VALUES (:book_no, :cust_id, :pkg_id, :date, :pax, :amount, "pending", :notes, NOW())
            ');
            $stmtInsert->execute([
                ':book_no' => $bookingNumber,
                ':cust_id' => $customerId,
                ':pkg_id'  => $packageId,
                ':date'    => $travelDate,
                ':pax'     => $travelers,
                ':amount'  => $totalAmount,
                ':notes'   => $notes !== '' ? $notes : null
            ]);

            $pdo->commit();

            set_flash_message('success', "Reservation {$bookingNumber} created successfully for {$travelers} traveler(s).");
            header('Location: ' . url('admin/reservations/index.php'));
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Booking failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Create Reservation';
$activePage = 'reservations';
$navTitle = 'New Booking';

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
                    <li class="breadcrumb-item"><a href="<?= url('admin/reservations/index.php') ?>" class="text-decoration-none">Reservations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">Create New Reservation</h3>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="<?= url('admin/reservations/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Reservations
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                <strong>Booking Error:</strong>
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
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-ui-checks-grid text-primary me-2"></i>Booking Details</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= url('admin/reservations/create.php') ?>" method="POST" id="reservationForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="row g-3">
                            <!-- Customer Selection -->
                            <div class="col-12">
                                <label for="customer_id" class="form-label fw-semibold small text-muted">Select Customer <span class="text-danger">*</span></label>
                                <select class="form-select" id="customer_id" name="customer_id" required>
                                    <option value="">-- Choose a Customer --</option>
                                    <?php foreach ($customers as $cust): ?>
                                        <option value="<?= $cust['customer_id'] ?>" <?= ((int)$customerId === (int)$cust['customer_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cust['customer_code'] . ' - ' . $cust['full_name'] . ' (' . $cust['email'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Package Selection -->
                            <div class="col-12">
                                <label for="package_id" class="form-label fw-semibold small text-muted">Select Tour Package <span class="text-danger">*</span></label>
                                <select class="form-select" id="package_id" name="package_id" required>
                                    <option value="">-- Choose a Package --</option>
                                    <?php foreach ($packages as $pkg): ?>
                                        <?php
                                        // Disable if no seats left
                                        $available = max(0, $pkg['maximum_capacity'] - $pkg['booked_capacity']);
                                        $disabled = $available <= 0 ? 'disabled' : '';
                                        $statusText = $available <= 0 ? ' (SOLD OUT)' : " ({$available} seats left)";
                                        ?>
                                        <option value="<?= $pkg['package_id'] ?>" <?= ((int)$packageId === (int)$pkg['package_id']) ? 'selected' : '' ?> <?= $disabled ?>>
                                            <?= htmlspecialchars($pkg['package_code'] . ' - ' . $pkg['package_name'] . $statusText, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Travel Date & Travelers -->
                            <div class="col-12 col-md-6">
                                <label for="travel_date" class="form-label fw-semibold small text-muted">Travel Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="travel_date" name="travel_date"
                                       value="<?= htmlspecialchars($travelDate, ENT_QUOTES, 'UTF-8') ?>" required>
                                <div class="form-text text-muted">Defaults to package schedule.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="number_of_travelers" class="form-label fw-semibold small text-muted">Number of Travelers <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="number_of_travelers" name="number_of_travelers"
                                       value="<?= $travelers ?>" min="1" required>
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                                <label for="notes" class="form-label fw-semibold small text-muted">Booking Notes / Special Requests</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= url('admin/reservations/index.php') ?>" class="btn btn-light border px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btnSubmit">
                                <i class="bi bi-check-lg me-1"></i> Confirm & Create Booking
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
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-calculator text-success me-2"></i>Live Summary</h5>
                </div>
                <div class="card-body p-4 bg-light">
                    
                    <div id="summaryPlaceholder" class="text-center py-5 text-muted">
                        <i class="bi bi-arrow-left-square fs-3 d-block mb-2"></i>
                        <p class="small mb-0">Select a package and enter travelers to see pricing and capacity.</p>
                    </div>

                    <div id="summaryContent" style="display: none;">
                        <h6 class="fw-bold text-dark text-truncate mb-1" id="previewPackageName">Package Name</h6>
                        <div class="text-muted small mb-3"><i class="bi bi-geo-alt me-1"></i><span id="previewDestination">Destination</span></div>
                        
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
                            <span>Available Seats:</span>
                            <strong class="fs-6" id="previewAvailable">0</strong>
                        </div>
                        <div id="capacityWarning" class="alert alert-danger py-2 px-3 small mt-2 mb-0" style="display: none;">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Requested travelers exceed available capacity.
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Inject package data for live preview calculation
    const packageData = <?= json_encode($packageData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;

    document.addEventListener('DOMContentLoaded', function () {
        const packageSelect = document.getElementById('package_id');
        const travelersInput = document.getElementById('number_of_travelers');
        const travelDateInput = document.getElementById('travel_date');
        
        const summaryPlaceholder = document.getElementById('summaryPlaceholder');
        const summaryContent = document.getElementById('summaryContent');
        const btnSubmit = document.getElementById('btnSubmit');

        function updateSummary() {
            const pkgId = packageSelect.value;
            const travelers = parseInt(travelersInput.value, 10) || 0;

            if (pkgId && packageData[pkgId]) {
                const pkg = packageData[pkgId];
                
                // Set default travel date if empty
                if (travelDateInput.value === '' && pkg.travel_date) {
                    travelDateInput.value = pkg.travel_date;
                }

                // Show Content
                summaryPlaceholder.style.display = 'none';
                summaryContent.style.display = 'block';

                // Populate Text
                document.getElementById('previewPackageName').textContent = pkg.name;
                document.getElementById('previewDestination').textContent = pkg.destination;
                
                const formatter = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' });
                document.getElementById('previewPrice').textContent = formatter.format(pkg.price);
                document.getElementById('previewTravelers').textContent = travelers;
                
                // Calculate Total
                const total = pkg.price * travelers;
                document.getElementById('previewTotal').textContent = formatter.format(total);

                // Capacity Logic
                document.getElementById('previewAvailable').textContent = pkg.available;

                if (travelers > pkg.available) {
                    document.getElementById('capacityWarning').style.display = 'block';
                    btnSubmit.disabled = true;
                } else {
                    document.getElementById('capacityWarning').style.display = 'none';
                    btnSubmit.disabled = false;
                }
            } else {
                // Hide Content
                summaryPlaceholder.style.display = 'block';
                summaryContent.style.display = 'none';
                btnSubmit.disabled = false;
            }
        }

        packageSelect.addEventListener('change', updateSummary);
        travelersInput.addEventListener('input', updateSummary);

        // Trigger on load in case of validation error reload
        updateSummary();
    });
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
