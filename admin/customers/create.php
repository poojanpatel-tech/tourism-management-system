<?php
/**
 * Create Customer
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$pdo = getDBConnection();

$errors = [];
$fullName = '';
$email = '';
$phone = '';
$gender = '';
$dateOfBirth = '';
$address = '';
$city = '';
$country = '';

// Auto-generate customer code
function generateCustomerCode(PDO $pdo): string
{
    $prefix = 'CUS' . date('Ymd');
    $stmt = $pdo->prepare("SELECT customer_code FROM customers WHERE customer_code LIKE :prefix ORDER BY customer_code DESC LIMIT 1");
    $stmt->execute([':prefix' => $prefix . '%']);
    $lastCode = $stmt->fetchColumn();

    if ($lastCode) {
        $lastNum = (int)substr($lastCode, strlen($prefix));
        $nextNum = $lastNum + 1;
    } else {
        $nextNum = 1;
    }

    return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
}

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

    if ($gender !== '' && !in_array($gender, ['Male', 'Female', 'Other'], true)) {
        $errors[] = 'Invalid gender selected.';
    }
    if ($gender === '') {
        $errors[] = 'Gender is required.';
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

    // Check duplicate email
    if (empty($errors) && $email !== '') {
        try {
            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE email = :email');
            $stmtCheck->execute([':email' => $email]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $errors[] = 'An account with this email already exists. Please use a different email address.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error checking email: ' . $e->getMessage();
        }
    }

    // Insert if valid
    if (empty($errors)) {
        try {
            $customerCode = generateCustomerCode($pdo);

            $stmt = $pdo->prepare('
                INSERT INTO customers (customer_code, full_name, email, phone, gender, date_of_birth, address, city, country, created_at)
                VALUES (:code, :name, :email, :phone, :gender, :dob, :address, :city, :country, NOW())
            ');
            $stmt->execute([
                ':code'    => $customerCode,
                ':name'    => $fullName,
                ':email'   => strtolower($email),
                ':phone'   => $phone,
                ':gender'  => $gender,
                ':dob'     => $dateOfBirth !== '' ? $dateOfBirth : null,
                ':address' => $address !== '' ? $address : null,
                ':city'    => $city !== '' ? $city : null,
                ':country' => $country,
            ]);

            set_flash_message('success', "Customer \"{$fullName}\" ({$customerCode}) registered successfully!");
            header('Location: ' . url('admin/customers/index.php'));
            exit;
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'email')) {
                $errors[] = 'An account with this email already exists.';
            } else {
                $errors[] = 'Failed to register customer: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Register New Customer';
$activePage = 'customers';
$navTitle = 'Add Customer';

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
                    <li class="breadcrumb-item active" aria-current="page">Register</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">Register New Customer</h3>
        </div>
        <div class="mt-3 mt-md-0">
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
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-person-plus-fill text-primary me-2"></i>Customer Information</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= url('admin/customers/create.php') ?>" method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="row g-3">
                            <!-- Full Name -->
                            <div class="col-12 col-md-6">
                                <label for="full_name" class="form-label fw-semibold small text-muted">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                       placeholder="e.g. Rahul Sharma"
                                       value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="100" autofocus>
                            </div>

                            <!-- Email -->
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label fw-semibold small text-muted">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email"
                                       placeholder="e.g. rahul@example.com"
                                       value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="100">
                                <div class="form-text text-muted">Must be unique. Used for contact and booking identification.</div>
                            </div>

                            <!-- Phone -->
                            <div class="col-12 col-md-6">
                                <label for="phone" class="form-label fw-semibold small text-muted">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                       placeholder="e.g. +91 9876543210"
                                       value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="20">
                            </div>

                            <!-- Gender -->
                            <div class="col-12 col-md-6">
                                <label for="gender" class="form-label fw-semibold small text-muted">Gender <span class="text-danger">*</span></label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">-- Select Gender --</option>
                                    <option value="Male" <?= ($gender === 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($gender === 'Female') ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= ($gender === 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <!-- Date of Birth -->
                            <div class="col-12 col-md-6">
                                <label for="date_of_birth" class="form-label fw-semibold small text-muted">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                       value="<?= htmlspecialchars($dateOfBirth, ENT_QUOTES, 'UTF-8') ?>"
                                       max="<?= date('Y-m-d') ?>">
                                <div class="form-text text-muted">Optional. Must not be a future date.</div>
                            </div>

                            <!-- Country -->
                            <div class="col-12 col-md-6">
                                <label for="country" class="form-label fw-semibold small text-muted">Country <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="country" name="country"
                                       placeholder="e.g. India"
                                       value="<?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>"
                                       required maxlength="50">
                            </div>

                            <!-- City -->
                            <div class="col-12 col-md-6">
                                <label for="city" class="form-label fw-semibold small text-muted">City</label>
                                <input type="text" class="form-control" id="city" name="city"
                                       placeholder="e.g. Bengaluru"
                                       value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>"
                                       maxlength="50">
                            </div>

                            <!-- Address -->
                            <div class="col-12 col-md-6">
                                <label for="address" class="form-label fw-semibold small text-muted">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"
                                          placeholder="e.g. 42 MG Road, Indiranagar"><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= url('admin/customers/index.php') ?>" class="btn btn-light border px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Register Customer
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
