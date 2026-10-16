<?php
/**
 * Public Tour Details Page - Ultra-Luxury Redesign
 * Tourism Management System — Patel Travels
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

// Fetch related itinerary
$stmt = $pdo->prepare("SELECT * FROM plan_itinerary WHERE package_id = :id ORDER BY day_number ASC");
$stmt->execute([':id' => $id]);
$itinerary = $stmt->fetchAll();

// Fetch inclusions and exclusions
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

// Highlights
$stmt = $pdo->prepare("SELECT * FROM plan_highlights WHERE package_id = :id ORDER BY display_order ASC, id ASC");
$stmt->execute([':id' => $id]);
$highlights = $stmt->fetchAll();

// Gallery
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

$pageTitle = $pkg['package_name'] . ' — Patel Travels';
$metaDescription = 'Experience ' . $pkg['package_name'] . ' in ' . $pkg['destination_name'] . ', ' . $pkg['country'] . '. Five-star private journey by Patel Travels.';
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';

$bgImage = get_package_image($pkg);
?>

<!-- ═══════════════════════════════════════════
     HERO BANNER
     ═══════════════════════════════════════════ -->
<section class="lux-hero" style="height: 72vh; min-height: 520px;">
    <div class="lux-hero__bg" style="background-image: url('<?= $bgImage ?>');"></div>
    <div class="lux-hero__content">
        <p class="eyebrow mb-3"><?= htmlspecialchars($pkg['destination_name']) ?>, <?= htmlspecialchars($pkg['country']) ?></p>
        <h1 class="lux-hero__title" style="font-size: clamp(2.4rem, 5.2vw, 4.4rem);"><?= htmlspecialchars($pkg['package_name']) ?></h1>
        
        <div class="d-flex justify-content-center gap-3 fw-semibold text-uppercase" style="letter-spacing: 0.16em; font-size: 0.74rem; color: var(--color-gold);">
            <div><i class="bi bi-clock me-1"></i> <?= htmlspecialchars($pkg['duration']) ?></div>
            <div>&bull;</div>
            <div><i class="bi bi-compass me-1"></i> <?= htmlspecialchars($pkg['plan_type'] ?? 'Custom') ?></div>
            <div>&bull;</div>
            <div><i class="bi bi-shield-check me-1"></i> Private Departure</div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     MAIN CONTENT & SIDEBAR
     ═══════════════════════════════════════════ -->
<div class="container section-pad bg-ivory">
    <div class="row g-5">
        <div class="col-lg-8 pe-lg-5">
            
            <!-- Highlights -->
            <?php if (!empty($highlights)): ?>
                <div class="mb-5 reveal">
                    <p class="eyebrow">CURATED HIGHLIGHTS</p>
                    <h2 class="mb-4" style="font-size: 2.2rem;">What Makes This Unforgettable</h2>
                    <div class="row g-4 mt-1">
                        <?php foreach ($highlights as $hl): ?>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start p-3 bg-white border rounded-1" style="border-color: var(--color-stone-light) !important;">
                                    <span class="text-gold me-3 fs-5">&diams;</span>
                                    <span class="text-dark fw-medium" style="line-height: 1.6; font-size: 0.98rem;"><?= htmlspecialchars($hl['highlight']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <hr class="my-5 border-secondary opacity-15">
            <?php endif; ?>

            <!-- Overview -->
            <div class="mb-5 reveal">
                <p class="eyebrow">JOURNEY OVERVIEW</p>
                <h2 class="mb-4" style="font-size: 2.2rem;">The Experience</h2>
                <div class="fs-5 lh-lg text-secondary">
                    <?= nl2br(htmlspecialchars($pkg['description'] ?? '')) ?>
                </div>
            </div>

            <hr class="my-5 border-secondary opacity-15">

            <!-- Day-by-Day Itinerary Timeline -->
            <?php if (!empty($itinerary)): ?>
                <div class="mb-5 reveal">
                    <p class="eyebrow">CHOREOGRAPHED DAYS</p>
                    <h2 class="mb-4" style="font-size: 2.2rem;">Day-by-Day Itinerary</h2>
                    <div class="itin-timeline mt-4">
                        <?php foreach ($itinerary as $day): ?>
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
                <hr class="my-5 border-secondary opacity-15">
            <?php endif; ?>

            <!-- Inclusions / Exclusions -->
            <div class="row g-4 mb-5 reveal">
                <div class="col-md-6">
                    <div class="incl-card">
                        <p class="eyebrow mb-2">COMPLIMENTARY SERVICES</p>
                        <h4>Included in Your Journey</h4>
                        <ul class="incl-list">
                            <?php if (empty($included)): ?>
                                <li><span class="incl-yes">&check;</span> 5-Star Accommodations & Daily Breakfast</li>
                                <li><span class="incl-yes">&check;</span> Private Chauffeur Airport Transfers</li>
                                <li><span class="incl-yes">&check;</span> Dedicated English-Speaking Private Guide</li>
                            <?php else: ?>
                                <?php foreach ($included as $item): ?>
                                    <li><span class="incl-yes">&check;</span> <?= htmlspecialchars($item['title']) ?></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="incl-card">
                        <p class="eyebrow mb-2">ADDITIONAL EXTRAS</p>
                        <h4>Not Included</h4>
                        <ul class="incl-list">
                            <?php if (empty($excluded)): ?>
                                <li><span class="incl-no">&minus;</span> International Flights & Visa Fees</li>
                                <li><span class="incl-no">&minus;</span> Personal Expenditures & Discretionary Tips</li>
                                <li><span class="incl-no">&minus;</span> Optional Premium Upgrades & Spa Treatments</li>
                            <?php else: ?>
                                <?php foreach ($excluded as $item): ?>
                                    <li><span class="incl-no">&minus;</span> <?= htmlspecialchars($item['title']) ?></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Cancellation & Guarantees -->
            <div class="p-4 bg-cream border rounded-1 mb-5 reveal" style="border-color: var(--color-border) !important;">
                <h4 class="mb-2" style="font-size: 1.25rem;"><i class="bi bi-shield-lock text-gold me-2"></i> Transparent Terms & Guarantees</h4>
                <p class="text-secondary small mb-0" style="line-height: 1.8;">
                    All bookings with Patel Travels include complimentary itinerary rescheduling up to 21 days prior to departure. Private group sizes are strictly limited to ensure exclusivity, personalized pacing, and authentic access.
                </p>
            </div>

            <!-- Gallery -->
            <?php if (!empty($gallery)): ?>
                <hr class="my-5 border-secondary opacity-15">
                <div class="mb-5 reveal">
                    <p class="eyebrow">VISUAL SANCTUARY</p>
                    <h2 class="mb-4" style="font-size: 2.2rem;">Moments from the Trail</h2>
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
        
        <!-- Sticky Sidebar (Desktop) -->
        <div class="col-lg-4">
            <div class="journey-sidebar">
                <div class="journey-sidebar__label">Signature Investment</div>
                <div class="journey-sidebar__price mb-1">₹<?= number_format($pkg['price']) ?></div>
                <div class="text-muted-custom mb-3" style="font-size: 0.8rem;"><?= htmlspecialchars($pkg['price_type'] ?? 'per person, based on double occupancy') ?></div>
                
                <div class="mb-4 text-secondary small" style="line-height: 1.6;">
                    Includes luxury accommodation, dedicated private transport, and local expert guiding.
                </div>
                
                <div class="mb-4">
                    <div class="journey-sidebar__row">
                        <span class="journey-sidebar__label mb-0">Duration</span>
                        <span class="journey-sidebar__val"><?= htmlspecialchars($pkg['duration']) ?></span>
                    </div>
                    <div class="journey-sidebar__row">
                        <span class="journey-sidebar__label mb-0">Departure</span>
                        <span class="journey-sidebar__val"><?= !empty($pkg['travel_date']) ? date('M d, Y', strtotime($pkg['travel_date'])) : 'Tailored on Request' ?></span>
                    </div>
                    <div class="journey-sidebar__row">
                        <span class="journey-sidebar__label mb-0">Group Type</span>
                        <span class="journey-sidebar__val"><?= htmlspecialchars($pkg['plan_type'] ?? 'Private Custom') ?></span>
                    </div>
                    <div class="journey-sidebar__row">
                        <span class="journey-sidebar__label mb-0">Availability</span>
                        <?php if ($available > 0): ?>
                            <span class="badge bg-midnight border text-gold px-2 py-1"><?= $available ?> Guests Max</span>
                        <?php else: ?>
                            <span class="badge bg-danger text-white px-2 py-1">Fully Booked</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($available > 0): ?>
                    <a href="<?= url('book.php?package_id=' . $pkg['package_id']) ?>" class="btn-lux w-100 mb-3">RESERVE THIS JOURNEY</a>
                <?php else: ?>
                    <button class="btn-lux w-100 mb-3" style="opacity: 0.5; cursor: not-allowed;" disabled>Fully Booked</button>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="<?= url('contact.php?subject=' . urlencode('Inquiry regarding ' . $pkg['package_name'])) ?>" class="small text-uppercase fw-bold text-decoration-none" style="letter-spacing: 0.14em; color: var(--color-charcoal); border-bottom: 1px solid var(--color-gold);">
                        Speak to a Private Advisor <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Bottom Sticky Booking Bar -->
<div class="d-lg-none fixed-bottom bg-midnight border-top p-3 shadow-lg" style="border-color: var(--color-gold-border) !important; z-index: 1040;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="text-gold fw-bold fs-5">₹<?= number_format($pkg['price']) ?></span>
            <small class="d-block text-white-50" style="font-size: 0.7rem;"><?= htmlspecialchars($pkg['duration']) ?></small>
        </div>
        <a href="<?= url('book.php?package_id=' . $pkg['package_id']) ?>" class="btn-lux py-2 px-4" style="font-size: 0.72rem;">Reserve Now</a>
    </div>
</div>

<!-- Bottom Bespoke Banner -->
<section class="section-pad bg-charcoal text-center text-white">
    <div class="container reveal">
        <p class="eyebrow" style="color: var(--color-gold);">BESPOKE CUSTOMIZATION</p>
        <h2 style="color: var(--color-white); margin-bottom: 1.5rem;">Would You Like to Personalize This Itinerary?</h2>
        <p style="color: rgba(245, 240, 231, 0.7); max-width: 580px; margin: 0 auto 2.5rem; line-height: 1.8;">We can adjust nights, substitute boutique luxury suites, or arrange private aviation to create your exact vision.</p>
        <a href="<?= url('contact.php?subject=' . urlencode('Customization Request for ' . $pkg['package_name'])) ?>" class="btn-lux-light">REQUEST PERSONALIZED ADJUSTMENTS</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
