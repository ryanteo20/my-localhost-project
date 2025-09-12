<?php
function sendNotification($employer_email, $employee_name, $message) {
    $subject = "SMEasyHR - Attendance Alert for $employee_name";
    
    // Create a more detailed email message
    $email_body = "
    <html>
    <head>
        <title>Attendance Alert</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 5px; }
            .content { padding: 20px; }
            .alert { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>SMEasyHR - Attendance Alert</h2>
            </div>
            <div class='content'>
                <p>Dear Employer,</p>
                
                <div class='alert'>
                    <strong>Attendance Issue Detected:</strong><br>
                    $message
                </div>
                
                <p><strong>Employee:</strong> $employee_name</p>
                <p><strong>Date:</strong> " . date('Y-m-d') . "</p>
                <p><strong>Time of Alert:</strong> " . date('Y-m-d H:i:s') . "</p>
                
                <p>Please review the employee's attendance and take appropriate action if necessary.</p>
                
                <p>You can view detailed attendance reports by logging into your SMEasyHR dashboard.</p>
                
                <p>Best regards,<br>
                SMEasyHR System</p>
            </div>
            <div class='footer'>
                <p>This is an automated message from SMEasyHR. Please do not reply to this email.</p>
                <p>If you have any questions, please contact your system administrator.</p>
            </div>
        </div>
    </body>
    </html>";
    
    // Set email headers for HTML email
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: SMEasyHR System <no-reply@smeasyhr.com>\r\n";
    $headers .= "Reply-To: no-reply@smeasyhr.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Send the email
    $result = mail($employer_email, $subject, $email_body, $headers);
    
    // Log the email attempt
    error_log("Email notification sent to $employer_email for employee $employee_name: " . ($result ? "SUCCESS" : "FAILED"));
    
    return $result;
}

// Alternative function for sending SMS notifications (requires SMS service integration)
function sendSMSNotification($phone_number, $employee_name, $message) {
    // This would require integration with an SMS service like Twilio
    // For now, just log the SMS attempt
    error_log("SMS notification would be sent to $phone_number: Employee $employee_name - $message");
    
    // Return true for demonstration purposes
    return true;
}

// Function to send push notifications (requires push notification service)
function sendPushNotification($user_id, $title, $message) {
    // This would require integration with a push notification service
    // For now, just log the push notification attempt
    error_log("Push notification would be sent to user $user_id: $title - $message");
    
    // Return true for demonstration purposes
    return true;
}
?>