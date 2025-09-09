<?php
// This should be the ENTIRE content of your database.php file
// Don't wrap $conn in a function or class

$servername = "localhost";
$username = "root";        // Your actual username
$password = "";            // Your actual password  
$dbname = "hr"; // Your actual database name

// Create connection - this MUST be in global scope
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

// Optional: For debugging (remove in production)
// echo "Database connected successfully";
?>