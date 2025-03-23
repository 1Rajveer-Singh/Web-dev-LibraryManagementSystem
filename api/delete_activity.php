<?php
header('Content-Type: application/json');
require_once '../db_connection.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['regNo'])) {
    echo json_encode(['success' => false, 'message' => 'Missing registration number']);
    exit;
}

// Sanitize input
$regNo = htmlspecialchars(trim($data['regNo']));

// Connect to database
$conn = connectDB();

// Prepare and execute the query
$query = "DELETE FROM activity_log WHERE registration_no = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $regNo);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Activity record deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No activity record found with that registration number']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete activity: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>