<?php
/**
 * Journey Enquiry Flow — Patel Travels
 */
session_name('TOURISM_CLIENT_SESSION');
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/public_functions.php';

$pdo = getDBConnection();
$packageId = $_GET['package_id'] ?? null;
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
    // If someone tries to book a sold out package directly
    $errors[] = "We're sorry, this journey is currently fully booked.";
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
    
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if ($travelers < 1) $errors[] = "Number of travelers must be at least 1.";
    if ($travelers > $available) $errors[] = "Cannot exceed available capacity of " . $available . " travelers.";
    
    if (empty($errors)) {
        try {
            $subject = "Journey Request: " . $pkg['package_name'];
            $message = "Special Requests: " . ($specialRequests ?: 'None');
            
            // Insert Enquiry - Safely using DB-sourced destination_id and package_id
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

$pageTitle = 'Request Journey: ' . $pkg['package_name'];
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<section class="page-hero" style="height: 50vh; min-height: 400px;">
    <div class="page-hero__bg" style="background-image: url('<?= get_public_image(null, 'hero') ?>'); background-position: center 30%;"></div>
    <div class="page-hero__content">
        <p class="eyebrow mb-3">Journey Enquiry</p>
        <h1 style="font-family: var(--font-heading); font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 400; color: #fff;">Request this journey.</h1>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <?php if ($success): ?>
            <div class="text-center py-5 reveal">
                <i class="bi bi-check-circle text-gold mb-4" style="font-size: 4rem;"></i>
                <h2 style="font-family: var(--font-heading); font-size: 2.5rem; color: var(--color-charcoal); margin-bottom: 1rem;">Your journey request has been received.</h2>
                <p style="color: var(--color-muted); font-size: 1.1rem; max-width: 600px; margin: 0 auto 2rem; line-height: 1.8;">Our travel team will review your requirements for <strong><?= htmlspecialchars($pkg['package_name']) ?></strong> and contact you shortly to begin planning.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?= url('index.php') ?>" class="btn-lux-outline">Return Home</a>
                    <a href="<?= url('tours.php') ?>" class="btn-lux">Explore More Journeys</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-5 justify-content-center">
                <!-- Form Column -->
                <div class="col-lg-7 reveal">
                    <div class="bg-white p-4 p-md-5 shadow-sm" style="border: 1px solid var(--color-border);">
                        <h3 style="font-family: var(--font-heading); font-size: 2rem; color: var(--color-charcoal); margin-bottom: 2rem;">Traveller Details</h3>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger mb-4 rounded-0 border-0" style="background-color: #fff5f5; color: #dc3545; font-size: 0.9rem;">
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
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                                </div>
                                
                                <div class="row g-4 mb-5">
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                    </div>
                                </div>
                                
                                <h3 style="font-family: var(--font-heading); font-size: 2rem; color: var(--color-charcoal); margin-bottom: 2rem;">Journey Requirements</h3>
                                
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Number of Travellers</label>
                                        <select name="travelers" class="form-select" required>
                                            <?php for ($i = 1; $i <= min(10, $available); $i++): ?>
                                                <option value="<?= $i ?>" <?= (($_POST['travelers'] ?? 1) == $i) ? 'selected' : '' ?>><?= $i ?> <?= $i === 1 ? 'Traveller' : 'Travellers' ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Travel Date</label>
                                        <input type="date" name="travel_date" class="form-control" value="<?= htmlspecialchars($_POST['travel_date'] ?? ($pkg['travel_date'] ?? date('Y-m-d', strtotime('+14 days')))) ?>" <?= !empty($pkg['travel_date']) ? 'readonly' : '' ?> required>
                                        <?php if (!empty($pkg['travel_date'])): ?>
                                            <div class="form-text mt-2" style="font-size: 0.75rem;">This journey has a fixed departure date.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-5">
                                    <label class="form-label">Special Requests & Notes</label>
                                    <textarea name="special_requests" class="form-control" rows="4" placeholder="Dietary requirements, accessibility needs, or special occasions..."><?= htmlspecialchars($_POST['special_requests'] ?? '') ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn-lux w-100">Submit Journey Request</button>
                                <p class="text-center text-muted mt-3" style="font-size: 0.8rem;">Submitting this form does not commit you to booking. Our team will contact you to discuss details and finalize pricing.</p>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar Column -->
                <div class="col-lg-4 reveal">
                    <div class="bg-stone p-4 p-md-5 sticky-top" style="top: 120px;">
                        <p class="eyebrow mb-4">Journey Summary</p>
                        
                        <div class="mb-4">
                            <img src="<?= get_public_image($pkg['image'], 'plan') ?>" alt="<?= htmlspecialchars($pkg['package_name']) ?>" class="w-100 object-fit-cover mb-4" style="height: 200px;">
                            <h4 style="font-family: var(--font-heading); font-size: 1.6rem; color: var(--color-charcoal); margin-bottom: 0.5rem;"><?= htmlspecialchars($pkg['package_name']) ?></h4>
                            <div style="font-size: 0.9rem; color: var(--color-muted);"><?= htmlspecialchars($pkg['destination_name']) ?>, <?= htmlspecialchars($pkg['country']) ?></div>
                        </div>
                        
                        <div class="mb-4 pb-4 border-bottom" style="border-color: rgba(0,0,0,0.1) !important;">
                            <div class="d-flex justify-content-between mb-3" style="font-size: 0.9rem;">
                                <span class="text-muted fw-medium">Duration</span>
                                <span class="text-charcoal fw-bold"><?= htmlspecialchars($pkg['duration']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3" style="font-size: 0.9rem;">
                                <span class="text-muted fw-medium">Plan Type</span>
                                <span class="text-charcoal fw-bold"><?= htmlspecialchars($pkg['plan_type'] ?? 'Custom') ?></span>
                            </div>
                            <div class="d-flex justify-content-between" style="font-size: 0.9rem;">
                                <span class="text-muted fw-medium">Price</span>
                                <span class="text-charcoal fw-bold">From ₹<?= number_format($pkg['price']) ?> <small class="text-muted" style="font-weight: 500; font-size: 0.8em;">(<?= htmlspecialchars($pkg['price_type'] ?? 'Per Person') ?>)</small></span>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center gap-3 text-muted" style="font-size: 0.85rem; line-height: 1.5;">
                            <i class="bi bi-shield-check fs-3 text-gold"></i>
                            <div>Your information is secure. We never share your details with third parties.</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
