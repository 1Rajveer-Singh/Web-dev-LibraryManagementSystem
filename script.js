/**
 * Library Management System
 * Main JavaScript file
 */
document.addEventListener('DOMContentLoaded', function() {
    // Form ID is 'activityForm' in your HTML (case sensitive)
    const form = document.getElementById('activityForm');
    
    // Create a success message element if it doesn't exist
    let successMessage = document.getElementById('success-message');
    if (!successMessage) {
        successMessage = document.createElement('div');
        successMessage.id = 'success-message';
        successMessage.className = 'alert alert-success mt-3 hidden';
        successMessage.textContent = 'Activity logged successfully!';
        form.parentNode.insertBefore(successMessage, form.nextSibling);
    }
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form values - matching the IDs in your HTML form
        const regNo = document.getElementById('regNo').value.trim();
        const name = document.getElementById('studentName').value.trim(); // Changed from 'name' to 'studentName'
        const activitySelect = document.getElementById('activity'); // Fixed variable name from activityselect to activitySelect
        const activity = activitySelect.value; // Fixed: directly use .value instead of .options[].value
        console.log(activity);
        
        // Validate form
        let isValid = true;
        
        // Clear previous error messages
        const errorElements = document.querySelectorAll('.error-message');
        errorElements.forEach(el => el.remove());
        
        if (!regNo) {
            showError('regNo', 'Registration number is required');
            isValid = false;
        }
        
        if (!name) {
            showError('studentName', 'Student name is required');
            isValid = false;
        }
        
        if (!activity) {
            showError('activity', 'Activity is required');
            isValid = false;
        }
        
        if (isValid) {
            
            // Prepare data for submission
            const data = {
                regNo: regNo,
                name: name,
                activity: activity
            };
            
            // Send data to server
            fetch('api/log_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Show success message
                    successMessage.classList.remove('hidden');
                    
                    // Reset form
                    form.reset();
                    
                    // Hide success message after 3 seconds
                    setTimeout(() => {
                        successMessage.classList.add('hidden');
                    }, 3000);
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the form');
            });
        }
    });
    
    // Helper function to show error messages
    function showError(inputId, message) {
        const input = document.getElementById(inputId);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message text-danger';
        errorDiv.textContent = message;
        input.parentNode.appendChild(errorDiv);
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // Configuration
    const CONFIG = {
        ADMIN: {
            USERNAME: 'rajveer',
            PASSWORD: 'rks@123'
        },
        API_ENDPOINTS: {
            ACTIVITY: 'api/log_activity.php',
            DELETE_ACTIVITY: 'api/delete_activity.php',
            GET_ACTIVITY: 'api/get_activity_log.php',
            ADD_BOOK: 'api/add_book.php',
            GET_BOOKS: 'api/get_books.php',
            ISSUE_BOOK: 'api/issue_book.php',
            RETURN_BOOK: 'api/return_book.php',
            DELETE_BOOK: 'api/delete_book.php',
            GET_ISSUED: 'api/get_issued_books.php'
        },
        ALERT_TIMEOUT: 3000
    };
    
    // DOM Elements cache
    const DOM = {
        adminLoginBtn: document.getElementById('adminLoginBtn'),
        logoutBtn: document.getElementById('logoutBtn'),
        closeModalBtn: document.querySelector('.close'),
        searchBookBtn: document.getElementById('searchBookBtn'),
        refreshBooksBtn: document.getElementById('refreshBooksBtn'),
        adminLoginForm: document.getElementById('adminLoginForm'),
        activityForm: document.getElementById('activityForm'),
        addBookForm: document.getElementById('addBookForm'),
        issueBookForm: document.getElementById('issueBookForm'),
        returnBookForm: document.getElementById('returnBookForm'),
        deleteBookForm: document.getElementById('deleteBookForm'),
        tabButtons: document.querySelectorAll('.tab-button'),
        adminTabs: document.querySelectorAll('.admin-tab'),
        adminModal: document.getElementById('adminModal'),
        container: document.querySelector('.container'),
        activityTable: document.querySelector('#activityTable tbody'),
        booksTable: document.querySelector('#booksTable tbody'),
        issuedBooksTable: document.querySelector('#issuedBooksTable tbody')
    };
    
    // Application state
    const state = {
        isAdmin: false,
        activeTab: 'activity',
        searchFilter: '',
        searchOption: ''
    };
    
    // ======================
    // Event Listeners
    // ======================
    
    DOM.tabButtons.forEach(button => {
        button.addEventListener('click', handleTabSwitch);
    });
    
    DOM.adminLoginBtn.addEventListener('click', showAdminModal);
    DOM.closeModalBtn.addEventListener('click', hideAdminModal);
    window.addEventListener('click', (event) => {
        if (event.target === DOM.adminModal) hideAdminModal();
    });
    DOM.adminLoginForm.addEventListener('submit', handleAdminLogin);
    DOM.logoutBtn.addEventListener('click', handleLogout);
    
    // Only attach event listeners if the elements exist
    if (DOM.activityForm) {
        DOM.activityForm.addEventListener('submit', handleActivityFormSubmit);
    }
    if (DOM.addBookForm) {
        DOM.addBookForm.addEventListener('submit', handleAddBookFormSubmit);
    }
    if (DOM.issueBookForm) {
        DOM.issueBookForm.addEventListener('submit', handleIssueBookFormSubmit);
    }
    if (DOM.returnBookForm) {
        DOM.returnBookForm.addEventListener('submit', handleReturnBookFormSubmit);
    }
    if (DOM.deleteBookForm) {
        DOM.deleteBookForm.addEventListener('submit', handleDeleteBookFormSubmit);
    }
    
    if (DOM.searchBookBtn) {
        DOM.searchBookBtn.addEventListener('click', handleBookSearch);
    }
    if (DOM.refreshBooksBtn) {
        DOM.refreshBooksBtn.addEventListener('click', handleBooksRefresh);
    }
    
    // ======================
    // Event Handlers
    // ======================
    function calculateFine(returnDate, currentDate = new Date(), finePerDay = 1) {
        // Convert returnDate to Date object if it's a string
        const dueDate = returnDate instanceof Date ? returnDate : new Date(returnDate);
        
        // Calculate days overdue
        let daysOverdue = 0;
        let fine = 0;
        
        if (currentDate > dueDate) {
            const diffTime = Math.abs(currentDate - dueDate);
            daysOverdue = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            fine = daysOverdue * finePerDay;
        }
        
        return {
            daysOverdue,
            fine
        };
    }
    
    function handleTabSwitch() {
        if (this.disabled) return;
        
        DOM.tabButtons.forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
        
        this.classList.add('active');
        state.activeTab = this.getAttribute('data-tab');
        
        const activePane = document.getElementById(state.activeTab);
        if (activePane) {
            activePane.classList.add('active');
        }
        
        if (state.activeTab === 'view-books') {
            loadBooks();
        } else if (state.activeTab === 'view-issued') {
            loadIssuedBooks();
        }
    }
    
    function showAdminModal() {
        if (DOM.adminModal) {
            DOM.adminModal.style.display = 'block';
        }
    }
    
    function hideAdminModal() {
        if (DOM.adminModal) {
            DOM.adminModal.style.display = 'none';
            if (DOM.adminLoginForm) {
                DOM.adminLoginForm.reset();
            }
        }
    }
    
    function handleAdminLogin(e) {
        e.preventDefault();
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        
        if (username === CONFIG.ADMIN.USERNAME && password === CONFIG.ADMIN.PASSWORD) {
            state.isAdmin = true;
            DOM.adminTabs.forEach(tab => tab.disabled = false);
            DOM.adminLoginBtn.classList.add('hidden');
            DOM.logoutBtn.classList.remove('hidden');
            hideAdminModal();
            logAdminActivity();
            showAlert('Login successful!', 'success');
        } else {
            showAlert('Invalid username or password', 'error');
        }
    }
    
    function handleLogout() {
        state.isAdmin = false;
        DOM.adminTabs.forEach(tab => tab.disabled = true);
        DOM.adminLoginBtn.classList.remove('hidden');
        DOM.logoutBtn.classList.add('hidden');
        const defaultTab = document.querySelector('.tab-button[data-tab="activity"]');
        if (defaultTab) {
            defaultTab.click();
        }
        showAlert('Logged out successfully', 'success');
    }
    
    
    function handleActivityFormSubmit(e) {
        try {
            e.preventDefault();
    
            // Get form elements safely
            let regNoElement = document.getElementById('regNo');
            let nameElement = document.getElementById('studentName');
            let activityElement = document.getElementById('activity'); // Ensure correct selection
    
            console.log("Activity Element:", activityElement); // Debug: Check if select element exists
            console.log("All elements:", regNoElement, nameElement, activityElement);
    
            if (!regNoElement || !nameElement || !activityElement) {
                console.error("One or more form elements not found!");
                showAlert('Form elements missing!', 'error');
                return;
            }
    
            // Get trimmed values safely
            const regNo = regNoElement.value.trim();
            const name = nameElement.value.trim();
            const activity = activityElement.value ? activityElement.value.trim() : ''; // Ensure it exists
    
            console.log("Form Values:", { regNo, name, activity }); // Debug: Check values
    
            // Validate input
            if (!regNo || !name || !activity) {
                showAlert('All fields are required!', 'error');
                return;
            }
    
            const formData = { regNo, name, activity };
    
            // Make API request
            apiRequest(CONFIG.API_ENDPOINTS.ACTIVITY, 'POST', formData)
                .then(data => {
                    if (data && data.success) {
                        showAlert('Activity logged successfully', 'success');
                        e.target.reset(); // Ensure proper form reset
                        loadActivityLog();
                    } else {
                        const errorMessage = data?.message || 'Failed to log activity';
                        console.error('API Error:', errorMessage);
                        showAlert(`Error: ${errorMessage}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('API request error:', error);
                    showAlert('An error occurred while logging activity', 'error');
                });
    
        } catch (error) {
            console.error('Unexpected error:', error);
            showAlert('An unexpected error occurred!', 'error');
        }
    }
    
    
    
    
    function handleAddBookFormSubmit(e) {
        e.preventDefault();
        const formData = {
            id: document.getElementById('bookId').value,
            title: document.getElementById('title').value,
            author: document.getElementById('author').value,
            publisher: document.getElementById('publisher').value,
            year: document.getElementById('year').value
        };
        
        if (!formData.id || !formData.title || !formData.author || !formData.publisher || !formData.year) {
            showAlert('Please fill all required fields', 'error');
            return;
        }
        
        apiRequest(CONFIG.API_ENDPOINTS.ADD_BOOK, 'POST', formData)
            .then(data => {
                if (data.success) {
                    showAlert('Book added successfully', 'success');
                    e.target.reset(); // Use e.target instead of this
                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(handleApiError('adding book'));
    }
    
    function handleIssueBookFormSubmit(e) {
        e.preventDefault();
        const formData = {
            bookId: document.getElementById('issueBookId').value,
            studentName: document.getElementById('issueStudentName').value,
            regNo: document.getElementById('issueRegNo').value
        };
        
        if (!formData.bookId || !formData.studentName || !formData.regNo) {
            showAlert('Please fill all required fields', 'error');
            return;
        }
        
        apiRequest(CONFIG.API_ENDPOINTS.ISSUE_BOOK, 'POST', formData)
            .then(data => {
                if (data.success) {
                    showAlert(`Book issued successfully to ${formData.studentName}`, 'success');
                    e.target.reset(); // Use e.target instead of this
                    loadIssuedBooks();
                    loadBooks();
                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(handleApiError('issuing book'));
    }
        
       
    
    function handleReturnBookFormSubmit(e) {
        e.preventDefault();
        const bookId = document.getElementById('returnBookId').value;
        
        if (!bookId) {
            showAlert('Please enter a book ID', 'error');
            return;
        }
        
        // First check if the book exists and get its details
        apiRequest(`${CONFIG.API_ENDPOINTS.GET_ISSUED}?bookId=${bookId}`, 'GET')
            .then(data => {
                if (data && data.length > 0) {
                    const book = data[0];
                    
                    // Auto calculate fine
                    const { daysOverdue, fine } = calculateFine(book.return_date);
                    
                    // Return the book with the calculated fine
                    return apiRequest(CONFIG.API_ENDPOINTS.RETURN_BOOK, 'POST', { 
                        bookId, 
                        fine,
                        daysOverdue 
                    }).then(returnData => {
                        // Attach fine information to the response
                        return { ...returnData, fine, daysOverdue };
                    });
                } else {
                    throw new Error('Book not found or not issued');
                }
            })
            .then(data => {
                if (data.success) {
                    let message = 'Book returned successfully';
                    if (data.fine > 0) {
                        message += `. Fine: Rs. ${data.fine} (${data.daysOverdue} days overdue)`;
                    }
                    showAlert(message, 'success');
                    e.target.reset();
                    loadIssuedBooks();
                    loadBooks();
                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error returning book:', error);
                showAlert(error.message || 'An error occurred while returning the book', 'error');
            });
    }
    
    function handleDeleteBookFormSubmit(e) {
        e.preventDefault();
        const bookId = document.getElementById('deleteBookId').value;
        
        if (!bookId) {
            showAlert('Please enter a book ID', 'error');
            return;
        }
        
        if (!confirm(`Are you sure you want to delete the book with ID: ${bookId}?`)) {
            return;
        }
        
        apiRequest(CONFIG.API_ENDPOINTS.DELETE_BOOK, 'POST', { bookId })
            .then(data => {
                if (data.success) {
                    showAlert('Book deleted successfully', 'success');
                    e.target.reset(); // Use e.target instead of this
                    loadBooks();
                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(handleApiError('deleting book'));
    }

    function handleBookSearch() {
        const searchTerm = document.getElementById('searchBookId').value.trim();
        const searchOptionElement = document.getElementById('searchOption');
        
        if (!searchTerm) {
            showAlert('Please enter a search term', 'error');
            return;
        }
        
        if (!searchOptionElement || !searchOptionElement.value) {
            showAlert('Please select a search criteria', 'error');
            return;
        }
        
        const searchOption = searchOptionElement.value;
        state.searchFilter = searchTerm;
        state.searchOption = searchOption.toLowerCase();
    
        // Build the query string with both parameters
        const queryString = `?term=${encodeURIComponent(searchTerm)}&option=${encodeURIComponent(state.searchOption)}`;
        const apiUrl = `${CONFIG.API_ENDPOINTS.GET_BOOKS}${queryString}`;
    
        console.log("Sending API request to:", apiUrl);
    
        apiRequest(apiUrl, 'GET')
            .then(response => {
                console.log("API Response:", response);
    
                if (!response || !response.success || !Array.isArray(response.books)) {
                    throw new Error(response.message || "Invalid API response");
                }
    
                const books = response.books;
                renderBooksTable(books);
    
                // Show feedback about search results
                const resultCount = books.length;
                const message = resultCount > 0 
                    ? `Found ${resultCount} books matching '${searchTerm}' in ${searchOption}`
                    : `No books found matching '${searchTerm}' in ${searchOption}`;
                
                showAlert(message, resultCount > 0 ? 'success' : 'info');
            })
            .catch(error => {
                console.error('Error searching books:', error);
                showAlert(error.message || 'An error occurred while searching books', 'error');
            });
    }
    
    
    function handleBooksRefresh() {
        try {
            // Clear the search input and reset state
            const searchInput = document.getElementById('searchBookId');
            if (searchInput) searchInput.value = '';
    
            // Reset the search option dropdown to default
            const searchOptionElement = document.getElementById('searchOption');
            if (searchOptionElement) searchOptionElement.selectedIndex = 0;
    
            // Reset search state
            state.searchFilter = '';
            state.searchOption = '';
    
            console.log("Refreshing book list...");
    
            // Load all books
            loadBooks()
                .then(() => showAlert('Book list refreshed successfully', 'success'))
                .catch(error => {
                    console.error('Error refreshing books:', error);
                    showAlert('Failed to refresh book list', 'error');
                });
    
        } catch (error) {
            console.error('Unexpected error in handleBooksRefresh:', error);
            showAlert('An unexpected error occurred', 'error');
        }
    }
    
    function addFineStyles() {
        // Create a style element if it doesn't exist already
        let styleElement = document.getElementById('fine-system-styles');
        if (!styleElement) {
            styleElement = document.createElement('style');
            styleElement.id = 'fine-system-styles';
            document.head.appendChild(styleElement);
            
            // Add CSS for fine-related styling
            styleElement.textContent = `
                .status-available {
                    color: green;
                    font-weight: bold;
                }
                .status-issued {
                    color: #ff9900;
                    font-weight: bold;
                }
                td[style*="color: red"] {
                    font-weight: bold;
                }
            `;
        }
    }
    
    // ======================
    // Data Loading Functions
    // ======================
    function initFineSystem() {
        try {
            // Add the necessary styles
            addFineStyles();
            
            // Make sure the issued books table header has the Fine column
            const issuedBooksTableHeader = document.querySelector('#issuedBooksTable thead tr');
            if (issuedBooksTableHeader) {
                // Check if the Fine column already exists
                const headers = issuedBooksTableHeader.querySelectorAll('th');
                let hasFineColumn = false;
                
                for (const header of headers) {
                    if (header.textContent.trim() === 'Fine') {
                        hasFineColumn = true;
                        break;
                    }
                }
                
                // Add the Fine column if it doesn't exist
                if (!hasFineColumn) {
                    const fineHeader = document.createElement('th');
                    fineHeader.textContent = 'Fine';
                    issuedBooksTableHeader.appendChild(fineHeader);
                }
            }
        } catch (error) {
            console.error('Error initializing fine system:', error);
            // Don't throw an error to prevent blocking the rest of the initialization
        }
    }
    
    
    function loadActivityLog() {
        apiRequest(CONFIG.API_ENDPOINTS.GET_ACTIVITY, 'GET')
            .then(data => {
                renderActivityTable(data);
            })
            .catch(handleApiError('loading activity log'));
    }
    
    // Update the loadBooks function to handle different search options and make it return a Promise
    function loadBooks(searchTerm = null, searchOption = null) {
        let url = CONFIG.API_ENDPOINTS.GET_BOOKS;
        
        // Determine query parameters
        if (searchTerm && searchOption) {
            url += `?term=${encodeURIComponent(searchTerm)}&option=${encodeURIComponent(searchOption)}`;
        } else if (state.searchFilter && state.searchOption) {
            url += `?term=${encodeURIComponent(state.searchFilter)}&option=${encodeURIComponent(state.searchOption)}`;
        }
    
        console.log("Fetching books from:", url);
    
        return apiRequest(url, 'GET')
            .then(response => {
                console.log("API Response:", response);
    
                // Handle API response based on its format
                let booksData = response;
                
                // If response has a "books" property, use that instead
                if (response && response.books) {
                    booksData = response.books;
                }
                
                // Ensure we have an array to work with
                if (!Array.isArray(booksData)) {
                    console.warn("Converting non-array response to empty array");
                    booksData = [];
                }
    
                renderBooksTable(booksData);
                return booksData; // Return the data for promise chaining
            })
            .catch(error => {
                console.error("Error loading books:", error);
                handleApiError("loading books")(error);
                return []; // Return empty array to avoid breaking promise chain
            });
    }
    

    
    function loadIssuedBooks() {
        apiRequest(CONFIG.API_ENDPOINTS.GET_ISSUED, 'GET')
            .then(data => {
                renderIssuedBooksTable(data);
            })
            .catch(handleApiError('loading issued books'));
    }
    
    // ======================
    // Rendering Functions
    // ======================
    
    function renderActivityTable(activities) {
        if (!DOM.activityTable) {
            console.error("Activity table not found");
            return;
        }
        
        DOM.activityTable.innerHTML = '';
        
        if (!activities || !Array.isArray(activities) || activities.length === 0) {
            DOM.activityTable.innerHTML = '<tr><td colspan="6" class="empty-table">No activity records found</td></tr>';
            return;
        }
        
        activities.forEach(activity => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHTML(activity.registration_no || '')}</td>
                <td>${escapeHTML(activity.name || '')}</td>
                <td>${escapeHTML(activity.activity || '')}</td>
                <td>${escapeHTML(activity.date || '')}</td>
                <td>${escapeHTML(activity.time || '')}</td>
                <td><span class="delete-btn" data-id="${escapeHTML(activity.registration_no || '')}"><i class="fas fa-trash"></i></span></td>
            `;
            DOM.activityTable.appendChild(row);
        });
        
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const regNo = this.getAttribute('data-id');
                deleteActivityLogEntry(regNo);
            });
        });
    }
    
    function renderBooksTable(books) {
        // Check if table element exists
        if (!DOM.booksTable) {
            console.error("Books table not found");
            return;
        }
    
        // Clear existing table content
        DOM.booksTable.innerHTML = '';
    
        // Handle empty or invalid book list
        if (!Array.isArray(books) || books.length === 0) {
            DOM.booksTable.innerHTML = '<tr><td colspan="6" class="empty-table">No books found</td></tr>';
            return;
        }
    
        books.forEach(book => {
            const row = document.createElement('tr');
            
            // Extract values with fallback defaults
            const id = book.id || '';
            const title = book.title || '';
            const author = book.author || '';
            const publisher = book.publisher || '';
            const year = book.year || '';
            const status = book.status || 'Unknown';
            const statusClass = status === 'Available' ? 'status-available' : 'status-issued';
    
            // Ensure year is properly displayed (default to 'N/A' if missing)
            const bookYear = (year && !isNaN(year)) ? escapeHTML(String(year)) : 'N/A';
    
            // Populate row with escaped data
            row.innerHTML = `
                <td>${escapeHTML(id)}</td>
                <td>${escapeHTML(title)}</td>
                <td>${escapeHTML(author)}</td>
                <td>${escapeHTML(publisher)}</td>
                <td>${bookYear}</td>
                <td class="${statusClass}">${escapeHTML(status)}</td>
            `;
    
            DOM.booksTable.appendChild(row);
        });
    }
    
    
    
    function renderIssuedBooksTable(books) {
        if (!DOM.issuedBooksTable) {
            console.error("Issued books table not found");
            return;
        }
        
        DOM.issuedBooksTable.innerHTML = '';
        
        if (!books || !Array.isArray(books) || books.length === 0) {
            DOM.issuedBooksTable.innerHTML = '<tr><td colspan="9" class="empty-table">No issued books found</td></tr>';
            return;
        }
        
        const currentDate = new Date();
        
        books.forEach(book => {
            const row = document.createElement('tr');
            
            // Auto calculate fine
            const { daysOverdue, fine } = calculateFine(book.return_date, currentDate);
            const isOverdue = daysOverdue > 0;
            
            // Format display strings
            const daysOverdueText = isOverdue ? `(${daysOverdue} days)` : '';
            const fineText = isOverdue ? `Rs. ${fine}` : 'No fine';
            
            row.innerHTML = `
                <td>${escapeHTML(book.id || '')}</td>
                <td>${escapeHTML(book.book_id || '')}</td>
                <td>${escapeHTML(book.title || '')}</td>
                <td>${escapeHTML(book.student_name || '')}</td>
                <td>${escapeHTML(book.registration_number || '')}</td>
                <td>${escapeHTML(book.issue_date || '')}</td>
                <td style="${isOverdue ? 'color: red;' : ''}">${escapeHTML(book.return_date || '')} ${daysOverdueText}</td>
                <td style="${isOverdue ? 'color: red;' : ''}">${fineText}</td>
            `;
            DOM.issuedBooksTable.appendChild(row);
        });
    }
    
    
    // ======================
    // Utility Functions
    // ======================
    function deleteActivityLogEntry(regNo) {
        if (!confirm('Are you sure you want to delete this activity record?')) {
            return;
        }
        
        // Make sure we're using the correct endpoint for deletion
        apiRequest(CONFIG.API_ENDPOINTS.DELETE_ACTIVITY, 'POST', { 
            regNo: regNo 
        })
        .then(data => {
            if (data.success) {
                showAlert('Activity record deleted successfully', 'success');
                loadActivityLog();
            } else {
                showAlert('Error: ' + (data.message || 'Failed to delete activity'), 'error');
            }
        })
        .catch(handleApiError('deleting activity record'));
    }
  
    
    
    function logAdminActivity() {
        apiRequest(CONFIG.API_ENDPOINTS.ACTIVITY, 'POST', {
            regNo: 'ADMIN',
            name: 'Administrator',
            activity: 'Admin Login'
        })
        .then(data => {
            if (!data.success) {
                console.error('Failed to log admin activity:', data.message || 'Unknown error');
                // Continue silently - no need to alert the user about this internal logging
            }
        })
        .catch(error => {
            // Log the error but don't disrupt the user experience
            console.error('Error logging admin activity:', error.message || error);
            // No alert needed for this background task
        });
    }
    function showAlert(message, type) {
        const existingAlert = document.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `
            <div class="alert-content">
                <i class="alert-icon fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${escapeHTML(message)}</span>
            </div>
            <button class="alert-close"><i class="fas fa-times"></i></button>
        `;
        
        DOM.container.prepend(alertDiv);
        
        alertDiv.querySelector('.alert-close').addEventListener('click', () => {
            alertDiv.remove();
        });
        
        setTimeout(() => {
            alertDiv.classList.add('fade-out');
            setTimeout(() => {
                alertDiv.remove();
            }, 500);
        }, CONFIG.ALERT_TIMEOUT);
    }
    
    function apiRequest(url, method, data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        if (data) {
            options.body = JSON.stringify(data);
        }
        
        return fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            });
    }
    
    function handleApiError(action) {
        return function(error) {
            console.error(`Error ${action}:`, error);
            showAlert(`An error occurred while ${action}. Please try again later.`, 'error');
        };
    }
    
    function escapeHTML(str) {
        if (!str || typeof str !== 'string') return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Initialize the application
    function init() {
        loadActivityLog();
        initFineSystem();
    }
    
    // Start the application
    init();
});