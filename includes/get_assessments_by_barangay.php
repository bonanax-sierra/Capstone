<?php
include 'db.php';

$stmt = $pdo->query("
    SELECT ai.barangay, COUNT(a.assessment_id) AS assessment_count
    FROM assessment a
    LEFT JOIN activity_info ai ON a.activity_id = ai.activity_id
    GROUP BY ai.barangay
");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$output = [];
foreach($results as $row){
    $output[$row['barangay']] = (int)$row['assessment_count'];
}

echo json_encode($output);
