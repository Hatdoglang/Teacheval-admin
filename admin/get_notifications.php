<?php
session_start();
include '../db_connect.php';

header('Content-Type: application/json');

// Set the start date (March 13, 2025) without an end date
$start_date = "2025-03-13";

// Prepare SQL query to fetch records from this date onward
$sql = "SELECT evaluation_id, timestamp 
        FROM evaluation_answers 
        WHERE timestamp >= ? 
        ORDER BY timestamp ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $start_date);
$stmt->execute();
$result = $stmt->get_result();

$evaluations = [];

while ($row = $result->fetch_assoc()) {
    $evaluations[] = [
        'evaluation_id' => $row['evaluation_id'],
        'timestamp' => $row['timestamp']
    ];
}

// Debugging: Check if any data is found
if (empty($evaluations)) {
    echo json_encode(['error' => 'No data found from 3/13/2025 onward']);
    exit;
}

echo json_encode(['evaluations' => $evaluations], JSON_PRETTY_PRINT);
?>
