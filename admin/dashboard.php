<?php
/**
 * Administrator Dashboard
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Session guard
require_login();

$pdo = getDBConnection();

// Initialize KPIs
$stats = [
    'total_destinations'     => 0,
    'active_destinations'    => 0,
    'total_packages'         => 0,
    'active_packages'        => 0,
    'total_customers'        => 0,
    'total_reservations'     => 0,
    'pending_reservations'   => 0,
    'confirmed_reservations' => 0,
    'cancelled_reservations' => 0,
    'total_travelers'        => 0,
    'total_revenue'          => 0,
];

try {
    // Destinations
    $stats['total_destinations'] = (int)$pdo->query('SELECT COUNT(*) FROM destinations')->fetchColumn();
    $stats['active_destinations'] = (int)$pdo->query('SELECT COUNT(*) FROM destinations WHERE status = "active"')->fetchColumn();
    
    // Packages
    $stats['total_packages'] = (int)$pdo->query('SELECT COUNT(*) FROM packages')->fetchColumn();
    $stats['active_packages'] = (int)$pdo->query('SELECT COUNT(*) FROM packages WHERE status = "active"')->fetchColumn();
    
    // Enquiries
    $enqStats = $pdo->query('
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = "new" THEN 1 ELSE 0 END) AS new_enq,
            SUM(CASE WHEN status = "in_discussion" THEN 1 ELSE 0 END) AS discussion,
            SUM(CASE WHEN status = "quoted" THEN 1 ELSE 0 END) AS quoted,
            SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) AS confirmed
        FROM enquiries
    ')->fetch(PDO::FETCH_ASSOC);

    $stats['total_enquiries'] = (int)($enqStats['total'] ?? 0);
    $stats['new_enquiries'] = (int)($enqStats['new_enq'] ?? 0);
    $stats['discussion_enquiries'] = (int)($enqStats['discussion'] ?? 0);
    $stats['quoted_enquiries'] = (int)($enqStats['quoted'] ?? 0);
    $stats['confirmed_enquiries'] = (int)($enqStats['confirmed'] ?? 0);

    // Recent Enquiries (Latest 10)
    $recentEnquiries = $pdo->query('
        SELECT e.id, e.name AS customer_name, e.email AS customer_email, e.created_at, e.status,
               p.package_name
        FROM enquiries e
        LEFT JOIN packages p ON e.package_id = p.package_id
        ORDER BY e.created_at DESC
        LIMIT 10
    ')->fetchAll();

    // Most Enquired Packages
    $popularPackages = $pdo->query('
        SELECT p.package_name, d.destination_name,
               COUNT(e.id) AS total_enquiries,
               COALESCE(SUM(CASE WHEN e.status = "confirmed" THEN 1 ELSE 0 END), 0) AS confirmed_enquiries
        FROM packages p
        INNER JOIN destinations d ON p.destination_id = d.destination_id
        LEFT JOIN enquiries e ON p.package_id = e.package_id
        GROUP BY p.package_id, p.package_name, d.destination_name
        ORDER BY total_enquiries DESC
        LIMIT 5
    ')->fetchAll();

    // Destination Performance
    $destinationPerformance = $pdo->query('
        SELECT d.destination_name,
               COUNT(DISTINCT p.package_id) AS package_count,
               COUNT(e.id) AS total_enquiries,
               COALESCE(SUM(CASE WHEN e.status = "confirmed" THEN 1 ELSE 0 END), 0) AS confirmed_enquiries
        FROM destinations d
        LEFT JOIN packages p ON d.destination_id = p.destination_id
        LEFT JOIN enquiries e ON p.package_id = e.package_id
        GROUP BY d.destination_id, d.destination_name
        ORDER BY total_enquiries DESC
        LIMIT 5
    ')->fetchAll();

    // Package Availability (Same accurate logic)
    $packageAvailability = $pdo->query('
        SELECT p.package_id, p.package_code, p.package_name, p.maximum_capacity, p.price, p.status,
               COALESCE(SUM(CASE WHEN e.status IN ("confirmed", "quoted") THEN e.travellers ELSE 0 END), 0) AS booked_count
        FROM packages p
        LEFT JOIN enquiries e ON p.package_id = e.package_id
        WHERE p.status = "active"
        GROUP BY p.package_id, p.package_code, p.package_name, p.maximum_capacity, p.price, p.status
        ORDER BY booked_count DESC, p.package_name ASC
        LIMIT 6
    ')->fetchAll();

} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

$pageTitle = 'Admin Dashboard';
$activePage = 'dashboard';
$navTitle = 'Operational Dashboard';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <!-- Page Header & Quick Welcome -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Welcome back, <?= htmlspecialchars(get_logged_in_user()['full_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>!</h3>
            <p class="text-muted small mb-0">Operational overview of packages, destinations, and real-time bookings.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex flex-wrap gap-2">
            <a href="<?= url('admin/enquiries/index.php') ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-envelope-paper-fill me-1"></i> View Enquiries
            </a>
            <a href="<?= url('admin/reports/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-bar-graph me-1"></i> Full Reports
            </a>
        </div>
    </div>

    <!-- Quick Actions Bar -->
    <div class="card mb-4 bg-light border-0 shadow-sm">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="fw-semibold text-dark small"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick Links:</span>
                <a href="<?= url('admin/destinations/create.php') ?>" class="text-decoration-none small text-muted hover-primary">
                    <i class="bi bi-geo-alt"></i> Add Destination
                </a>
                <a href="<?= url('admin/packages/create.php') ?>" class="text-decoration-none small text-muted hover-primary">
                    <i class="bi bi-box-seam"></i> Add Package
                </a>
                <a href="<?= url('admin/customers/create.php') ?>" class="text-decoration-none small text-muted hover-primary">
                    <i class="bi bi-person-plus"></i> Register Customer
                </a>
                <a href="<?= url('admin/reservations/index.php') ?>" class="text-decoration-none small text-muted hover-primary">
                    <i class="bi bi-list-task"></i> All Reservations
                </a>
            </div>
        </div>
    </div>

    <!-- 11 Primary Dashboard KPI Cards -->
    <div class="row g-3 mb-4">
        <!-- Revenue Card (Spans 2 cols on md for emphasis) -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card stat-card p-3 h-100 bg-primary text-white shadow">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label text-white-50">Confirmed Booking Revenue</div>
                        <div class="stat-number mt-1 text-white"><?= format_currency($stats['total_revenue']) ?></div>
                        <div class="text-white-50 small mt-1">
                            <i class="bi bi-cash-stack me-1"></i> Earned from confirmed trips
                        </div>
                    </div>
                    <div class="stat-icon bg-white text-primary rounded-circle" style="opacity: 0.9;">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card stat-card p-3 h-100 shadow-sm border-0">
                <div class="text-muted small fw-semibold text-uppercase mb-1">Destinations</div>
                <h3 class="fw-bold mb-0 text-dark"><?= $stats['total_destinations'] ?></h3>
                <div class="small text-success mt-1"><i class="bi bi-check-circle me-1"></i><?= $stats['active_destinations'] ?> Active</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card stat-card p-3 h-100 shadow-sm border-0">
                <div class="text-muted small fw-semibold text-uppercase mb-1">Packages</div>
                <h3 class="fw-bold mb-0 text-dark"><?= $stats['total_packages'] ?></h3>
                <div class="small text-success mt-1"><i class="bi bi-check-circle me-1"></i><?= $stats['active_packages'] ?> Active</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card stat-card p-3 h-100 shadow-sm border-0">
                <div class="text-muted small fw-semibold text-uppercase mb-1">Enquiries</div>
                <h3 class="fw-bold mb-0 text-dark"><?= $stats['total_enquiries'] ?></h3>
                <div class="small text-muted mt-1"><i class="bi bi-envelope me-1"></i>Total Received</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card stat-card p-3 h-100 shadow-sm border-0">
                <div class="text-muted small fw-semibold text-uppercase mb-1">New Leads</div>
                <h3 class="fw-bold mb-0 text-primary"><?= $stats['new_enquiries'] ?></h3>
                <div class="small text-muted mt-1"><i class="bi bi-bell-fill me-1"></i>Awaiting Action</div>
            </div>
        </div>
    </div>

    <!-- Enquiry Status Summary -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card p-3 h-100 shadow-sm border-0 border-start border-4 border-secondary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold">Total Enquiries</div>
                        <h4 class="fw-bold mb-0 mt-1"><?= $stats['total_enquiries'] ?></h4>
                    </div>
                    <i class="bi bi-journal-bookmark fs-2 text-secondary opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card p-3 h-100 shadow-sm border-0 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold">In Discussion</div>
                        <h4 class="fw-bold mb-0 mt-1 text-warning-emphasis"><?= $stats['discussion_enquiries'] ?></h4>
                    </div>
                    <i class="bi bi-chat-dots fs-2 text-warning opacity-50"></i>
                </div>
                <?php $pct = $stats['total_enquiries'] > 0 ? round(($stats['discussion_enquiries'] / $stats['total_enquiries']) * 100) : 0; ?>
                <div class="small text-muted mt-2"><?= $pct ?>% of total</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card p-3 h-100 shadow-sm border-0 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold">Quoted</div>
                        <h4 class="fw-bold mb-0 mt-1 text-primary"><?= $stats['quoted_enquiries'] ?></h4>
                    </div>
                    <i class="bi bi-file-earmark-text fs-2 text-primary opacity-50"></i>
                </div>
                <?php $pct = $stats['total_enquiries'] > 0 ? round(($stats['quoted_enquiries'] / $stats['total_enquiries']) * 100) : 0; ?>
                <div class="small text-muted mt-2"><?= $pct ?>% of total</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card p-3 h-100 shadow-sm border-0 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold">Confirmed / Won</div>
                        <h4 class="fw-bold mb-0 mt-1 text-success"><?= $stats['confirmed_enquiries'] ?></h4>
                    </div>
                    <i class="bi bi-check-circle fs-2 text-success opacity-50"></i>
                </div>
                <?php $pct = $stats['total_enquiries'] > 0 ? round(($stats['confirmed_enquiries'] / $stats['total_enquiries']) * 100) : 0; ?>
                <div class="small text-muted mt-2"><?= $pct ?>% of total</div>
            </div>
        </div>
    </div>

    <!-- Main Data Rows -->
    <div class="row g-4 mb-4">
        
        <!-- Recent Enquiries -->
        <div class="col-12">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Enquiries</h6>
                    <a href="<?= url('admin/enquiries/index.php') ?>" class="btn btn-sm btn-outline-primary py-0">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID #</th>
                                <th>Customer</th>
                                <th>Package</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentEnquiries)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No enquiries found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentEnquiries as $row): ?>
                                    <tr>
                                        <td class="fw-bold font-monospace small text-primary">
                                            <a href="<?= url('admin/enquiries/view.php?id=' . $row['id']) ?>" class="text-decoration-none">
                                                #<?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($row['customer_email'], ENT_QUOTES, 'UTF-8') ?></div>
                                        </td>
                                        <td>
                                            <?php if ($row['package_name']): ?>
                                                <div class="text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($row['package_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($row['package_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">General Enquiry</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small"><?= format_date($row['created_at']) ?></td>
                                        <td><?= get_status_badge($row['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Analytical Rows -->
    <div class="row g-4 mb-4">
        
        <!-- Most Enquired Packages -->
        <div class="col-12 col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Most Enquired Packages</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Package</th>
                                <th class="text-center">Enquiries</th>
                                <th class="text-end">Won</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($popularPackages)): ?>
                                <tr><td colspan="3" class="text-center py-3 text-muted">No data.</td></tr>
                            <?php else: ?>
                                <?php foreach ($popularPackages as $pp): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark text-truncate" style="max-width: 140px;" title="<?= htmlspecialchars($pp['package_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($pp['package_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div class="small text-muted"><?= htmlspecialchars($pp['destination_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                        </td>
                                        <td class="text-center fw-bold text-muted"><?= $pp['total_enquiries'] ?></td>
                                        <td class="text-end fw-bold text-success small"><?= $pp['confirmed_enquiries'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Destination Performance -->
        <div class="col-12 col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-pin-map me-2 text-danger"></i>Destination Performance</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Destination</th>
                                <th class="text-center">Enquiries</th>
                                <th class="text-end">Won</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($destinationPerformance)): ?>
                                <tr><td colspan="3" class="text-center py-3 text-muted">No data.</td></tr>
                            <?php else: ?>
                                <?php foreach ($destinationPerformance as $dp): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($dp['destination_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="small text-muted"><?= $dp['package_count'] ?> Packages</div>
                                        </td>
                                        <td class="text-center fw-bold text-muted"><?= $dp['total_enquiries'] ?></td>
                                        <td class="text-end fw-bold text-success small"><?= $dp['confirmed_enquiries'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Package Availability Overview -->
        <div class="col-12 col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart-fill me-2 text-success"></i>Package Availability</h6>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($packageAvailability)): ?>
                        <div class="text-center py-4 text-muted">No package data available.</div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($packageAvailability as $pkg): ?>
                                <?php
                                $capacity = (int)$pkg['maximum_capacity'];
                                $booked = (int)$pkg['booked_count'];
                                $available = max(0, $capacity - $booked);
                                $percent = $capacity > 0 ? min(100, round(($booked / $capacity) * 100)) : 0;

                                $barColor = 'bg-success';
                                $statusLabel = 'Available';
                                $statusClass = 'text-success';

                                if ($percent >= 100) {
                                    $barColor = 'bg-danger';
                                    $statusLabel = 'Sold Out';
                                    $statusClass = 'text-danger';
                                } elseif ($percent >= 80) {
                                    $barColor = 'bg-warning';
                                    $statusLabel = 'Limited';
                                    $statusClass = 'text-warning-emphasis';
                                }
                                ?>
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="fw-semibold small text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($pkg['package_name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($pkg['package_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="small font-monospace text-muted">
                                            <strong><?= $booked ?></strong>/<?= $capacity ?>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar <?= $barColor ?>" role="progressbar" style="width: <?= $percent ?>%;" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1" style="font-size: 0.7rem;">
                                        <span class="fw-bold <?= $statusClass ?>"><?= $statusLabel ?></span>
                                        <span class="text-muted"><?= $available ?> left</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
