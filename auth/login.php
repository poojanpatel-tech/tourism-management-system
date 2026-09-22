<?php
/**
 * Administrator Login Page
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// If administrator is already authenticated, redirect straight to dashboard
if (is_logged_in()) {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}

$errorMessage = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $errorMessage = 'Invalid security token. Please try submitting again.';
    } elseif (empty($identifier) || empty($password)) {
        $errorMessage = 'Please enter both your username/email and password.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare('SELECT admin_id, username, password, full_name, email FROM admins WHERE username = :username OR email = :email LIMIT 1');
            $stmt->execute([
                ':username' => $identifier,
                ':email' => $identifier
            ]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Successful authentication
                login_user($admin);
                set_flash_message('success', 'Welcome back, ' . $admin['full_name'] . '!');
                header('Location: ' . url('admin/dashboard.php'));
                exit;
            } else {
                $errorMessage = 'Invalid username/email or password. Please try again.';
            }
        } catch (PDOException $e) {
            $errorMessage = 'Database error during authentication. Please contact support.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PATEL TRAVELS — Admin Login</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: none;
            overflow: hidden;
        }
        .login-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 2rem 2rem 1.5rem;
            text-align: center;
        }
        .login-brand-icon {
            width: 60px;
            height: 60px;
            background: #ccfbf1;
            color: #0f766e;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        .login-body {
            padding: 2rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="login-brand-icon">
            <i class="bi bi-compass-fill"></i>
        </div>
        <h4 class="fw-bold mb-1 text-dark">PATEL TRAVELS</h4>
        <p class="text-muted small mb-0">Tourism Management System &bull; Admin Portal</p>
    </div>

    <div class="login-body">
        <?php render_flash_message(); ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger d-flex align-items-center mb-3 py-2 px-3 small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <form action="<?= url('auth/login.php') ?>" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="mb-3">
                <label for="identifier" class="form-label fw-semibold small text-muted">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input type="text"
                           class="form-control border-start-0 ps-0"
                           id="identifier"
                           name="identifier"
                           value="<?= htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Enter username or email"
                           required
                           autofocus>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="password" class="form-label fw-semibold small text-muted mb-0">Password</label>
                </div>
                <div class="input-group mt-1">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password"
                           class="form-control border-start-0 border-end-0 ps-0"
                           id="password"
                           name="password"
                           placeholder="Enter password"
                           required>
                    <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePassword">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mt-2 shadow-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Dashboard
            </button>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted small mb-0 fw-semibold">Head Office: SBR, Ahmedabad (AHM)</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Password visibility toggler
    const toggleBtn = document.getElementById('togglePassword');
    const pwdInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', function () {
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                pwdInput.type = 'password';
                toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    }
</script>
</body>
</html>
