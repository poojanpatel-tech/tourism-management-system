<?php
/**
 * Database Configuration & PDO Connection
 * Tourism Management System (College Project)
 *
 * Configured for XAMPP default settings (localhost, root, empty password).
 */

// Prevent direct script execution if accessed outside PHP context
defined('DB_HOST') or define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
defined('DB_PORT') or define('DB_PORT', getenv('DB_PORT') ?: '3306');
defined('DB_NAME') or define('DB_NAME', getenv('DB_NAME') ?: 'tourism_management');
defined('DB_USER') or define('DB_USER', getenv('DB_USER') ?: 'root');
defined('DB_PASS') or define('DB_PASS', getenv('DB_PASS') ?: '');
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8mb4');

/**
 * Get or create the active PDO database connection.
 *
 * @return PDO
 */
function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Render a clean, helpful college-friendly troubleshooting guide
        renderDatabaseErrorScreen($e);
        exit;
    }
}

/**
 * Check if the database connection is alive without halting the script.
 * Useful for the dashboard and system health status.
 *
 * @return array ['connected' => bool, 'message' => string]
 */
function checkDBStatus(): array
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    try {
        $testPdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 2,
        ]);
        return ['connected' => true, 'message' => 'Connected successfully to ' . DB_NAME];
    } catch (PDOException $e) {
        return ['connected' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Render a graceful error page when MySQL is offline or database.sql is not yet imported.
 *
 * @param PDOException $e
 */
function renderDatabaseErrorScreen(PDOException $e): void
{
    http_response_code(500);
    // Securely log the actual PDO exception for server admins
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Provide a safe, generic message to the public user
    $errorMsg = "Unable to connect to the application database. Please try again later.";
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error - Tourism Management System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            body { background: #f1f5f9; font-family: system-ui, -apple-system, sans-serif; color: #1e293b; min-height: 100vh; display: flex; align-items: center; }
            .error-card { max-width: 680px; margin: auto; border: none; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
            .badge-step { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; margin-right: 8px; }
            pre { background: #0f172a; color: #f87171; padding: 12px 16px; border-radius: 8px; font-size: 0.85rem; }
        </style>
    </head>
    <body>
        <div class="container py-5">
            <div class="card error-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="text-danger mb-2">
                        <i class="bi bi-database-exclamation fs-1"></i>
                    </div>
                    <h3 class="fw-bold">Database Connection Notice</h3>
                    <p class="text-muted">The Tourism Management System could not connect to MySQL.</p>
                </div>

                <div class="alert alert-danger py-2 px-3">
                    <strong>Error Details:</strong>
                    <div class="mt-1"><pre class="mb-0 text-wrap"><?= $errorMsg ?></pre></div>
                </div>

                <h5 class="fw-semibold mt-3 mb-3">Quick Setup Steps for College Evaluation:</h5>
                <ol class="list-group list-group-numbered mb-4">
                    <li class="list-group-item">Open <strong>XAMPP Control Panel</strong> and click <strong>Start</strong> next to both <strong>Apache</strong> and <strong>MySQL</strong>.</li>
                    <li class="list-group-item">Open your browser and navigate to <a href="http://localhost/phpmyadmin" target="_blank" class="fw-medium">http://localhost/phpmyadmin</a>.</li>
                    <li class="list-group-item">Click the <strong>Import</strong> tab at the top.</li>
                    <li class="list-group-item">Choose the file <code>database.sql</code> located in this project's root folder and click <strong>Go</strong> (it will automatically create the <code>tourism_management</code> database and all sample data).</li>
                    <li class="list-group-item">Refresh this page to access the application!</li>
                </ol>

                <div class="d-flex justify-content-between align-items-center">
                    <button onclick="window.location.reload();" class="btn btn-primary px-4">
                        <i class="bi bi-arrow-clockwise me-1"></i> Retry Connection
                    </button>
                    <span class="text-muted small">Config: <code>config/database.php</code></span>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
