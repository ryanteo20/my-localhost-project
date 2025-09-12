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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle form data for file uploads
        $candidate_name = $_POST['candidate_name'] ?? null;
        $email = $_POST['email'] ?? null;
        $position_id = $_POST['position_id'] ?? null;
        $rating = $_POST['rating'] ?? 0;
        $stage = $_POST['stage'] ?? 'New';
        $notes = $_POST['notes'] ?? '';
        
        // Validate required fields
        if (!$candidate_name || !$email || !$position_id) {
            throw new Exception('Missing required fields');
        }
        
        // Handle resume upload if present
        $resume_path = null;
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $resume_file = $_FILES['resume'];
            
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($resume_file['type'], $allowed_types)) {
                throw new Exception('Invalid file type. Only PDF, DOC, and DOCX files are allowed.');
            }

            if ($resume_file['size'] > $max_size) {
                throw new Exception('File size too large. Maximum size is 5MB.');
            }

            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/resumes/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Clean candidate name for filename
            $clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $candidate_name);
            $clean_name = preg_replace('/_+/', '_', $clean_name);
            $clean_name = trim($clean_name, '_');
            
            // Get file extension
            $file_extension = pathinfo($resume_file['name'], PATHINFO_EXTENSION);
            
            // Generate filename with candidate name and timestamp
            $filename = $clean_name . '_Resume_' . time() . '.' . $file_extension;
            $resume_path = $upload_dir . $filename;

            if (!move_uploaded_file($resume_file['tmp_name'], $resume_path)) {
                throw new Exception('Failed to upload resume file');
            }
        }
        
        // Insert new candidate
        $sql = "INSERT INTO job_applications (position_id, candidate_name, email, rating, stage, notes, resume_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception('Database prepare error: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, 'ississs', $position_id, $candidate_name, $email, $rating, $stage, $notes, $resume_path);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Database execute error: ' . mysqli_stmt_error($stmt));
        }
        
        $candidate_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        // Get position name for response
        $position_query = "SELECT job_title FROM job_positions WHERE id = ?";
        $position_stmt = mysqli_prepare($conn, $position_query);
        mysqli_stmt_bind_param($position_stmt, 'i', $position_id);
        mysqli_stmt_execute($position_stmt);
        $position_result = mysqli_stmt_get_result($position_stmt);
        $position_row = mysqli_fetch_assoc($position_result);
        mysqli_stmt_close($position_stmt);
        
        echo json_encode([
            'success' => true,
            'message' => 'Candidate added successfully',
            'candidate' => [
                'id' => $candidate_id,
                'candidate_name' => $candidate_name,
                'email' => $email,
                'job_title' => $position_row['job_title'] ?? 'Unknown Position',
                'rating' => $rating,
                'stage' => $stage,
                'resume_uploaded' => !is_null($resume_path)
            ]
        ]);
        
    } else {
        throw new Exception('Invalid request method');
    }
    
} catch (Exception $e) {
    error_log('Save candidate error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>