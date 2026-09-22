<?php
/**
 * Delete Customer Handler
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
    header('Location: ' . url('admin/customers/index.php'));
    exit;
}

$pdo = getDBConnection();

$id = (int)($_POST['customer_id'] ?? 0);
$action = trim($_POST['action'] ?? 'delete');
$csrf = $_POST['csrf_token'] ?? '';

if (!verify_csrf_token($csrf)) {
    set_flash_message('danger', 'Invalid security token. Please try again.');
    header('Location: ' . url('admin/customers/index.php'));
    exit;
}

if ($id <= 0) {
    set_flash_message('danger', 'Invalid customer ID specified.');
    header('Location: ' . url('admin/customers/index.php'));
    exit;
}

try {
    // 1. Check if customer exists
    $stmt = $pdo->prepare('SELECT customer_id, full_name, customer_code FROM customers WHERE customer_id = :id');
    $stmt->execute([':id' => $id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        set_flash_message('danger', 'The customer you are attempting to delete does not exist.');
        header('Location: ' . url('admin/customers/index.php'));
        exit;
    }

    $custName = $customer['full_name'];
    $custCode = $customer['customer_code'];

    // 2. Handle Deletion with Reservation Dependency Check
    if ($action === 'delete') {
        // Count reservations associated with this customer
        $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE customer_id = :id');
        $stmtCount->execute([':id' => $id]);
        $reservationCount = (int)$stmtCount->fetchColumn();

        if ($reservationCount > 0) {
            // Foreign Key dependency — prevent hard deletion
            set_flash_message(
                'warning',
                "Cannot delete customer \"{$custName}\" ({$custCode}) because they have {$reservationCount} reservation(s). " .
                "To maintain accurate financial and booking records, this customer cannot be removed."
            );
            header('Location: ' . url('admin/customers/index.php'));
            exit;
        }

        // Safe to delete — no reservations reference this customer
        $stmtDelete = $pdo->prepare('DELETE FROM customers WHERE customer_id = :id');
        $stmtDelete->execute([':id' => $id]);

        set_flash_message('success', "Customer \"{$custName}\" ({$custCode}) was permanently deleted from the database.");
        header('Location: ' . url('admin/customers/index.php'));
        exit;
    }

    // Fallback for unknown action
    set_flash_message('warning', 'Unknown action requested.');
    header('Location: ' . url('admin/customers/index.php'));
    exit;

} catch (PDOException $e) {
    set_flash_message('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . url('admin/customers/index.php'));
    exit;
}
