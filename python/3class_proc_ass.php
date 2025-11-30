<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // -------------------------
    // 1. Collect Form Data
    // -------------------------
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $age = (int)($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $civilStatus = $_POST['civilStatus'] ?? '';
    $employmentStatus = $_POST['employmentStatus'] ?? '';
    $schoolStatus = $_POST['schoolStatus'] ?? '';
    $schoolName = (int)($_POST['schoolName'] ?? 0);
    $gradeLevel = $_POST['gradeLevel'] ?? '';
    $issues = $_POST['issues'] ?? [];
    $othersDetail = $_POST['othersDetail'] ?? '';
    $desiredService = $_POST['desiredService'] ?? '';
    $pregnant = $_POST['pregnant'] ?? '';
    $pregnantAge = $_POST['pregnantAge'] ?? '';
    $mobileUse = $_POST['mobileUse'] ?? '';
    $mobileReason = $_POST['mobileReason'] ?? '';
    $mobilePhoneBrand = $_POST['mobilePhoneBrand'] ?? '';

    // -------------------------
    // 2. Handle 'Others' issue
    // -------------------------
    if (in_array('Others', $issues) && !empty($othersDetail)) {
        $issues[array_search('Others', $issues)] = "Others: $othersDetail";
    }
    $issuesJson = json_encode($issues);

    // -------------------------
    // 3. Insert / Update School
    // -------------------------
    $stmt = $pdo->prepare("SELECT school_id FROM school WHERE school_id = ?");
    $stmt->execute([$schoolName]);
    $school = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($school) {
        $school_id = $school['school_id'];
    } else {
        $insertSchool = $pdo->prepare("INSERT INTO school (name) VALUES (?)");
        $insertSchool->execute([$schoolName]);
        $school_id = $pdo->lastInsertId();
    }

    // -------------------------
    // 4. Insert Assessment
    // -------------------------
    $sql = "INSERT INTO assessment (
                name, address, age, gender, dob,
                civil_status, employment_status, school_status,
                grade_level, school_id, problems, desired_service,
                pregnant, pregnant_age, mobile_use,
                mobile_reason, mobile_phone_brand
            ) VALUES (
                :name, :address, :age, :gender, :dob,
                :civilStatus, :employmentStatus, :schoolStatus,
                :gradeLevel, :school_id, :problems, :desiredService,
                :pregnant, :pregnantAge, :mobileUse,
                :mobileReason, :mobilePhoneBrand
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'name' => $name,
        'address' => $address,
        'age' => $age,
        'gender' => $gender,
        'dob' => $dob,
        'civilStatus' => $civilStatus,
        'employmentStatus' => $employmentStatus,
        'schoolStatus' => $schoolStatus,
        'gradeLevel' => $gradeLevel,
        'school_id' => $school_id,
        'problems' => $issuesJson,
        'desiredService' => $desiredService,
        'pregnant' => $pregnant,
        'pregnantAge' => $pregnantAge,
        'mobileUse' => $mobileUse,
        'mobileReason' => $mobileReason,
        'mobilePhoneBrand' => $mobilePhoneBrand
    ]);

    $assessment_id = $pdo->lastInsertId();

    // -------------------------
    // 5. Call ML API
    // -------------------------
    $api_url = "http://127.0.0.1:5000/predict_risk"; // Flask API URL

    $postData = [
        "age" => $age,
        "gender" => $gender,
        "civil_status" => $civilStatus,
        "employment_status" => $employmentStatus,
        "school_status" => $schoolStatus,
        "grade_level" => $gradeLevel,
        "school_id" => $school_id,
        "problems" => $issues,
        "desired_service" => $desiredService,
        "pregnant" => $pregnant,
        "pregnant_age" => $pregnantAge,
        "mobile_use" => $mobileUse
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    $response = curl_exec($ch);
    curl_close($ch);

    $ml_result = json_decode($response, true);

    if (!empty($ml_result['risk_category'])) {
        $risk_category = $ml_result['risk_category'];

        // -------------------------
        // 6. Update assessment with ML prediction
        // -------------------------
        $update = $pdo->prepare("UPDATE assessment SET risk_category = ? WHERE assessment_id = ?");
        $update->execute([$risk_category, $assessment_id]);
    } else {
        $risk_category = "Unknown";
    }

    // -------------------------
    // 7. Return result
    // -------------------------
    echo "
    <script>
        alert('Assessment saved. ML predicted risk category: $risk_category');
        window.location.href = '../enhanced_reports.php';
    </script>";
    exit();
} else {
    echo "Invalid request.";
}
