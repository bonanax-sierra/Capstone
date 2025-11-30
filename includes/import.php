<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require '../vendor/autoload.php';
require 'db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    if (!empty($file)) {
        try {
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $importedCount = 0;
            $_SESSION['alert'] = ""; // Initialize empty alert message

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                $name = trim($row[0]);
                $address = trim($row[1]);
                $age = (int)$row[2];
                $gender = trim($row[3]);
                $dob = trim($row[4]);
                $civil_status = trim($row[5]);
                $employment_status = trim($row[6]);
                $school_status = trim($row[7]);
                $grade_level = trim($row[8]);
                $school_name = trim($row[9]);

                // Lookup school_id using school name
                $schoolStmt = $pdo->prepare("SELECT school_id FROM school WHERE name = ?");
                $schoolStmt->execute([$school_name]);
                $school = $schoolStmt->fetch();

                if (!$school) {
                    $_SESSION['alert'] .= "❌ Row $i skipped: School '$school_name' not found.\n";
                    continue;
                }

                $school_id = $school['school_id'];

                $problems = json_encode(array_map('trim', explode(',', $row[10])));
                $desired_service = trim($row[11]);
                $pregnant = trim($row[12]);
                $pregnant_age = trim($row[13]);
                $mobile_use = trim($row[14]);
                $mobile_reason = trim($row[15]);
                $mobile_brand = trim($row[16]);
                $risk_category = trim($row[17]);

                // Check for duplicate (based on name + dob + school_id)
                $checkStmt = $pdo->prepare("SELECT * FROM assessment WHERE name = ? AND dob = ? AND school_id = ?");
                $checkStmt->execute([$name, $dob, $school_id]);
                $existingAssessment = $checkStmt->fetch();

                if ($existingAssessment) {
                    $_SESSION['alert'] .= "❌ Row $i skipped: Duplicate found for '$name' at '$school_name'.\n";
                    continue;
                }

                // Insert if not duplicate
                $stmt = $pdo->prepare("INSERT INTO assessment (
                    name, address, age, gender, dob,
                    civil_status, employment_status, school_status,
                    grade_level, school_id, problems, desired_service,
                    pregnant, pregnant_age, mobile_use, mobile_reason,
                    mobile_phone_brand, risk_category
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $name, $address, $age, $gender, $dob,
                    $civil_status, $employment_status, $school_status,
                    $grade_level, $school_id, $problems, $desired_service,
                    $pregnant, $pregnant_age, $mobile_use, $mobile_reason,
                    $mobile_brand, $risk_category
                ]);

                $importedCount++;
            }

            if ($importedCount > 0) {
                $_SESSION['alert'] .= "✅ $importedCount record(s) successfully imported!";
            }

        } catch (Exception $e) {
            $_SESSION['alert'] = "❌ Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['alert'] = "❌ Please select a file.";
    }

    header("Location: ../take_assessment.php");
    exit();
} else {
    $_SESSION['alert'] = "❌ Form was not submitted correctly.";
    header("Location: ../take_assessment.php");
    exit();
}


// <?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// session_start();
// require '../vendor/autoload.php';
// require 'db.php';

// use PhpOffice\PhpSpreadsheet\IOFactory;

// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
//     $file = $_FILES['excel_file']['tmp_name'];

//     if (!empty($file)) {
//         try {
//             $spreadsheet = IOFactory::load($file);
//             $sheet = $spreadsheet->getActiveSheet();
//             $rows = $sheet->toArray();

//             for ($i = 1; $i < count($rows); $i++) {
//                 $row = $rows[$i];

//                 $name = trim($row[0]);
//                 $address = trim($row[1]);
//                 $age = (int)$row[2];
//                 $gender = trim($row[3]);
//                 $dob = trim($row[4]);
//                 $civil_status = trim($row[5]);
//                 $employment_status = trim($row[6]);
//                 $school_status = trim($row[7]);
//                 $grade_level = trim($row[8]);
//                 $school_name = trim($row[9]);

//                 // Lookup school_id using school name
//                 $schoolStmt = $pdo->prepare("SELECT school_id FROM school WHERE name = ?");
//                 $schoolStmt->execute([$school_name]);
//                 $school = $schoolStmt->fetch();

//                 if (!$school) {
//                     $_SESSION['alert'] = "❌ Row $i insert error: School '$school_name' not found.";
//                     continue;
//                 }

//                 $school_id = $school['school_id'];

//                 $problems = json_encode(explode(',', $row[10]));
//                 $desired_service = trim($row[11]);
//                 $pregnant = trim($row[12]);
//                 $pregnant_age = trim($row[13]);
//                 $mobile_use = trim($row[14]);
//                 $mobile_reason = trim($row[15]);
//                 $mobile_brand = trim($row[16]);
//                 $risk_category = trim($row[17]);

//                 // Check for duplicate (based on name + dob + school_id)
//                 $checkStmt = $pdo->prepare("SELECT * FROM assessment WHERE name = ? AND dob = ? AND school_id = ?");
//                 $checkStmt->execute([$name, $dob, $school_id]);
//                 $existingAssessment = $checkStmt->fetch();

//                 if ($existingAssessment) {
//                     $_SESSION['alert'] = "❌ Row $i skipped: Duplicate entry found for '$name' at '$school_name'.";
//                     continue; // skip this row
//                 }


//                 $stmt = $pdo->prepare("INSERT INTO assessment (
//                     name, address, age, gender, dob,
//                     civil_status, employment_status, school_status,
//                     grade_level, school_id, problems, desired_service,
//                     pregnant, pregnant_age, mobile_use, mobile_reason,
//                     mobile_phone_brand, risk_category
//                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

//                 $stmt->execute([
//                     $name,
//                     $address,
//                     $age,
//                     $gender,
//                     $dob,
//                     $civil_status,
//                     $employment_status,
//                     $school_status,
//                     $grade_level,
//                     $school_id,
//                     $problems,
//                     $desired_service,
//                     $pregnant,
//                     $pregnant_age,
//                     $mobile_use,
//                     $mobile_reason,
//                     $mobile_brand,
//                     $risk_category
//                 ]);
//             }

//             $_SESSION['alert'] = "✅ Data successfully imported!";
//         } catch (Exception $e) {
//             $_SESSION['alert'] = "❌ Error: " . $e->getMessage();
//         }
//     } else {
//         $_SESSION['alert'] = "❌ Please select a file.";
//     }

//     header("Location: ../take_assessment.php");
//     exit();
// } else {
//     $_SESSION['alert'] = "❌ Form was not submitted correctly.";
//     header("Location: ../take_assessment.php");
//     exit();
// }


