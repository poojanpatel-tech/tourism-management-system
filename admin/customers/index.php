<?php
/**
 * Customers Module - Full List & Management
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

$search = trim($_GET['search'] ?? '');
$countryFilter = trim($_GET['country'] ?? '');
$genderFilter = trim($_GET['gender'] ?? '');

// Fetch unique countries for dropdown
$countries = $pdo->query('SELECT DISTINCT country FROM customers WHERE country IS NOT NULL AND country != "" ORDER BY country ASC')->fetchAll(PDO::FETCH_COLUMN);

// Build customer query with reservation aggregation
$sql = '
    SELECT c.customer_id, c.customer_code, c.full_name, c.email, c.phone, c.gender,
           c.date_of_birth, c.address, c.city, c.country, c.created_at,
           COUNT(r.reservation_id) AS total_bookings,
           COALESCE(SUM(CASE WHEN r.status IN ("confirmed", "pending") THEN r.number_of_travelers ELSE 0 END), 0) AS total_travelers,
           COALESCE(SUM(CASE WHEN r.status = "confirmed" THEN r.total_amount ELSE 0 END), 0) AS total_spent
    FROM customers c
    LEFT JOIN reservations r ON c.customer_id = r.customer_id
    WHERE 1=1
';
$params = [];

if ($search !== '') {
    $sql .= ' AND (c.full_name LIKE :search OR c.customer_code LIKE :search OR c.email LIKE :search OR c.phone LIKE :search OR c.city LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($countryFilter !== '') {
    $sql .= ' AND c.country = :country';
    $params[':country'] = $countryFilter;
}

if ($genderFilter !== '' && in_array($genderFilter, ['Male', 'Female', 'Other'], true)) {
    $sql .= ' AND c.gender = :gender';
    $params[':gender'] = $genderFilter;
}

$sql .= ' GROUP BY c.customer_id, c.customer_code, c.full_name, c.email, c.phone, c.gender,
                   c.date_of_birth, c.address, c.city, c.country, c.created_at
          ORDER BY c.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$totalCustomers = count($customers);
$hasFilters = ($search !== '' || $countryFilter !== '' || $genderFilter !== '');

$pageTitle = 'Customer Directory';
$activePage = 'customers';
$navTitle = 'Customer Management';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <!-- Page Header & Actions -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Customer Directory</h3>
            <p class="text-muted small mb-0">Registered tourists, contact directory, and booking histories.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= url('admin/customers/create.php') ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-person-plus me-1"></i> Add New Customer
            </a>
            <a href="<?= url('admin/reservations/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-calendar2-check me-1"></i> Reservations
            </a>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('admin/customers/index.php') ?>" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Search name, code, email, phone, city..."
                               value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select name="country" class="form-select">
                        <option value="">All Countries</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>" <?= ($countryFilter === $c) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="gender" class="form-select">
                        <option value="">All Genders</option>
                        <option value="Male" <?= ($genderFilter === 'Male') ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($genderFilter === 'Female') ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= ($genderFilter === 'Other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary px-3">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <?php if ($hasFilters): ?>
                        <a href="<?= url('admin/customers/index.php') ?>" class="btn btn-outline-secondary" title="Clear all filters">
                            <i class="bi bi-x-circle me-1"></i> Clear
                        </a>
                    <?php endif; ?>
                    <span class="ms-auto text-muted small d-none d-sm-inline">
                        <strong><?= $totalCustomers ?></strong> customer(s)
                    </span>
                </div>
            </form>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Code</th>
                        <th>Customer Name</th>
                        <th>Email & Phone</th>
                        <th>Gender</th>
                        <th>Location</th>
                        <th>DOB</th>
                        <th class="text-center">Bookings</th>
                        <th>Spent</th>
                        <th class="small">Registered</th>
                        <th class="text-end" style="min-width: 130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 text-muted"></i>
                                <h6 class="fw-bold text-dark">No Customers Found</h6>
                                <p class="small text-muted mb-3">No customer records match your search criteria.</p>
                                <a href="<?= url('admin/customers/create.php') ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-person-plus me-1"></i> Register Customer
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $cust): ?>
                            <?php
                            $custId = (int)$cust['customer_id'];
                            $bookings = (int)$cust['total_bookings'];
                            $initials = strtoupper(substr($cust['full_name'], 0, 1));
                            ?>
                            <tr>
                                <td class="fw-bold font-monospace text-muted small">#<?= $custId ?></td>
                                <td class="font-monospace fw-bold text-primary small">
                                    <a href="<?= url('admin/customers/view.php?id=' . $custId) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($cust['customer_code'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                            <?= $initials ?>
                                        </div>
                                        <div>
                                            <a href="<?= url('admin/customers/view.php?id=' . $custId) ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= htmlspecialchars($cust['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($cust['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted small"><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($cust['phone'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($cust['gender'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= htmlspecialchars($cust['city'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($cust['country'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td class="small text-muted"><?= format_date($cust['date_of_birth']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 font-monospace">
                                        <?= $bookings ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?= format_currency($cust['total_spent']) ?></td>
                                <td class="small text-muted"><?= format_date($cust['created_at']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= url('admin/customers/view.php?id=' . $custId) ?>"
                                           class="btn btn-outline-info" title="View Profile">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= url('admin/customers/edit.php?id=' . $custId) ?>"
                                           class="btn btn-outline-primary" title="Edit Customer">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-delete-customer"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteCustomerModal"
                                                data-id="<?= $custId ?>"
                                                data-name="<?= htmlspecialchars($cust['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-code="<?= htmlspecialchars($cust['customer_code'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-bookings="<?= $bookings ?>"
                                                title="Delete Customer">
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

<!-- Delete Customer Modal -->
<div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="deleteCustomerModalLabel">
                    <i class="bi bi-shield-exclamation text-warning me-2"></i>Delete Customer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-2">
                    You are about to manage: <strong id="modalCustName">Customer</strong>
                    (<code id="modalCustCode">CODE</code>)
                </p>

                <div id="modalResWarning" class="alert alert-warning py-2 px-3 small my-3" style="display: none;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Reservation Dependency:</strong> This customer has <strong id="modalResCount">0</strong> reservation(s).
                    Deleting would break booking records. The customer record should be preserved.
                </div>

                <div id="modalSafeNotice" class="alert alert-info py-2 px-3 small my-3" style="display: none;">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    This customer has no reservations and can be safely deleted.
                </div>

                <form action="<?= url('admin/customers/delete.php') ?>" method="POST"
                      onsubmit="return confirm('Are you sure you want to permanently delete this customer? This action cannot be undone.');">
                    <input type="hidden" name="customer_id" id="modalDeleteId" value="0">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" id="modalDeleteBtn"
                            class="btn btn-danger w-100 py-2 mt-2">
                        <i class="bi bi-trash3 me-1"></i> Permanently Delete Customer
                    </button>
                </form>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-delete-customer').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.getAttribute('data-id');
                var name = this.getAttribute('data-name');
                var code = this.getAttribute('data-code');
                var bookings = parseInt(this.getAttribute('data-bookings'), 10) || 0;

                document.getElementById('modalCustName').textContent = name;
                document.getElementById('modalCustCode').textContent = code;
                document.getElementById('modalDeleteId').value = id;

                if (bookings > 0) {
                    document.getElementById('modalResWarning').style.display = 'block';
                    document.getElementById('modalSafeNotice').style.display = 'none';
                    document.getElementById('modalResCount').textContent = bookings;
                    document.getElementById('modalDeleteBtn').disabled = true;
                } else {
                    document.getElementById('modalResWarning').style.display = 'none';
                    document.getElementById('modalSafeNotice').style.display = 'block';
                    document.getElementById('modalDeleteBtn').disabled = false;
                }
            });
        });
    });
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
