<?php 
// api/get_books.php
header('Content-Type: application/json');
require_once '../db_connection.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$conn = connectDB();

try {
    // Prepare base query
    $query = "SELECT * FROM books";
    $params = [];
    $types = "";

    // Check for search parameters
    if (!empty($_GET['term']) && !empty($_GET['option'])) {
        $searchTerm = $_GET['term'];
        $searchOption = strtolower($_GET['option']);

        // Map front-end options to database columns
        $columnMap = [
            'id' => 'id',
            'title' => 'title',
            'author' => 'author',
            'publisher' => 'publisher',
            'year' => 'year'
        ];

        // Validate the search option
        if (!isset($columnMap[$searchOption])) {
            throw new Exception("Invalid search option provided.");
        }

        $column = $columnMap[$searchOption];

        // Exact match for 'year', partial match for other text fields
        if ($column === 'year') {
            $query .= " WHERE $column = ? ORDER BY id";
            $params[] = (int) $searchTerm;
            $types .= "i"; // Integer type for year
        } else {
            $query .= " WHERE $column LIKE ? ORDER BY id";
            $params[] = "%{$searchTerm}%";
            $types .= "s"; // String type for text-based search
        }
    } elseif (!empty($_GET['bookId'])) {
        // Search by specific book ID
        $query .= " WHERE id = ?";
        $params[] = $_GET['bookId'];
        $types .= "s";
    } else {
        // Default case: return all books
        $query .= " ORDER BY id";
    }

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare SQL statement.");
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $books = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'books' => $books]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching books: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>
