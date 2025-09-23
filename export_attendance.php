<?php
require('database.php');
require('session.php');

// Check if user is logged in
if (!isset($_SESSION['ID'])) {
    header('Location: login.php');
    exit();
}

// Get parameters from URL
$employee_id = $_GET['employee_id'] ?? $_SESSION['ID']; // Use session ID if no employee_id provided
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');
$export_format = $_GET['format'] ?? 'csv'; // csv or excel

// Security check: If user is not an admin/employer, they can only export their own data
$current_user_role = $_SESSION['role'] ?? '';
if ($current_user_role !== 'Employer' && $employee_id != $_SESSION['ID']) {
    die('Access denied: You can only export your own attendance data');
}

// Validate parameters
if (!$employee_id) {
    die('Employee ID is required');
}

// Get employee information
$employee_query = "SELECT username, role FROM employeelogin WHERE ID = ?";
$stmt = $conn->prepare($employee_query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee_result = $stmt->get_result();
$employee = $employee_result->fetch_assoc();

if (!$employee) {
    die('Employee not found');
}

// Get attendance records
$attendance_query = "SELECT 
    date,
    clock_in,
    clock_out,
    status,
    ip_address,
    location_coordinates,
    TIMEDIFF(clock_out, clock_in) as work_duration
FROM attendance 
WHERE employee_id = ? 
AND MONTH(date) = ? 
AND YEAR(date) = ? 
ORDER BY date ASC";

$stmt = $conn->prepare($attendance_query);
$stmt->bind_param("iii", $employee_id, $selected_month, $selected_year);
$stmt->execute();
$attendance_result = $stmt->get_result();

// Calculate totals
$total_present = 0;
$total_absent = 0;
$total_leave = 0;
$total_halfday = 0;
$total_off = 0;
$total_work_hours = 0;

$attendance_data = [];
while ($row = $attendance_result->fetch_assoc()) {
    $attendance_data[] = $row;
    
    // Count totals
    switch ($row['status']) {
        case 'present': 
            $total_present++; 
            // Calculate work hours if both clock_in and clock_out exist
            if ($row['clock_in'] && $row['clock_out']) {
                $work_duration = strtotime($row['clock_out']) - strtotime($row['clock_in']);
                $total_work_hours += $work_duration / 3600; // Convert to hours
            }
            break;
        case 'absent': $total_absent++; break;
        case 'off day': $total_off++; break;
        case 'on-leave': $total_leave++; break;
        case 'half-day': $total_halfday++; break;
    }
}

$month_name = date('F', mktime(0, 0, 0, $selected_month, 10));

// Export as CSV
if ($export_format === 'csv') {
    $filename = "attendance_{$employee['username']}_{$month_name}_{$selected_year}.csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add header information
    fputcsv($output, ['ATTENDANCE REPORT']);
    fputcsv($output, ['Employee:', $employee['username']]);
    fputcsv($output, ['Role:', $employee['role']]);
    fputcsv($output, ['Period:', "$month_name $selected_year"]);
    fputcsv($output, ['Generated on:', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Add summary
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Present Days:', $total_present]);
    fputcsv($output, ['Total Absent Days:', $total_absent]);
    fputcsv($output, ['Total Leave Days:', $total_leave]);
    fputcsv($output, ['Total Half Days:', $total_halfday]);
    fputcsv($output, ['Total Off Days:', $total_off]);
    fputcsv($output, ['Total Work Hours:', number_format($total_work_hours, 2)]);
    fputcsv($output, []);
    
    // Add column headers
    fputcsv($output, ['Date', 'Clock In', 'Clock Out', 'Status', 'Work Duration', 'IP Address']);
    
    // Add attendance data
    foreach ($attendance_data as $row) {
        $work_duration = $row['work_duration'] ?? 'N/A';
        if ($work_duration && $work_duration !== 'N/A') {
            // Convert duration to readable format
            $duration_parts = explode(':', $work_duration);
            $work_duration = $duration_parts[0] . 'h ' . $duration_parts[1] . 'm';
        }
        
        fputcsv($output, [
            $row['date'],
            $row['clock_in'] ?? 'N/A',
            $row['clock_out'] ?? 'N/A',
            $row['status'],
            $work_duration,
            $row['ip_address'] ?? 'N/A'
        ]);
    }
    
    fclose($output);
    exit();
}

// Export as Excel (using HTML table that Excel can read)
if ($export_format === 'excel') {
    $filename = "attendance_{$employee['username']}_{$month_name}_{$selected_year}.xls";
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';
    
    // Header information
    echo '<tr><td colspan="6"><strong>ATTENDANCE REPORT</strong></td></tr>';
    echo '<tr><td>Employee:</td><td colspan="5">' . htmlspecialchars($employee['username']) . '</td></tr>';
    echo '<tr><td>Role:</td><td colspan="5">' . htmlspecialchars($employee['role']) . '</td></tr>';
    echo '<tr><td>Period:</td><td colspan="5">' . htmlspecialchars("$month_name $selected_year") . '</td></tr>';
    echo '<tr><td>Generated on:</td><td colspan="5">' . date('Y-m-d H:i:s') . '</td></tr>';
    echo '<tr><td colspan="6"></td></tr>';
    
    // Summary
    echo '<tr><td colspan="6"><strong>SUMMARY</strong></td></tr>';
    echo '<tr><td>Total Present Days:</td><td>' . $total_present . '</td><td></td><td></td><td></td><td></td></tr>';
    echo '<tr><td>Total Absent Days:</td><td>' . $total_absent . '</td><td></td><td></td><td></td><td></td></tr>';
    echo '<tr><td>Total Leave Days:</td><td>' . $total_leave . '</td><td></td><td></td><td></td><td></td></tr>';
    echo '<tr><td>Total Half Days:</td><td>' . $total_halfday . '</td><td></td><td></td><td></td><td></td></tr>';
    echo '<tr><td>Total Off Days:</td><td>' . $total_off . '</td><td></td><td></td><td></td><td></td></tr>';
    echo '<tr><td>Total Work Hours:</td><td>' . number_format($total_work_hours, 2) . '</td><td></td><td></td><td></td><td></td></tr>';
    echo '<tr><td colspan="6"></td></tr>';
    
    // Column headers
    echo '<tr style="background-color: #f0f0f0;">';
    echo '<td><strong>Date</strong></td>';
    echo '<td><strong>Clock In</strong></td>';
    echo '<td><strong>Clock Out</strong></td>';
    echo '<td><strong>Status</strong></td>';
    echo '<td><strong>Work Duration</strong></td>';
    echo '<td><strong>IP Address</strong></td>';
    echo '</tr>';
    
    // Attendance data
    foreach ($attendance_data as $row) {
        $work_duration = $row['work_duration'] ?? 'N/A';
        if ($work_duration && $work_duration !== 'N/A') {
            $duration_parts = explode(':', $work_duration);
            $work_duration = $duration_parts[0] . 'h ' . $duration_parts[1] . 'm';
        }
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['date']) . '</td>';
        echo '<td>' . htmlspecialchars($row['clock_in'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($row['clock_out'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($row['status']) . '</td>';
        echo '<td>' . htmlspecialchars($work_duration) . '</td>';
        echo '<td>' . htmlspecialchars($row['ip_address'] ?? 'N/A') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit();
}

// If no valid format specified, show error
die('Invalid export format specified');
?>