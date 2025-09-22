<?php
// filepath: /Applications/XAMPP/xamppfiles/htdocs/update_candidate_stage.php
require('database.php');
require('session.php');

// Set the content type to JSON
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['candidate_id']) || !isset($data['stage'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
    
    $candidate_id = $data['candidate_id'];
    $stage = $data['stage'];
    
    // Update candidate stage
    $update_query = "UPDATE job_applications SET stage = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "si", $stage, $candidate_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        if (mysqli_stmt_affected_rows($update_stmt) > 0) {
            echo json_encode(['success' => true, 'message' => 'Stage updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or candidate not found']);
        }
    } else {
        throw new Exception('Database update failed: ' . mysqli_stmt_error($update_stmt));
    }
    
    mysqli_stmt_close($update_stmt);
    
} catch (Exception $e) {
    error_log("Update Stage Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>