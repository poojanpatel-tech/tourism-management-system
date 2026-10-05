<?php
/**
 * Manage Package Gallery
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
            $altText = trim($_POST['alt_text'] ?? '');
            
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxFileSize = 2 * 1024 * 1024;
                $fileTmp = $_FILES['image_file']['tmp_name'];
                $fileSize = $_FILES['image_file']['size'];
                $fileType = mime_content_type($fileTmp);
                $fileExt = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));

                if (!in_array($fileType, $allowedTypes)) {
                    set_flash_message('danger', 'Image must be JPEG, PNG, GIF, or WebP format.');
                } elseif ($fileSize > $maxFileSize) {
                    set_flash_message('danger', 'Image file size cannot exceed 2MB.');
                } else {
                    $safeFilename = 'gal_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                    $uploadDir = __DIR__ . '/../../assets/images/packages/';
                    
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    if (move_uploaded_file($fileTmp, $uploadDir . $safeFilename)) {
                        $stmt = $pdo->prepare("INSERT INTO plan_images (package_id, image_path, alt_text, display_order) VALUES (:pid, :path, :alt, 0)");
                        $stmt->execute([
                            ':pid' => $id,
                            ':path' => $safeFilename,
                            ':alt' => $altText
                        ]);
                        set_flash_message('success', 'Image added successfully.');
                    } else {
                        set_flash_message('danger', 'Failed to save uploaded image.');
                    }
                }
            } else {
                set_flash_message('danger', 'Please select a valid image file to upload.');
            }
        } elseif ($action === 'delete') {
            $imageId = (int)$_POST['image_id'];
            
            // Get filename to delete physical file if needed
            $stmt = $pdo->prepare("SELECT image_path FROM plan_images WHERE id = :id AND package_id = :pid");
            $stmt->execute([':id' => $imageId, ':pid' => $id]);
            $imageRecord = $stmt->fetch();
            
            if ($imageRecord) {
                $filepath = __DIR__ . '/../../assets/images/packages/' . $imageRecord['image_path'];
                if (file_exists($filepath) && is_file($filepath)) {
                    // Only delete if it's not the main package image
                    if ($package['image'] !== $imageRecord['image_path']) {
                        @unlink($filepath);
                    }
                }
                
                $stmt = $pdo->prepare("DELETE FROM plan_images WHERE id = :id AND package_id = :pid");
                $stmt->execute([':id' => $imageId, ':pid' => $id]);
                set_flash_message('success', 'Image deleted.');
            }
        }
    }
    
    header("Location: " . url("admin/packages/gallery.php?id=$id"));
    exit;
}

// Fetch Gallery Images
$stmt = $pdo->prepare("SELECT * FROM plan_images WHERE package_id = :id ORDER BY id ASC");
$stmt->execute([':id' => $id]);
$images = $stmt->fetchAll();

$pageTitle = 'Gallery: ' . $package['package_name'];
$activePage = 'packages';
$navTitle = 'Manage Gallery';

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
                    <li class="breadcrumb-item active" aria-current="page">Gallery</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">Image Gallery</h3>
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
                    <a href="<?= url('admin/packages/inclusions.php?id=' . $id) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-list-check me-1"></i> Inclusions / Exclusions
                    </a>
                    <a href="<?= url('admin/packages/highlights.php?id=' . $id) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-star me-1"></i> Highlights
                    </a>
                    <a href="<?= url('admin/packages/gallery.php?id=' . $id) ?>" class="btn btn-primary">
                        <i class="bi bi-images me-1"></i> Image Gallery
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php display_flash_message(); ?>

    <div class="row">
        <!-- Add New Image Form -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-upload me-2 text-primary"></i>Upload Image</h6>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Select Image <span class="text-danger">*</span></label>
                            <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" required>
                            <div class="form-text">Max size: 2MB.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Caption / Alt Text (Optional)</label>
                            <input type="text" name="alt_text" class="form-control" placeholder="e.g. Sunset view from hotel">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Upload Image</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Gallery Grid -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-images me-2 text-primary"></i>Current Gallery</h6>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($images)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-image display-4 text-secondary opacity-25 d-block mb-3"></i>
                            <p class="mb-0">No images in gallery yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($images as $img): ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="position-relative bg-light rounded overflow-hidden" style="padding-top: 75%;">
                                        <img src="<?= str_starts_with($img['image_path'], 'http') ? $img['image_path'] : url('assets/images/packages/' . htmlspecialchars($img['image_path'])) ?>" 
                                             class="position-absolute top-0 w-100 h-100 object-fit-cover" 
                                             alt="<?= htmlspecialchars($img['alt_text'] ?? '') ?>"
                                             onerror="this.src='https://placehold.co/400x300?text=Image+Missing'">
                                        
                                        <form action="" method="POST" class="position-absolute top-0 end-0 m-2">
                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger shadow-sm py-1 px-2" title="Delete" onclick="return confirm('Delete this image?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php if (!empty($img['alt_text'])): ?>
                                            <div class="position-absolute bottom-0 w-100 bg-dark bg-opacity-75 text-white p-2 small text-truncate">
                                                <?= htmlspecialchars($img['alt_text']) ?>
                                            </div>
                                        <?php endif; ?>
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
