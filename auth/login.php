<?php
/**
 * Administrator & Management Login Page — Patel Travels
 * Ultra-Luxury Management Portal
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
                $errorMessage = 'Invalid credentials. Please verify your username and password.';
            }
        } catch (PDOException $e) {
            $errorMessage = 'Database error during authentication. Please contact system administrator.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PATEL TRAVELS — Management Portal</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --color-midnight: #10110F;
            --color-charcoal: #191A17;
            --color-gold: #C6A76A;
            --color-gold-hover: #D8BA7D;
            --color-ivory: #F5F0E7;
            --font-heading: 'Cormorant Garamond', Georgia, serif;
            --font-body: 'Manrope', sans-serif;
        }
        body {
            font-family: var(--font-body);
            background-color: var(--color-midnight);
            background-image: radial-gradient(circle at 50% 30%, rgba(198, 167, 106, 0.08) 0%, rgba(16, 17, 15, 0.98) 70%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: var(--color-ivory);
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--color-charcoal);
            border: 1px solid rgba(198, 167, 106, 0.25);
            border-radius: 8px;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.7);
            overflow: hidden;
        }
        .login-header {
            padding: 2.8rem 2.2rem 1.8rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .login-brand {
            font-family: var(--font-heading);
            font-size: 2rem;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #ffffff;
            margin-bottom: 0.3rem;
        }
        .login-brand span {
            color: var(--color-gold);
        }
        .login-sub {
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--color-gold);
        }
        .login-body {
            padding: 2.2rem;
        }
        .form-label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(245, 240, 231, 0.8);
            margin-bottom: 0.5rem;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #ffffff;
            font-family: var(--font-body);
            font-size: 0.95rem;
            padding: 0.8rem 1rem;
            border-radius: 4px;
            transition: all 0.25s ease;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--color-gold);
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(198, 167, 106, 0.15);
        }
        .input-group-text {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--color-gold);
            border-radius: 4px 0 0 4px;
        }
        .btn-gold {
            background: var(--color-gold);
            color: var(--color-midnight);
            font-weight: 700;
            font-size: 0.76rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            padding: 0.95rem 1.5rem;
            border: 1px solid var(--color-gold);
            border-radius: 4px;
            width: 100%;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .btn-gold:hover {
            background: var(--color-gold-hover);
            border-color: var(--color-gold-hover);
            box-shadow: 0 6px 20px rgba(198, 167, 106, 0.3);
            transform: translateY(-1px);
        }
        .return-link {
            font-size: 0.76rem;
            letter-spacing: 0.1em;
            color: rgba(245, 240, 231, 0.5);
            text-decoration: none;
            transition: color 0.2s;
        }
        .return-link:hover {
            color: var(--color-gold);
        }
        .faculty-note {
            background: rgba(198, 167, 106, 0.08);
            border: 1px solid rgba(198, 167, 106, 0.3);
            border-radius: 6px;
            padding: 1rem 1.1rem;
            font-size: 0.82rem;
            line-height: 1.7;
        }
        .faculty-note-title {
            color: var(--color-gold);
            font-weight: 700;
            font-size: 0.86rem;
            letter-spacing: 0.02em;
            margin-bottom: 0.5rem;
        }
        .faculty-note ul {
            list-style: disc inside;
            margin-bottom: 0;
            padding-left: 0;
            color: var(--color-ivory);
        }
        .faculty-note ul li {
            margin-bottom: 0.2rem;
        }
        .faculty-note strong {
            color: var(--color-gold);
        }
        .faculty-note code {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
            padding: 0.15rem 0.45rem;
            border-radius: 3px;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="login-brand">PATEL TRAVELS<span>.</span></div>
        <div class="login-sub">Executive Management Portal</div>
    </div>

    <div class="login-body">
        <?php render_flash_message(); ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4 py-2 px-3 small border-0 rounded-1" style="background: rgba(220, 38, 38, 0.2); color: #fca5a5;" role="alert">
                <i class="bi bi-shield-exclamation me-2 fs-5"></i>
                <div><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <form action="<?= url('auth/login.php') ?>" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="mb-4">
                <label for="identifier" class="form-label">Username or Administrator Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text"
                           class="form-control"
                           id="identifier"
                           name="identifier"
                           value="<?= htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="admin"
                           required
                           autofocus>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label mb-0">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input type="password"
                           class="form-control"
                           id="password"
                           name="password"
                           placeholder="••••••••"
                           required>
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePasswordBtn" style="border-color: rgba(255, 255, 255, 0.12); color: rgba(255,255,255,0.6);" aria-label="Toggle password visibility">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="mt-4 pt-2">
                <button type="submit" class="btn-gold">
                    AUTHENTICATE &amp; ENTER PORTAL <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </form>

        <!-- 📌 Faculty Evaluation Note -->
        <div class="faculty-note mt-4">
            <div class="faculty-note-title">📌 Faculty Evaluation — Admin Login Details</div>
            <ul>
                <li><strong>Login ID:</strong> <code>admin</code></li>
                <li><strong>Password:</strong> <code>admin123</code></li>
            </ul>
        </div>

        <div class="text-center mt-4 pt-3 border-top" style="border-color: rgba(255, 255, 255, 0.06) !important;">
            <a href="<?= url('index.php') ?>" class="return-link">
                <i class="bi bi-arrow-left me-1"></i> Return to Public Website
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const pwdInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    if (toggleBtn && pwdInput && toggleIcon) {
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
});
</script>

</body>
</html>
