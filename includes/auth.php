<?php
/**
 * Authentication & Helper Utilities
 * Tourism Management System (College Project)
 */

if (session_status() === PHP_SESSION_NONE) {
    // Set secure session cookie parameters
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Compute base path relative to web root
$docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));

$subDir = '';
if ($docRoot && str_starts_with($projectRoot, $docRoot)) {
    $subDir = substr($projectRoot, strlen($docRoot));
}
$subDir = '/' . trim($subDir, '/');
if ($subDir === '/') {
    $subDir = '';
}

defined('BASE_PATH') or define('BASE_PATH', $subDir);
defined('PROJECT_ROOT') or define('PROJECT_ROOT', $projectRoot);

/**
 * Generate a consistent absolute URL for assets and routes.
 *
 * @param string $path
 * @return string
 */
function url(string $path = ''): string
{
    $cleanPath = ltrim($path, '/');
    if ($cleanPath === '') {
        return BASE_PATH === '' ? '/' : BASE_PATH . '/';
    }
    return BASE_PATH . '/' . $cleanPath;
}

/**
 * Check if an administrator is currently authenticated.
 *
 * @return bool
 */
function is_logged_in(): bool
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && !empty($_SESSION['admin_user']);
}

/**
 * Guard protected admin pages; redirects to login if not logged in.
 *
 * @return void
 */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash_message('warning', 'Please login to access the administration dashboard.');
        header('Location: ' . url('auth/login.php'));
        exit;
    }
}

/**
 * Get logged-in administrator data.
 *
 * @return array|null
 */
function get_logged_in_user(): ?array
{
    return $_SESSION['admin_user'] ?? null;
}

/**
 * Authenticate and log in administrator.
 *
 * @param array $admin
 * @return void
 */
function login_user(array $admin): void
{
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user'] = [
        'admin_id'  => $admin['admin_id'],
        'username'  => $admin['username'],
        'full_name' => $admin['full_name'],
        'email'     => $admin['email'],
    ];
}

/**
 * Log out administrator and clear session data.
 *
 * @return void
 */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Set a flash message for the next request.
 *
 * @param string $type ('success', 'danger', 'warning', 'info')
 * @param string $message
 * @return void
 */
function set_flash_message(string $type, string $message): void
{
    $_SESSION['flash_message'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Retrieve and clear active flash message.
 *
 * @return array|null
 */
function get_flash_message(): ?array
{
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

/**
 * Render flash message as a Bootstrap 5 dismissible alert.
 *
 * @return void
 */
function render_flash_message(): void
{
    $flash = get_flash_message();
    if (!$flash) {
        return;
    }

    $type = htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');

    $icon = match ($flash['type']) {
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-octagon-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        default   => 'bi-info-circle-fill',
    };

    echo <<<HTML
    <div class="alert alert-{$type} alert-dismissible fade show d-flex align-items-center shadow-sm my-3" role="alert">
        <i class="bi {$icon} me-2 fs-5"></i>
        <div>{$message}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
HTML;
}

/**
 * Clean & sanitize user input.
 *
 * @param mixed $data
 * @return string
 */
function sanitize(mixed $data): string
{
    if ($data === null) {
        return '';
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency for displays.
 *
 * @param float|int|string $amount
 * @return string
 */
function format_currency(float|int|string $amount): string
{
    $amount = (float)$amount;
    $isNegative = $amount < 0;
    $amount = abs($amount);
    
    $parts = explode('.', number_format($amount, 2, '.', ''));
    $whole = $parts[0];
    $fraction = $parts[1];
    
    $last3 = substr($whole, -3);
    $rest = substr($whole, 0, -3);
    if ($rest != '') {
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) . ',';
    }
    $formatted = $rest . $last3;
    if ($isNegative) {
        $formatted = '-' . $formatted;
    }
    
    if ($fraction === '00') {
        return '₹' . $formatted;
    }
    return '₹' . $formatted . '.' . $fraction;
}

/**
 * Format date for friendly display.
 *
 * @param string|null $dateStr
 * @param string $format
 * @return string
 */
function format_date(?string $dateStr, string $format = 'M d, Y'): string
{
    if (empty($dateStr)) {
        return 'N/A';
    }
    $timestamp = strtotime($dateStr);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

/**
 * Render appropriate Bootstrap badge for statuses.
 *
 * @param string $status
 * @return string
 */
function get_status_badge(string $status): string
{
    $statusLower = strtolower(trim($status));
    return match ($statusLower) {
        'confirmed', 'active' => '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle me-1"></i>' . ucfirst($statusLower) . '</span>',
        'pending'             => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-clock-history me-1"></i>' . ucfirst($statusLower) . '</span>',
        'cancelled', 'inactive' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle me-1"></i>' . ucfirst($statusLower) . '</span>',
        'sold_out'            => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-slash-circle me-1"></i>Sold Out</span>',
        default               => '<span class="badge bg-light text-dark border px-2 py-1">' . htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') . '</span>',
    };
}

/**
 * Generate and store a CSRF token in the session.
 * 
 * @return string
 */
function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token against the session.
 * 
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
