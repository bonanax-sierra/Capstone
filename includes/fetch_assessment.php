<?php
include 'db.php'; // your PDO connection
header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM assessments WHERE assessment_id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
    echo json_encode(['success' => true, 'assessment' => $data]);
} else {
    echo json_encode(['success' => false]);
}
?>
