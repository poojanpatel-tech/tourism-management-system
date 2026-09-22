<?php
/**
 * Create Tour Package
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

// Fetch active destinations for dropdown
try {
    $destinations = $pdo->query("SELECT destination_id, destination_name, country FROM destinations WHERE status = 'active' ORDER BY destination_name ASC")->fetchAll();
} catch (PDOException $e) {
    $destinations = [];
}

$errors = [];
$packageName = '';
$packageCode = '';
$destinationId = '';
$duration = '';
$price = '';
$maxCapacity = '';
$travelDate = '';
$description = '';
$includedServices = '';
$excludedServices = '';
$image = '';
$status = 'active';

// Auto-generate package code suggestion
function generatePackageCode(PDO $pdo): string
{
    $prefix = 'PKG' . date('Ymd');
    $stmtMax = $pdo->prepare("SELECT package_code FROM packages WHERE package_code LIKE :prefix ORDER BY package_code DESC LIMIT 1");
    $stmtMax->execute([':prefix' => $prefix . '%']);
    $lastCode = $stmtMax->fetchColumn();

    if ($lastCode) {
        $lastNum = (int)substr($lastCode, strlen($prefix));
        $nextNum = $lastNum + 1;
    } else {
        $nextNum = 1;
    }

    return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
}

$suggestedCode = generatePackageCode($pdo);

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
    $image = trim($_POST['image'] ?? '');
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
    } elseif (!ctype_digit($maxCapacity) || (int)$maxCapacity <= 0) {
        $errors[] = 'Maximum Capacity must be a positive integer.';
    }

    if ($travelDate !== '' && !strtotime($travelDate)) {
        $errors[] = 'Travel Date is not a valid date.';
    }

    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    // Validate destination exists and is active
    if (empty($errors) && $destinationId > 0) {
        $stmtDest = $pdo->prepare("SELECT destination_id FROM destinations WHERE destination_id = :id AND status = 'active'");
        $stmtDest->execute([':id' => $destinationId]);
        if (!$stmtDest->fetch()) {
            $errors[] = 'The selected destination is not available or does not exist.';
        }
    }

    // Check for duplicate package code
    if (empty($errors)) {
        try {
            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM packages WHERE package_code = :code');
            $stmtCheck->execute([':code' => $packageCode]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $errors[] = "A package with code \"{$packageCode}\" already exists. Please use a unique code.";
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error checking package code: ' . $e->getMessage();
        }
    }

    // Handle image upload
    $imageFilename = $image; // default: use text field value
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB
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

    // Insert if valid
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO packages (package_code, package_name, destination_id, duration, price,
                                      maximum_capacity, travel_date, description, included_services,
                                      excluded_services, image, status, created_at)
                VALUES (:code, :name, :dest_id, :duration, :price, :capacity, :travel_date,
                        :description, :included, :excluded, :image, :status, NOW())
            ');
            $stmt->execute([
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
            ]);

            set_flash_message('success', "Tour package \"{$packageName}\" ({$packageCode}) created successfully!");
            header('Location: ' . url('admin/packages/index.php'));
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Failed to create package: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add New Tour Package';
$activePage = 'packages';
$navTitle = 'Create Package';

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
                    <li class="breadcrumb-item active" aria-current="page">Add New</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">Add New Tour Package</h3>
        </div>
        <div class="mt-3 mt-md-0">
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
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-box2-heart text-primary me-2"></i>Package Details</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= url('admin/packages/create.php') ?>" method="POST" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="row g-3">
                            <!-- Package Name -->
                            <div class="col-12 col-md-6">
                                <label for="package_name" class="form-label fw-semibold small text-muted">Package Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="package_name" name="package_name"
                                       placeholder="e.g. Bali Tropical Haven & Beach Retreat"
                                       value="<?= htmlspecialchars($packageName, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="150" autofocus>
                            </div>

                            <!-- Package Code -->
                            <div class="col-12 col-md-6">
                                <label for="package_code" class="form-label fw-semibold small text-muted">Package Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace" id="package_code" name="package_code"
                                       placeholder="e.g. PKG20260921001"
                                       value="<?= htmlspecialchars($packageCode !== '' ? $packageCode : $suggestedCode, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="50">
                                <div class="form-text text-muted">Auto-generated. You may modify it. Must be unique.</div>
                            </div>

                            <!-- Destination -->
                            <div class="col-12 col-md-6">
                                <label for="destination_id" class="form-label fw-semibold small text-muted">Destination <span class="text-danger">*</span></label>
                                <select class="form-select" id="destination_id" name="destination_id" required>
                                    <option value="">-- Select Destination --</option>
                                    <?php foreach ($destinations as $d): ?>
                                        <option value="<?= (int)$d['destination_id'] ?>" <?= ($destinationId === (int)$d['destination_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($d['destination_name'] . ' — ' . $d['country'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-muted">Only active destinations are shown.</div>
                            </div>

                            <!-- Duration -->
                            <div class="col-12 col-md-6">
                                <label for="duration" class="form-label fw-semibold small text-muted">Duration <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="duration" name="duration"
                                       placeholder="e.g. 5 Days / 4 Nights"
                                       value="<?= htmlspecialchars($duration, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="50">
                            </div>

                            <!-- Price -->
                            <div class="col-12 col-md-4">
                                <label for="price" class="form-label fw-semibold small text-muted">Price (₹) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">₹</span>
                                    <input type="number" class="form-control" id="price" name="price"
                                           placeholder="0.00" step="0.01" min="0"
                                           value="<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>"
                                           required>
                                </div>
                            </div>

                            <!-- Maximum Capacity -->
                            <div class="col-12 col-md-4">
                                <label for="maximum_capacity" class="form-label fw-semibold small text-muted">Maximum Capacity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="maximum_capacity" name="maximum_capacity"
                                       placeholder="e.g. 20" min="1"
                                       value="<?= htmlspecialchars($maxCapacity, ENT_QUOTES, 'UTF-8') ?>"
                                       required>
                                <div class="form-text text-muted">Max travelers allowed per trip.</div>
                            </div>

                            <!-- Travel Date -->
                            <div class="col-12 col-md-4">
                                <label for="travel_date" class="form-label fw-semibold small text-muted">Travel Date</label>
                                <input type="date" class="form-control" id="travel_date" name="travel_date"
                                       value="<?= htmlspecialchars($travelDate, ENT_QUOTES, 'UTF-8') ?>">
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label for="description" class="form-label fw-semibold small text-muted">Description (Optional)</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                          placeholder="Brief, engaging overview of the tour experience..."><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <!-- Included Services -->
                            <div class="col-12 col-md-6">
                                <label for="included_services" class="form-label fw-semibold small text-muted">Included Services</label>
                                <textarea class="form-control" id="included_services" name="included_services" rows="3"
                                          placeholder="e.g. Hotel Stay, Breakfast, Airport Transfers, Guided Tours"><?= htmlspecialchars($includedServices, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <!-- Excluded Services -->
                            <div class="col-12 col-md-6">
                                <label for="excluded_services" class="form-label fw-semibold small text-muted">Excluded Services</label>
                                <textarea class="form-control" id="excluded_services" name="excluded_services" rows="3"
                                          placeholder="e.g. International Flights, Travel Insurance, Personal Expenses"><?= htmlspecialchars($excludedServices, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <!-- Image Upload -->
                            <div class="col-12 col-md-6">
                                <label for="image_file" class="form-label fw-semibold small text-muted">Package Image (Optional)</label>
                                <input type="file" class="form-control" id="image_file" name="image_file" accept="image/jpeg,image/png,image/gif,image/webp">
                                <div class="form-text text-muted">Max 2MB. JPEG, PNG, GIF, or WebP.</div>
                            </div>

                            <!-- Image Filename (fallback) -->
                            <div class="col-12 col-md-6">
                                <label for="image" class="form-label fw-semibold small text-muted">Or Image Filename</label>
                                <input type="text" class="form-control" id="image" name="image"
                                       placeholder="e.g. bali.jpg (existing file in assets/images)"
                                       value="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>">
                                <div class="form-text text-muted">Use if image already exists in assets/images/.</div>
                            </div>

                            <!-- Status -->
                            <div class="col-12 col-md-6">
                                <label for="status" class="form-label fw-semibold small text-muted">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Active (Available for Bookings)</option>
                                    <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Inactive (Hidden from bookings)</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= url('admin/packages/index.php') ?>" class="btn btn-light border px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Save Package
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
