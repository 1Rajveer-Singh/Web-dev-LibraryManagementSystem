<?php
// upload.php - Handles book data import from Excel

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($_FILES)) {
    header('Content-Type: application/json');
    require_once '../db_connection.php';

    try {
        $conn = connectDB();
        $data = json_decode(file_get_contents("php://input"), true);

        // Check if received data is valid
        if (!$data || !is_array($data)) {
            throw new Exception("Invalid or empty data received. Please check your Excel file.");
        }

        // Validate expected columns
        $requiredColumns = ['id', 'title', 'author', 'publisher', 'year'];
        foreach ($data as $index => $row) {
            if (array_diff($requiredColumns, array_keys($row))) {
                throw new Exception("Row " . ($index + 1) . " has missing or incorrect columns.");
            }
        }

        $conn->begin_transaction();

        // Prepare SQL statements
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM books WHERE id = ?");
        $stmtInsert = $conn->prepare("INSERT INTO books (id, title, author, publisher, year, status) VALUES (?, ?, ?, ?, ?, 'Available')");

        if (!$stmtCheck || !$stmtInsert) {
            throw new Exception("Database error: " . $conn->error);
        }

        $successCount = 0;
        $skipCount = 0;
        $errors = [];

        foreach ($data as $index => $row) {
            // Trim data and check types
            $id = trim($row['id']);
            $title = trim($row['title']);
            $author = trim($row['author']);
            $publisher = trim($row['publisher']);
            $year = trim($row['year']);

            if (!is_string($id) || !is_string($title) || !is_string($author) || !is_string($publisher) || !is_numeric($year)) {
                $errors[] = "Row " . ($index + 1) . ": Invalid data format.";
                $skipCount++;
                continue;
            }

            // Check for duplicate book ID
            $stmtCheck->bind_param("s", $id);
            $stmtCheck->execute();
            $stmtCheck->bind_result($count);
            $stmtCheck->fetch();
            $stmtCheck->reset();

            if ($count > 0) {
                $errors[] = "Row " . ($index + 1) . ": Duplicate book ID ($id) - already exists.";
                $skipCount++;
                continue;
            }

            // Insert into database
            $stmtInsert->bind_param("ssssi", $id, $title, $author, $publisher, $year);
            if (!$stmtInsert->execute()) {
                $errors[] = "Row " . ($index + 1) . ": Database error - " . $stmtInsert->error;
                $skipCount++;
            } else {
                $successCount++;
            }
        }

        if (empty($errors)) {
            $conn->commit();
            echo json_encode([
                "success" => true, 
                "message" => "Books uploaded successfully.", 
                "details" => ["total" => count($data), "successful" => $successCount, "skipped" => $skipCount]
            ]);
        } else {
            $conn->rollback();
            echo json_encode([
                "success" => false, 
                "message" => "Some records failed to import.",
                "errors" => $errors
            ]);
        }

        // Close connections
        $stmtCheck->close();
        $stmtInsert->close();
        $conn->close();
    } catch (Exception $e) {
        if (isset($conn) && $conn->ping()) {
            $conn->rollback();
            $conn->close();
        }
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
    exit;
}
?>
