<?php
session_start();
include '../db_connect.php';  // Ensure correct path

// Update all unseen notifications to "seen"
$sql = "UPDATE evaluation_answers SET seen = 1 WHERE seen = 0";
$conn->query($sql);

echo json_encode(['status' => 'success']);
?>
