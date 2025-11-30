<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid access.");
}

$assessment_id = intval($_POST['assessment_id']);
$risk_ai = $_POST['risk_ai'];
$confidence = intval($_POST['confidence']);
$risk_validated = $_POST['risk_validated'];
$remarks = trim($_POST['remarks']);

// Save validation
$stmt = $pdo->prepare("INSERT INTO validation (
    assessment_id, risk_ai, ai_confidence, risk_validated, remarks, validated_at
) VALUES (?, ?, ?, ?, ?, NOW())");

$stmt->execute([$assessment_id, $risk_ai, $confidence, $risk_validated, $remarks]);

// Update main table with validated risk
$pdo->prepare("UPDATE assessment SET risk_category_validated = ? WHERE assessment_id = ?")
    ->execute([$risk_validated, $assessment_id]);

header("Location: ../enhanced_reports.php?validated=1");
exit;
?>
