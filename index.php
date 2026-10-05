<?php
/**
 * Homepage — Patel Travels Luxury Brand
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/public_functions.php';

$pdo = getDBConnection();

// Featured destinations (deterministic, not random)
$stmt = $pdo->query("SELECT * FROM destinations WHERE status = 'active' ORDER BY destination_id ASC LIMIT 4");
$destinations = $stmt->fetchAll();

// Featured journeys
$stmt = $pdo->query("
    SELECT p.*, d.destination_name, d.country 
    FROM packages p 
    JOIN destinations d ON p.destination_id = d.destination_id 
    WHERE p.status = 'active' 
    ORDER BY p.featured DESC, p.display_order ASC, p.created_at DESC 
    LIMIT 6
");
$packages = $stmt->fetchAll();

$pageTitle = 'Curated Travel Experiences';
$metaDescription = 'Discover thoughtfully designed journeys and unforgettable destinations. Premium curated travel experiences by Patel Travels.';
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<!-- ═══════════════════════════════════════════
     HERO — Full Viewport
     ═══════════════════════════════════════════ -->
<section class="lux-hero">
    <div class="lux-hero__bg" style="background-image: url('<?= get_public_image(null, 'hero') ?>');"></div>
    <div class="lux-hero__content">
        <p class="eyebrow mb-4">Curated Travel Experiences</p>
        <h1 class="lux-hero__title">Travel Beyond<br>The Ordinary</h1>
        <p class="lux-hero__sub">Thoughtfully designed journeys, unforgettable destinations and travel experiences created around the way you want to explore.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= url('tours.php') ?>" class="btn-lux">Explore Journeys</a>
            <a href="<?= url('destinations.php') ?>" class="btn-lux-light">Discover Destinations</a>
        </div>
    </div>
    <div class="lux-hero__scroll">
        Scroll to explore<br><i class="bi bi-chevron-down mt-2 d-block"></i>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     INTRODUCTION — Editorial
     ═══════════════════════════════════════════ -->
<section class="section-pad">
    <div class="container">
        <div class="lux-intro reveal">
            <div>
                <p class="eyebrow mb-3">The Art of Travel</p>
                <h2 class="lux-intro__heading">Journeys designed around<br>the places worth remembering.</h2>
            </div>
            <div>
                <p class="lux-intro__text">We believe travel is not simply about reaching a destination. It is about the experiences along the way — the cultures you encounter, the landscapes that take your breath away, and the moments that stay with you long after you return.</p>
                <p class="lux-intro__text">Every journey we craft is built on local insight, personal attention, and a genuine love for the art of travel.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     DESTINATIONS — Editorial Grid
     ═══════════════════════════════════════════ -->
<?php if (!empty($destinations)): ?>
<section class="section-pad bg-stone">
    <div class="container">
        <div class="lux-section-head reveal">
            <p class="eyebrow mb-3">Places Worth Discovering</p>
            <h2>Discover somewhere<br>extraordinary.</h2>
        </div>
        <div class="row g-4">
            <?php foreach ($destinations as $i => $dest): ?>
                <div class="col-lg-<?= ($i < 2) ? '6' : '6' ?> reveal">
                    <a href="<?= url('destination-details.php?id=' . $dest['destination_id']) ?>" class="dest-card">
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
        <div class="text-center mt-5 pt-3 reveal">
            <a href="<?= url('destinations.php') ?>" class="btn-lux-outline">View All Destinations</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════
     CURATED JOURNEYS
     ═══════════════════════════════════════════ -->
<?php if (!empty($packages)): ?>
<section class="section-pad">
    <div class="container">
        <div class="lux-section-head reveal">
            <p class="eyebrow mb-3">Curated Journeys</p>
            <h2>Choose the way you<br>want to travel.</h2>
        </div>
        <div class="row g-4">
            <?php foreach ($packages as $pkg): ?>
                <div class="col-lg-4 col-md-6 reveal">
                    <a href="<?= url('tour-details.php?id=' . $pkg['package_id']) ?>" class="journey-card">
                        <div class="journey-card__img">
                            <img src="<?= get_public_image($pkg['image'] ?? null, 'plan') ?>" alt="<?= htmlspecialchars($pkg['package_name']) ?>" loading="lazy">
                            <?php if (!empty($pkg['plan_type'])): ?>
                                <div class="journey-card__badge"><?= htmlspecialchars($pkg['plan_type']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="journey-card__body">
                            <div class="journey-card__dest"><?= htmlspecialchars($pkg['destination_name']) ?></div>
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
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════
     WHY TRAVEL WITH US — Qualitative
     ═══════════════════════════════════════════ -->
<section class="section-pad bg-stone">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 reveal">
                <p class="eyebrow mb-3">Why Travel With Us</p>
                <h2 style="font-family: var(--font-heading); font-size: clamp(2rem, 3.5vw, 2.8rem); font-weight: 400; line-height: 1.15;">More than a trip.<br>A journey thoughtfully<br>taken care of.</h2>
                <a href="<?= url('about.php') ?>" class="btn-lux mt-4">Learn More</a>
            </div>
            <div class="col-lg-6 offset-lg-1 reveal">
                <div class="row g-4">
                    <div class="col-sm-6">
                        <div class="lux-benefit">
                            <h4 class="lux-benefit__title">Thoughtfully Planned</h4>
                            <p class="lux-benefit__text">Every detail considered, from the first enquiry to the final day of your journey.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="lux-benefit">
                            <h4 class="lux-benefit__title">Personalized Journeys</h4>
                            <p class="lux-benefit__text">Itineraries shaped around how you want to travel, not generic tour groups.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="lux-benefit">
                            <h4 class="lux-benefit__title">Local Insight</h4>
                            <p class="lux-benefit__text">Experience destinations through the eyes of those who know them best.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="lux-benefit">
                            <h4 class="lux-benefit__title">Human Support</h4>
                            <p class="lux-benefit__text">Real people, available when you need them. Before, during and after your trip.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     EDITORIAL FEATURE — Split
     ═══════════════════════════════════════════ -->
<section class="reveal">
    <div class="lux-split">
        <div class="lux-split__img">
            <img src="<?= get_public_image(null, 'destination') ?>" alt="Travel Experience" loading="lazy">
        </div>
        <div class="lux-split__content">
            <p class="eyebrow mb-3">Beyond the Itinerary</p>
            <h2 style="font-family: var(--font-heading); font-size: clamp(2rem, 3.5vw, 3rem); font-weight: 400; line-height: 1.15; margin-bottom: 1.5rem;">Discover the places<br>that stay with you.</h2>
            <p style="color: var(--color-muted); line-height: 1.8; margin-bottom: 2rem;">The best journeys are not measured in kilometres. They are measured in moments — the unexpected conversation, the hidden courtyard, the sunrise you almost missed. That is what we help you find.</p>
            <a href="<?= url('tours.php') ?>" class="btn-lux" style="align-self: flex-start;">Explore Journeys</a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     JOURNEY PLANNING CTA
     ═══════════════════════════════════════════ -->
<section class="section-pad bg-charcoal" style="text-align: center; color: #fff;">
    <div class="container reveal">
        <p class="eyebrow mb-3" style="color: var(--color-gold);">Ready to Begin?</p>
        <h2 style="font-family: var(--font-heading); font-size: clamp(2rem, 4vw, 3.5rem); font-weight: 400; color: #fff; margin-bottom: 1.5rem;">Your next journey<br>starts with a conversation.</h2>
        <p style="color: rgba(255,255,255,0.55); max-width: 500px; margin: 0 auto 2.5rem; line-height: 1.8;">Tell us where you want to go, how you want to travel, and what matters most. We will help shape the journey around you.</p>
        <a href="<?= url('contact.php') ?>" class="btn-lux-light">Plan Your Journey</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
