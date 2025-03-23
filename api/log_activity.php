<?php
header("'Content-Type': 'application/json'");
require_once '../db_connection.php';

// Check for database connection
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? "Connection variable not set"));
}

// Set header to handle AJAX responses properly
header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// Get JSON data from request body instead of POST variables
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if required fields exist in the JSON data
    if (!empty($data['regNo']) && !empty($data['name']) && !empty($data['activity'])) {
        
        // Sanitize input data to prevent SQL injection
        $regN = mysqli_real_escape_string($conn, $data['regNo']);
        $Name = mysqli_real_escape_string($conn, $data['name']);
        $Activity = mysqli_real_escape_string($conn, $data['activity']);
        echo "$regN","$Name","$Activity";
        // Get current date and time
        $current_date = date("Y-m-d");
        $current_time = date("H:i:s");
        
        // SQL query to insert data
        $sql = "INSERT INTO activity_log (registration_no, name, activity, date, time)
                VALUES ('$regN', '$Name', '$Activity', '$current_date', '$current_time')";
        
        // Execute query and check if successful
        if ($conn->query($sql) === TRUE) {
            $response['success'] = true;
            $response['message'] = "Activity logged successfully!";
        } else {
            $response['message'] = "Error: " . $conn->error;
        }
    } else {
        $response['message'] = "Please fill out all fields.";
    }
} else {
    $response['message'] = "Invalid request method. Only POST requests are accepted.";
}

// Close database connection
$conn->close();

// Return JSON response
echo json_encode($response);
exit;
?>