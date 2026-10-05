<?php
/**
 * Public Functions Component
 * Tourism Management System
 */

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

if (!function_exists('url')) {
    function url(string $path = ''): string {
        $cleanPath = ltrim($path, '/');
        if ($cleanPath === '') {
            return BASE_PATH === '' ? '/' : BASE_PATH . '/';
        }
        return BASE_PATH . '/' . $cleanPath;
    }
}

if (!function_exists('is_client_logged_in')) {
    function is_client_logged_in(): bool {
        return isset($_SESSION['client_logged_in']) && $_SESSION['client_logged_in'] === true && !empty($_SESSION['client_user_id']);
    }
}

if (!function_exists('get_client_user')) {
    function get_client_user(): ?array {
        return $_SESSION['client_user'] ?? null;
    }
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(?string $token): bool {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('get_public_image')) {
    function get_public_image(?string $imageName, string $type = 'plan'): string {
        if (!empty($imageName)) {
            // Allow full external URLs directly for development/licensed CDNs
            if (str_starts_with($imageName, 'http://') || str_starts_with($imageName, 'https://')) {
                return $imageName;
            }
            
            // For plans/packages, the dir is assets/images/packages/
            // For destinations, the dir is assets/images/destinations/
            $dir = ($type === 'destination') ? 'destinations/' : 'packages/';
            return url("assets/images/{$dir}" . htmlspecialchars($imageName));
        }
        
        // Fallbacks
        if ($type === 'destination') {
            return url("assets/public/images/fallback/destination.jpg");
        } else if ($type === 'hero') {
            return url("assets/public/images/fallback/hero.jpg");
        } else {
            return url("assets/public/images/fallback/plan.jpg");
        }
    }
}
