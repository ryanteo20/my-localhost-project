<?php
/**
 * Improved Attendance Monitoring Script
 * This script ensures all absent employees are tracked and notifications are sent
 * 
 * Usage: php attendance_monitor.php [action]
 * Actions: mark_absent, check_late, check_early_out, missing_clockout, daily_report
 */

require_once('database.php');
require_once('send_notification.php');

// Set timezone and get current date/time
date_default_timezone_set('Asia/Kuala_Lumpur');
$date = date('Y-m-d');
$current_time = date('H:i:s');
$datetime_now = date('Y-m-d H:i:s');

// Log function
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
    error_log("[$timestamp] Attendance Monitor: $message");
}

// Function to insert notification into the database
function insertNotification($employee_id, $employer_id, $message) {
    global $conn;
    
    $query = "INSERT INTO notifications (employee_id, employer_id, message, status, created_at) 
              VALUES (?, ?, ?, 'unread', NOW())";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        logMessage("Error preparing notification query: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("iis", $employee_id, $employer_id, $message);
    $result = $stmt->execute();
    
    if (!$result) {
        logMessage("Error inserting notification: " . $stmt->error);
        return false;
    }
    
    logMessage("Notification inserted for employee $employee_id to employer $employer_id");
    return true;
}

// Function to check if notification already exists today
function notificationExistsToday($employee_id, $employer_id, $message_pattern) {
    global $conn, $date;
    
    $query = "SELECT ID FROM notifications 
              WHERE employee_id = ? AND employer_id = ? 
              AND DATE(created_at) = ? 
              AND message LIKE ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiss", $employee_id, $employer_id, $date, $message_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to get employer email
function getEmployerEmail($employer_id) {
    global $conn;
    
    $query = "SELECT email FROM employeelogin WHERE ID = ? AND role IN ('employer', 'admin')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row ? $row['email'] : null;
}

// MAIN FUNCTION: Mark employees as absent and send notifications
function markAbsentEmployees() {
    global $conn, $date;
    
    logMessage("Starting absent employee check for $date");
    
    // Get all active employees who haven't clocked in today
    $query = "SELECT el.ID, el.username, el.employer_id
              FROM employeelogin el
              LEFT JOIN attendance a ON el.ID = a.employee_id AND a.date = ?
              WHERE el.role = 'employee' 
              AND el.status = 'active'
              AND a.employee_id IS NULL";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $absent_count = 0;
    $notification_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $employee_id = $row['ID'];
        $employee_name = $row['username'];
        $employer_id = $row['employer_id'];
        
        logMessage("Processing absent employee: $employee_name (ID: $employee_id)");
        
        // Create absent attendance record
        $insertQuery = "INSERT INTO attendance (employee_id, date, status, created_at) 
                       VALUES (?, ?, 'absent', NOW())
                       ON DUPLICATE KEY UPDATE status = 'absent'";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("is", $employee_id, $date);
        
        if ($insertStmt->execute()) {
            $absent_count++;
            logMessage("Marked $employee_name as absent in attendance table");
            
            // Send notification if employer exists
            if ($employer_id) {
                $employer_email = getEmployerEmail($employer_id);
                
                if ($employer_email) {
                    $message_pattern = "%marked as ABSENT%";
                    
                    // Check if we already sent this notification today
                    if (!notificationExistsToday($employee_id, $employer_id, $message_pattern)) {
                        $notification_message = "$employee_name has not clocked in today and is marked as ABSENT (as of " . date('g:i A') . ")";
                        
                        // Insert system notification
                        if (insertNotification($employee_id, $employer_id, $notification_message)) {
                            // Send email notification
                            if (sendNotification($employer_email, $employee_name, $notification_message)) {
                                $notification_count++;
                                logMessage("Email notification sent to $employer_email for absent employee $employee_name");
                            } else {
                                logMessage("Failed to send email to $employer_email for $employee_name");
                            }
                        }
                    } else {
                        logMessage("Notification already sent today for absent employee $employee_name");
                    }
                } else {
                    logMessage("No employer email found for employer ID: $employer_id");
                }
            } else {
                logMessage("No employer assigned to employee $employee_name");
            }
        } else {
            logMessage("Failed to mark $employee_name as absent: " . $insertStmt->error);
        }
    }
    
    logMessage("Absent employee check completed. Marked $absent_count as absent, sent $notification_count notifications");
}

// Function to check for late clock-ins
function checkLateClockIns() {
    global $conn, $date;
    
    logMessage("Checking for late clock-ins for $date");
    
    $query = "SELECT a.*, el.username, el.employer_id
              FROM attendance a 
              JOIN employeelogin el ON a.employee_id = el.ID
              WHERE a.date = ? 
              AND a.clock_in IS NOT NULL 
              AND TIME(a.clock_in) > '09:00:00'
              AND el.status = 'active'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $late_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $message_pattern = "%clocked in late%";
        
        if (!notificationExistsToday($row['employee_id'], $row['employer_id'], $message_pattern)) {
            $clock_in_time = strtotime($row['clock_in']);
            $cutoff_time = strtotime($date . ' 09:00:00');
            $late_minutes = round(($clock_in_time - $cutoff_time) / 60);
            
            $notification_message = $row['username'] . " clocked in late at " . 
                                  date('g:i A', $clock_in_time) . 
                                  " (${late_minutes} minutes after 9:00 AM)";
            
            $employer_email = getEmployerEmail($row['employer_id']);
            
            if ($employer_email) {
                insertNotification($row['employee_id'], $row['employer_id'], $notification_message);
                sendNotification($employer_email, $row['username'], $notification_message);
                $late_count++;
                logMessage("Late clock-in notification sent for " . $row['username']);
            }
        }
    }
    
    logMessage("Late clock-in check completed. Processed $late_count late employees");
}

// Function to check for early clock-outs
function checkEarlyClockOuts() {
    global $conn, $date;
    
    logMessage("Checking for early clock-outs for $date");
    
    $query = "SELECT a.*, el.username, el.employer_id
              FROM attendance a 
              JOIN employeelogin el ON a.employee_id = el.ID
              WHERE a.date = ? 
              AND a.clock_out IS NOT NULL 
              AND TIME(a.clock_out) < '18:00:00'
              AND el.status = 'active'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $early_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $message_pattern = "%clocked out early%";
        
        if (!notificationExistsToday($row['employee_id'], $row['employer_id'], $message_pattern)) {
            $clock_out_time = strtotime($row['clock_out']);
            $cutoff_time = strtotime($date . ' 18:00:00');
            $early_minutes = round(($cutoff_time - $clock_out_time) / 60);
            
            $notification_message = $row['username'] . " clocked out early at " . 
                                  date('g:i A', $clock_out_time) . 
                                  " (${early_minutes} minutes before 6:00 PM)";
            
            $employer_email = getEmployerEmail($row['employer_id']);
            
            if ($employer_email) {
                insertNotification($row['employee_id'], $row['employer_id'], $notification_message);
                sendNotification($employer_email, $row['username'], $notification_message);
                $early_count++;
                logMessage("Early clock-out notification sent for " . $row['username']);
            }
        }
    }
    
    logMessage("Early clock-out check completed. Processed $early_count early departures");
}

// Function to check for missing clock-outs
function checkMissingClockOuts() {
    global $conn, $date;
    
    logMessage("Checking for missing clock-outs for $date");
    
    $query = "SELECT a.*, el.username, el.employer_id
              FROM attendance a 
              JOIN employeelogin el ON a.employee_id = el.ID
              WHERE a.date = ? 
              AND a.clock_in IS NOT NULL 
              AND (a.clock_out IS NULL OR a.clock_out = '')
              AND el.status = 'active'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $missing_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $message_pattern = "%has not clocked out%";
        
        if (!notificationExistsToday($row['employee_id'], $row['employer_id'], $message_pattern)) {
            $notification_message = $row['username'] . " has not clocked out today (clocked in at " . 
                                  date('g:i A', strtotime($row['clock_in'])) . ")";
            
            $employer_email = getEmployerEmail($row['employer_id']);
            
            if ($employer_email) {
                insertNotification($row['employee_id'], $row['employer_id'], $notification_message);
                sendNotification($employer_email, $row['username'], $notification_message);
                $missing_count++;
                logMessage("Missing clock-out notification sent for " . $row['username']);
            }
        }
    }
    
    logMessage("Missing clock-out check completed. Processed $missing_count missing clock-outs");
}

// Function to generate daily attendance report
function generateDailyReport() {
    global $conn, $date;
    
    logMessage("Generating daily attendance report for $date");
    
    // Get summary statistics
    $stats_query = "SELECT 
                      COUNT(DISTINCT el.ID) as total_employees,
                      COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.employee_id END) as present_count,
                      COUNT(DISTINCT CASE WHEN a.status = 'absent' THEN a.employee_id END) as absent_count,
                      COUNT(DISTINCT CASE WHEN TIME(a.clock_in) > '09:00:00' THEN a.employee_id END) as late_count,
                      COUNT(DISTINCT CASE WHEN TIME(a.clock_out) < '18:00:00' AND a.clock_out IS NOT NULL THEN a.employee_id END) as early_count
                    FROM employeelogin el
                    LEFT JOIN attendance a ON el.ID = a.employee_id AND a.date = ?
                    WHERE el.role = 'employee' AND el.status = 'active'";
    
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    
    logMessage("Daily Statistics:");
    logMessage("- Total Active Employees: " . $stats['total_employees']);
    logMessage("- Present: " . $stats['present_count']);
    logMessage("- Absent: " . $stats['absent_count']);
    logMessage("- Late Clock-ins: " . $stats['late_count']);
    logMessage("- Early Clock-outs: " . $stats['early_count']);
    
    // Send summary to each employer
    $employers_query = "SELECT DISTINCT ID, email, username as company_name 
                       FROM employeelogin 
                       WHERE role IN ('employer', 'admin') AND status = 'active'";
    $employers_result = $conn->query($employers_query);
    
    while ($employer = $employers_result->fetch_assoc()) {
        sendDailyReportToEmployer($employer, $stats);
    }
}

// Function to send daily report email
function sendDailyReportToEmployer($employer, $stats) {
    global $conn, $date;
    
    $subject = "Daily Attendance Report - " . date('F j, Y', strtotime($date));
    
    $email_body = "
    <html>
    <head><title>Daily Attendance Report</title></head>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Daily Attendance Summary</h2>
        <p><strong>Date:</strong> " . date('F j, Y', strtotime($date)) . "</p>
        
        <h3>Overview</h3>
        <ul>
            <li>Total Employees: {$stats['total_employees']}</li>
            <li>Present: {$stats['present_count']}</li>
            <li>Absent: {$stats['absent_count']}</li>
            <li>Late Arrivals: {$stats['late_count']}</li>
            <li>Early Departures: {$stats['early_count']}</li>
        </ul>
        
        <p>For detailed reports, please log into your SMEasyHR dashboard.</p>
        
        <hr>
        <p><small>This is an automated report from SMEasyHR System</small></p>
    </body>
    </html>";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: SMEasyHR System <no-reply@smeasyhr.com>\r\n";
    
    if (mail($employer['email'], $subject, $email_body, $headers)) {
        logMessage("Daily report sent to " . $employer['email']);
    } else {
        logMessage("Failed to send daily report to " . $employer['email']);
    }
}

// Main execution based on command line argument
if ($argc < 2) {
    echo "Usage: php attendance_monitor.php [action]\n";
    echo "Actions:\n";
    echo "  mark_absent     - Mark employees as absent (run at 9:15 AM)\n";
    echo "  check_late      - Check for late clock-ins (run at 9:30 AM)\n";
    echo "  check_early_out - Check for early departures (run at 6:15 PM)\n";
    echo "  missing_clockout- Check for missing clock-outs (run at 6:30 PM)\n";
    echo "  daily_report    - Generate daily report (run at 7:00 PM)\n";
    echo "  full_check      - Run all checks\n";
    exit(1);
}

$action = $argv[1];

logMessage("Starting attendance monitor with action: $action");

switch ($action) {
    case 'mark_absent':
        markAbsentEmployees();
        break;
        
    case 'check_late':
        checkLateClockIns();
        break;
        
    case 'check_early_out':
        checkEarlyClockOuts();
        break;
        
    case 'missing_clockout':
        checkMissingClockOuts();
        break;
        
    case 'daily_report':
        generateDailyReport();
        break;
        
    case 'full_check':
        markAbsentEmployees();
        checkLateClockIns();
        checkEarlyClockOuts();
        checkMissingClockOuts();
        generateDailyReport();
        break;
        
    default:
        logMessage("Unknown action: $action");
        exit(1);
}

logMessage("Attendance monitor completed for action: $action");
?>