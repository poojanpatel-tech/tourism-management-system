<?php
/**
 * Vercel Serverless Front-Controller Dispatcher
 * Project: Patel Travels — Tourism Management System
 * 
 * Safely dispatches incoming HTTP requests to the target PHP scripts
 * while preserving existing URL patterns, query strings, and session contexts.
 */

// Define project root directory
$projectRoot = dirname(__DIR__);

// Extract requested path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$requestPath = '/' . ltrim($requestPath, '/');

// 1. Security Guard: Strictly block direct access to sensitive project files
$forbiddenPatterns = [
    '/\.env/i',
    '/\.git/i',
    '/\.htaccess/i',
    '/database\.sql/i',
    '/seed\.sql/i',
    '/migration.*\.sql/i',
    '/Dockerfile.*/i',
    '/config\//i',
    '/scratch\//i',
    '/\.md$/i',
    '/composer\.(json|lock)$/i'
];

foreach ($forbiddenPatterns as $pattern) {
    if (preg_match($pattern, $requestPath)) {
        http_response_code(403);
        echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body style='font-family: sans-serif; text-align: center; padding: 50px;'><h1>403 Forbidden</h1><p>Access to this resource is prohibited.</p></body></html>";
        exit;
    }
}

// 2. Resolve target PHP script
$targetFile = null;

if ($requestPath === '/' || $requestPath === '/index.php') {
    $targetFile = 'index.php';
} elseif ($requestPath === '/admin' || $requestPath === '/admin/') {
    $targetFile = 'admin/dashboard.php';
} elseif ($requestPath === '/auth' || $requestPath === '/auth/') {
    $targetFile = 'auth/login.php';
} else {
    $cleanRel = ltrim($requestPath, '/');
    
    // Check exact path
    if (file_exists($projectRoot . '/' . $cleanRel) && !is_dir($projectRoot . '/' . $cleanRel)) {
        $targetFile = $cleanRel;
    } 
    // Check extensionless URL (.php)
    elseif (file_exists($projectRoot . '/' . $cleanRel . '.php')) {
        $targetFile = $cleanRel . '.php';
    }
    // Check directory index
    elseif (is_dir($projectRoot . '/' . $cleanRel) && file_exists($projectRoot . '/' . $cleanRel . '/index.php')) {
        $targetFile = $cleanRel . '/index.php';
    }
}

// 3. Fallback / 404 Handling
if (!$targetFile) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body style='font-family: sans-serif; text-align: center; padding: 50px;'><h1>404 Not Found</h1><p>The requested page could not be found.</p><p><a href='/'>Return to Homepage</a></p></body></html>";
    exit;
}

$fullTargetPath = realpath($projectRoot . '/' . $targetFile);

// Verify canonical path to prevent directory traversal
if (!$fullTargetPath || !str_starts_with(str_replace('\\', '/', $fullTargetPath), str_replace('\\', '/', $projectRoot))) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

// 4. Configure environment globals for target script
$_SERVER['SCRIPT_FILENAME'] = $fullTargetPath;
$_SERVER['SCRIPT_NAME'] = '/' . ltrim($targetFile, '/');
$_SERVER['PHP_SELF'] = '/' . ltrim($targetFile, '/');

// Execute the requested PHP script in its directory context
chdir(dirname($fullTargetPath));
require $fullTargetPath;
