<?php
include 'includes/auth.php';
include 'includes/db.php';

// Require admin/staff authentication
requireStaffOrAdmin();

// ================================
// --- Top 5 Schools with Highest Risk ---
// ================================
$topStmt = $pdo->query("
    SELECT 
        s.name AS school_name,
        SUM(CASE WHEN a.risk_category = 'High' THEN 1 ELSE 0 END) AS high_risk,
        SUM(CASE WHEN a.risk_category = 'Medium' THEN 1 ELSE 0 END) AS medium_risk,
        SUM(CASE WHEN a.risk_category = 'Low' THEN 1 ELSE 0 END) AS low_risk
    FROM assessment a
    LEFT JOIN school s ON a.school_id = s.school_id
    GROUP BY a.school_id, s.name
    ORDER BY high_risk DESC
    LIMIT 5
");
$topSchools = $topStmt->fetchAll(PDO::FETCH_ASSOC);

// ================================
// --- Risk Trend Over Time (Monthly) ---
// ================================
$trendStmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%b') AS month,
        SUM(CASE WHEN risk_category = 'High' THEN 1 ELSE 0 END) AS high_risk,
        SUM(CASE WHEN risk_category = 'Medium' THEN 1 ELSE 0 END) AS medium_risk,
        SUM(CASE WHEN risk_category = 'Low' THEN 1 ELSE 0 END) AS low_risk
    FROM assessment
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY YEAR(created_at), MONTH(created_at)
");

$months = $high = $medium = $low = [];
while ($row = $trendStmt->fetch(PDO::FETCH_ASSOC)) {
    $months[] = $row['month'] ?? '';
    $high[] = (int) ($row['high_risk'] ?? 0);
    $medium[] = (int) ($row['medium_risk'] ?? 0);
    $low[] = (int) ($row['low_risk'] ?? 0);
}

// ================================
// --- Total Assessments ---
// ================================
$totalStmt = $pdo->query("SELECT COUNT(*) AS total FROM assessment");
$totalAssessments = (int) ($totalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

// ================================
// --- Risk Counts ---
// ================================
$riskStmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN risk_category = 'High' THEN 1 ELSE 0 END) AS high_risk,
        SUM(CASE WHEN risk_category = 'Medium' THEN 1 ELSE 0 END) AS medium_risk,
        SUM(CASE WHEN risk_category = 'Low' THEN 1 ELSE 0 END) AS low_risk
    FROM assessment
");
$riskData = $riskStmt->fetch(PDO::FETCH_ASSOC);

$totalHigh = (int) ($riskData['high_risk'] ?? 0);
$totalMedium = (int) ($riskData['medium_risk'] ?? 0);
$totalLow = (int) ($riskData['low_risk'] ?? 0);

// ================================
// --- High Risk Percentage ---
// ================================
$highPercentage = $totalAssessments > 0
    ? round(($totalHigh / $totalAssessments) * 100, 2)
    : 0;

// ================================
// --- Schools Covered ---
// ================================
$schoolStmt = $pdo->query("SELECT COUNT(DISTINCT school_id) AS schools FROM assessment WHERE school_id IS NOT NULL");
$schoolsCovered = (int) ($schoolStmt->fetch(PDO::FETCH_ASSOC)['schools'] ?? 0);

// ================================
// --- Latest Update ---
// ================================
$updateStmt = $pdo->query("SELECT DATE_FORMAT(MAX(created_at), '%b %Y') AS last_update FROM assessment");
$latestUpdate = $updateStmt->fetch(PDO::FETCH_ASSOC)['last_update'] ?? 'N/A';

// ================================
// --- Gender (Sex) Counts ---
// ================================
$genderStmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN sex = 'Male' THEN 1 ELSE 0 END) AS male,
        SUM(CASE WHEN sex = 'Female' THEN 1 ELSE 0 END) AS female,
        SUM(CASE WHEN sex = 'Other' THEN 1 ELSE 0 END) AS other_count
    FROM assessment
");
$genderData = $genderStmt->fetch(PDO::FETCH_ASSOC);

$totalMale = (int) ($genderData['male'] ?? 0);
$totalFemale = (int) ($genderData['female'] ?? 0);
$totalOther = (int) ($genderData['other_count'] ?? 0);

// ================================
// --- Encode Data for JS ---
// ================================
$chartData = [
    "months" => $months,
    "high" => $high,
    "medium" => $medium,
    "low" => $low,
    "topSchools" => $topSchools,
    "riskDistribution" => [
        "high" => $totalHigh,
        "medium" => $totalMedium,
        "low" => $totalLow
    ],
    "genderDistribution" => [
        "male" => $totalMale,
        "female" => $totalFemale,
        "other" => $totalOther
    ],
    "totalAssessments" => $totalAssessments,
    "highPercentage" => $highPercentage,
    "schoolsCovered" => $schoolsCovered,
    "latestUpdate" => $latestUpdate
];

echo "<script>const chartData = " . json_encode($chartData) . ";</script>";

$page_title = "Risk Analysis"; // Custom page title
include_once 'includes/header.php';
?>

<body class="has-charts">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header mb-4" style="margin-bottom:1.5rem;">
            <h2 style="font-weight:700;">Risk Analysis <small style="color:#6c757d;font-weight:400;">(Deep
                    Insights)</small></h2>
        </div>

        <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="includes/export_risk_excel.php" class="btn btn-success btn-lg"
                style="display:inline-flex; align-items:center; gap:0.5rem;">
                <i class="bi bi-file-earmark-excel"></i> Export to Excel
            </a>
        </div>

        <!-- Top 5 Schools -->
        <div class="top-schools mb-4">
            <h5 style="font-weight:600;margin-bottom:1rem;">Top 5 Schools with Highest Risk</h5>
            <div style="overflow-x:auto; box-shadow:0 2px 6px rgba(0,0,0,0.1); border-radius:0.5rem;">
                <table class="table table-striped table-hover align-middle mb-0" style="min-width:100%;">
                    <thead class="table-dark">
                        <tr>
                            <th>School</th>
                            <th>High Risk Cases</th>
                            <th>Moderate</th>
                            <th>Low</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topSchools as $school): ?>
                            <tr style="transition: background-color 0.3s ease;"
                                onmouseover="this.style.backgroundColor='rgba(0,0,0,0.05)'"
                                onmouseout="this.style.backgroundColor=''">
                                <td><?= htmlspecialchars($school['school_name'] ?? '') ?></td>
                                <td style="color:#00000;font-weight:600;"><?= (int) ($school['high_risk'] ?? 0) ?></td>
                                <td style="color:#00000;"><?= (int) ($school['medium_risk'] ?? 0) ?></td>
                                <td style="color:#00000;"><?= (int) ($school['low_risk'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary Cards with Animated Counters -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div style="
            text-align:center; 
            padding:1.2rem; 
            box-shadow:0 2px 8px rgba(0,0,0,0.08); 
            border-radius:0.75rem; 
            border: 1px solid #dee2e6; 
            background: #fff;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;"
                    onmouseover="this.style.transform='translateY(-3px) scale(1.03)'; this.style.boxShadow='0 10px 25px rgba(0,0,0,0.15)'; this.style.borderColor='#0d6efd';"
                    onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'; this.style.borderColor='#dee2e6';">
                    <h6 style="color:#6c757d;">Total Assessments</h6>
                    <h3 style="color:#00000;font-weight:700;" id="totalAssessmentsCounter">0</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div style="
            text-align:center; 
            padding:1.2rem; 
            box-shadow:0 2px 8px rgba(0,0,0,0.08); 
            border-radius:0.75rem; 
            border: 1px solid #dee2e6; 
            background: #fff;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;"
                    onmouseover="this.style.transform='translateY(-3px) scale(1.03)'; this.style.boxShadow='0 10px 25px rgba(0,0,0,0.15)'; this.style.borderColor='#dc3545';"
                    onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'; this.style.borderColor='#dee2e6';">
                    <h6 style="color:#6c757d;">High Risk %</h6>
                    <h3 style="color:#00000;font-weight:700;" id="highRiskCounter">0%</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div style="
            text-align:center; 
            padding:1.2rem; 
            box-shadow:0 2px 8px rgba(0,0,0,0.08); 
            border-radius:0.75rem; 
            border: 1px solid #dee2e6; 
            background: #fff;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;"
                    onmouseover="this.style.transform='translateY(-3px) scale(1.03)'; this.style.boxShadow='0 10px 25px rgba(0,0,0,0.15)'; this.style.borderColor='#28a745';"
                    onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'; this.style.borderColor='#dee2e6';">
                    <h6 style="color:#6c757d;">Schools Covered</h6>
                    <h3 style="color:#00000;font-weight:700;" id="schoolsCoveredCounter">0</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div style="
            text-align:center; 
            padding:1.2rem; 
            box-shadow:0 2px 8px rgba(0,0,0,0.08); 
            border-radius:0.75rem; 
            border: 1px solid #dee2e6; 
            background: #fff;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;"
                    onmouseover="this.style.transform='translateY(-3px) scale(1.03)'; this.style.boxShadow='0 10px 25px rgba(0,0,0,0.15)'; this.style.borderColor='#000000ff';"
                    onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'; this.style.borderColor='#dee2e6';">
                    <h6 style="color:#6c757d;">Latest Update</h6>
                    <h3 style="color:#000000;font-weight:700;" id="latestUpdateCounter">0</h3>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div style="padding:1rem; box-shadow:0 2px 8px rgba(0,0,0,0.08); border-radius:0.5rem; transition: transform 0.25s ease, box-shadow 0.25s ease;"
                    onmouseover="this.style.transform='translateY(-3px) scale(1.02)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'"
                    onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                    <h5 style="font-weight:600;margin-bottom:1rem;">Trend Over Time</h5>
                    <canvas id="riskTrendChart" style="width:100%; height: 200px;"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div style="padding:1rem; box-shadow:0 2px 8px rgba(0,0,0,0.08); border-radius:0.5rem; transition: transform 0.25s ease, box-shadow 0.25s ease;"
                    onmouseover="this.style.transform='translateY(-3px) scale(1.02)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'"
                    onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                    <h5 style="font-weight:600;margin-bottom:1rem;">Risk by School</h5>
                    <canvas id="riskBarChart" style="width:100%; height: 200px;"></canvas>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div style="padding:1rem; box-shadow:0 2px 8px rgba(0,0,0,0.08); border-radius:0.5rem; transition: transform 0.25s ease, box-shadow 0.25s ease;"
                    onmouseover="this.style.transform='translateY(-3px) scale(1.02)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'"
                    onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                    <h5 style="font-weight:600;margin-bottom:1rem;">Risk Distribution</h5>
                    <div style="height:300px;"> <!-- container height -->
                        <canvas id="riskPieChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div style="padding:1rem; box-shadow:0 2px 8px rgba(0,0,0,0.08); border-radius:0.5rem; transition: transform 0.25s ease, box-shadow 0.25s ease;"
                    onmouseover="this.style.transform='translateY(-3px) scale(1.02)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'"
                    onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                    <h5 style="font-weight:600;margin-bottom:1rem;">Sex Distribution</h5>
                    <div style="height:300px;"> <!-- container height -->
                        <canvas id="genderDoughnutChart"></canvas>
                    </div>
                </div>
            </div>
        </div>


        <!-- Animated Counters Script -->
        <script>
            function animateCounter(id, endValue, duration = 1500, suffix = '') {
                const el = document.getElementById(id);
                let start = 0;
                const stepTime = Math.abs(Math.floor(duration / endValue));
                const timer = setInterval(() => {
                    start += 1;
                    el.innerText = start + suffix;
                    if (start >= endValue) clearInterval(timer);
                }, stepTime);
            }

            // Initialize counters
            animateCounter('totalAssessmentsCounter', <?= $totalAssessments ?>);
            animateCounter('highRiskCounter', <?= $highPercentage ?>, 1500, '%');
            animateCounter('schoolsCoveredCounter', <?= $schoolsCovered ?>);
            animateCounter('latestUpdateCounter', <?= htmlspecialchars($latestUpdate ?? 0) ?>);
        </script>

    </div>

    <script src="js/sidebar-highlight.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {

            // Check if chartData exists
            if (typeof chartData === "undefined") {
                console.error("chartData missing!");
                return;
            }

            // Get values from PHP -> JS
            const totalAssessments = chartData.totalAssessments ?? 0;
            const highPercentage = chartData.highPercentage ?? 0;
            const schoolsCovered = chartData.schoolsCovered ?? 0;
            const latestUpdate = chartData.latestUpdate ?? "N/A";

            // Insert values into cards
            document.getElementById("totalAssessmentsCounter").textContent = totalAssessments;
            document.getElementById("highRiskCounter").textContent = highPercentage + "%";
            document.getElementById("schoolsCoveredCounter").textContent = schoolsCovered;
            document.getElementById("latestUpdateCounter").textContent = latestUpdate;
        });

        function animateCounter(id, target, suffix = "") {
            const element = document.getElementById(id);
            let current = 0;
            const step = target / 40;

            const counter = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(counter);
                }
                element.textContent = Math.floor(current) + suffix;
            }, 20);
        }

        document.addEventListener("DOMContentLoaded", () => {
            animateCounter("totalAssessmentsCounter", chartData.totalAssessments);
            animateCounter("highRiskCounter", chartData.highPercentage, "%");
            animateCounter("schoolsCoveredCounter", chartData.schoolsCovered);
            document.getElementById("latestUpdateCounter").textContent = chartData.latestUpdate;
        });


        // Charts initialization using chartData object
        const ctxLine = document.getElementById('riskTrendChart');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: chartData.months,
                datasets: [
                    { label: 'High Risk', data: chartData.high, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.1)', tension: 0.3 },
                    { label: 'Medium Risk', data: chartData.medium, borderColor: '#ffc107', backgroundColor: 'rgba(255,193,7,0.1)', tension: 0.3 },
                    { label: 'Low Risk', data: chartData.low, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.1)', tension: 0.3 }
                ]
            }
        });

        const ctxBar = document.getElementById('riskBarChart');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: chartData.topSchools.map(s => s.school_name ?? ''),
                datasets: [
                    { label: 'High Risk', data: chartData.topSchools.map(s => s.high_risk ?? 0), backgroundColor: '#dc3545' },
                    { label: 'Medium Risk', data: chartData.topSchools.map(s => s.medium_risk ?? 0), backgroundColor: '#ffc107' },
                    { label: 'Low Risk', data: chartData.topSchools.map(s => s.low_risk ?? 0), backgroundColor: '#28a745' }
                ]
            },
            options: { respsonsive: true, plugins: { legend: { position: 'top' } } }
        });

        const ctxPie = document.getElementById('riskPieChart');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['High Risk', 'Medium Risk', 'Low Risk'],
                datasets: [{
                    data: [chartData.riskDistribution.high, chartData.riskDistribution.medium, chartData.riskDistribution.low],
                    backgroundColor: ['#dc3545', '#ffc107', '#28a745'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // crucial
                plugins: { legend: { position: 'right', labels: { font: { size: 14 } } } }
            }
        });

        const ctxDoughnut = document.getElementById('genderDoughnutChart');
        new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female', 'Other'],
                datasets: [{
                    data: [chartData.genderDistribution.male, chartData.genderDistribution.female, chartData.genderDistribution.other],
                    backgroundColor: ['#007bff', '#e83e8c', '#6c757d'],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // crucial
                plugins: { legend: { position: 'right', labels: { font: { size: 14 } } } }
            }
        });

    </script>

</body>

</html>