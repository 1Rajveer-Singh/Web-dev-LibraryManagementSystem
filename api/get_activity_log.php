<?php
header('Content-Type: application/json');
require_once '../db_connection.php';

// Connect to database
$conn = connectDB();

// Prepare and execute the query
$query = "SELECT * FROM activity_log ORDER BY date DESC, time DESC";
$result = $conn->query($query);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch activity log: ' . $conn->error]);
    exit;
}

// Fetch results
$activities = [];
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}

// Return JSON response
echo json_encode($activities);

$conn->close();
?>