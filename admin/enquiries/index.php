<?php
/**
 * Manage Enquiries (CRM)
 * Tourism Management System
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

// Fetch enquiries
$stmt = $pdo->query("
    SELECT e.*, p.package_name, p.plan_type, d.destination_name, d.country
    FROM enquiries e
    LEFT JOIN packages p ON e.package_id = p.package_id
    LEFT JOIN destinations d ON e.destination_id = d.destination_id
    ORDER BY e.created_at DESC
");
$enquiries = $stmt->fetchAll();

$pageTitle = 'Enquiries Management';
$activePage = 'enquiries';
$navTitle = 'Enquiries';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-0 text-dark">Journey Enquiries</h3>
            <p class="text-muted mb-0">Manage customer journey requests</p>
        </div>
    </div>

    <?php display_flash_message(); ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Customer</th>
                            <th>Journey</th>
                            <th>Date Received</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enquiries)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-4 text-secondary opacity-25 d-block mb-3"></i>
                                    No enquiries found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($enquiries as $enq): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="text-muted small">#<?= $enq['id'] ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($enq['name']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($enq['email']) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($enq['package_name'])): ?>
                                            <div class="fw-medium text-primary"><?= htmlspecialchars($enq['package_name']) ?></div>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars($enq['destination_name']) ?>, <?= htmlspecialchars($enq['country']) ?> 
                                                <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($enq['plan_type']) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="fw-medium"><?= htmlspecialchars($enq['subject']) ?></div>
                                            <div class="small text-muted">General Enquiry</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= date('M d, Y', strtotime($enq['created_at'])) ?><br>
                                        <span class="small text-muted"><?= date('h:i A', strtotime($enq['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = 'bg-secondary';
                                        switch ($enq['status']) {
                                            case 'new': $badgeClass = 'bg-info text-dark'; break;
                                            case 'contacted':
                                            case 'read': $badgeClass = 'bg-primary'; break;
                                            case 'in_discussion':
                                            case 'replied': $badgeClass = 'bg-warning text-dark'; break;
                                            case 'quoted': $badgeClass = 'bg-success'; break;
                                            case 'confirmed': $badgeClass = 'bg-success bg-gradient shadow-sm'; break;
                                            case 'closed':
                                            case 'cancelled': $badgeClass = 'bg-danger'; break;
                                        }
                                        // Standardize display status
                                        $displayStatus = ucfirst(str_replace('_', ' ', $enq['status']));
                                        if ($enq['status'] === 'read') $displayStatus = 'Contacted';
                                        if ($enq['status'] === 'replied') $displayStatus = 'In Discussion';
                                        ?>
                                        <span class="badge <?= $badgeClass ?> rounded-pill px-3"><?= $displayStatus ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="<?= url('admin/enquiries/view.php?id=' . $enq['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
