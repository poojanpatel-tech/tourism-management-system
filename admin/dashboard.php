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
    
    // Customers
    $stats['total_customers'] = (int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
    
    // Reservations
    $resStats = $pdo->query('
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) AS confirmed,
            SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN status != "cancelled" THEN number_of_travelers ELSE 0 END) AS travelers,
            SUM(CASE WHEN status = "confirmed" THEN total_amount ELSE 0 END) AS revenue
        FROM reservations
    ')->fetch(PDO::FETCH_ASSOC);

    $stats['total_reservations'] = (int)($resStats['total'] ?? 0);
    $stats['pending_reservations'] = (int)($resStats['pending'] ?? 0);
    $stats['confirmed_reservations'] = (int)($resStats['confirmed'] ?? 0);
    $stats['cancelled_reservations'] = (int)($resStats['cancelled'] ?? 0);
    $stats['total_travelers'] = (int)($resStats['travelers'] ?? 0);
    $stats['total_revenue'] = (float)($resStats['revenue'] ?? 0);

    // Recent Reservations (Latest 10)
    $recentReservations = $pdo->query('
        SELECT r.reservation_id, r.booking_number, r.travel_date, r.number_of_travelers, r.total_amount, r.reservation_date, r.status,
               c.full_name AS customer_name, c.email AS customer_email,
               p.package_name, p.package_code
        FROM reservations r
        INNER JOIN customers c ON r.customer_id = c.customer_id
        INNER JOIN packages p ON r.package_id = p.package_id
        ORDER BY r.reservation_date DESC
        LIMIT 10
    ')->fetchAll();

    // Upcoming Trips
    $upcomingTrips = $pdo->query('
        SELECT r.reservation_id, r.booking_number, r.travel_date, r.number_of_travelers, r.status,
               c.full_name AS customer_name,
               p.package_name
        FROM reservations r
        INNER JOIN customers c ON r.customer_id = c.customer_id
        INNER JOIN packages p ON r.package_id = p.package_id
        WHERE r.travel_date >= CURDATE() AND r.status != "cancelled"
        ORDER BY r.travel_date ASC
        LIMIT 6
    ')->fetchAll();

    // Most Booked Packages
    $popularPackages = $pdo->query('
        SELECT p.package_name, d.destination_name,
               COUNT(r.reservation_id) AS total_bookings,
               COALESCE(SUM(CASE WHEN r.status != "cancelled" THEN r.number_of_travelers ELSE 0 END), 0) AS total_travelers,
               COALESCE(SUM(CASE WHEN r.status = "confirmed" THEN r.total_amount ELSE 0 END), 0) AS confirmed_revenue
        FROM packages p
        INNER JOIN destinations d ON p.destination_id = d.destination_id
        LEFT JOIN reservations r ON p.package_id = r.package_id
        GROUP BY p.package_id, p.package_name, d.destination_name
        ORDER BY total_bookings DESC, confirmed_revenue DESC
        LIMIT 5
    ')->fetchAll();

    // Destination Performance
    $destinationPerformance = $pdo->query('
        SELECT d.destination_name,
               COUNT(DISTINCT p.package_id) AS package_count,
               COUNT(r.reservation_id) AS total_bookings,
               COALESCE(SUM(CASE WHEN r.status != "cancelled" THEN r.number_of_travelers ELSE 0 END), 0) AS total_travelers,
               COALESCE(SUM(CASE WHEN r.status = "confirmed" THEN r.total_amount ELSE 0 END), 0) AS confirmed_revenue
        FROM destinations d
        LEFT JOIN packages p ON d.destination_id = p.destination_id
        LEFT JOIN reservations r ON p.package_id = r.package_id
        GROUP BY d.destination_id, d.destination_name
        ORDER BY confirmed_revenue DESC, total_bookings DESC
        LIMIT 5
    ')->fetchAll();

    // Package Availability (Same accurate logic)
    $packageAvailability = $pdo->query('
        SELECT p.package_id, p.package_code, p.package_name, p.maximum_capacity, p.price, p.status,
               COALESCE(SUM(CASE WHEN r.status IN ("confirmed", "pending") THEN r.number_of_travelers ELSE 0 END), 0) AS booked_count
        FROM packages p
        LEFT JOIN reservations r ON p.package_id = r.package_id
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
            <a href="<?= url('admin/reservations/create.php') ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-calendar-plus me-1"></i> New Booking
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
                <div class="text-muted small fw-semibold text-uppercase mb-1">Customers</div>
                <h3 class="fw-bold mb-0 text-dark"><?= $stats['total_customers'] ?></h3>
                <div class="small text-muted mt-1"><i class="bi bi-people me-1"></i>Registered</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card stat-card p-3 h-100 shadow-sm border-0">
                <div class="text-muted small fw-semibold text-uppercase mb-1">Travelers</div>
                <h3 class="fw-bold mb-0 text-primary"><?= $stats['total_travelers'] ?></h3>
                <div class="small text-muted mt-1"><i class="bi bi-person-walking me-1"></i>Active bookings</div>
            </div>
        </div>
    </div>

    <!-- Reservation Status Summary -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card p-3 h-100 shadow-sm border-0 border-start border-4 border-secondary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold">Total Reservations</div>
                        <h4 class="fw-bold mb-0 mt-1"><?= $stats['total_reservations'] ?></h4>
                    </div>
                    <i class="bi bi-journal-bookmark fs-2 text-secondary opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card p-3 h-100 shadow-sm border-0 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold">Pending</div>
                        <h4 class="fw-bold mb-0 mt-1 text-warning-emphasis"><?= $stats['pending_reservations'] ?></h4>
                    </div>
                    <i class="bi bi-hourglass-split fs-2 text-warning opacity-50"></i>
                </div>
                <?php $pct = $stats['total_reservations'] > 0 ? round(($stats['pending_reservations'] / $stats['total_reservations']) * 100) : 0; ?>
                <div class="small text-muted mt-2"><?= $pct ?>% of total</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card p-3 h-100 shadow-sm border-0 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold">Confirmed</div>
                        <h4 class="fw-bold mb-0 mt-1 text-success"><?= $stats['confirmed_reservations'] ?></h4>
                    </div>
                    <i class="bi bi-check-circle fs-2 text-success opacity-50"></i>
                </div>
                <?php $pct = $stats['total_reservations'] > 0 ? round(($stats['confirmed_reservations'] / $stats['total_reservations']) * 100) : 0; ?>
                <div class="small text-muted mt-2"><?= $pct ?>% of total</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card p-3 h-100 shadow-sm border-0 border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-bold">Cancelled</div>
                        <h4 class="fw-bold mb-0 mt-1 text-danger"><?= $stats['cancelled_reservations'] ?></h4>
                    </div>
                    <i class="bi bi-x-circle fs-2 text-danger opacity-50"></i>
                </div>
                <?php $pct = $stats['total_reservations'] > 0 ? round(($stats['cancelled_reservations'] / $stats['total_reservations']) * 100) : 0; ?>
                <div class="small text-muted mt-2"><?= $pct ?>% of total</div>
            </div>
        </div>
    </div>

    <!-- Main Data Rows -->
    <div class="row g-4 mb-4">
        
        <!-- Recent Reservations -->
        <div class="col-12 col-xl-8">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Reservations</h6>
                    <a href="<?= url('admin/reservations/index.php') ?>" class="btn btn-sm btn-outline-primary py-0">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Booking #</th>
                                <th>Customer</th>
                                <th>Package</th>
                                <th>Date</th>
                                <th class="text-center">Pax</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentReservations)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No reservations found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentReservations as $row): ?>
                                    <tr>
                                        <td class="fw-bold font-monospace small text-primary">
                                            <a href="<?= url('admin/reservations/view.php?id=' . $row['reservation_id']) ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($row['booking_number'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($row['package_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($row['package_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </td>
                                        <td class="small"><?= format_date($row['travel_date']) ?></td>
                                        <td class="text-center fw-semibold"><?= (int)$row['number_of_travelers'] ?></td>
                                        <td class="fw-bold text-dark"><?= format_currency($row['total_amount']) ?></td>
                                        <td><?= get_status_badge($row['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Upcoming Trips -->
        <div class="col-12 col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-calendar-event me-2 text-info"></i>Upcoming Trips</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($upcomingTrips)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                            No upcoming trips scheduled.
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($upcomingTrips as $trip): ?>
                                <a href="<?= url('admin/reservations/view.php?id=' . $trip['reservation_id']) ?>" class="list-group-item list-group-item-action p-3">
                                    <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 fw-bold text-dark text-truncate" style="max-width: 70%;"><?= htmlspecialchars($trip['package_name'], ENT_QUOTES, 'UTF-8') ?></h6>
                                        <small class="text-primary fw-bold font-monospace"><?= format_date($trip['travel_date'], 'M d, Y') ?></small>
                                    </div>
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($trip['customer_name'], ENT_QUOTES, 'UTF-8') ?></small>
                                        <span class="badge bg-light text-dark border"><i class="bi bi-people-fill me-1"></i><?= $trip['number_of_travelers'] ?> Pax</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Analytical Rows -->
    <div class="row g-4 mb-4">
        
        <!-- Most Booked Packages -->
        <div class="col-12 col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Most Booked Packages</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Package</th>
                                <th class="text-center">Pax</th>
                                <th class="text-end">Revenue</th>
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
                                        <td class="text-center fw-bold text-muted"><?= $pp['total_travelers'] ?></td>
                                        <td class="text-end fw-bold text-success small"><?= format_currency($pp['confirmed_revenue']) ?></td>
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
                                <th class="text-center">Bookings</th>
                                <th class="text-end">Revenue</th>
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
                                        <td class="text-center fw-bold text-muted"><?= $dp['total_bookings'] ?></td>
                                        <td class="text-end fw-bold text-success small"><?= format_currency($dp['confirmed_revenue']) ?></td>
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
