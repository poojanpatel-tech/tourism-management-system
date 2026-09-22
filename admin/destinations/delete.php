<?php
/**
 * Delete / Deactivate Destination Handler
 * Tourism Management System (College Project)
 *
 * Implements safe foreign key relational integrity checks.
 * Prevents hard deletion when active or historical packages reference the destination.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

// Guard against non-POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('danger', 'Unauthorized request method. Destructive actions must be submitted via POST.');
    header('Location: ' . url('admin/destinations/index.php'));
    exit;
}

$pdo = getDBConnection();

$id = (int)($_POST['destination_id'] ?? 0);
$action = trim($_POST['action'] ?? 'delete'); // 'delete' or 'deactivate'
$csrf = $_POST['csrf_token'] ?? '';

if (!verify_csrf_token($csrf)) {
    set_flash_message('danger', 'Invalid security token. Please try again.');
    header('Location: ' . url('admin/destinations/index.php'));
    exit;
}

if ($id <= 0) {
    set_flash_message('danger', 'Invalid destination ID specified.');
    header('Location: ' . url('admin/destinations/index.php'));
    exit;
}

try {
    // 1. Check if destination exists
    $stmt = $pdo->prepare('SELECT destination_id, destination_name, status FROM destinations WHERE destination_id = :id');
    $stmt->execute([':id' => $id]);
    $destination = $stmt->fetch();

    if (!$destination) {
        set_flash_message('danger', 'The destination you are attempting to delete does not exist.');
        header('Location: ' . url('admin/destinations/index.php'));
        exit;
    }

    $destName = $destination['destination_name'];

    // 2. Handle Deactivation Action
    if ($action === 'deactivate') {
        $stmtDeactivate = $pdo->prepare('UPDATE destinations SET status = "inactive", updated_at = NOW() WHERE destination_id = :id');
        $stmtDeactivate->execute([':id' => $id]);

        set_flash_message('info', "Destination \"{$destName}\" has been marked as Inactive. It is now hidden from new tour packages.");
        header('Location: ' . url('admin/destinations/index.php'));
        exit;
    }

    // 3. Handle Deletion Action with Relational Integrity Check
    if ($action === 'delete') {
        // Query packages associated with this destination
        $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM packages WHERE destination_id = :id');
        $stmtCount->execute([':id' => $id]);
        $packageCount = (int)$stmtCount->fetchColumn();

        if ($packageCount > 0) {
            // Foreign Key dependency found - Prevent hard deletion!
            set_flash_message(
                'warning',
                "Cannot delete destination \"{$destName}\" because it is linked to {$packageCount} tour package(s). To maintain booking records, please reassign or delete those packages first, or switch this destination's status to Inactive."
            );
            header('Location: ' . url('admin/destinations/index.php'));
            exit;
        }

        // Safe to delete - No packages reference this destination
        $stmtDelete = $pdo->prepare('DELETE FROM destinations WHERE destination_id = :id');
        $stmtDelete->execute([':id' => $id]);

        set_flash_message('success', "Destination \"{$destName}\" was deleted successfully from the database.");
        header('Location: ' . url('admin/destinations/index.php'));
        exit;
    }

    // Fallback for unknown action
    set_flash_message('warning', 'Unknown action requested.');
    header('Location: ' . url('admin/destinations/index.php'));
    exit;

} catch (PDOException $e) {
    set_flash_message('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . url('admin/destinations/index.php'));
    exit;
}
