<?php
/**
 * Edit Destination
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

// Validate ID safely
$id = (int)($_GET['id'] ?? $_POST['destination_id'] ?? 0);

if ($id <= 0) {
    set_flash_message('danger', 'Invalid destination ID specified.');
    header('Location: ' . url('admin/destinations/index.php'));
    exit;
}

// Fetch existing destination
try {
    $stmt = $pdo->prepare('SELECT * FROM destinations WHERE destination_id = :id');
    $stmt->execute([':id' => $id]);
    $destination = $stmt->fetch();

    if (!$destination) {
        set_flash_message('danger', 'The requested destination does not exist.');
        header('Location: ' . url('admin/destinations/index.php'));
        exit;
    }
} catch (PDOException $e) {
    set_flash_message('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . url('admin/destinations/index.php'));
    exit;
}

$errors = [];
$name = $destination['destination_name'];
$country = $destination['country'];
$description = $destination['description'] ?? '';
$status = $destination['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['destination_name'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $csrf = $_POST['csrf_token'] ?? '';

    // Validation
    if (!verify_csrf_token($csrf)) {
        $errors[] = 'Invalid security token. Please try submitting again.';
    }
    if ($name === '') {
        $errors[] = 'Destination Name is required.';
    } elseif (mb_strlen($name) > 100) {
        $errors[] = 'Destination Name cannot exceed 100 characters.';
    }

    if ($country === '') {
        $errors[] = 'Country is required.';
    } elseif (mb_strlen($country) > 100) {
        $errors[] = 'Country cannot exceed 100 characters.';
    }

    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    // Check for duplicate destination excluding current record
    if (empty($errors)) {
        try {
            $stmtCheck = $pdo->prepare('
                SELECT COUNT(*) FROM destinations
                WHERE LOWER(destination_name) = LOWER(:name)
                  AND LOWER(country) = LOWER(:country)
                  AND destination_id != :id
            ');
            $stmtCheck->execute([
                ':name'    => $name,
                ':country' => $country,
                ':id'      => $id,
            ]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $errors[] = "Another destination named \"{$name}\" in \"{$country}\" already exists.";
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error checking duplicates: ' . $e->getMessage();
        }
    }

    // Update record if validation passes
    if (empty($errors)) {
        try {
            $stmtUpdate = $pdo->prepare('
                UPDATE destinations
                SET destination_name = :name,
                    country          = :country,
                    description      = :description,
                    status           = :status,
                    updated_at       = NOW()
                WHERE destination_id = :id
            ');
            $stmtUpdate->execute([
                ':name'        => $name,
                ':country'     => $country,
                ':description' => $description !== '' ? $description : null,
                ':status'      => $status,
                ':id'          => $id,
            ]);

            set_flash_message('success', "Destination \"{$name}\" updated successfully!");
            header('Location: ' . url('admin/destinations/index.php'));
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Failed to update destination: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Destination: ' . $destination['destination_name'];
$activePage = 'destinations';
$navTitle = 'Edit Destination';

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
                    <li class="breadcrumb-item"><a href="<?= url('admin/destinations/index.php') ?>" class="text-decoration-none">Destinations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit #<?= (int)$destination['destination_id'] ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">Edit Destination: <?= htmlspecialchars($destination['destination_name'], ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= url('admin/destinations/view.php?id=' . $id) ?>" class="btn btn-outline-info">
                <i class="bi bi-eye me-1"></i> View Details
            </a>
            <a href="<?= url('admin/destinations/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Destinations
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
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Update Destination Details</h5>
                    <span class="badge bg-light text-muted border font-monospace">ID: #<?= (int)$id ?></span>
                </div>
                <div class="card-body p-4">
                    <form action="<?= url('admin/destinations/edit.php?id=' . $id) ?>" method="POST" autocomplete="off">
                        <input type="hidden" name="destination_id" value="<?= (int)$id ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                        <div class="row g-3">
                            <!-- Destination Name -->
                            <div class="col-12 col-md-6">
                                <label for="destination_name" class="form-label fw-semibold small text-muted">Destination Name <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control"
                                       id="destination_name"
                                       name="destination_name"
                                       value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                                       required
                                       maxlength="100">
                                <div class="form-text text-muted">City, region, or landmark title.</div>
                            </div>

                            <!-- Country -->
                            <div class="col-12 col-md-6">
                                <label for="country" class="form-label fw-semibold small text-muted">Country <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control"
                                       id="country"
                                       name="country"
                                       value="<?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>"
                                       required
                                       maxlength="100">
                                <div class="form-text text-muted">Country where this destination is located.</div>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label for="description" class="form-label fw-semibold small text-muted">Description (Optional)</label>
                                <textarea class="form-control"
                                          id="description"
                                          name="description"
                                          rows="4"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
                                <div class="form-text text-muted">Brief overview shown to tourists and administrators.</div>
                            </div>

                            <!-- Status -->
                            <div class="col-12 col-md-6">
                                <label for="status" class="form-label fw-semibold small text-muted">Operational Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Active (Available for Tour Packages)</option>
                                    <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Inactive (Hidden from booking inquiries)</option>
                                </select>
                            </div>

                            <!-- Timestamps Info -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-muted">Record History</label>
                                <div class="p-2 bg-light border rounded small text-muted">
                                    <div>Created: <strong><?= format_date($destination['created_at'], 'M d, Y H:i') ?></strong></div>
                                    <div>Updated: <strong><?= format_date($destination['updated_at'] ?? $destination['created_at'], 'M d, Y H:i') ?></strong></div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= url('admin/destinations/index.php') ?>" class="btn btn-light border px-4">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Update Destination
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
