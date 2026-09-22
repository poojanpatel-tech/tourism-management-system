<?php
/**
 * Edit Customer
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

$id = (int)($_GET['id'] ?? $_POST['customer_id'] ?? 0);

if ($id <= 0) {
    set_flash_message('danger', 'Invalid customer ID specified.');
    header('Location: ' . url('admin/customers/index.php'));
    exit;
}

// Fetch existing customer
try {
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE customer_id = :id');
    $stmt->execute([':id' => $id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        set_flash_message('danger', 'The requested customer does not exist.');
        header('Location: ' . url('admin/customers/index.php'));
        exit;
    }
} catch (PDOException $e) {
    set_flash_message('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . url('admin/customers/index.php'));
    exit;
}

$errors = [];
$fullName = $customer['full_name'];
$email = $customer['email'];
$phone = $customer['phone'];
$gender = $customer['gender'];
$dateOfBirth = $customer['date_of_birth'] ?? '';
$address = $customer['address'] ?? '';
$city = $customer['city'] ?? '';
$country = $customer['country'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';

    // ---- Validation ----
    if (!verify_csrf_token($csrf)) {
        $errors[] = 'Invalid security token. Please try submitting again.';
    }
    if ($fullName === '') {
        $errors[] = 'Full Name is required.';
    } elseif (mb_strlen($fullName) > 100) {
        $errors[] = 'Full Name cannot exceed 100 characters.';
    }

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 100) {
        $errors[] = 'Email cannot exceed 100 characters.';
    }

    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    } elseif (mb_strlen($phone) > 20) {
        $errors[] = 'Phone number cannot exceed 20 characters.';
    } elseif (mb_strlen(preg_replace('/[^0-9]/', '', $phone)) < 7) {
        $errors[] = 'Phone number must contain at least 7 digits.';
    }

    if ($gender === '' || !in_array($gender, ['Male', 'Female', 'Other'], true)) {
        $errors[] = 'Please select a valid gender.';
    }

    if ($country === '') {
        $errors[] = 'Country is required.';
    } elseif (mb_strlen($country) > 50) {
        $errors[] = 'Country cannot exceed 50 characters.';
    }

    if ($dateOfBirth !== '') {
        $dobTimestamp = strtotime($dateOfBirth);
        if (!$dobTimestamp) {
            $errors[] = 'Date of Birth is not a valid date.';
        } elseif ($dobTimestamp > time()) {
            $errors[] = 'Date of Birth cannot be in the future.';
        }
    }

    if (mb_strlen($city) > 50) {
        $errors[] = 'City cannot exceed 50 characters.';
    }

    // Check duplicate email excluding current customer
    if (empty($errors) && $email !== '') {
        try {
            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE email = :email AND customer_id != :id');
            $stmtCheck->execute([':email' => strtolower($email), ':id' => $id]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $errors[] = 'An account with this email already exists. Please use a different email address.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error checking email.';
        }
    }

    // Update if valid
    if (empty($errors)) {
        try {
            $stmtUpdate = $pdo->prepare('
                UPDATE customers
                SET full_name     = :name,
                    email         = :email,
                    phone         = :phone,
                    gender        = :gender,
                    date_of_birth = :dob,
                    address       = :address,
                    city          = :city,
                    country       = :country,
                    updated_at    = NOW()
                WHERE customer_id = :id
            ');
            $stmtUpdate->execute([
                ':name'    => $fullName,
                ':email'   => strtolower($email),
                ':phone'   => $phone,
                ':gender'  => $gender,
                ':dob'     => $dateOfBirth !== '' ? $dateOfBirth : null,
                ':address' => $address !== '' ? $address : null,
                ':city'    => $city !== '' ? $city : null,
                ':country' => $country,
                ':id'      => $id,
            ]);

            set_flash_message('success', "Customer \"{$fullName}\" updated successfully!");
            header('Location: ' . url('admin/customers/index.php'));
            exit;
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'email')) {
                $errors[] = 'An account with this email already exists.';
            } else {
                $errors[] = 'Failed to update customer: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Edit Customer: ' . $customer['full_name'];
$activePage = 'customers';
$navTitle = 'Edit Customer';

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
                    <li class="breadcrumb-item"><a href="<?= url('admin/customers/index.php') ?>" class="text-decoration-none">Customers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit #<?= $id ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">Edit: <?= htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="<?= url('admin/customers/view.php?id=' . $id) ?>" class="btn btn-outline-info">
                <i class="bi bi-eye me-1"></i> View Profile
            </a>
            <a href="<?= url('admin/customers/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Customers
            </a>
        </div>
    </div>

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

    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Update Customer Details</h5>
                    <span class="badge bg-light text-muted border font-monospace"><?= htmlspecialchars($customer['customer_code'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="card-body p-4">
                    <form action="<?= url('admin/customers/edit.php?id=' . $id) ?>" method="POST" autocomplete="off">
                        <input type="hidden" name="customer_id" value="<?= $id ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="full_name" class="form-label fw-semibold small text-muted">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                       value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="100">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label fw-semibold small text-muted">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="100">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="phone" class="form-label fw-semibold small text-muted">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                       value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="20">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="gender" class="form-label fw-semibold small text-muted">Gender <span class="text-danger">*</span></label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">-- Select Gender --</option>
                                    <option value="Male" <?= ($gender === 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($gender === 'Female') ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= ($gender === 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="date_of_birth" class="form-label fw-semibold small text-muted">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                       value="<?= htmlspecialchars($dateOfBirth, ENT_QUOTES, 'UTF-8') ?>"
                                       max="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="country" class="form-label fw-semibold small text-muted">Country <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="country" name="country"
                                       value="<?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="50">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="city" class="form-label fw-semibold small text-muted">City</label>
                                <input type="text" class="form-control" id="city" name="city"
                                       value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>"
                                       maxlength="50">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="address" class="form-label fw-semibold small text-muted">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <!-- Record History -->
                            <div class="col-12">
                                <div class="p-2 bg-light border rounded small text-muted d-flex gap-4">
                                    <div>Created: <strong><?= format_date($customer['created_at'], 'M d, Y H:i') ?></strong></div>
                                    <div>Updated: <strong><?= format_date($customer['updated_at'] ?? $customer['created_at'], 'M d, Y H:i') ?></strong></div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= url('admin/customers/index.php') ?>" class="btn btn-light border px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Update Customer
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
