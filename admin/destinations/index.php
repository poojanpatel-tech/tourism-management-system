<?php
/**
 * Destinations Module - Index List & Management
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

// Base SQL with relational package counting
$sql = '
    SELECT d.destination_id, d.destination_name, d.country, d.description, d.status, d.created_at,
           COUNT(p.package_id) AS total_packages
    FROM destinations d
    LEFT JOIN packages p ON d.destination_id = p.destination_id
    WHERE 1=1
';
$params = [];

if ($search !== '') {
    $sql .= ' AND (d.destination_name LIKE :search OR d.country LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($statusFilter !== '' && in_array($statusFilter, ['active', 'inactive'], true)) {
    $sql .= ' AND d.status = :status';
    $params[':status'] = $statusFilter;
}

$sql .= ' GROUP BY d.destination_id, d.destination_name, d.country, d.description, d.status, d.created_at ORDER BY d.destination_name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$destinations = $stmt->fetchAll();

// Calculate total and status metrics
$totalDestinations = count($destinations);
$activeCount = 0;
$inactiveCount = 0;
foreach ($destinations as $d) {
    if ($d['status'] === 'active') {
        $activeCount++;
    } else {
        $inactiveCount++;
    }
}

$pageTitle = 'Destinations Management';
$activePage = 'destinations';
$navTitle = 'Destinations Management';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <!-- Page Header & Actions -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Destinations Management</h3>
            <p class="text-muted small mb-0">Manage global and domestic tourist destinations, countries, and tour package linkages.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= url('admin/destinations/create.php') ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Add New Destination
            </a>
            <a href="<?= url('admin/packages/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-box-seam me-1"></i> View Packages
            </a>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('admin/destinations/index.php') ?>" class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control border-start-0"
                               placeholder="Search destination name or country..."
                               value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?= ($statusFilter === 'active') ? 'selected' : '' ?>>Active Only (<?= $activeCount ?>)</option>
                        <option value="inactive" <?= ($statusFilter === 'inactive') ? 'selected' : '' ?>>Inactive Only (<?= $inactiveCount ?>)</option>
                    </select>
                </div>

                <!-- Submit & Clear Buttons -->
                <div class="col-6 col-md-4 d-flex gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary px-3">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <?php if ($search !== '' || $statusFilter !== ''): ?>
                        <a href="<?= url('admin/destinations/index.php') ?>" class="btn btn-outline-secondary" title="Clear all filters">
                            <i class="bi bi-x-circle me-1"></i> Clear
                        </a>
                    <?php endif; ?>
                    <span class="ms-auto text-muted small d-none d-sm-inline">
                        <strong><?= $totalDestinations ?></strong> destination(s)
                    </span>
                </div>
            </form>
        </div>
    </div>

    <!-- Destinations Data Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th>Destination Name</th>
                        <th>Country</th>
                        <th>Description</th>
                        <th class="text-center">Packages</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th class="text-end" style="min-width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($destinations)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-geo-alt fs-1 d-block mb-2 text-muted"></i>
                                <h6 class="fw-bold text-dark">No Destinations Found</h6>
                                <p class="small text-muted mb-3">No destination records match your filter criteria.</p>
                                <a href="<?= url('admin/destinations/create.php') ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg me-1"></i> Add Destination Now
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($destinations as $dest): ?>
                            <?php
                            $destId = (int)$dest['destination_id'];
                            $destName = $dest['destination_name'];
                            $destCountry = $dest['country'];
                            $pkgCount = (int)$dest['total_packages'];
                            ?>
                            <tr>
                                <td class="fw-bold font-monospace text-muted small">
                                    #<?= $destId ?>
                                </td>
                                <td>
                                    <a href="<?= url('admin/destinations/view.php?id=' . $destId) ?>" class="fw-bold text-dark text-decoration-none">
                                        <?= htmlspecialchars($destName, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="bi bi-flag me-1 text-primary"></i><?= htmlspecialchars($destCountry, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-muted small text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($dest['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($dest['description'] ?? 'No description provided.', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($pkgCount > 0): ?>
                                        <a href="<?= url('admin/destinations/view.php?id=' . $destId) ?>" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none font-monospace px-2 py-1" title="View linked tour packages">
                                            <?= $pkgCount ?> package(s)
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border font-monospace px-2 py-1">0 packages</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= get_status_badge($dest['status']) ?>
                                </td>
                                <td class="small text-muted">
                                    <?= format_date($dest['created_at']) ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <!-- View -->
                                        <a href="<?= url('admin/destinations/view.php?id=' . $destId) ?>"
                                           class="btn btn-outline-info"
                                           title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <!-- Edit -->
                                        <a href="<?= url('admin/destinations/edit.php?id=' . $destId) ?>"
                                           class="btn btn-outline-primary"
                                           title="Edit Destination">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <!-- Delete / Deactivate Trigger -->
                                        <button type="button"
                                                class="btn btn-outline-danger btn-delete-destination"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteConfirmModal"
                                                data-id="<?= $destId ?>"
                                                data-name="<?= htmlspecialchars($destName, ENT_QUOTES, 'UTF-8') ?>"
                                                data-country="<?= htmlspecialchars($destCountry, ENT_QUOTES, 'UTF-8') ?>"
                                                data-packages="<?= $pkgCount ?>"
                                                title="Delete or Deactivate Destination">
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

<!-- Modal: Safe Delete / Deactivate Destination -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="deleteConfirmModalLabel">
                    <i class="bi bi-shield-exclamation text-warning me-2"></i>Destination Action
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-2">
                    You are managing: <strong id="modalDestName">Destination</strong> (<span id="modalDestCountry">Country</span>)
                </p>

                <!-- Warning shown if destination has related packages -->
                <div id="modalPackageWarning" class="alert alert-warning py-2 px-3 small my-3" style="display: none;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Foreign Key Dependency:</strong> This destination currently has <strong id="modalPackageCount">0</strong> linked tour package(s).
                    MySQL constraints prevent hard deleting it. You can safely switch its status to <strong>Inactive</strong> instead.
                </div>

                <div id="modalSafeNotice" class="alert alert-info py-2 px-3 small my-3" style="display: none;">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    This destination has 0 linked tour packages and can be safely deleted or deactivated.
                </div>

                <div class="d-flex flex-column gap-2 mt-3">
                    <!-- Option 1: Deactivate (Safe) -->
                    <form action="<?= url('admin/destinations/delete.php') ?>" method="POST">
                        <input type="hidden" name="destination_id" id="modalDeactivateId" value="0">
                        <input type="hidden" name="action" value="deactivate">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-outline-warning text-dark w-100 py-2 text-start d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold"><i class="bi bi-pause-circle me-1"></i> Mark as Inactive</div>
                                <div class="small text-muted">Hides destination from package creation while preserving database integrity.</div>
                            </div>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>

                    <!-- Option 2: Hard Delete (Only enabled if 0 packages) -->
                    <form action="<?= url('admin/destinations/delete.php') ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this destination? This cannot be undone.');">
                        <input type="hidden" name="destination_id" id="modalDeleteId" value="0">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" id="modalDeleteBtn" class="btn btn-outline-danger w-100 py-2 text-start d-flex align-items-center justify-content-between mt-2">
                            <div>
                                <div class="fw-bold"><i class="bi bi-trash3 me-1"></i> Permanently Delete</div>
                                <div class="small text-muted" id="modalDeleteHint">Removes this destination completely from MySQL.</div>
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
    // Hook data into Delete Confirm Modal when modal opens
    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.btn-delete-destination');
        const modalDestName = document.getElementById('modalDestName');
        const modalDestCountry = document.getElementById('modalDestCountry');
        const modalPackageWarning = document.getElementById('modalPackageWarning');
        const modalPackageCount = document.getElementById('modalPackageCount');
        const modalSafeNotice = document.getElementById('modalSafeNotice');
        const modalDeactivateId = document.getElementById('modalDeactivateId');
        const modalDeleteId = document.getElementById('modalDeleteId');
        const modalDeleteBtn = document.getElementById('modalDeleteBtn');
        const modalDeleteHint = document.getElementById('modalDeleteHint');

        deleteButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const country = this.getAttribute('data-country');
                const packages = parseInt(this.getAttribute('data-packages'), 10) || 0;

                modalDestName.textContent = name;
                modalDestCountry.textContent = country;
                modalDeactivateId.value = id;
                modalDeleteId.value = id;

                if (packages > 0) {
                    modalPackageWarning.style.display = 'block';
                    modalSafeNotice.style.display = 'none';
                    modalPackageCount.textContent = packages;
                    modalDeleteBtn.disabled = true;
                    modalDeleteHint.textContent = 'Disabled because ' + packages + ' tour package(s) are linked to this destination.';
                } else {
                    modalPackageWarning.style.display = 'none';
                    modalSafeNotice.style.display = 'block';
                    modalDeleteBtn.disabled = false;
                    modalDeleteHint.textContent = 'Removes this destination completely from MySQL.';
                }
            });
        });
    });
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
