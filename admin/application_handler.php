<?php
// Include database connection
require_once 'db_connect.php';

// Function to get all applications
function getAllApplications() {
    global $conn;
    
    $sql = "SELECT a.application_id, CONCAT(s.first_name, ' ', s.last_name) as student_name, 
            p.program_name, a.submission_date, a.application_status 
            FROM applications a
            JOIN students s ON a.student_id = s.student_id
            JOIN programs p ON a.program_id = p.program_id
            ORDER BY a.submission_date DESC";
    
    $result = $conn->query($sql);
    $applications = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $applications[] = $row;
        }
    }
    
    return $applications;
}

// Function to add a new application
function addApplication($studentId, $typeId, $programId, $semesterId) {
    global $conn;
    
    $sql = "INSERT INTO applications (student_id, type_id, program_id, semester_id, application_status) 
            VALUES (?, ?, ?, ?, 'Pending')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $studentId, $typeId, $programId, $semesterId);
    
    if ($stmt->execute()) {
        $applicationId = $stmt->insert_id;
        
        // Add entry to status history
        $historySQL = "INSERT INTO application_status_history (application_id, status, comments) 
                      VALUES (?, 'Pending', 'Application submitted')";
        $historyStmt = $conn->prepare($historySQL);
        $historyStmt->bind_param("i", $applicationId);
        $historyStmt->execute();
        
        return $applicationId;
    } else {
        return false;
    }
}

// Function to update application status
function updateApplicationStatus($applicationId, $status, $comments, $updatedBy) {
    global $conn;
    
    // Update the application status
    $sql = "UPDATE applications SET application_status = ?, 
            decision_date = CASE WHEN ? IN ('Approved', 'Rejected') THEN CURRENT_TIMESTAMP ELSE decision_date END 
            WHERE application_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $status, $status, $applicationId);
    
    if ($stmt->execute()) {
        // Add to status history
        $historySQL = "INSERT INTO application_status_history (application_id, status, comments, updated_by) 
                      VALUES (?, ?, ?, ?)";
        $historyStmt = $conn->prepare($historySQL);
        $historyStmt->bind_param("isss", $applicationId, $status, $comments, $updatedBy);
        
        return $historyStmt->execute();
    }
    
    return false;
}

// Function to get application details
function getApplicationDetails($applicationId) {
    global $conn;
    
    $sql = "SELECT a.*, 
            CONCAT(s.first_name, ' ', s.middle_name, ' ', s.last_name) as student_name,
            s.email as student_email, s.phone as student_phone,
            p.program_name, st.type_name, sem.semester_name
            FROM applications a
            JOIN students s ON a.student_id = s.student_id
            JOIN programs p ON a.program_id = p.program_id
            JOIN student_types st ON a.type_id = st.type_id
            JOIN semesters sem ON a.semester_id = sem.semester_id
            WHERE a.application_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $application = $result->fetch_assoc();
        
        // Get application documents
        $docSql = "SELECT * FROM application_documents WHERE application_id = ?";
        $docStmt = $conn->prepare($docSql);
        $docStmt->bind_param("i", $applicationId);
        $docStmt->execute();
        $docResult = $docStmt->get_result();
        
        $application['documents'] = [];
        while ($doc = $docResult->fetch_assoc()) {
            $application['documents'][] = $doc;
        }
        
        // Get status history
        $historySql = "SELECT * FROM application_status_history 
                      WHERE application_id = ? ORDER BY status_date DESC";
        $historyStmt = $conn->prepare($historySql);
        $historyStmt->bind_param("i", $applicationId);
        $historyStmt->execute();
        $historyResult = $historyStmt->get_result();
        
        $application['status_history'] = [];
        while ($history = $historyResult->fetch_assoc()) {
            $application['status_history'][] = $history;
        }
        
        // Get student type specific details
        if ($application['type_id'] == 1) { // New Student
            $detailSql = "SELECT * FROM new_student_details WHERE application_id = ?";
            $detailStmt = $conn->prepare($detailSql);
            $detailStmt->bind_param("i", $applicationId);
            $detailStmt->execute();
            $detailResult = $detailStmt->get_result();
            
            if ($detailResult->num_rows > 0) {
                $application['type_details'] = $detailResult->fetch_assoc();
            }
        } elseif ($application['type_id'] == 2) { // Returning Student
            $detailSql = "SELECT * FROM returning_student_details WHERE application_id = ?";
            $detailStmt = $conn->prepare($detailSql);
            $detailStmt->bind_param("i", $applicationId);
            $detailStmt->execute();
            $detailResult = $detailStmt->get_result();
            
            if ($detailResult->num_rows > 0) {
                $application['type_details'] = $detailResult->fetch_assoc();
            }
        } elseif ($application['type_id'] == 3) { // Transfer Student
            $detailSql = "SELECT * FROM transfer_student_details WHERE application_id = ?";
            $detailStmt = $conn->prepare($detailSql);
            $detailStmt->bind_param("i", $applicationId);
            $detailStmt->execute();
            $detailResult = $detailStmt->get_result();
            
            if ($detailResult->num_rows > 0) {
                $application['type_details'] = $detailResult->fetch_assoc();
            }
        }
        
        return $application;
    }
    
    return null;
}

// Handle API requests if this file is accessed directly
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'getAllApplications':
            echo json_encode(getAllApplications());
            break;
        
        case 'addApplication':
            $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
            $typeId = isset($_POST['type_id']) ? (int)$_POST['type_id'] : 0;
            $programId = isset($_POST['program_id']) ? (int)$_POST['program_id'] : 0;
            $semesterId = isset($_POST['semester_id']) ? (int)$_POST['semester_id'] : 0;
            
            $result = addApplication($studentId, $typeId, $programId, $semesterId);
            echo json_encode(['success' => $result !== false, 'application_id' => $result]);
            break;
            
        case 'updateStatus':
            $applicationId = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
            $status = isset($_POST['status']) ? $_POST['status'] : '';
            $comments = isset($_POST['comments']) ? $_POST['comments'] : '';
            $updatedBy = isset($_POST['updated_by']) ? $_POST['updated_by'] : '';
            
            $result = updateApplicationStatus($applicationId, $status, $comments, $updatedBy);
            echo json_encode(['success' => $result]);
            break;
            
        case 'getApplicationDetails':
            $applicationId = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
            $result = getApplicationDetails($applicationId);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}
?>