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

// Set timezone to India Standard Time
date_default_timezone_set('Asia/Kolkata');

// Get current IST time for exit_time
$exitTime = date('H:i:s');

// Start transaction to ensure data consistency
$conn->begin_transaction();

try {
    // Step 1: Retrieve the record from activity_log
    $selectQuery = "SELECT registration_no, name, activity, date, time FROM activity_log WHERE registration_no = ?";
    $selectStmt = $conn->prepare($selectQuery);
    $selectStmt->bind_param("s", $regNo);
    $selectStmt->execute();
    $result = $selectStmt->get_result();
    
    if ($result->num_rows === 0) {
        // No record found with that registration number
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'No activity record found with that registration number']);
        exit;
    }
    
    // Step 2: Insert records into the records table
    $insertQuery = "INSERT INTO records (registration_no, name, activity, date, entry_time, exit_time) VALUES (?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    
    // Process each record (there might be multiple with the same registration number)
    while ($row = $result->fetch_assoc()) {
        $insertStmt->bind_param("ssssss", 
            $row['registration_no'], 
            $row['name'], 
            $row['activity'], 
            $row['date'], 
            $row['time'],  // Use the original time as entry_time
            $exitTime      // Use current IST time as exit_time
        );
        
        if (!$insertStmt->execute()) {
            throw new Exception("Failed to insert record: " . $conn->error);
        }
    }
    
    $selectStmt->close();
    $insertStmt->close();
    
    // Step 3: Delete records from activity_log
    $deleteQuery = "DELETE FROM activity_log WHERE registration_no = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("s", $regNo);
    
    if (!$deleteStmt->execute()) {
        throw new Exception("Failed to delete activity: " . $conn->error);
    }
    
    $deletedCount = $deleteStmt->affected_rows;
    $deleteStmt->close();
    
    // Commit the transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Activity record(s) transferred to records and deleted successfully',
        'records_transferred' => $deletedCount,
        'exit_time' => $exitTime . ' IST'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>