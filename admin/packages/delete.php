<?php
/**
 * Delete / Deactivate Tour Package Handler
 * Tourism Management System (College Project)
 *
 * Implements safe reservation dependency checks before deletion.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

// Guard against non-POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('danger', 'Unauthorized request method. Destructive actions must be submitted via POST.');
    header('Location: ' . url('admin/packages/index.php'));
    exit;
}

$pdo = getDBConnection();

$id = (int)($_POST['package_id'] ?? 0);
$action = trim($_POST['action'] ?? 'delete'); // 'delete' or 'deactivate'
$csrf = $_POST['csrf_token'] ?? '';

if (!verify_csrf_token($csrf)) {
    set_flash_message('danger', 'Invalid security token. Please try again.');
    header('Location: ' . url('admin/packages/index.php'));
    exit;
}

if ($id <= 0) {
    set_flash_message('danger', 'Invalid package ID specified.');
    header('Location: ' . url('admin/packages/index.php'));
    exit;
}

try {
    // 1. Check if package exists
    $stmt = $pdo->prepare('SELECT package_id, package_name, package_code, status FROM packages WHERE package_id = :id');
    $stmt->execute([':id' => $id]);
    $package = $stmt->fetch();

    if (!$package) {
        set_flash_message('danger', 'The package you are attempting to manage does not exist.');
        header('Location: ' . url('admin/packages/index.php'));
        exit;
    }

    $pkgName = $package['package_name'];
    $pkgCode = $package['package_code'];

    // 2. Handle Deactivation Action
    if ($action === 'deactivate') {
        $stmtDeactivate = $pdo->prepare('UPDATE packages SET status = "inactive", updated_at = NOW() WHERE package_id = :id');
        $stmtDeactivate->execute([':id' => $id]);

        set_flash_message('info', "Package \"{$pkgName}\" ({$pkgCode}) has been marked as Inactive. It is now hidden from new bookings.");
        header('Location: ' . url('admin/packages/index.php'));
        exit;
    }

    // 3. Handle Deletion with Reservation Dependency Check
    if ($action === 'delete') {
        // Count reservations associated with this package
        $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE package_id = :id');
        $stmtCount->execute([':id' => $id]);
        $reservationCount = (int)$stmtCount->fetchColumn();

        if ($reservationCount > 0) {
            // Foreign Key dependency — prevent hard deletion
            set_flash_message(
                'warning',
                "Cannot delete package \"{$pkgName}\" ({$pkgCode}) because it has {$reservationCount} reservation(s). " .
                "To maintain booking records, please reassign or cancel those reservations first, or switch this package's status to Inactive."
            );
            header('Location: ' . url('admin/packages/index.php'));
            exit;
        }

        // Safe to delete — no reservations reference this package
        $stmtDelete = $pdo->prepare('DELETE FROM packages WHERE package_id = :id');
        $stmtDelete->execute([':id' => $id]);

        set_flash_message('success', "Package \"{$pkgName}\" ({$pkgCode}) was permanently deleted from the database.");
        header('Location: ' . url('admin/packages/index.php'));
        exit;
    }

    // Fallback for unknown action
    set_flash_message('warning', 'Unknown action requested.');
    header('Location: ' . url('admin/packages/index.php'));
    exit;

} catch (PDOException $e) {
    set_flash_message('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . url('admin/packages/index.php'));
    exit;
}
