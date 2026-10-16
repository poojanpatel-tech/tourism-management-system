<?php
/**
 * Destinations Directory — Patel Travels
 * Editorial, Luxury Destination Portfolio
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/public_functions.php';

$pdo = getDBConnection();

// Search and filter parameters
$search = trim($_GET['q'] ?? '');
$filterCountry = trim($_GET['country'] ?? '');

$where = ["d.status = 'active'"];
$params = [];

if (!empty($search)) {
    $where[] = "(d.destination_name LIKE :search OR d.country LIKE :search OR d.description LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if (!empty($filterCountry)) {
    $where[] = "d.country = :country";
    $params[':country'] = $filterCountry;
}

$whereSQL = implode(' AND ', $where);

// Fetch destinations with active journey counts
$stmt = $pdo->prepare("
    SELECT d.*, COUNT(p.package_id) as package_count 
    FROM destinations d 
    LEFT JOIN packages p ON d.destination_id = p.destination_id AND p.status = 'active' 
    WHERE {$whereSQL} 
    GROUP BY d.destination_id 
    ORDER BY d.destination_name ASC
");
$stmt->execute($params);
$destinations = $stmt->fetchAll();

// Distinct countries for filter
$countryStmt = $pdo->query("SELECT DISTINCT country FROM destinations WHERE status = 'active' ORDER BY country ASC");
$countries = $countryStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Curated Destinations';
$metaDescription = 'Explore extraordinary destinations curated by Patel Travels. From tropical paradises and European capitals to serene backwaters.';
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<!-- ═══════════════════════════════════════════
     DESTINATIONS HERO
     ═══════════════════════════════════════════ -->
<section class="page-hero">
    <div class="page-hero__bg" style="background-image: url('<?= get_public_image(null, 'hero') ?>');"></div>
    <div class="page-hero__content">
        <p class="eyebrow" style="color: var(--color-gold);">PORTFOLIO OF WONDER</p>
        <h1 class="page-hero__title">Places That Stay With You.</h1>
        <p class="page-hero__sub">Immerse yourself in remarkable landscapes, iconic cultural capitals, and restorative natural havens.</p>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     FILTER & SEARCH BAR
     ═══════════════════════════════════════════ -->
<section class="py-4 bg-cream border-bottom" style="border-color: var(--color-border) !important;">
    <div class="container">
        <form action="<?= url('destinations.php') ?>" method="GET" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-gold" style="border-color: var(--color-border);"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Search by destination or landmark..." value="<?= htmlspecialchars($search) ?>" style="border-color: var(--color-border); padding: 0.75rem 1rem;">
                </div>
            </div>
            <div class="col-md-4">
                <select name="country" class="form-select" onchange="this.form.submit()" style="border-color: var(--color-border); padding: 0.75rem 1rem;">
                    <option value="">All Countries & Regions</option>
                    <?php foreach ($countries as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= ($filterCountry === $c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn-lux w-100" style="padding: 0.75rem 1.2rem;">FILTER</button>
                <?php if (!empty($search) || !empty($filterCountry)): ?>
                    <a href="<?= url('destinations.php') ?>" class="btn-lux-outline" style="padding: 0.75rem 1rem;" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     DESTINATIONS GRID
     ═══════════════════════════════════════════ -->
<section class="section-pad bg-ivory">
    <div class="container">
        <?php if (empty($destinations)): ?>
            <div class="text-center py-5 reveal">
                <i class="bi bi-compass fs-1 text-gold mb-3 d-block"></i>
                <h3 class="mb-2">No Destinations Found</h3>
                <p class="text-muted-custom mb-4">We could not find any destinations matching your search criteria.</p>
                <a href="<?= url('destinations.php') ?>" class="btn-lux">VIEW ALL DESTINATIONS</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($destinations as $dest): ?>
                    <div class="col-lg-4 col-md-6 reveal">
                        <a href="<?= url('destination-details.php?id=' . $dest['destination_id']) ?>" class="dest-card">
                            <img src="<?= get_destination_image($dest) ?>" alt="<?= htmlspecialchars($dest['destination_name']) ?>" loading="lazy">
                            <div class="dest-card__overlay">
                                <div class="dest-card__country"><?= htmlspecialchars($dest['country']) ?></div>
                                <h3 class="dest-card__name"><?= htmlspecialchars($dest['destination_name']) ?></h3>
                                <?php if (!empty($dest['description'])): ?>
                                    <p class="dest-card__desc"><?= htmlspecialchars($dest['description']) ?></p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="badge bg-midnight border border-secondary text-gold px-2 py-1" style="font-size: 0.68rem; letter-spacing: 0.1em;">
                                        <?= (int)$dest['package_count'] ?> <?= $dest['package_count'] == 1 ? 'Journey' : 'Journeys' ?>
                                    </span>
                                    <span class="dest-card__arrow">Explore <i class="bi bi-arrow-right"></i></span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
