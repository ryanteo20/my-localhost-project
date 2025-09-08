<?php
/**
 * Automated Attendance Check Script
 * This script should be run as a cron job to check for attendance violations
 * 
 * Recommended cron schedule:
 * - 9:15 AM: Check for employees who haven't clocked in
 * - 6:15 PM: Check for employees who haven't clocked out
 * - 9:30 AM: Final reminder for late clock-ins
 * 
 * Example cron entries:
 * 15 9 * * 1-5 php /path/to/cron_attendance_check.php check_missing_clockin
 * 15 18 * * 1-5 php /path/to/cron_attendance_check.php check_missing_clockout  
 * 30 9 * * 1-5 php /path/to/cron_attendance_check.php final_clockin_reminder
 */

require_once('database.php');
require_once('send_notification.php');

date_default_timezone_set('Asia/Kuala_Lumpur');
$date = date('Y-m-d');
$current_time = date('H:i:s');

// Function to insert notification into the database
function insertNotification($employee_id, $employer_id, $message) {
    global $con;
    $query = "INSERT INTO notifications (employee_id, employer_id, message, status, created_at) VALUES (?, ?, ?, 'unread', NOW())";
    $stmt = $con->prepare($query);
    $stmt->bind_param("iis", $employee_id, $employer_id, $message);
    return $stmt->execute();
}

// Function to check if notification already exists today
function notificationExistsToday($employee_id, $employer_id, $message_pattern) {
    global $con, $date;
    $query = "SELECT ID FROM notifications 
              WHERE employee_id = ? AND employer_id = ? 
              AND DATE(created_at) = ? 
              AND message LIKE ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("iiss", $employee_id, $employer_id, $date, $message_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to get employer details
function getEmployerDetails($employer_id) {
    global $con;
    $query = "SELECT email, phone FROM employers WHERE ID = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $employer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Check for employees who haven't clocked in by 9:15 AM
function checkMissingClockIn() {
    global $con, $date;
    
    echo "Checking for missing clock-ins...\n";
    
    $query = "
        SELECT el.ID, el.username, el.employer_id, el.email as employee_email
        FROM employeelogin el 
        LEFT JOIN attendance a ON el.ID = a.employee_id AND a.date = ?
        WHERE (a.clock_in IS NULL OR a.clock_in = '') 
        AND el.status = 'active'
        AND el.role = 'employee'
    ";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications_sent = 0;
    
    while ($row = $result->fetch_assoc()) {
        $message_pattern = "%has not clocked in%";
        
        // Check if notification already sent today
        if (!notificationExistsToday($row['ID'], $row['employer_id'], $message_pattern)) {
            $notification_message = $row['username'] . " has not clocked in today (as of " . date('g:i A') . ")";
            
            // Insert system notification
            if (insertNotification($row['ID'], $row['employer_id'], $notification_message)) {
                echo "System notification created for employee: " . $row['username'] . "\n";
                
                // Get employer details and send email
                $employer = getEmployerDetails($row['employer_id']);
                if ($employer && $employer['email']) {
                    if (sendNotification($employer['email'], $row['username'], $notification_message)) {
                        echo "Email sent to employer for employee: " . $row['username'] . "\n";
                        $notifications_sent++;
                    }
                }
            }
        }
    }
    
    echo "Missing clock-in check completed. Notifications sent: $notifications_sent\n";
}

// Check for employees who clocked out before 6:00 PM
function checkEarlyClockOut() {
    global $con, $date;
    
    echo "Checking for early clock-outs...\n";
    
    $query = "
        SELECT a.*, el.username, el.employer_id
        FROM attendance a 
        JOIN employeelogin el ON a.employee_id = el.ID
        WHERE a.date = ? 
        AND a.clock_out IS NOT NULL 
        AND a.clock_out != ''
        AND TIME(a.clock_out) < '18:00:00'
        AND el.status = 'active'
    ";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications_sent = 0;
    
    while ($row = $result->fetch_assoc()) {
        $message_pattern = "%clocked out early%";
        
        // Check if notification already sent today
        if (!notificationExistsToday($row['employee_id'], $row['employer_id'], $message_pattern)) {
            $clock_out_time = strtotime($row['clock_out']);
            $early_minutes = round((strtotime($date . ' 18:00:00') - $clock_out_time) / 60);
            
            $notification_message = $row['username'] . " clocked out early at " . 
                                  date('g:i A', $clock_out_time) . 
                                  " (${early_minutes} minutes before 6:00 PM)";
            
            // Insert system notification
            if (insertNotification($row['employee_id'], $row['employer_id'], $notification_message)) {
                echo "System notification created for employee: " . $row['username'] . "\n";
                
                // Get employer details and send email
                $employer = getEmployerDetails($row['employer_id']);
                if ($employer && $employer['email']) {
                    if (sendNotification($employer['email'], $row['username'], $notification_message)) {
                        echo "Email sent to employer for employee: " . $row['username'] . "\n";
                        $notifications_sent++;
                    }
                }
            }
        }
    }
    
    echo "Early clock-out check completed. Notifications sent: $notifications_sent\n";
}

// Check for employees who still haven't clocked out by 6:15 PM
function checkMissingClockOut() {
    global $con, $date;
    
    echo "Checking for missing clock-outs...\n";
    
    $query = "
        SELECT a.*, el.username, el.employer_id
        FROM attendance a 
        JOIN employeelogin el ON a.employee_id = el.ID
        WHERE a.date = ? 
        AND a.clock_in IS NOT NULL 
        AND a.clock_in != ''
        AND (a.clock_out IS NULL OR a.clock_out = '')
        AND el.status = 'active'
    ";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications_sent = 0;
    
    while ($row = $result->fetch_assoc()) {
        $message_pattern = "%has not clocked out%";
        
        // Check if notification already sent today
        if (!notificationExistsToday($row['employee_id'], $row['employer_id'], $message_pattern)) {
            $notification_message = $row['username'] . " has not clocked out today (as of " . date('g:i A') . ")";
            
            // Insert system notification
            if (insertNotification($row['employee_id'], $row['employer_id'], $notification_message)) {
                echo "System notification created for employee: " . $row['username'] . "\n";
                
                // Get employer details and send email
                $employer = getEmployerDetails($row['employer_id']);
                if ($employer && $employer['email']) {
                    if (sendNotification($employer['email'], $row['username'], $notification_message)) {
                        echo "Email sent to employer for employee: " . $row['username'] . "\n";
                        $notifications_sent++;
                    }
                }
            }
        }
    }
    
    echo "Missing clock-out check completed. Notifications sent: $notifications_sent\n";
}

// Daily summary report
function sendDailySummary() {
    global $con, $date;
    
    echo "Generating daily attendance summary...\n";
    
    // Get all employers
    $employer_query = "SELECT DISTINCT ID, email FROM employers WHERE status = 'active'";
    $employer_result = $con->query($employer_query);
    
    while ($employer = $employer_result->fetch_assoc()) {
        // Get attendance summary for this employer's employees
        $summary_query = "
            SELECT 
                el.username,
                a.clock_in,
                a.clock_out,
                CASE 
                    WHEN a.clock_in IS NULL THEN 'Absent'
                    WHEN TIME(a.clock_in) > '09:00:00' THEN 'Late'
                    WHEN a.clock_out IS NULL THEN 'Missing Clock-out'
                    WHEN TIME(a.clock_out) < '18:00:00' THEN 'Early Departure'
                    ELSE 'On Time'
                END as status
            FROM employeelogin el 
            LEFT JOIN attendance a ON el.ID = a.employee_id AND a.date = ?
            WHERE el.employer_id = ? AND el.status = 'active' AND el.role = 'employee'
            ORDER BY el.username
        ";
        
        $stmt = $con->prepare($summary_query);
        $stmt->bind_param("si", $date, $employer['ID']);
        $stmt->execute();
        $summary_result = $stmt->get_result();
        
        $summary_data = [];
        while ($row = $summary_result->fetch_assoc()) {
            $summary_data[] = $row;
        }
        
        // Send summary email if there are employees
        if (!empty($summary_data)) {
            sendDailySummaryEmail($employer['email'], $summary_data);
        }
    }
    
    echo "Daily summary reports sent.\n";
}

function sendDailySummaryEmail($employer_email, $summary_data) {
    $subject = "Daily Attendance Summary - " . date('Y-m-d');
    
    $email_body = "<html><head><title>Daily Attendance Summary</title></head><body>";
    $email_body .= "<h2>Daily Attendance Summary for " . date('Y-m-d') . "</h2>";
    $email_body .= "<table border='1' cellpadding='5' cellspacing='0'>";
    $email_body .= "<tr><th>Employee</th><th>Clock In</th><th>Clock Out</th><th>Status</th></tr>";
    
    foreach ($summary_data as $row) {
        $email_body .= "<tr>";
        $email_body .= "<td>" . htmlspecialchars($row['username']) . "</td>";
        $email_body .= "<td>" . ($row['clock_in'] ? date('g:i A', strtotime($row['clock_in'])) : 'N/A') . "</td>";
        $email_body .= "<td>" . ($row['clock_out'] ? date('g:i A', strtotime($row['clock_out'])) : 'N/A') . "</td>";
        $email_body .= "<td>" . $row['status'] . "</td>";
        $email_body .= "</tr>";
    }
    
    $email_body .= "</table></body></html>";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: SMEasyHR System <no-reply@smeasyhr.com>\r\n";
    
    mail($employer_email, $subject, $email_body, $headers);
}

// Main execution
if ($argc > 1) {
    $command = $argv[1];
    
    switch ($command) {
        case 'check_missing_clockin':
            checkMissingClockIn();
            break;
        case 'check_missing_clockout':
            checkMissingClockOut();
            break;
        case 'check_early_clockout':
            checkEarlyClockOut();
            break;
        case 'daily_summary':
            sendDailySummary();
            break;
        case 'all_checks':
            checkMissingClockIn();
            checkEarlyClockOut();
            checkMissingClockOut();
            break;
        default:
            echo "Usage: php cron_attendance_check.php [check_missing_clockin|check_missing_clockout|check_early_clockout|daily_summary|all_checks]\n";
            break;
    }
} else {
    echo "Usage: php cron_attendance_check.php [check_missing_clockin|check_missing_clockout|check_early_clockout|daily_summary|all_checks]\n";
}
?>