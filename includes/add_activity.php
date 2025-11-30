<?php
// Show errors for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
require 'db.php';

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data and sanitize
    $municipality   = trim($_POST['municipality'] ?? '');
    $barangay       = trim($_POST['barangay'] ?? '');
    $activityTitle  = trim($_POST['activityTitle'] ?? '');
    $activityType   = trim($_POST['activityType'] ?? '');
    $activityDate   = trim($_POST['activityDate'] ?? '');

    // Optional: Basic validation
    if ($municipality && $barangay && $activityTitle && $activityType && $activityDate) {
        try {
            // Prepare and execute SQL insert
            $stmt = $pdo->prepare("INSERT INTO activity_info 
                (municipality, barangay, activity_title, activity_type, activity_date) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $municipality,
                $barangay,
                $activityTitle,
                $activityType,
                $activityDate
            ]);

            // ✅ Redirect after successful insert
            header("Location: ../reports.php");
            exit(); // Always call exit after header redirects
        } catch (PDOException $e) {
            echo "Database error: " . $e->getMessage();
        }
    } else {
        echo "Please fill out all required fields.";
    }
} else {
    echo "Invalid request.";
}
