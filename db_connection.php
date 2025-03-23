<?php
// db_connection.php
function connectDB() {
    $host = 'localhost';
    $dbname = 'library_system';
    $username = 'root';
    $password = '';
    $port="3306";
    // Create a new MySQLi connection
    $conn = new mysqli($host, $username, $password, $dbname,$port);
    // Check for connection errors
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }return $conn;}?>
