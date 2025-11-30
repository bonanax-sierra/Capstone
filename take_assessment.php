<?php
session_start();

// Include the database connection file
include 'includes/db.php';

// Fetch schools
try {
  $stmt = $pdo->prepare("SELECT school_id, name FROM school");
  $stmt->execute();
  $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Query failed: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Assessment Form</title>

  <!-- CSS -->
  <link rel="stylesheet" href="css/sidebar.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      display: flex;
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f1f3f6;
      overflow-x: hidden;
    }

    .main-content {
      flex: 1;
      padding: 40px;
      background-color: #f1f3f6;
      margin-left: 250px;
      /* space for sidebar */
      transition: margin-left 0.3s ease-in-out;
    }

    .main-title {
      font-size: 1.8rem;
      font-weight: bold;
      color: #343a40;
      margin-bottom: 30px;
    }

    .container {
      background-color: #ffffff;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .section-title {
      margin-top: 30px;
      font-weight: 600;
      color: #495057;
      font-size: 1.2rem;
      border-bottom: 1px solid #dee2e6;
      padding-bottom: 8px;
    }

    .result-box {
      margin-top: 30px;
      padding: 20px;
      border-radius: 10px;
      background-color: #e9f7ef;
      border-left: 6px solid #28a745;
    }

    .result-box h5 {
      margin-bottom: 5px;
    }

    /* 🔧 Responsive adjustments */
    @media (max-width: 992px) {
      .main-content {
        padding: 30px;
        margin-left: 0;
      }

      .container {
        padding: 25px;
      }

      .main-title {
        font-size: 1.6rem;
        text-align: center;
      }

      .section-title {
        font-size: 1.1rem;
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 20px;
      }

      .main-title {
        font-size: 1.4rem;
      }

      .container {
        padding: 20px;
      }

      .result-box {
        padding: 15px;
      }
    }

    @media (max-width: 576px) {
      .main-title {
        font-size: 1.2rem;
      }

      .container {
        padding: 15px;
      }

      .section-title {
        font-size: 1rem;
      }

      .result-box h5 {
        font-size: 1rem;
      }
    }

    /* Show button only on mobile */
    @media (max-width: 768px) {
      .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        padding: 1rem;
        background: linear-gradient(to bottom, #0d6efd, #00458a);
        border-bottom: 1px solid #dee2e6;
        box-shadow: none;
      }

      .main-content {
        margin-left: 0;
        padding: 20px;
      }
    }
  </style>
</head>

<body>

  <!-- Sidebar -->
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <div class="main-title">Adolescent Risk Assessment Form</div>

    <div class="container">

      <form id="importForm" action="includes/import.php" method="POST" enctype="multipart/form-data">
        <!-- Hidden file input -->
        <input type="file" id="excelFile" name="excel_file" accept=".xls,.xlsx" style="display: none;" required>

        <!-- Trigger file selection -->
        <button type="button" id="chooseFileBtn" class="btn btn-secondary">Choose Excel File</button>
        <span id="fileName">No file chosen</span>

        <!-- Submit form manually -->
        <button type="submit" id="submitBtn" class="btn btn-lg rounded-pill px-5"
          style="background-color: #28a745; color: white; border: none;" name="import">
          Import to Database
        </button>
      </form>



      <!-- Import Status Message -->
      <div id="importStatus" class="alert d-none mt-3"></div>


      <!-- Modal -->
      <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="feedbackModalLabel">Import Result</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <?php
              if (isset($_SESSION['modal_message'])) {
                echo $_SESSION['modal_message'];
                unset($_SESSION['modal_message']); // clear after display
              }
              ?>
            </div>
          </div>
        </div>
      </div>


      <form id="assessmentForm" method="POST" action="includes/proc_assessment.php">
        <!-- Adolescent Info -->
        <div class="section-title">Adolescent Information</div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="name" name="name" required />
          </div>
          <div class="col-md-6 mb-3">
            <label for="address" class="form-label">Address</label>
            <input type="text" class="form-control" id="address" name="address" required />
          </div>
          <div class="col-md-3 mb-3">
            <label for="age" class="form-label">Age</label>
            <input type="number" class="form-control" id="age" name="age" required />
          </div>
          <div class="col-md-3 mb-3">
            <label for="gender" class="form-label">Sex</label>
            <select class="form-select" id="gender" name="gender" required>
              <option value="">Choose...</option>
              <option>Male</option>
              <option>Female</option>
            </select>
          </div>
          <div class="col-md-3 mb-3">
            <label for="dob" class="form-label">Date of Birth</label>
            <input type="date" class="form-control" id="dob" name="dob" required />
          </div>
        </div>

        <!-- Civil & Employment -->
        <div class="section-title">Status Information</div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Civil Status</label>
            <select class="form-select" id="civilStatus" name="civilStatus" required>
              <option value="">Choose...</option>
              <option>Single</option>
              <option>Married</option>
              <option>Living in</option>
              <option>Not Living-in</option>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Employment Status</label>
            <select class="form-select" id="employmentStatus" name="employmentStatus" required>
              <option value="">Choose...</option>
              <option>Employed</option>
              <option>Unemployed</option>
              <option>Underemployed</option>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">School Attending Status</label>
            <select class="form-select" id="schoolStatus" name="schoolStatus" required>
              <option value="">Choose...</option>
              <option>Out of School Youth</option>
              <option>In School Youth</option>
              <option>Vocational/Technical</option>
            </select>
          </div>
        </div>

        <!-- Grade Level -->
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="schoolName" class="form-label">School Name</label>
            <select class="form-control" id="schoolName" name="schoolName" required>
              <option value="">Select a school</option>
              <?php
              foreach ($schools as $school) {
                echo "<option value='" . htmlspecialchars($school['school_id']) . "'>" . htmlspecialchars($school['name']) . "</option>";
              }
              ?>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="gradeLevel" class="form-label">Grade Level</label>
            <input type="text" class="form-control" id="gradeLevel" name="gradeLevel" required />
          </div>
        </div>

        <!-- Current Issues -->
        <div class="section-title">Current Problems/Issues (Select Top 3)</div>
        <div class="row">
          <div class="col-md-4">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Love Life" /> Love Life</div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Bullying" /> Bullying</div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Substance Abuse" /> Substance Abuse</div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="School Works" /> School Works</div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Family Problem" /> Family Problem</div>
          </div>
          <div class="col-md-4">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Peer Pressure" /> Peer Pressure</div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Too early sexual activity" /> Too early sexual activity</div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Medical/Dental" /> Medical/Dental</div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Mental Health" /> Mental Health</div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Spiritual Emptiness" /> Spiritual Emptiness</div>
          </div>
          <div class="col-md-4">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Violence (VAWC)" /> Violence (VAWC)</div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="issues[]" value="Others" /> Others, please specify:</div>
            <input type="text" class="form-control mt-1" name="othersDetail" />
          </div>
        </div>

        <!-- Desired Services -->
        <div class="section-title">Desired Services (Choose One)</div>
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
        <div class="section-title">Sexuality</div>
        <div class="mb-3">
          <label class="form-label">Have you ever been pregnant? (If female)</label>
          <select class="form-select" id="pregnant" name="pregnant">
            <option value="">Choose...</option>
            <option>No</option>
            <option>Yes</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">If yes, at what age?</label>
          <select class="form-select" id="pregnantAge" name="pregnantAge">
            <option value="">Choose...</option>
            <option>10-14</option>
            <option>15-17</option>
            <option>18-19</option>
          </select>
        </div>

        <!-- Mobile Accessibility -->
        <div class="section-title">Mobile Accessibility</div>
        <div class="mb-3">
          <label class="form-label">Do you use a mobile phone?</label>
          <select class="form-select" id="mobileUse" name="mobileUse">
            <option value="">Choose...</option>
            <option>No</option>
            <option>Yes</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Reason/s for using mobile phone</label>
          <input type="text" class="form-control" id="mobileReason" name="mobileReason" />
        </div>
        <div class="mb-3">
          <label class="form-label">If Yes, which mobile phone(s) do you use?</label>
          <input type="text" class="form-control" id="mobilePhoneBrand" name="mobilePhoneBrand" />
        </div>

        <!-- Hidden fields populated from modal -->
        <input type="hidden" name="municipality" id="municipalityInput">
        <input type="hidden" name="barangay" id="barangayInput">
        <input type="hidden" name="activityTitle" id="activityTitleInput">
        <input type="hidden" name="activityType" id="activityTypeInput">
        <input type="hidden" name="activityDate" id="activityDateInput">

        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#activityModal">
          Submit
        </button>
      </form>
      

      <!-- Success Modal -->
      <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content text-center">
            <div class="modal-header bg-success text-white">
              <h5 class="modal-title w-100" id="successModalLabel">Success</h5>
            </div>
            <div class="modal-body">
              <p>Your assessment has been successfully submitted!</p>
            </div>
          </div>
        </div>
      </div>


      <!-- Result Box -->
      <div class="result-box d-none" id="resultBox">
        <h5>🧠 Risk Score Result:</h5>
        <p id="riskScore" class="fw-bold mb-1"></p>
        <h5>📌 Suggested Action:</h5>
        <p id="suggestedAction" class="mb-0"></p>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    // document.getElementById("assessmentForm").addEventListener("submit", function(e) {
    //   e.preventDefault();

    //   let behaviorScore = 0;
    //   let emotionalScore = 0;

    //   document.querySelectorAll(".behavior").forEach((select) => {
    //     behaviorScore += parseInt(select.value);
    //   });

    //   document.querySelectorAll(".emotional").forEach((select) => {
    //     emotionalScore += parseInt(select.value);
    //   });

    //   const totalScore = behaviorScore + emotionalScore;
    //   let riskLevel = "";
    //   let action = "";

    //   if (totalScore >= 6) {
    //     riskLevel = "High Risk";
    //     action = "Immediate referral to a mental health professional is recommended.";
    //   } else if (totalScore >= 3) {
    //     riskLevel = "Moderate Risk";
    //     action = "Monitor and consider counseling sessions.";
    //   } else {
    //     riskLevel = "Low Risk";
    //     action = "No immediate action required. Continue regular observation.";
    //   }

    //   document.getElementById("riskScore").textContent = riskLevel;
    //   document.getElementById("suggestedAction").textContent = action;
    //   document.getElementById("resultBox").classList.remove("d-none");
    // });

    document.getElementById('assessmentForm').addEventListener('submit', (event) => {
      // Create or reuse error container
      let errorContainer = document.querySelector('.alert.alert-danger');
      if (!errorContainer) {
        errorContainer = document.createElement('div');
        errorContainer.className = 'alert alert-danger';
        document.getElementById('assessmentForm').prepend(errorContainer);
      }
      errorContainer.innerHTML = '';
      errorContainer.style.display = 'none';

      // Helper function to show error
      function showError(message) {
        errorContainer.innerHTML += `<p>${message}</p>`;
        errorContainer.style.display = 'block';
        event.preventDefault(); // Prevent submission on error
        return false;
      }

      // Get form elements
      const name = document.getElementById('name').value.trim();
      const address = document.getElementById('address').value.trim();
      const age = parseInt(document.getElementById('age').value, 10);
      const gender = document.getElementById('gender').value;
      const dob = new Date(document.getElementById('dob').value);
      const civilStatus = document.getElementById('civilStatus').value;
      const employmentStatus = document.getElementById('employmentStatus').value;
      const schoolStatus = document.getElementById('schoolStatus').value;
      const schoolName = document.getElementById('schoolName').value;
      const gradeLevel = document.getElementById('gradeLevel').value.trim();
      const desiredService = document.getElementById('desiredService').value;
      const pregnant = document.getElementById('pregnant').value;
      const pregnantAge = document.getElementById('pregnantAge').value;
      const mobileUse = document.getElementById('mobileUse').value;
      const mobileReason = document.getElementById('mobileReason').value.trim();
      const mobilePhoneBrand = document.getElementById('mobilePhoneBrand').value.trim();

      // Validate required fields
      if (!name) return showError('Full Name is required.');
      if (!address) return showError('Address is required.');
      if (isNaN(age) || age < 10 || age > 19) return showError('Age must be a number between 10 and 19.');
      if (!gender) return showError('Sex is required.');
      if (isNaN(dob.getTime())) return showError('Invalid Date of Birth.');
      if (!civilStatus) return showError('Civil Status is required.');
      if (!employmentStatus) return showError('Employment Status is required.');
      if (!schoolStatus) return showError('School Attending Status is required.');
      if (!schoolName) return showError('School Name is required.');
      if (!gradeLevel) return showError('Grade Level is required.');
      if (!desiredService) return showError('Desired Service is required.');

      // Validate DOB
      const currentDate = new Date('2025-07-17');
      if (dob > currentDate) return showError('Date of Birth cannot be in the future.');

      // Validate age-DOB consistency
      let calculatedAge = currentDate.getFullYear() - dob.getFullYear();
      const monthDiff = currentDate.getMonth() - dob.getMonth();
      const dayDiff = currentDate.getDate() - dob.getDate();
      if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) calculatedAge--;
      if (Math.abs(age - calculatedAge) > 1) return showError('Age does not match Date of Birth.');

      // Validate Current Problems/Issues (exactly 3 selections)
      const checkboxes = document.querySelectorAll('.form-check-input[type="checkbox"]');
      const checkedIssues = Array.from(checkboxes).filter(cb => cb.checked);
      if (checkedIssues.length !== 3) return showError('Please select exactly three Current Problems/Issues.');

      // Validate "Others" text input if selected
      const othersCheckbox = Array.from(checkboxes).find(cb => cb.value === 'Others');
      const othersInput = document.querySelector('.form-control.mt-1');
      if (othersCheckbox.checked && !othersInput.value.trim()) {
        return showError('Please specify details for "Others" in Current Problems/Issues.');
      }

      // Validate pregnancy fields
      if (gender === 'Female' && !pregnant) return showError('Pregnancy status is required for females.');
      if (gender === 'Female' && pregnant === 'Yes' && !pregnantAge) {
        return showError('Age of pregnancy is required if pregnant.');
      }

      // Validate mobile phone fields
      if (!mobileUse) return showError('Mobile phone usage status is required.');
      if (mobileUse === 'Yes' && (!mobileReason || !mobilePhoneBrand)) {
        return showError('Reason for mobile use and mobile phone brand are required if you use a mobile phone.');
      }

      // If all validations pass, show alert and submit
      alert('Form is valid! Ready to submit.');
      // Form submits naturally (no event.preventDefault() called)
    });

    document.addEventListener('DOMContentLoaded', function() {
      const chooseFileBtn = document.getElementById('chooseFileBtn');
      const fileInput = document.getElementById('excelFile');
      const fileNameSpan = document.getElementById('fileName');

      chooseFileBtn.addEventListener('click', function() {
        fileInput.click(); // Trigger file dialog
      });

      fileInput.addEventListener('change', function() {
        if (fileInput.files.length > 0) {
          fileNameSpan.textContent = fileInput.files[0].name;
        } else {
          fileNameSpan.textContent = 'No file chosen';
        }
      });
    });

    function triggerFileInput() {
      const fileInput = document.getElementById('excelFile');
      fileInput.click(); // open file dialog

      fileInput.onchange = function() {
        if (fileInput.files.length > 0) {
          document.getElementById('importForm').submit(); // auto-submit after selecting file
        }
      };
    }
  </script>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/sidebar-highlight.js"></script>

  <?php if (isset($_SESSION['alert'])): ?>
    <script>
      alert(`<?= addslashes($_SESSION['alert']) ?>`);
    </script>
    <?php unset($_SESSION['alert']); ?>
  <?php endif; ?>


  <?php if (isset($_SESSION['alert'])): ?>
    <script>
      alert("<?= addslashes($_SESSION['alert']) ?>");
    </script>
    <?php unset($_SESSION['alert']); ?>
  <?php endif; ?>

</body>

</html>