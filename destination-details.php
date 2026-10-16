<?php
/**
 * Destination Spotlight — Patel Travels
 * Immersive Editorial Overview & Associated Journeys
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

// Fetch active packages for this destination
$stmt = $pdo->prepare("
    SELECT * FROM packages 
    WHERE destination_id = :id AND status = 'active'
    ORDER BY featured DESC, display_order ASC, price ASC
");
$stmt->execute([':id' => $id]);
$plans = $stmt->fetchAll();

$pageTitle = 'Discover ' . $dest['destination_name'] . ', ' . $dest['country'];
$metaDescription = 'Explore curated luxury journeys to ' . $dest['destination_name'] . ', ' . $dest['country'] . '. Five-star itineraries by Patel Travels.';
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<!-- ═══════════════════════════════════════════
     DESTINATION HERO
     ═══════════════════════════════════════════ -->
<section class="page-hero" style="height: 65vh; min-height: 480px;">
    <div class="page-hero__bg" style="background-image: url('<?= get_destination_image($dest) ?>');"></div>
    <div class="page-hero__content">
        <p class="eyebrow" style="color: var(--color-gold);"><?= htmlspecialchars($dest['country']) ?></p>
        <h1 class="page-hero__title"><?= htmlspecialchars($dest['destination_name']) ?></h1>
        <p class="page-hero__sub">An invitation to serenity, cultural refinement, and unforgettable vistas.</p>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     DESTINATION OVERVIEW (EDITORIAL)
     ═══════════════════════════════════════════ -->
<section class="section-pad bg-cream border-bottom" style="border-color: var(--color-border) !important;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 reveal">
                <div class="text-center mb-4">
                    <p class="eyebrow">ESSENCE OF THE REGION</p>
                    <h2 class="mb-3">Where Timeless Beauty Meets Warm Hospitality.</h2>
                </div>
                <div class="fs-5 lh-lg text-secondary text-center px-lg-4" style="color: var(--color-text-secondary);">
                    <?= nl2br(htmlspecialchars($dest['description'])) ?>
                </div>
                <div class="d-flex justify-content-center gap-4 mt-4 pt-2 text-uppercase fw-semibold" style="font-size: 0.76rem; letter-spacing: 0.16em; color: var(--color-gold);">
                    <div><i class="bi bi-geo-alt-fill me-1"></i> <?= htmlspecialchars($dest['country']) ?></div>
                    <div>&bull;</div>
                    <div><i class="bi bi-shield-check me-1"></i> Verified Luxury Standards</div>
                    <div>&bull;</div>
                    <div><i class="bi bi-clock-history me-1"></i> Bespoke Year-Round</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     CURATED JOURNEYS FOR THIS DESTINATION
     ═══════════════════════════════════════════ -->
<section class="section-pad bg-ivory">
    <div class="container">
        <div class="lux-section-head reveal">
            <p class="eyebrow">CURATED ITINERARIES</p>
            <h2>Signature Journeys to <?= htmlspecialchars($dest['destination_name']) ?></h2>
            <p>Every itinerary is individually crafted with five-star accommodations, private transfers, and experienced local guides.</p>
        </div>

        <?php if (!empty($plans)): ?>
            <div class="row g-4">
                <?php foreach ($plans as $pkg): ?>
                    <div class="col-lg-4 col-md-6 reveal">
                        <div class="journey-card">
                            <a href="<?= url('tour-details.php?id=' . $pkg['package_id']) ?>" class="journey-card__img">
                                <img src="<?= get_package_image($pkg) ?>" alt="<?= htmlspecialchars($pkg['package_name']) ?>" loading="lazy">
                                <?php if (!empty($pkg['plan_type'])): ?>
                                    <span class="journey-card__badge"><?= htmlspecialchars($pkg['plan_type']) ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="journey-card__body">
                                <div class="journey-card__dest"><?= htmlspecialchars($dest['destination_name']) ?></div>
                                <h3 class="journey-card__name">
                                    <a href="<?= url('tour-details.php?id=' . $pkg['package_id']) ?>">
                                        <?= htmlspecialchars($pkg['package_name']) ?>
                                    </a>
                                </h3>
                                <div class="journey-card__meta">
                                    <i class="bi bi-clock me-1 text-gold"></i> <?= htmlspecialchars($pkg['duration']) ?>
                                </div>
                                <?php if (!empty($pkg['description'])): ?>
                                    <p class="journey-card__desc"><?= htmlspecialchars($pkg['description']) ?></p>
                                <?php endif; ?>
                                <div class="journey-card__foot">
                                    <div>
                                        <span class="journey-card__price">₹<?= number_format($pkg['price']) ?></span>
                                        <small>per person</small>
                                    </div>
                                    <a href="<?= url('tour-details.php?id=' . $pkg['package_id']) ?>" class="journey-card__cta">
                                        Details <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 reveal">
                <i class="bi bi-stars fs-1 text-gold mb-3 d-block"></i>
                <h3>Bespoke Journeys Coming Soon</h3>
                <p class="text-muted-custom mb-4">Our curators are currently finalizing exclusive new access itineraries for <?= htmlspecialchars($dest['destination_name']) ?>.</p>
                <a href="<?= url('contact.php?subject=' . urlencode('Custom Itinerary for ' . $dest['destination_name'])) ?>" class="btn-lux">REQUEST CUSTOM ITINERARY</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
