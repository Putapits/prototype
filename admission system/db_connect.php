<?php
// Database connection settings
$servername = "localhost";
$username = "root";  // Default XAMPP/WAMP username
$password = "";      // Default XAMPP/WAMP password
$dbname = "admission_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");
?>