<?php
$role = $_SESSION['role'] ?? null;
?>

<!-- Sidebar -->
<div class="sidebar d-flex flex-column">
  <h3>Risk Analytics for Adolescents</h3>

  <?php if ($role === 'admin'): ?>
    <!-- 🛠️ ADMIN SIDEBAR -->
    <a href="admin_dashboard.php" class="nav-link">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="risk_analysis.php" class="nav-link">
      <i class="bi bi-activity"></i> Risk Analysis
    </a>
    <a href="enhanced_reports.php" class="nav-link">
      <i class="bi bi-graph-up"></i> Enhanced Reports
    </a>
    <a href="activity_management.php" class="nav-link">
      <i class="bi bi-calendar-event"></i> Activities & Assessments
    </a>
    <a href="staff_management.php" class="nav-link">
      <i class="bi bi-people"></i> Staff Management
    </a>
    <a href="settings.php" class="nav-link">
      <i class="bi bi-gear"></i> Settings
    </a>
    <a href="system_status.php" class="nav-link">
      <i class="bi bi-activity"></i> System Status
    </a>

    <!-- 🔓 Logout Button -->
    <div class="mt-auto p-3">
      <a href="includes/logout.php" class="btn btn-danger w-100">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>

  <?php elseif ($role === 'staff'): ?>
    <!-- 👩‍💻 STAFF SIDEBAR -->
    <a href="admin_dashboard.php" class="nav-link">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="risk_analysis.php" class="nav-link">
      <i class="bi bi-activity"></i> Risk Analysis
    </a>
    <a href="enhanced_reports.php" class="nav-link">
      <i class="bi bi-graph-up"></i> Enhanced Reports
    </a>
    <a href="activity_management.php" class="nav-link">
      <i class="bi bi-calendar-event"></i> Activities & Assessments
    </a>

    <!-- 🔓 Logout Button -->
    <div class="mt-auto p-3">
      <a href="includes/logout.php" class="btn btn-danger w-100">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>

  <?php else: ?>
    <!-- 🌍 PUBLIC SIDEBAR (not logged in) -->
    <a href="index.php" class="nav-link">
      <i class="bi bi-house-door"></i> Home
    </a>
    <a href="about.php" class="nav-link">
      <i class="bi bi-info-circle"></i> About
    </a>

    <!-- 🔐 Login Button -->
    <div class="mt-auto p-3">
      <a href="admin_login.php" class="btn btn-primary w-100">
        <i class="bi bi-person-lock"></i> Login
      </a>
    </div>

  <?php endif; ?>
</div>
