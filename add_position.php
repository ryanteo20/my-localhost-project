<?php
require('database.php');
require('session.php');

// Check if user is employer
if ($_SESSION['role'] != 'Employer') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['name'])) {
    $name = mysqli_real_escape_string($conn, $data['name']);
    $description = mysqli_real_escape_string($conn, $data['description']);
    
    $query = "INSERT INTO job_positions (position_name, description) VALUES ('$name', '$description')";
    
    if (mysqli_query($conn, $query)) {
        $id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>