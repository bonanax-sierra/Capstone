<?php
include 'includes/auth.php';
include 'includes/db.php';

// Require admin authentication
requireStaffOrAdmin();

$current_user = getCurrentUser();

// Get activity_id from URL
$activity_id = (int) ($_GET['activity_id'] ?? 0);

if (!$activity_id) {
    $_SESSION['error'] = "Invalid activity ID";
    header("Location: activity_management.php");
    exit;
}

// Get activity details
try {
    $activity_stmt = $pdo->prepare("SELECT * FROM activity_info WHERE activity_id = ?");
    $activity_stmt->execute([$activity_id]);
    $activity = $activity_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        $_SESSION['error'] = "Activity not found";
        header("Location: activity_management.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error loading activity: " . $e->getMessage();
    header("Location: activity_management.php");
    exit;
}

// Fetch schools for the form
try {
    $schools_stmt = $pdo->prepare("SELECT school_id, name FROM school ORDER BY name");
    $schools_stmt->execute();
    $schools = $schools_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $schools = [];
}

// Get existing assessments for this activity
try {
    $assessments_stmt = $pdo->prepare("SELECT a.*, s.name as school_name FROM assessment a LEFT JOIN school s ON a.school_id = s.school_id WHERE a.activity_id = ? ORDER BY a.created_at DESC");
    $assessments_stmt->execute([$activity_id]);
    $existing_assessments = $assessments_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $existing_assessments = [];
}
?>

<?php
$page_title = "Activity Assessment"; // Custom title for this page
include_once 'includes/header.php';
?>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- Activity Header -->
        <div class="card activity-header">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1"><?php echo htmlspecialchars($activity['activity_title']); ?></h4>
                        <p class="mb-0">
                            <i class="bi bi-calendar"></i>
                            <?php
                            echo !empty($activity['date_of_activity'])
                                ? date('M j, Y', strtotime($activity['date_of_activity']))
                                : 'Date not set';
                            ?>
                            <?php if (!empty($activity['barangay']) || !empty($activity['municipality'])): ?>
                                | <i class="bi bi-geo-alt"></i>
                                <?php
                                $locationParts = array_filter([$activity['barangay'], $activity['municipality']]);
                                echo htmlspecialchars(implode(', ', $locationParts));
                                ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <a href="activity_management.php" class="btn btn-light">
                            <i class="bi bi-arrow-left"></i> Back to Activities
                        </a>
                    </div>
                </div>
            </div>
        </div>


        <!-- Display Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Assessment Form -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-clipboard-plus"></i> New Assessment</h5>
                    </div>
                    <div class="card-body">
                        <form action="includes/proc_assessment.php" method="POST">
                            <input type="hidden" name="activity_id" value="<?php echo $activity_id; ?>">

                            <!-- Personal Information Section -->
                            <div class="form-section">
                                <h6 class="text-primary mb-3"><i class="bi bi-person"></i> Personal Information</h6>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">First Name *</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="middle_name" class="form-label">Middle Name</label>
                                            <input type="text" class="form-control" id="middle_name" name="middle_name">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Last Name *</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="extension_name" class="form-label">Extension</label>
                                            <input type="text" class="form-control" id="extension_name"
                                                name="extension_name" placeholder="Jr., Sr., III">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" id="date_of_birth"
                                                name="date_of_birth">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="sex" class="form-label">Gender *</label>
                                            <select class="form-select" id="sex" name="sex" required>
                                                <option value="">Select Gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="civil_status" class="form-label">Civil Status *</label>
                                            <select class="form-select" id="civil_status" name="civil_status" required>
                                                <option value="">Choose...</option>
                                                <option value="Single">Single</option>
                                                <option value="Married">Married</option>
                                                <option value="Living in">Living in</option>
                                                <option value="Not Living-in">Not Living-in</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="employment_status" class="form-label">Employment Status
                                                *</label>
                                            <select class="form-select" id="employment_status" name="employment_status"
                                                required>
                                                <option value="">Choose...</option>
                                                <option value="Employed">Employed</option>
                                                <option value="Unemployed">Unemployed</option>
                                                <option value="Underemployed">Underemployed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="school_status" class="form-label">School Attending Status
                                                *</label>
                                            <select class="form-select" id="school_status" name="school_status"
                                                required>
                                                <option value="">Choose...</option>
                                                <option value="Out of School Youth">Out of School Youth</option>
                                                <option value="In School Youth">In School Youth</option>
                                                <option value="Vocational/Technical">Vocational/Technical</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Education Section -->
                            <div class="form-section">
                                <h6 class="text-primary mb-3"><i class="bi bi-book"></i> Education Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="school_id" class="form-label">School Name *</label>
                                            <select class="form-select" id="school_id" name="school_id" required>
                                                <option value="">Select a school</option>
                                                <?php foreach ($schools as $school): ?>
                                                    <option value="<?php echo $school['school_id']; ?>">
                                                        <?php echo htmlspecialchars($school['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="grade_level" class="form-label">Grade Level *</label>
                                            <input type="text" class="form-control" id="grade_level" name="grade_level"
                                                required placeholder="e.g., Grade 10, 1st Year College">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Problems/Issues Section -->
                            <div class="form-section">
                                <h6 class="text-primary mb-3"><i class="bi bi-exclamation-triangle"></i> Current
                                    Problems/Issues (Select Top 3)</h6>
                                <div class="row">
                                    <?php
                                    $issues = [
                                        "Love Life",
                                        "Bullying",
                                        "Substance Abuse",
                                        "School Works",
                                        "Family Problem",
                                        "Peer Pressure",
                                        "Too early sexual activity",
                                        "Medical/Dental",
                                        "Mental Health",
                                        "Spiritual Emptiness",
                                        "Violence (VAWC)",
                                        "Others"
                                    ];
                                    ?>
                                    <?php foreach ($issues as $index => $issue): ?>
                                        <div class="col-md-4">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="current_problems[]"
                                                    value="<?php echo $issue; ?>" id="issue<?php echo $index + 1; ?>">
                                                <label class="form-check-label"
                                                    for="issue<?php echo $index + 1; ?>"><?php echo $issue; ?></label>
                                            </div>
                                            <?php if ($issue === "Others"): ?>
                                                <input type="text" class="form-control mt-1" name="others_detail"
                                                    placeholder="Please specify...">
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Desired Services Section -->
                            <div class="form-section">
                                <h6 class="text-primary mb-3"><i class="bi bi-heart"></i> Desired Services (Choose One)
                                </h6>
                                <div class="mb-3">
                                    <select class="form-select" id="desired_service" name="desired_service" required>
                                        <option value="">Select service...</option>
                                        <option value="Medical/Dental">Medical/Dental</option>
                                        <option value="Reproductive Health (incl. Family Planning)">Reproductive Health
                                            (incl. Family Planning)</option>
                                        <option value="Guidance/Counseling">Guidance/Counseling</option>
                                        <option value="Rehabilitation">Rehabilitation</option>
                                        <option value="Information/Education">Information/Education</option>
                                        <option value="Spiritual Formation">Spiritual Formation</option>
                                        <option value="Vaccination">Vaccination</option>
                                        <option value="Support for education/technical skills">Support for
                                            education/technical skills</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Sexuality Section -->
                            <div class="form-section">
                                <h6 class="text-primary mb-3"><i class="bi bi-person-hearts"></i> Sexuality</h6>
                                <div class="row">
                                    <!-- Female Pregnancy Section -->
                                    <div class="col-md-6 sexuality-field" id="female-section" style="display:none;">
                                        <div class="mb-3">
                                            <label for="pregnant" class="form-label">Have you ever been
                                                pregnant?</label>
                                            <select class="form-select" id="pregnant" name="pregnant">
                                                <option value="">Choose...</option>
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 sexuality-field" id="female-age-section" style="display:none;">
                                        <div class="mb-3">
                                            <label for="pregnant_age" class="form-label">If yes, at what age?</label>
                                            <select class="form-select" id="pregnant_age" name="pregnant_age">
                                                <option value="">Choose...</option>
                                                <option value="10-14">10-14</option>
                                                <option value="15-17">15-17</option>
                                                <option value="18-19">18-19</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Male Impregnated Section -->
                                    <div class="col-md-6 sexuality-field" id="male-section" style="display:none;">
                                        <div class="mb-3">
                                            <label for="impregnated" class="form-label">Have you ever impregnated
                                                someone?</label>
                                            <select class="form-select" id="impregnated" name="impregnated">
                                                <option value="">Choose...</option>
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!-- Mobile Accessibility Section -->
                            <div class="form-section">
                                <h6 class="text-primary mb-3"><i class="bi bi-phone"></i> Mobile Accessibility</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="mobile_use" class="form-label">Do you use a mobile
                                                phone?</label>
                                            <select class="form-select" id="mobile_use" name="mobile_use">
                                                <option value="">Choose...</option>
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="mobile_reason" class="form-label">Reason/s for using mobile
                                                phone</label>
                                            <input type="text" class="form-control" id="mobile_reason"
                                                name="mobile_reason">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="mobile_phone_brand" class="form-label">Which mobile phone(s) do
                                                you use?</label>
                                            <input type="text" class="form-control" id="mobile_phone_brand"
                                                name="mobile_phone_brand">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Section -->
                            <div class="d-flex justify-content-between">
                                <a href="activity_management.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Submit Assessment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <!-- Existing Assessments -->
            <div class="col-md-4">
                <div class="card fade-in-up">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i> Existing Assessments</h5>
                        <span class="badge bg-primary"><?php echo count($existing_assessments); ?> Total</span>
                    </div>
                    <div class="card-body" id="assessmentList">
                        <?php if (!empty($existing_assessments)): ?>
                            <?php
                            // Sort assessments by created_at (latest first)
                            usort($existing_assessments, function ($a, $b) {
                                return strtotime($b['created_at']) - strtotime($a['created_at']);
                            });

                            // Get only the last 5
                            $latest_assessments = array_slice($existing_assessments, 0, 10);
                            ?>

                            <?php foreach ($latest_assessments as $assessment): ?>
                                <?php
                                $full_name = $assessment['first_name']
                                    . (!empty($assessment['middle_name']) ? " " . $assessment['middle_name'] : '')
                                    . " " . $assessment['last_name']
                                    . (!empty($assessment['extension_name']) ? " " . $assessment['extension_name'] : '');

                                $age = 'N/A';
                                if (!empty($assessment['date_of_birth'])) {
                                    $dob = new DateTime($assessment['date_of_birth']);
                                    $today = new DateTime();
                                    $age = $today->diff($dob)->y;
                                }

                                $risk_class = match ($assessment['risk_category']) {
                                    'High' => 'danger',
                                    'Medium' => 'warning',
                                    'Low' => 'success',
                                    default => 'secondary'
                                };
                                ?>
                                <div class="assessment-item <?php echo strtolower($assessment['risk_category'] ?? ''); ?>">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($full_name); ?></h6>
                                    <small class="text-muted">
                                        Age: <?php echo $age; ?> |
                                        Risk: <span class="badge bg-<?php echo $risk_class; ?>">
                                            <?php echo htmlspecialchars($assessment['risk_category'] ?? 'N/A'); ?>
                                        </span>
                                    </small><br>
                                    <small class="text-muted">
                                        <?php echo !empty($assessment['created_at']) ? date('M j, Y', strtotime($assessment['created_at'])) : 'N/A'; ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <p class="text-muted text-center m-0">No assessments yet for this activity.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar-highlight.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const maxSelection = 3;

            // Updated names from the form
            const checkboxes = document.querySelectorAll('input[name="current_problems[]"]');
            const dobInput = document.getElementById('date_of_birth');
            const ageInput = document.getElementById('age');
            const genderSelect = document.getElementById('sex');
            const sexualitySection = document.getElementById('sexuality-section');
            const othersDetailInput = document.querySelector('input[name="others_detail"]');
            const form = document.querySelector('form');

            // -----------------------------
            // Limit checkbox selection to 3
            // -----------------------------
            checkboxes.forEach(cb => {
                cb.addEventListener('change', function () {
                    const checked = document.querySelectorAll('input[name="current_problems[]"]:checked');

                    if (checked.length > maxSelection) {
                        this.checked = false;
                        alert(`You can only select up to ${maxSelection} issues.`);
                    }

                    updateIssueCounter(checked.length);
                });
            });

            function updateIssueCounter(count) {
                let counter = document.getElementById('issueCounter');
                if (!counter) {
                    counter = document.createElement('small');
                    counter.id = 'issueCounter';
                    counter.className = 'text-muted';
                    const heading = document.querySelector('.form-section h6');
                    if (heading) heading.appendChild(counter);
                }

                counter.textContent = ` (${count}/${maxSelection} selected)`;

                if (count === maxSelection) counter.className = 'text-success';
                else if (count > 0) counter.className = 'text-warning';
                else counter.className = 'text-muted';
            }

            // -----------------------------
            // Form validation on submit
            // -----------------------------
            form.addEventListener('submit', function (e) {
                const checkedIssues = document.querySelectorAll('input[name="current_problems[]"]:checked');

                if (checkedIssues.length !== maxSelection) {
                    e.preventDefault();
                    alert(`Please select exactly ${maxSelection} issues.`);
                    return false;
                }

                const othersChecked = document.querySelector('input[name="current_problems[]"][value="Others"]:checked');
                if (othersChecked && !othersDetailInput.value.trim()) {
                    e.preventDefault();
                    alert('Please provide details for "Others" issue.');
                    othersDetailInput.focus();
                    return false;
                }
            });

            // -----------------------------
            // Auto-calculate Age from DOB
            // -----------------------------
            dobInput.addEventListener('change', function () {
                if (this.value) {
                    const dob = new Date(this.value);
                    const today = new Date();
                    let age = today.getFullYear() - dob.getFullYear();
                    const birthdayPassed = (today.getMonth() > dob.getMonth()) ||
                        (today.getMonth() === dob.getMonth() && today.getDate() >= dob.getDate());
                    if (!birthdayPassed) age--;

                    if (age >= 10 && age <= 25) {
                        ageInput.value = age;
                    } else {
                        ageInput.value = '';
                        alert("Age must be between 10 and 25.");
                    }
                }
            });

            // -----------------------------
            // Show/hide sexuality section based on gender
            // -----------------------------
            function toggleSexualitySection() {
                if (genderSelect.value === "Male") {
                    sexualitySection.style.display = "none";
                    sexualitySection.querySelectorAll("select, input").forEach(el => el.value = "");
                } else {
                    sexualitySection.style.display = "block";
                }
            }

            genderSelect.addEventListener('change', toggleSexualitySection);

            // Run on page load
            toggleSexualitySection();
        });

        // Show/hide pregnancy/impregnation fields
        const genderSelect = document.getElementById('sex');
        const femaleSection = document.getElementById('female-section');
        const femaleAgeSection = document.getElementById('female-age-section');
        const maleSection = document.getElementById('male-section');

        genderSelect.addEventListener('change', function () {
            const gender = this.value;

            if (gender === 'Female') {
                femaleSection.style.display = 'block';
                femaleAgeSection.style.display = 'block';
                maleSection.style.display = 'none';
            } else if (gender === 'Male') {
                maleSection.style.display = 'block';
                femaleSection.style.display = 'none';
                femaleAgeSection.style.display = 'none';
            } else {
                femaleSection.style.display = 'none';
                femaleAgeSection.style.display = 'none';
                maleSection.style.display = 'none';
            }
        });
    </script>


</body>

</html>