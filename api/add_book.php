<?php
// api/add_book.php
header('Content-Type: application/json');
require_once '../db_connection.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['id']) || !isset($data['title']) || !isset($data['author']) || !isset($data['publisher']) || !isset($data['year'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$conn = connectDB();

try {
    // Check if book already exists
    $stmt = $conn->prepare("SELECT id FROM books WHERE id = ?");
    $stmt->bind_param("s", $data['id']);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'Book with this ID already exists']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // Add the book
    $stmt = $conn->prepare("INSERT INTO books (id, title, author, publisher, year, status) VALUES (?, ?, ?, ?, ?, 'Available')");
    $stmt->bind_param("ssssi", $data['id'], $data['title'], $data['author'], $data['publisher'], $data['year']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Book added successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error adding book']);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
