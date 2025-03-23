<?php
// api/delete_book.php
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
if (!isset($data['bookId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing book ID']);
    exit;
}

$conn = connectDB();

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if book exists
    $stmt = $conn->prepare("SELECT status FROM books WHERE id = ?");
    $stmt->bind_param("s", $data['bookId']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->rollback();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        $conn->close();
        exit;
    }

    // Check if book is issued
    $book = $result->fetch_assoc();
    if ($book['status'] === 'Issued') {
        $stmt->close();
        $conn->rollback();
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'Cannot delete book while it is issued']);
        $conn->close();
        exit;
    }

    $stmt->close();

    // Delete book
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("s", $data['bookId']);

    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
    } else {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error deleting book']);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    // Check if a transaction is active before rolling back
    if ($conn->errno) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    $conn->close();
}
?>
