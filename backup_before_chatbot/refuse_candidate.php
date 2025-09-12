<?php
require('database.php');
require('session.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Log the incoming request for debugging
error_log("Refuse candidate request received: " . print_r($_POST, true));

if ($_SESSION['role'] != 'Employer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $candidate_id = $_POST['candidate_id'] ?? '';
    $refuse_reason = $_POST['refuse_reason'] ?? '';
    $send_email = $_POST['send_email'] ?? '0';
    
    error_log("Processing refuse for candidate ID: $candidate_id, reason: $refuse_reason");
    
    if (empty($candidate_id) || empty($refuse_reason)) {
        $error = 'Candidate ID and reason are required. Received: ID=' . $candidate_id . ', Reason=' . $refuse_reason;
        error_log($error);
        echo json_encode(['success' => false, 'message' => $error]);
        exit();
    }
    
    try {
        // First, check if the required columns exist
        $check_columns_query = "SHOW COLUMNS FROM job_applications LIKE 'refuse_reason'";
        $column_result = mysqli_query($conn, $check_columns_query);
        
        if (mysqli_num_rows($column_result) == 0) {
            // Add the missing columns
            $alter_query1 = "ALTER TABLE job_applications ADD COLUMN refuse_reason TEXT DEFAULT NULL";
            $alter_query2 = "ALTER TABLE job_applications ADD COLUMN refused_at TIMESTAMP NULL DEFAULT NULL";
            
            if (!mysqli_query($conn, $alter_query1)) {
                throw new Exception('Error adding refuse_reason column: ' . mysqli_error($conn));
            }
            
            if (!mysqli_query($conn, $alter_query2)) {
                throw new Exception('Error adding refused_at column: ' . mysqli_error($conn));
            }
            
            error_log("Added missing columns to job_applications table");
        }
        
        // Update candidate stage to 'Refuse' and add refuse reason
        $update_query = "UPDATE job_applications SET stage = 'Refuse', refuse_reason = ?, refused_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        
        if (!$stmt) {
            throw new Exception('Error preparing statement: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "si", $refuse_reason, $candidate_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error executing statement: ' . mysqli_stmt_error($stmt));
        }
        
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        error_log("Updated $affected_rows rows for candidate ID: $candidate_id");
        
        if ($affected_rows == 0) {
            throw new Exception('No candidate found with ID: ' . $candidate_id);
        }
        
        $response = ['success' => true, 'message' => 'Candidate refused successfully'];
        
        // Send email notification if requested
        if ($send_email == '1') {
            // Get candidate details
            $candidate_query = "SELECT ja.candidate_name, ja.email, jp.job_title 
                               FROM job_applications ja 
                               LEFT JOIN job_positions jp ON ja.position_id = jp.id 
                               WHERE ja.id = ?";
            $stmt = mysqli_prepare($conn, $candidate_query);
            
            if (!$stmt) {
                error_log('Error preparing candidate query: ' . mysqli_error($conn));
            } else {
                mysqli_stmt_bind_param($stmt, "i", $candidate_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $candidate = mysqli_fetch_assoc($result);
                
                if ($candidate) {
                    // Send email (you might want to use a proper email library)
                    $to = $candidate['email'];
                    $subject = "Application Status Update - " . $candidate['job_title'];
                    $message = "Dear " . $candidate['candidate_name'] . ",\n\n";
                    $message .= "Thank you for your interest in the " . $candidate['job_title'] . " position.\n\n";
                    $message .= "After careful consideration, we have decided not to move forward with your application at this time.\n\n";
                    $message .= "Reason: " . $refuse_reason . "\n\n";
                    $message .= "We appreciate the time you invested in the application process and wish you the best in your job search.\n\n";
                    $message .= "Best regards,\nSMEasyHR Team";
                    
                    $headers = "From: noreply@smeasyhr.com\r\n";
                    $headers .= "Reply-To: noreply@smeasyhr.com\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion();
                    
                    if (mail($to, $subject, $message, $headers)) {
                        $response['email_sent'] = true;
                        error_log("Email sent successfully to: $to");
                    } else {
                        $response['email_sent'] = false;
                        $response['message'] .= ' (Email notification failed)';
                        error_log("Failed to send email to: $to");
                    }
                } else {
                    error_log("No candidate found for email sending with ID: $candidate_id");
                }
            }
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log("Exception in refuse_candidate.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Expected POST, got: ' . $_SERVER['REQUEST_METHOD']]);
}
?>