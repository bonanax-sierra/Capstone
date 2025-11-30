<?php
/**
 * System Cleanup and Optimization Script
 * This script removes unnecessary files and optimizes the system
 * Run this script once to clean up the enhanced system
 */

session_start();
include 'includes/auth.php';
include 'includes/db.php';

// Require admin authentication
requireAdmin();

$cleanup_log = [];
$errors = [];

function logAction($message) {
    global $cleanup_log;
    $cleanup_log[] = date('Y-m-d H:i:s') . " - " . $message;
}

function logError($message) {
    global $errors;
    $errors[] = date('Y-m-d H:i:s') . " - ERROR: " . $message;
}

// Only run cleanup if explicitly requested
if (isset($_POST['run_cleanup']) && $_POST['run_cleanup'] === 'yes') {
    
    logAction("Starting system cleanup and optimization...");
    
    // 1. Remove duplicate or unnecessary files
    $files_to_check = [
        'admin_dashboard_old.php',
        'index_old.php',
        'reports_backup.php',
        'test.php',
        'debug.php',
        'temp.php'
    ];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                logAction("Removed unnecessary file: $file");
            } else {
                logError("Failed to remove file: $file");
            }
        }
    }
    
    // 2. Clean up temporary directories
    $temp_dirs = ['temp', 'cache', 'logs'];
    foreach ($temp_dirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file) && (time() - filemtime($file)) > 86400) { // Older than 24 hours
                    if (unlink($file)) {
                        logAction("Removed old temporary file: $file");
                    }
                }
            }
        }
    }
    
    // 3. Database optimization
    try {
        // Remove old log entries (older than 90 days)
        $stmt = $pdo->prepare("DELETE FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
        $deleted = $stmt->execute();
        if ($deleted) {
            $count = $stmt->rowCount();
            logAction("Cleaned up $count old log entries");
        }
        
        // Optimize database tables
        $tables = ['assessment', 'admin', 'school', 'activity_info', 'admin_logs', 'system_settings'];
        foreach ($tables as $table) {
            $pdo->exec("OPTIMIZE TABLE $table");
            logAction("Optimized table: $table");
        }
        
        // Update table statistics
        foreach ($tables as $table) {
            $pdo->exec("ANALYZE TABLE $table");
        }
        logAction("Updated table statistics");
        
    } catch (PDOException $e) {
        logError("Database optimization failed: " . $e->getMessage());
    }
    
    // 4. Clean up session files (if using file-based sessions)
    if (ini_get('session.save_handler') === 'files') {
        $session_path = session_save_path();
        if ($session_path && is_dir($session_path)) {
            $session_files = glob($session_path . '/sess_*');
            $cleaned = 0;
            foreach ($session_files as $file) {
                if ((time() - filemtime($file)) > 3600) { // Older than 1 hour
                    if (unlink($file)) {
                        $cleaned++;
                    }
                }
            }
            if ($cleaned > 0) {
                logAction("Cleaned up $cleaned old session files");
            }
        }
    }
    
    // 5. Create .htaccess for security (if it doesn't exist)
    $htaccess_content = "# Security Headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection \"1; mode=block\"
    Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains\"
</IfModule>

# Prevent access to sensitive files
<Files ~ \"\\.(sql|log|ini|conf)$\">
    Order allow,deny
    Deny from all
</Files>

# Prevent access to includes directory browsing
<Directory \"includes\">
    Options -Indexes
</Directory>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css \"access plus 1 month\"
    ExpiresByType application/javascript \"access plus 1 month\"
    ExpiresByType image/png \"access plus 1 month\"
    ExpiresByType image/jpg \"access plus 1 month\"
    ExpiresByType image/jpeg \"access plus 1 month\"
    ExpiresByType image/gif \"access plus 1 month\"
</IfModule>";

    if (!file_exists('.htaccess')) {
        if (file_put_contents('.htaccess', $htaccess_content)) {
            logAction("Created .htaccess file for security and performance");
        } else {
            logError("Failed to create .htaccess file");
        }
    }
    
    // 6. Update system settings
    try {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
        
        $settings = [
            ['last_cleanup', date('Y-m-d H:i:s'), 'string', 'Last system cleanup date'],
            ['system_optimized', '1', 'boolean', 'System has been optimized'],
            ['cleanup_version', '2.0', 'string', 'Cleanup script version']
        ];
        
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
        
        logAction("Updated system settings");
        
    } catch (PDOException $e) {
        logError("Failed to update system settings: " . $e->getMessage());
    }
    
    // 7. Create backup directory structure
    $backup_dirs = ['backups', 'backups/database', 'backups/files'];
    foreach ($backup_dirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                logAction("Created backup directory: $dir");
            } else {
                logError("Failed to create backup directory: $dir");
            }
        }
    }
    
    logAction("System cleanup and optimization completed!");
    
    // Log the cleanup action
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $_SESSION['admin_id'],
            'system_cleanup',
            'System cleanup and optimization completed',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        logError("Failed to log cleanup action: " . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Cleanup - Risk Analytics</title>
    <link rel="stylesheet" href="css/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            display: flex;
            background-color: #f8f9fa;
        }
        .main-content {
            flex: 1;
            padding: 40px;
            margin-left: 250px;
        }
        .page-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 30px;
        }
        .card {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: none;
            margin-bottom: 20px;
        }
        .log-output {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            max-height: 400px;
            overflow-y: auto;
        }
        .warning-box {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            border: none;
            color: #721c24;
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-title">
            <i class="bi bi-tools"></i> System Cleanup & Optimization
        </div>
        
        <?php if (!empty($cleanup_log) || !empty($errors)): ?>
            <!-- Cleanup Results -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-check-circle"></i> Cleanup Results</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($cleanup_log)): ?>
                        <h6 class="text-success">Actions Completed:</h6>
                        <div class="log-output">
                            <?php foreach ($cleanup_log as $log): ?>
                                <div><?php echo htmlspecialchars($log); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <h6 class="text-danger mt-3">Errors:</h6>
                        <div class="log-output">
                            <?php foreach ($errors as $error): ?>
                                <div class="text-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="system_status.php" class="btn btn-primary">
                            <i class="bi bi-activity"></i> View System Status
                        </a>
                        <a href="admin_dashboard.php" class="btn btn-success">
                            <i class="bi bi-house"></i> Return to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Cleanup Warning and Form -->
            <div class="card warning-box">
                <div class="card-header">
                    <h5><i class="bi bi-exclamation-triangle"></i> Important Notice</h5>
                </div>
                <div class="card-body">
                    <p><strong>This cleanup script will:</strong></p>
                    <ul>
                        <li>Remove unnecessary and temporary files</li>
                        <li>Clean up old log entries (older than 90 days)</li>
                        <li>Optimize database tables for better performance</li>
                        <li>Create security configurations</li>
                        <li>Set up proper directory structure</li>
                    </ul>
                    <p class="mb-0"><strong>Note:</strong> This action cannot be undone. Make sure you have a backup before proceeding.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-play-circle"></i> Run System Cleanup</h5>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return confirm('Are you sure you want to run the system cleanup? This action cannot be undone.');">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirm_cleanup" required>
                                <label class="form-check-label" for="confirm_cleanup">
                                    I understand that this will permanently remove unnecessary files and optimize the database
                                </label>
                            </div>
                        </div>
                        
                        <input type="hidden" name="run_cleanup" value="yes">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-tools"></i> Run Cleanup & Optimization
                        </button>
                        <a href="admin_dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar-highlight.js"></script>
</body>
</html>
