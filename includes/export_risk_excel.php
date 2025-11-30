<?php
require '../vendor/autoload.php';
include 'auth.php';
include 'db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Require admin/staff authentication
requireStaffOrAdmin();

// ================================
// Fetch assessment data
// ================================
$sql = "
SELECT 
  ai.municipality AS Municipality,
  ai.barangay AS Barangay,
  ai.activity_title AS `Activity Title`,
  ai.activity_type AS `Activity Type`,
  ai.date_of_activity AS `Date of Activity`,
  a.first_name AS `First Name`,
  a.middle_name AS `Middle Name`,
  a.last_name AS `Last Name`,
  a.extension_name AS `Extension Name`,
  a.sex AS `Sex`,
  a.date_of_birth AS `Date of Birth`,
  a.civil_status AS `Civil Status`,
  a.school_status AS `School Status`,
  a.highest_educational_attainment AS `Highest Educational Attainment`,
  a.email AS `Email`,
  a.current_problems AS `Current Problems`,
  a.desired_service AS `Desired Services`,
  a.pregnant AS `Have you ever been pregnant?`,
  a.family_planning_method AS `Are you currently using any Family Planning method?`,
  a.impregnated_someone AS `Have you impregnated someone?`,
  a.mobile_accessibility AS `Mobile Accessibility`,
  a.mobile_number AS `Mobile Number`,
  a.mobile_phone_type AS `Mobile Phone Type`,
  a.risk_category AS `risk_category`
FROM assessment a
LEFT JOIN activity_info ai ON a.activity_id = ai.activity_id
ORDER BY a.created_at DESC
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================================
// Create Spreadsheet
// ================================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Assessment Data');

// Helper function to get Excel column letter
function getColumnLetter($index) {
    $letter = '';
    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $letter = chr(65 + $mod) . $letter;
        $index = intval(($index - $mod) / 26);
    }
    return $letter;
}

// ================================
// Prepare data for Excel
// ================================
if (!empty($rows)) {
    $headers = array_keys($rows[0]);

    // Add Age and Problem Count to headers
    $headers[] = "Age";
    $headers[] = "Num Problems";

    // Write headers
    foreach ($headers as $i => $header) {
        $colLetter = getColumnLetter($i + 1);
        $sheet->setCellValue($colLetter . '1', $header);
        $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
    }

    // Write data rows
    $rowNum = 2;
    foreach ($rows as $dataRow) {
        $colIndex = 1;

        // Compute Age
        $age = null;
        if (!empty($dataRow['Date of Birth'])) {
            $dob = new DateTime($dataRow['Date of Birth']);
            $today = new DateTime();
            $age = $dob->diff($today)->y;
        }

        // Count number of problems
        $numProblems = 0;
        if (!empty($dataRow['Current Problems']) && json_validate($dataRow['Current Problems'])) {
            $decoded = json_decode($dataRow['Current Problems'], true);
            $numProblems = is_array($decoded) ? count($decoded) : 0;
        }

        foreach ($dataRow as $value) {
            // Decode JSON for current_problems
            if (is_string($value) && json_validate($value)) {
                $decoded = json_decode($value, true);
                $value = is_array($decoded) ? implode(", ", $decoded) : $value;
            }
            $colLetter = getColumnLetter($colIndex);
            $sheet->setCellValue($colLetter . $rowNum, $value);
            $colIndex++;
        }

        // Append Age
        $sheet->setCellValue(getColumnLetter($colIndex++) . $rowNum, $age);
        // Append Num Problems
        $sheet->setCellValue(getColumnLetter($colIndex++) . $rowNum, $numProblems);

        $rowNum++;
    }

    // Auto-size columns
    foreach (range('A', $sheet->getHighestColumn()) as $colLetter) {
        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
    }
} else {
    $sheet->setCellValue('A1', 'No data found.');
}

// ================================
// Output Excel file
// ================================
$filename = "Risk_Assessment_Training_" . date("Ymd_His") . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

// ================================
// Helper function to check JSON
// ================================
function json_validate($string) {
    if (!is_string($string)) return false;
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}
