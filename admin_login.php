<?php
include 'includes/db.php'; // your PDO connection

session_start();

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Check CSRF token
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF validation failed.");
  }

  $username = $_POST['username'];
  $password = $_POST['password'];

  // Check user
  $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
  $stmt->execute([$username, $username]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user['password'])) {
    // Store session data
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    // Redirect based on role
    if ($user['role'] == 'admin') {
      header("Location: admin_dashboard.php");
    } else {
      header("Location: user_dashboard.php");
    }
    exit;
  } else {
    $_SESSION['error'] = "Invalid username or password.";
    header("Location: admin_login.php");
    exit;
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Adolescent Risk System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f1f3f6;
      height: 100vh;
      overflow: hidden;
    }

    .login-container {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100%;
      max-width: 100%;
      padding: 20px;
    }

    .login-wrapper {
      display: flex;
      width: 100%;
      max-width: 1000px;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
      background-color: #ffffff;
    }

    .login-image {
      flex: 1;
      background: url('https://www.rosemet.com/wp-content/uploads/2024/04/Understanding-What-is-Quantitative-Risk-Analysis-Cover.webp') no-repeat center center;
      background-size: cover;
      min-height: 400px;
    }

    .login-form {
      flex: 1;
      padding: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #ffffff;
    }

    .login-card {
      width: 100%;
      max-width: 400px;
    }

    .login-title {
      font-size: 1.8rem;
      font-weight: bold;
      text-align: center;
      margin-bottom: 25px;
      color: #343a40;
    }

    .form-control {
      border-radius: 8px;
    }

    .btn-primary {
      border-radius: 8px;
      padding: 10px;
      font-weight: 600;
    }

    .form-text {
      text-align: center;
      margin-top: 15px;
      font-size: 0.9rem;
      color: #6c757d;
    }

    @media (max-width: 768px) {
      .login-wrapper {
        flex-direction: column;
      }

      .login-image {
        height: 200px;
      }
    }
  </style>
</head>

<body>

  <!-- Main Adjustable Container -->
  <div class="login-container">
    <div class="login-wrapper">
      <!-- Left Side Image -->
      <div class="login-image"></div>



      <!-- Right Side Form -->
      <div class="login-form">
        <div class="login-card">
          <div class="login-title">Adolescent Risk System Login</div>
          <?php
          if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?php echo $_SESSION['error']; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php
            unset($_SESSION['error']); // clear after showing
          endif;
          ?>
          <form method="POST" action="includes/login_process.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="mb-3">
              <label for="username" class="form-label">Username or Email</label>
              <input type="text" class="form-control" id="username" name="username" required />
            </div>

            <div class="mb-3 position-relative">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required />
                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                  <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Login</button>
          </form>



          <!-- Back Button -->
          <a href="index.php" class="btn btn-secondary w-100 mt-2">← Back</a>

          <div class="form-text">
            Don't have an account? <a href="register.php">Register here</a>.
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.getElementById("togglePassword").addEventListener("click", function() {
      const passwordInput = document.getElementById("password");
      const toggleIcon = document.getElementById("toggleIcon");

      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleIcon.classList.remove("bi-eye");
        toggleIcon.classList.add("bi-eye-slash");
      } else {
        passwordInput.type = "password";
        toggleIcon.classList.remove("bi-eye-slash");
        toggleIcon.classList.add("bi-eye");
      }
    });
  </script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>