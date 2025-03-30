<?php
// upload.php (Handles the database operations)

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json");

    // Database connection
    $servername = "localhost";
    $username = "root"; // Adjust if necessary
    $password = ""; // Adjust if necessary
    $database = "library_db"; // Change as per your database

    $conn = new mysqli($servername, $username, $password, $database);

    // Check database connection
    if ($conn->connect_error) {
        die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
    }

    // Read JSON data from the request
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !is_array($data)) {
        die(json_encode(["error" => "Invalid or empty data"]));
    }

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO books (id, title, author, publisher, year, status) VALUES (?, ?, ?, ?, ?, 'Available')");
    if (!$stmt) {
        die(json_encode(["error" => "Prepare statement failed: " . $conn->error]));
    }

    // Insert each book record
    foreach ($data as $row) {
        if (!isset($row['id'], $row['title'], $row['author'], $row['publisher'], $row['year'])) {
            continue; // Skip invalid rows
        }

        $stmt->bind_param("ssssi", $row['id'], $row['title'], $row['author'], $row['publisher'], $row['year']);
        $stmt->execute();
    }

    // Close resources
    $stmt->close();
    $conn->close();

    echo json_encode(["success" => "Books uploaded successfully"]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Books</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.3/xlsx.full.min.js"></script>
</head>
<body>

    <h2>Upload Excel File</h2>
    <form id="uploadForm">
        <label for="excelFile">Import Excel File:</label>
        <input type="file" id="excelFile" accept=".xlsx, .xls">
        <button type="button" onclick="uploadExcel()">Upload</button>
    </form>

    <script>
        function uploadExcel() {
            let fileInput = document.getElementById("excelFile");
            if (!fileInput.files.length) {
                alert("Please select an Excel file first.");
                return;
            }

            let file = fileInput.files[0];
            let reader = new FileReader();

            reader.onload = function (e) {
                let data = new Uint8Array(e.target.result);
                let workbook = XLSX.read(data, { type: 'array' });
                let sheetName = workbook.SheetNames[0];
                let sheet = workbook.Sheets[sheetName];
                let jsonData = XLSX.utils.sheet_to_json(sheet);

                if (!jsonData.length) {
                    alert("Excel file is empty!");
                    return;
                }

                fetch("upload.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(jsonData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert("Error: " + data.error);
                    } else {
                        alert(data.success);
                    }
                })
                .catch(error => console.error("Upload Error:", error));
            };

            reader.readAsArrayBuffer(file);
        }
    </script>

</body>
</html>
