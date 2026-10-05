<?php
/**
 * Manage Package Itinerary
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

// Handle Add / Edit / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        set_flash_message('danger', 'Invalid security token.');
    } else {
        if ($action === 'add' || $action === 'edit') {
            $dayNumber = (int)$_POST['day_number'];
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            
            if ($dayNumber <= 0 || empty($title) || empty($description)) {
                set_flash_message('danger', 'Day number, title, and description are required.');
            } else {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO plan_itinerary (package_id, day_number, title, description, display_order) VALUES (:pid, :dn, :title, :desc, :do)");
                    $stmt->execute([
                        ':pid' => $id,
                        ':dn' => $dayNumber,
                        ':title' => $title,
                        ':desc' => $description,
                        ':do' => $dayNumber
                    ]);
                    set_flash_message('success', 'Itinerary day added successfully.');
                } else {
                    $itineraryId = (int)$_POST['itinerary_id'];
                    $stmt = $pdo->prepare("UPDATE plan_itinerary SET day_number = :dn, title = :title, description = :desc, display_order = :do WHERE id = :id AND package_id = :pid");
                    $stmt->execute([
                        ':dn' => $dayNumber,
                        ':title' => $title,
                        ':desc' => $description,
                        ':do' => $dayNumber,
                        ':id' => $itineraryId,
                        ':pid' => $id
                    ]);
                    set_flash_message('success', 'Itinerary day updated successfully.');
                }
            }
        } elseif ($action === 'delete') {
            $itineraryId = (int)$_POST['itinerary_id'];
            $stmt = $pdo->prepare("DELETE FROM plan_itinerary WHERE id = :id AND package_id = :pid");
            $stmt->execute([':id' => $itineraryId, ':pid' => $id]);
            set_flash_message('success', 'Itinerary day deleted.');
        }
    }
    
    header("Location: " . url("admin/packages/itinerary.php?id=$id"));
    exit;
}

// Fetch Itinerary
$stmt = $pdo->prepare("SELECT * FROM plan_itinerary WHERE package_id = :id ORDER BY day_number ASC");
$stmt->execute([':id' => $id]);
$itinerary = $stmt->fetchAll();

$pageTitle = 'Itinerary: ' . $package['package_name'];
$activePage = 'packages';
$navTitle = 'Manage Itinerary';

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
                    <li class="breadcrumb-item"><a href="<?= url('admin/packages/index.php') ?>" class="text-decoration-none">Tour Packages</a></li>
                    <li class="breadcrumb-item"><a href="<?= url("admin/packages/edit.php?id=$id") ?>" class="text-decoration-none">Edit #<?= $id ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Itinerary</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">Itinerary: <?= htmlspecialchars($package['package_name']) ?></h3>
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
                    <a href="<?= url('admin/packages/itinerary.php?id=' . $id) ?>" class="btn btn-primary">
                        <i class="bi bi-map me-1"></i> Itinerary Builder
                    </a>
                    <a href="<?= url('admin/packages/inclusions.php?id=' . $id) ?>" class="btn btn-outline-secondary">
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
        <!-- Add New Day Form -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Day</h6>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Day Number <span class="text-danger">*</span></label>
                            <input type="number" name="day_number" class="form-control" min="1" value="<?= count($itinerary) + 1 ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Arrival in Dubai" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="5" placeholder="Detailed itinerary for this day..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add Itinerary Day</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Existing Days List -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-list-ol me-2 text-primary"></i>Itinerary Days</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($itinerary)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-map display-4 text-secondary opacity-50 mb-3 d-block"></i>
                            <p class="mb-0">No itinerary days added yet. Use the form to add the first day.</p>
                        </div>
                    <?php else: ?>
                        <div class="accordion accordion-flush" id="itineraryAccordion">
                            <?php foreach ($itinerary as $index => $day): ?>
                                <div class="accordion-item border-bottom">
                                    <h2 class="accordion-header" id="heading<?= $day['id'] ?>">
                                        <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?> bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $day['id'] ?>">
                                            Day <?= $day['day_number'] ?>: <?= htmlspecialchars($day['title']) ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $day['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#itineraryAccordion">
                                        <div class="accordion-body p-4">
                                            <form action="" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="itinerary_id" value="<?= $day['id'] ?>">
                                                
                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-3">
                                                        <label class="form-label small fw-semibold">Day Number</label>
                                                        <input type="number" name="day_number" class="form-control" value="<?= $day['day_number'] ?>" required>
                                                    </div>
                                                    <div class="col-md-9">
                                                        <label class="form-label small fw-semibold">Title</label>
                                                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($day['title']) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Description</label>
                                                    <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($day['description']) ?></textarea>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                                                    <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('delete-form-<?= $day['id'] ?>').submit();">Delete Day</button>
                                                </div>
                                            </form>
                                            
                                            <form id="delete-form-<?= $day['id'] ?>" action="" method="POST" style="display: none;">
                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="itinerary_id" value="<?= $day['id'] ?>">
                                            </form>
                                        </div>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
