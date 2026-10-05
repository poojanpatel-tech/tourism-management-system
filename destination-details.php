<?php
/**
 * Destination Detail — Patel Travels
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/public_functions.php';

$pdo = getDBConnection();
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header('Location: ' . url('destinations.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM destinations WHERE destination_id = :id AND status = 'active'");
$stmt->execute([':id' => $id]);
$dest = $stmt->fetch();

if (!$dest) {
    header('Location: ' . url('destinations.php'));
    exit;
}

// Fetch plans for this destination
$stmt = $pdo->prepare("
    SELECT * FROM packages 
    WHERE destination_id = :id AND status = 'active'
    ORDER BY featured DESC, display_order ASC, price ASC
");
$stmt->execute([':id' => $id]);
$plans = $stmt->fetchAll();

$pageTitle = 'Discover ' . $dest['destination_name'];
$metaDescription = 'Explore curated journeys to ' . $dest['destination_name'] . ', ' . $dest['country'] . '. Discover travel plans designed by Patel Travels.';
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<section class="page-hero" style="height: 70vh;">
    <div class="page-hero__bg" style="background-image: url('<?= get_public_image($dest['image'] ?? null, 'destination') ?>');"></div>
    <div class="page-hero__content">
        <p class="eyebrow mb-3">Destination</p>
        <h1 style="font-family: var(--font-heading); font-size: clamp(2.5rem, 6vw, 4.5rem); font-weight: 400; color: #fff;">Discover <?= htmlspecialchars($dest['destination_name']) ?></h1>
        <p style="color: rgba(255,255,255,0.7); font-size: 1rem; letter-spacing: 0.1em; text-transform: uppercase; margin-top: 0.5rem;"><?= htmlspecialchars($dest['country']) ?></p>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <!-- Description -->
        <?php if (!empty($dest['description'])): ?>
        <div class="row justify-content-center mb-5 reveal">
            <div class="col-lg-8">
                <div style="font-size: 1.05rem; line-height: 1.9; color: var(--color-muted);">
                    <?= nl2br(htmlspecialchars($dest['description'])) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Travel Plans -->
        <?php if (!empty($plans)): ?>
        <div class="reveal" style="margin-top: 2rem;">
            <div class="lux-section-head">
                <p class="eyebrow mb-3">Curated Journeys</p>
                <h2>Choose your journey<br>to <?= htmlspecialchars($dest['destination_name']) ?>.</h2>
            </div>
            <div class="row g-4">
                <?php foreach ($plans as $pkg): ?>
                    <div class="col-lg-4 col-md-6 reveal">
                        <a href="<?= url('tour-details.php?id=' . $pkg['package_id']) ?>" class="journey-card">
                            <div class="journey-card__img">
                                <img src="<?= get_public_image($pkg['image'] ?? null, 'plan') ?>" alt="<?= htmlspecialchars($pkg['package_name']) ?>" loading="lazy">
                                <?php if (!empty($pkg['plan_type'])): ?>
                                    <div class="journey-card__badge"><?= htmlspecialchars($pkg['plan_type']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="journey-card__body">
                                <div class="journey-card__dest"><?= htmlspecialchars($dest['destination_name']) ?></div>
                                <h3 class="journey-card__name"><?= htmlspecialchars($pkg['package_name']) ?></h3>
                                <div class="journey-card__meta"><?= htmlspecialchars($pkg['duration']) ?></div>
                                <div class="journey-card__price">From ₹<?= number_format($pkg['price']) ?> <small><?= htmlspecialchars($pkg['price_type'] ?? 'per person') ?></small></div>
                                <span class="journey-card__cta">Explore Journey <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
            <div class="text-center py-5 reveal">
                <p class="text-muted-custom" style="font-size: 1.1rem;">Journeys to <?= htmlspecialchars($dest['destination_name']) ?> are being curated. Contact us for a personalized itinerary.</p>
                <a href="<?= url('contact.php') ?>" class="btn-lux mt-3">Get In Touch</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
