<?php
/**
 * Journey Reservation Flow — Patel Travels
 * Ultra-Luxury Reservation Workflow
 */
if (session_status() === PHP_SESSION_NONE) {
    if (is_dir('/tmp') && is_writable('/tmp')) {
        ini_set('session.save_path', '/tmp');
    }
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_name('TOURISM_CLIENT_SESSION');
    session_start();
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/public_functions.php';

$pdo = getDBConnection();
$packageId = $_GET['package_id'] ?? $_GET['id'] ?? null;
$errors = [];
$success = false;

if (!$packageId || !is_numeric($packageId)) {
    header('Location: ' . url('tours.php'));
    exit;
}

// Fetch package - MUST BE ACTIVE
$stmt = $pdo->prepare("
    SELECT p.*, d.destination_name, d.country 
    FROM packages p
    JOIN destinations d ON p.destination_id = d.destination_id
    WHERE p.package_id = :id AND p.status = 'active'
");
$stmt->execute([':id' => $packageId]);
$pkg = $stmt->fetch();

if (!$pkg) {
    header('Location: ' . url('tours.php'));
    exit;
}

// Check Availability
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(travellers), 0) 
    FROM enquiries 
    WHERE package_id = :id AND status IN ('quoted', 'confirmed')
");
$stmt->execute([':id' => $packageId]);
$booked = (int)$stmt->fetchColumn();
$capacity = (int)$pkg['maximum_capacity'];
$available = max(0, $capacity - $booked);

if ($available <= 0) {
    $errors[] = "We're sorry, this signature journey is currently fully booked.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $available > 0) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $travelers = (int)($_POST['travelers'] ?? 1);
    $travelDate = $_POST['travel_date'] ?? $pkg['travel_date'];
    $specialRequests = trim($_POST['special_requests'] ?? '');
    
    if (empty($name)) $errors[] = "Lead guest full name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email address is required.";
    if ($travelers < 1) $errors[] = "Number of travellers must be at least 1.";
    if ($travelers > $available) $errors[] = "Cannot exceed available capacity of " . $available . " travellers.";
    
    if (empty($errors)) {
        try {
            $subject = "Private Journey Request: " . $pkg['package_name'];
            $message = "Special Requests: " . ($specialRequests ?: 'None');
            
            $stmt = $pdo->prepare("
                INSERT INTO enquiries (name, email, phone, subject, message, status, destination_id, package_id, travel_date, travellers, created_at)
                VALUES (:name, :email, :phone, :subject, :msg, 'new', :dest_id, :pkg_id, :travel_date, :travellers, NOW())
            ");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':subject' => $subject,
                ':msg' => $message,
                ':dest_id' => $pkg['destination_id'],
                ':pkg_id' => $pkg['package_id'],
                ':travel_date' => $travelDate,
                ':travellers' => $travelers
            ]);
            $success = true;
        } catch (PDOException $e) {
            $errors[] = "An error occurred while submitting your request. Please try again later.";
            error_log("Enquiry Error: " . $e->getMessage());
        }
    }
}

$pageTitle = 'Reserve ' . $pkg['package_name'];
$metaDescription = 'Secure your reservation for ' . $pkg['package_name'] . ' with Patel Travels. Private bespoke travel planning.';
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<section class="page-hero" style="height: 50vh; min-height: 400px;">
    <div class="page-hero__bg" style="background-image: url('<?= get_package_image($pkg) ?>');"></div>
    <div class="page-hero__content">
        <p class="eyebrow" style="color: var(--color-gold);">BESPOKE RESERVATION</p>
        <h1 class="page-hero__title">Begin Your Journey.</h1>
        <p class="page-hero__sub"><?= htmlspecialchars($pkg['package_name']) ?> &bull; <?= htmlspecialchars($pkg['destination_name']) ?></p>
    </div>
</section>

<section class="section-pad bg-ivory">
    <div class="container">
        <?php if ($success): ?>
            <div class="text-center py-5 reveal mx-auto" style="max-width: 650px;">
                <div class="mb-4">
                    <i class="bi bi-check2-circle text-gold" style="font-size: 4.5rem;"></i>
                </div>
                <h2 style="font-size: 2.8rem; margin-bottom: 1rem;">Reservation Request Confirmed</h2>
                <p class="fs-5 text-secondary mb-4 pb-2" style="line-height: 1.8;">
                    Thank you, <strong><?= htmlspecialchars($name) ?></strong>. Your request for <strong><?= htmlspecialchars($pkg['package_name']) ?></strong> has been transmitted directly to our private concierge desk.
                </p>
                <div class="p-4 bg-cream border rounded-1 mb-5 text-start" style="border-color: var(--color-border) !important;">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <span class="text-muted-custom small text-uppercase fw-bold">Journey</span>
                            <div class="fw-bold"><?= htmlspecialchars($pkg['package_name']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted-custom small text-uppercase fw-bold">Departure Date</span>
                            <div class="fw-bold"><?= date('F j, Y', strtotime($travelDate)) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted-custom small text-uppercase fw-bold">Travellers</span>
                            <div class="fw-bold"><?= $travelers ?> <?= $travelers === 1 ? 'Guest' : 'Guests' ?></div>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted-custom small text-uppercase fw-bold">Contact Email</span>
                            <div class="fw-bold"><?= htmlspecialchars($email) ?></div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?= url('index.php') ?>" class="btn-lux">Return to Homepage</a>
                    <a href="<?= url('tours.php') ?>" class="btn-lux-outline">Explore More Journeys</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-5 justify-content-center">
                <!-- Form Column -->
                <div class="col-lg-7 reveal">
                    <div class="lux-form-card">
                        <p class="eyebrow">STEP 1 OF 2 &bull; GUEST INFORMATION</p>
                        <h3 class="mb-4" style="font-size: 2rem;">Lead Traveller Details</h3>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger mb-4 rounded-1 border-0" style="background-color: #fef2f2; color: #991b1b; font-size: 0.92rem;">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= htmlspecialchars($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($available > 0): ?>
                            <form method="POST" action="" class="lux-form">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                
                                <div class="mb-4">
                                    <label class="form-label">Full Legal Name <span class="text-gold">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="e.g. Eleanor Vance" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                                </div>
                                
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address <span class="text-gold">*</span></label>
                                        <input type="email" name="email" class="form-control" placeholder="eleanor@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone / WhatsApp</label>
                                        <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                    </div>
                                </div>
                                
                                <p class="eyebrow mt-4">STEP 2 OF 2 &bull; JOURNEY PREFERENCES</p>
                                <h3 class="mb-4" style="font-size: 2rem;">Dates & Party Size</h3>
                                
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Number of Travellers</label>
                                        <select name="travelers" id="travellerCount" class="form-select" required>
                                            <?php for ($i = 1; $i <= min(12, $available); $i++): ?>
                                                <option value="<?= $i ?>" <?= (($_POST['travelers'] ?? 2) == $i) ? 'selected' : '' ?>>
                                                    <?= $i ?> <?= $i === 1 ? 'Guest' : 'Guests' ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Preferred Departure Date</label>
                                        <input type="date" name="travel_date" class="form-control" value="<?= htmlspecialchars($_POST['travel_date'] ?? ($pkg['travel_date'] ?? date('Y-m-d', strtotime('+21 days')))) ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Special Requests & Dietary Preferences</label>
                                    <textarea name="special_requests" class="form-control" rows="4" placeholder="Flight preferences, dietary considerations, celebratory milestones, or room configuration..."><?= htmlspecialchars($_POST['special_requests'] ?? '') ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn-lux w-100 py-3">SUBMIT BESPOKE RESERVATION REQUEST</button>
                                <p class="text-center text-muted-custom mt-3 small">Submitting this request entails no immediate payment obligation. A dedicated concierge specialist will review your preferences and provide a private confirmation proposal.</p>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar Summary Column -->
                <div class="col-lg-5 reveal">
                    <div class="journey-sidebar" style="top: 120px;">
                        <p class="eyebrow mb-3">JOURNEY ITINERARY</p>
                        
                        <div class="mb-4">
                            <img src="<?= get_package_image($pkg) ?>" alt="<?= htmlspecialchars($pkg['package_name']) ?>" class="w-100 rounded-1 mb-3 object-fit-cover" style="height: 220px;">
                            <h4 style="font-size: 1.6rem; margin-bottom: 0.4rem;"><?= htmlspecialchars($pkg['package_name']) ?></h4>
                            <div class="text-gold fw-semibold small text-uppercase" style="letter-spacing: 0.14em;"><?= htmlspecialchars($pkg['destination_name']) ?>, <?= htmlspecialchars($pkg['country']) ?></div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="journey-sidebar__row">
                                <span class="journey-sidebar__label mb-0">Duration</span>
                                <span class="journey-sidebar__val"><?= htmlspecialchars($pkg['duration']) ?></span>
                            </div>
                            <div class="journey-sidebar__row">
                                <span class="journey-sidebar__label mb-0">Rate per Person</span>
                                <span class="journey-sidebar__val text-gold">₹<?= number_format($pkg['price']) ?></span>
                            </div>
                            <div class="journey-sidebar__row">
                                <span class="journey-sidebar__label mb-0">Est. Total Investment</span>
                                <span class="journey-sidebar__val fs-5" id="totalEstimate">₹<?= number_format($pkg['price'] * (int)($_POST['travelers'] ?? 2)) ?></span>
                            </div>
                            <div class="journey-sidebar__row">
                                <span class="journey-sidebar__label mb-0">Private Concierge</span>
                                <span class="journey-sidebar__val text-success">Complimentary</span>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start gap-3 p-3 bg-cream rounded-1 border" style="border-color: var(--color-border) !important;">
                            <i class="bi bi-shield-lock-fill fs-3 text-gold"></i>
                            <div class="small text-secondary" style="line-height: 1.6;">
                                <strong>Five-Star Guarantee</strong><br>
                                Enjoy private chauffeur handling, verified five-star properties, and flexible rescheduling up to 21 days before departure.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const travellerSelect = document.getElementById('travellerCount');
    const totalEstimate = document.getElementById('totalEstimate');
    const basePrice = <?= (float)$pkg['price'] ?>;
    
    if (travellerSelect && totalEstimate) {
        travellerSelect.addEventListener('change', function() {
            const count = parseInt(this.value) || 1;
            const total = count * basePrice;
            totalEstimate.textContent = '₹' + total.toLocaleString('en-IN');
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
