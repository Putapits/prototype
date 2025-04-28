<?php
// process_returning_student.php - Process returning student application form

// Include database connection
require_once 'db_config.php';

// Initialize response array
$response = array(
    'status' => false,
    'message' => '',
    'redirect' => ''
);

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Collect and sanitize form data
    $studentId = mysqli_real_escape_string($conn, $_POST['studentId']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $program = mysqli_real_escape_string($conn, $_POST['program']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $additionalInfo = mysqli_real_escape_string($conn, isset($_POST['additionalInfo']) ? $_POST['additionalInfo'] : '');
    
    // Validate required fields
    if(empty($studentId) || empty($email) || empty($program) || empty($semester)) {
        $response['message'] = "Please fill all required fields.";
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            displayPage($response);
        }
        exit;
    }
    
    // Validate email format
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format.";
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            displayPage($response);
        }
        exit;
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Check if student exists with this email
        $sql = "SELECT student_id FROM students WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 0) {
            $response['message'] = "No student record found with the email address: " . htmlspecialchars($email) . ". Please verify your email address or register as a new student.";
            $response['redirect'] = "index.html";
            if (isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                displayPage($response);
            }
            exit;
        }
        
        $row = mysqli_fetch_assoc($result);
        $databaseStudentId = $row['student_id'];
        
        // Get program ID
        $sql = "SELECT program_id FROM programs WHERE program_code = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $program);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 0) {
            $response['message'] = "Invalid program selected.";
            if (isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                displayPage($response);
            }
            exit;
        }
        
        $row = mysqli_fetch_assoc($result);
        $programId = $row['program_id'];
        
        // Get semester ID
        $sql = "SELECT semester_id FROM semesters WHERE semester_code = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $semester);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 0) {
            $response['message'] = "Invalid semester selected.";
            if (isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                displayPage($response);
            }
            exit;
        }
        
        $row = mysqli_fetch_assoc($result);
        $semesterId = $row['semester_id'];
        
        // Get student type ID for 'Returning Student'
        $sql = "SELECT type_id FROM student_types WHERE type_name = 'Returning Student'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        $typeId = $row['type_id'];
        
        // Insert application
        $sql = "INSERT INTO applications (student_id, type_id, program_id, semester_id) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiii", $databaseStudentId, $typeId, $programId, $semesterId);
        mysqli_stmt_execute($stmt);
        $applicationId = mysqli_insert_id($conn);
        
        // Insert returning student details
        $sql = "INSERT INTO returning_student_details (application_id, previous_student_id, additional_info) 
                VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iss", $applicationId, $studentId, $additionalInfo);
        mysqli_stmt_execute($stmt);
        
        // Insert initial status history
        $sql = "INSERT INTO application_status_history (application_id, status, comments) 
                VALUES (?, 'Pending', 'Returning student application submitted')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $applicationId);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        $response['status'] = true;
        $response['message'] = "Application submitted successfully! Your Application ID is: " . $applicationId;
        header('Location: confirmation.php?id=' . $applicationId);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $response['message'] = "Error: " . $e->getMessage();
    }
    
} else {
    $response['message'] = "Invalid request method.";
}

// Helper function to check if request is AJAX
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Helper function to display the page
function displayPage($response) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Application Status</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-6">
            <div class="text-center">
                <?php if ($response['status']): ?>
                    <div class="text-green-600 mb-4">
                        <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <h2 class="text-xl font-semibold mt-2">Success!</h2>
                    </div>
                <?php else: ?>
                    <div class="text-red-600 mb-4">
                        <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <h2 class="text-xl font-semibold mt-2">Error</h2>
                    </div>
                <?php endif; ?>
                
                <p class="text-gray-700 mb-6"><?php echo htmlspecialchars($response['message']); ?></p>
                
                <?php if (isset($response['redirect'])): ?>
                    <a href="<?php echo htmlspecialchars($response['redirect']); ?>" 
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                        Register as New Student
                    </a>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="javascript:history.back()" 
                       class="text-blue-600 hover:text-blue-800 font-medium">
                        ← Return to Previous Page
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}

// Handle the response
if (isAjaxRequest()) {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    displayPage($response);
}
?>