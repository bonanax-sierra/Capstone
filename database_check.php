<?php
/**
 * Database Compatibility Checker
 * This script checks your database structure and identifies compatibility issues
 */

include 'includes/db.php';

$checks = [];
$issues = [];
$recommendations = [];

try {
    // Check if admin table exists and has correct structure
    $admin_check = $pdo->query("SHOW TABLES LIKE 'admin'");
    if ($admin_check->rowCount() > 0) {
        $checks['admin_table'] = '✅ Admin table exists';
        
        // Check admin table columns
        $admin_columns = $pdo->query("SHOW COLUMNS FROM admin");
        $admin_cols = $admin_columns->fetchAll(PDO::FETCH_COLUMN);
        
        $required_admin_cols = ['admin_id', 'username', 'password', 'email'];
        $missing_admin_cols = array_diff($required_admin_cols, $admin_cols);
        
        if (empty($missing_admin_cols)) {
            $checks['admin_structure'] = '✅ Admin table has required columns';
        } else {
            $issues['admin_structure'] = '❌ Admin table missing columns: ' . implode(', ', $missing_admin_cols);
        }
        
        // Check if admin table has data
        $admin_count = $pdo->query("SELECT COUNT(*) FROM admin")->fetchColumn();
        $checks['admin_data'] = "✅ Admin table has $admin_count records";
        
    } else {
        $issues['admin_table'] = '❌ Admin table does not exist';
        $recommendations[] = 'Create admin table or run database upgrade script';
    }
    
    // Check if users table exists (legacy)
    $users_check = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($users_check->rowCount() > 0) {
        $users_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        $checks['users_table'] = "⚠️ Legacy users table exists with $users_count admin records";
        $recommendations[] = 'Migrate admin users from users table to admin table';
    } else {
        $checks['users_table'] = '✅ No legacy users table found';
    }
    
    // Check activity_info table structure
    $activity_check = $pdo->query("SHOW TABLES LIKE 'activity_info'");
    if ($activity_check->rowCount() > 0) {
        $checks['activity_table'] = '✅ Activity_info table exists';
        
        $activity_columns = $pdo->query("SHOW COLUMNS FROM activity_info");
        $activity_cols = $activity_columns->fetchAll(PDO::FETCH_COLUMN);
        
        // Check if it's old structure (has activity_title) or new structure (has activity_name)
        if (in_array('activity_title', $activity_cols)) {
            $issues['activity_structure'] = '⚠️ Activity_info table has old structure (activity_title)';
            $recommendations[] = 'Migrate activity_info table to new structure';
        } elseif (in_array('activity_name', $activity_cols)) {
            $checks['activity_structure'] = '✅ Activity_info table has new structure';
        } else {
            $issues['activity_structure'] = '❌ Activity_info table has unknown structure';
        }
        
        $activity_count = $pdo->query("SELECT COUNT(*) FROM activity_info")->fetchColumn();
        $checks['activity_data'] = "✅ Activity_info table has $activity_count records";
        
    } else {
        $issues['activity_table'] = '❌ Activity_info table does not exist';
        $recommendations[] = 'Create activity_info table';
    }
    
    // Check assessment table
    $assessment_check = $pdo->query("SHOW TABLES LIKE 'assessment'");
    if ($assessment_check->rowCount() > 0) {
        $checks['assessment_table'] = '✅ Assessment table exists';
        
        $assessment_count = $pdo->query("SELECT COUNT(*) FROM assessment")->fetchColumn();
        $checks['assessment_data'] = "✅ Assessment table has $assessment_count records";
        
        // Check if assessment table has activity_id column
        $assessment_columns = $pdo->query("SHOW COLUMNS FROM assessment");
        $assessment_cols = $assessment_columns->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('activity_id', $assessment_cols)) {
            $checks['assessment_activity_link'] = '✅ Assessment table has activity_id column';
        } else {
            $issues['assessment_activity_link'] = '⚠️ Assessment table missing activity_id column';
            $recommendations[] = 'Add activity_id column to assessment table';
        }
        
    } else {
        $issues['assessment_table'] = '❌ Assessment table does not exist';
    }
    
    // Check school table
    $school_check = $pdo->query("SHOW TABLES LIKE 'school'");
    if ($school_check->rowCount() > 0) {
        $school_count = $pdo->query("SELECT COUNT(*) FROM school")->fetchColumn();
        $checks['school_table'] = "✅ School table exists with $school_count records";
    } else {
        $issues['school_table'] = '❌ School table does not exist';
    }
    
    // Check for enhanced tables
    $enhanced_tables = ['admin_logs', 'system_settings', 'backup_history'];
    foreach ($enhanced_tables as $table) {
        $table_check = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($table_check->rowCount() > 0) {
            $checks["enhanced_$table"] = "✅ Enhanced table $table exists";
        } else {
            $issues["enhanced_$table"] = "⚠️ Enhanced table $table missing";
        }
    }
    
} catch (PDOException $e) {
    $issues['database_connection'] = '❌ Database error: ' . $e->getMessage();
}

// Determine overall status
$total_checks = count($checks);
$total_issues = count($issues);
$compatibility_score = $total_checks > 0 ? round(($total_checks / ($total_checks + $total_issues)) * 100) : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database Compatibility Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .check-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .compatibility-score {
            font-size: 3rem;
            font-weight: bold;
        }
        .score-excellent { color: #28a745; }
        .score-good { color: #17a2b8; }
        .score-warning { color: #ffc107; }
        .score-poor { color: #dc3545; }
        .check-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .check-item:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>
    <div class="check-container">
        <div class="text-center mb-4">
            <h1><i class="bi bi-database-check"></i> Database Compatibility Check</h1>
            <p class="text-muted">Checking your database structure for compatibility with the enhanced system</p>
        </div>
        
        <!-- Compatibility Score -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <h5>Compatibility Score</h5>
                <div class="compatibility-score <?php 
                    if ($compatibility_score >= 90) echo 'score-excellent';
                    elseif ($compatibility_score >= 70) echo 'score-good';
                    elseif ($compatibility_score >= 50) echo 'score-warning';
                    else echo 'score-poor';
                ?>"><?php echo $compatibility_score; ?>%</div>
                <p class="text-muted">
                    <?php 
                    if ($compatibility_score >= 90) echo 'Excellent - Your database is fully compatible!';
                    elseif ($compatibility_score >= 70) echo 'Good - Minor issues that can be easily fixed';
                    elseif ($compatibility_score >= 50) echo 'Fair - Some compatibility issues need attention';
                    else echo 'Poor - Significant compatibility issues found';
                    ?>
                </p>
            </div>
        </div>
        
        <div class="row">
            <!-- Successful Checks -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="bi bi-check-circle"></i> Passed Checks (<?php echo count($checks); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($checks)): ?>
                            <?php foreach ($checks as $check): ?>
                                <div class="check-item"><?php echo $check; ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No successful checks</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Issues Found -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="bi bi-exclamation-triangle"></i> Issues Found (<?php echo count($issues); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($issues)): ?>
                            <?php foreach ($issues as $issue): ?>
                                <div class="check-item"><?php echo $issue; ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-success">No issues found!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recommendations -->
        <?php if (!empty($recommendations)): ?>
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5><i class="bi bi-lightbulb"></i> Recommendations</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <?php foreach ($recommendations as $recommendation): ?>
                            <li><?php echo $recommendation; ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="bi bi-tools"></i> Recommended Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2 d-md-flex">
                    <?php if ($total_issues > 0): ?>
                        <a href="login_recovery.php" class="btn btn-warning">
                            <i class="bi bi-key"></i> Emergency Login Recovery
                        </a>
                        <button class="btn btn-info" onclick="runUpgrade()">
                            <i class="bi bi-database-up"></i> Run Database Upgrade
                        </button>
                    <?php endif; ?>
                    
                    <a href="admin_login.php" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right"></i> Try Login
                    </a>
                    
                    <button class="btn btn-secondary" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i> Recheck
                    </button>
                </div>
                
                <?php if ($total_issues > 0): ?>
                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>Quick Fix:</strong> If you can't login, use the Emergency Login Recovery first, 
                            then run the Database Upgrade to fix all compatibility issues.
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function runUpgrade() {
            if (confirm('This will run the database upgrade script. Make sure you have a backup first. Continue?')) {
                // You can implement this to run the upgrade script
                alert('Please run the database upgrade script manually:\n\nmysql -u root -p capstone < includes/database_upgrade.sql');
            }
        }
    </script>
</body>
</html>
