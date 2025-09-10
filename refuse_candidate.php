<?php
require('database.php');
require('session.php');

header('Content-Type: application/json');

if ($_SESSION['role'] != 'Employer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $candidate_id = $_POST['candidate_id'] ?? '';
    $refuse_reason = $_POST['refuse_reason'] ?? '';
    $send_email = $_POST['send_email'] ?? '0';
    
    if (empty($candidate_id) || empty($refuse_reason)) {
        echo json_encode(['success' => false, 'message' => 'Candidate ID and reason are required']);
        exit();
    }
    
    try {
        // Update candidate stage to 'Refuse' and add refuse reason
        $update_query = "UPDATE job_applications SET stage = 'Refuse', refuse_reason = ?, refused_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $refuse_reason, $candidate_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error refusing candidate: ' . mysqli_error($conn));
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
                } else {
                    $response['email_sent'] = false;
                    $response['message'] .= ' (Email notification failed)';
                }
            }
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>