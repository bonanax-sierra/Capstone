<?php
require_once 'db.php';

$stmt = $pdo->query("SELECT school_id, name FROM school");
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($schools);
?>
