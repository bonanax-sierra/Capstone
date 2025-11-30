<?php
include 'includes/auth.php';
include 'includes/db.php';

// Require admin authentication
requireAdmin();

$current_user = getCurrentUser();

// Handle staff actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_staff') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'staff';

        $errors = [];

        if (empty($name)) {
            $errors[] = "Full name is required";
        }

        if (empty($username) || strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters";
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required";
        }

        if (empty($password) || strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }

        if (!in_array($role, ['staff', 'coordinator', 'manager'])) {
            $errors[] = "Invalid role selected";
        }

        // Check for duplicates
        if (empty($errors)) {
            $check = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);
            if ($check->rowCount() > 0) {
                $errors[] = "Username or email already exists";
            }
        }

        if (empty($errors)) {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, role, created_by, created_at) 
                                       VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $username, $email, $hashed_password, $role, $current_user['user_id']]);
                $_SESSION['success'] = "Staff created successfully!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error creating staff: " . $e->getMessage();
            }
        } else {
            $_SESSION['errors'] = $errors;
        }

        header("Location: staff_management.php");
        exit;
    }

    if ($action === 'delete_staff') {
        $user_id = (int) ($_POST['user_id'] ?? 0);

        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role IN ('staff', 'coordinator', 'manager')");
            $stmt->execute([$user_id]);
            $_SESSION['success'] = "Staff deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting staff: " . $e->getMessage();
        }

        header("Location: staff_management.php");
        exit;
    }
}

// Get all staff users
try {
    $staffs_stmt = $pdo->query("SELECT user_id, name, username, email, role, created_at 
                                FROM users 
                                WHERE role IN ('staff', 'coordinator', 'manager') 
                                ORDER BY created_at DESC");
    $staffs = $staffs_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $staffs = [];
    $error_message = "Error loading staffs: " . $e->getMessage();
}
?>

<?php
$page_title = "Staff Management"; // Custom title for this page
include_once 'includes/header.php';
?>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="card-header">
            <h3><i class="bi bi-heart-pulse"></i> Staff Management</h3>
        </div>

        <!-- Display Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['errors'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>

        <!-- Add Staff Button -->
        <div class="mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                <i class="bi bi-person-plus"></i> Add New Staff
            </button>
        </div>

        <!-- Staffs Table -->
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-table"></i> System Staffs</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($staffs)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staffs as $staff): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($staff['user_id']); ?></td>
                                        <td><?php echo htmlspecialchars($staff['name']); ?></td>
                                        <td><?php echo htmlspecialchars($staff['username']); ?></td>
                                        <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                        <td><span
                                                class="badge bg-secondary"><?php echo htmlspecialchars($staff['role']); ?></span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($staff['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;"
                                                onsubmit="return confirm('Are you sure you want to delete this staff?');">
                                                <input type="hidden" name="action" value="delete_staff">
                                                <input type="hidden" name="user_id" value="<?php echo $staff['user_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-people fs-1 text-muted"></i>
                        <p class="text-muted">No staffs found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Staff</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_staff">

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="staff">Staff</option>
                                <option value="coordinator">Coordinator</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar-highlight.js"></script>

</body>

</html>