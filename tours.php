<?php
/**
 * Signature Journeys Directory — Patel Travels
 * Editorial, Luxury Tour Package Portfolio
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/public_functions.php';

$pdo = getDBConnection();

// Fetch filter parameters
$filterType = trim($_GET['type'] ?? '');
$filterDest = trim($_GET['destination'] ?? '');
$search = trim($_GET['q'] ?? '');
$sortBy = trim($_GET['sort'] ?? 'featured');
$travelDate = trim($_GET['travel_date'] ?? '');
$travellers = (int)($_GET['travellers'] ?? 0);

$where = ["p.status = 'active'"];
$params = [];

if (!empty($filterType)) {
    $where[] = "p.plan_type = :type";
    $params[':type'] = $filterType;
}

if (!empty($filterDest) && is_numeric($filterDest)) {
    $where[] = "p.destination_id = :dest";
    $params[':dest'] = $filterDest;
}

if (!empty($search)) {
    $where[] = "(p.package_name LIKE :search OR p.description LIKE :search OR d.destination_name LIKE :search OR d.country LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$whereSQL = implode(' AND ', $where);

// Sorting
$orderSQL = "p.featured DESC, p.display_order ASC, p.created_at DESC";
if ($sortBy === 'price_low') $orderSQL = "p.price ASC";
if ($sortBy === 'price_high') $orderSQL = "p.price DESC";
if ($sortBy === 'duration') $orderSQL = "p.duration_days ASC";

$stmt = $pdo->prepare("
    SELECT p.*, d.destination_name, d.country 
    FROM packages p 
    JOIN destinations d ON p.destination_id = d.destination_id 
    WHERE {$whereSQL} 
    ORDER BY {$orderSQL}
");
$stmt->execute($params);
$packages = $stmt->fetchAll();

// Destination list for filter
$destStmt = $pdo->query("SELECT destination_id, destination_name FROM destinations WHERE status = 'active' ORDER BY destination_name ASC");
$destList = $destStmt->fetchAll();

// Plan types for filter
$typeStmt = $pdo->query("SELECT DISTINCT plan_type FROM packages WHERE status = 'active' AND plan_type IS NOT NULL AND plan_type != '' ORDER BY plan_type ASC");
$typeList = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Signature Travel Collections';
$metaDescription = 'Explore our portfolio of handcrafted journeys across the world. Five-star accommodations, private guides, and bespoke pacing by Patel Travels.';
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<!-- ═══════════════════════════════════════════
     JOURNEYS HERO
     ═══════════════════════════════════════════ -->
<section class="page-hero">
    <div class="page-hero__bg" style="background-image: url('<?= get_public_image(null, 'hero') ?>');"></div>
    <div class="page-hero__content">
        <p class="eyebrow" style="color: var(--color-gold);">BESPOKE TRAVEL COLLECTIONS</p>
        <h1 class="page-hero__title">Signature Journeys.</h1>
        <p class="page-hero__sub">Meticulously paced itineraries combining private cultural access, five-star hospitality, and scenic wonder.</p>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     ADVANCED FILTER & SEARCH BAR
     ═══════════════════════════════════════════ -->
<section class="py-4 bg-cream border-bottom" style="border-color: var(--color-border) !important;">
    <div class="container">
        <form action="<?= url('tours.php') ?>" method="GET" class="row g-3 align-items-center">
            <div class="col-lg-3 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-gold" style="border-color: var(--color-border);"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Search journeys..." value="<?= htmlspecialchars($search) ?>" style="border-color: var(--color-border); padding: 0.75rem 1rem;">
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <select name="destination" class="form-select" onchange="this.form.submit()" style="border-color: var(--color-border); padding: 0.75rem 1rem;">
                    <option value="">All Destinations</option>
                    <?php foreach ($destList as $d): ?>
                        <option value="<?= $d['destination_id'] ?>" <?= ($filterDest == $d['destination_id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['destination_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <select name="type" class="form-select" onchange="this.form.submit()" style="border-color: var(--color-border); padding: 0.75rem 1rem;">
                    <option value="">All Collections</option>
                    <?php foreach ($typeList as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= ($filterType === $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <select name="sort" class="form-select" onchange="this.form.submit()" style="border-color: var(--color-border); padding: 0.75rem 1rem;">
                    <option value="featured" <?= ($sortBy === 'featured') ? 'selected' : '' ?>>Featured First</option>
                    <option value="price_low" <?= ($sortBy === 'price_low') ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_high" <?= ($sortBy === 'price_high') ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="duration" <?= ($sortBy === 'duration') ? 'selected' : '' ?>>Duration</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-4 d-flex gap-2">
                <button type="submit" class="btn-lux w-100" style="padding: 0.75rem 1rem;">FILTER</button>
                <?php if (!empty($search) || !empty($filterDest) || !empty($filterType) || $sortBy !== 'featured'): ?>
                    <a href="<?= url('tours.php') ?>" class="btn-lux-outline" style="padding: 0.75rem 1rem;" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     JOURNEYS GRID
     ═══════════════════════════════════════════ -->
<section class="section-pad bg-ivory">
    <div class="container">
        <?php if (empty($packages)): ?>
            <div class="text-center py-5 reveal">
                <i class="bi bi-compass fs-1 text-gold mb-3 d-block"></i>
                <h3 class="mb-2">No Journeys Match Your Search</h3>
                <p class="text-muted-custom mb-4">Try adjusting your filters or search terms to explore available collections.</p>
                <a href="<?= url('tours.php') ?>" class="btn-lux">RESET FILTERS</a>
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2">
                <span class="text-muted-custom" style="font-size: 0.88rem;">Showing <strong><?= count($packages) ?></strong> curated itineraries</span>
                <span class="badge bg-cream text-dark border px-3 py-2" style="font-size: 0.72rem; letter-spacing: 0.1em; text-transform: uppercase;">100% Tailored Private Departures</span>
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
                                    <i class="bi bi-people me-1 text-gold"></i> Max <?= (int)$pkg['maximum_capacity'] ?> Guests
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
                                        <a href="<?= url('book.php?package_id=' . $pkg['package_id']) ?>" class="btn-lux" style="padding: 0.55rem 1.1rem; font-size: 0.68rem;">Reserve</a>
                                        <a href="<?= url('tour-details.php?id=' . $pkg['package_id']) ?>" class="btn-lux-outline" style="padding: 0.55rem 0.9rem; font-size: 0.68rem;">Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
