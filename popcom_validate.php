<?php
session_start();
include 'includes/db.php';

// Page Title
$page_title = "POPCOM Validation";
include 'includes/header.php';

// ---------------------------
// Validate incoming parameters
// ---------------------------
if (!isset($_GET['assessment_id'], $_GET['risk'], $_GET['confidence'])) {
    die("<div class='alert alert-danger m-5'>Invalid access.</div>");
}

$assessment_id = intval($_GET['assessment_id']);
$risk = htmlspecialchars($_GET['risk']);
$confidence = intval($_GET['confidence']);

// Fetch assessment details
$stmt = $pdo->prepare("SELECT * FROM assessment WHERE assessment_id = ?");
$stmt->execute([$assessment_id]);
$assessment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assessment) {
    die("<div class='alert alert-danger m-5'>Assessment not found.</div>");
}

// ---------------------------
// Determine auto-validation from AI
// ---------------------------
$auto_risk = "Low Risk"; // Default fallback
if (stripos($risk, 'high') !== false) {
    $auto_risk = "High Risk";
} elseif (stripos($risk, 'medium') !== false) {
    $auto_risk = "Medium Risk";
} elseif (stripos($risk, 'low') !== false) {
    $auto_risk = "Low Risk";
}
?>

<div class="main-content">
    <?php include_once 'includes/sidebar.php'; ?>
    <div class="container-fluid px-4">

        <div class="card shadow-lg border-0 mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0">
                    <i class="bi bi-shield-check me-2"></i>POPCOM Validation – AI Risk Analysis
                </h4>
            </div>

            <div class="card-body p-4">

                <!-- Assessment Details -->
                <div class="mb-4">
                    <h5 class="fw-bold mb-2">
                        Assessment ID: 
                        <span class="text-primary"><?= $assessment_id ?></span>
                    </h5>
                    <p><strong>Name:</strong> <?= htmlspecialchars($assessment['first_name'] . ' ' . $assessment['middle_name'] . ' ' . $assessment['last_name']) ?></p>
                    <p><strong>Grade Level:</strong> <?= htmlspecialchars($assessment['grade_level']) ?></p>
                </div>

                <!-- AI Result Box -->
                <div class="p-4 rounded shadow-sm bg-light border mb-4">
                    <h6 class="fw-bold">AI Predicted Result</h6>

                    <p class="mt-3">
                        <strong>Risk Category:</strong>
                        <span class="badge bg-dark fs-6 px-3 py-2"><?= $risk ?></span>
                    </p>

                    <p class="mt-2">
                        <strong>Confidence Level:</strong> 
                        <?= $confidence ?>%
                    </p>

                    <div class="progress mt-3" style="height: 20px;">
                        <div 
                            class="progress-bar bg-success" 
                            role="progressbar"
                            style="width: <?= $confidence ?>%;" 
                            aria-valuenow="<?= $confidence ?>" 
                            aria-valuemin="0" 
                            aria-valuemax="100">
                            <?= $confidence ?>%
                        </div>
                    </div>
                </div>

                <!-- POPCOM Validation Form (AI-driven default) -->
                <form method="post" action="includes/validate_save.php" class="mt-4">

                    <input type="hidden" name="assessment_id" value="<?= $assessment_id ?>">
                    <input type="hidden" name="risk_ai" value="<?= $risk ?>">
                    <input type="hidden" name="confidence" value="<?= $confidence ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Validation Decision</label>
                        <select name="risk_validated" class="form-select form-select-lg" required>
                            <option value="">-- Select POPCOM Conclusion --</option>
                            <option value="High Risk" <?= $auto_risk === "High Risk" ? 'selected' : '' ?>>High Risk</option>
                            <option value="Medium Risk" <?= $auto_risk === "Medium Risk" ? 'selected' : '' ?>>Medium Risk</option>
                            <option value="Low Risk" <?= $auto_risk === "Low Risk" ? 'selected' : '' ?>>Low Risk</option>
                        </select>
                        <small class="text-muted">AI recommendation automatically selected. You may override if needed.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Remarks (Optional)</label>
                        <textarea 
                            name="remarks" 
                            rows="3" 
                            class="form-control"
                            placeholder="Add insights, justification, or notes..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100">
                        <i class="bi bi-check-circle me-2"></i>Submit Validation
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>
</body>
</html>
