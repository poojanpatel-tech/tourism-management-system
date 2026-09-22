<?php
/**
 * Tour Packages Module - Index List & Management
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$destFilter = (int)($_GET['destination_id'] ?? 0);

// Fetch destinations for filter dropdown
$destinations = $pdo->query('SELECT destination_id, destination_name, country FROM destinations ORDER BY destination_name ASC')->fetchAll();

// Build package query with real-time booking counts
$sql = '
    SELECT p.package_id, p.package_code, p.package_name, p.destination_id, p.duration, p.price,
           p.maximum_capacity, p.travel_date, p.description, p.included_services, p.excluded_services,
           p.image, p.status, p.created_at,
           d.destination_name, d.country,
           COALESCE(SUM(CASE WHEN r.status IN ("confirmed", "pending") THEN r.number_of_travelers ELSE 0 END), 0) AS booked_count,
           (SELECT COUNT(*) FROM reservations r2 WHERE r2.package_id = p.package_id) AS total_reservations
    FROM packages p
    INNER JOIN destinations d ON p.destination_id = d.destination_id
    LEFT JOIN reservations r ON p.package_id = r.package_id
    WHERE 1=1
';
$params = [];

if ($search !== '') {
    $sql .= ' AND (p.package_name LIKE :search OR p.package_code LIKE :search OR d.destination_name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($statusFilter !== '' && in_array($statusFilter, ['active', 'inactive', 'sold_out'], true)) {
    $sql .= ' AND p.status = :status';
    $params[':status'] = $statusFilter;
}

if ($destFilter > 0) {
    $sql .= ' AND p.destination_id = :destination_id';
    $params[':destination_id'] = $destFilter;
}

$sql .= ' GROUP BY p.package_id, p.package_code, p.package_name, p.destination_id, p.duration, p.price,
                   p.maximum_capacity, p.travel_date, p.description, p.included_services, p.excluded_services,
                   p.image, p.status, p.created_at, d.destination_name, d.country
          ORDER BY p.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$packages = $stmt->fetchAll();

$totalPackages = count($packages);

$pageTitle = 'Tour Packages Management';
$activePage = 'packages';
$navTitle = 'Tour Packages Catalog';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <!-- Page Header & Actions -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Tour Packages Catalog</h3>
            <p class="text-muted small mb-0">Manage tour itineraries, pricing, durations, seat capacities, and booking availability.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= url('admin/packages/create.php') ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Add New Package
            </a>
            <a href="<?= url('admin/destinations/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-geo-alt me-1"></i> Destinations
            </a>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('admin/packages/index.php') ?>" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search package name, code, or destination..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select name="destination_id" class="form-select">
                        <option value="">All Destinations</option>
                        <?php foreach ($destinations as $d): ?>
                            <option value="<?= (int)$d['destination_id'] ?>" <?= ($destFilter === (int)$d['destination_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['destination_name'] . ' — ' . $d['country'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?= ($statusFilter === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($statusFilter === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        <option value="sold_out" <?= ($statusFilter === 'sold_out') ? 'selected' : '' ?>>Sold Out</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary px-3">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <?php if ($search !== '' || $statusFilter !== '' || $destFilter > 0): ?>
                        <a href="<?= url('admin/packages/index.php') ?>" class="btn btn-outline-secondary" title="Clear all filters">
                            <i class="bi bi-x-circle me-1"></i> Clear
                        </a>
                    <?php endif; ?>
                    <span class="ms-auto text-muted small d-none d-sm-inline">
                        <strong><?= $totalPackages ?></strong> package(s)
                    </span>
                </div>
            </form>
        </div>
    </div>

    <!-- Tour Packages Data Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Code</th>
                        <th>Package Name</th>
                        <th>Destination</th>
                        <th>Duration</th>
                        <th>Price</th>
                        <th>Travel Date</th>
                        <th>Availability</th>
                        <th>Status</th>
                        <th class="small">Created</th>
                        <th class="text-end" style="min-width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($packages)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-box2-heart fs-1 d-block mb-2 text-muted"></i>
                                <h6 class="fw-bold text-dark">No Tour Packages Found</h6>
                                <p class="small text-muted mb-3">No packages match your search or filter criteria.</p>
                                <a href="<?= url('admin/packages/create.php') ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg me-1"></i> Add Package Now
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($packages as $pkg): ?>
                            <?php
                            $pkgId = (int)$pkg['package_id'];
                            $capacity = (int)$pkg['maximum_capacity'];
                            $booked = (int)$pkg['booked_count'];
                            $available = max(0, $capacity - $booked);
                            $fillRate = $capacity > 0 ? min(100, round(($booked / $capacity) * 100)) : 0;
                            $resCount = (int)$pkg['total_reservations'];

                            // Availability label
                            if ($available <= 0) {
                                $availLabel = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle me-1"></i>Sold Out</span>';
                            } elseif ($fillRate >= 50) {
                                $availLabel = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-exclamation-triangle me-1"></i>Limited</span>';
                            } else {
                                $availLabel = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle me-1"></i>Available</span>';
                            }
                            ?>
                            <tr>
                                <td class="fw-bold font-monospace text-muted small">#<?= $pkgId ?></td>
                                <td class="font-monospace fw-bold text-primary small">
                                    <a href="<?= url('admin/packages/view.php?id=' . $pkgId) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($pkg['package_code'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?= url('admin/packages/view.php?id=' . $pkgId) ?>" class="fw-bold text-dark text-decoration-none">
                                        <?= htmlspecialchars($pkg['package_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold small text-dark">
                                        <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($pkg['destination_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <span class="text-muted small"><?= htmlspecialchars($pkg['country'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-clock me-1 text-primary"></i><?= htmlspecialchars($pkg['duration'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?= format_currency($pkg['price']) ?></td>
                                <td class="small text-muted"><?= format_date($pkg['travel_date']) ?></td>
                                <td>
                                    <div class="mb-1"><?= $availLabel ?></div>
                                    <div class="small text-muted font-monospace"><?= $booked ?>/<?= $capacity ?> <span class="text-muted">(<?= $available ?> free)</span></div>
                                    <div class="progress mt-1" style="height: 5px; width: 90px;">
                                        <div class="progress-bar <?= ($fillRate >= 80 ? 'bg-danger' : ($fillRate >= 50 ? 'bg-warning' : 'bg-success')) ?>"
                                             role="progressbar" style="width: <?= $fillRate ?>%;">
                                        </div>
                                    </div>
                                </td>
                                <td><?= get_status_badge($pkg['status']) ?></td>
                                <td class="small text-muted"><?= format_date($pkg['created_at']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <!-- View -->
                                        <a href="<?= url('admin/packages/view.php?id=' . $pkgId) ?>"
                                           class="btn btn-outline-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <!-- Edit -->
                                        <a href="<?= url('admin/packages/edit.php?id=' . $pkgId) ?>"
                                           class="btn btn-outline-primary" title="Edit Package">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <!-- Delete / Deactivate -->
                                        <button type="button"
                                                class="btn btn-outline-danger btn-delete-package"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deletePackageModal"
                                                data-id="<?= $pkgId ?>"
                                                data-name="<?= htmlspecialchars($pkg['package_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-code="<?= htmlspecialchars($pkg['package_code'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-reservations="<?= $resCount ?>"
                                                title="Delete or Deactivate Package">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Safe Delete / Deactivate Package -->
<div class="modal fade" id="deletePackageModal" tabindex="-1" aria-labelledby="deletePackageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="deletePackageModalLabel">
                    <i class="bi bi-shield-exclamation text-warning me-2"></i>Package Action
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-2">
                    You are managing: <strong id="modalPkgName">Package</strong>
                    (<code id="modalPkgCode">CODE</code>)
                </p>

                <!-- Warning if package has reservations -->
                <div id="modalResWarning" class="alert alert-warning py-2 px-3 small my-3" style="display: none;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Reservation Dependency:</strong> This package has <strong id="modalResCount">0</strong> reservation(s).
                    Deleting it would break booking records. You can safely switch its status to <strong>Inactive</strong> instead.
                </div>

                <div id="modalSafeNotice" class="alert alert-info py-2 px-3 small my-3" style="display: none;">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    This package has 0 reservations and can be safely deleted or deactivated.
                </div>

                <div class="d-flex flex-column gap-2 mt-3">
                    <!-- Deactivate -->
                    <form action="<?= url('admin/packages/delete.php') ?>" method="POST">
                        <input type="hidden" name="package_id" id="modalDeactivateId" value="0">
                        <input type="hidden" name="action" value="deactivate">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-outline-warning text-dark w-100 py-2 text-start d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold"><i class="bi bi-pause-circle me-1"></i> Mark as Inactive</div>
                                <div class="small text-muted">Hides package from new bookings while preserving existing reservation data.</div>
                            </div>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>

                    <!-- Hard Delete -->
                    <form action="<?= url('admin/packages/delete.php') ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this package? This cannot be undone.');">
                        <input type="hidden" name="package_id" id="modalDeleteId" value="0">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" id="modalDeleteBtn" class="btn btn-outline-danger w-100 py-2 text-start d-flex align-items-center justify-content-between mt-2">
                            <div>
                                <div class="fw-bold"><i class="bi bi-trash3 me-1"></i> Permanently Delete</div>
                                <div class="small text-muted" id="modalDeleteHint">Removes this package completely from MySQL.</div>
                            </div>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.btn-delete-package');

        deleteButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const code = this.getAttribute('data-code');
                const reservations = parseInt(this.getAttribute('data-reservations'), 10) || 0;

                document.getElementById('modalPkgName').textContent = name;
                document.getElementById('modalPkgCode').textContent = code;
                document.getElementById('modalDeactivateId').value = id;
                document.getElementById('modalDeleteId').value = id;

                if (reservations > 0) {
                    document.getElementById('modalResWarning').style.display = 'block';
                    document.getElementById('modalSafeNotice').style.display = 'none';
                    document.getElementById('modalResCount').textContent = reservations;
                    document.getElementById('modalDeleteBtn').disabled = true;
                    document.getElementById('modalDeleteHint').textContent = 'Disabled because ' + reservations + ' reservation(s) reference this package.';
                } else {
                    document.getElementById('modalResWarning').style.display = 'none';
                    document.getElementById('modalSafeNotice').style.display = 'block';
                    document.getElementById('modalDeleteBtn').disabled = false;
                    document.getElementById('modalDeleteHint').textContent = 'Removes this package completely from MySQL.';
                }
            });
        });
    });
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
