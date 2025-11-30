<?php
require 'db.php';

header('Content-Type: application/json');

try {
    if (isset($_GET['assessment_id'])) {
        $assessment_id = intval($_GET['assessment_id']);

        $sql = "SELECT 
                    a.assessment_id,
                    a.first_name,
                    a.middle_name,
                    a.last_name,
                    a.extension_name,
                    CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name, a.extension_name) AS full_name,
                    TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) AS age,
                    a.sex AS gender,
                    a.civil_status,
                    a.school_status,
                    a.employment_status,
                    a.highest_educational_attainment,
                    a.email,
                    a.grade_level,
                    a.current_problems,
                    a.desired_service,
                    a.pregnant,
                    a.pregnant_age,
                    a.family_planning_method,
                    a.impregnated_someone,
                    a.address,
                    a.mobile_accessibility,
                    a.mobile_number,
                    a.mobile_phone_type,
                    a.mobile_reason,
                    a.risk_category,
                    a.risk_category_validated,
                    a.risk_category_random_forest,
                    a.risk_category_xgboost,
                    a.risk_category_ann,
                    a.created_at,
                    s.name AS school_name,
                    act.activity_title,
                    act.municipality,
                    act.barangay,
                    act.date_of_activity
                FROM assessment a
                LEFT JOIN school s ON a.school_id = s.school_id
                LEFT JOIN activity_info act ON a.activity_id = act.activity_id
                WHERE a.assessment_id = :assessment_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['assessment_id' => $assessment_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // ✅ Decode JSON fields safely
            $data['current_problems'] = !empty($data['current_problems'])
                ? (json_decode($data['current_problems'], true) ?: [])
                : [];

            $data['desired_service'] = !empty($data['desired_service'])
                ? (json_decode($data['desired_service'], true) ?: [])
                : [];

            echo json_encode($data);
        } else {
            echo json_encode(['error' => 'Assessment not found']);
        }
        exit;
    }

    if (isset($_GET['barangay'])) {
        $barangay = $_GET['barangay'];

        $sql = "SELECT 
            a.assessment_id,
            CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name, a.extension_name) AS full_name,
            a.risk_category,
            s.name AS school_name,
            act.barangay,
            a.created_at
        FROM assessment a
        LEFT JOIN school s ON a.school_id = s.school_id
        LEFT JOIN activity_info act ON a.activity_id = act.activity_id
        WHERE act.barangay = :barangay
        ORDER BY a.created_at DESC";


        $stmt = $pdo->prepare($sql);
        $stmt->execute(['barangay' => $barangay]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($data);
        exit;
    }


    if (isset($_GET['activity_id'])) {
        $activity_id = intval($_GET['activity_id']);

        $sql = "SELECT 
                    a.assessment_id,
                    CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name, a.extension_name) AS full_name,
                    TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) AS age,
                    a.sex AS gender,
                    a.risk_category,
                    s.name AS school_name,
                    act.activity_title
                FROM assessment a
                LEFT JOIN school s ON a.school_id = s.school_id
                LEFT JOIN activity_info act ON a.activity_id = act.activity_id
                WHERE a.activity_id = :activity_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['activity_id' => $activity_id]);

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
        exit;
    }

    echo json_encode(['error' => 'Missing parameter']);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
