<?php
require('database.php');
require('session.php');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['candidate_id']) || !isset($data['stage'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$stmt = $conn->prepare("UPDATE job_applications SET stage = ? WHERE id = ?");
$stmt->bind_param("si", $data['stage'], $data['candidate_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?>