<?php 
require_once 'db_connection.php';
// Initialize variables
$message = '';
$messageType = '';
$recentActivities = [];
$regNo = $studentName = $activity = '';
// Start session to store messages between redirects
session_start();
// Check for messages from previous POST request
if (isset($_SESSION['message']) && isset($_SESSION['messageType'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    
    // Clear session variables after use
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}
// Set timezone to India Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');
// Handle POST request for logging activity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logActivity') {
    $conn = connectDB();
    
    if (!$conn) {
        $_SESSION['message'] = "Database connection failed!";
        $_SESSION['messageType'] = "error";
        // Redirect early if connection fails
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Validate input
    $regNo = trim($_POST['regNo'] ?? '');
    $studentName = trim($_POST['studentName'] ?? '');
    $activity = trim($_POST['activity'] ?? '');
    
    if (empty($regNo) || empty($studentName) || empty($activity)) {
        $_SESSION['message'] = "Please fill all required fields!";
        $_SESSION['messageType'] = "error";
    } else if (!preg_match('/^[A-Za-z0-9]{6,10}$/', $regNo)) {
        $_SESSION['message'] = "Registration number must be 6-10 alphanumeric characters!";
        $_SESSION['messageType'] = "error";
    } else if (strlen($studentName) < 3) {
        $_SESSION['message'] = "Student name must be at least 3 characters!";
        $_SESSION['messageType'] = "error";
    } else if (strlen($activity) > 255) {
        // Added validation for activity length
        $_SESSION['message'] = "Activity description is too long!";
        $_SESSION['messageType'] = "error";
    } else {
        try {
            // Get current date and time in IST
            $current_date = date("Y-m-d");
            $current_time = date("H:i:s");
            
            // Prepare statement
            $stmt = $conn->prepare("INSERT INTO activity_log (registration_no, name, activity, date, time) VALUES (?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            // Bind parameters
            $stmt->bind_param("sssss", $regNo, $studentName, $activity, $current_date, $current_time);
            
            // Execute
            if ($stmt->execute()) {
                $_SESSION['message'] = "Activity logged successfully!";
                $_SESSION['messageType'] = "success";
                $regNo = $studentName = $activity = ''; // Clear input fields after success
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = "error";
        }
    }
    
    $conn->close();
    
    // Redirect to the same page to prevent form resubmission on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
// Fetch recent activities
try {
    $conn = connectDB();
    
    if (!$conn) {
        // Handle connection failure
        $message = "Database connection failed when fetching activities";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("SELECT registration_no, name, activity, date, time FROM activity_log ORDER BY date DESC, time DESC LIMIT 10");
        
        if ($stmt && $stmt->execute()) {
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $recentActivities[] = $row;
            }
            
            $stmt->close();
        } else {
            $message = "Failed to fetch recent activities";
            $messageType = "error";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    error_log("Error fetching activities: " . $e->getMessage());
    $message = "Error fetching recent activities";
    $messageType = "error";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Library Management System for tracking books and student activities">
    <title>Library Management System</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .butfive {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    color: white;
    border: none;
    padding: 12px 20px;
    font-size: 1rem;
    font-weight: bold;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
    box-shadow: 0 4px 10px rgba(39, 174, 96, 0.3);
    display: inline-block;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 1px;
    outline: none;
}

.butfive:hover {
    background: linear-gradient(135deg, #27ae60, #219150);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(39, 174, 96, 0.5);
}

.butfive:active {
    transform: translateY(1px);
    box-shadow: 0 2px 5px rgba(39, 174, 96, 0.5);
}

.butfive:disabled {
    background: #95a5a6;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

#progressBar {
    width: 100%;
    height: 25px;
    background: #eee;
    border-radius: 5px;
    margin-top: 15px;
    overflow: hidden;
    box-shadow: inset 0px 1px 3px rgba(0, 0, 0, 0.2);
}
#progressFill {
    height: 100%;
    width: 0%;
    background: #4CAF50;
    color: white;
    line-height: 25px;
    text-align: center;
    border-radius: 5px;
    transition: width 0.4s ease-in-out;
}

#result {
    margin-top: 20px;
    padding: 10px;
    font-size: 14px;
    display: none;
    border-radius: 5px;
}
#fileInput {
    padding: 10px;
    font-size: 16px;
    border: 2px solid #007bff;
    border-radius: 5px;
    background-color: #f8f9fa;
    color: #333;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
}

#fileInput:hover {
    background-color: #e9ecef;
    border-color: #0056b3;
}

#fileInput::-webkit-file-upload-button {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s ease;
}

#fileInput::-webkit-file-upload-button:hover {
    background-color: #0056b3;
}










    </style>
</head>
<body>
    <div class="alert alert-warning alert-animated">
        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
        <div class="alert-content">
            <div class="alert-title">Important!</div>
            <p>All fields are required to complete this form.</p>
        </div>
    </div>
      
    <div class="alert alert-info alert-dismissible alert-animated">
        <i class="fas fa-info-circle" aria-hidden="true"></i>
        <div class="alert-content">
            <div class="alert-title">Information</div>
            <p>The library will be closed for maintenance this weekend.</p>
        </div>
        <button class="close" aria-label="Close alert">&times;</button>
    </div>

    <div class="container">
        <header>
            <h1><i class="fas fa-book" aria-hidden="true"></i> Library Management System</h1>
            <div class="user-controls">
                <button id="adminLoginBtn" class="btn" aria-label="Admin Login"><i class="fas fa-user-shield" aria-hidden="true"></i> Admin Login</button>
                <button id="logoutBtn" class="btn btn-danger hidden" aria-label="Logout"><i class="fas fa-sign-out-alt" aria-hidden="true"></i> Logout</button>
            </div>
        </header>
        
        <!-- Admin Login Modal -->
        <div id="adminModal" class="modal" role="dialog" aria-labelledby="adminModalTitle" aria-hidden="true">
            <div class="modal-content">
                <span class="close" aria-label="Close modal">&times;</span>
                <h2 id="adminModalTitle">Admin Login</h2>
                <form id="adminLoginForm">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" autocomplete="username" placeholder="Enter username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Enter password" required>
                    </div>
                    <button type="submit" class="btn">Login</button>
                </form>
            </div>
        </div>
        
        <!-- Tab Navigation -->
        <div class="tabs" role="tablist">
            <button class="tab-button active" data-tab="activity" role="tab" aria-selected="true" aria-controls="activity">Activity Log</button>
            <button class="tab-button" data-tab="view-books" role="tab" aria-selected="false" aria-controls="view-books">View Books</button>
            <button class="tab-button admin-tab" data-tab="add-books" role="tab" aria-selected="false" aria-controls="add-books" disabled>Add Books</button>
            <button class="tab-button admin-tab" data-tab="issue-books" role="tab" aria-selected="false" aria-controls="issue-books" disabled>Issue Books</button>
            <button class="tab-button admin-tab" data-tab="return-books" role="tab" aria-selected="false" aria-controls="return-books" disabled>Return Books</button>
            <button class="tab-button admin-tab" data-tab="delete-books" role="tab" aria-selected="false" aria-controls="delete-books" disabled>Delete Books</button>
            <button class="tab-button admin-tab" data-tab="view-issued" role="tab" aria-selected="false" aria-controls="view-issued" disabled>Issued Books</button>
           
            <button class="tab-button admin-tab" data-tab="Record Manager" role="tab" aria-selected="false" aria-controls="records-manager"onclick="window.location.href='check.php';" disabled>Records Management</button>

        </div>
        
        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Activity Log Tab -->
            <div id="activity" class="tab-pane active" role="tabpanel" aria-labelledby="activity-tab">
                <div class="form-section">
                    <h2>Log Student Activity</h2>
                    
                    <div class="form-container">
                        <h3>Activity Log Form</h3>
                        
                        <div id="success-message" class="success hidden" role="alert">Activity logged successfully!</div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="logActivity">
                            
                            <label for="regNo">Registration Number:</label>
                            <input type="text" id="regNo" name="regNo" value="<?php echo htmlspecialchars($regNo); ?>" required pattern="[A-Za-z0-9]{6,10}" title="Registration number must be 6-10 alphanumeric characters">
                    
                            <label for="studentName">Student Name:</label>
                            <input type="text" id="studentName" name="studentName" value="<?php echo htmlspecialchars($studentName); ?>" required minlength="3">
                    
                            <label for="activity">Activity:</label>
                            <select id="activity" name="activity" required>
                                <option value="" disabled selected>Select activity</option>
                                <option value="study" <?php echo $activity === 'study' ? 'selected' : ''; ?>>Study</option>
                                <option value="reading_book" <?php echo $activity === 'reading_book' ? 'selected' : ''; ?>>Reading Book</option>
                                <option value="return_book" <?php echo $activity === 'return_book' ? 'selected' : ''; ?>>Return Book</option>
                            </select>
                            <br><br>
                            <button type="submit" class="butfive">Log Activity</button>
                        </form>
                    </div>
                </div>
                
                <div class="table-section">
                    <h2>Recent Activity</h2>
                    <div class="table-container">
                        <table id="activityTable" aria-label="Recent Student Activities">
                            <thead>
                                <tr>
                                    <th scope="col">Reg. No.</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Activity</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Time</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Activity data will be loaded here -->
                                <tr>
                                    <td colspan="6" class="empty-table-message">No activity records found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- View Books Tab -->
            <div id="view-books" class="tab-pane" role="tabpanel" aria-labelledby="view-books-tab">
                <div class="search-section">
                    <h3>Search Book</h3>
                    <div class="search-box">
                        <div class="form-group">
                            <label for="searchBookId">Search Term:</label>
                            <input type="text" id="searchBookId" placeholder="Enter search term">
                        </div>
                        
                        <div class="form-group">
                            <label for="searchOption">Search By:</label>
                            <select id="searchOption" name="searchOption" required>
                                <option value="" disabled selected>Select search criteria</option>
                                <option value="ID">Book ID</option>
                                <option value="Title">Title</option>
                                <option value="Author">Author</option>
                                <option value="Publisher">Publisher</option>
                                <option value="Year">Year</option>
                            </select>
                        </div>
                        
                        
                        <div class="button-group">
                            <button id="searchBookBtn" class="btn" aria-label="Search books"><i class="fas fa-search" aria-hidden="true"></i> Search</button>
                            <button id="refreshBooksBtn" class="btn" aria-label="Refresh book list"><i class="fas fa-sync" aria-hidden="true"></i> Refresh</button>
                        </div>
                    </div>
                </div>
                
                <div class="table-section">
                    <h2>Books Collection</h2>
                    <div class="table-container">
                        <table id="booksTable" aria-label="Books Collection">
                            <thead>
                                <tr>
                                    <th scope="col">Book ID</th>
                                    <th scope="col">Title</th>
                                    <th scope="col">Author</th>
                                    <th scope="col">Publisher</th>
                                    <th scope="col">Year</th>
                                    <th scope="col" class="status">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Books data will be loaded here -->
                                <tr>
                                    <td colspan="6" class="empty-table-message">No books found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                </div>
            </div>
            
            <!-- Add Books Tab -->
            <div id="add-books" class="tab-pane" role="tabpanel" aria-labelledby="add-books-tab">
            <div class="form-section">
        <h2>Upload Excel File Data To Database</h2>
        <input type="file" id="fileInput" accept=".xlsx, .xls">
        <button class="btn btn-success" onclick="processExcel()">Upload</button>
        <div id="progressBar"><div id="progressFill">0%</div></div>
        <div id="result"></div>
    </div><script>
        function processExcel() {
    let fileInput = document.getElementById("fileInput");
    let file = fileInput.files[0];

    if (!file) {
        showError("Please select an Excel file.");
        return;
    }

    if (!file.name.endsWith(".xlsx") && !file.name.endsWith(".xls")) {
        showError("Invalid file format. Please upload a valid Excel file.");
        return;
    }

    let reader = new FileReader();
    
    reader.onload = function (e) {
        try {
            let data = new Uint8Array(e.target.result);
            let workbook = XLSX.read(data, { type: "array" });

            if (workbook.SheetNames.length === 0) {
                showError("Excel file is empty or corrupted.");
                return;
            }

            let sheet = workbook.Sheets[workbook.SheetNames[0]];
            let jsonData = XLSX.utils.sheet_to_json(sheet);

            if (!jsonData.length) {
                showError("Excel file contains no valid data.");
                return;
            }

            let requiredColumns = ["id", "title", "author", "publisher", "year"];
            let firstRow = jsonData[0];

            if (!requiredColumns.every(col => col in firstRow)) {
                showError("Excel file is missing required columns: " + requiredColumns.join(", "));
                return;
            }

            let invalidRows = [];
            jsonData.forEach((row, index) => {
                if (!row.id || !row.title || !row.author || !row.publisher || isNaN(row.year)) {
                    invalidRows.push(index + 1);
                }
            });

            if (invalidRows.length > 0) {
                showError(`Invalid data in rows: ${invalidRows.join(", ")}. Ensure all fields are filled correctly.`);
                return;
            }

            document.getElementById("progressBar").style.display = "block";
            document.getElementById("progressFill").style.width = "30%";

            fetch("api/upload.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(jsonData)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById("progressFill").style.width = "100%";
                setTimeout(() => document.getElementById("progressBar").style.display = "none", 500);

                if (data.success) {
                    showSuccess(`Books uploaded successfully. Verified: ${data.details.verified}/${data.details.total}. Skipped: ${data.details.skipped}`);
                } else {
                    showError(`Upload failed: ${data.message}<br>Errors: ${data.errors ? data.errors.join("<br>") : "Unknown error"}`);
                }
            })
            .catch(error => {
                showError("An error occurred while sending data: " + error.message);
            });
        } catch (error) {
            showError("Error processing file: " + error.message);
        }
    };

    reader.readAsArrayBuffer(file);
}

function showError(message) {
    let result = document.getElementById("result");
    result.style.display = "block";
    result.style.background = "#f8d7da";
    result.innerHTML = `<strong>Error:</strong> ${message}`;
}

function showSuccess(message) {
    let result = document.getElementById("result");
    result.style.display = "block";
    result.style.background = "#d4edda";
    result.innerHTML = `<strong>Success:</strong> ${message}`;
}


    </script>
   
            
                
                
                   <div class="form-section">
                    <h2>Add New Book</h2>
                    <form id="addBookForm" novalidate>
                        <div class="form-group">
                            <label for="bookId">Book ID:</label>
                            <input type="text" id="bookId" name="bookId" autocomplete="off" placeholder="Enter book ID (e.g., B001)" required aria-describedby="bookId-error">
                            <div class="error" id="bookId-error" role="alert"></div>
                        </div>
                        <div class="form-group">
                            <label for="title">Title:</label>
                            <input type="text" id="title" name="title" autocomplete="off" placeholder="Enter book title" required aria-describedby="title-error">
                            <div class="error" id="title-error" role="alert"></div>
                        </div>
                        <div class="form-group">
                            <label for="author">Author:</label>
                            <input type="text" id="author" name="author" autocomplete="off" placeholder="Enter author name" required aria-describedby="author-error">
                            <div class="error" id="author-error" role="alert"></div>
                        </div>
                        <div class="form-group">
                            <label for="publisher">Publisher:</label>
                            <input type="text" id="publisher" name="publisher" autocomplete="off" placeholder="Enter publisher name" required aria-describedby="publisher-error">
                            <div class="error" id="publisher-error" role="alert"></div>
                        </div>
                        <div class="form-group">
                            <label for="year">Publication Year:</label>
                            <input type="number" id="year" name="year" autocomplete="off" placeholder="Enter publication year" min="1000" max="2099" required aria-describedby="year-error">
                            <div class="error" id="year-error" role="alert"></div>
                        </div>
                        <button type="submit" class="btn btn-success">Add Book</button>
                        
                    </form>
                </div>
            </div>
            
            <!-- Issue Books Tab -->
            <div id="issue-books" class="tab-pane" role="tabpanel" aria-labelledby="issue-books-tab">
                <div class="form-section">
                    <h2>Issue Book to Student</h2>
                    <form id="issueBookForm" novalidate>
                        <div class="form-group">
                            <label for="issueBookId">Book ID:</label>
                            <input type="text" id="issueBookId" name="issueBookId" placeholder="Enter book ID" required aria-describedby="issueBookId-error">
                            <div class="error" id="issueBookId-error" role="alert"></div>
                        </div>
                        <div class="form-group">
                            <label for="issueStudentName">Student Name:</label>
                            <input type="text" id="issueStudentName" name="issueStudentName" placeholder="Enter student name" required aria-describedby="issueStudentName-error">
                            <div class="error" id="issueStudentName-error" role="alert"></div>
                        </div>
                        <div class="form-group">
                            <label for="issueRegNo">Registration Number:</label>
                            <input type="text" id="issueRegNo" name="issueRegNo" placeholder="Enter registration number" required aria-describedby="issueRegNo-error">
                            <div class="error" id="issueRegNo-error" role="alert"></div>
                        </div>
                        <button type="submit" class="btn btn-success">Issue Book</button>
                    </form>
                </div>
            </div>
            
            <!-- Return Books Tab -->
            <div id="return-books" class="tab-pane" role="tabpanel" aria-labelledby="return-books-tab">
                <div class="form-section">
                    <h2>Return Book</h2>
                    <form id="returnBookForm" novalidate>
                        <div class="form-group">
                            <label for="returnBookId">Book ID:</label>
                            <input type="text" id="returnBookId" name="returnBookId" placeholder="Enter book ID" required aria-describedby="returnBookId-error">
                            <div class="error" id="returnBookId-error" role="alert"></div>
                        </div>
                        <button type="submit" class="btn btn-success">Return Book</button>
                    </form>
                </div>
            </div>
            
            <!-- Delete Books Tab -->
            <div id="delete-books" class="tab-pane" role="tabpanel" aria-labelledby="delete-books-tab">
                <div class="form-section">
                    <h2>Delete Book</h2>
                    <form id="deleteBookForm" novalidate>
                        <div class="form-group">
                            <label for="deleteBookId">Book ID:</label>
                            <input type="text" id="deleteBookId" name="deleteBookId" placeholder="Enter book ID" required aria-describedby="deleteBookId-error">
                            <div class="error" id="deleteBookId-error" role="alert"></div>
                        </div>
                        <button type="submit" class="btn btn-danger">Delete Book</button>
                    </form>
                </div>
            </div>
            
            <!-- View Issued Books Tab -->
            <div id="view-issued" class="tab-pane" role="tabpanel" aria-labelledby="view-issued-tab">
                <div class="table-section">
                    <h2>Issued Books</h2>
                    <div class="table-container">
                        <table id="issuedBooksTable" aria-label="Issued Books">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Book ID</th>
                                    <th scope="col">Title</th>
                                    <th scope="col">Student Name</th>
                                    <th scope="col">Reg. No.</th>
                                    <th scope="col">Issue Date</th>
                                    <th scope="col">Return Date</th>
                                    <th scope="col">Fine</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Issued books data will be loaded here -->
                                <tr>
                                    <td colspan="8" class="empty-table-message">No issued books found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <center><p>&copy; <span id="current-year">2025</span> Library Management System. All rights reserved.</p></center>
        </div>
    </footer>
    
    <script src="script.js"></script>
    <script>
        // Set current year in footer
        document.getElementById('current-year').textContent = new Date().getFullYear();
    </script>
</body>
</html>