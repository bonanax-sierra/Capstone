<?php
require 'db.php';
session_start();
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    if (empty($_POST['assessment_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing assessment ID']);
        exit;
    }

    $assessment_id = intval($_POST['assessment_id']);
    $user_id = $_SESSION['user_id'] ?? null; // ✅ who made the change (null if not logged in)

    // ✅ Editable fields
    $fields = [
        'first_name',
        'middle_name',
        'last_name',
        'extension_name',
        'sex',
        'date_of_birth',
        'civil_status',
        'school_status',
        'employment_status',
        'highest_educational_attainment',
        'email',
        'grade_level',
        'current_problems',
        'desired_service',
        'pregnant',
        'pregnant_age',
        'family_planning_method',
        'impregnated_someone',
        'address',
        'mobile_accessibility',
        'mobile_number',
        'mobile_phone_type',
        'mobile_reason'
    ];


    // ✅ Fetch old data before update
    $stmtOld = $pdo->prepare("SELECT " . implode(', ', $fields) . " FROM assessment WHERE assessment_id = ?");
    $stmtOld->execute([$assessment_id]);
    $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

    if (!$oldData) {
        echo json_encode(['success' => false, 'message' => 'Assessment not found']);
        exit;
    }

    // ✅ Build update
    $setParts = [];
    $params = ['assessment_id' => $assessment_id];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $setParts[] = "$field = :$field";
            $params[$field] = $_POST[$field];
        }
    }

    // ✅ Handle JSON column properly
    if (isset($_POST['current_problems'])) {
        $problems = $_POST['current_problems'];
        if (!is_array($problems)) {
            $problems = array_map('trim', explode(',', $problems));
        }
        $params['current_problems'] = json_encode($problems, JSON_UNESCAPED_UNICODE);
    }

    if (empty($setParts)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }

    // ✅ Perform update
    $sql = "UPDATE assessment SET " . implode(', ', $setParts) . " WHERE assessment_id = :assessment_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // ✅ Compare old vs new values and insert history
    $historyStmt = $pdo->prepare("
        INSERT INTO assessment_history (assessment_id, field_name, old_value, new_value, changed_by)
        VALUES (:assessment_id, :field_name, :old_value, :new_value, :changed_by)
    ");

    foreach ($fields as $field) {
        if (array_key_exists($field, $params)) {
            $oldVal = $oldData[$field] ?? null;
            $newVal = $params[$field];

            // Convert arrays to JSON for proper comparison
            if (is_array($oldVal)) $oldVal = json_encode($oldVal);
            if (is_array($newVal)) $newVal = json_encode($newVal);

            if ($oldVal != $newVal) {
                $historyStmt->execute([
                    'assessment_id' => $assessment_id,
                    'field_name' => $field,
                    'old_value' => $oldVal,
                    'new_value' => $newVal,
                    'changed_by' => $user_id
                ]);
            }
        }
    }

    // ✅ Fetch updated fields for ML API
    $stmt = $pdo->prepare("
        SELECT 
            TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) AS age,
            sex,
            school_status,
            employment_status,
            highest_educational_attainment,
            civil_status,
            desired_service,
            pregnant,
            impregnated_someone,
            family_planning_method,
            mobile_accessibility,
            mobile_phone_type
        FROM assessment
        WHERE assessment_id = ?
    ");
    $stmt->execute([$assessment_id]);
    $dataForAPI = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dataForAPI) {
        echo json_encode(['success' => false, 'message' => 'Assessment not found after update']);
        exit;
    }

    // ✅ Call prediction API
    $apiUrl = "http://127.0.0.1:5000/predict_risk";
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataForAPI));
    $response = curl_exec($ch);
    curl_close($ch);

    $apiResult = json_decode($response, true);

    // ✅ Update risk results
    if (isset($apiResult['risk_category'])) {
        $updateML = $pdo->prepare("
            UPDATE assessment 
            SET 
                risk_category = :risk_category,
                risk_category_ann = :risk_category_ann,
                risk_category_random_forest = :risk_category_random_forest,
                risk_category_xgboost = :risk_category_xgboost
            WHERE assessment_id = :assessment_id
        ");
        $updateML->execute([
            'risk_category' => $apiResult['risk_category'] ?? null,
            'risk_category_ann' => $apiResult['risk_category_ann'] ?? null,
            'risk_category_random_forest' => $apiResult['risk_category_random_forest'] ?? null,
            'risk_category_xgboost' => $apiResult['risk_category_xgboost'] ?? null,
            'assessment_id' => $assessment_id
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Assessment updated and history recorded successfully',
        'api_result' => $apiResult ?? null
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
