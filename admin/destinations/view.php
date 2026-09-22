<?php
/**
 * View Destination Details & Associated Packages
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash_message('danger', 'Invalid destination ID specified.');
    header('Location: ' . url('admin/destinations/index.php'));
    exit;
}

try {
    // 1. Fetch Destination details
    $stmt = $pdo->prepare('SELECT * FROM destinations WHERE destination_id = :id');
    $stmt->execute([':id' => $id]);
    $destination = $stmt->fetch();

    if (!$destination) {
        set_flash_message('danger', 'The destination you requested does not exist.');
        header('Location: ' . url('admin/destinations/index.php'));
        exit;
    }

    // 2. Fetch associated packages with real-time booked counts
    $stmtPackages = $pdo->prepare('
        SELECT p.package_id, p.package_code, p.package_name, p.duration, p.price,
               p.maximum_capacity, p.travel_date, p.status, p.created_at,
               COALESCE(SUM(CASE WHEN r.status IN ("confirmed", "pending") THEN r.number_of_travelers ELSE 0 END), 0) AS booked_count
        FROM packages p
        LEFT JOIN reservations r ON p.package_id = r.package_id
        WHERE p.destination_id = :id
        GROUP BY p.package_id, p.package_code, p.package_name, p.duration, p.price,
                 p.maximum_capacity, p.travel_date, p.status, p.created_at
        ORDER BY p.package_name ASC
    ');
    $stmtPackages->execute([':id' => $id]);
    $packages = $stmtPackages->fetchAll();

    $packageCount = count($packages);

} catch (PDOException $e) {
    set_flash_message('danger', 'Database query error: ' . $e->getMessage());
    header('Location: ' . url('admin/destinations/index.php'));
    exit;
}

$pageTitle = 'Destination: ' . $destination['destination_name'];
$activePage = 'destinations';
$navTitle = 'Destination Profile';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('admin/dashboard.php') ?>" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('admin/destinations/index.php') ?>" class="text-decoration-none">Destinations</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($destination['destination_name'], ENT_QUOTES, 'UTF-8') ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($destination['destination_name'], ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= url('admin/destinations/edit.php?id=' . $id) ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-pencil-square me-1"></i> Edit Destination
            </a>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="bi bi-trash3 me-1"></i> Delete / Deactivate
            </button>
            <a href="<?= url('admin/destinations/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Destination Info Card (4 Cols) -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Destination Details</h5>
                    <span class="badge bg-light text-muted border font-monospace">ID #<?= (int)$destination['destination_id'] ?></span>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Destination Name</label>
                        <h4 class="fw-bold text-dark mt-1"><?= htmlspecialchars($destination['destination_name'], ENT_QUOTES, 'UTF-8') ?></h4>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Country</label>
                        <div class="mt-1">
                            <span class="badge bg-light text-dark border fs-6 px-3 py-2">
                                <i class="bi bi-flag-fill text-primary me-2"></i><?= htmlspecialchars($destination['country'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Status</label>
                        <div class="mt-1">
                            <?= get_status_badge($destination['status']) ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Description</label>
                        <div class="p-3 bg-light rounded-3 text-muted small mt-1 border" style="line-height: 1.6;">
                            <?= !empty($destination['description']) ? nl2br(htmlspecialchars($destination['description'], ENT_QUOTES, 'UTF-8')) : '<em>No description provided for this destination.</em>' ?>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="small text-muted">
                        <div class="d-flex justify-content-between py-1">
                            <span>Created Date:</span>
                            <span class="fw-semibold text-dark"><?= format_date($destination['created_at'], 'M d, Y H:i') ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span>Last Updated:</span>
                            <span class="fw-semibold text-dark"><?= format_date($destination['updated_at'] ?? $destination['created_at'], 'M d, Y H:i') ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span>Associated Packages:</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">
                                <?= $packageCount ?> Package(s)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Associated Packages List (8 Cols) -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-box2-heart-fill text-info me-2"></i>Associated Tour Packages</h5>
                        <small class="text-muted">Itineraries configured for <?= htmlspecialchars($destination['destination_name'], ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                    <span class="badge bg-primary px-3 py-2 rounded-pill font-monospace"><?= $packageCount ?> Package(s)</span>
                </div>

                <div class="card-body p-0">
                    <?php if (empty($packages)): ?>
                        <div class="text-center py-5 px-3">
                            <div class="mb-3 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                                <h6 class="fw-bold text-dark">No Tour Packages Linked</h6>
                                <p class="small text-muted col-md-8 mx-auto">
                                    There are currently no tour packages configured for this destination. This destination can be safely removed or kept active for future packages.
                                </p>
                            </div>
                            <a href="<?= url('admin/packages/index.php') ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-box-seam me-1"></i> View Packages Catalog
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Package Name</th>
                                        <th>Duration</th>
                                        <th>Price</th>
                                        <th>Booked / Cap</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($packages as $pkg): ?>
                                        <?php
                                        $capacity = (int)$pkg['maximum_capacity'];
                                        $booked = (int)$pkg['booked_count'];
                                        $fillRate = $capacity > 0 ? min(100, round(($booked / $capacity) * 100)) : 0;
                                        ?>
                                        <tr>
                                            <td class="font-monospace fw-bold text-primary small">
                                                <?= htmlspecialchars($pkg['package_code'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($pkg['package_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($pkg['package_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                                <div class="text-muted small">
                                                    Travel: <?= format_date($pkg['travel_date']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?= htmlspecialchars($pkg['duration'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold text-teal">
                                                <?= format_currency($pkg['price']) ?>
                                            </td>
                                            <td>
                                                <div class="small fw-semibold text-dark font-monospace mb-1">
                                                    <?= $booked ?> / <?= $capacity ?>
                                                </div>
                                                <div class="progress" style="height: 5px; width: 80px;">
                                                    <div class="progress-bar <?= ($fillRate >= 80 ? 'bg-danger' : ($fillRate >= 50 ? 'bg-warning' : 'bg-success')) ?>"
                                                         role="progressbar" style="width: <?= $fillRate ?>%;">
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?= get_status_badge($pkg['status']) ?>
                                            </td>
                                            <td>
                                                <a href="<?= url('admin/packages/index.php?search=' . urlencode($pkg['package_code'])) ?>" class="btn btn-sm btn-outline-secondary" title="View in Catalog">
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Safe Delete / Deactivate Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="deleteModalLabel">
                    <i class="bi bi-shield-exclamation text-warning me-2"></i>Destination Actions
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-2">
                    You are managing destination: <strong><?= htmlspecialchars($destination['destination_name'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($destination['country'], ENT_QUOTES, 'UTF-8') ?>).
                </p>

                <?php if ($packageCount > 0): ?>
                    <div class="alert alert-warning py-2 px-3 small my-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Foreign Key Protection:</strong> This destination currently has <strong><?= $packageCount ?> linked tour package(s)</strong>.
                        To prevent broken customer reservations, MySQL prevents deleting it directly. We recommend deactivating it instead.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info py-2 px-3 small my-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        This destination has 0 linked tour packages. It can be safely deleted or deactivated.
                    </div>
                <?php endif; ?>

                <div class="d-flex flex-column gap-2 mt-3">
                    <!-- Option 1: Deactivate (Safe) -->
                    <form action="<?= url('admin/destinations/delete.php') ?>" method="POST">
                        <input type="hidden" name="destination_id" value="<?= (int)$id ?>">
                        <input type="hidden" name="action" value="deactivate">
                        <button type="submit" class="btn btn-outline-warning text-dark w-100 py-2 text-start d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold"><i class="bi bi-pause-circle me-1"></i> Mark as Inactive</div>
                                <div class="small text-muted">Hides destination from package creation while preserving historical data.</div>
                            </div>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>

                    <!-- Option 2: Hard Delete (Only if zero packages) -->
                    <form action="<?= url('admin/destinations/delete.php') ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this destination? This action cannot be undone.');">
                        <input type="hidden" name="destination_id" value="<?= (int)$id ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-outline-danger w-100 py-2 text-start d-flex align-items-center justify-content-between mt-2" <?= ($packageCount > 0 ? 'disabled' : '') ?>>
                            <div>
                                <div class="fw-bold"><i class="bi bi-trash3 me-1"></i> Permanently Delete</div>
                                <div class="small text-muted"><?= ($packageCount > 0 ? 'Disabled because ' . $packageCount . ' package(s) are linked.' : 'Completely removes this destination from MySQL.') ?></div>
                            </div>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
