<?php
// process_transfer_student.php - Process transfer student application form

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
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $middleName = mysqli_real_escape_string($conn, $_POST['middleName'] ?? ''); // Optional field
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $previousInstitution = mysqli_real_escape_string($conn, $_POST['previousInstitution']);
    $yearFrom = mysqli_real_escape_string($conn, $_POST['yearFrom']);
    $yearTo = mysqli_real_escape_string($conn, $_POST['yearTo']);
    $program = mysqli_real_escape_string($conn, $_POST['program']);
    $transferCredits = mysqli_real_escape_string($conn, $_POST['transferCredits']);
    $reasonForTransfer = mysqli_real_escape_string($conn, $_POST['reasonForTransfer']);
    
    // Validate required fields
    if(empty($firstName) || empty($lastName) || empty($email) || empty($previousInstitution) || 
       empty($yearFrom) || empty($yearTo) || empty($program) || empty($transferCredits)) {
        $response['message'] = "Please fill all required fields.";
        echo json_encode($response);
        exit;
    }
    
    // Validate email format
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format.";
        echo json_encode($response);
        exit;
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Check if student already exists
        $sql = "SELECT student_id FROM students WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $studentId = $row['student_id'];
        } else {
            // Insert new student
            $sql = "INSERT INTO students (first_name, middle_name, last_name, email, phone) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssss", $firstName, $middleName, $lastName, $email, $phone);
            mysqli_stmt_execute($stmt);
            $studentId = mysqli_insert_id($conn);
        }
        
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
        
        // Get student type ID for 'Transfer Student'
        $sql = "SELECT type_id FROM student_types WHERE type_name = 'Transfer Student'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        $typeId = $row['type_id'];
        
        // Get default semester ID (Fall 2025)
        $sql = "SELECT semester_id FROM semesters WHERE semester_code = 'fall2025'";
        $result = mysqli_query($conn, $sql);
        
        if(mysqli_num_rows($result) == 0) {
            // If fall2025 doesn't exist, get any available semester
            $sql = "SELECT semester_id FROM semesters LIMIT 1";
            $result = mysqli_query($conn, $sql);
            
            if(mysqli_num_rows($result) == 0) {
                throw new Exception("No valid semester found. Please contact the admissions office.");
            }
        }
        
        $row = mysqli_fetch_assoc($result);
        $semesterId = $row['semester_id'];
        
        // Insert application
        $sql = "INSERT INTO applications (student_id, type_id, program_id, semester_id) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiii", $studentId, $typeId, $programId, $semesterId);
        mysqli_stmt_execute($stmt);
        $applicationId = mysqli_insert_id($conn);
        
        // Handle transcript file upload
        $transcriptFilePath = NULL;
        if(isset($_FILES['transcripts']) && $_FILES['transcripts']['error'] == 0) {
            $targetDir = "uploads/transcripts/";
            
            // Create directory if it doesn't exist
            if(!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $fileName = $studentId . '_' . time() . '_' . basename($_FILES["transcripts"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            
            // Upload file
            if(move_uploaded_file($_FILES["transcripts"]["tmp_name"], $targetFilePath)) {
                $transcriptFilePath = $targetFilePath;
            } else {
                throw new Exception("There was an error uploading your transcript.");
            }
        }
        
        // Insert transfer student details
        $sql = "INSERT INTO transfer_student_details (application_id, previous_institution, year_from, year_to, 
                completed_credits, transcript_file_path, reason_for_transfer) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isiisss", $applicationId, $previousInstitution, $yearFrom, $yearTo, 
                            $transferCredits, $transcriptFilePath, $reasonForTransfer);
        mysqli_stmt_execute($stmt);
        
        // Insert initial status history
        $sql = "INSERT INTO application_status_history (application_id, status, comments) 
                VALUES (?, 'Pending', 'Transfer student application submitted')";
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

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>