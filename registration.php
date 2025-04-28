<?php
// registration_handler.php - Processes the registration form submission

// Initialize session if needed for flash messages
session_start();

// Database connection parameters
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "admission_system";

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Process only if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $first_name = trim($_POST["first-name"]);
    $last_name = trim($_POST["last-name"]);
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm-password"]);
    
    // Validation
    if (empty($first_name)) {
        $response['errors']['first_name'] = "First name is required";
    }
    
    if (empty($last_name)) {
        $response['errors']['last_name'] = "Last name is required";
    }
    
    if (empty($email)) {
        $response['errors']['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = "Please enter a valid email address";
    }
    
    if (empty($password)) {
        $response['errors']['password'] = "Password is required";
    } elseif (strlen($password) < 8) {
        $response['errors']['password'] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirm_password) {
        $response['errors']['confirm_password'] = "Passwords do not match";
    }
    
    // If no errors, proceed with registration
    if (empty($response['errors'])) {
        try {
            // Create connection
            $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
            
            // Check connection
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Check if email already exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $response['errors']['email'] = "Email already registered";
                $check_stmt->close();
            } else {
                $check_stmt->close();
                
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Prepare and execute the SQL query
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssss", $first_name, $last_name, $email, $hashed_password);
                
                if ($stmt->execute()) {
                    // Registration successful
                    $response['success'] = true;
                    $response['message'] = "Registration successful! You can now log in.";
                    
                    // Set success message in session for display after redirect
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => 'Registration successful! You can now log in.'
                    ];
                    
                    // Close statement
                    $stmt->close();
                    $conn->close();
                    
                    // Redirect to login page
                    header("Location: login.php");
                    exit();
                } else {
                    throw new Exception("Registration failed: " . $conn->error);
                }
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
    }
    
    // If there are errors and we haven't redirected, store errors in session
    if (!$response['success']) {
        $_SESSION['form_errors'] = $response['errors'];
        $_SESSION['form_data'] = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email
        ];
        
        // Redirect back to the registration form
        header("Location: register.html");
        exit();
    }
}
?>