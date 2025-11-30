<?php
/**
 * Emergency Login Recovery Script
 * Use this script to recover access to your admin account
 * Delete this file after use for security
 */

include 'includes/db.php';

$recovery_message = '';
$recovery_error = '';

// Check if recovery is requested
if (isset($_POST['recover_login'])) {
    $username = trim($_POST['username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username)) {
        $recovery_error = "Username is required";
    } elseif (empty($new_password) || strlen($new_password) < 6) {
        $recovery_error = "Password must be at least 6 characters";
    } elseif ($new_password !== $confirm_password) {
        $recovery_error = "Passwords do not match";
    } else {
        try {
            // Check if user exists in admin table
            $check_stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE username = ?");
            $check_stmt->execute([$username]);
            
            if ($check_stmt->rowCount() > 0) {
                // Update password in admin table
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE admin SET password = ?, updated_at = NOW() WHERE username = ?");
                $update_stmt->execute([$hashed_password, $username]);
                $recovery_message = "Password updated successfully in admin table! You can now login.";
            } else {
                // Check if user exists in users table (old structure)
                $check_users = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND role = 'admin'");
                $check_users->execute([$username]);
                
                if ($check_users->rowCount() > 0) {
                    // Update password in users table
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_users = $pdo->prepare("UPDATE users SET password = ? WHERE username = ? AND role = 'admin'");
                    $update_users->execute([$hashed_password, $username]);
                    
                    // Also migrate to admin table
                    $migrate_stmt = $pdo->prepare("INSERT IGNORE INTO admin (username, email, password, admin_token, created_at) SELECT username, email, ?, 'recovered-account', NOW() FROM users WHERE username = ? AND role = 'admin'");
                    $migrate_stmt->execute([$hashed_password, $username]);
                    
                    $recovery_message = "Password updated and account migrated to new system! You can now login.";
                } else {
                    $recovery_error = "Username not found in admin accounts";
                }
            }
        } catch (PDOException $e) {
            $recovery_error = "Database error: " . $e->getMessage();
        }
    }
}

// Get current admin accounts for reference
try {
    $admin_stmt = $pdo->query("SELECT username, email, created_at FROM admin ORDER BY created_at DESC");
    $admin_accounts = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $admin_accounts = [];
}

// Check if users table exists (old structure)
try {
    $users_stmt = $pdo->query("SELECT username, email, role FROM users WHERE role = 'admin' ORDER BY user_id DESC");
    $users_accounts = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users_accounts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Recovery - Risk Analytics</title>
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
        .recovery-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .accounts-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="recovery-container">
        <div class="text-center mb-4">
            <i class="bi bi-shield-exclamation text-warning" style="font-size: 3rem;"></i>
            <h2 class="mt-3">Emergency Login Recovery</h2>
            <p class="text-muted">Reset your admin password to regain access</p>
        </div>
        
        <div class="warning-box">
            <h6><i class="bi bi-exclamation-triangle"></i> Security Notice</h6>
            <p class="mb-0">This is an emergency recovery script. <strong>Delete this file immediately after use</strong> to maintain system security.</p>
        </div>
        
        <?php if ($recovery_message): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($recovery_message); ?>
                <div class="mt-2">
                    <a href="admin_login.php" class="btn btn-success btn-sm">Go to Login Page</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($recovery_error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-x-circle"></i> <?php echo htmlspecialchars($recovery_error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Admin Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
                <div class="form-text">Enter the username you want to recover</div>
            </div>
            
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
                <div class="form-text">Minimum 6 characters</div>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" name="recover_login" class="btn btn-warning w-100">
                <i class="bi bi-key"></i> Reset Password & Recover Access
            </button>
        </form>
        
        <!-- Show existing accounts for reference -->
        <?php if (!empty($admin_accounts) || !empty($users_accounts)): ?>
            <div class="accounts-list">
                <h6><i class="bi bi-people"></i> Existing Admin Accounts</h6>
                
                <?php if (!empty($admin_accounts)): ?>
                    <p class="mb-2"><strong>Admin Table:</strong></p>
                    <ul class="list-unstyled">
                        <?php foreach ($admin_accounts as $admin): ?>
                            <li class="mb-1">
                                <code><?php echo htmlspecialchars($admin['username']); ?></code>
                                <small class="text-muted">(<?php echo htmlspecialchars($admin['email']); ?>)</small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (!empty($users_accounts)): ?>
                    <p class="mb-2 mt-3"><strong>Users Table (Legacy):</strong></p>
                    <ul class="list-unstyled">
                        <?php foreach ($users_accounts as $user): ?>
                            <li class="mb-1">
                                <code><?php echo htmlspecialchars($user['username']); ?></code>
                                <small class="text-muted">(<?php echo htmlspecialchars($user['email']); ?>)</small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                After recovering access, run the database upgrade script to fix compatibility issues.
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
