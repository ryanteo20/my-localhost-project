<?php
// filepath: /Applications/XAMPP/xamppfiles/htdocs/save_candidate.php
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
    // Get form data
    $candidate_name = $_POST['candidate_name'] ?? null;
    $email = $_POST['email'] ?? null;
    $position_id = $_POST['position_id'] ?? null;
    $rating = $_POST['rating'] ?? 0;
    $stage = $_POST['stage'] ?? 'New';
    $notes = $_POST['notes'] ?? '';

    // Validate required fields
    if (!$candidate_name || !$email || !$position_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Check if email already exists
    $check_query = "SELECT id FROM job_applications WHERE email = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $email);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'A candidate with this email already exists']);
        exit;
    }

    // Handle resume upload if provided
    $resume_uploaded = false;
    $resume_path = null;
    
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/resumes/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_info = $_FILES['resume'];
        $file_extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        $allowed_extensions = ['pdf', 'doc', 'docx'];
        if (!in_array($file_extension, $allowed_extensions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, and DOCX files are allowed.']);
            exit;
        }
        
        // Validate file size (5MB max)
        if ($file_info['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size too large. Maximum size is 5MB.']);
            exit;
        }
        
        // Generate unique filename
        $filename = 'resume_' . time() . '_' . uniqid() . '.' . $file_extension;
        $resume_path = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file_info['tmp_name'], $resume_path)) {
            $resume_uploaded = true;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload resume file']);
            exit;
        }
    }
    
    // Insert new candidate
    $insert_query = "INSERT INTO job_applications (candidate_name, email, position_id, stage, rating, notes, resume_path, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "ssissss", $candidate_name, $email, $position_id, $stage, $rating, $notes, $resume_path);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        $candidate_id = mysqli_insert_id($conn);
        
        // Update resume filename with candidate ID if resume was uploaded
        if ($resume_uploaded && $candidate_id) {
            $new_filename = 'resume_' . $candidate_id . '_' . time() . '.' . $file_extension;
            $new_resume_path = $upload_dir . $new_filename;
            
            if (rename($resume_path, $new_resume_path)) {
                // Update the database with the new path
                $update_path_query = "UPDATE job_applications SET resume_path = ? WHERE id = ?";
                $update_path_stmt = mysqli_prepare($conn, $update_path_query);
                mysqli_stmt_bind_param($update_path_stmt, "si", $new_resume_path, $candidate_id);
                mysqli_stmt_execute($update_path_stmt);
                mysqli_stmt_close($update_path_stmt);
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Candidate added successfully',
            'candidate_id' => $candidate_id,
            'candidate' => [
                'resume_uploaded' => $resume_uploaded
            ]
        ]);
    } else {
        // If insert failed and we uploaded a file, clean it up
        if ($resume_uploaded && file_exists($resume_path)) {
            unlink($resume_path);
        }
        
        throw new Exception('Database insert failed: ' . mysqli_stmt_error($insert_stmt));
    }
    
    mysqli_stmt_close($insert_stmt);
    
} catch (Exception $e) {
    // Clean up uploaded file if there was an error
    if (isset($resume_path) && $resume_path && file_exists($resume_path)) {
        unlink($resume_path);
    }
    
    error_log("Save Candidate Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>