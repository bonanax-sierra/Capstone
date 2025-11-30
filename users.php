<?php
session_start();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Adolescents Directory</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="css/sidebar.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
            margin-bottom: 20px;
        }

        .filter-row .form-control,
        .filter-row .form-select {
            font-size: 0.9rem;
        }

        .table-hover tbody tr:hover {
            background-color: #f1f1f1;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-title">Adolescent Records</div>

        <!-- Filters -->
        <div class="row filter-row g-3 mb-3">
            <div class="col-md-3">
                <input type="text" class="form-control" id="filterName" placeholder="Filter by name">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" id="filterSchool" placeholder="Filter by school">
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" id="filterAge" placeholder="Filter by age">
            </div>
            <div class="col-md-4">
                <select class="form-select" id="filterRisk">
                    <option value="">Filter by risk level</option>
                    <option>High Risk</option>
                    <option>Moderate Risk</option>
                    <option>Low Risk</option>
                </select>
            </div>
        </div>

        <!-- Adolescent Table -->
        <table class="table table-bordered table-hover" id="adolescentTable">
            <thead class="table-light">
                <tr>
                    <th>Full Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>School</th>
                    <th>Grade</th>
                    <th>Last Risk Level</th>
                </tr>
            </thead>
            <tbody>
                <tr data-bs-toggle="modal" data-bs-target="#profileModal"
                    data-name="Jane Dela Cruz" data-age="14" data-gender="Female"
                    data-school="Rizal High School" data-grade="9" data-risk="High Risk">
                    <td>Jane Dela Cruz</td>
                    <td>14</td>
                    <td>Female</td>
                    <td>Rizal High School</td>
                    <td>Grade 9</td>
                    <td><span class="badge bg-danger">High Risk</span></td>
                </tr>
                <tr data-bs-toggle="modal" data-bs-target="#profileModal"
                    data-name="Mark Santos" data-age="13" data-gender="Male"
                    data-school="Quezon High School" data-grade="8" data-risk="Moderate Risk">
                    <td>Mark Santos</td>
                    <td>13</td>
                    <td>Male</td>
                    <td>Quezon High School</td>
                    <td>Grade 8</td>
                    <td><span class="badge bg-warning text-dark">Moderate Risk</span></td>
                </tr>
                <tr data-bs-toggle="modal" data-bs-target="#profileModal"
                    data-name="Alexa Mendoza" data-age="15" data-gender="Female"
                    data-school="Pasig National HS" data-grade="10" data-risk="Low Risk">
                    <td>Alexa Mendoza</td>
                    <td>15</td>
                    <td>Female</td>
                    <td>Pasig National HS</td>
                    <td>Grade 10</td>
                    <td><span class="badge bg-success">Low Risk</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title"><i class="bi bi-person-lines-fill me-2"></i>Adolescent Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body px-4 py-3">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted">👤 Personal Information</h6>
                                <p><strong>Name:</strong> <span id="modalName"></span></p>
                                <p><strong>Age:</strong> <span id="modalAge"></span></p>
                                <p><strong>Sex:</strong> <span id="modalGender"></span></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted">🏫 School Details</h6>
                                <p><strong>School:</strong> <span id="modalSchool"></span></p>
                                <p><strong>Grade:</strong> <span id="modalGrade"></span></p>
                                <p><strong>Risk Level:</strong> <span id="modalRisk" class="fw-bold"></span></p>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <h6 class="text-primary mb-2"><i class="bi bi-journal-medical me-2"></i>Assessment Notes</h6>
                        <p class="bg-light p-3 rounded">Student is showing signs of emotional distress and has been referred to counseling for further support.</p>

                        <h6 class="text-primary mt-4 mb-2"><i class="bi bi-lightbulb me-2"></i>Recommendation</h6>
                        <p class="bg-light p-3 rounded">Monitor weekly. Consider peer support sessions and additional academic guidance.</p>
                    </div>
                </div>

                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar-highlight.js"></script>

    <script>
        // Populate modal on row click
        $('#adolescentTable tbody tr').click(function() {
            const row = $(this);
            $('#modalName').text(row.data('name'));
            $('#modalAge').text(row.data('age'));
            $('#modalGender').text(row.data('gender'));
            $('#modalSchool').text(row.data('school'));
            $('#modalGrade').text(row.data('grade'));
            $('#modalRisk').text(row.data('risk'));
        });

        // Filter logic
        function filterTable() {
            const name = $('#filterName').val().toLowerCase();
            const school = $('#filterSchool').val().toLowerCase();
            const age = $('#filterAge').val();
            const risk = $('#filterRisk').val();

            $('#adolescentTable tbody tr').each(function() {
                const row = $(this);
                const rowName = row.find('td:eq(0)').text().toLowerCase();
                const rowAge = row.find('td:eq(1)').text();
                const rowSchool = row.find('td:eq(3)').text().toLowerCase();
                const rowRisk = row.find('td:eq(5)').text().trim();

                const matchesName = !name || rowName.includes(name);
                const matchesSchool = !school || rowSchool.includes(school);
                const matchesAge = !age || rowAge === age;
                const matchesRisk = !risk || rowRisk === risk;

                if (matchesName && matchesSchool && matchesAge && matchesRisk) {
                    row.show();
                } else {
                    row.hide();
                }
            });
        }

        $('#filterName, #filterSchool, #filterAge, #filterRisk').on('input change', filterTable);
    </script>
</body>

</html>