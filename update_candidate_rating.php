<?php
require('database.php');
require('session.php');

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['candidate_id']) || !isset($data['rating'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate rating
$rating = (int)$data['rating'];
if ($rating < 0 || $rating > 3) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating value']);
    exit;
}

// Update the rating in the database
$stmt = $conn->prepare("UPDATE job_applications SET rating = ? WHERE id = ?");
$stmt->bind_param("ii", $rating, $data['candidate_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?>