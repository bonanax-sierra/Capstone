<?php
session_start();
include 'db.php';
include 'auth.php';
include 'functions.php'; // ✅ Make sure this contains logUserAction()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username']);
    $password        = trim($_POST['password']);

    // Validation for empty fields
    if (empty($usernameOrEmail) && empty($password)) {
        $_SESSION['error'] = "⚠ Please enter your username/email and password.";
        header("Location: ../admin_login.php");
        exit;
    } elseif (empty($usernameOrEmail)) {
        $_SESSION['error'] = "⚠ Username or email is required.";
        header("Location: ../admin_login.php");
        exit;
    } elseif (empty($password)) {
        $_SESSION['error'] = "⚠ Password is required.";
        header("Location: ../admin_login.php");
        exit;
    }

    try {
        // Case-insensitive search
        $stmt = $pdo->prepare("
            SELECT user_id, username, email, password, role 
            FROM users 
            WHERE LOWER(username) = LOWER(:ue) OR LOWER(email) = LOWER(:ue)
            LIMIT 1
        ");
        $stmt->execute(['ue' => $usernameOrEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['error'] = "❌ No account found with that username or email.";
            header("Location: ../admin_login.php");
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            $_SESSION['error'] = "❌ Incorrect password. Please try again.";
            header("Location: ../admin_login.php");
            exit;
        }

        // ✅ Login successful
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['login_time'] = time();

        // ✅ Log the login action
        logUserAction($pdo, $user['user_id'], 'login', 'User logged in successfully');

        // Redirect
        if ($user['role'] === 'admin') {
            header("Location: ../admin_dashboard.php");
        } else {
            header("Location: ../admin_dashboard.php"); // could be changed for other roles
        }
        exit;

    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = "⚠ Something went wrong. Please try again later.";
        header("Location: ../admin_login.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../admin_login.php");
    exit;
}
