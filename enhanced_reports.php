<?php
include 'includes/auth.php';
include 'includes/db.php';

// Require admin/staff authentication
requireStaffOrAdmin();

$current_user = getCurrentUser();

// Initialize filters
$filters = [];
$where_conditions = [];
$params = [];

// Apply filters if provided
if (!empty($_GET['school'])) {
    $filters['school'] = $_GET['school'];
    $where_conditions[] = "s.name = ?";
    $params[] = $_GET['school'];
}

if (!empty($_GET['risk'])) {
    $filters['risk'] = $_GET['risk'];
    $where_conditions[] = "a.risk_category = ?";
    $params[] = $_GET['risk'];
}

if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
    $where_conditions[] = "DATE(a.created_at) >= ?";
    $params[] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
    $where_conditions[] = "DATE(a.created_at) <= ?";
    $params[] = $_GET['date_to'];
}

if (!empty($_GET['activity_id'])) {
    $filters['activity_id'] = (int) $_GET['activity_id'];
    $where_conditions[] = "a.activity_id = ?";
    $params[] = $filters['activity_id'];
}

// Build main query
$sql = "SELECT a.*, s.name AS school_name, ai.activity_title, ai.barangay 
        FROM assessment a
        LEFT JOIN school s ON a.school_id = s.school_id
        LEFT JOIN activity_info ai ON a.activity_id = ai.activity_id";

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY a.created_at DESC";

// Pagination
$page = (int) ($_GET['page'] ?? 1);
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Get total records for pagination
$count_sql = str_replace("SELECT a.*, s.name AS school_name, ai.activity_title, ai.barangay", "SELECT COUNT(*)", $sql);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Limit query for pagination
$sql .= " LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch schools for filters
$schools_sql = "SELECT DISTINCT name FROM school ORDER BY name";
$schools_stmt = $pdo->prepare($schools_sql);
$schools_stmt->execute();
$schools = $schools_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch activities for filters
$activities_sql = "SELECT activity_id, activity_title FROM activity_info ORDER BY activity_title";
$activities_stmt = $pdo->prepare($activities_sql);
$activities_stmt->execute();
$activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary statistics
$stats_sql = "SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN risk_category = 'High' THEN 1 ELSE 0 END) AS high_risk,
    SUM(CASE WHEN risk_category = 'Medium' THEN 1 ELSE 0 END) AS medium_risk,
    SUM(CASE WHEN risk_category = 'Low' THEN 1 ELSE 0 END) AS low_risk
    FROM assessment a
    LEFT JOIN school s ON a.school_id = s.school_id";

if (!empty($where_conditions)) {
    $stats_sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$page_title = "Reports";
include_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .modal-content {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            border-bottom: none;
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: none;
            padding: 1rem 1.5rem;
        }

        .risk-badge {
            padding: 0.5em 1em;
            font-size: 0.9em;
        }

        .stats-card {
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body class="bg-light">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content p-4 p-md-5">
        <!-- Page Header -->
        <div
            style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; padding:1rem 1.5rem; background:#f8f9fa; border-radius:0.75rem; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
            <div>
                <h2
                    style="font-weight:700; color:#0d6efd; margin-bottom:0.3rem; display:flex; align-items:center; gap:0.5rem;">
                    <i class="bi bi-bar-chart-fill" style="font-size:1.5rem;"></i>
                    Assessment Reports & Analytics
                </h2>
                <p style="color:#6c757d; margin:0; font-size:0.95rem;">Monitor and analyze youth risk assessment data in
                    real-time</p>
            </div>
            <div style="text-align:right;">
                <span
                    style="font-size:1.25rem; font-weight:600; color:#343a40;"><?php echo date('F j, Y'); ?></span><br>
                <small style="color:#6c757d;">Last updated just now</small>
            </div>
        </div>

        <!-- Eye-Friendly Summary Stats Cards using Bootstrap -->
        <div class="row g-4 mb-5">

            <!-- Total Assessments -->
            <div class="col-xl-3 col-md-6">
                <div class="card text-dark h-100 shadow rounded-4"
                    style="background: linear-gradient(135deg, #d4f1f4, #a9e4f7);">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-people fs-1 opacity-75"></i>
                        </div>
                        <div>
                            <h4 class="mb-1"><?php echo number_format($stats['total']); ?></h4>
                            <p class="mb-0">Total Assessments</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- High Risk -->
            <div class="col-xl-3 col-md-6">
                <div class="card text-dark h-100 shadow rounded-4"
                    style="background: linear-gradient(135deg, #fddde6, #f9c2d1);">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-exclamation-triangle-fill fs-1"></i>
                        </div>
                        <div>
                            <h4 class="mb-1"><?php echo number_format($stats['high_risk']); ?></h4>
                            <p class="mb-0">High Risk</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medium Risk -->
            <div class="col-xl-3 col-md-6">
                <div class="card text-dark h-100 shadow rounded-4"
                    style="background: linear-gradient(135deg, #fff3e6, #ffe0b2);">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-exclamation-circle-fill fs-1"></i>
                        </div>
                        <div>
                            <h4 class="mb-1"><?php echo number_format($stats['medium_risk']); ?></h4>
                            <p class="mb-0">Medium Risk</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Risk -->
            <div class="col-xl-3 col-md-6">
                <div class="card text-dark h-100 shadow rounded-4"
                    style="background: linear-gradient(135deg, #dff7e1, #b8e3c3);">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-check-circle-fill fs-1"></i>
                        </div>
                        <div>
                            <h4 class="mb-1"><?php echo number_format($stats['low_risk']); ?></h4>
                            <p class="mb-0">Low Risk</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Filters Section -->
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body">
                <h5 class="card-title mb-4">
                    <i class="bi bi-funnel-fill text-primary me-2"></i>Filter Records
                </h5>
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-medium">Activity</label>
                        <select name="activity_id" class="form-select form-select-lg">
                            <option value="">All Activities</option>
                            <?php foreach ($activities as $activity): ?>
                                <option value="<?= $activity['activity_id'] ?>" <?= ($filters['activity_id'] ?? '') == $activity['activity_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($activity['activity_title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-medium">Risk Level</label>
                        <select name="risk" class="form-select form-select-lg">
                            <option value="">All Levels</option>
                            <option value="High" <?= ($filters['risk'] ?? '') === 'High' ? 'selected' : '' ?>>High Risk
                            </option>
                            <option value="Medium" <?= ($filters['risk'] ?? '') === 'Medium' ? 'selected' : '' ?>>Medium
                                Risk</option>
                            <option value="Low" <?= ($filters['risk'] ?? '') === 'Low' ? 'selected' : '' ?>>Low Risk
                            </option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-medium">From Date</label>
                        <input type="date" name="date_from" class="form-control form-control-lg"
                            value="<?= $filters['date_from'] ?? '' ?>">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-medium">To Date</label>
                        <input type="date" name="date_to" class="form-control form-control-lg"
                            value="<?= $filters['date_to'] ?? '' ?>">
                    </div>
                    <div class="col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-search"></i> Apply Filters
                        </button>
                        <a href="?" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Assessment Table -->
        <div class="card border-0 shadow-lg">
            <div
                class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center py-4">
                <h4 class="mb-0" style="color:black;">
                    <i class="bi bi-table me-2"></i>Assessment Records
                </h4>
                <div>
                    <span class="badge bg-white text-primary fs-6 px-4 py-2">
                        <i class="bi bi-person-lines-fill me-2"></i>
                        <?= number_format($total_records) ?> Total Records
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <?php if (!empty($assessments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-dark fw-semibold">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Full Name</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>School</th>
                                    <th>Activity</th>
                                    <th>Risk Level</th>
                                    <th>Problems</th>
                                    <th>Date Assessed</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-dark">
                                <?php foreach ($assessments as $a):
                                    $age = !empty($a['date_of_birth']) ? (new DateTime())->diff(new DateTime($a['date_of_birth']))->y : '—';
                                    $problems = json_decode($a['current_problems'] ?? '[]', true);
                                    $problemText = is_array($problems) && count($problems) ?
                                        htmlspecialchars($problems[0]) . (count($problems) > 1 ? " <span class='text-muted small'>+" . (count($problems) - 1) . " more</span>" : "")
                                        : "<em class='text-muted'>None</em>";
                                    $riskBadge = match ($a['risk_category']) {
                                        'High' => 'bg-danger',
                                        'Medium' => 'bg-warning text-dark',
                                        'Low' => 'bg-success',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <tr class="border-start border-3 border-light" id="row-<?= $a['assessment_id'] ?>">
                                        <td class="ps-4 fw-medium">#<?= $a['assessment_id'] ?></td>
                                        <td class="fw-semibold">
                                            <?= htmlspecialchars(trim("{$a['first_name']} {$a['middle_name']} {$a['last_name']} {$a['extension_name']}")) ?>
                                        </td>
                                        <td><?= $age ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= ucfirst($a['sex'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($a['school_name'] ?? '—') ?></td>
                                        <td class="text-truncate" style="max-width: 150px;">
                                            <?= htmlspecialchars($a['activity_title'] ?? '—') ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $riskBadge ?> px-3 py-2 fw-semibold">
                                                <?= $a['risk_category'] ?? 'Unknown' ?>
                                            </span>
                                        </td>
                                        <td class="small"><?= $problemText ?></td>
                                        <td class="text-muted small">
                                            <?= date('M d, Y', strtotime($a['created_at'])) ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-outline-primary btn-sm view-btn"
                                                    data-id="<?= $a['assessment_id'] ?>" title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-success btn-sm edit-btn"
                                                    data-id="<?= $a['assessment_id'] ?>" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm delete-btn"
                                                    data-id="<?= $a['assessment_id'] ?>" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer bg-white border-top-0 py-4">
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                            href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                            Previous
                                        </a>
                                    </li>
                                    <?php
                                    $start = max(1, $page - 2);
                                    $end = min($total_pages, $page + 2);
                                    for ($i = $start; $i <= $end; $i++):
                                        ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link"
                                                href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                            href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                            Next
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <div class="text-center text-muted small mt-3">
                                Showing page <?= $page ?> of <?= $total_pages ?>
                                (<?= number_format($total_records) ?> total records)
                            </div>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted opacity-50"></i>
                        <h4 class="mt-4 text-muted">No assessment records found</h4>
                        <p class="text-muted">Try adjusting your filters or add new assessments.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- View Assessment Modal -->
    <div class="modal fade" id="viewAssessmentModal" tabindex="-1" aria-labelledby="viewAssessmentLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-person-fill me-2"></i>Assessment Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewAssessmentDetails">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-3">Loading details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Assessment Modal -->
    <div class="modal fade" id="editAssessmentModal" tabindex="-1" aria-labelledby="editAssessmentLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0">

                <!-- Modal Header -->
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i>Edit Assessment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body" id="editAssessmentDetails">
                    <div class="text-center py-5">
                        <div class="spinner-border text-warning" role="status"></div>
                        <p class="mt-3 mb-0">Loading assessment data...</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-warning" id="saveAssessmentBtn">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this assessment? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // 🟦 VIEW MODAL
            document.querySelectorAll('.view-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const assessmentId = button.dataset.id;
                    const modalEl = document.getElementById('viewAssessmentModal');
                    const detailsEl = document.getElementById('viewAssessmentDetails');
                    const modal = new bootstrap.Modal(modalEl);

                    // Loading spinner
                    detailsEl.innerHTML = `
                    <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3">Loading details...</p>
                    </div>`;
                    modal.show();

                    fetch(`includes/get_assessment.php?assessment_id=${assessmentId}`)
                        .then(res => res.json())
                        .then(data => {
                            if (!data || data.error) {
                                detailsEl.innerHTML = `<p class="text-danger text-center">Assessment not found.</p>`;
                                return;
                            }

                            const problems = Array.isArray(data.current_problems) && data.current_problems.length ?
                                `<ul class="list-unstyled">${data.current_problems.map(p => `<li><i class="bi bi-dot text-primary"></i>${p}</li>`).join('')}</ul>` :
                                "<em>No problems recorded.</em>";

                            detailsEl.innerHTML = `
                            <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <tbody>
                                <tr><th>Full Name</th><td>${data.full_name ?? 'N/A'}</td></tr>
                                <tr><th>Age</th><td>${data.age ?? 'N/A'}</td></tr>
                                <tr><th>Gender</th><td>${data.gender ?? 'N/A'}</td></tr>
                                <tr><th>Civil Status</th><td>${data.civil_status ?? 'N/A'}</td></tr>
                                <tr><th>School Status</th><td>${data.school_status ?? 'N/A'}</td></tr>
                                <tr><th>Employment Status</th><td>${data.employment_status ?? 'N/A'}</td></tr>
                                <tr><th>Educational Attainment</th><td>${data.highest_educational_attainment ?? 'N/A'}</td></tr>
                                <tr><th>Email</th><td>${data.email ?? 'N/A'}</td></tr>
                                <tr><th>Desired Service</th><td>${data.desired_service ?? 'N/A'}</td></tr>
                                <tr><th>Validated Risk</th>
                                    <td>
                                        <span class="badge 
                                            ${data.risk_category_validated === 'High' ? 'bg-danger' :
                                                                    data.risk_category_validated === 'Medium' ? 'bg-warning' :
                                                                        data.risk_category_validated === 'Low' ? 'bg-success' : 'bg-secondary'}">
                                            ${data.risk_category_validated ?? 'N/A'}
                                        </span>
                                    </td>
                                </tr>
                                <tr><th>Risk Category</th>
                                    <td><span class="badge ${data.risk_category === 'High' ? 'bg-danger' : data.risk_category === 'Medium' ? 'bg-warning' : 'bg-success'}">${data.risk_category ?? 'N/A'}</span></td>
                                </tr>
                                <tr><th>Activity</th><td>${data.activity_title ?? 'N/A'}</td></tr>
                                <tr><th>Municipality</th><td>${data.municipality ?? 'N/A'}</td></tr>
                                <tr><th>Barangay</th><td>${data.barangay ?? 'N/A'}</td></tr>
                                <tr><th>Date Created</th><td>${new Date(data.created_at).toLocaleString()}</td></tr>
                                <tr><th>Problems</th><td>${problems}</td></tr>
                                </tbody>
                            </table>
                            </div>`;
                        })
                        .catch(err => {
                            detailsEl.innerHTML = `<p class="text-danger text-center">Failed to load details.</p>`;
                            console.error(err);
                        });
                });
            });


            // 🟨 EDIT MODAL
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const assessmentId = button.dataset.id;
                    const modalEl = document.getElementById('editAssessmentModal');
                    const detailsEl = document.getElementById('editAssessmentDetails');
                    const modal = new bootstrap.Modal(modalEl);

                    // Show loading spinner
                    detailsEl.innerHTML = `
                    <div class="text-center p-4">
                        <div class="spinner-border text-warning" role="status"></div>
                        <p class="mt-3">Loading assessment data...</p>
                    </div>`;
                    modal.show();

                    // Fetch assessment data
                    fetch(`includes/get_assessment.php?assessment_id=${assessmentId}`)
                        .then(res => res.json())
                        .then(data => {
                            if (!data || data.error) {
                                detailsEl.innerHTML = `<p class="text-danger text-center">Assessment not found.</p>`;
                                return;
                            }

                            // Render editable form
                            detailsEl.innerHTML = `
                <form id="editAssessmentForm" class="needs-validation" novalidate>
                    <input type="hidden" name="assessment_id" value="${data.assessment_id}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" value="${data.first_name ?? ''}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name" value="${data.middle_name ?? ''}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" value="${data.last_name ?? ''}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Extension</label>
                            <input type="text" class="form-control" name="extension_name" value="${data.extension_name ?? ''}" placeholder="Jr., Sr., III">
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" value="${data.date_of_birth ?? ''}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender *</label>
                            <select class="form-select" name="sex" required>
                                <option value="">Select Gender</option>
                                <option value="Male" ${data.sex === 'Male' ? 'selected' : ''}>Male</option>
                                <option value="Female" ${data.sex === 'Female' ? 'selected' : ''}>Female</option>
                                <option value="Other" ${data.sex === 'Other' ? 'selected' : ''}>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2">${data.address ?? ''}</textarea>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label class="form-label">Civil Status *</label>
                            <select class="form-select" name="civil_status" required>
                                <option value="">Choose...</option>
                                <option value="Single" ${data.civil_status === 'Single' ? 'selected' : ''}>Single</option>
                                <option value="Married" ${data.civil_status === 'Married' ? 'selected' : ''}>Married</option>
                                <option value="Living in" ${data.civil_status === 'Living in' ? 'selected' : ''}>Living in</option>
                                <option value="Not Living-in" ${data.civil_status === 'Not Living-in' ? 'selected' : ''}>Not Living-in</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Employment Status *</label>
                            <select class="form-select" name="employment_status" required>
                                <option value="">Choose...</option>
                                <option value="Employed" ${data.employment_status === 'Employed' ? 'selected' : ''}>Employed</option>
                                <option value="Unemployed" ${data.employment_status === 'Unemployed' ? 'selected' : ''}>Unemployed</option>
                                <option value="Underemployed" ${data.employment_status === 'Underemployed' ? 'selected' : ''}>Underemployed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">School Status *</label>
                            <select class="form-select" name="school_status" required>
                                <option value="">Choose...</option>
                                <option value="Out of School Youth" ${data.school_status === 'Out of School Youth' ? 'selected' : ''}>Out of School Youth</option>
                                <option value="In School Youth" ${data.school_status === 'In School Youth' ? 'selected' : ''}>In School Youth</option>
                                <option value="Vocational/Technical" ${data.school_status === 'Vocational/Technical' ? 'selected' : ''}>Vocational/Technical</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Update Assessment
                        </button>
                    </div>
                </form>`;

                            // 🟢 Handle submission via AJAX
                            const editForm = document.getElementById('editAssessmentForm');
                            editForm.addEventListener('submit', e => {
                                e.preventDefault();

                                const formData = new FormData(editForm);

                                fetch('includes/update_assessment.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                    .then(res => res.json())
                                    .then(response => {
                                        if (response.success) {
                                            detailsEl.innerHTML = `
                                <div class="alert alert-success text-center">
                                    <i class="bi bi-check-circle"></i> ${response.message}
                                </div>`;
                                            setTimeout(() => location.reload(), 1200);
                                        } else {
                                            detailsEl.innerHTML = `
                                <div class="alert alert-danger text-center">
                                    <i class="bi bi-x-circle"></i> ${response.message}
                                </div>`;
                                        }
                                    })
                                    .catch(err => {
                                        console.error(err);
                                        detailsEl.innerHTML = `<div class="alert alert-danger text-center">Update failed.</div>`;
                                    });
                            });
                        })
                        .catch(err => {
                            detailsEl.innerHTML = `<p class="text-danger text-center">Failed to load form.</p>`;
                            console.error(err);
                        });
                });
            });

            // 🧡 Save Handler
            document.getElementById('saveAssessmentBtn').addEventListener('click', () => {
                const form = document.getElementById('editAssessmentForm');
                if (!form) return;
                fetch('includes/update_assessment.php', {
                    method: 'POST',
                    body: new FormData(form)
                })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            alert('✅ Assessment updated successfully!');
                            bootstrap.Modal.getInstance(document.getElementById('editAssessmentModal')).hide();
                            location.reload();
                        } else {
                            alert('❌ Update failed: ' + (result.message || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('An error occurred while updating.');
                    });
            });

        });

        document.addEventListener('DOMContentLoaded', () => {
            let assessmentToDelete = null; // Store the assessment ID to delete
            const deleteModalEl = document.getElementById('deleteConfirmModal');
            const deleteModal = new bootstrap.Modal(deleteModalEl);
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    assessmentToDelete = btn.dataset.id; // Save ID
                    deleteModal.show(); // Show the modal
                });
            });

            confirmDeleteBtn.addEventListener('click', () => {
                if (!assessmentToDelete) return;

                fetch('includes/delete_assessment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ assessment_id: assessmentToDelete })
                })
                    .then(res => res.json())
                    .then(response => {
                        if (response.success) {
                            const row = document.getElementById('row-' + assessmentToDelete);
                            if (row) row.remove();
                            bootstrap.Modal.getInstance(deleteModalEl).hide();
                            assessmentToDelete = null;
                        } else {
                            alert('Failed to delete assessment: ' + (response.message || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Error occurred while deleting assessment.');
                    });
            });
        });

    </script>
</body>




</html>