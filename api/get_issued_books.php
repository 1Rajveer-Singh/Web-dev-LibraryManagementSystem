<?php // api/get_issued_books.php 
header('Content-Type: application/json'); 
require_once '../db_connection.php'; 
 // Only allow GET requests
  if ($_SERVER['REQUEST_METHOD'] !== 'GET') 
  {     http_response_code(405);
         echo json_encode(['success' => false, 'message' => 'Method not allowed']);exit; } 
               $conn = connectDB(); 
                try { $query = "SELECT * FROM issued_books ORDER BY issue_date";  
                       $result = $conn->query($query);
                             if ($result->num_rows > 0) {         $issuedBooks = $result->fetch_all(MYSQLI_ASSOC); 
                                         // Calculate overdue status for each book 
                                                 $today = new DateTime(); 
                                                         foreach ($issuedBooks as &$book)
                                                          {             $returnDate = new DateTime($book['return_date']); 
                                                                        if ($today > $returnDate) {                 $interval = $today->diff($returnDate); 
                                                                                            $book['overdue_days'] = $interval->format('%a');
                                                                                                             $book['is_overdue'] = true;             }
                                                                                                              else {                 $book['overdue_days'] = '0';
                                                                                                                                 $book['is_overdue'] = false;             }         }       
                                                                                                                                    echo json_encode($issuedBooks);   
                                                                                                                                  } else
                                                                                                                                   {         echo json_encode([]); 
                                                                                                                                    }      $result->free();  
                                                                                                                                       $conn->close();
                                                                                                                                     } catch (Exception $e) {     http_response_code(500);  
                                                                                                                                           echo json_encode(['success' => false, 'message' => 'Error fetching issued books: ' . $e->getMessage()]);     $conn->close(); } 
