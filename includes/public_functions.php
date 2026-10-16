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
            if (str_starts_with($imageName, 'http://') || str_starts_with($imageName, 'https://')) {
                return $imageName;
            }
            $dir = ($type === 'destination') ? 'destinations/' : 'packages/';
            return url("assets/images/{$dir}" . htmlspecialchars($imageName));
        }
        
        if ($type === 'destination') {
            return url("assets/public/images/fallback/destination.jpg");
        } else if ($type === 'hero') {
            return url("assets/public/images/fallback/hero.jpg");
        } else {
            return url("assets/public/images/fallback/plan.jpg");
        }
    }
}

if (!function_exists('get_destination_image')) {
    function get_destination_image(array $dest): string {
        $id = (int)($dest['destination_id'] ?? 0);
        $map = [
            1 => 'bali.jpg',
            2 => 'paris.jpg',
            3 => 'kyoto.jpg',
            4 => 'swiss.jpg',
            5 => 'dubai.jpg',
            6 => 'kerala.jpg',
        ];
        if (isset($map[$id])) {
            return url("assets/images/destinations/" . $map[$id]);
        }
        $name = strtolower($dest['destination_name'] ?? '');
        foreach (['bali', 'paris', 'kyoto', 'swiss', 'dubai', 'kerala'] as $key) {
            if (str_contains($name, $key)) {
                return url("assets/images/destinations/{$key}.jpg");
            }
        }
        return url("assets/public/images/fallback/destination.jpg");
    }
}

if (!function_exists('get_package_image')) {
    function get_package_image(array $pkg): string {
        $img = $pkg['image'] ?? '';
        if (!empty($img)) {
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                return $img;
            }
            return url("assets/images/packages/" . htmlspecialchars($img));
        }
        $id = (int)($pkg['package_id'] ?? 0);
        $map = [
            1 => 'bali.jpg',
            2 => 'paris.jpg',
            3 => 'kyoto.jpg',
            4 => 'swiss.jpg',
            5 => 'dubai.jpg',
            6 => 'kerala.jpg',
        ];
        if (isset($map[$id])) {
            return url("assets/images/packages/" . $map[$id]);
        }
        return url("assets/public/images/fallback/plan.jpg");
    }
}
