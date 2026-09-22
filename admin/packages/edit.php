<?php
/**
 * Edit Tour Package
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

// Validate ID safely
$id = (int)($_GET['id'] ?? $_POST['package_id'] ?? 0);

if ($id <= 0) {
    set_flash_message('danger', 'Invalid package ID specified.');
    header('Location: ' . url('admin/packages/index.php'));
    exit;
}

// Fetch existing package
try {
    $stmt = $pdo->prepare('SELECT * FROM packages WHERE package_id = :id');
    $stmt->execute([':id' => $id]);
    $package = $stmt->fetch();

    if (!$package) {
        set_flash_message('danger', 'The requested tour package does not exist.');
        header('Location: ' . url('admin/packages/index.php'));
        exit;
    }
} catch (PDOException $e) {
    set_flash_message('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . url('admin/packages/index.php'));
    exit;
}

// Fetch destinations for dropdown (active + the package's current destination even if inactive)
try {
    $stmtDest = $pdo->prepare("
        SELECT destination_id, destination_name, country, status
        FROM destinations
        WHERE status = 'active' OR destination_id = :current_dest
        ORDER BY destination_name ASC
    ");
    $stmtDest->execute([':current_dest' => (int)$package['destination_id']]);
    $destinations = $stmtDest->fetchAll();
} catch (PDOException $e) {
    $destinations = [];
}

$errors = [];
$packageName = $package['package_name'];
$packageCode = $package['package_code'];
$destinationId = (int)$package['destination_id'];
$duration = $package['duration'];
$price = $package['price'];
$maxCapacity = $package['maximum_capacity'];
$travelDate = $package['travel_date'] ?? '';
$description = $package['description'] ?? '';
$includedServices = $package['included_services'] ?? '';
$excludedServices = $package['excluded_services'] ?? '';
$image = $package['image'] ?? '';
$status = $package['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $packageName = trim($_POST['package_name'] ?? '');
    $packageCode = trim($_POST['package_code'] ?? '');
    $destinationId = (int)($_POST['destination_id'] ?? 0);
    $duration = trim($_POST['duration'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $maxCapacity = trim($_POST['maximum_capacity'] ?? '');
    $travelDate = trim($_POST['travel_date'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $includedServices = trim($_POST['included_services'] ?? '');
    $excludedServices = trim($_POST['excluded_services'] ?? '');
    $image = trim($_POST['image'] ?? $package['image'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $csrf = $_POST['csrf_token'] ?? '';

    // ---- Validation ----
    if (!verify_csrf_token($csrf)) {
        $errors[] = 'Invalid security token. Please try submitting again.';
    }
    if ($packageName === '') {
        $errors[] = 'Package Name is required.';
    } elseif (mb_strlen($packageName) > 150) {
        $errors[] = 'Package Name cannot exceed 150 characters.';
    }

    if ($packageCode === '') {
        $errors[] = 'Package Code is required.';
    } elseif (mb_strlen($packageCode) > 50) {
        $errors[] = 'Package Code cannot exceed 50 characters.';
    }

    if ($destinationId <= 0) {
        $errors[] = 'Please select a valid destination.';
    }

    if ($duration === '') {
        $errors[] = 'Duration is required.';
    } elseif (mb_strlen($duration) > 50) {
        $errors[] = 'Duration cannot exceed 50 characters.';
    }

    if ($price === '') {
        $errors[] = 'Price is required.';
    } elseif (!is_numeric($price) || (float)$price < 0) {
        $errors[] = 'Price must be a valid non-negative number.';
    }

    if ($maxCapacity === '') {
        $errors[] = 'Maximum Capacity is required.';
    } elseif (!ctype_digit((string)$maxCapacity) || (int)$maxCapacity <= 0) {
        $errors[] = 'Maximum Capacity must be a positive integer.';
    }

    if ($travelDate !== '' && !strtotime($travelDate)) {
        $errors[] = 'Travel Date is not a valid date.';
    }

    if (!in_array($status, ['active', 'inactive', 'sold_out'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    // Check for duplicate package code excluding current record
    if (empty($errors)) {
        try {
            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM packages WHERE package_code = :code AND package_id != :id');
            $stmtCheck->execute([':code' => $packageCode, ':id' => $id]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $errors[] = "Another package with code \"{$packageCode}\" already exists.";
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error checking package code: ' . $e->getMessage();
        }
    }

    // Validate destination exists
    if (empty($errors) && $destinationId > 0) {
        $stmtDestCheck = $pdo->prepare("SELECT destination_id FROM destinations WHERE destination_id = :id");
        $stmtDestCheck->execute([':id' => $destinationId]);
        if (!$stmtDestCheck->fetch()) {
            $errors[] = 'The selected destination does not exist.';
        }
    }

    // Handle image upload
    $imageFilename = $image;
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxFileSize = 2 * 1024 * 1024;
        $fileTmp = $_FILES['image_file']['tmp_name'];
        $fileSize = $_FILES['image_file']['size'];
        $fileType = mime_content_type($fileTmp);
        $fileExt = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = 'Image must be JPEG, PNG, GIF, or WebP format.';
        } elseif ($fileSize > $maxFileSize) {
            $errors[] = 'Image file size cannot exceed 2MB.';
        } elseif (!in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $errors[] = 'Invalid image file extension.';
        } else {
            $safeFilename = 'pkg_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
            $uploadDir = __DIR__ . '/../../assets/images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            if (move_uploaded_file($fileTmp, $uploadDir . $safeFilename)) {
                $imageFilename = $safeFilename;
            } else {
                $errors[] = 'Failed to save uploaded image.';
            }
        }
    }

    // Update if valid
    if (empty($errors)) {
        try {
            $stmtUpdate = $pdo->prepare('
                UPDATE packages
                SET package_code     = :code,
                    package_name     = :name,
                    destination_id   = :dest_id,
                    duration         = :duration,
                    price            = :price,
                    maximum_capacity = :capacity,
                    travel_date      = :travel_date,
                    description      = :description,
                    included_services = :included,
                    excluded_services = :excluded,
                    image            = :image,
                    status           = :status,
                    updated_at       = NOW()
                WHERE package_id = :id
            ');
            $stmtUpdate->execute([
                ':code'        => $packageCode,
                ':name'        => $packageName,
                ':dest_id'     => $destinationId,
                ':duration'    => $duration,
                ':price'       => (float)$price,
                ':capacity'    => (int)$maxCapacity,
                ':travel_date' => $travelDate !== '' ? $travelDate : null,
                ':description' => $description !== '' ? $description : null,
                ':included'    => $includedServices !== '' ? $includedServices : null,
                ':excluded'    => $excludedServices !== '' ? $excludedServices : null,
                ':image'       => $imageFilename !== '' ? $imageFilename : null,
                ':status'      => $status,
                ':id'          => $id,
            ]);

            set_flash_message('success', "Tour package \"{$packageName}\" updated successfully!");
            header('Location: ' . url('admin/packages/index.php'));
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Failed to update package: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Package: ' . $package['package_name'];
$activePage = 'packages';
$navTitle = 'Edit Package';

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
                    <li class="breadcrumb-item active" aria-current="page">Edit #<?= (int)$package['package_id'] ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">Edit: <?= htmlspecialchars($package['package_name'], ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= url('admin/packages/view.php?id=' . $id) ?>" class="btn btn-outline-info">
                <i class="bi bi-eye me-1"></i> View Details
            </a>
            <a href="<?= url('admin/packages/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Packages
            </a>
        </div>
    </div>

    <!-- Error Alerts -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                <strong>Please resolve the following issues:</strong>
            </div>
            <ul class="mb-0 ps-4">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Update Package Details</h5>
                    <span class="badge bg-light text-muted border font-monospace">ID: #<?= (int)$id ?></span>
                </div>
                <div class="card-body p-4">
                    <form action="<?= url('admin/packages/edit.php?id=' . $id) ?>" method="POST" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="package_id" value="<?= (int)$id ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                        <div class="row g-3">
                            <!-- Package Name -->
                            <div class="col-12 col-md-6">
                                <label for="package_name" class="form-label fw-semibold small text-muted">Package Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="package_name" name="package_name"
                                       value="<?= htmlspecialchars($packageName, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="150">
                            </div>

                            <!-- Package Code -->
                            <div class="col-12 col-md-6">
                                <label for="package_code" class="form-label fw-semibold small text-muted">Package Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace" id="package_code" name="package_code"
                                       value="<?= htmlspecialchars($packageCode, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="50">
                                <div class="form-text text-muted">Must be unique across all packages.</div>
                            </div>

                            <!-- Destination -->
                            <div class="col-12 col-md-6">
                                <label for="destination_id" class="form-label fw-semibold small text-muted">Destination <span class="text-danger">*</span></label>
                                <select class="form-select" id="destination_id" name="destination_id" required>
                                    <option value="">-- Select Destination --</option>
                                    <?php foreach ($destinations as $d): ?>
                                        <option value="<?= (int)$d['destination_id'] ?>" <?= ($destinationId === (int)$d['destination_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($d['destination_name'] . ' — ' . $d['country'], ENT_QUOTES, 'UTF-8') ?>
                                            <?= ($d['status'] === 'inactive') ? ' (Inactive)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Duration -->
                            <div class="col-12 col-md-6">
                                <label for="duration" class="form-label fw-semibold small text-muted">Duration <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="duration" name="duration"
                                       value="<?= htmlspecialchars($duration, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="50">
                            </div>

                            <!-- Price -->
                            <div class="col-12 col-md-4">
                                <label for="price" class="form-label fw-semibold small text-muted">Price (₹) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">₹</span>
                                    <input type="number" class="form-control" id="price" name="price"
                                           step="0.01" min="0"
                                           value="<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>"
                                           required>
                                </div>
                            </div>

                            <!-- Maximum Capacity -->
                            <div class="col-12 col-md-4">
                                <label for="maximum_capacity" class="form-label fw-semibold small text-muted">Maximum Capacity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="maximum_capacity" name="maximum_capacity"
                                       min="1" value="<?= htmlspecialchars($maxCapacity, ENT_QUOTES, 'UTF-8') ?>"
                                       required>
                            </div>

                            <!-- Travel Date -->
                            <div class="col-12 col-md-4">
                                <label for="travel_date" class="form-label fw-semibold small text-muted">Travel Date</label>
                                <input type="date" class="form-control" id="travel_date" name="travel_date"
                                       value="<?= htmlspecialchars($travelDate, ENT_QUOTES, 'UTF-8') ?>">
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label for="description" class="form-label fw-semibold small text-muted">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <!-- Included Services -->
                            <div class="col-12 col-md-6">
                                <label for="included_services" class="form-label fw-semibold small text-muted">Included Services</label>
                                <textarea class="form-control" id="included_services" name="included_services" rows="3"><?= htmlspecialchars($includedServices, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <!-- Excluded Services -->
                            <div class="col-12 col-md-6">
                                <label for="excluded_services" class="form-label fw-semibold small text-muted">Excluded Services</label>
                                <textarea class="form-control" id="excluded_services" name="excluded_services" rows="3"><?= htmlspecialchars($excludedServices, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <!-- Image Upload -->
                            <div class="col-12 col-md-6">
                                <label for="image_file" class="form-label fw-semibold small text-muted">Replace Image (Optional)</label>
                                <input type="file" class="form-control" id="image_file" name="image_file" accept="image/jpeg,image/png,image/gif,image/webp">
                                <div class="form-text text-muted">Current: <strong><?= htmlspecialchars($image !== '' ? $image : 'No image', ENT_QUOTES, 'UTF-8') ?></strong></div>
                            </div>

                            <!-- Image Filename -->
                            <div class="col-12 col-md-6">
                                <label for="image" class="form-label fw-semibold small text-muted">Or Image Filename</label>
                                <input type="text" class="form-control" id="image" name="image"
                                       value="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>">
                            </div>

                            <!-- Status -->
                            <div class="col-12 col-md-6">
                                <label for="status" class="form-label fw-semibold small text-muted">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                    <option value="sold_out" <?= ($status === 'sold_out') ? 'selected' : '' ?>>Sold Out</option>
                                </select>
                            </div>

                            <!-- Record History -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-muted">Record History</label>
                                <div class="p-2 bg-light border rounded small text-muted">
                                    <div>Created: <strong><?= format_date($package['created_at'], 'M d, Y H:i') ?></strong></div>
                                    <div>Updated: <strong><?= format_date($package['updated_at'] ?? $package['created_at'], 'M d, Y H:i') ?></strong></div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= url('admin/packages/index.php') ?>" class="btn btn-light border px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Update Package
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
