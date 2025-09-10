<?php
require('database.php');
require('session.php');

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['position_id']) || !isset($data['candidate_name']) || !isset($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Prepare SQL statement
$stmt = $conn->prepare("INSERT INTO job_applications (position_id, candidate_name, email, rating, stage, created_at) VALUES (?, ?, ?, ?, 'New', NOW())");

$stmt->bind_param("issi", 
    $data['position_id'],
    $data['candidate_name'],
    $data['email'],
    $data['rating']
);

// Execute the statement
if ($stmt->execute()) {
    $candidate_id = $stmt->insert_id;
    
    // Return success response with candidate data
    $candidate = [
        'id' => $candidate_id,
        'name' => $data['candidate_name'],
        'email' => $data['email'],
        'position_id' => $data['position_id'],
        'rating' => $data['rating'],
        'stage' => 'New'
    ];
    
    echo json_encode(['success' => true, 'candidate' => $candidate]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?>