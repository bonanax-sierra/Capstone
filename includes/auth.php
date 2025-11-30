<?php
session_start();

/**
 * ----------------------------
 * AUTHORIZATION FUNCTIONS
 * ----------------------------
 */

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: admin_login.php"); // shared login for staff/admin
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: index.php"); // fallback if not admin
        exit;
    }
}

function requireStaffOrAdmin() {
    if (!isLoggedIn() || !in_array($_SESSION['role'], ['admin', 'staff'])) {
        header("Location: index.php");
        exit;
    }
}

/**
 * ----------------------------
 * USER SESSION CHECKS
 * ----------------------------
 */

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function isStaff(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'staff';
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;

    return [
        'user_id'  => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email'    => $_SESSION['email'] ?? '',
        'role'     => $_SESSION['role'] ?? 'viewer'
    ];
}

/**
 * ----------------------------
 * LOGIN / LOGOUT
 * ----------------------------
 */

function loginUser(array $user, $pdo) {
    $_SESSION['user_id']    = $user['user_id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['email']      = $user['email'] ?? '';
    $_SESSION['role']       = $user['role'];
    $_SESSION['login_time'] = time();

    // Regenerate session ID for security
    session_regenerate_id(true);

    // Log login
    logUserAction($pdo, $user['user_id'], 'login', 'User logged in.');
}

function logoutUser($pdo, $reason = 'User logged out') {
    if (isset($_SESSION['user_id'])) {
        logUserAction($pdo, $_SESSION['user_id'], 'logout', $reason);
    }

    // Clear session
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}


/**
 * ----------------------------
 * SESSION TIMEOUT
 * ----------------------------
 */

function checkSessionTimeout($pdo, $timeout = 3600) {
    // No user logged in
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // If login_time is missing, initialize it
    if (!isset($_SESSION['login_time'])) {
        $_SESSION['login_time'] = time();
        return true;
    }

    // Check if timeout exceeded
    if (time() - $_SESSION['login_time'] > $timeout) {
        // Log the logout action due to timeout
        logoutUser($pdo, 'Session timed out');
        return false;
    }

    // Update last activity timestamp
    $_SESSION['login_time'] = time();
    return true;
}


/**
 * ----------------------------
 * UTILITY FUNCTIONS
 * ----------------------------
 */

function sanitizeInput($input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateCSRF(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRF($token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ----------------------------
 * LOGGING FUNCTION
 * ----------------------------
 */

function logUserAction($pdo, $user_id, $action, $details = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_logs (user_id, action, details, ip_address) 
            VALUES (:user_id, :action, :details, :ip_address)
        ");

        $stmt->execute([
            'user_id'    => $user_id,
            'action'     => $action,
            'details'    => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("User log error: " . $e->getMessage());
    }
}
