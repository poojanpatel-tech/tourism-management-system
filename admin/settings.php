<?php
/**
 * Admin - Business Settings
 */
session_name('TOURISM_ADMIN_SESSION');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('danger', 'Invalid security token.');
        header('Location: settings.php');
        exit;
    }

    $settingsFields = [
        'company_name', 'tagline', 'email', 'phone', 'whatsapp', 'address', 'working_hours',
        'instagram', 'facebook', 'google_maps', 'footer_description'
    ];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (:key, :val)");
        
        foreach ($settingsFields as $key) {
            $val = trim($_POST[$key] ?? '');
            $stmt->execute([':key' => $key, ':val' => $val]);
        }
        
        $pdo->commit();
        set_flash_message('success', 'Business settings updated successfully.');
    } catch (PDOException $e) {
        $pdo->rollBack();
        set_flash_message('danger', 'Failed to update settings: ' . $e->getMessage());
    }
    
    header('Location: settings.php');
    exit;
}

// Fetch current settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settingsMap = [];
while ($row = $stmt->fetch()) {
    $settingsMap[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Business Settings';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Business Settings</h2>
    </div>
    
    <?php display_flash_message(); ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form method="POST" action="settings.php">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <h5 class="mb-3 border-bottom pb-2">General Information</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Company Name</label>
                        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($settingsMap['company_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Tagline / Slogan</label>
                        <input type="text" name="tagline" class="form-control" value="<?= htmlspecialchars($settingsMap['tagline'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-medium">Footer Description</label>
                        <textarea name="footer_description" class="form-control" rows="2"><?= htmlspecialchars($settingsMap['footer_description'] ?? '') ?></textarea>
                    </div>
                </div>

                <h5 class="mb-3 border-bottom pb-2">Contact Details</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($settingsMap['email'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settingsMap['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">WhatsApp</label>
                        <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($settingsMap['whatsapp'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Physical Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($settingsMap['address'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Working Hours</label>
                        <textarea name="working_hours" class="form-control" rows="2" placeholder="E.g., Mon - Sat, 10:00 AM - 6:00 PM"><?= htmlspecialchars($settingsMap['working_hours'] ?? '') ?></textarea>
                    </div>
                </div>

                <h5 class="mb-3 border-bottom pb-2">Social Links</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Instagram URL</label>
                        <input type="url" name="instagram" class="form-control" value="<?= htmlspecialchars($settingsMap['instagram'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Facebook URL</label>
                        <input type="url" name="facebook" class="form-control" value="<?= htmlspecialchars($settingsMap['facebook'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Google Maps URL</label>
                        <input type="url" name="google_maps" class="form-control" value="<?= htmlspecialchars($settingsMap['google_maps'] ?? '') ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="dashboard.php" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
