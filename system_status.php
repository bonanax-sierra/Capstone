<?php
include 'includes/auth.php';
include 'includes/db.php';

// Require admin access
requireAdmin();

$current_user = getCurrentUser();

// ----------------------------
// Helper functions
// ----------------------------
function checkDatabase($pdo) {
    try {
        $pdo->query("SELECT 1");
        return ['status' => 'healthy', 'message' => 'Database connection successful'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
    }
}

function checkTables($pdo, $tables) {
    $missing = [];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() == 0) {
                $missing[] = $table;
            }
        } catch (PDOException $e) {
            $missing[] = $table;
        }
    }
    if (empty($missing)) {
        return ['status' => 'healthy', 'message' => 'All required tables exist'];
    }
    return ['status' => 'warning', 'message' => 'Missing tables: ' . implode(', ', $missing)];
}

function checkPermissions($dirs) {
    $issues = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                $issues[] = "$dir (cannot create)";
            }
        } elseif (!is_writable($dir)) {
            $issues[] = "$dir (not writable)";
        }
    }
    if (empty($issues)) {
        return ['status' => 'healthy', 'message' => 'File permissions are correct'];
    }
    return ['status' => 'warning', 'message' => 'Permission issues: ' . implode(', ', $issues)];
}

function checkExtensions($extensions) {
    $missing = array_filter($extensions, fn($ext) => !extension_loaded($ext));
    if (empty($missing)) {
        return ['status' => 'healthy', 'message' => 'All required PHP extensions loaded'];
    }
    return ['status' => 'error', 'message' => 'Missing extensions: ' . implode(', ', $missing)];
}

// ----------------------------
// System Health Checks
// ----------------------------
$health_checks = [
    'database'    => checkDatabase($pdo),
    'tables'      => checkTables($pdo, ['users', 'assessment', 'school', 'activity_info', 'user_logs', 'system_settings']),
    'permissions' => checkPermissions(['backups']),
    'extensions'  => checkExtensions(['pdo', 'pdo_mysql', 'json', 'openssl']),
];

// ----------------------------
// System Statistics
// ----------------------------
$stats = [];
$system_settings = [];
try {
    $stats['db_size'] = $pdo->query("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()
    ")->fetchColumn() . ' MB';

    $stats['total_assessments'] = $pdo->query("SELECT COUNT(*) FROM assessment")->fetchColumn();
    $stats['total_schools']     = $pdo->query("SELECT COUNT(*) FROM school")->fetchColumn();
    $stats['total_activities']  = $pdo->query("SELECT COUNT(*) FROM activity_info")->fetchColumn();
    $stats['total_admins']      = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();

    $stats['recent_assessments'] = $pdo->query("SELECT COUNT(*) FROM assessment WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    $stats['recent_logins']      = $pdo->query("SELECT COUNT(*) FROM user_logs WHERE action = 'login' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();

    $system_settings = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $stats_error = "Error loading statistics: " . $e->getMessage();
}

// ----------------------------
// Recent Logs
// ----------------------------
try {
    $recent_logs = $pdo->query("
        SELECT ul.*, u.username
        FROM user_logs ul
        LEFT JOIN users u ON ul.user_id = u.user_id
        ORDER BY ul.created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_logs = [];
    error_log("Error fetching user logs: " . $e->getMessage());
}

$page_title = "System Status";
include_once 'includes/header.php';
?>

<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

    <!-- Health Checks -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-heart-pulse"></i> System Health Checks</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($health_checks as $check_name => $check): ?>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-<?php
                                        echo $check['status'] === 'healthy' ? 'check-circle-fill text-success' : 
                                            ($check['status'] === 'warning' ? 'exclamation-triangle-fill text-warning' : 'x-circle-fill text-danger');
                                    ?> fs-4 me-3"></i>
                                    <div>
                                        <h6 class="mb-0"><?php echo ucfirst(str_replace('_', ' ', $check_name)); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($check['message']); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics -->
    <div class="row mb-4">
        <?php if (!empty($stats)): ?>
            <?php
            $metrics = [
                ['label' => 'Total Assessments', 'value' => $stats['total_assessments'], 'class' => 'metric-card'],
                ['label' => 'Schools', 'value' => $stats['total_schools'], 'class' => 'bg-success text-white'],
                ['label' => 'Activities', 'value' => $stats['total_activities'], 'class' => 'bg-info text-white'],
                ['label' => 'Admin Users', 'value' => $stats['total_admins'], 'class' => 'bg-warning text-white'],
            ];
            foreach ($metrics as $m): ?>
                <div class="col-md-3">
                    <div class="card <?php echo $m['class']; ?>">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo number_format($m['value']); ?></h3>
                            <p class="mb-0"><?php echo $m['label']; ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- System Info -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header"><h5><i class="bi bi-info-circle"></i> System Information</h5></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><td><strong>PHP Version:</strong></td><td><?php echo PHP_VERSION; ?></td></tr>
                        <tr><td><strong>Database Size:</strong></td><td><?php echo $stats['db_size'] ?? 'N/A'; ?></td></tr>
                        <tr><td><strong>Server Software:</strong></td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td></tr>
                        <tr><td><strong>System Version:</strong></td><td><?php echo $system_settings['database_version'] ?? '1.0'; ?></td></tr>
                        <tr><td><strong>Recent Assessments (7 days):</strong></td><td><?php echo number_format($stats['recent_assessments'] ?? 0); ?></td></tr>
                        <tr><td><strong>Recent Logins (24h):</strong></td><td><?php echo number_format($stats['recent_logins'] ?? 0); ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- System Settings -->
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-sliders"></i> System Configuration</h5></div>
                <div class="card-body">
                    <?php if (!empty($system_settings)): ?>
                        <table class="table table-sm">
                            <?php foreach ($system_settings as $key => $value): ?>
                                <tr><td><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong></td><td><?php echo htmlspecialchars($value); ?></td></tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No system settings found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Logs -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-clock-history"></i> Recent Activity</h5></div>
                <div class="card-body">
                    <?php if (!empty($recent_logs)): ?>
                        <?php foreach ($recent_logs as $log): ?>
                            <div class="log-entry border-bottom pb-2 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span><strong><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong> <?php echo htmlspecialchars($log['action']); ?></span>
                                    <small class="text-muted"><?php echo date('M j, H:i', strtotime($log['created_at'])); ?></small>
                                </div>
                                <?php if (!empty($log['details'])): ?>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($log['details']); ?></small>
                                <?php endif; ?>
                                <small class="text-muted">IP: <?php echo htmlspecialchars($log['ip_address']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No recent activity logs found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/sidebar-highlight.js"></script>
<script>
    // Auto-refresh every 30s
    setTimeout(() => location.reload(), 30000);
</script>
</body>
</html>
