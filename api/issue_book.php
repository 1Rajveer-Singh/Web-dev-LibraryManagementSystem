<?php
// api/issue_book.php
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
if (!isset($data['bookId']) || !isset($data['studentName']) || !isset($data['regNo'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$conn = connectDB();

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if book exists and is available
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
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

    if ($book['status'] !== 'Available') {
        $conn->rollback();
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'Book is currently issued']);
        exit;
    }

    // Update book status
    $stmt = $conn->prepare("UPDATE books SET status = 'Issued' WHERE id = ?");
    $stmt->bind_param("s", $data['bookId']);
    $stmt->execute();

    // Calculate return date (14 days from today)
    $issueDate = date('Y-m-d');
    $returnDate = date('Y-m-d', strtotime('+14 days'));

    // Insert into issued_books table
    $stmt = $conn->prepare("INSERT INTO issued_books (book_id, title, student_name, registration_number, issue_date, return_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $data['bookId'], $book['title'], $data['studentName'], $data['regNo'], $issueDate, $returnDate);
    $stmt->execute();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Book issued successfully']);
} catch (Exception $e) {
    if ($conn->errno) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error issuing book: ' . $e->getMessage()]);
} finally {
    $stmt->close();
    $conn->close();
}
?>
