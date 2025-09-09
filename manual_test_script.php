<?php
/**
 * Manual Test Script for Attendance Notification System
 * This script helps you test the notification system manually
 */

require('database.php');
require_once('send_notification.php');

date_default_timezone_set('Asia/Kuala_Lumpur');
$date = date('Y-m-d');

echo "=== SMEasyHR Attendance Notification System Test ===\n";
echo "Date: $date\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Function to display menu
function showMenu() {
    echo "Choose an action:\n";
    echo "1. Check current attendance status\n";
    echo "2. Manually mark employees as absent\n";
    echo "3. Test notification system\n";
    echo "4. View all employees\n";
    echo "5. View attendance records for today\n";
    echo "6. Clear today's attendance (for testing)\n";
    echo "7. Send test email\n";
    echo "8. View notifications\n";
    echo "0. Exit\n";
    echo "Enter your choice: ";
}

function checkAttendanceStatus() {
    global $con, $date;
    
    echo "\n=== Current Attendance Status for $date ===\n";
    
    $query = "SELECT 
                el.ID, el.username, el.employer_id,
                a.status, a.clock_in, a.clock_out
              FROM employeelogin el
              LEFT JOIN attendance a ON el.ID = a.employee_id AND a.date = ?
              WHERE el.role = 'employee' AND el.status = 'active'
              ORDER BY el.username";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    printf("%-5s %-20s %-10s %-10s %-10s\n", "ID", "Employee", "Status", "Clock In", "Clock Out");
    echo str_repeat("-", 65) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['status'] ?: 'No Record';
        $clock_in = $row['clock_in'] ? date('H:i', strtotime($row['clock_in'])) : 'N/A';
        $clock_out = $row['clock_out'] ? date('H:i', strtotime($row['clock_out'])) : 'N/A';
        
        printf("%-5d %-20s %-10s %-10s %-10s\n", 
               $row['ID'], 
               substr($row['username'], 0, 19), 
               $status, 
               $clock_in, 
               $clock_out);
    }
    echo "\n";
}
function manuallyMarkAbsent() {
    global $con, $date;
    
    echo "\n=== Manually Mark Employees as Absent ===\n";
    
    // Get employees who don't have attendance records today
    $query = "SELECT el.ID, el.username, el.employer_id
              FROM employeelogin el
              LEFT JOIN attendance a ON el.ID = a.employee_id AND a.date = ?
              WHERE el.role = 'employee' 
              AND el.status = 'active'
              AND a.employee_id IS NULL";
    
    $stmt = $con->prepare($query);
    
    if (!$stmt) {
        echo "✗ Error preparing the query: " . $con->error . "\n";
        return;
    }

    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        // Mark as absent
        $insertQuery = "INSERT INTO attendance (employee_id, date, status) 
                        VALUES (?, ?, 'absent')";
        $insertStmt = $con->prepare($insertQuery);
        
        if (!$insertStmt) {
            echo "✗ Error preparing the insert query: " . $con->error . "\n";
            return;
        }

        $insertStmt->bind_param("is", $row['ID'], $date);
        
        if ($insertStmt->execute()) {
            echo "Marked {$row['username']} (ID: {$row['ID']}) as absent\n";
            $count++;
        } else {
            echo "✗ Error marking {$row['username']} (ID: {$row['ID']}) as absent: " . $con->error . "\n";
        }
    }
    
    echo "Total employees marked as absent: $count\n\n";
}


function testNotificationSystem() {
    global $con, $date;
    
    echo "\n=== Testing Notification System ===\n";
    
    // Check if notifications table exists first
    $tableCheck = $con->query("SHOW TABLES LIKE 'notifications'");
    if ($tableCheck->num_rows === 0) {
        echo "✗ Error: 'notifications' table does not exist!\n";
        echo "Please create the notifications table first using the provided SQL.\n";
        return;
    }
    
    // Get an absent employee for testing
    $query = "SELECT a.employee_id, el.username, el.employer_id, pi.email as employer_email
              FROM attendance a
              JOIN employeelogin el ON a.employee_id = el.ID
              LEFT JOIN employeelogin emp ON el.employer_id = emp.ID
              LEFT JOIN personal_information pi ON emp.ID = pi.personal_id
              WHERE a.date = ? AND a.status = 'absent'
              LIMIT 1";
    
    $stmt = $con->prepare($query);
    if (!$stmt) {
        echo "✗ Error preparing first query: " . $con->error . "\n";
        return;
    }
    
    $stmt->bind_param("s", $date);  // Make sure $date is a valid string (e.g., '2025-09-09')
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Check if employer_id is null
        if (is_null($row['employer_id'])) {
            echo "✗ No employer associated with this employee. Skipping notification.\n";
            return;
        }
        
        $message = "TEST NOTIFICATION: {$row['username']} is marked as absent for testing purposes";
        
        // Insert system notification
        $notifyQuery = "INSERT INTO notifications (employee_id, employer_id, message, status, created_at) 
                       VALUES (?, ?, ?, 'unread', NOW())";
        $notifyStmt = $con->prepare($notifyQuery);
        
        if (!$notifyStmt) {
            echo "✗ Error preparing notification query: " . $con->error . "\n";
            return;
        }
        
        $notifyStmt->bind_param("iis", $row['employee_id'], $row['employer_id'], $message);
        
        if ($notifyStmt->execute()) {
            echo "✓ System notification created\n";
            
            // Send email if employer email exists
            if ($row['employer_email']) {
                if (sendNotification($row['employer_email'], $row['username'], $message)) {
                    echo "✓ Email notification sent to: {$row['employer_email']}\n";
                } else {
                    echo "✗ Failed to send email notification\n";
                }
            } else {
                echo "! No employer email found\n";
            }
        } else {
            echo "✗ Failed to create system notification: " . $notifyStmt->error . "\n";
        }
        
        $notifyStmt->close();
    } else {
        echo "No absent employees found for testing. Run option 2 first.\n";
    }
    
    $stmt->close();
    echo "\n";
}



function viewAllEmployees() {
    global $con;
    
    echo "\n=== All Active Employees ===\n";
    
    // Join employeelogin with personal_information to get the email
$query = "SELECT el.ID, el.username, pi.email, el.employer_id, el.role, el.status 
          FROM employeelogin el
          LEFT JOIN personal_information pi ON el.employer_id = pi.personal_id
          WHERE el.status = 'active' 
          ORDER BY el.role, el.username";


    
    $result = $con->query($query);
    
    if (!$result) {
        echo "✗ Error: " . $con->error . "\n";
        return;
    }
    
    printf("%-5s %-20s %-25s %-12s %-10s %-10s\n", "ID", "Username", "Email", "Employer ID", "Role", "Status");
    echo str_repeat("-", 90) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        printf("%-5d %-20s %-25s %-12s %-10s %-10s\n", 
               $row['ID'], 
               substr($row['username'], 0, 19),
               substr($row['email'], 0, 24),
               $row['employer_id'] ?: 'N/A',
               $row['role'],
               $row['status']);
    }
    echo "\n";
}


function viewTodayAttendance() {
    global $con, $date;
    
    echo "\n=== Today's Attendance Records ===\n";
    
    $query = "SELECT a.*, el.username 
              FROM attendance a
              JOIN employeelogin el ON a.employee_id = el.ID
              WHERE a.date = ?
              ORDER BY el.username";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    printf("%-5s %-20s %-10s %-10s %-10s\n", "ID", "Employee", "Status", "Clock In", "Clock Out");
    echo str_repeat("-", 65) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        $clock_in = $row['clock_in'] ? date('H:i', strtotime($row['clock_in'])) : 'N/A';
        $clock_out = $row['clock_out'] ? date('H:i', strtotime($row['clock_out'])) : 'N/A';
        
        printf("%-5d %-20s %-10s %-10s %-10s\n", 
               $row['employee_id'],
               substr($row['username'], 0, 19),
               $row['status'],
               $clock_in,
               $clock_out);
    }
    echo "\n";
}

function clearTodayAttendance() {
    global $con, $date;
    
    echo "\n=== Clear Today's Attendance (FOR TESTING ONLY) ===\n";
    echo "This will delete all attendance records for $date\n";
    echo "Are you sure? Type 'YES' to confirm: ";
    
    $confirmation = trim(fgets(STDIN));
    
    if ($confirmation === 'YES') {
        $query = "DELETE FROM attendance WHERE date = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $date);
        
        if ($stmt->execute()) {
            echo "✓ Cleared " . $stmt->affected_rows . " attendance records for $date\n";
        } else {
            echo "✗ Failed to clear attendance records\n";
        }
    } else {
        echo "Operation cancelled\n";
    }
    echo "\n";
}

function sendTestEmail() {
    echo "\n=== Send Test Email ===\n";
    echo "Enter email address to test: ";
    $email = trim(fgets(STDIN));
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "This is a test notification from SMEasyHR system at " . date('Y-m-d H:i:s');
        
        if (sendNotification($email, "Test Employee", $message)) {
            echo "✓ Test email sent successfully to $email\n";
        } else {
            echo "✗ Failed to send test email to $email\n";
        }
    } else {
        echo "✗ Invalid email address\n";
    }
    echo "\n";
}

function viewNotifications() {
    global $con, $date;
    
    echo "\n=== Recent Notifications ===\n";
    
    // Make sure the correct columns are being selected
    $query = "SELECT n.ID, n.message, n.status, el.username as employee_name, emp.username as employer_name
              FROM notifications n
              LEFT JOIN employeelogin el ON n.employee_id = el.ID
              LEFT JOIN employeelogin emp ON n.employer_id = emp.ID
              WHERE DATE(n.created_at) = ?
              ORDER BY n.created_at DESC
              LIMIT 20";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        printf("%-5s %-15s %-15s %-10s %-50s\n", "ID", "Employee", "Employer", "Status", "Message");
        echo str_repeat("-", 100) . "\n";
        
        while ($row = $result->fetch_assoc()) {
            // Ensure that 'ID', 'employee_name', and other keys are present
            printf("%-5d %-15s %-15s %-10s %-50s\n", 
                   $row['ID'],
                   substr($row['employee_name'], 0, 14),
                   substr($row['employer_name'], 0, 14),
                   $row['status'],
                   substr($row['message'], 0, 49));
        }
    } else {
        echo "No notifications found for today.\n";
    }
    
    echo "\n";
}


// Main execution loop
while (true) {
    showMenu();
    $choice = trim(fgets(STDIN));
    
    switch ($choice) {
        case '1':
            checkAttendanceStatus();
            break;
            
        case '2':
            manuallyMarkAbsent();
            break;
            
        case '3':
            testNotificationSystem();
            break;
            
        case '4':
            viewAllEmployees();
            break;
            
        case '5':
            viewTodayAttendance();
            break;
            
        case '6':
            clearTodayAttendance();
            break;
            
        case '7':
            sendTestEmail();
            break;
            
        case '8':
            viewNotifications();
            break;
            
        case '0':
            echo "Goodbye!\n";
            exit(0);
            
        default:
            echo "Invalid choice. Please try again.\n\n";
    }
    
    echo "Press Enter to continue...";
    fgets(STDIN);
    echo "\n" . str_repeat("=", 50) . "\n";
}