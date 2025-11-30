<?php
require 'db.php';

if (!isset($_GET['school_id'])) {
    echo json_encode(['error' => 'School ID not provided.']);
    exit;
}

$schoolId = intval($_GET['school_id']);

try {
    // 1. Fetch school name
    $schoolStmt = $pdo->prepare("SELECT name FROM school WHERE school_id = ?");
    $schoolStmt->execute([$schoolId]);
    $school = $schoolStmt->fetch(PDO::FETCH_ASSOC);

    if (!$school) {
        echo json_encode(['error' => 'School not found.']);
        exit;
    }

    $schoolName = $school['name'];

    // 2. Fetch problems from assessments (fix column name!)
    $stmt = $pdo->prepare("SELECT current_problems FROM assessment WHERE school_id = ?");
    $stmt->execute([$schoolId]);

    $problemCounts = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $problems = json_decode($row['current_problems'], true);
        if (is_array($problems)) {
            foreach ($problems as $problem) {
                $problemCounts[$problem] = ($problemCounts[$problem] ?? 0) + 1;
            }
        }
    }

    arsort($problemCounts);
    $topProblems = array_slice($problemCounts, 0, 3);

    $result = [
        'school_name' => $schoolName,
        'top_problems' => [],
    ];

    foreach ($topProblems as $problem => $count) {
        $result['top_problems'][] = ['problem' => $problem, 'count' => $count];
    }

    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
