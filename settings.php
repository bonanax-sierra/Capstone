<?php
include 'includes/auth.php';
include 'includes/db.php';

// Require admin authentication
requireAdmin();

$current_user = getCurrentUser(); // should return row from `users`
if (!$current_user) {
    // If no user found, force logout for safety
    header("Location: includes/logout.php");
    exit;
}

// -------------------
// Handle Profile Update
// -------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name              = sanitizeInput($_POST['name'] ?? '');
        $username          = sanitizeInput($_POST['username'] ?? '');
        $email             = sanitizeInput($_POST['email'] ?? '');
        $current_password  = $_POST['current_password'] ?? '';
        $new_password      = $_POST['new_password'] ?? '';
        $confirm_password  = $_POST['confirm_password'] ?? '';

        $errors = [];

        // Validate current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$current_user['user_id']]);
        $stored_password = $stmt->fetchColumn();

        if (!$stored_password || !password_verify($current_password, $stored_password)) {
            $errors[] = "Current password is incorrect";
        }

        // Basic field checks
        if (empty($name)) {
            $errors[] = "Name cannot be empty";
        }

        if (empty($username) || strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters";
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required";
        }

        // Password checks (only if user wants to change)
        if (!empty($new_password)) {
            if (strlen($new_password) < 8) {
                $errors[] = "New password must be at least 8 characters";
            }
            if ($new_password !== $confirm_password) {
                $errors[] = "New passwords do not match";
            }
        }

        // Check for duplicate username/email
        if (empty($errors)) {
            $check = $pdo->prepare("
                SELECT user_id FROM users 
                WHERE (username = ? OR email = ?) AND user_id != ?
            ");
            $check->execute([$username, $email, $current_user['user_id']]);
            if ($check->rowCount() > 0) {
                $errors[] = "Username or email already exists";
            }
        }

        // Process update
        if (empty($errors)) {
            try {
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, username = ?, email = ?, password = ? 
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$name, $username, $email, $hashed_password, $current_user['user_id']]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, username = ?, email = ? 
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$name, $username, $email, $current_user['user_id']]);
                }

                // Update session values
                $_SESSION['username'] = $username;
                $_SESSION['email']    = $email;
                $_SESSION['name']     = $name;

                // Log the action
                logUserAction($pdo, $current_user['user_id'], 'profile_update', 'User updated profile settings');

                $_SESSION['success'] = "Profile updated successfully!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
            }
        } else {
            $_SESSION['errors'] = $errors;
        }

        header("Location: settings.php");
        exit;
    }
}

// -------------------
// Fetch System Statistics
// -------------------
try {
    $stats = [];
    $stats['total_assessments'] = $pdo->query("SELECT COUNT(*) FROM assessment")->fetchColumn();
    $stats['total_schools']     = $pdo->query("SELECT COUNT(*) FROM school")->fetchColumn();
    $stats['total_activities']  = $pdo->query("SELECT COUNT(*) FROM activity_info")->fetchColumn();
    $stats['recent_assessments']= $pdo->query("
        SELECT COUNT(*) FROM assessment 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetchColumn();
} catch (PDOException $e) {
    $stats = [];
    $stats_error = "Error loading statistics: " . $e->getMessage();
}

// -------------------
// Page Layout
// -------------------
$page_title = "Settings";
include_once 'includes/header.php';
?>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="card-header">
            <h3><i class="bi bi-heart-pulse"></i> System Status</h3>
        </div>

        <!-- Alerts -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['errors'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <li><?= htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Settings -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-person-gear"></i> Profile Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?= htmlspecialchars($current_user['name'] ?? ''); ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username"
                                            value="<?= htmlspecialchars($current_user['username'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            value="<?= htmlspecialchars($current_user['email'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h6>Change Password</h6>

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                        <div class="form-text">Leave blank to keep current password</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Statistics + Actions -->
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-header">
                        <h5><i class="bi bi-graph-up"></i> System Statistics</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($stats)): ?>
                            <div class="d-flex justify-content-between"><span>Total Assessments:</span> <strong><?= number_format($stats['total_assessments']); ?></strong></div>
                            <div class="d-flex justify-content-between"><span>Total Schools:</span> <strong><?= number_format($stats['total_schools']); ?></strong></div>
                            <div class="d-flex justify-content-between"><span>Total Activities:</span> <strong><?= number_format($stats['total_activities']); ?></strong></div>
                            <div class="d-flex justify-content-between"><span>Recent (7 days):</span> <strong><?= number_format($stats['recent_assessments']); ?></strong></div>
                        <?php else: ?>
                            <p class="text-center">Statistics unavailable</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="bi bi-tools"></i> System Actions</h5>
                    </div>
                    <div class="card-body">
                        <a href="includes/logout.php" class="btn btn-danger btn-sm w-100" onclick="return confirm('Are you sure you want to logout?');">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar-highlight.js"></script>
</body>
</html>
