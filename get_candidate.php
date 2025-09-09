<?php
require('database.php');
require('session.php');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['candidate_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing candidate ID']);
    exit;
}

$stmt = $conn->prepare("SELECT ja.*, jp.job_title 
    FROM job_applications ja 
    LEFT JOIN job_positions jp ON ja.position_id = jp.id 
    WHERE ja.id = ?");
    
$stmt->bind_param("i", $data['candidate_id']);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $candidate = $result->fetch_assoc();
    
    if ($candidate) {
        echo json_encode(['success' => true, 'candidate' => $candidate]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Candidate not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?>