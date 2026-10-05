<?php
/**
 * Manage Package Inclusions & Exclusions
 * Tourism Management System
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash_message('danger', 'Invalid package ID.');
    header('Location: ' . url('admin/packages/index.php'));
    exit;
}

// Fetch existing package
$stmt = $pdo->prepare('SELECT * FROM packages WHERE package_id = :id');
$stmt->execute([':id' => $id]);
$package = $stmt->fetch();

if (!$package) {
    set_flash_message('danger', 'Tour package not found.');
    header('Location: ' . url('admin/packages/index.php'));
    exit;
}

// Handle Add / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        set_flash_message('danger', 'Invalid security token.');
    } else {
        if ($action === 'add') {
            $type = $_POST['type'];
            $title = trim($_POST['title']);
            
            if (empty($title) || !in_array($type, ['included', 'excluded'])) {
                set_flash_message('danger', 'Valid type and title are required.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO plan_inclusions (package_id, type, title, display_order) VALUES (:pid, :type, :title, 0)");
                $stmt->execute([
                    ':pid' => $id,
                    ':type' => $type,
                    ':title' => $title
                ]);
                set_flash_message('success', 'Item added successfully.');
            }
        } elseif ($action === 'delete') {
            $inclusionId = (int)$_POST['inclusion_id'];
            $stmt = $pdo->prepare("DELETE FROM plan_inclusions WHERE id = :id AND package_id = :pid");
            $stmt->execute([':id' => $inclusionId, ':pid' => $id]);
            set_flash_message('success', 'Item deleted.');
        }
    }
    
    header("Location: " . url("admin/packages/inclusions.php?id=$id"));
    exit;
}

// Fetch Inclusions
$stmt = $pdo->prepare("SELECT * FROM plan_inclusions WHERE package_id = :id ORDER BY type ASC, id ASC");
$stmt->execute([':id' => $id]);
$items = $stmt->fetchAll();

$included = array_filter($items, fn($i) => $i['type'] === 'included');
$excluded = array_filter($items, fn($i) => $i['type'] === 'excluded');

$pageTitle = 'Inclusions: ' . $package['package_name'];
$activePage = 'packages';
$navTitle = 'Manage Inclusions & Exclusions';

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
                    <li class="breadcrumb-item"><a href="<?= url('admin/packages/index.php') ?>" class="text-decoration-none">Tour Packages</a></li>
                    <li class="breadcrumb-item"><a href="<?= url("admin/packages/edit.php?id=$id") ?>" class="text-decoration-none">Edit #<?= $id ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Inclusions</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">Inclusions & Exclusions</h3>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= url('admin/packages/edit.php?id=' . $id) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Edit
            </a>
        </div>
    </div>

    <!-- Management Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3 d-flex flex-wrap gap-2">
                    <a href="<?= url('admin/packages/edit.php?id=' . $id) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-info-circle me-1"></i> Basic Details
                    </a>
                    <a href="<?= url('admin/packages/itinerary.php?id=' . $id) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-map me-1"></i> Itinerary Builder
                    </a>
                    <a href="<?= url('admin/packages/inclusions.php?id=' . $id) ?>" class="btn btn-primary">
                        <i class="bi bi-list-check me-1"></i> Inclusions / Exclusions
                    </a>
                    <a href="<?= url('admin/packages/highlights.php?id=' . $id) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-star me-1"></i> Highlights
                    </a>
                    <a href="<?= url('admin/packages/gallery.php?id=' . $id) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-images me-1"></i> Image Gallery
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php display_flash_message(); ?>

    <div class="row">
        <!-- Add New Item Form -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Item</h6>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="included">Included (✓)</option>
                                <option value="excluded">Excluded (×)</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Item Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Daily Breakfast" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add Item</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lists -->
        <div class="col-lg-8">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-success border-bottom border-3">
                            <h6 class="fw-bold mb-0"><i class="bi bi-check-circle-fill me-2 text-success"></i>Included Services</h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($included as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <span><?= htmlspecialchars($item['title']) ?></span>
                                        <form action="" method="POST" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="inclusion_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (empty($included)): ?>
                                    <li class="list-group-item text-muted text-center py-4">No inclusions added.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-danger border-bottom border-3">
                            <h6 class="fw-bold mb-0"><i class="bi bi-x-circle-fill me-2 text-danger"></i>Excluded Services</h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($excluded as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <span><?= htmlspecialchars($item['title']) ?></span>
                                        <form action="" method="POST" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="inclusion_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (empty($excluded)): ?>
                                    <li class="list-group-item text-muted text-center py-4">No exclusions added.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
