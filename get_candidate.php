<?php
// filepath: /Applications/XAMPP/xamppfiles/htdocs/get_candidate.php
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
    
    if (!$data || !isset($data['candidate_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing candidate ID']);
        exit;
    }
    
    $candidate_id = $data['candidate_id'];
    
    // Fetch candidate data with job position
    $query = "SELECT ja.*, jp.job_title 
              FROM job_applications ja 
              LEFT JOIN job_positions jp ON ja.position_id = jp.id 
              WHERE ja.id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $candidate_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Prepare resume information
        $resume_info = [
            'exists' => false,
            'filename' => null,
            'url' => null
        ];
        
        if ($row['resume_path'] && file_exists($row['resume_path'])) {
            $resume_info['exists'] = true;
            $resume_info['filename'] = basename($row['resume_path']);
            $resume_info['url'] = $row['resume_path'];
        }
        
        echo json_encode([
            'success' => true,
            'candidate' => $row,
            'resume' => $resume_info
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Candidate not found']);
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    error_log("Get Candidate Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>