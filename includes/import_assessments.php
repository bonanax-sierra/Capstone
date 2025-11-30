<?php
session_start();
require '../vendor/autoload.php'; // PhpSpreadsheet
include 'db.php'; // PDO connection

use PhpOffice\PhpSpreadsheet\IOFactory;
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');

// -------------------------
// Helper: call XGBoost API
// -------------------------
function predictRiskBatch(array $rowsData): array {
    $apiUrl = "http://localhost:5000/predict_risk";

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($rowsData));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch) || $httpCode < 200 || $httpCode >= 300) {
        curl_close($ch);
        return array_fill(0, count($rowsData), "Low");
    }
    curl_close($ch);

    $result = json_decode($response, true);
    if (!is_array($result)) return array_fill(0, count($rowsData), "Low");

    $preds = [];
    foreach ($result as $r) {
        $preds[] = is_array($r) ? ($r['xgboost'] ?? $r['prediction'] ?? "Low") : "Low";
    }

    return array_pad($preds, count($rowsData), "Low");
}

// -------------------------
// Helpers
// -------------------------
function safe_trim_val($val, $default = '') {
    if (is_string($val)) return trim($val);
    return ($val === null) ? $default : (string)$val;
}

function safe_date($val) {
    if (empty($val)) return null;
    $ts = strtotime($val);
    return $ts ? date('Y-m-d', $ts) : null;
}

function cell(array $row, array $hx, string $col, $default = '') {
    if (!isset($hx[$col])) return $default;
    $index = $hx[$col];
    $val = $row[$index] ?? $default;
    return safe_trim_val($val, $default);
}

function is_row_empty(array $row, array $hx, array $requiredCols): bool {
    foreach ($requiredCols as $col) {
        if (isset($hx[$col]) && trim((string)($row[$hx[$col]] ?? '')) !== '') return false;
    }
    return true;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['excel_file']['tmp_name'])) {
        $filePath = $_FILES['excel_file']['tmp_name'];
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            $_SESSION['error'] = "Uploaded file has no data.";
            header("Location: ../activity_management.php"); exit;
        }

        // Headers
        $headerRow = array_values($rows[1]);
        $headers = array_map('trim', $headerRow);
        $hx = array_flip($headers);

        $allValues = [];
        $mlInputs = [];
        $activityMap = [];
        $requiredCols = ['First Name', 'Last Name', 'Date of Birth'];

        $rowNumbers = array_keys($rows);
        sort($rowNumbers);

        foreach ($rowNumbers as $rIdx) {
            if ($rIdx == 1) continue;
            $rawRow = array_values($rows[$rIdx]);

            if (is_row_empty($rawRow, $hx, $requiredCols)) continue;

            // --- Activity info ---
            $municipality     = cell($rawRow, $hx, 'Municipality', '');
            $barangay         = cell($rawRow, $hx, 'Barangay', '');
            $activity_title   = cell($rawRow, $hx, 'Activity Title', '');
            $activity_type    = cell($rawRow, $hx, 'Activity Type', '');
            $date_of_activity = safe_date(cell($rawRow, $hx, 'Date of Activity', ''));
            $activityKey = md5($activity_title . '|' . $barangay . '|' . ($date_of_activity ?? ''));

            $activity_id = null;
            if (!empty($activity_title) && !empty($barangay) && !empty($date_of_activity)) {
                if (isset($activityMap[$activityKey])) {
                    $activity_id = $activityMap[$activityKey];
                } else {
                    $stmt = $pdo->prepare("SELECT activity_id FROM activity_info WHERE activity_title = ? AND barangay = ? AND date_of_activity = ?");
                    $stmt->execute([$activity_title, $barangay, $date_of_activity]);
                    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($activity) {
                        $activity_id = (int)$activity['activity_id'];
                    } else {
                        $ins = $pdo->prepare("INSERT INTO activity_info (municipality, barangay, activity_title, activity_type, date_of_activity, status) VALUES (?, ?, ?, ?, ?, 'active')");
                        $ins->execute([$municipality, $barangay, $activity_title, $activity_type, $date_of_activity]);
                        $activity_id = (int)$pdo->lastInsertId();
                    }
                    $activityMap[$activityKey] = $activity_id;
                }
            }

            // --- School info ---
            $school_name = cell($rawRow, $hx, 'School Name', '');
            $school_id = null;
            if (!empty($school_name)) {
                $stmt = $pdo->prepare("SELECT school_id FROM school WHERE name = ?");
                $stmt->execute([$school_name]);
                $school = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($school) {
                    $school_id = (int)$school['school_id'];
                } else {
                    $ins = $pdo->prepare("INSERT INTO school (name) VALUES (?)");
                    $ins->execute([$school_name]);
                    $school_id = (int)$pdo->lastInsertId();
                }
            }

            // --- Personal & assessment info ---
            $first_name     = cell($rawRow, $hx, 'First Name', '');
            $middle_name    = cell($rawRow, $hx, 'Middle Name', '');
            $last_name      = cell($rawRow, $hx, 'Last Name', '');
            $extension_name = cell($rawRow, $hx, 'Extension Name', '');
            $sex            = cell($rawRow, $hx, 'Sex', 'Other');
            $date_of_birth  = safe_date(cell($rawRow, $hx, 'Date of Birth', ''));
            $civil_status   = cell($rawRow, $hx, 'Civil Status', 'Other');
            $school_status  = cell($rawRow, $hx, 'School Status', 'Out of School Youth');
            $highest_educ   = cell($rawRow, $hx, 'Highest Educational Attainment', '');
            $email          = cell($rawRow, $hx, 'Email', '');

            $current_problems = cell($rawRow, $hx, 'Current Problems', '');
            $current_problems = $current_problems !== '' ? array_filter(array_map('trim', explode(',', $current_problems))) : [];

            $desired_services = cell($rawRow, $hx, 'Desired Services', '');
            $desired_services = $desired_services !== '' ? array_filter(array_map('trim', explode(',', $desired_services))) : [];

            $pregnant        = cell($rawRow, $hx, 'Have you ever been pregnant?', 'N/A');
            $family_planning = cell($rawRow, $hx, 'Are you currently using any Family Planning method?', '');
            $impregnated     = cell($rawRow, $hx, 'Have you impregnated someone?', 'N/A');
            $mobile_access   = cell($rawRow, $hx, 'Mobile Accessibility', 'No');
            $mobile_number   = cell($rawRow, $hx, 'Mobile Number', '');
            $mobile_type     = cell($rawRow, $hx, 'Mobile Phone Type', 'Unknown');

            $employment_status = cell($rawRow, $hx, 'Employment Status', '');
            $grade_level       = cell($rawRow, $hx, 'Grade Level', '');
            $pregnant_age      = cell($rawRow, $hx, 'Pregnancy Age', '');
            $address           = cell($rawRow, $hx, 'Address', '');

            $age = 0;
            if (!empty($date_of_birth)) {
                $dobObj = date_create($date_of_birth);
                $today = date_create('today');
                if ($dobObj) $age = (int)date_diff($dobObj, $today)->y;
            }

            $mlInputs[] = [
                "age" => $age,
                "sex" => $sex,
                "civil_status" => $civil_status,
                "school_status" => $school_status,
                "highest_educational_attainment" => $highest_educ,
                "problems" => $current_problems,
                "services" => $desired_services,
                "pregnant" => $pregnant,
                "impregnated_someone" => $impregnated,
                "school_id" => $school_id
            ];

            $allValues[] = [
                $first_name, $middle_name, $last_name, $extension_name, $sex,
                $date_of_birth, $civil_status, $school_status, $highest_educ, $email,
                json_encode($current_problems, JSON_UNESCAPED_UNICODE),
                json_encode($desired_services, JSON_UNESCAPED_UNICODE),
                $pregnant, $family_planning, $impregnated,
                $mobile_access, $mobile_number, $mobile_type,
                null, null, null, null, // predictions
                $school_id, $activity_id,
                $employment_status, $grade_level, $pregnant_age, $address
            ];
        }

        if (empty($allValues)) {
            $_SESSION['error'] = "No valid rows found in Excel.";
            header("Location: ../activity_management.php"); exit;
        }

        // --- Batch API ---
        $predictions = predictRiskBatch($mlInputs);
        foreach ($allValues as $idx => &$row) {
            $pred = $predictions[$idx] ?? "Low";
            $row[18] = $pred;
            $row[19] = $pred;
            $row[20] = $pred;
            $row[21] = $pred;
        }
        unset($row);

        // --- Bulk insert ---
        $pdo->beginTransaction();
        try {
            $columns = [
                'first_name','middle_name','last_name','extension_name','sex',
                'date_of_birth','civil_status','school_status','highest_educational_attainment','email',
                'current_problems','desired_service','pregnant','family_planning_method','impregnated_someone',
                'mobile_accessibility','mobile_number','mobile_phone_type',
                'risk_category','risk_category_random_forest','risk_category_ann','risk_category_xgboost',
                'school_id','activity_id',
                'employment_status','grade_level','pregnant_age','address'
            ];

            $chunkSize = 500;
            $chunks = array_chunk($allValues, $chunkSize);
            foreach ($chunks as $chunk) {
                $placeholders = [];
                $flatValues = [];
                foreach ($chunk as $row) {
                    $placeholders[] = '(' . implode(',', array_fill(0, count($row), '?')) . ')';
                    foreach ($row as $v) $flatValues[] = $v;
                }
                $sql = "INSERT INTO assessment (" . implode(',', $columns) . ") VALUES " . implode(',', $placeholders);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($flatValues);
            }
            $pdo->commit();
            $_SESSION['success'] = "Excel data imported and risk predicted successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please upload an Excel file.";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error importing file: " . $e->getMessage();
}

header("Location: ../activity_management.php");
exit;
?>
