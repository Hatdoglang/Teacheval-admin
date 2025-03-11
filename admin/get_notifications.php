<?php
session_start();
include '../db_connect.php';

header('Content-Type: application/json');

$current_date = date("Y-m-d");

// Fetch evaluations submitted today at or after '15:23:15'
$sql = "SELECT evaluation_id, MIN(timestamp) as timestamp 
        FROM evaluation_answers 
        WHERE DATE(timestamp) = ? AND TIME(timestamp) >= '15:23:15' 
        GROUP BY evaluation_id 
        ORDER BY evaluation_id, timestamp";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $current_date);
$stmt->execute();
$result = $stmt->get_result();

$evaluations = [];

while ($row = $result->fetch_assoc()) {
    $evaluations[] = [
        'evaluation_id' => $row['evaluation_id'],
        'timestamp' => $row['timestamp']
    ];
}

echo json_encode(['evaluations' => $evaluations]);
?>
