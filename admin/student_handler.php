<?php
// Include database connection
require_once 'db_connect.php';

// Function to get all students
function getAllStudents() {
    global $conn;
    
    $sql = "SELECT s.student_id, s.first_name, s.middle_name, s.last_name, s.email, s.phone, 
            COUNT(a.application_id) as application_count
            FROM students s
            LEFT JOIN applications a ON s.student_id = a.student_id
            GROUP BY s.student_id
            ORDER BY s.last_name, s.first_name";
    
    $result = $conn->query($sql);
    $students = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    
    return $students;
}

// Function to add new student
function addStudent($firstName, $middleName, $lastName, $email, $phone, $dateOfBirth) {
    global $conn;
    
    // Check if email already exists
    $checkSql = "SELECT student_id FROM students WHERE email = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Insert new student
    $sql = "INSERT INTO students (first_name, middle_name, last_name, email, phone, date_of_birth) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $firstName, $middleName, $lastName, $email, $phone, $dateOfBirth);
    
    if ($stmt->execute()) {
        return ['success' => true, 'student_id' => $stmt->insert_id];
    } else {
        return ['success' => false, 'message' => $stmt->error];
    }
}

// Function to get student details
function getStudentDetails($studentId) {
    global $conn;
    
    $sql = "SELECT * FROM students WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        // Get student applications
        $appSql = "SELECT a.application_id, a.application_status, a.submission_date, 
                p.program_name, s.semester_name
                FROM applications a 
                JOIN programs p ON a.program_id = p.program_id
                JOIN semesters s ON a.semester_id = s.semester_id
                WHERE a.student_id = ?
                ORDER BY a.submission_date DESC";
        
        $appStmt = $conn->prepare($appSql);
        $appStmt->bind_param("i", $studentId);
        $appStmt->execute();
        $appResult = $appStmt->get_result();
        
        $student['applications'] = [];
        while ($app = $appResult->fetch_assoc()) {
            $student['applications'][] = $app;
        }
        
        return $student;
    }
    
    return null;
}

// Function to update student
function updateStudent($studentId, $firstName, $middleName, $lastName, $email, $phone, $dateOfBirth) {
    global $conn;
    
    // Check if email already exists for another student
    $checkSql = "SELECT student_id FROM students WHERE email = ? AND student_id != ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("si", $email, $studentId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already exists for another student'];
    }
    
    // Update student
    $sql = "UPDATE students 
            SET first_name = ?, middle_name = ?, last_name = ?, 
                email = ?, phone = ?, date_of_birth = ? 
            WHERE student_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $firstName, $middleName, $lastName, $email, $phone, $dateOfBirth, $studentId);
    
    if ($stmt->execute()) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => $stmt->error];
    }
}

// Handle API requests if this file is accessed directly
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'getAllStudents':
            echo json_encode(getAllStudents());
            break;
        
        case 'addStudent':
            $firstName = isset($_POST['first_name']) ? $_POST['first_name'] : '';
            $middleName = isset($_POST['middle_name']) ? $_POST['middle_name'] : '';
            $lastName = isset($_POST['last_name']) ? $_POST['last_name'] : '';
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
            $dateOfBirth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
            
            $result = addStudent($firstName, $middleName, $lastName, $email, $phone, $dateOfBirth);
            echo json_encode($result);
            break;
            
        case 'getStudentDetails':
            $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
            $result = getStudentDetails($studentId);
            echo json_encode($result);
            break;
            
        case 'updateStudent':
            $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
            $firstName = isset($_POST['first_name']) ? $_POST['first_name'] : '';
            $middleName = isset($_POST['middle_name']) ? $_POST['middle_name'] : '';
            $lastName = isset($_POST['last_name']) ? $_POST['last_name'] : '';
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
            $dateOfBirth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
            
            $result = updateStudent($studentId, $firstName, $middleName, $lastName, $email, $phone, $dateOfBirth);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}
?>