<?php
session_start();
include 'auth.php';
include 'db.php';

// Require admin authentication
requireAdmin();

require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (!isset($_GET['type'])) {
    header("Location: ../reports.php");
    exit;
}

$export_type = $_GET['type'];
$filters = [];

// Apply filters if provided
if (!empty($_GET['school'])) {
    $filters['school'] = $_GET['school'];
}
if (!empty($_GET['risk'])) {
    $filters['risk'] = $_GET['risk'];
}
if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}
if (!empty($_GET['activity_id'])) {
    $filters['activity_id'] = (int)$_GET['activity_id'];
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

try {
    switch ($export_type) {
        case 'assessments':
            exportAssessments($sheet, $pdo, $filters);
            $filename = 'assessments_export_' . date('Y-m-d_H-i-s') . '.xlsx';
            break;
            
        case 'summary':
            exportSummaryReport($sheet, $pdo, $filters);
            $filename = 'summary_report_' . date('Y-m-d_H-i-s') . '.xlsx';
            break;
            
        case 'risk_analysis':
            exportRiskAnalysis($sheet, $pdo, $filters);
            $filename = 'risk_analysis_' . date('Y-m-d_H-i-s') . '.xlsx';
            break;
            
        default:
            throw new Exception("Invalid export type");
    }
    
    // Output the file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"$filename\"");
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = "Export failed: " . $e->getMessage();
    header("Location: ../reports.php");
    exit;
}

function exportAssessments($sheet, $pdo, $filters) {
    $sheet->setTitle('Assessment Data');
    
    // Title
    $sheet->mergeCells('A1:S1');
    $sheet->setCellValue('A1', 'Adolescent Risk Assessment Data Export');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Export info
    $sheet->setCellValue('A2', 'Export Date: ' . date('Y-m-d H:i:s'));
    $sheet->setCellValue('A3', 'Exported by: ' . ($_SESSION['username'] ?? 'Unknown'));
    
    // Headers
    $headers = [
        'ID', 'Name', 'Address', 'Age', 'Gender', 'Date of Birth', 'Civil Status',
        'Employment Status', 'School Status', 'Grade Level', 'School Name',
        'Problems', 'Desired Service', 'Pregnant', 'Pregnant Age', 'Mobile Use',
        'Mobile Reason', 'Mobile Brand', 'Risk Category'
    ];
    
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '5', $header);
        $sheet->getStyle($col . '5')->getFont()->setBold(true);
        $sheet->getStyle($col . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3F2FD');
        $col++;
    }
    
    // Build query with filters
    $sql = "SELECT a.*, s.name as school_name FROM assessment a 
            LEFT JOIN school s ON a.school_id = s.school_id WHERE 1=1";
    $params = [];
    
    if (!empty($filters['school'])) {
        $sql .= " AND s.name = ?";
        $params[] = $filters['school'];
    }
    
    if (!empty($filters['risk'])) {
        $sql .= " AND a.risk_category = ?";
        $params[] = $filters['risk'];
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND DATE(a.created_at) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND DATE(a.created_at) <= ?";
        $params[] = $filters['date_to'];
    }
    
    if (!empty($filters['activity_id'])) {
        $sql .= " AND a.activity_id = ?";
        $params[] = $filters['activity_id'];
    }
    
    $sql .= " ORDER BY a.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $row = 6;
    while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $problems = json_decode($data['problems'], true);
        $problemsText = is_array($problems) ? implode(', ', $problems) : '';
        
        $values = [
            $data['assessment_id'],
            $data['name'],
            $data['address'],
            $data['age'],
            $data['gender'],
            $data['dob'],
            $data['civil_status'],
            $data['employment_status'],
            $data['school_status'],
            $data['grade_level'],
            $data['school_name'] ?? 'N/A',
            $problemsText,
            $data['desired_service'],
            $data['pregnant'],
            $data['pregnant_age'],
            $data['mobile_use'],
            $data['mobile_reason'],
            $data['mobile_phone_brand'],
            $data['risk_category']
        ];
        
        $col = 'A';
        foreach ($values as $value) {
            $sheet->setCellValue($col . $row, $value);
            $col++;
        }
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', 'S') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

function exportSummaryReport($sheet, $pdo, $filters) {
    $sheet->setTitle('Summary Report');
    
    // Title
    $sheet->mergeCells('A1:D1');
    $sheet->setCellValue('A1', 'Risk Assessment Summary Report');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $row = 3;
    
    // Total assessments
    $total_stmt = $pdo->query("SELECT COUNT(*) as total FROM assessment");
    $total = $total_stmt->fetchColumn();
    
    $sheet->setCellValue('A' . $row, 'Total Assessments:');
    $sheet->setCellValue('B' . $row, $total);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $row += 2;
    
    // Risk distribution
    $sheet->setCellValue('A' . $row, 'Risk Level Distribution:');
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $row++;
    
    $risk_stmt = $pdo->query("SELECT risk_category, COUNT(*) as count FROM assessment GROUP BY risk_category");
    while ($risk_data = $risk_stmt->fetch(PDO::FETCH_ASSOC)) {
        $percentage = $total > 0 ? round(($risk_data['count'] / $total) * 100, 1) : 0;
        $sheet->setCellValue('A' . $row, $risk_data['risk_category']);
        $sheet->setCellValue('B' . $row, $risk_data['count']);
        $sheet->setCellValue('C' . $row, $percentage . '%');
        $row++;
    }
    
    $row += 2;
    
    // Top problems
    $sheet->setCellValue('A' . $row, 'Top Problems:');
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $row++;
    
    $problems_query = "
        SELECT JSON_UNQUOTE(JSON_EXTRACT(problems, '$[0]')) AS problem, COUNT(*) as count
        FROM assessment WHERE JSON_LENGTH(problems) > 0 GROUP BY problem
        UNION ALL
        SELECT JSON_UNQUOTE(JSON_EXTRACT(problems, '$[1]')) AS problem, COUNT(*) as count
        FROM assessment WHERE JSON_LENGTH(problems) > 1 GROUP BY problem
        UNION ALL
        SELECT JSON_UNQUOTE(JSON_EXTRACT(problems, '$[2]')) AS problem, COUNT(*) as count
        FROM assessment WHERE JSON_LENGTH(problems) > 2 GROUP BY problem
        ORDER BY count DESC LIMIT 10
    ";
    
    $problems_stmt = $pdo->prepare($problems_query);
    $problems_stmt->execute();
    
    while ($problem_data = $problems_stmt->fetch(PDO::FETCH_ASSOC)) {
        $sheet->setCellValue('A' . $row, $problem_data['problem']);
        $sheet->setCellValue('B' . $row, $problem_data['count']);
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', 'D') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

function exportRiskAnalysis($sheet, $pdo, $filters) {
    $sheet->setTitle('Risk Analysis');
    
    // Title
    $sheet->mergeCells('A1:F1');
    $sheet->setCellValue('A1', 'Detailed Risk Analysis Report');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Headers
    $headers = ['School', 'Total Students', 'High Risk', 'Medium Risk', 'Low Risk', 'Risk Percentage'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '3', $header);
        $sheet->getStyle($col . '3')->getFont()->setBold(true);
        $sheet->getStyle($col . '3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3F2FD');
        $col++;
    }
    
    // Get risk analysis by school
    $sql = "SELECT s.name as school_name,
                   COUNT(*) as total,
                   SUM(CASE WHEN a.risk_category = 'High Risk' THEN 1 ELSE 0 END) as high_risk,
                   SUM(CASE WHEN a.risk_category = 'Medium Risk' THEN 1 ELSE 0 END) as medium_risk,
                   SUM(CASE WHEN a.risk_category = 'Low Risk' THEN 1 ELSE 0 END) as low_risk
            FROM assessment a
            LEFT JOIN school s ON a.school_id = s.school_id
            GROUP BY s.school_id, s.name
            ORDER BY high_risk DESC, total DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $row = 4;
    while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $risk_percentage = $data['total'] > 0 ? round(($data['high_risk'] / $data['total']) * 100, 1) : 0;
        
        $sheet->setCellValue('A' . $row, $data['school_name'] ?? 'Unknown School');
        $sheet->setCellValue('B' . $row, $data['total']);
        $sheet->setCellValue('C' . $row, $data['high_risk']);
        $sheet->setCellValue('D' . $row, $data['medium_risk']);
        $sheet->setCellValue('E' . $row, $data['low_risk']);
        $sheet->setCellValue('F' . $row, $risk_percentage . '%');
        
        // Color code high risk schools
        if ($risk_percentage > 50) {
            $sheet->getStyle('A' . $row . ':F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFEBEE');
        } elseif ($risk_percentage > 25) {
            $sheet->getStyle('A' . $row . ':F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF3E0');
        }
        
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}
