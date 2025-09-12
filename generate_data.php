<?php
require('database.php');

// Set time limit for long-running script
set_time_limit(300);

echo "<h2>Generating HR Data for All Active Employees (Jan 1 - May 31, 2025)</h2>";

// First, let's check all table structures
echo "<h3>Checking Database Structure:</h3>";

// Check attendance table
$structure_query = "DESCRIBE attendance";
$structure_result = mysqli_query($conn, $structure_query);

if ($structure_result) {
    echo "<p><strong>Attendance table structure:</strong></p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($structure_result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "<p style='color:red'>Error checking attendance table structure: " . mysqli_error($conn) . "</p>";
}

// Check leave_apply table
$leave_query = "DESCRIBE leave_apply";
$leave_result = mysqli_query($conn, $leave_query);

if ($leave_result) {
    echo "<p><strong>Leave_apply table structure:</strong></p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($leave_result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "<p style='color:orange'>Leave_apply table doesn't exist or error: " . mysqli_error($conn) . "</p>";
}

// Check claims table
$claims_query = "DESCRIBE claims";
$claims_result = mysqli_query($conn, $claims_query);

if ($claims_result) {
    echo "<p><strong>Claims table structure:</strong></p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($claims_result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "<p style='color:orange'>Claims table doesn't exist or error: " . mysqli_error($conn) . "</p>";
}

// Get all active employees
$employees_query = "SELECT ID, username, role FROM employeelogin WHERE status = 'active' OR status IS NULL";
$employees_result = mysqli_query($conn, $employees_query);

if (!$employees_result) {
    die("Error fetching employees: " . mysqli_error($conn));
}

$employees = [];
while ($row = mysqli_fetch_assoc($employees_result)) {
    $employees[] = $row;
}

echo "<p>Found " . count($employees) . " active employees</p>";

// Display employees list
echo "<p><strong>Employees found:</strong></p>";
echo "<ul>";
foreach ($employees as $emp) {
    echo "<li>ID: {$emp['ID']}, Username: {$emp['username']}, Role: {$emp['role']}</li>";
}
echo "</ul>";

// Define Malaysian public holidays for 2025
$public_holidays = [
    '2025-01-01' => 'New Year Day',
    '2025-01-29' => 'Chinese New Year',
    '2025-01-30' => 'Chinese New Year',
    '2025-04-18' => 'Good Friday',
    '2025-05-01' => 'Labor Day',
    '2025-05-12' => 'Vesak Day',
    '2025-06-02' => 'Gawai Dayak',
    '2025-06-03' => 'Kings Birthday',
    '2025-08-31' => 'National Day',
    '2025-09-16' => 'Malaysia Day'
];

// Function to check if date is weekend (Saturday or Sunday)
function isWeekend($date) {
    $dayOfWeek = date('w', strtotime($date));
    return ($dayOfWeek == 0 || $dayOfWeek == 6); // 0 = Sunday, 6 = Saturday
}

// Function to generate realistic attendance pattern
function generateAttendancePattern($employee_id, $date, $public_holidays) {
    // Check if it's a public holiday
    if (isset($public_holidays[$date])) {
        return [
            'status' => 'off day',
            'clock_in' => NULL,
            'clock_out' => NULL,
            'ip_address' => '192.168.0.' . $employee_id,
            'location_coordinates' => '3.14' . rand(100, 999) . ',101.' . rand(100, 999),
            'notes' => $public_holidays[$date]
        ];
    }
    
    // Check if it's weekend
    if (isWeekend($date)) {
        $dayName = date('l', strtotime($date));
        return [
            'status' => 'off day',
            'clock_in' => NULL,
            'clock_out' => NULL,
            'ip_address' => '192.168.0.' . $employee_id,
            'location_coordinates' => '3.14' . rand(100, 999) . ',101.' . rand(100, 999),
            'notes' => $dayName
        ];
    }
    
    // Generate realistic work patterns
    $rand = rand(1, 100);
    
    // 5% chance of absence (sick leave, annual leave, etc.)
    if ($rand <= 5) {
        $leave_types = ['on-leave', 'absent'];
        return [
            'status' => $leave_types[array_rand($leave_types)],
            'clock_in' => NULL,
            'clock_out' => NULL,
            'ip_address' => '192.168.0.' . $employee_id,
            'location_coordinates' => '3.14' . rand(100, 999) . ',101.' . rand(100, 999),
            'notes' => 'Sick leave/Annual leave'
        ];
    }
    
    // 10% chance of half day
    if ($rand <= 15) {
        $clock_in = $date . ' 09:00:00';
        $clock_out = $date . ' 13:00:00'; // Half day - out at 1 PM
        return [
            'status' => 'half-day',
            'clock_in' => $clock_in,
            'clock_out' => $clock_out,
            'ip_address' => '192.168.0.' . $employee_id,
            'location_coordinates' => '3.14' . rand(100, 999) . ',101.' . rand(100, 999),
            'notes' => 'Half day'
        ];
    }
    
    // Normal work day
    $clock_in_times = ['08:55:00', '09:00:00', '09:05:00', '09:10:00'];
    $clock_out_times = ['17:00:00', '17:30:00', '18:00:00', '18:30:00'];
    
    $clock_in_time = $clock_in_times[array_rand($clock_in_times)];
    $clock_out_time = $clock_out_times[array_rand($clock_out_times)];
    
    return [
        'status' => 'present',
        'clock_in' => $date . ' ' . $clock_in_time,
        'clock_out' => $date . ' ' . $clock_out_time,
        'ip_address' => '192.168.0.' . $employee_id,
        'location_coordinates' => '3.14' . rand(100, 999) . ',101.' . rand(100, 999),
        'notes' => NULL
    ];
}

// Clear existing data for the period
echo "<p>Clearing existing data for Jan-May 2025...</p>";

// Clear attendance data
$delete_attendance = mysqli_query($conn, "DELETE FROM attendance WHERE date BETWEEN '2025-01-01' AND '2025-05-31'");
if (!$delete_attendance) {
    echo "<p style='color:red'>Error clearing attendance data: " . mysqli_error($conn) . "</p>";
} else {
    echo "<p style='color:green'>✅ Attendance data cleared successfully</p>";
}

// Clear leave_apply data
$delete_leave = mysqli_query($conn, "DELETE FROM leave_apply WHERE leave_datestart >= '2025-01-01' AND leave_dateend <= '2025-05-31'");
if (!$delete_leave) {
    echo "<p style='color:orange'>Note: Could not clear leave_apply data (table may not exist): " . mysqli_error($conn) . "</p>";
} else {
    echo "<p style='color:green'>✅ Leave applications data cleared successfully</p>";
}

// Clear claims data
$delete_claims = mysqli_query($conn, "DELETE FROM claims WHERE transaction_date >= '2025-01-01' AND transaction_date <= '2025-05-31'");
if (!$delete_claims) {
    echo "<p style='color:orange'>Note: Could not clear claims data (table may not exist): " . mysqli_error($conn) . "</p>";
} else {
    echo "<p style='color:green'>✅ Claims data cleared successfully</p>";
}

// ========================================
// GENERATE ATTENDANCE DATA
// ========================================
echo "<h3>🔄 Generating Attendance Data...</h3>";

$total_attendance = 0;
$attendance_errors = 0;

foreach ($employees as $employee) {
    echo "<p>Generating attendance for Employee ID {$employee['ID']} ({$employee['username']})...</p>";
    
    $start_date = '2025-01-01';
    $end_date = '2025-05-31';
    
    $current_date = $start_date;
    while ($current_date <= $end_date) {
        $attendance = generateAttendancePattern($employee['ID'], $current_date, $public_holidays);
        
        $query = "INSERT INTO attendance (employee_id, date, clock_in, clock_out, status, ip_address, location_coordinates, notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isssssss", 
                $employee['ID'],
                $current_date,
                $attendance['clock_in'],
                $attendance['clock_out'],
                $attendance['status'],
                $attendance['ip_address'],
                $attendance['location_coordinates'],
                $attendance['notes']
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $total_attendance++;
            } else {
                $attendance_errors++;
                echo "<p style='color:red'>❌ Error inserting attendance for {$employee['username']} on {$current_date}: " . mysqli_stmt_error($stmt) . "</p>";
            }
            mysqli_stmt_close($stmt);
        } else {
            $attendance_errors++;
        }
        
        $current_date = date('Y-m-d', strtotime($current_date . ' + 1 day'));
    }
}

// ========================================
// GENERATE LEAVE APPLICATIONS DATA
// ========================================
echo "<h3>🔄 Generating Leave Applications Data...</h3>";

// Leave applications data following your table structure
$leave_applications = [
    // Employee 1 (3 applications)
    [1, 'annual', '2025-01-15', '2025-01-15', 1, 'Personal matters', 'Approved', '2025-01-10'],
    [1, 'sick', '2025-03-20', '2025-03-20', 1, 'Flu symptoms', 'Approved', '2025-03-20'],
    [1, 'annual', '2025-05-15', '2025-05-16', 2, 'Long weekend break', 'Pending for review', '2025-05-10'],

    // Employee 2 (3 applications)
    [2, 'sick', '2025-01-22', '2025-01-22', 1, 'Doctor appointment', 'Approved', '2025-01-21'],
    [2, 'annual', '2025-03-07', '2025-03-09', 3, 'Family event', 'Approved', '2025-02-28'],
    [2, 'annual', '2025-05-20', '2025-05-22', 3, 'Travel plans', 'Pending for review', '2025-05-15'],

    // Employee 3 (2 applications)
    [3, 'annual', '2025-02-14', '2025-02-14', 1, 'Personal appointment', 'Approved', '2025-02-10'],
    [3, 'sick', '2025-04-08', '2025-04-08', 1, 'Medical checkup', 'Approved', '2025-04-07'],

    // Employee 4 (2 applications)
    [4, 'annual', '2025-02-17', '2025-02-19', 3, 'Birthday celebration', 'Approved', '2025-02-10'],
    [4, 'emergency', '2025-04-25', '2025-04-25', 1, 'Family emergency', 'Approved', '2025-04-25'],

    // Employee 5 (2 applications)
    [5, 'annual', '2025-01-30', '2025-01-31', 2, 'Chinese New Year celebration', 'Approved', '2025-01-20'],
    [5, 'sick', '2025-03-12', '2025-03-12', 1, 'Dental appointment', 'Approved', '2025-03-10'],

    // Employee 6 (2 applications)
    [6, 'annual', '2025-02-05', '2025-02-07', 3, 'Personal vacation', 'Approved', '2025-01-28'],
    [6, 'annual', '2025-04-14', '2025-04-14', 1, 'Bank matters', 'Approved', '2025-04-10'],

    // Employee 7 (2 applications)
    [7, 'sick', '2025-01-18', '2025-01-18', 1, 'Health checkup', 'Approved', '2025-01-17'],
    [7, 'annual', '2025-05-08', '2025-05-09', 2, 'Personal matters', 'Pending for review', '2025-05-01'],
];

$total_leave = 0;
$leave_errors = 0;

foreach ($leave_applications as $leave) {
    if ($leave[0] <= count($employees)) { // Check if employee exists
        $query = "INSERT INTO leave_apply (fk_leaveapply_id, leave_type, leave_datestart, leave_dateend, leave_length, leave_reason, leave_review, apply_date) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isssdsss", 
                $leave[0], $leave[1], $leave[2], $leave[3], $leave[4], 
                $leave[5], $leave[6], $leave[7]
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $total_leave++;
            } else {
                $leave_errors++;
                echo "<p style='color:red'>❌ Error inserting leave application: " . mysqli_stmt_error($stmt) . "</p>";
            }
            mysqli_stmt_close($stmt);
        } else {
            $leave_errors++;
            echo "<p style='color:red'>❌ Error preparing leave statement: " . mysqli_error($conn) . "</p>";
        }
    }
}

// ========================================
// GENERATE CLAIMS DATA
// ========================================
echo "<h3>🔄 Generating Claims Data...</h3>";

// Claims data following your table structure
$claims_data = [
    // Employee 1 (4 claims)
    [1, 'Travel', '2025-01-15', 45.50, 'INV001', 'Client meeting transportation', 'Approved', '2025-01-18'],
    [1, 'Meal', '2025-02-28', 28.90, 'INV002', 'Lunch during overtime work', 'Approved', '2025-03-03'],
    [1, 'Medical', '2025-04-12', 85.00, 'INV003', 'Annual health checkup', 'Approved', '2025-04-15'],
    [1, 'Travel', '2025-05-05', 32.00, 'INV004', 'Site visit fuel cost', 'Pending', NULL],

    // Employee 2 (3 claims)
    [2, 'Medical', '2025-01-22', 65.00, 'INV005', 'Dental cleaning', 'Approved', '2025-01-25'],
    [2, 'Training', '2025-03-15', 200.00, 'INV006', 'IT certification exam fee', 'Approved', '2025-03-18'],
    [2, 'Meal', '2025-04-25', 38.70, 'INV007', 'Team dinner expense', 'Approved', '2025-04-28'],

    // Employee 3 (3 claims)
    [3, 'Travel', '2025-02-05', 52.30, 'INV008', 'Workshop attendance', 'Approved', '2025-02-08'],
    [3, 'Medical', '2025-03-20', 120.00, 'INV009', 'Prescription medication', 'Approved', '2025-03-23'],
    [3, 'Meal', '2025-05-18', 31.80, 'INV010', 'Working lunch with client', 'Pending', NULL],

    // Employee 4 (4 claims)
    [4, 'Training', '2025-01-12', 180.00, 'INV011', 'Marketing workshop', 'Approved', '2025-01-15'],
    [4, 'Travel', '2025-02-14', 89.20, 'INV012', 'Sales meeting travel', 'Approved', '2025-02-17'],
    [4, 'Meal', '2025-04-10', 76.50, 'INV013', 'Client entertainment', 'Approved', '2025-04-13'],
    [4, 'Medical', '2025-05-12', 95.50, 'INV014', 'Eye examination', 'Pending', NULL],

    // Employee 5 (3 claims)
    [5, 'Medical', '2025-01-18', 78.90, 'INV015', 'Annual health checkup', 'Approved', '2025-01-21'],
    [5, 'Travel', '2025-03-08', 43.60, 'INV016', 'Supplier meeting', 'Approved', '2025-03-11'],
    [5, 'Training', '2025-04-22', 320.00, 'INV017', 'Technical certification', 'Approved', '2025-04-25'],

    // Employee 6 (3 claims)
    [6, 'Travel', '2025-02-28', 67.40, 'INV018', 'Product launch event', 'Approved', '2025-03-03'],
    [6, 'Meal', '2025-04-08', 54.80, 'INV019', 'Business lunch', 'Approved', '2025-04-11'],
    [6, 'Medical', '2025-05-22', 145.00, 'INV020', 'Physiotherapy sessions', 'Pending', NULL],

    // Employee 7 (3 claims)
    [7, 'Travel', '2025-01-25', 125.50, 'INV021', 'Equipment installation trip', 'Approved', '2025-01-28'],
    [7, 'Meal', '2025-03-15', 68.20, 'INV022', 'Project milestone celebration', 'Approved', '2025-03-18'],
    [7, 'Medical', '2025-05-10', 87.30, 'INV023', 'Medical consultation', 'Pending', NULL],
];

$total_claims = 0;
$claims_errors = 0;

foreach ($claims_data as $claim) {
    if ($claim[0] <= count($employees)) { // Check if employee exists
        $query = "INSERT INTO claims (employee_id, category, transaction_date, amount, invoice_number, notes, status, approved_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issdssss", 
                $claim[0], $claim[1], $claim[2], $claim[3], $claim[4], 
                $claim[5], $claim[6], $claim[7]
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $total_claims++;
            } else {
                $claims_errors++;
                echo "<p style='color:red'>❌ Error inserting claim: " . mysqli_stmt_error($stmt) . "</p>";
            }
            mysqli_stmt_close($stmt);
        } else {
            $claims_errors++;
            echo "<p style='color:red'>❌ Error preparing claims statement: " . mysqli_error($conn) . "</p>";
        }
    }
}

// ========================================
// SUMMARY RESULTS
// ========================================
echo "<h3 style='color:green'>✅ All HR Data Generation Completed!</h3>";
echo "<div style='background:#f8f9fa; padding:15px; border-radius:5px; margin:10px 0;'>";
echo "<h4>📊 Generation Summary:</h4>";
echo "<ul>";
echo "<li><strong>Attendance Records:</strong> {$total_attendance} inserted, {$attendance_errors} errors</li>";
echo "<li><strong>Leave Applications:</strong> {$total_leave} inserted, {$leave_errors} errors</li>";
echo "<li><strong>Claims:</strong> {$total_claims} inserted, {$claims_errors} errors</li>";
echo "<li><strong>Employees Processed:</strong> " . count($employees) . "</li>";
echo "<li><strong>Date Range:</strong> January 1, 2025 - May 31, 2025</li>";
echo "</ul>";
echo "</div>";

// Display summary statistics for all tables
echo "<h3>📈 Database Statistics:</h3>";

// Attendance stats
$attendance_stats = "SELECT 
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN status = 'absent' OR status = 'on-leave' THEN 1 ELSE 0 END) as absent_days,
    SUM(CASE WHEN status = 'off day' THEN 1 ELSE 0 END) as off_days,
    SUM(CASE WHEN status = 'half-day' THEN 1 ELSE 0 END) as half_days
    FROM attendance 
    WHERE date BETWEEN '2025-01-01' AND '2025-05-31'";

$att_result = mysqli_query($conn, $attendance_stats);
if ($att_result) {
    $att_stats = mysqli_fetch_assoc($att_result);
    echo "<h4>📅 Attendance Summary:</h4>";
    echo "<ul>";
    echo "<li>Total Records: {$att_stats['total_records']}</li>";
    echo "<li>Present Days: {$att_stats['present_days']}</li>";
    echo "<li>Absent/Leave Days: {$att_stats['absent_days']}</li>";
    echo "<li>Off Days: {$att_stats['off_days']}</li>";
    echo "<li>Half Days: {$att_stats['half_days']}</li>";
    echo "</ul>";
}

// Leave stats
$leave_stats = "SELECT 
    COUNT(*) as total_applications,
    SUM(CASE WHEN leave_review = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN leave_review = 'Pending for review' THEN 1 ELSE 0 END) as pending,
    SUM(leave_length) as total_days_requested
    FROM leave_apply 
    WHERE leave_datestart >= '2025-01-01' AND leave_dateend <= '2025-05-31'";

$leave_result = mysqli_query($conn, $leave_stats);
if ($leave_result) {
    $leave_stat = mysqli_fetch_assoc($leave_result);
    echo "<h4>🏖️ Leave Applications Summary:</h4>";
    echo "<ul>";
    echo "<li>Total Applications: {$leave_stat['total_applications']}</li>";
    echo "<li>Approved: {$leave_stat['approved']}</li>";
    echo "<li>Pending: {$leave_stat['pending']}</li>";
    echo "<li>Total Days Requested: {$leave_stat['total_days_requested']}</li>";
    echo "</ul>";
}

// Claims stats
$claims_stats = "SELECT 
    COUNT(*) as total_claims,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(amount) as total_amount,
    SUM(CASE WHEN status = 'Approved' THEN amount ELSE 0 END) as approved_amount
    FROM claims 
    WHERE transaction_date >= '2025-01-01' AND transaction_date <= '2025-05-31'";

$claims_result = mysqli_query($conn, $claims_stats);
if ($claims_result) {
    $claims_stat = mysqli_fetch_assoc($claims_result);
    echo "<h4>💰 Claims Summary:</h4>";
    echo "<ul>";
    echo "<li>Total Claims: {$claims_stat['total_claims']}</li>";
    echo "<li>Approved: {$claims_stat['approved']}</li>";
    echo "<li>Pending: {$claims_stat['pending']}</li>";
    echo "<li>Total Amount: RM " . number_format($claims_stat['total_amount'], 2) . "</li>";
    echo "<li>Approved Amount: RM " . number_format($claims_stat['approved_amount'], 2) . "</li>";
    echo "</ul>";
}

echo "<p><a href='C_payslip.php' style='background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>← Back to Payslip Management</a></p>";
?>