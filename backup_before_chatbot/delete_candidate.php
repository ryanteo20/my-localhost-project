<?php
require('database.php');
require('session.php');

header('Content-Type: application/json');

if ($_SESSION['role'] != 'Employer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $candidate_id = $input['candidate_id'] ?? '';
    
    if (empty($candidate_id)) {
        echo json_encode(['success' => false, 'message' => 'Candidate ID is required']);
        exit();
    }
    
    try {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        // Get candidate info for cleanup
        $candidate_query = "SELECT resume_path FROM job_applications WHERE id = ?";
        $stmt = mysqli_prepare($conn, $candidate_query);
        mysqli_stmt_bind_param($stmt, "i", $candidate_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $candidate = mysqli_fetch_assoc($result);
        
        if (!$candidate) {
            throw new Exception('Candidate not found');
        }
        
        // Delete resume file if exists
        if (!empty($candidate['resume_path']) && file_exists($candidate['resume_path'])) {
            unlink($candidate['resume_path']);
        }
        
        // Delete candidate record
        $delete_query = "DELETE FROM job_applications WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $candidate_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error deleting candidate: ' . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        echo json_encode(['success' => true, 'message' => 'Candidate deleted successfully']);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>