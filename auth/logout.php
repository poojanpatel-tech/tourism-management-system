<?php
/**
 * Administrator Logout Script
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../includes/auth.php';

logout_user();

// Flash message must be set after new session is started or in clean state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
set_flash_message('info', 'You have been successfully logged out.');

header('Location: ' . url('auth/login.php'));
exit;
