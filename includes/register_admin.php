<?php
session_start();
include 'db.php'; // DB connection

// Expected admin registration token
$expected_token = "ADMIN-2025-MYSECRET123";

if (isset($_POST['register_admin'])) {
    $admin_token = trim($_POST['admin_token'] ?? '');
    $username    = trim($_POST['username'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';

    // ❌ Invalid token
    if ($admin_token !== $expected_token) {
        header("Location: ../register.php?error=token");
        exit;
    }

    // ❌ Empty fields
    if (empty($username) || empty($email) || empty($password)) {
        header("Location: ../register.php?error=empty");
        exit;
    }

    // ✅ Check for duplicate username or email
    $check = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $check->execute([$username, $email]);
    if ($check->rowCount() > 0) {
        header("Location: ../register.php?error=duplicate");
        exit;
    }

    // ✅ Insert new admin
    try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
        $stmt->execute([$username, $hashed_password, $email]);

        $_SESSION['success'] = "✅ Admin registered successfully! You can now login.";
        header("Location: ../admin_login.php");
        exit;
    } catch (PDOException $e) {
        header("Location: ../register.php?error=database");
        exit;
    }
}
