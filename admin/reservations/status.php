<?php
/**
 * Update Reservation Status
 * Tourism Management System (College Project)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('danger', 'Invalid request method.');
    header('Location: ' . url('admin/reservations/index.php'));
    exit;
}

$id = (int)($_POST['reservation_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');
$csrf = $_POST['csrf_token'] ?? '';

if (!verify_csrf_token($csrf)) {
    set_flash_message('danger', 'Invalid security token. Please try again.');
    header('Location: ' . url('admin/reservations/index.php'));
    exit;
}

if ($id <= 0 || !in_array($newStatus, ['pending', 'confirmed', 'cancelled'], true)) {
    set_flash_message('danger', 'Invalid status update parameters.');
    header('Location: ' . url('admin/reservations/index.php'));
    exit;
}

$pdo = getDBConnection();

try {
    $pdo->beginTransaction();

    // 1. Fetch current reservation
    $stmtRes = $pdo->prepare('SELECT status, package_id, number_of_travelers, booking_number FROM reservations WHERE reservation_id = :id FOR UPDATE');
    $stmtRes->execute([':id' => $id]);
    $reservation = $stmtRes->fetch();

    if (!$reservation) {
        throw new Exception('Reservation not found.');
    }

    if ($reservation['status'] === $newStatus) {
        throw new Exception("Reservation is already {$newStatus}.");
    }

    // 2. Capacity Check (Only needed if we are moving TO a consuming status, e.g. cancelled -> confirmed)
    // However, pending -> confirmed doesn't consume NEW capacity, but we check just to be safe.
    if ($newStatus !== 'cancelled') {
        // We only care about capacity if the CURRENT status wasn't already consuming capacity, 
        // OR we just want to ensure it's still valid. In this system, BOTH pending and confirmed consume capacity.
        // So cancelled -> confirmed/pending requires new capacity.
        if ($reservation['status'] === 'cancelled') {
            
            // Check capacity EXCLUDING this cancelled reservation (which wouldn't be counted anyway, but good practice)
            $stmtLock = $pdo->prepare('
                SELECT p.maximum_capacity,
                       COALESCE(SUM(CASE WHEN r.status IN ("confirmed", "pending") THEN r.number_of_travelers ELSE 0 END), 0) AS booked_capacity
                FROM packages p
                LEFT JOIN reservations r ON p.package_id = r.package_id
                WHERE p.package_id = :pkg_id
                GROUP BY p.package_id
                FOR UPDATE
            ');
            $stmtLock->execute([':pkg_id' => $reservation['package_id']]);
            $lockedPkg = $stmtLock->fetch();

            if (!$lockedPkg) {
                throw new Exception('Associated package not found.');
            }

            $availableSeats = max(0, (int)$lockedPkg['maximum_capacity'] - (int)$lockedPkg['booked_capacity']);
            if ((int)$reservation['number_of_travelers'] > $availableSeats) {
                throw new Exception("Cannot confirm. Only {$availableSeats} seats available.");
            }
        }
    }

    // 3. Update Status
    $stmtUpdate = $pdo->prepare('UPDATE reservations SET status = :status, updated_at = NOW() WHERE reservation_id = :id');
    $stmtUpdate->execute([
        ':status' => $newStatus,
        ':id' => $id
    ]);

    $pdo->commit();

    set_flash_message('success', "Reservation {$reservation['booking_number']} marked as {$newStatus}.");

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash_message('danger', 'Failed to update status: ' . $e->getMessage());
}

// Return to where they came from (could be view page or index page)
$redirect = $_SERVER['HTTP_REFERER'] ?? url('admin/reservations/index.php');
header('Location: ' . $redirect);
exit;
