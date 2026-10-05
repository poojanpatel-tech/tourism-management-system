<?php
/**
 * Public Tour Details Page - Premium Redesign
 * Tourism Management System
 */
session_name('TOURISM_CLIENT_SESSION');
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/public_functions.php';

$pdo = getDBConnection();
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header('Location: ' . url('tours.php'));
    exit;
}

// Fetch Package
$stmt = $pdo->prepare("
    SELECT p.*, d.destination_name, d.country 
    FROM packages p 
    JOIN destinations d ON p.destination_id = d.destination_id 
    WHERE p.package_id = :id AND p.status IN ('active', 'sold_out')
");
$stmt->execute([':id' => $id]);
$pkg = $stmt->fetch();

if (!$pkg) {
    header('Location: ' . url('tours.php'));
    exit;
}

// Fetch related data
$stmt = $pdo->prepare("SELECT * FROM plan_itinerary WHERE package_id = :id ORDER BY day_number ASC");
$stmt->execute([':id' => $id]);
$itinerary = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM plan_inclusions WHERE package_id = :id ORDER BY type ASC, display_order ASC, id ASC");
$stmt->execute([':id' => $id]);
$inclusionsData = $stmt->fetchAll();
$included = array_filter($inclusionsData, fn($i) => $i['type'] === 'included');
$excluded = array_filter($inclusionsData, fn($i) => $i['type'] === 'excluded');

if (empty($included) && !empty($pkg['included_services'])) {
    $legacyInc = array_filter(array_map('trim', explode(',', $pkg['included_services'])));
    foreach ($legacyInc as $inc) $included[] = ['title' => $inc];
}
if (empty($excluded) && !empty($pkg['excluded_services'])) {
    $legacyExc = array_filter(array_map('trim', explode(',', $pkg['excluded_services'])));
    foreach ($legacyExc as $exc) $excluded[] = ['title' => $exc];
}

$stmt = $pdo->prepare("SELECT * FROM plan_highlights WHERE package_id = :id ORDER BY display_order ASC, id ASC");
$stmt->execute([':id' => $id]);
$highlights = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM plan_images WHERE package_id = :id ORDER BY display_order ASC, id ASC");
$stmt->execute([':id' => $id]);
$gallery = $stmt->fetchAll();

// Calculate Availability
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(travellers), 0) 
    FROM enquiries 
    WHERE package_id = :id AND status IN ('quoted', 'confirmed')
");
$stmt->execute([':id' => $id]);
$booked = (int)$stmt->fetchColumn();
$capacity = (int)$pkg['maximum_capacity'];
$available = max(0, $capacity - $booked);

if ($pkg['status'] === 'sold_out') {
    $available = 0;
}

$pageTitle = $pkg['package_name'];
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';

$bgImage = get_public_image($pkg['image'], 'plan');
?>

<section class="lux-hero" style="height: 75vh; min-height: 500px;">
    <div class="lux-hero__bg" style="background-image: url('<?= $bgImage ?>');"></div>
    <div class="lux-hero__content">
        <p class="eyebrow mb-4"><?= htmlspecialchars($pkg['destination_name']) ?>, <?= htmlspecialchars($pkg['country']) ?></p>
        <h1 class="lux-hero__title" style="font-size: clamp(2.5rem, 5vw, 4rem);"><?= htmlspecialchars($pkg['package_name']) ?></h1>
        
        <div class="d-flex justify-content-center gap-4 fw-semibold mt-4 text-uppercase" style="letter-spacing: 0.15em; font-size: 0.75rem;">
            <div><?= htmlspecialchars($pkg['duration']) ?></div>
            <div>&bull;</div>
            <div><?= htmlspecialchars($pkg['plan_type'] ?? 'Custom') ?></div>
        </div>
    </div>
</section>

<div class="container section-pad">
    <div class="row g-5">
        <div class="col-lg-8 pe-lg-5">
            
            <!-- Highlights -->
            <?php if (!empty($highlights)): ?>
                <div class="mb-5 reveal">
                    <h2 class="mb-4" style="font-family: var(--font-heading); font-size: 2.2rem;">Journey Highlights</h2>
                    <div class="row g-4 mt-2">
                        <?php foreach ($highlights as $hl): ?>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <span class="text-gold me-3" style="font-size: 1.2rem;">&diams;</span>
                                    <span class="text-dark fw-medium" style="line-height: 1.6; font-size: 1.05rem;"><?= htmlspecialchars($hl['highlight']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <hr class="my-5 border-secondary opacity-10">
            <?php endif; ?>

            <!-- Overview -->
            <div class="mb-5 reveal">
                <h2 class="mb-4" style="font-family: var(--font-heading); font-size: 2.2rem;">Overview</h2>
                <div style="color: var(--color-muted); line-height: 1.8; font-size: 1.05rem;">
                    <?= nl2br(htmlspecialchars($pkg['description'] ?? '')) ?>
                </div>
            </div>

            <hr class="my-5 border-secondary opacity-10">

            <!-- Itinerary Timeline -->
            <?php if (!empty($itinerary)): ?>
                <div class="mb-5 reveal">
                    <h2 class="mb-4" style="font-family: var(--font-heading); font-size: 2.2rem;">Itinerary</h2>
                    <div class="itin-timeline mt-5">
                        <?php foreach ($itinerary as $index => $day): ?>
                            <div class="itin-day">
                                <div class="itin-day__num">
                                    <?= sprintf('%02d', $day['day_number']) ?>
                                </div>
                                <div>
                                    <div class="itin-day__label mb-1">Day <?= $day['day_number'] ?></div>
                                    <h4 class="itin-day__title"><?= htmlspecialchars($day['title']) ?></h4>
                                    <div class="itin-day__desc">
                                        <?= nl2br(htmlspecialchars($day['description'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <hr class="my-5 border-secondary opacity-10">
            <?php endif; ?>

            <!-- Inclusions / Exclusions -->
            <div class="row g-5 mb-5 reveal">
                <div class="col-md-6">
                    <h4 class="mb-4" style="font-family: var(--font-heading); font-size: 1.8rem;">Included</h4>
                    <ul class="incl-list">
                        <?php if (empty($included)): ?>
                            <li><span class="incl-icon incl-no">&minus;</span> Not specified</li>
                        <?php else: ?>
                            <?php foreach ($included as $item): ?>
                                <li><span class="incl-icon incl-yes">&check;</span> <?= htmlspecialchars($item['title']) ?></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h4 class="mb-4" style="font-family: var(--font-heading); font-size: 1.8rem;">Not Included</h4>
                    <ul class="incl-list">
                        <?php if (empty($excluded)): ?>
                            <li><span class="incl-icon incl-no">&minus;</span> Not specified</li>
                        <?php else: ?>
                            <?php foreach ($excluded as $item): ?>
                                <li><span class="incl-icon incl-no">&times;</span> <?= htmlspecialchars($item['title']) ?></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Gallery -->
            <?php if (!empty($gallery)): ?>
                <hr class="my-5 border-secondary opacity-10">
                <div class="mb-5 reveal">
                    <h2 class="mb-4" style="font-family: var(--font-heading); font-size: 2.2rem;">Gallery</h2>
                    <div class="lux-gallery mt-4">
                        <?php foreach ($gallery as $img): ?>
                            <div class="lux-gallery__item">
                                <img src="<?= get_public_image($img['image_path'], 'plan') ?>" alt="<?= htmlspecialchars($img['alt_text'] ?? 'Gallery Image') ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sticky Sidebar -->
        <div class="col-lg-4">
            <div class="journey-sidebar">
                <div class="journey-sidebar__label">Starting From</div>
                <div class="journey-sidebar__price mb-1">?<?= number_format($pkg['price']) ?></div>
                <div class="text-muted mb-4" style="font-size: 0.8rem;"><?= htmlspecialchars($pkg['price_type'] ?? 'per person') ?></div>
                
                <div class="mb-4 text-muted" style="font-size: 0.85rem; line-height: 1.6;">
                    <?= htmlspecialchars($pkg['price_note'] ?? 'Final quote depends on dates, accommodation, group size and selected services.') ?>
                </div>
                
                <div class="mb-4">
                    <?php if (!empty($pkg['best_time'])): ?>
                    <div class="journey-sidebar__row">
                        <span class="journey-sidebar__label mb-0">Best Time</span>
                        <span class="journey-sidebar__val"><?= htmlspecialchars($pkg['best_time']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="journey-sidebar__row">
                        <span class="journey-sidebar__label mb-0">Travel Date</span>
                        <span class="journey-sidebar__val"><?= !empty($pkg['travel_date']) ? date('M d, Y', strtotime($pkg['travel_date'])) : 'Flexible' ?></span>
                    </div>
                    <div class="journey-sidebar__row">
                        <span class="journey-sidebar__label mb-0">Availability</span>
                        <?php if ($available > 0): ?>
                            <span class="journey-sidebar__val"><?= $available ?> Spots Left</span>
                        <?php else: ?>
                            <span class="journey-sidebar__val text-danger">Sold Out</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($available > 0): ?>
                    <a href="<?= url('book.php?package_id=' . $pkg['package_id']) ?>" class="btn-lux w-100 mb-3">Request This Journey</a>
                <?php else: ?>
                    <button class="btn-lux w-100 mb-3" style="opacity: 0.5; cursor: not-allowed;" disabled>Fully Booked</button>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="<?= url('contact.php?subject=Inquiry about ' . urlencode($pkg['package_name'])) ?>" class="journey-sidebar__label" style="text-decoration: none; color: var(--color-charcoal); border-bottom: 1px solid var(--color-gold);">Contact an Advisor</a>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="section-pad bg-charcoal" style="text-align: center; color: #fff;">
    <div class="container reveal">
        <p class="eyebrow mb-3" style="color: var(--color-gold);">Personalised Planning</p>
        <h2 style="font-family: var(--font-heading); font-size: clamp(2rem, 4vw, 3.5rem); font-weight: 400; color: #fff; margin-bottom: 1.5rem;">Ready to embark<br>on this journey?</h2>
        <p style="color: rgba(255,255,255,0.55); max-width: 500px; margin: 0 auto 2.5rem; line-height: 1.8;">Our travel advisors are ready to craft the perfect itinerary for your needs. Connect with us to start planning your next great escape.</p>
        <a href="<?= url('book.php?package_id=' . $pkg['package_id']) ?>" class="btn-lux-light">Request This Journey</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
