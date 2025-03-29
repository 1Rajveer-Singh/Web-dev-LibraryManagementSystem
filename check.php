<?php
// Include database connection
require_once 'db_connection.php';
$conn = connectDB();
// Handle CSV Export
if (isset($_GET['export_excel']) && $_GET['export_excel'] == '1') {
    // Ensure no output has been sent yet
    ob_clean();
    
    // Set headers for CSV output
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="library_records_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Create output stream
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // CSV header row
    fputcsv($output, ['Registration No', 'Name', 'Activity', 'Date', 'Entry Time', 'Exit Time']);

    // Build and execute the SQL query
    $sql = "SELECT registration_no, name, activity, date, entry_time, exit_time 
            FROM records 
            ORDER BY date DESC, entry_time DESC";
    $result = $conn->query($sql);

    // Output each row to CSV
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['registration_no'],
            $row['name'],
            $row['activity'],
            $row['date'],
            $row['entry_time'],
            $row['exit_time']
        ]);
    }

    fclose($output);
    $conn->close();
    exit;
}

// Handle Excel (.xlsx) Export - Using proper XML Spreadsheet format
if (isset($_GET['export_xls']) && $_GET['export_xls'] == '1') {
    // Ensure no output has been sent yet
    ob_clean();
    
    $filename = 'library_records_' . date('Y-m-d') . '.xls';
    
    // Set headers for Excel file
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Basic Excel XML structure
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
    echo ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
    echo ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
    echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
    echo '<Worksheet ss:Name="Library Records">' . "\n";
    echo '<Table>' . "\n";
    
    // Header row
    echo '<Row>' . "\n";
    $headers = ['Registration No', 'Name', 'Activity', 'Date', 'Entry Time', 'Exit Time'];
    foreach ($headers as $header) {
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
    }
    echo '</Row>' . "\n";

    // Data rows
    $sql = "SELECT registration_no, name, activity, date, entry_time, exit_time 
            FROM records 
            ORDER BY date DESC, entry_time DESC";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        echo '<Row>' . "\n";
        foreach (['registration_no', 'name', 'activity', 'date', 'entry_time', 'exit_time'] as $field) {
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row[$field]) . '</Data></Cell>' . "\n";
        }
        echo '</Row>' . "\n";
    }

    echo '</Table>' . "\n";
    echo '</Worksheet>' . "\n";
    echo '</Workbook>';

    $conn->close();
    exit;
}

// Process search parameters (unchanged)
$searchCondition = "";
if (!empty($_GET['search_field']) && !empty($_GET['search_value'])) {
    $search_field = $conn->real_escape_string($_GET['search_field']);
    $search_value = $conn->real_escape_string($_GET['search_value']);

    if (in_array($search_field, ['entry_time', 'exit_time', 'name'])) {
        $searchCondition = " WHERE $search_field LIKE '%$search_value%'";
    } elseif ($search_field == 'date' || $search_field == 'registration_no') {
        $searchCondition = " WHERE $search_field = '$search_value'";
    }
}

// Pagination setup (unchanged)
$page = max(1, (int)($_GET['page'] ?? 1));
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Count total records
$sql_count = "SELECT COUNT(*) AS total FROM records" . $searchCondition;
$total_result = $conn->query($sql_count);
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch records with sorting
$sql = "SELECT * FROM records" . $searchCondition . " ORDER BY date DESC, entry_time DESC LIMIT $offset, $records_per_page";
$result = $conn->query($sql);?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library System - Records Management</title>
    <link rel="stylesheet" href="style2.css">
</head>
<body>
    <h1>Library System Records Management</h1>
    <button onclick="window.location.href='index.php';">Go to Home</button>

    <form method="get">
        <label for="search_field">Search By:</label>
        <select id="search_field" name="search_field" onchange="updateInputType()">
            <option value="">-- Select Field --</option>
            <option value="registration_no">Registration No</option>
            <option value="name">Name</option>
            <option value="date">Date</option>
            <option value="entry_time">Entry Time</option>
            <option value="exit_time">Exit Time</option>
        </select>

        <input type="text" id="search_value" name="search_value" placeholder="Enter search term...">
        <button type="submit">Search</button>
        <button type="button" onclick="window.location.href='?export_excel=1' + getSearchParams()">Export to CSV</button>
        <button type="button" onclick="window.location.href='?export_xls=1' + getSearchParams()">Export to Excel</button>
        <button type="button" onclick="window.location.href=window.location.pathname">Refresh</button>
    </form>

    <script>
        function updateInputType() {
            let field = document.getElementById("search_field").value;
            let input = document.getElementById("search_value");

            if (field === "entry_time" || field === "exit_time") {
                input.type = "time";
            } else if (field === "date") {
                input.type = "date";
            } else {
                input.type = "text";
            }
        }

        function getSearchParams() {
            let field = document.getElementById("search_field").value;
            let value = document.getElementById("search_value").value;
            return field && value ? "&search_field=" + encodeURIComponent(field) + "&search_value=" + encodeURIComponent(value) : "";
        }

        window.onload = function() {
            updateInputType();
        };
    </script>

    <table border="1">
        <tr>
            <th>Registration No</th><th>Name</th><th>Activity</th><th>Date</th><th>Entry Time</th><th>Exit Time</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row["registration_no"]) ?></td>
                <td><?= htmlspecialchars($row["name"]) ?></td>
                <td><?= htmlspecialchars($row["activity"]) ?></td>
                <td><?= htmlspecialchars($row["date"]) ?></td>
                <td><?= htmlspecialchars($row["entry_time"]) ?></td>
                <td><?= htmlspecialchars($row["exit_time"]) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</body>
</html>

<?php $conn->close(); ?>
