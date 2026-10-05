<?php
// client_server.php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__ . $uri) && is_file(__DIR__ . $uri) && !str_ends_with($uri, '.php')) {
    return false; // serve static files as is
}

if ($uri === '/') {
    require_once __DIR__ . '/index.php';
    exit;
}

// Block access to admin pages
if (str_starts_with($uri, '/admin/') || str_starts_with($uri, '/auth/')) {
    http_response_code(403);
    echo "<h1>403 Forbidden</h1><p>Admin panel is not accessible from the Client port.</p>";
    exit;
}

if (file_exists(__DIR__ . $uri)) {
    require_once __DIR__ . $uri;
} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
}
