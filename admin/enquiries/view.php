<?php
/**
 * View / Manage Enquiry (CRM)
 * Tourism Management System
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash_message('danger', 'Invalid enquiry ID.');
    header('Location: ' . url('admin/enquiries/index.php'));
    exit;
}

// Fetch enquiry with relations
$stmt = $pdo->prepare("
    SELECT e.*, p.package_name, p.plan_type, p.duration, d.destination_name, d.country
    FROM enquiries e
    LEFT JOIN packages p ON e.package_id = p.package_id
    LEFT JOIN destinations d ON e.destination_id = d.destination_id
    WHERE e.id = :id
");
$stmt->execute([':id' => $id]);
$enquiry = $stmt->fetch();

if (!$enquiry) {
    set_flash_message('danger', 'Enquiry not found.');
    header('Location: ' . url('admin/enquiries/index.php'));
    exit;
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf)) {
        set_flash_message('danger', 'Invalid security token.');
    } else {
        $allowedStatuses = ['new', 'contacted', 'in_discussion', 'quoted', 'confirmed', 'closed', 'cancelled'];
        // map legacy statuses if any
        if ($status === 'read') $status = 'contacted';
        if ($status === 'replied') $status = 'in_discussion';
        
        if (in_array($status, $allowedStatuses)) {
            $stmt = $pdo->prepare("UPDATE enquiries SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $id]);
            set_flash_message('success', 'Enquiry status updated successfully.');
            // Refresh
            header("Location: " . url("admin/enquiries/view.php?id=$id"));
            exit;
        } else {
            set_flash_message('danger', 'Invalid status selected.');
        }
    }
}

$pageTitle = 'Enquiry #' . $enquiry['id'];
$activePage = 'enquiries';
$navTitle = 'View Enquiry';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('admin/dashboard.php') ?>" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('admin/enquiries/index.php') ?>" class="text-decoration-none">Enquiries</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View #<?= $enquiry['id'] ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">Enquiry #<?= $enquiry['id'] ?></h3>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= url('admin/enquiries/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Enquiries
            </a>
        </div>
    </div>

    <?php display_flash_message(); ?>

    <div class="row g-4">
        <!-- CRM Detail Area -->
        <div class="col-lg-8">
            <!-- Customer Details -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-uppercase" style="letter-spacing: 1px;"><i class="bi bi-person-badge text-primary me-2"></i>Customer Details</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase" style="letter-spacing: 1px;">Name</div>
                            <div class="fw-semibold fs-5"><?= htmlspecialchars($enquiry['name']) ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase" style="letter-spacing: 1px;">Email</div>
                            <div><a href="mailto:<?= htmlspecialchars($enquiry['email']) ?>" class="text-decoration-none"><?= htmlspecialchars($enquiry['email']) ?></a></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase" style="letter-spacing: 1px;">Phone</div>
                            <div>
                                <?php if (!empty($enquiry['phone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($enquiry['phone']) ?>" class="text-decoration-none"><?= htmlspecialchars($enquiry['phone']) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">Not provided</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Journey Details -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-uppercase" style="letter-spacing: 1px;"><i class="bi bi-geo-alt text-primary me-2"></i>Journey Information</h6>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($enquiry['package_id'])): ?>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="text-muted small text-uppercase" style="letter-spacing: 1px;">Destination</div>
                                <div class="fw-semibold"><?= htmlspecialchars($enquiry['destination_name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($enquiry['country']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small text-uppercase" style="letter-spacing: 1px;">Travel Plan</div>
                                <div class="fw-bold text-primary"><a href="<?= url('admin/packages/edit.php?id=' . $enquiry['package_id']) ?>" class="text-decoration-none"><?= htmlspecialchars($enquiry['package_name']) ?> <i class="bi bi-box-arrow-up-right small"></i></a></div>
                                <div><span class="badge bg-light text-dark border"><?= htmlspecialchars($enquiry['plan_type']) ?></span> &bull; <span class="text-muted small"><?= htmlspecialchars($enquiry['duration']) ?></span></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small text-uppercase" style="letter-spacing: 1px;">Target Travel Date</div>
                                <div class="fw-semibold">
                                    <?= !empty($enquiry['travel_date']) ? date('F d, Y', strtotime($enquiry['travel_date'])) : 'Flexible / Not specified' ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small text-uppercase" style="letter-spacing: 1px;">Travellers</div>
                                <div class="fw-semibold"><?= (int)$enquiry['travellers'] ?> <i class="bi bi-people ms-1 text-muted"></i></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-muted"><i class="bi bi-info-circle me-1"></i> General enquiry without a specific package attached.</div>
                        <div class="mt-3">
                            <div class="text-muted small text-uppercase" style="letter-spacing: 1px;">Subject</div>
                            <div class="fw-semibold fs-6"><?= htmlspecialchars($enquiry['subject']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Message -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-uppercase" style="letter-spacing: 1px;"><i class="bi bi-chat-text text-primary me-2"></i>Customer Message / Requirements</h6>
                </div>
                <div class="card-body p-4 bg-light">
                    <div class="font-monospace text-dark" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;"><?= htmlspecialchars($enquiry['message']) ?></div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Management Area -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4 position-sticky" style="top: 20px;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-uppercase" style="letter-spacing: 1px;">Enquiry Management</h6>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        
                        <div class="mb-4">
                            <label class="form-label text-muted small text-uppercase" style="letter-spacing: 1px;">Current Status</label>
                            <?php
                            $currStatus = $enquiry['status'];
                            if ($currStatus === 'read') $currStatus = 'contacted';
                            if ($currStatus === 'replied') $currStatus = 'in_discussion';
                            ?>
                            <select name="status" class="form-select border-2 bg-light shadow-none fw-semibold">
                                <option value="new" <?= $currStatus === 'new' ? 'selected' : '' ?>>New</option>
                                <option value="contacted" <?= $currStatus === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                                <option value="in_discussion" <?= $currStatus === 'in_discussion' ? 'selected' : '' ?>>In Discussion</option>
                                <option value="quoted" <?= $currStatus === 'quoted' ? 'selected' : '' ?>>Quoted</option>
                                <option value="confirmed" <?= $currStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed (Won)</option>
                                <option value="closed" <?= $currStatus === 'closed' ? 'selected' : '' ?>>Closed (Lost)</option>
                                <option value="cancelled" <?= $currStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 fw-semibold shadow-sm mb-4">
                            <i class="bi bi-save me-2"></i> Save Changes
                        </button>
                    </form>
                    
                    <hr class="border-secondary opacity-25 my-4">
                    
                    <div class="small">
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted text-uppercase" style="letter-spacing: 1px;">Received On</span>
                            <span class="fw-semibold text-end"><?= date('M d, Y', strtotime($enquiry['created_at'])) ?><br><span class="text-muted"><?= date('h:i A', strtotime($enquiry['created_at'])) ?></span></span>
                        </div>
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted text-uppercase" style="letter-spacing: 1px;">Last Updated</span>
                            <span class="fw-semibold text-end"><?= date('M d, Y', strtotime($enquiry['updated_at'])) ?><br><span class="text-muted"><?= date('h:i A', strtotime($enquiry['updated_at'])) ?></span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
