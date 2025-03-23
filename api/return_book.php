<?php
// api/return_book.php
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

    // Check if book is issued
    $stmt = $conn->prepare("SELECT status FROM books WHERE id = ?");
    $stmt->bind_param("s", $data['bookId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();

    if (!$book) {
        $conn->rollback();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit;
    }

    if ($book['status'] === 'Available') {
        $conn->rollback();
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'Book is not currently issued']);
        exit;
    }

    // Update book status
    $stmt = $conn->prepare("UPDATE books SET status = 'Available' WHERE id = ?");
    $stmt->bind_param("s", $data['bookId']);
    $stmt->execute();

    // Remove from issued_books table
    $stmt = $conn->prepare("DELETE FROM issued_books WHERE book_id = ?");
    $stmt->bind_param("s", $data['bookId']);
    $stmt->execute();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Book returned successfully']);
} catch (Exception $e) {
    if ($conn->errno) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error returning book: ' . $e->getMessage()]);
} finally {
    $stmt->close();
    $conn->close();
}
?>
