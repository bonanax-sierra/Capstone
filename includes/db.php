<?php
$host = 'localhost';     // Usually localhost
$dbname = 'capstone'; // Change this to your database name
$user = 'root';          // Your DB username (often 'root' for local servers)
$pass = '';              // Your DB password ('' if no password on localhost)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    // Enable PDO error mode to Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If connection fails, show error
    die("Database connection failed: " . $e->getMessage());
}
?>
