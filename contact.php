<?php
/**
 * Contact — Patel Travels
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/public_functions.php';

$success = false;
$errors = [];
$prefilledSubject = $_GET['subject'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($message)) $errors[] = "Message is required.";

    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO enquiries (name, email, phone, subject, message, status, created_at) 
                VALUES (:name, :email, :phone, :subject, :message, 'new', NOW())
            ");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':subject' => $subject,
                ':message' => $message
            ]);
            $success = true;
        } catch (PDOException $e) {
            $errors[] = "An error occurred while sending your message. Please try again later.";
            error_log("Contact Error: " . $e->getMessage());
        }
    }
}

// Fetch business settings
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Get in Touch';
$metaDescription = 'Contact Patel Travels to start planning your next journey.';
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<section class="page-hero" style="height: 60vh; min-height: 500px;">
    <div class="page-hero__bg" style="background-image: url('<?= get_public_image(null, 'hero') ?>'); background-position: center top;"></div>
    <div class="page-hero__content">
        <p class="eyebrow mb-3">Get in Touch</p>
        <h1 style="font-family: var(--font-heading); font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 400; color: #fff;">How can we help<br>plan your journey?</h1>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <?php if ($success): ?>
            <div class="text-center py-5 reveal">
                <i class="bi bi-check-circle text-gold mb-4" style="font-size: 4rem;"></i>
                <h2 style="font-family: var(--font-heading); font-size: 2.5rem; color: var(--color-charcoal); margin-bottom: 1rem;">Thank you for reaching out.</h2>
                <p style="color: var(--color-muted); font-size: 1.1rem; max-width: 500px; margin: 0 auto 2rem;">Your message has been received. One of our travel advisors will contact you shortly to discuss your plans.</p>
                <a href="<?= url('index.php') ?>" class="btn-lux">Return Home</a>
            </div>
        <?php else: ?>
            <div class="row g-5">
                <div class="col-lg-5 reveal">
                    <h2 style="font-family: var(--font-heading); font-size: 2.5rem; color: var(--color-charcoal); margin-bottom: 1.5rem;">Let's start a conversation.</h2>
                    <p style="color: var(--color-muted); line-height: 1.8; margin-bottom: 3rem;">Whether you know exactly where you want to go, or you're simply looking for inspiration, our team is ready to help you craft the perfect itinerary.</p>
                    
                    <div class="mb-4">
                        <p class="eyebrow mb-2">Visit Us</p>
                        <p style="color: var(--color-charcoal); font-weight: 500;"><?= nl2br(htmlspecialchars($settings['address'] ?? '')) ?></p>
                    </div>
                    <div class="mb-4">
                        <p class="eyebrow mb-2">Call Us</p>
                        <p style="color: var(--color-charcoal); font-weight: 500;"><?= htmlspecialchars($settings['phone'] ?? '') ?></p>
                    </div>
                    <div class="mb-4">
                        <p class="eyebrow mb-2">Email Us</p>
                        <p style="color: var(--color-charcoal); font-weight: 500;"><?= htmlspecialchars($settings['email'] ?? '') ?></p>
                    </div>
                </div>
                
                <div class="col-lg-6 offset-lg-1 reveal">
                    <div class="bg-white p-4 p-md-5" style="border: 1px solid var(--color-border);">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger mb-4 rounded-0 border-0" style="background-color: #fff5f5; color: #dc3545; font-size: 0.9rem;">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= htmlspecialchars($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="lux-form">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            
                            <div class="mb-4">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Subject</label>
                                <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($_POST['subject'] ?? $prefilledSubject) ?>">
                            </div>
                            
                            <div class="mb-5">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="5" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn-lux w-100">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
