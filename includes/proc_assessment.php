<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "Invalid request.";
    exit;
}

// -----------------------------
// 1. Fetch & sanitize data
// -----------------------------
$assessment = [
    'activity_id' => (int) ($_POST['activity_id'] ?? 0),
    'first_name' => trim((string) ($_POST['first_name'] ?? '')),
    'middle_name' => trim((string) ($_POST['middle_name'] ?? '')),
    'last_name' => trim((string) ($_POST['last_name'] ?? '')),
    'extension_name' => trim((string) ($_POST['extension_name'] ?? '')),
    'sex' => $_POST['sex'] ?? '',
    'date_of_birth' => $_POST['date_of_birth'] ?? '',
    'civil_status' => $_POST['civil_status'] ?? '',
    'employment_status' => $_POST['employment_status'] ?? '',
    'school_status' => $_POST['school_status'] ?? '',
    'grade_level' => trim((string) ($_POST['grade_level'] ?? '')),
    'school_id' => (int) ($_POST['school_id'] ?? 0),
    'current_problems' => $_POST['current_problems'] ?? [],
    'others_detail' => trim((string) ($_POST['others_detail'] ?? '')),
    'desired_service' => $_POST['desired_service'] ?? '',
    'pregnant' => $_POST['pregnant'] ?? 'N/A',
    'pregnant_age' => $_POST['pregnant_age'] ?? null,
    'impregnated' => $_POST['impregnated'] ?? 'N/A',
    'mobile_use' => $_POST['mobile_use'] ?? 'No',
    'mobile_reason' => trim((string) ($_POST['mobile_reason'] ?? '')),
    'mobile_phone_brand' => trim((string) ($_POST['mobile_phone_brand'] ?? '')),
    'address' => trim((string) ($_POST['address'] ?? ''))
];

// -----------------------------
// 2. Validation
// -----------------------------
$errors = [];
if ($assessment['activity_id'] <= 0)
    $errors[] = "Activity ID is required.";
if (!$assessment['first_name'] || !$assessment['last_name'])
    $errors[] = "Full name is required.";
if (!$assessment['sex'])
    $errors[] = "Gender is required.";
if (!$assessment['date_of_birth'] || !strtotime($assessment['date_of_birth']))
    $errors[] = "Valid DOB required.";
if (!$assessment['civil_status'])
    $errors[] = "Civil Status required.";
if (!$assessment['employment_status'])
    $errors[] = "Employment Status required.";
if (!$assessment['school_status'])
    $errors[] = "School Status required.";
if ($assessment['school_id'] <= 0)
    $errors[] = "School is required.";
if (!$assessment['grade_level'])
    $errors[] = "Grade Level required.";
if (count($assessment['current_problems']) === 0)
    $errors[] = "Select at least one problem.";
if (in_array('Others', $assessment['current_problems']) && !$assessment['others_detail'])
    $errors[] = "Details for 'Others' problem are required.";
if (!$assessment['desired_service'])
    $errors[] = "Desired Service required.";

if ($assessment['sex'] === 'Female') {
    if ($assessment['pregnant'] === 'N/A' || !$assessment['pregnant'])
        $errors[] = "Pregnancy status required.";
    if ($assessment['pregnant'] === 'Yes' && !$assessment['pregnant_age'])
        $errors[] = "Pregnancy age required.";
    $assessment['impregnated'] = 'N/A';
} elseif ($assessment['sex'] === 'Male') {
    if ($assessment['impregnated'] === 'N/A' || !$assessment['impregnated'])
        $errors[] = "Impregnation status required.";
    $assessment['pregnant'] = 'N/A';
    $assessment['pregnant_age'] = null;
}

if ($assessment['mobile_use'] === 'Yes' && (!$assessment['mobile_reason'] || !$assessment['mobile_phone_brand'])) {
    $errors[] = "Mobile reason and brand required if mobile is used.";
}

if ($errors) {
    echo "Validation errors: " . implode(", ", $errors);
    exit;
}

// -----------------------------
// 3. Handle 'Others' problem
// -----------------------------
if (in_array('Others', $assessment['current_problems']) && $assessment['others_detail']) {
    $assessment['current_problems'][array_search('Others', $assessment['current_problems'])] = "Others: " . $assessment['others_detail'];
}
$problems_json = json_encode($assessment['current_problems']);

// -----------------------------
// 4. Ensure school exists
// -----------------------------
$stmt = $pdo->prepare("SELECT school_id FROM school WHERE school_id = ?");
$stmt->execute([$assessment['school_id']]);
if (!$stmt->fetch(PDO::FETCH_ASSOC))
    die("Selected school does not exist.");

// -----------------------------
// 5. Insert assessment
// -----------------------------
$sql = "INSERT INTO assessment (
    activity_id, first_name, middle_name, last_name, extension_name,
    sex, date_of_birth, civil_status, employment_status,
    school_status, grade_level, school_id, current_problems,
    desired_service, pregnant, pregnant_age, impregnated_someone,
    mobile_accessibility, mobile_reason, mobile_phone_type, address, created_at
) VALUES (
    :activity_id, :first_name, :middle_name, :last_name, :extension_name,
    :sex, :dob, :civil_status, :employment_status,
    :school_status, :grade_level, :school_id, :current_problems,
    :desired_service, :pregnant, :pregnant_age, :impregnated,
    :mobile_use, :mobile_reason, :mobile_phone_brand, :address, NOW()
)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'activity_id' => $assessment['activity_id'],
    'first_name' => $assessment['first_name'],
    'middle_name' => $assessment['middle_name'],
    'last_name' => $assessment['last_name'],
    'extension_name' => $assessment['extension_name'],
    'sex' => $assessment['sex'],
    'dob' => $assessment['date_of_birth'],
    'civil_status' => $assessment['civil_status'],
    'employment_status' => $assessment['employment_status'],
    'school_status' => $assessment['school_status'],
    'grade_level' => $assessment['grade_level'],
    'school_id' => $assessment['school_id'],
    'current_problems' => $problems_json,
    'desired_service' => $assessment['desired_service'],
    'pregnant' => $assessment['pregnant'],
    'pregnant_age' => $assessment['pregnant_age'],
    'impregnated' => $assessment['impregnated'],
    'mobile_use' => $assessment['mobile_use'],
    'mobile_reason' => $assessment['mobile_reason'],
    'mobile_phone_brand' => $assessment['mobile_phone_brand'],
    'address' => $assessment['address']
]);
$assessment_id = $pdo->lastInsertId();

// -----------------------------
// 6. Call ML API
// -----------------------------
$apiUrl = "http://127.0.0.1:5000/predict_risk";
// Convert 'current_problems' JSON string back to array if needed
$problems_array = json_decode($problems_json, true) ?: [];

// Ensure 'services' is an array
$services_array = $assessment['desired_service'] ? [$assessment['desired_service']] : [];

$payload = [
    [
        'date_of_birth' => $assessment['date_of_birth'],
        'sex' => $assessment['sex'],                    // matches API expected key
        'civil_status' => $assessment['civil_status'],
        'employment_status' => $assessment['employment_status'],
        'school_status' => $assessment['school_status'],
        'grade_level' => $assessment['grade_level'],
        'school_id' => $assessment['school_id'],
        'problems' => $problems_array,                 // must be array
        'services' => $services_array,                 // must be array
        'pregnant' => $assessment['pregnant'],
        'pregnant_age' => $assessment['pregnant_age'],
        'impregnated_someone' => $assessment['impregnated'],
        'mobile_use' => $assessment['mobile_use']
    ]
];


$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
$mlResults = $response ? json_decode($response, true) : [];
curl_close($ch);

// -----------------------------
// 7. Parse ML Result
// -----------------------------
$risk_category = "Unknown";
$risk_percentage = 0;

if (!empty($mlResults[0]['xgboost']) && is_array($mlResults[0]['xgboost'])) {
    $probabilities = $mlResults[0]['xgboost']; // now matches API key
    $risk_category = array_search(max($probabilities), $probabilities);
    $risk_percentage = round(max($probabilities) * 100); // Confidence %
}


// -----------------------------
// 8. Update assessment with ML results
// -----------------------------
$stmt = $pdo->prepare("UPDATE assessment SET risk_category_xgboost = ?, risk_category = ? WHERE assessment_id = ?");
$stmt->execute([$risk_category, $risk_category, $assessment_id]);

// -----------------------------
// 9. Background retraining
// -----------------------------
$trainScript = "../python/classifiers.py";
$cmd = "nohup python3 " . escapeshellarg($trainScript) . " > /dev/null 2>&1 &";
exec($cmd);

// -----------------------------
// 10. Display Bootstrap modal
// -----------------------------
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Assessment Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="modal fade" id="riskModal" tabindex="-1" aria-labelledby="riskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="riskModalLabel">Assessment Result</h5>
                </div>
                <div class="modal-body text-center">
                    <p><strong>Predicted Risk Category:</strong> <?= htmlspecialchars($risk_category) ?></p>
                    <p><strong>Prediction Confidence:</strong> <?= $risk_percentage ?>%</p>
                    <div class="progress mt-3">
                        <div class="progress-bar" role="progressbar" style="width: <?= $risk_percentage ?>%;"
                            aria-valuenow="<?= $risk_percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">Redirecting to validation...</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var riskModal = new bootstrap.Modal(document.getElementById('riskModal'));
        riskModal.show();
        setTimeout(function () {
            window.location.href = "../popcom_validate.php?assessment_id=<?= urlencode($assessment_id) ?>&risk=<?= urlencode($risk_category) ?>&confidence=<?= urlencode($risk_percentage) ?>";
        }, 4000);
    </script>
</body>

</html>