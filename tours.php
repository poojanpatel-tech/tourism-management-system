<?php
/**
 * Journeys Listing — Patel Travels
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/public_functions.php';

$pdo = getDBConnection();

// Fetch filters
$filterType = $_GET['type'] ?? '';
$filterDest = $_GET['destination'] ?? '';
$sortBy = $_GET['sort'] ?? 'featured';

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

$whereSQL = implode(' AND ', $where);

$orderSQL = "p.featured DESC, p.display_order ASC, p.created_at DESC";
if ($sortBy === 'price_low') $orderSQL = "p.price ASC";
if ($sortBy === 'price_high') $orderSQL = "p.price DESC";
if ($sortBy === 'duration') $orderSQL = "p.duration_days ASC";

$stmt = $pdo->prepare("
    SELECT p.*, d.destination_name, d.country 
    FROM packages p 
    JOIN destinations d ON p.destination_id = d.destination_id 
    WHERE $whereSQL 
    ORDER BY $orderSQL
");
$stmt->execute($params);
$packages = $stmt->fetchAll();

// Get destination list for filter
$destStmt = $pdo->query("SELECT destination_id, destination_name FROM destinations WHERE status = 'active' ORDER BY destination_name ASC");
$destList = $destStmt->fetchAll();

// Get plan types for filter
$typeStmt = $pdo->query("SELECT DISTINCT plan_type FROM packages WHERE status = 'active' AND plan_type IS NOT NULL ORDER BY plan_type");
$typeList = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Curated Journeys';
$metaDescription = 'Browse our collection of curated travel journeys. Day trips, premium escapes, family holidays and more.';
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<section class="page-hero">
    <div class="page-hero__bg" style="background-image: url('<?= get_public_image(null, 'hero') ?>');"></div>
    <div class="page-hero__content">
        <p class="eyebrow mb-3">Curated Journeys</p>
        <h1 style="font-family: var(--font-heading); font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 400; color: #fff;">Choose the way you<br>want to travel.</h1>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <!-- Filters -->
        <div class="row mb-5 g-3 reveal">
            <div class="col-md-4">
                <select class="form-select" style="border-radius: 0; border-color: var(--color-border); padding: 0.75rem 1rem; font-size: 0.9rem;" onchange="location.href=this.value;">
                    <option value="<?= url('tours.php') ?>">All Destinations</option>
                    <?php foreach ($destList as $d): ?>
                        <option value="<?= url('tours.php?destination=' . $d['destination_id'] . ($filterType ? '&type=' . urlencode($filterType) : '') . ($sortBy !== 'featured' ? '&sort=' . urlencode($sortBy) : '')) ?>" <?= ($filterDest == $d['destination_id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['destination_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" style="border-radius: 0; border-color: var(--color-border); padding: 0.75rem 1rem; font-size: 0.9rem;" onchange="location.href=this.value;">
                    <option value="<?= url('tours.php' . ($filterDest ? '?destination=' . urlencode($filterDest) : '')) ?>">All Journey Types</option>
                    <?php foreach ($typeList as $t): ?>
                        <option value="<?= url('tours.php?type=' . urlencode($t) . ($filterDest ? '&destination=' . urlencode($filterDest) : '') . ($sortBy !== 'featured' ? '&sort=' . urlencode($sortBy) : '')) ?>" <?= ($filterType === $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" style="border-radius: 0; border-color: var(--color-border); padding: 0.75rem 1rem; font-size: 0.9rem;" onchange="location.href=this.value;">
                    <option value="<?= url('tours.php?' . http_build_query(array_filter(['destination' => $filterDest, 'type' => $filterType, 'sort' => 'featured']))) ?>" <?= ($sortBy === 'featured') ? 'selected' : '' ?>>Featured First</option>
                    <option value="<?= url('tours.php?' . http_build_query(array_filter(['destination' => $filterDest, 'type' => $filterType, 'sort' => 'price_low']))) ?>" <?= ($sortBy === 'price_low') ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="<?= url('tours.php?' . http_build_query(array_filter(['destination' => $filterDest, 'type' => $filterType, 'sort' => 'price_high']))) ?>" <?= ($sortBy === 'price_high') ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="<?= url('tours.php?' . http_build_query(array_filter(['destination' => $filterDest, 'type' => $filterType, 'sort' => 'duration']))) ?>" <?= ($sortBy === 'duration') ? 'selected' : '' ?>>Duration: Shortest</option>
                </select>
            </div>
        </div>

        <?php if (empty($packages)): ?>
            <div class="text-center py-5 reveal">
                <p style="font-family: var(--font-heading); font-size: 1.8rem; color: var(--color-charcoal); margin-bottom: 1rem;">No journeys match your selection.</p>
                <p class="text-muted-custom mb-4">Try adjusting your filters or explore all our destinations.</p>
                <a href="<?= url('tours.php') ?>" class="btn-lux-outline">View All Journeys</a>
            </div>
        <?php else: ?>
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
                                <div class="journey-card__dest"><?= htmlspecialchars($pkg['destination_name']) ?>, <?= htmlspecialchars($pkg['country']) ?></div>
                                <h3 class="journey-card__name"><?= htmlspecialchars($pkg['package_name']) ?></h3>
                                <div class="journey-card__meta"><?= htmlspecialchars($pkg['duration']) ?></div>
                                <div class="journey-card__price">From ₹<?= number_format($pkg['price']) ?> <small><?= htmlspecialchars($pkg['price_type'] ?? 'per person') ?></small></div>
                                <span class="journey-card__cta">Explore Journey <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
