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
    // Handle both JSON and form data
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = null;
        $resume_file = null;
        
        // Check if it's a file upload (multipart/form-data)
        if (isset($_FILES['resume']) && !empty($_POST['candidate_id'])) {
            // Handle file upload
            $input = $_POST;
            $resume_file = $_FILES['resume'];
        } else {
            // Handle JSON data
            $json = file_get_contents('php://input');
            $input = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data');
            }
        }

        $candidate_id = $input['candidate_id'] ?? null;
        $candidate_name = $input['candidate_name'] ?? null;
        $email = $input['email'] ?? null;
        $position_id = $input['position_id'] ?? null;
        $notes = $input['notes'] ?? '';
        $rating = $input['rating'] ?? 0;

        // Validate required fields
        if (!$candidate_id || !$candidate_name || !$email || !$position_id) {
            throw new Exception('Missing required fields');
        }

        // Start building the update query
        $update_fields = [];
        $params = [];
        $types = '';

        // Always update these basic fields
        $update_fields[] = "candidate_name = ?";
        $params[] = $candidate_name;
        $types .= 's';

        $update_fields[] = "email = ?";
        $params[] = $email;
        $types .= 's';

        $update_fields[] = "position_id = ?";
        $params[] = $position_id;
        $types .= 'i';

        $update_fields[] = "notes = ?";
        $params[] = $notes;
        $types .= 's';

        $update_fields[] = "rating = ?";
        $params[] = $rating;
        $types .= 'i';

        // Handle resume upload if present
        $resume_path = null;
        if ($resume_file && $resume_file['error'] === UPLOAD_ERR_OK) {
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

            // Clean candidate name for filename (remove special characters)
            $clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $candidate_name);
            $clean_name = preg_replace('/_+/', '_', $clean_name); // Replace multiple underscores with single
            $clean_name = trim($clean_name, '_'); // Remove leading/trailing underscores
            
            // Get file extension
            $file_extension = pathinfo($resume_file['name'], PATHINFO_EXTENSION);
            
            // Generate filename with candidate name and timestamp
            $filename = $clean_name . '_Resume_' . time() . '.' . $file_extension;
            $resume_path = $upload_dir . $filename;

            // Delete old resume file if it exists
            $old_resume_query = "SELECT resume_path FROM job_applications WHERE id = ?";
            $old_stmt = mysqli_prepare($conn, $old_resume_query);
            if ($old_stmt) {
                mysqli_stmt_bind_param($old_stmt, 'i', $candidate_id);
                mysqli_stmt_execute($old_stmt);
                $old_result = mysqli_stmt_get_result($old_stmt);
                if ($old_row = mysqli_fetch_assoc($old_result)) {
                    $old_resume_path = $old_row['resume_path'];
                    if (!empty($old_resume_path) && file_exists($old_resume_path)) {
                        unlink($old_resume_path); // Delete old file
                    }
                }
                mysqli_stmt_close($old_stmt);
            }

            if (!move_uploaded_file($resume_file['tmp_name'], $resume_path)) {
                throw new Exception('Failed to upload resume file');
            }

            // Add resume path to update fields
            $update_fields[] = "resume_path = ?";
            $params[] = $resume_path;
            $types .= 's';
        }

        // Add candidate_id for WHERE clause
        $params[] = $candidate_id;
        $types .= 'i';

        // Build and execute the update query
        $sql = "UPDATE job_applications SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception('Database prepare error: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Database execute error: ' . mysqli_stmt_error($stmt));
        }

        $affected_rows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Candidate updated successfully',
                'resume_uploaded' => !is_null($resume_path),
                'resume_path' => $resume_path,
                'filename' => isset($filename) ? $filename : null
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or candidate not found']);
        }
    } else {
        throw new Exception('Invalid request method');
    }

} catch (Exception $e) {
    error_log('Update candidate error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>