<?php
/**
 * Public Header — Patel Travels Luxury Brand
 */
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.save_path', '/tmp');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_name('TOURISM_CLIENT_SESSION');
    session_start();
}

require_once __DIR__ . '/public_functions.php';

$pageTitle = isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' — Patel Travels' : 'Patel Travels — Curated Travel Experiences';
$metaDescription = $metaDescription ?? 'Discover thoughtfully designed journeys and unforgettable destinations with Patel Travels. Premium curated travel experiences across India and beyond.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="website">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Luxury Stylesheet -->
    <link rel="stylesheet" href="<?= url('assets/css/public.css') ?>">
</head>
<body>
