<?php
session_start();

require 'includes/db.php';
require 'vendor/autoload.php';

// Fetch schools
try {
    $stmt = $pdo->prepare("SELECT school_id, name FROM school");
    $stmt->execute();
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_GET['export']) && $_GET['export'] === 'full_assessment') {
    $activity_id = isset($_GET['activity_id']) ? (int) $_GET['activity_id'] : 0;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Assessment Data');

    // Title row
    $sheet->mergeCells('A1:T1');
    $sheet->setCellValue('A1', "Assessment Records for Activity #{$activity_id}");
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

    // Header row
    $headers = [
        'Assessment ID',
        'Name',
        'Address',
        'Age',
        'Gender',
        'Date of Birth',
        'Civil Status',
        'Employment Status',
        'School Status',
        'Grade Level',
        'School Name',
        'Problems',
        'Desired Service',
        'Pregnant',
        'Pregnant Age',
        'Mobile Use',
        'Mobile Reason',
        'Mobile Phone Brand',
        'Created At',
        'Risk Category',
        'Municipality',
        'Barangay',
        'Activity Title',
        'Activity Type',
        'Activity Date',
    ];

    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '2', $header);
        $sheet->getStyle($col . '2')->getFont()->setBold(true);
        $col++;
    }

    // Fetch assessment + school + activity info for only that activity
    $sql = "
        SELECT a.*, 
               s.name AS school_name,
               act.municipality, act.barangay, act.activity_title, act.activity_type, act.activity_date
        FROM assessment a
        LEFT JOIN school s ON a.school_id = s.school_id
        LEFT JOIN activity_info act ON a.activity_id = act.id
        WHERE a.activity_id = :activity_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['activity_id' => $activity_id]);

    $rowNum = 3;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $problems = json_decode($row['problems'], true);
        $problemsFormatted = is_array($problems) ? implode(', ', $problems) : '';

        $data = [
            $row['assessment_id'],
            $row['name'],
            $row['address'],
            $row['age'],
            $row['gender'],
            $row['dob'],
            $row['civil_status'],
            $row['employment_status'],
            $row['school_status'],
            $row['grade_level'],
            $row['school_name'] ?? 'N/A',
            $problemsFormatted,
            $row['desired_service'],
            $row['pregnant'],
            $row['pregnant_age'],
            $row['mobile_use'],
            $row['mobile_reason'],
            $row['mobile_phone_brand'],
            $row['created_at'],
            $row['risk_category'],
            $row['municipality'],
            $row['barangay'],
            $row['activity_title'],
            $row['activity_type'],
            $row['activity_date'],
        ];

        $col = 'A';
        foreach ($data as $cellValue) {
            $sheet->setCellValue($col . $rowNum, $cellValue);
            $col++;
        }

        $rowNum++;
    }

    // Output the file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=assessment_activity_{$activity_id}.xlsx");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Reports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Styles and Libraries -->
    <link rel="stylesheet" href="css/sidebar.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <!-- jQuery (required for libraries below) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- SheetJS (for Excel export) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>

    <!-- jsPDF (for PDF export) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

    <style>
        body {
            display: flex;
            background-color: #f8f9fa;
        }

        .main-content {
            flex: 1;
            padding: 40px;
            margin-left: 250px;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 30px;
        }

        .filter-box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .table-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .export-buttons {
            margin-bottom: 15px;
        }

        th {
            cursor: pointer;
        }

        /* Highlight the row on hover */
        #reportTable tbody tr:hover {
            background-color: #f5f5f5;
            /* Light gray background */
            cursor: pointer;
            /* Change cursor to pointer to indicate clickability */
            transition: background-color 0.3s ease;
            /* Smooth transition */
        }

        #reportTable tbody tr:hover {
            background-color: #eef7ff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Hide buttons when printing */
        @media print {

            button,
            .btn,
            a.btn {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-title">Assessment Reports</div>

        <!-- Filter Section -->
        <form id="filterForm" class="mb-4">
            <div class="filter-box row g-3">
                <div class="col-md-4">
                    <label for="filterSchool" class="form-label">School</label>
                    <select class="form-select" id="filterSchool" name="school">
                        <option value="">All Schools</option>
                        <?php
                        try {
                            $schoolQuery = $pdo->query("SELECT name FROM school ORDER BY name ASC");
                            while ($school = $schoolQuery->fetch(PDO::FETCH_ASSOC)) {
                                $schoolName = htmlspecialchars($school['name']);
                                echo "<option value=\"{$schoolName}\">{$schoolName}</option>";
                            }
                        } catch (PDOException $e) {
                            echo "<option disabled>Error loading schools</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filterRisk" class="form-label">Risk Level</label>
                    <select class="form-select" id="filterRisk" name="risk">
                        <option value="">All</option>
                        <option value="High Risk">High Risk</option>
                        <option value="Medium Risk">Medium Risk</option>
                        <option value="Low Risk">Low Risk</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filterDate" class="form-label">Date</label>
                    <input type="date" class="form-control" id="filterDate" name="date" />
                </div>
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </div>
            </div>
        </form>

        <!--  Buttons -->
        <div class="export-buttons d-flex justify-content-end gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#initialActivityModal">
                <i class="bi bi-plus-circle"></i> Add Activity
            </button>
            <!-- <button class="btn btn-danger"><i class="bi bi-file-earmark-pdf"></i> Export to PDF</button> -->
            <button class="btn btn-secondary" onclick="printCurrentTable()">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>

        <!-- Initial Activity Modal -->
        <div class="modal fade" id="initialActivityModal" tabindex="-1" aria-labelledby="initialActivityModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="initialActivityForm" action="includes/add_activity.php" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="initialActivityModalLabel">Activity Information Required</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">

                            <div class="mb-3">
                                <label for="municipality" class="form-label">Municipality</label>
                                <input type="text" class="form-control" id="municipality" name="municipality" required>
                            </div>

                            <div class="mb-3">
                                <label for="barangay" class="form-label">Barangay</label>
                                <input type="text" class="form-control" id="barangay" name="barangay" required>
                            </div>

                            <div class="mb-3">
                                <label for="activityTitle" class="form-label">Activity Title</label>
                                <input type="text" class="form-control" id="activityTitle" name="activityTitle" required>
                            </div>

                            <div class="mb-3">
                                <label for="activityType" class="form-label">Activity Type</label>
                                <input type="text" class="form-control" id="activityType" name="activityType" required>
                            </div>

                            <div class="mb-3">
                                <label for="activityDate" class="form-label">Date of Activity</label>
                                <input type="date" class="form-control" id="activityDate" name="activityDate" required>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Proceed</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <h3 id="tableHeading">Activity Info</h3>
        <!-- Report Table -->
        <div class="table-container">
            <table class="table table-bordered table-hover" id="reportTable">
                <thead class='table-light'>
                    <tr>
                        <th onclick="sortTable(0)">Municipality</th>
                        <th onclick="sortTable(1)">Barangay</th>
                        <th onclick="sortTable(2)">Activity Title</th>
                        <th onclick="sortTable(3)">Activity Type</th>
                        <th onclick="sortTable(4)">Date of Activity</th>
                        <th>Actions</th> <!-- New column -->
                    </tr>
                </thead>
                <tbody>
                    <?php
                    require 'includes/db.php';

                    try {
                        $stmt = $pdo->query("SELECT * FROM activity_info ORDER BY activity_date DESC");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr onclick=\"showSingleActivity(" . $row['id'] . ")\">";
                            echo "<td>" . htmlspecialchars($row['municipality']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['barangay']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['activity_title']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['activity_type']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['activity_date']) . "</td>";
                            echo "<td>";
                            echo "<a href='edit_activity.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary me-1'>Edit</a>";
                            echo "<a href='delete_activity.php?id=" . $row['id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this activity?\");'>Delete</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='6'>Error: " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <!-- Single Activity View -->
            <div id="singleActivityView" style="display: none;">
                <!-- Dynamic Title -->
                <h4 class="text-center mb-4">
                    <?php
                    if (isset($_GET['activity_id'])) {
                        $activity_id = (int) $_GET['activity_id'];
                        echo "Activity #{$activity_id} - Assessment Records";
                    } else {
                        echo "Assessment Records";
                    }
                    ?>
                </h4>

                <!-- Buttons -->
                <div class="d-flex justify-content-between mb-3 gap-2 flex-wrap">
                    <button class="btn btn-secondary flex-fill" onclick="goBack()">← Back to List</button>

                    <button class="btn btn-success flex-fill" data-bs-toggle="modal" data-bs-target="#addAssessmentModal">
                        + Add Assessment
                    </button>

                    <a href="reports.php?export=full_assessment&activity_id=<?php echo $activity_id; ?>"
                        id="exportBtn"
                        class="btn btn-info flex-fill">
                        <i class="bi bi-file-earmark-excel"></i> Export to Excel
                    </a>

                    <button class="btn btn-primary flex-fill" data-bs-toggle="modal" data-bs-target="#importDataModal">
                        <i class="bi bi-upload"></i> Import Data
                    </button>
                </div>

                <!-- Import Data Modal -->
                <div class="modal fade" id="importDataModal" tabindex="-1" aria-labelledby="importDataModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="includes/import_assessments.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="importDataModalLabel">
                                        <i class="bi bi-upload"></i> Import Assessments from Excel
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="excelFile" class="form-label">Choose Excel File</label>
                                        <input class="form-control" type="file" name="excel_file" id="excelFile" ...>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-upload"></i> Import
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <table class="table table-bordered">
                    <thead class="table-secondary">
                        <tr>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>School</th>
                        </tr>
                    </thead>
                    <tbody id="singleActivityBody">
                        <!-- JS will populate here -->
                    </tbody>
                </table>

            </div>
        </div>

        <!-- Add Assessment Modal -->
        <div class="modal fade" id="addAssessmentModal" tabindex="-1" aria-labelledby="addAssessmentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content p-4">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAssessmentModalLabel">Add Assessment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <!-- FORM START -->
                        <form id="assessmentForm" method="POST" action="includes/proc_assessment.php">

                            <!-- Adolescent Information -->
                            <div class="section-title mb-3">Adolescent Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="activity_id" class="form-label">Select Activity</label>
                                    <select class="form-select" id="activity_id" name="activity_id" required>
                                        <option value="">Choose Activity...</option>
                                        <?php
                                        $stmt = $pdo->query("SELECT id, activity_title FROM activity_info ORDER BY activity_title ASC");
                                        while ($row = $stmt->fetch()) {
                                            echo "<option value='{$row['id']}'>" . htmlspecialchars($row['activity_title']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" required>
                                </div>

                                <div class="col-md-3">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob" required>
                                </div>

                                <div class="col-md-3">
                                    <label for="age" class="form-label">Age</label>
                                    <input type="number" class="form-control" id="age" name="age" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label for="gender" class="form-label">Sex</label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="">Choose...</option>
                                        <option>Male</option>
                                        <option>Female</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Status Information -->
                            <div class="section-title mt-4 mb-3">Status Information</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="civilStatus" class="form-label">Civil Status</label>
                                    <select class="form-select" id="civilStatus" name="civilStatus" required>
                                        <option value="">Choose...</option>
                                        <option>Single</option>
                                        <option>Married</option>
                                        <option>Living in</option>
                                        <option>Not Living-in</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="employmentStatus" class="form-label">Employment Status</label>
                                    <select class="form-select" id="employmentStatus" name="employmentStatus" required>
                                        <option value="">Choose...</option>
                                        <option>Employed</option>
                                        <option>Unemployed</option>
                                        <option>Underemployed</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="schoolStatus" class="form-label">School Attending Status</label>
                                    <select class="form-select" id="schoolStatus" name="schoolStatus" required>
                                        <option value="">Choose...</option>
                                        <option>Out of School Youth</option>
                                        <option>In School Youth</option>
                                        <option>Vocational/Technical</option>
                                    </select>
                                </div>
                            </div>

                            <!-- School & Grade -->
                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <label for="schoolName" class="form-label">School Name</label>
                                    <select class="form-select" id="schoolName" name="schoolName" required>
                                        <option value="">Select a school</option>
                                        <?php
                                        foreach ($schools as $school) {
                                            echo "<option value='" . htmlspecialchars($school['school_id']) . "'>" . htmlspecialchars($school['name']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="gradeLevel" class="form-label">Grade Level</label>
                                    <input type="text" class="form-control" id="gradeLevel" name="gradeLevel" required>
                                </div>
                            </div>

                            <!-- Current Issues -->
                            <div class="section-title mt-4 mb-3">Current Problems/Issues (Select Top 3)</div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Love Life"> Love Life</div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Bullying"> Bullying</div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Substance Abuse"> Substance Abuse</div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="School Works"> School Works</div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Family Problem"> Family Problem</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Peer Pressure"> Peer Pressure</div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Too early sexual activity"> Too early sexual activity</div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Medical/Dental"> Medical/Dental</div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Mental Health"> Mental Health</div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Spiritual Emptiness"> Spiritual Emptiness</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Violence (VAWC)"> Violence (VAWC)</div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Others"> Others</div>
                                    <input type="text" class="form-control mt-2" name="othersDetail" placeholder="If Others, specify">
                                </div>
                            </div>

                            <!-- Desired Services -->
                            <div class="section-title mt-4 mb-3">Desired Services (Choose One)</div>
                            <select class="form-select mb-3" id="desiredService" name="desiredService" required>
                                <option value="">Select service...</option>
                                <option>Medical/Dental</option>
                                <option>Reproductive Health (incl. Family Planning)</option>
                                <option>Guidance/Counseling</option>
                                <option>Rehabilitation</option>
                                <option>Information/Education</option>
                                <option>Spiritual Formation</option>
                                <option>Vaccination</option>
                                <option>Support for education/technical skills</option>
                                <option>Others</option>
                            </select>

                            <!-- Sexuality -->
                            <div class="section-title mt-4 mb-3">Sexuality</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="pregnant" class="form-label">Have you ever been pregnant? (If female)</label>
                                    <select class="form-select" id="pregnant" name="pregnant">
                                        <option value="">Choose...</option>
                                        <option>No</option>
                                        <option>Yes</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="pregnantAge" class="form-label">If yes, at what age?</label>
                                    <select class="form-select" id="pregnantAge" name="pregnantAge">
                                        <option value="">Choose...</option>
                                        <option>10-14</option>
                                        <option>15-17</option>
                                        <option>18-19</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Mobile Accessibility -->
                            <div class="section-title mt-4 mb-3">Mobile Accessibility</div>
                            <div class="mb-3">
                                <label for="mobileUse" class="form-label">Do you use a mobile phone?</label>
                                <select class="form-select" id="mobileUse" name="mobileUse">
                                    <option value="">Choose...</option>
                                    <option>No</option>
                                    <option>Yes</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="mobileReason" class="form-label">Reason/s for using mobile phone</label>
                                <input type="text" class="form-control" id="mobileReason" name="mobileReason">
                            </div>
                            <div class="mb-3">
                                <label for="mobilePhoneBrand" class="form-label">If Yes, which mobile phone(s) do you use?</label>
                                <input type="text" class="form-control" id="mobilePhoneBrand" name="mobilePhoneBrand">
                            </div>

                            <!-- Hidden fields -->
                            <input type="hidden" name="municipality" id="municipalityInput">
                            <input type="hidden" name="barangay" id="barangayInput">
                            <input type="hidden" name="activityTitle" id="activityTitleInput">
                            <input type="hidden" name="activityType" id="activityTypeInput">
                            <input type="hidden" name="activityDate" id="activityDateInput">

                            <!-- Submit -->
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary mt-3">Submit</button>
                            </div>
                        </form>
                        <!-- FORM END -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar-highlight.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById('filterForm');

            if (!form) {
                console.error('filterForm not found!');
                return;
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const schoolFilter = document.getElementById('filterSchool').value.toLowerCase().trim();
                const riskFilter = document.getElementById('filterRisk').value.toLowerCase().trim();
                const dateFilter = document.getElementById('filterDate').value;

                console.log("Filtering:", {
                    schoolFilter,
                    riskFilter,
                    dateFilter
                });

                const rows = document.querySelectorAll('#reportTable tbody tr');

                rows.forEach(row => {
                    const date = row.cells[0].textContent.trim();
                    const school = row.cells[3].textContent.toLowerCase().trim();
                    const risk = row.cells[4].textContent.toLowerCase().trim();

                    const matchSchool = !schoolFilter || school === schoolFilter;
                    const matchRisk = !riskFilter || risk.includes(riskFilter);
                    const matchDate = !dateFilter || date === dateFilter;

                    row.style.display = (matchSchool && matchRisk && matchDate) ? '' : 'none';
                });
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            // Excel Export
            document.querySelector(".btn-success").addEventListener("click", function() {
                let table = document.getElementById("reportTable");
                let workbook = XLSX.utils.table_to_book(table, {
                    sheet: "Report"
                });
                XLSX.writeFile(workbook, "StudentReport.xlsx");
            });

            // // PDF Export
            // document.querySelector(".btn-danger").addEventListener("click", function() {
            //     try {
            //         if (!window.jspdf || !window.jspdf.jsPDF) {
            //             throw new Error("jsPDF library not loaded. Please check the script source and network connection.");
            //         }

            //         const {
            //             jsPDF
            //         } = window.jspdf;
            //         const doc = new jsPDF();

            //         // Add title
            //         doc.setFontSize(16);
            //         doc.text("Assessment Report", 10, 10);

            //         // Check if autoTable is available
            //         let useAutoTable = typeof doc.autoTable === 'function';
            //         if (!useAutoTable) {
            //             console.warn("autoTable plugin not loaded. Falling back to text-based export.");
            //         }

            //         // Extract table data
            //         const table = document.getElementById("reportTable");
            //         const rows = table.querySelectorAll("tbody tr");
            //         const tableData = [];
            //         const headers = ["Date", "Name", "Age", "School", "Risk Level", "Suggested Action"];
            //         tableData.push(headers);

            //         // Extract visible rows only
            //         let hasVisibleRows = false;
            //         rows.forEach(row => {
            //             if (row.style.display !== 'none') {
            //                 hasVisibleRows = true;
            //                 const cells = row.querySelectorAll("td");
            //                 const rowData = [
            //                     cells[0].textContent.trim(),
            //                     cells[1].textContent.trim(),
            //                     cells[2].textContent.trim(),
            //                     cells[3].textContent.trim(),
            //                     cells[4].textContent.trim(),
            //                     cells[5].textContent.trim()
            //                 ];
            //                 tableData.push(rowData);
            //             }
            //         });

            //         // If no visible rows, add message
            //         if (!hasVisibleRows) {
            //             doc.setFontSize(12);
            //             doc.text("No data available after applying filters.", 10, 20);
            //             doc.save("StudentReport.pdf");
            //             return;
            //         }

            //         if (useAutoTable) {
            //             // Generate table with autoTable
            //             doc.autoTable({
            //                 head: [headers],
            //                 body: tableData.slice(1),
            //                 theme: 'striped',
            //                 styles: {
            //                     fontSize: 10,
            //                     cellPadding: 2
            //                 },
            //                 headStyles: {
            //                     fillColor: [22, 160, 133],
            //                     textColor: [255, 255, 255]
            //                 },
            //                 margin: {
            //                     top: 20
            //                 },
            //                 columnStyles: {
            //                     0: {
            //                         cellWidth: 30
            //                     }, // Date
            //                     1: {
            //                         cellWidth: 40
            //                     }, // Name
            //                     2: {
            //                         cellWidth: 20
            //                     }, // Age
            //                     3: {
            //                         cellWidth: 40
            //                     }, // School
            //                     4: {
            //                         cellWidth: 30
            //                     }, // Risk Level
            //                     5: {
            //                         cellWidth: 40
            //                     } // Suggested Action
            //                 }
            //             });
            //         } else {
            //             // Fallback to text-based export
            //             let y = 20;
            //             doc.setFontSize(12);
            //             doc.text(headers.join("  "), 10, y);
            //             y += 10;
            //             tableData.slice(1).forEach(row => {
            //                 doc.text(row.join("  "), 10, y);
            //                 y += 10;
            //             });
            //         }

            //         doc.save("StudentReport.pdf");
            //     } catch (error) {
            //         console.error("PDF Export Error:", error.message);
            //         alert("Failed to export PDF: " + error.message);
            //     }
            // });

            // Print
            document.querySelector(".btn-secondary").addEventListener("click", function() {
                let printContents = document.getElementById("reportTable").outerHTML;
                let printWindow = window.open('', '', 'height=600,width=800');
                printWindow.document.write('<html><head><title>Print Report</title>');
                printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
                printWindow.document.write('</head><body>');
                printWindow.document.write(printContents);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();
            });
        });

        let sortDirection = true; // true = ascending, false = descending

        function sortTable(columnIndex) {
            const table = document.getElementById("reportTable");
            const tbody = table.querySelector("tbody");
            const rows = Array.from(tbody.querySelectorAll("tr"));

            rows.sort((a, b) => {
                const cellA = a.cells[columnIndex].textContent.trim().toLowerCase();
                const cellB = b.cells[columnIndex].textContent.trim().toLowerCase();

                if (!isNaN(cellA) && !isNaN(cellB)) {
                    return sortDirection ? cellA - cellB : cellB - cellA;
                } else if (Date.parse(cellA) && Date.parse(cellB)) {
                    return sortDirection ? new Date(cellA) - new Date(cellB) : new Date(cellB) - new Date(cellA);
                } else {
                    return sortDirection ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
                }
            });

            // Clear and re-append sorted rows
            tbody.innerHTML = "";
            rows.forEach(row => tbody.appendChild(row));

            // Toggle sort direction for next click
            sortDirection = !sortDirection;
        }

        function showSingleActivity(activityId) {
            // Hide list, show single view
            document.getElementById('reportTable').style.display = 'none';
            document.getElementById('singleActivityView').style.display = 'block';

            // Update export button link with activityId
            document.getElementById('exportBtn').href =
                'reports.php?export=full_assessment&activity_id=' + activityId;

            // Fetch data
            fetch('includes/get_assessments.php?activity_id=' + activityId)
                .then(response => response.json())
                .then(data => {
                    let tbody = document.getElementById('singleActivityBody');
                    tbody.innerHTML = ""; // clear old rows

                    if (data.length > 0) {
                        // Update the dynamic title with activity_title
                        document.querySelector('#singleActivityView h4').innerText =
                            `${data[0].activity_title} - Assessment Records`;

                        // Build rows
                        data.forEach(row => {
                            let tr = document.createElement("tr");
                            tr.innerHTML = `
                        <td>${row.name}</td>
                        <td>${row.address}</td>
                        <td>${row.age}</td>
                        <td>${row.gender}</td>
                        <td>${row.school_name ?? 'N/A'}</td>
                    `;
                            tbody.appendChild(tr);
                        });
                    } else {
                        tbody.innerHTML = `<tr><td colspan="5" class="text-center">
                                      No assessment records found for this activity.
                                   </td></tr>`;
                        // Reset title
                        document.querySelector('#singleActivityView h4').innerText =
                            `Activity #${activityId} - Assessment Records`;
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function goBack() {
            document.getElementById('reportTable').style.display = 'table';
            document.getElementById('singleActivityView').style.display = 'none';
        }

        document.getElementById("dob").addEventListener("change", function() {
            let dob = new Date(this.value);
            if (!isNaN(dob.getTime())) {
                let today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                let monthDiff = today.getMonth() - dob.getMonth();
                let dayDiff = today.getDate() - dob.getDate();

                // Adjust if birthday hasn't occurred yet this year
                if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
                    age--;
                }

                document.getElementById("age").value = age;
            } else {
                document.getElementById("age").value = "";
            }
        });

        function printCurrentTable() {
            const reportTable = document.getElementById("reportTable");
            const singleActivityView = document.getElementById("singleActivityView");

            let printContent = "";

            if (singleActivityView.style.display !== "none") {
                printContent = singleActivityView.innerHTML;
            } else {
                printContent = reportTable.outerHTML;
            }

            const win = window.open("", "_blank");
            win.document.write(`
                <html>
                <head>
                    <title>Print Table</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; font-family: Arial, sans-serif; }
                        h4 { text-align: center; margin-bottom: 20px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                        @media print {
                            button, .btn, a.btn { display: none !important; }
                        }
                    </style>
                </head>
                <body>
                    ${printContent}
                </body>
                </html>
            `);
            win.document.close();
            win.print();
        }
    </script>

</body>

</html>