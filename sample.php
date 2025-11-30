<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $pageTitle ?? 'Dashboard' ?> | Risk Analytics</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    body {
      background-color: #f4f6f9;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .sidebar {
      width: 240px;
      position: fixed;
      top: 0; left: 0; bottom: 0;
      background: #343a40;
      padding-top: 60px;
    }
    .sidebar a {
      display: block;
      padding: 12px 20px;
      color: #adb5bd;
      text-decoration: none;
    }
    .sidebar a:hover, .sidebar a.active {
      background: #495057;
      color: #fff;
    }
    .navbar {
      position: fixed;
      top: 0; left: 240px; right: 0;
      background: #fff;
      border-bottom: 1px solid #dee2e6;
      padding: 10px 20px;
      z-index: 1030;
    }
    .main-content {
      margin-left: 240px;
      margin-top: 70px;
      padding: 20px;
    }
    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <a href="dashboard.php" class="active"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
    <a href="risk_analysis.php"><i class="bi bi-bar-chart-line me-2"></i>Risk Analysis</a>
    <a href="reports.php"><i class="bi bi-file-earmark-text me-2"></i>Reports</a>
    <a href="students.php"><i class="bi bi-people me-2"></i>Students</a>
    <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
  </div>

  <!-- Navbar -->
  <nav class="navbar">
    <span class="fw-bold"><?= $pageTitle ?? 'Dashboard' ?></span>
  </nav>

  <!-- Main Content -->
  <div class="main-content">
    <?php if (isset($content)) echo $content; ?>
  </div>

</body>
</html>
