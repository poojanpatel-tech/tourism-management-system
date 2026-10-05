<?php
/**
 * Destinations Listing — Patel Travels
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/public_functions.php';

$pdo = getDBConnection();
$stmt = $pdo->query("SELECT * FROM destinations WHERE status = 'active' ORDER BY destination_name ASC");
$destinations = $stmt->fetchAll();

$pageTitle = 'Destinations';
$metaDescription = 'Explore extraordinary destinations curated by Patel Travels. Find your next unforgettable journey.';
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<section class="page-hero">
    <div class="page-hero__bg" style="background-image: url('<?= get_public_image(null, 'hero') ?>');"></div>
    <div class="page-hero__content">
        <p class="eyebrow mb-3">Explore the World</p>
        <h1 style="font-family: var(--font-heading); font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 400; color: #fff;">Discover somewhere<br>extraordinary.</h1>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <?php if (empty($destinations)): ?>
            <div class="text-center py-5">
                <p class="text-muted-custom" style="font-size: 1.1rem;">No destinations available at the moment. Please check back soon.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($destinations as $dest): ?>
                    <div class="col-lg-4 col-md-6 reveal">
                        <a href="<?= url('destination-details.php?id=' . $dest['destination_id']) ?>" class="dest-card" style="height: 420px;">
                            <img src="<?= get_public_image($dest['image'] ?? null, 'destination') ?>" alt="<?= htmlspecialchars($dest['destination_name']) ?>" loading="lazy">
                            <div class="dest-card__overlay">
                                <div class="dest-card__country"><?= htmlspecialchars($dest['country']) ?></div>
                                <h3 class="dest-card__name"><?= htmlspecialchars($dest['destination_name']) ?></h3>
                                <span class="dest-card__arrow">Explore <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
