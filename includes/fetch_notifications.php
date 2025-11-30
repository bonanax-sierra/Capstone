<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

// Ensure logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// 1️⃣ Count "High Risk" cases
$stmt = $pdo->query("
    SELECT COUNT(*) AS high_risk_count
    FROM assessment
    WHERE risk_category = 'High'
");
$highRiskCount = $stmt->fetch(PDO::FETCH_ASSOC)['high_risk_count'] ?? 0;

// 2️⃣ Create notification if it doesn’t exist today
$check = $pdo->prepare("
    SELECT COUNT(*) 
    FROM notifications 
    WHERE title = 'High Risk Alert' 
      AND DATE(created_at) = CURDATE()
");
$check->execute();

if ($check->fetchColumn() == 0 && $highRiskCount > 0) {
    $msg = "There are currently $highRiskCount adolescents classified as High Risk in the latest assessments.";
    $insert = $pdo->prepare("
        INSERT INTO notifications (title, message, type, target_user_id)
        VALUES ('High Risk Alert', :msg, 'warning', :user_id)
    ");
    $insert->execute([':msg' => $msg, ':user_id' => $user_id]);
}

// 3️⃣ Fetch latest notifications
$notif = $pdo->prepare("
    SELECT title, message, type, created_at
    FROM notifications
    WHERE target_user_id = :user_id
    ORDER BY created_at DESC
    LIMIT 5
");
$notif->execute([':user_id' => $user_id]);
$notifications = $notif->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'high_risk_count' => $highRiskCount,
    'notifications' => $notifications
]);
?>
