<?php
include 'auth.php';
include 'db.php';
requireStaffOrAdmin();

$data = json_decode(file_get_contents('php://input'), true);
$assessment_id = $data['assessment_id'] ?? null;

if ($assessment_id) {
    $stmt = $pdo->prepare("DELETE FROM assessment WHERE assessment_id = ?");
    if ($stmt->execute([$assessment_id])) {
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Unable to delete assessment.']);
