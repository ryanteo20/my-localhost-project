<?php
require('database.php');
require('session.php');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['position_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing position ID']);
    exit;
}

// Get current count of applications
$query = "SELECT COUNT(*) as count FROM job_applications WHERE position_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $data['position_id']);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];

// Update the job_positions table
$update_query = "UPDATE job_positions SET applications = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("ii", $count, $data['position_id']);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'count' => $count]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update count']);
}

$stmt->close();
$update_stmt->close();
$conn->close();
?>