<?php
/**
 * Delete Reservation
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

// Strict POST-only execution
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('danger', 'Invalid request method. Deletion requires a form submission.');
    header('Location: ' . url('admin/reservations/index.php'));
    exit;
}

$id = (int)($_POST['reservation_id'] ?? 0);
$csrf = $_POST['csrf_token'] ?? '';

if (!verify_csrf_token($csrf)) {
    set_flash_message('danger', 'Invalid security token. Please try again.');
    header('Location: ' . url('admin/reservations/index.php'));
    exit;
}

if ($id <= 0) {
    set_flash_message('danger', 'Invalid reservation ID specified.');
    header('Location: ' . url('admin/reservations/index.php'));
    exit;
}

$pdo = getDBConnection();

try {
    $pdo->beginTransaction();

    // Verify existence
    $stmtCheck = $pdo->prepare('SELECT booking_number FROM reservations WHERE reservation_id = :id FOR UPDATE');
    $stmtCheck->execute([':id' => $id]);
    $reservation = $stmtCheck->fetch();

    if (!$reservation) {
        throw new Exception('The requested reservation does not exist.');
    }

    // Hard Delete
    $stmtDelete = $pdo->prepare('DELETE FROM reservations WHERE reservation_id = :id');
    $stmtDelete->execute([':id' => $id]);

    $pdo->commit();

    set_flash_message('success', "Reservation {$reservation['booking_number']} has been permanently deleted.");

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash_message('danger', 'Failed to delete reservation: ' . $e->getMessage());
}

header('Location: ' . url('admin/reservations/index.php'));
exit;
