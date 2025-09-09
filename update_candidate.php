<?php
require('database.php');
require('session.php');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['candidate_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing candidate ID']);
    exit;
}

$stmt = $conn->prepare("UPDATE job_applications SET 
    candidate_name = ?,
    email = ?,
    phone = ?,
    position_id = ?,
    notes = ?,
    tags = ?
    WHERE id = ?");

$stmt->bind_param("ssssssi", 
    $data['candidate_name'],
    $data['email'],
    $data['phone'],
    $data['position_id'],
    $data['notes'],
    $data['tags'],
    $data['candidate_id']
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?>