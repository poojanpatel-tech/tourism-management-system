<?php
// admin_server.php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__ . $uri) && is_file(__DIR__ . $uri) && !str_ends_with($uri, '.php')) {
    return false; // serve static files as is
}

if ($uri === '/') {
    header('Location: /admin/dashboard.php');
    exit;
}

// Block access to client pages
$client_pages = [
    '/index.php', '/tours.php', '/destinations.php', '/contact.php', 
    '/login.php', '/register.php', '/profile.php', '/my-bookings.php',
    '/tour-details.php', '/destination-details.php', '/book.php', '/payment.php', '/write-review.php'
];

if (in_array($uri, $client_pages)) {
    http_response_code(403);
    echo "<h1>403 Forbidden</h1><p>Client panel is not accessible from the Admin port.</p>";
    exit;
}

if (file_exists(__DIR__ . $uri)) {
    require_once __DIR__ . $uri;
} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
}
