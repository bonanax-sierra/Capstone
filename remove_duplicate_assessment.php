<?php
/**
 * Remove Duplicate Assessment File
 * This script helps remove the standalone take_assessment.php since it's now integrated into activities
 */

session_start();
include 'includes/auth.php';
include 'includes/db.php';

// Require admin authentication
requireAdmin();

$current_user = getCurrentUser();

if (isset($_POST['remove_file']) && $_POST['remove_file'] === 'yes') {
    $files_to_remove = [
        'take_assessment.php'
    ];
    
    $removed_files = [];
    $errors = [];
    
    foreach ($files_to_remove as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                $removed_files[] = $file;
            } else {
                $errors[] = "Failed to remove: $file";
            }
        } else {
            $errors[] = "File not found: $file";
        }
    }
    
    // Log the action
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $current_user['admin_id'],
            'remove_duplicate_files',
            'Removed duplicate assessment files: ' . implode(', ', $removed_files),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        // Ignore logging errors
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Remove Duplicate Assessment - Risk Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 600px;
        }
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="text-center mb-4">
            <i class="bi bi-trash text-warning" style="font-size: 3rem;"></i>
            <h2 class="mt-3">Remove Duplicate Assessment File</h2>
            <p class="text-muted">Clean up the standalone assessment file since it's now integrated into activities</p>
        </div>
        
        <?php if (isset($removed_files) && !empty($removed_files)): ?>
            <div class="success-box">
                <h5><i class="bi bi-check-circle text-success"></i> Files Removed Successfully</h5>
                <ul class="mb-0">
                    <?php foreach ($removed_files as $file): ?>
                        <li><?php echo htmlspecialchars($file); ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-3">
                    <a href="activity_management.php" class="btn btn-success">
                        <i class="bi bi-calendar-event"></i> Go to Activities & Assessments
                    </a>
                    <a href="admin_dashboard.php" class="btn btn-primary">
                        <i class="bi bi-house"></i> Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <h6>Errors:</h6>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!isset($removed_files)): ?>
            <div class="warning-box">
                <h5><i class="bi bi-info-circle"></i> About This Action</h5>
                <p><strong>What this does:</strong></p>
                <ul>
                    <li>Removes the standalone <code>take_assessment.php</code> file</li>
                    <li>Prevents confusion between old and new assessment methods</li>
                    <li>Assessments are now integrated into the Activities system</li>
                </ul>
                
                <p><strong>Why remove it:</strong></p>
                <ul>
                    <li>Assessments are now tied to specific activities</li>
                    <li>Better organization and tracking</li>
                    <li>Eliminates duplicate functionality</li>
                    <li>Cleaner navigation menu</li>
                </ul>
                
                <p class="mb-0"><strong>Note:</strong> This action cannot be undone, but you can always recreate the file if needed.</p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-exclamation-triangle"></i> Confirm Removal</h5>
                </div>
                <div class="card-body">
                    <p>Are you sure you want to remove the duplicate assessment file?</p>
                    
                    <form method="POST" onsubmit="return confirm('Are you sure you want to remove the standalone assessment file? This action cannot be undone.');">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirm_removal" required>
                                <label class="form-check-label" for="confirm_removal">
                                    I understand that assessments are now integrated into activities and I want to remove the duplicate file
                                </label>
                            </div>
                        </div>
                        
                        <input type="hidden" name="remove_file" value="yes">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-trash"></i> Remove Duplicate File
                            </button>
                            <a href="activity_management.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                <strong>New Workflow:</strong> Go to Activities → Select Activity → Add Assessment
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
