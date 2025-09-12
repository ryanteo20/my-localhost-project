<?php
require('database.php');
require('session.php');

header('Content-Type: application/json');

// Check if user is logged in and is an employer
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Employer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }
    
    $candidate_id = $data['candidate_id'] ?? null;
    
    if (!$candidate_id) {
        throw new Exception('Candidate ID is required');
    }
    
    // Fetch candidate details including resume
    $stmt = mysqli_prepare($conn, "
        SELECT ja.*, jp.job_title 
        FROM job_applications ja 
        LEFT JOIN job_positions jp ON ja.position_id = jp.id 
        WHERE ja.id = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $candidate_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Check if resume file exists
        $resume_exists = false;
        $resume_filename = '';
        $resume_url = '';
        
        if (!empty($row['resume_path'])) {
            $resume_exists = file_exists($row['resume_path']);
            $resume_filename = basename($row['resume_path']);
            $resume_url = $row['resume_path'];
        }
        
        mysqli_stmt_close($stmt);
        
        echo json_encode([
            'success' => true,
            'candidate' => $row,
            'resume' => [
                'exists' => $resume_exists,
                'filename' => $resume_filename,
                'url' => $resume_url,
                'path' => $row['resume_path'] ?? ''
            ]
        ]);
    } else {
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => false, 'message' => 'Candidate not found']);
    }
    
} catch (Exception $e) {
    error_log('Get candidate error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>