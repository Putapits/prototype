<?php
// process_new_student.php - Process new student application form

// Include database connection
require_once 'db_config.php';

// Initialize response array
$response = array(
    'status' => false,
    'message' => '',
    'redirect' => '',
    'debug' => array() // Add debug array
);

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Debug: Log all POST data
    $response['debug']['post_data'] = $_POST;
    
    // Collect and sanitize form data
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName'] ?? '');
    $middleName = mysqli_real_escape_string($conn, $_POST['middleName'] ?? ''); // Optional field
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? ''); // Optional field
    $dob = mysqli_real_escape_string($conn, $_POST['dob'] ?? ''); // Optional field
    $program = mysqli_real_escape_string($conn, $_POST['program'] ?? '');
    $highSchool = mysqli_real_escape_string($conn, $_POST['highSchool'] ?? '');
    $graduationYear = mysqli_real_escape_string($conn, $_POST['graduationYear'] ?? '');
    $personalStatement = mysqli_real_escape_string($conn, $_POST['personalStatement'] ?? ''); // Optional field
    
    // Debug: Log sanitized values
    $response['debug']['sanitized_values'] = array(
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'program' => $program,
        'highSchool' => $highSchool,
        'graduationYear' => $graduationYear
    );
    
    // Validate required fields
    $missingFields = array();
    if(empty($firstName)) $missingFields[] = 'First Name';
    if(empty($lastName)) $missingFields[] = 'Last Name';
    if(empty($email)) $missingFields[] = 'Email';
    if(empty($program)) $missingFields[] = 'Program';
    if(empty($highSchool)) $missingFields[] = 'High School';
    if(empty($graduationYear)) $missingFields[] = 'Graduation Year';
    
    if(!empty($missingFields)) {
        $response['message'] = "Please fill all required fields. Missing: " . implode(', ', $missingFields);
        $response['debug']['missing_fields'] = $missingFields;
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
    
    // Validate graduation year
    if($graduationYear < 2000 || $graduationYear > 2025) {
        $response['message'] = "Invalid graduation year.";
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
        // Check if student already exists with this email
        $sql = "SELECT student_id FROM students WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) > 0) {
            $response['message'] = "A student with this email already exists.";
            if (isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                displayPage($response);
            }
            exit;
        }
        
        // Insert new student
        $sql = "INSERT INTO students (first_name, middle_name, last_name, email, phone, date_of_birth) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $firstName, $middleName, $lastName, $email, $phone, $dob);
        mysqli_stmt_execute($stmt);
        $studentId = mysqli_insert_id($conn);
        
        // Get program ID
        $sql = "SELECT program_id FROM programs WHERE program_code = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $program);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 0) {
            throw new Exception("Invalid program selected.");
        }
        
        $row = mysqli_fetch_assoc($result);
        $programId = $row['program_id'];
        
        // Get semester ID for 1st Semester (automatically set for new students)
        $sql = "SELECT semester_id FROM semesters WHERE semester_code = '1sem'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        $semesterId = $row['semester_id'];
        
        // Get student type ID for 'New Student'
        $sql = "SELECT type_id FROM student_types WHERE type_name = 'New Student'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        $typeId = $row['type_id'];
        
        // Insert application
        $sql = "INSERT INTO applications (student_id, type_id, program_id, semester_id) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiii", $studentId, $typeId, $programId, $semesterId);
        mysqli_stmt_execute($stmt);
        $applicationId = mysqli_insert_id($conn);
        
        // Insert new student details
        $sql = "INSERT INTO new_student_details (application_id, high_school, graduation_year, personal_statement) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isis", $applicationId, $highSchool, $graduationYear, $personalStatement);
        mysqli_stmt_execute($stmt);
        
        // Insert initial status history
        $sql = "INSERT INTO application_status_history (application_id, status, comments) 
                VALUES (?, 'Pending', 'New student application submitted')";
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
