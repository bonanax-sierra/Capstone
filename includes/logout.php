<?php
session_start();
include 'auth.php';
include 'db.php'; // Make sure $pdo is available

// Perform logout
logoutUser($pdo);

// Redirect to login page
header("Location: ../index.php?logout=1");
exit;
