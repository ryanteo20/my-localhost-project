<?php
// filepath: /Applications/XAMPP/xamppfiles/htdocs/update_candidate.php
require('database.php');
require('session.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

// Set the content type to JSON
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Debug: Log received data
    error_log("POST data received: " . print_r($_POST, true));
    error_log("FILES data received: " . print_r($_FILES, true));

    // Get form data (not JSON since we're using FormData)
    $candidate_id = $_POST['candidate_id'] ?? null;
    $candidate_name = $_POST['candidate_name'] ?? null;
    $email = $_POST['email'] ?? null;
    $position_id = $_POST['position_id'] ?? null;
    $notes = $_POST['notes'] ?? '';
    $rating = $_POST['rating'] ?? 0;

    // Debug: Log parsed values
    error_log("Parsed values - ID: $candidate_id, Name: $candidate_name, Email: $email, Position: $position_id");

    // Validate required fields
    if (!$candidate_id || !$candidate_name || !$email || !$position_id) {
        $missing_fields = [];
        if (!$candidate_id) $missing_fields[] = 'candidate_id';
        if (!$candidate_name) $missing_fields[] = 'candidate_name';
        if (!$email) $missing_fields[] = 'email';
        if (!$position_id) $missing_fields[] = 'position_id';
        
        echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing_fields)]);
        exit;
    }

    // Validate candidate exists
    $check_query = "SELECT id FROM job_applications WHERE id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    
    if (!$check_stmt) {
        throw new Exception('Failed to prepare check statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($check_stmt, "i", $candidate_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Candidate not found']);
        exit;
    }
    
    mysqli_stmt_close($check_stmt);

    // Handle resume upload if provided
    $resume_uploaded = false;
    $resume_path = null;
    
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/resumes/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception('Failed to create upload directory');
            }
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
        $filename = 'resume_' . $candidate_id . '_' . time() . '.' . $file_extension;
        $resume_path = $upload_dir . $filename;
        
        // Delete old resume if exists
        $old_resume_query = "SELECT resume_path FROM job_applications WHERE id = ?";
        $old_resume_stmt = mysqli_prepare($conn, $old_resume_query);
        
        if (!$old_resume_stmt) {
            throw new Exception('Failed to prepare old resume statement: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($old_resume_stmt, "i", $candidate_id);
        mysqli_stmt_execute($old_resume_stmt);
        $old_resume_result = mysqli_stmt_get_result($old_resume_stmt);
        
        if ($old_row = mysqli_fetch_assoc($old_resume_result)) {
            if ($old_row['resume_path'] && file_exists($old_row['resume_path'])) {
                unlink($old_row['resume_path']);
            }
        }
        
        mysqli_stmt_close($old_resume_stmt);
        
        // Move uploaded file
        if (move_uploaded_file($file_info['tmp_name'], $resume_path)) {
            $resume_uploaded = true;
        } else {
            throw new Exception('Failed to move uploaded file');
        }
    }
    
    // Ensure rating is an integer
    $rating = (int)$rating;
    
    // Update candidate information
    if ($resume_uploaded) {
        $update_query = "UPDATE job_applications SET 
                        candidate_name = ?, 
                        email = ?, 
                        position_id = ?, 
                        notes = ?, 
                        rating = ?,
                        resume_path = ?,
                        updated_at = NOW() 
                        WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        
        if (!$update_stmt) {
            throw new Exception('Failed to prepare update statement: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($update_stmt, "ssisssi", $candidate_name, $email, $position_id, $notes, $rating, $resume_path, $candidate_id);
    } else {
        $update_query = "UPDATE job_applications SET 
                        candidate_name = ?, 
                        email = ?, 
                        position_id = ?, 
                        notes = ?, 
                        rating = ?,
                        updated_at = NOW() 
                        WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        
        if (!$update_stmt) {
            throw new Exception('Failed to prepare update statement: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($update_stmt, "ssissi", $candidate_name, $email, $position_id, $notes, $rating, $candidate_id);
    }
    
    if (mysqli_stmt_execute($update_stmt)) {
        if (mysqli_stmt_affected_rows($update_stmt) > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Candidate updated successfully',
                'resume_uploaded' => $resume_uploaded
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes were made']);
        }
    } else {
        throw new Exception('Database update failed: ' . mysqli_stmt_error($update_stmt));
    }
    
    mysqli_stmt_close($update_stmt);
    
} catch (Exception $e) {
    error_log("Update Candidate Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn) {
        mysqli_close($conn);
    }
}
?>