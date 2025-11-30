<?php
include 'includes/auth.php';
include 'includes/db.php';

// Require admin authentication
requireStaffOrAdmin();

$current_user = getCurrentUser();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle activity actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token";
        header("Location: activity_management.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_activity' || $action === 'update_activity') {
        $municipality = sanitizeInput($_POST['municipality'] ?? '');
        $barangay = sanitizeInput($_POST['barangay'] ?? '');
        $activity_title = sanitizeInput($_POST['activity_title'] ?? '');
        $activity_type = sanitizeInput($_POST['activity_type'] ?? '');
        $date_of_activity = $_POST['date_of_activity'] ?? '';
        $status = in_array($_POST['status'] ?? 'active', ['active', 'completed', 'cancelled']) ? $_POST['status'] : 'active';
        $activity_id = ($action === 'update_activity') ? (int) ($_POST['activity_id'] ?? 0) : 0;

        $errors = [];

        if (empty($activity_title)) {
            $errors[] = "Activity title is required";
        } elseif (strlen($activity_title) > 255) {
            $errors[] = "Activity title must be less than 255 characters";
        }

        if (empty($date_of_activity)) {
            $errors[] = "Date of activity is required";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_activity)) {
            $errors[] = "Invalid date format";
        }

        if (strlen($municipality) > 100) {
            $errors[] = "Municipality must be less than 100 characters";
        }

        if (strlen($barangay) > 100) {
            $errors[] = "Barangay must be less than 100 characters";
        }

        if (strlen($activity_type) > 100) {
            $errors[] = "Activity type must be less than 100 characters";
        }

        if ($action === 'update_activity' && $activity_id <= 0) {
            $errors[] = "Invalid activity ID";
        }

        if (empty($errors)) {
            try {
                if ($action === 'create_activity') {
                    $stmt = $pdo->prepare("
                        INSERT INTO activity_info 
                        (municipality, barangay, activity_title, activity_type, date_of_activity, status, created_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $municipality,
                        $barangay,
                        $activity_title,
                        $activity_type,
                        $date_of_activity ?: null,
                        $status,
                        $current_user['user_id']
                    ]);
                    $_SESSION['success'] = "Activity created successfully!";
                } else {
                    // Verify activity exists before updating
                    $check_stmt = $pdo->prepare("SELECT 1 FROM activity_info WHERE activity_id = ?");
                    $check_stmt->execute([$activity_id]);
                    if ($check_stmt->fetchColumn()) {
                        $stmt = $pdo->prepare("
                            UPDATE activity_info 
                            SET 
                                municipality = ?, 
                                barangay = ?, 
                                activity_title = ?, 
                                activity_type = ?, 
                                date_of_activity = ?, 
                                status = ?, 
                                updated_at = NOW() 
                            WHERE activity_id = ?
                        ");
                        $stmt->execute([
                            $municipality,
                            $barangay,
                            $activity_title,
                            $activity_type,
                            $date_of_activity ?: null,
                            $status,
                            $activity_id
                        ]);
                        $_SESSION['success'] = "Activity updated successfully!";
                    } else {
                        $_SESSION['error'] = "Activity not found";
                    }
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error " . ($action === 'create_activity' ? 'creating' : 'updating') . " activity: " . $e->getMessage();
            }
        } else {
            $_SESSION['errors'] = $errors;
        }

        header("Location: activity_management.php");
        exit;
    }

    if ($action === 'delete_activity') {
        $activity_id = (int) ($_POST['activity_id'] ?? 0);

        if ($activity_id <= 0) {
            $_SESSION['error'] = "Invalid activity ID";
            header("Location: activity_management.php");
            exit;
        }

        try {
            // Begin transaction to ensure atomicity
            $pdo->beginTransaction();

            // Verify activity exists
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_info WHERE activity_id = ?");
            $check_stmt->execute([$activity_id]);
            if (!$check_stmt->fetchColumn()) {
                $pdo->rollBack();
                $_SESSION['error'] = "Activity not found";
                header("Location: activity_management.php");
                exit;
            }

            // Delete associated assessments
            $assessment_stmt = $pdo->prepare("DELETE FROM assessment WHERE activity_id = ?");
            $assessment_stmt->execute([$activity_id]);
            $assessment_count = $assessment_stmt->rowCount();

            // Delete the activity
            $activity_stmt = $pdo->prepare("DELETE FROM activity_info WHERE activity_id = ?");
            $activity_stmt->execute([$activity_id]);

            // Commit transaction
            $pdo->commit();
            $_SESSION['success'] = "Activity and $assessment_count associated assessment(s) deleted successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error deleting activity and assessments: " . $e->getMessage();
        }

        header("Location: activity_management.php");
        exit;
    }
}

// Get all activities with statistics (Read)
try {
    $activities_sql = "SELECT ai.*, 
                              COUNT(a.assessment_id) as assessment_count,
                              SUM(CASE WHEN a.risk_category = 'High' THEN 1 ELSE 0 END) as high_risk_count
                       FROM activity_info ai 
                       LEFT JOIN assessment a ON ai.activity_id = a.activity_id 
                       GROUP BY ai.activity_id 
                       ORDER BY ai.created_at DESC";
    $activities_stmt = $pdo->prepare($activities_sql);
    $activities_stmt->execute();
    $activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $activities = [];
    $_SESSION['error'] = "Error loading activities: " . $e->getMessage();
}

// Get activity for editing if requested
$edit_activity = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    try {
        $edit_stmt = $pdo->prepare("SELECT * FROM activity_info WHERE activity_id = ?");
        $edit_stmt->execute([$edit_id]);
        $edit_activity = $edit_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_activity) {
            $_SESSION['error'] = "Activity not found for editing";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error loading activity for editing: " . $e->getMessage();
    }
}
?>

<?php
$page_title = "Activity Management";
include_once 'includes/header.php';
?>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-title mb-4">
            <h2><i class="bi bi-calendar-event me-2"></i> Activities & Assessments Management</h2>
        </div>

        <!-- Info Alert -->
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <h6><i class="bi bi-info-circle me-2"></i> New Integrated Workflow</h6>
            <p class="mb-2">Assessments are now integrated with activities for better organization:</p>
            <ol class="mb-2">
                <li><strong>Create Activity</strong> - Set up your assessment activity</li>
                <li><strong>Add Assessments</strong> - Click "Add Assessment" for each participant</li>
                <li><strong>View Reports</strong> - Generate reports filtered by activity</li>
            </ol>
            <small class="text-muted">
                This replaces the standalone "Take Assessment" page for better tracking and organization.
                <?php if (file_exists('take_assessment.php')): ?>
                    <a href="remove_duplicate_assessment.php" class="text-decoration-none">Remove old assessment file</a>
                <?php endif; ?>
            </small>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- Display Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['errors'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php unset($_SESSION['errors']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Activity Form -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-plus-circle me-2"></i>
                    <?php echo $edit_activity ? 'Edit Activity' : 'Create New Activity'; ?>
                </h5>
            </div>
            <div class="card-body p-4">
                <!-- Create/Update Activity Form -->
                <form id="activityForm" method="POST" novalidate>
                    <input type="hidden" name="action"
                        value="<?php echo $edit_activity ? 'update_activity' : 'create_activity'; ?>">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <?php if ($edit_activity): ?>
                        <input type="hidden" name="activity_id" value="<?php echo $edit_activity['activity_id']; ?>">
                    <?php endif; ?>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="activity_title" class="form-label fw-bold">Activity Title <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="activity_title" name="activity_title"
                                    value="<?php echo htmlspecialchars($edit_activity['activity_title'] ?? ''); ?>"
                                    required maxlength="255" aria-describedby="activityTitleHelp">
                                <div id="activityTitleHelp" class="form-text">Enter a descriptive title for the activity
                                    (max 255 characters).</div>
                                <div class="invalid-feedback">Activity title is required and must be less than 255
                                    characters.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label匆 <label for="activity_type" class="form-label fw-bold">Activity Type</label>
                                    <input type="text" class="form-control" id="activity_type" name="activity_type"
                                        value="<?php echo htmlspecialchars($edit_activity['activity_type'] ?? ''); ?>"
                                        maxlength="100" aria-describedby="activityTypeHelp">
                                    <div id="activityTypeHelp" class="form-text">Specify the type of activity (optional,
                                        max 100 characters).</div>
                                    <div class="invalid-feedback">Activity type must be less than 100 characters.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="municipality" class="form-label fw-bold">Municipality</label>
                                <input type="text" class="form-control" id="municipality" name="municipality"
                                    value="<?php echo htmlspecialchars($edit_activity['municipality'] ?? ''); ?>"
                                    maxlength="100" aria-describedby="municipalityHelp">
                                <div id="municipalityHelp" class="form-text">Enter the municipality name (optional, max
                                    100 characters).</div>
                                <div class="invalid-feedback">Municipality must be less than 100 characters.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="barangay" class="form-label fw-bold">Barangay</label>
                                <input type="text" class="form-control" id="barangay" name="barangay"
                                    value="<?php echo htmlspecialchars($edit_activity['barangay'] ?? ''); ?>"
                                    maxlength="100" aria-describedby="barangayHelp">
                                <div id="barangayHelp" class="form-text">Enter the barangay name (optional, max 100
                                    characters).</div>
                                <div class="invalid-feedback">Barangay must be less than 100 characters.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="date_of_activity" class="form-label fw-bold">Date of Activity <span
                                class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date_of_activity" name="date_of_activity"
                            value="<?php echo $edit_activity['date_of_activity'] ?? ''; ?>" required
                            aria-describedby="dateHelp">
                        <div id="dateHelp" class="form-text">Select the date of the activity.</div>
                        <div class="invalid-feedback">Please select a valid date.</div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label fw-bold">Status</label>
                        <select class="form-select" id="status" name="status" aria-describedby="statusHelp">
                            <option value="active" <?php echo ($edit_activity['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="completed" <?php echo ($edit_activity['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($edit_activity['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <div id="statusHelp" class="form-text">Select the current status of the activity.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top"
                            title="Save the activity" id="submitButton">
                            <i class="bi bi-check-circle me-2"></i>
                            <span
                                class="button-text"><?php echo $edit_activity ? 'Update Activity' : 'Create Activity'; ?></span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"
                                aria-hidden="true"></span>
                        </button>

                        <!-- Import Button -->
                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal"
                            data-bs-target="#importModal" data-bs-placement="top" title="Import Excel">
                            <i class="bi bi-upload me-2"></i> Import
                        </button>

                        <?php if ($edit_activity): ?>
                            <a href="activity_management.php" class="btn btn-outline-secondary" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="Cancel editing">
                                <i class="bi bi-x-circle me-2"></i> Cancel Edit
                            </a>
                        <?php endif; ?>
                    </div>

                </form>

                <!-- Import Modal -->
                <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="includes/import_assessments.php" method="POST" enctype="multipart/form-data"
                                id="importForm" novalidate>
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="import recogModalLabel">Import Excel File</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="excel_file" class="form-label fw-bold">Choose Excel File</label>
                                        <input type="file" name="excel_file" id="excel_file" class="form-control"
                                            accept=".xls,.xlsx" required aria-describedby="excelHelp">
                                        <div id="excelHelp" class="form-text">Upload an Excel file (.xls or .xlsx) to
                                            import assessments.</div>
                                        <div class="invalid-feedback">Please select a valid Excel file.</div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success" id="importSubmitButton">
                                        <span class="button-text">Upload & Import</span>
                                        <span class="spinner-border spinner-border-sm d-none" role="status"
                                            aria-hidden="true"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activities List -->
        <div class="card shadow-sm">
            <div style="
        background:#ffffff;
        padding:20px 25px;
        border-bottom:1px solid #e3e6ee;
        box-shadow:0 2px 6px rgba(0,0,0,0.05);
        border-radius:12px 12px 0 0;
    ">
                <div style="
            display:flex;
            flex-direction:column;
            row-gap:12px;
            width:100%;
        " class="flex-md-row justify-content-between align-items-md-center">

                    <!-- Title -->
                    <h3 style="
                margin:0;
                display:flex;
                align-items:center;
                font-size:22px;
                font-weight:600;
                color:#2d2d2d;
            " class="d-flex">
                        <i class="bi bi-activity" style="
                    margin-right:10px;
                    font-size:24px;
                    color:#4a6cf7;
                "></i>
                        Activities Overview
                    </h3>

                    <!-- Search Bar -->
                    <div style="
                position:relative;
                width:100%;
                max-width:280px;
            ">
                        <input type="text" id="activitySearch" placeholder="Search activities..." style="
                    width:100%;
                    padding:10px 14px 10px 40px;
                    border:1px solid #d4d9e3;
                    border-radius:8px;
                    font-size:14px;
                    transition:0.2s;
                " onfocus="this.style.borderColor='#4a6cf7';" onblur="this.style.borderColor='#d4d9e3';">

                        <i class="bi bi-search" style="
                    position:absolute;
                    top:50%;
                    left:12px;
                    transform:translateY(-50%);
                    font-size:16px;
                    color:#6c7580;
                "></i>
                    </div>

                </div>
            </div>


            <div class="card-body">
                <?php if (!empty($activities)): ?>
                    <div class="row" id="activitiesContainer">
                        <?php foreach ($activities as $activity): ?>
                            <div class="col-md-6 col-lg-4 mb-4 activity-item">
                                <div class="card activity-card h-100 border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title"><?php echo htmlspecialchars($activity['activity_title']); ?>
                                            </h6>
                                            <span class="badge 
                                                <?php
                                                switch ($activity['status']) {
                                                    case 'active':
                                                        echo 'bg-success';
                                                        break;
                                                    case 'completed':
                                                        echo 'bg-primary';
                                                        break;
                                                    case 'cancelled':
                                                        echo 'bg-danger';
                                                        break;
                                                    default:
                                                        echo 'bg-secondary';
                                                }
                                                ?>">
                                                <?php echo ucfirst($activity['status']); ?>
                                            </span>
                                        </div>

                                        <?php if (!empty($activity['activity_type'])): ?>
                                            <p class="card-text text-muted small">
                                                <?php echo htmlspecialchars(substr($activity['activity_type'], 0, 100)); ?>
                                                <?php if (strlen($activity['activity_type']) > 100)
                                                    echo '...'; ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if (!empty($activity['date_of_activity'])): ?>
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    <?php echo date('M j, Y', strtotime($activity['date_of_activity'])); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($activity['barangay'])): ?>
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-geo-alt me-1"></i>
                                                    <?php echo htmlspecialchars($activity['barangay']); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>

                                        <div class="row text-center mt-3">
                                            <div class="col-6 border-end">
                                                <h6 class="mb-0"><?php echo number_format($activity['assessment_count']); ?>
                                                </h6>
                                                <small class="text-muted">Assessments</small>
                                            </div>
                                            <div class="col-6">
                                                <h6 class="mb-0 text-danger">
                                                    <?php echo number_format($activity['high_risk_count']); ?></h6>
                                                <small class="text-muted">High Risk</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-footer bg-transparent">
                                        <div class="d-grid gap-2">

                                            <!-- Add Assessment Button -->
                                            <a href="activity_assessment.php?activity_id=<?php echo $activity['activity_id']; ?>"
                                                class="btn btn-success btn-sm rounded-pill">
                                                <i class="bi bi-clipboard-plus me-1"></i> Add Assessment
                                            </a>

                                            <!-- Edit / Delete / Reports Buttons -->
                                            <div class="btn-group w-100" role="group">

                                                <a href="?edit=<?php echo $activity['activity_id']; ?>"
                                                    class="btn btn-outline-primary btn-sm rounded-pill">
                                                    <i class="bi bi-pencil me-1"></i> Edit
                                                </a>

                                                <form action="" method="POST" class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this activity?');">
                                                    <input type="hidden" name="action" value="delete_activity">
                                                    <input type="hidden" name="activity_id"
                                                        value="<?php echo $activity['activity_id']; ?>">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                                                        <i class="bi bi-trash me-1"></i> Delete
                                                    </button>
                                                </form>

                                                <a href="enhanced_reports.php?activity_id=<?php echo $activity['activity_id']; ?>"
                                                    class="btn btn-outline-info btn-sm rounded-pill">
                                                    <i class="bi bi-bar-chart me-1"></i> Reports
                                                </a>

                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x fs-1 text-muted"></i>
                        <h5 class="text-muted mt-3">No activities found</h5>
                        <p class="text-muted">Create your first activity to get started.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar-highlight.js"></script>
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function () {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Client-side form validation and loading state
            const forms = document.querySelectorAll('#activityForm, #importForm');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    } else {
                        // Show loading state
                        const submitButton = form.querySelector('[type="submit"]');
                        if (submitButton) {
                            submitButton.disabled = true;
                            const buttonText = submitButton.querySelector('.button-text');
                            const spinner = submitButton.querySelector('.spinner-border');
                            if (buttonText && spinner) {
                                buttonText.classList.add('d-none');
                                spinner.classList.remove('d-none');
                            }
                        }
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });

        document.getElementById('activitySearch').addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const items = document.querySelectorAll('.activity-item');

            items.forEach(item => {
                const title = item.querySelector('.card-title').textContent.toLowerCase();
                item.style.display = title.includes(query) ? '' : 'none';
            });
        });
    </script>
</body>

</html>