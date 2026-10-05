<?php
/**
 * Premium Navbar — Patel Travels
 * Transparent on hero pages, converts to solid on scroll
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="lux-navbar navbar navbar-expand-lg" id="luxNavbar">
    <div class="container">
        <a class="navbar-brand" href="<?= url('index.php') ?>">Patel Travels</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent" aria-controls="navContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage == 'destinations.php' || $currentPage == 'destination-details.php') ? 'active' : '' ?>" href="<?= url('destinations.php') ?>">Destinations</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage == 'tours.php' || $currentPage == 'tour-details.php') ? 'active' : '' ?>" href="<?= url('tours.php') ?>">Journeys</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage == 'about.php') ? 'active' : '' ?>" href="<?= url('about.php') ?>">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage == 'contact.php') ? 'active' : '' ?>" href="<?= url('contact.php') ?>">Contact</a>
                </li>
            </ul>
            <a href="<?= url('contact.php') ?>" class="nav-cta">Plan Your Journey</a>
        </div>
    </div>
</nav>

<?php
// Flash messages
if (isset($_SESSION['public_flash'])) {
    $flash = $_SESSION['public_flash'];
    unset($_SESSION['public_flash']);
    $type = htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
    $msg = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
    echo "<div class='container' style='margin-top: 100px;'><div class='alert alert-{$type} alert-dismissible fade show border-0' role='alert'>
        {$msg}
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
    </div></div>";
}
?>
