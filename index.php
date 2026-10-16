<?php
/**
 * Ultra-Luxury Homepage — Patel Travels
 * Editorial, Five-Star Hospitality Brand Experience
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/public_functions.php';

$pdo = getDBConnection();

// Fetch destinations for curated showcase
$stmt = $pdo->query("SELECT * FROM destinations WHERE status = 'active' ORDER BY destination_id ASC LIMIT 4");
$destinations = $stmt->fetchAll();

// All destinations for the luxury journey finder
$allDestStmt = $pdo->query("SELECT destination_id, destination_name FROM destinations WHERE status = 'active' ORDER BY destination_name ASC");
$allDestinations = $allDestStmt->fetchAll();

// Distinct categories / plan types for finder
$catStmt = $pdo->query("SELECT DISTINCT plan_type FROM packages WHERE status = 'active' AND plan_type IS NOT NULL AND plan_type != '' ORDER BY plan_type ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Signature Tour Packages
$stmt = $pdo->query("
    SELECT p.*, d.destination_name, d.country 
    FROM packages p 
    JOIN destinations d ON p.destination_id = d.destination_id 
    WHERE p.status = 'active' 
    ORDER BY p.featured DESC, p.display_order ASC, p.created_at DESC 
    LIMIT 6
");
$packages = $stmt->fetchAll();

$pageTitle = 'The World, Experienced Differently';
$metaDescription = 'Discover remarkable destinations and thoughtfully curated journeys designed to make every moment unforgettable. Bespoke luxury travel by Patel Travels.';
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<!-- ═══════════════════════════════════════════
     CINEMATIC HERO SECTION
     ═══════════════════════════════════════════ -->
<section class="lux-hero">
    <div class="lux-hero__bg" style="background-image: url('<?= get_public_image(null, 'hero') ?>');"></div>
    <div class="lux-hero__content">
        <p class="eyebrow mb-3">EXCEPTIONAL JOURNEYS. EXTRAORDINARY PLACES.</p>
        <h1 class="lux-hero__title">The World, Experienced Differently.</h1>
        <p class="lux-hero__sub">Discover remarkable destinations and thoughtfully curated journeys designed to make every moment unforgettable.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= url('destinations.php') ?>" class="btn-lux">EXPLORE DESTINATIONS</a>
            <a href="<?= url('contact.php') ?>" class="btn-lux-light">PLAN YOUR JOURNEY ↗</a>
        </div>
    </div>
    <div class="lux-hero__scroll">
        Scroll to explore<br><i class="bi bi-chevron-down mt-2 d-block"></i>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     LUXURY JOURNEY FINDER
     ═══════════════════════════════════════════ -->
<div class="container lux-finder-wrap">
    <div class="lux-finder reveal">
        <form action="<?= url('tours.php') ?>" method="GET">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <div class="lux-finder__field">
                        <label class="lux-finder__label"><i class="bi bi-geo-alt me-1 text-gold"></i> Destination</label>
                        <select name="destination" class="lux-finder__select">
                            <option value="">All Destinations</option>
                            <?php foreach ($allDestinations as $d): ?>
                                <option value="<?= $d['destination_id'] ?>"><?= htmlspecialchars($d['destination_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="lux-finder__field">
                        <label class="lux-finder__label"><i class="bi bi-calendar3 me-1 text-gold"></i> Travel Date</label>
                        <input type="date" name="travel_date" class="lux-finder__input" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <div class="lux-finder__field">
                        <label class="lux-finder__label"><i class="bi bi-people me-1 text-gold"></i> Travellers</label>
                        <select name="travellers" class="lux-finder__select">
                            <option value="1">1 Guest</option>
                            <option value="2" selected>2 Guests</option>
                            <option value="4">3–4 Guests</option>
                            <option value="6">5+ Guests (Private Group)</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <div class="lux-finder__field">
                        <label class="lux-finder__label"><i class="bi bi-compass me-1 text-gold"></i> Collection</label>
                        <select name="type" class="lux-finder__select">
                            <option value="">All Styles</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-12">
                    <button type="submit" class="btn-lux lux-finder__btn">
                        <span>SEARCH</span> <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     CURATED DESTINATIONS ("Places That Stay With You")
     ═══════════════════════════════════════════ -->
<?php if (!empty($destinations)): ?>
<section class="section-pad bg-stone-light">
    <div class="container">
        <div class="lux-section-head reveal">
            <p class="eyebrow">CURATED DESTINATIONS</p>
            <h2>Places That Stay With You.</h2>
            <p>From the serene water villas of tropical islands to timeless European capitals and misty alpine peaks, explore sanctuaries crafted for memories.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($destinations as $i => $dest): ?>
                <div class="col-lg-6 col-md-6 reveal">
                    <a href="<?= url('destination-details.php?id=' . $dest['destination_id']) ?>" class="dest-card">
                        <img src="<?= get_destination_image($dest) ?>" alt="<?= htmlspecialchars($dest['destination_name']) ?>" loading="lazy">
                        <div class="dest-card__overlay">
                            <div class="dest-card__country"><?= htmlspecialchars($dest['country']) ?></div>
                            <h3 class="dest-card__name"><?= htmlspecialchars($dest['destination_name']) ?></h3>
                            <?php if (!empty($dest['description'])): ?>
                                <p class="dest-card__desc"><?= htmlspecialchars($dest['description']) ?></p>
                            <?php endif; ?>
                            <span class="dest-card__arrow">Discover Destination <i class="bi bi-arrow-right"></i></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5 pt-3 reveal">
            <a href="<?= url('destinations.php') ?>" class="btn-lux-outline">VIEW ALL DESTINATIONS</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════
     BRAND STORY (EDITORIAL)
     ═══════════════════════════════════════════ -->
<section class="section-pad brand-story">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6 pe-lg-5 reveal">
                <p class="eyebrow">THE ART OF CURATED TRAVEL</p>
                <div class="brand-story__quote">
                    "We do not simply book trips. We choreograph unrepeatable moments of beauty, calm, and wonder."
                </div>
                <div class="brand-stats">
                    <div>
                        <div class="brand-stat__num">100%</div>
                        <div class="brand-stat__label">Bespoke Design</div>
                    </div>
                    <div>
                        <div class="brand-stat__num">5-Star</div>
                        <div class="brand-stat__label">Hospitality Partners</div>
                    </div>
                    <div>
                        <div class="brand-stat__num">24 / 7</div>
                        <div class="brand-stat__label">Dedicated Concierge</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 reveal">
                <div class="brand-story__body ps-lg-4">
                    <p>At Patel Travels, we believe travel is among life’s greatest art forms. It is not merely moving across borders; it is the feeling of awakening in a historic Parisian residence, sailing through ancient Kyoto gardens, or watching twilight descend across alpine glaciers.</p>
                    <p>Our journey designers work with intimate local knowledge to grant you privileged access, private guides, and seamless transfers. Every detail is calibrated so your only responsibility is immersion.</p>
                    <div class="mt-4 pt-2">
                        <a href="<?= url('about.php') ?>" class="btn-lux-outline">READ OUR STORY</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     SIGNATURE TOUR COLLECTIONS
     ═══════════════════════════════════════════ -->
<?php if (!empty($packages)): ?>
<section class="section-pad bg-ivory">
    <div class="container">
        <div class="lux-section-head reveal">
            <p class="eyebrow">SIGNATURE COLLECTIONS</p>
            <h2>Choose the Way You Want to Travel.</h2>
            <p>Thoughtfully paced itineraries curated with luxury accommodations, private transportation, and cultural immersion.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($packages as $pkg): ?>
                <div class="col-lg-4 col-md-6 reveal">
                    <div class="journey-card">
                        <a href="<?= url('tour-details.php?id=' . $pkg['package_id']) ?>" class="journey-card__img">
                            <img src="<?= get_package_image($pkg) ?>" alt="<?= htmlspecialchars($pkg['package_name']) ?>" loading="lazy">
                            <?php if (!empty($pkg['plan_type'])): ?>
                                <span class="journey-card__badge"><?= htmlspecialchars($pkg['plan_type']) ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="journey-card__body">
                            <div class="journey-card__dest"><?= htmlspecialchars($pkg['destination_name']) ?>, <?= htmlspecialchars($pkg['country']) ?></div>
                            <h3 class="journey-card__name">
                                <a href="<?= url('tour-details.php?id=' . $pkg['package_id']) ?>">
                                    <?= htmlspecialchars($pkg['package_name']) ?>
                                </a>
                            </h3>
                            <div class="journey-card__meta">
                                <i class="bi bi-clock me-1 text-gold"></i> <?= htmlspecialchars($pkg['duration']) ?>
                                &bull;
                                <i class="bi bi-shield-check me-1 text-gold"></i> Fully Guaranteed
                            </div>
                            <?php if (!empty($pkg['description'])): ?>
                                <p class="journey-card__desc"><?= htmlspecialchars($pkg['description']) ?></p>
                            <?php endif; ?>
                            <div class="journey-card__foot">
                                <div>
                                    <span class="journey-card__price">₹<?= number_format($pkg['price']) ?></span>
                                    <small>per person</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="<?= url('tour-details.php?id=' . $pkg['package_id']) ?>" class="journey-card__cta">
                                        Details <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5 pt-3 reveal">
            <a href="<?= url('tours.php') ?>" class="btn-lux">EXPLORE ALL JOURNEYS</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════
     CONCIERGE PILLARS (PHILOSOPHY)
     ═══════════════════════════════════════════ -->
<section class="section-pad bg-midnight">
    <div class="container">
        <div class="lux-section-head reveal text-white">
            <p class="eyebrow" style="color: var(--color-gold);">THE CONCIERGE STANDARD</p>
            <h2 style="color: var(--color-white);">Effortless Luxury at Every Stage.</h2>
            <p style="color: rgba(245, 240, 231, 0.7);">We combine thoughtful travel design with five-star hospitality standards so your time is solely reserved for inspiration.</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6 reveal">
                <div class="concierge-pillar">
                    <div class="pillar-num">01</div>
                    <h3 class="pillar-title">Privileged Local Access</h3>
                    <p class="pillar-desc">Private entrances to cultural monuments, curated dining reservations, and certified English-speaking historians who unveil destinations beyond standard tourist trails.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 reveal">
                <div class="concierge-pillar">
                    <div class="pillar-num">02</div>
                    <h3 class="pillar-title">Tailored Pacing & Intimacy</h3>
                    <p class="pillar-desc">Every day is structured around balance. Experience exhilarating morning excursions paired with tranquil afternoons at five-star chalets, boutique ryokans, and wellness resorts.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 reveal">
                <div class="concierge-pillar">
                    <div class="pillar-num">03</div>
                    <h3 class="pillar-title">Round-the-Clock Concierge</h3>
                    <p class="pillar-desc">From seamless flight coordination and luxury chauffeur transfers to immediate itinerary adjustments, our private concierge team remains by your side at all hours.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     CALL TO ACTION — BESPOKE INQUIRY
     ═══════════════════════════════════════════ -->
<section class="section-pad bg-stone-light text-center">
    <div class="container reveal">
        <div class="mx-auto" style="max-width: 680px;">
            <p class="eyebrow">YOUR NEXT CHAPTER</p>
            <h2>Ready to Experience the World Differently?</h2>
            <p class="mb-4 pb-2">Connect with our senior travel specialists to begin crafting a bespoke itinerary tailored precisely to your schedule, preferences, and desires.</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="<?= url('contact.php') ?>" class="btn-lux">REQUEST BESPOKE ITINERARY</a>
                <a href="<?= url('tours.php') ?>" class="btn-lux-outline">BROWSE SIGNATURE TOURS</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
