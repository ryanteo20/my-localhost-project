<?php
require('database.php');
require('session.php');

// Check if user is logged in
if (!isset($_SESSION['ID'])) {
    header('Location: login.php');
    exit();
}

// Get form parameters
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$employee_filter = $_POST['employee_filter'] ?? 'all';
$selected_employees = $_POST['selected_employees'] ?? [];
$report_status = $_POST['report_status'] ?? 'all';
$report_category = $_POST['report_category'] ?? 'all';

// Validate required parameters
if (empty($start_date) || empty($end_date)) {
    die('Start date and end date are required');
}

// Check what attachment column exists in your claims table
$check_columns_query = "DESCRIBE claims";
$columns_result = mysqli_query($conn, $check_columns_query);
$attachment_column = null;

if ($columns_result) {
    while ($column = mysqli_fetch_assoc($columns_result)) {
        if (in_array($column['Field'], ['attachment', 'attachment_path', 'attachment_file'])) {
            $attachment_column = $column['Field'];
            break;
        }
    }
}

// Use the correct attachment column or null if none exists
$attachment_select = $attachment_column ? "c.$attachment_column" : "NULL as attachment";

// Build the base query
$query = "SELECT 
    c.claim_id,
    el.username as employee_name,
    COALESCE(pi.full_name, el.username) as full_name,
    c.category,
    c.transaction_date,
    c.amount,
    c.invoice_number,
    c.notes,
    $attachment_select as attachment,
    c.status,
    c.created_at,
    c.rejection_reason,
    c.approved_at
FROM claims c
INNER JOIN employeelogin el ON c.employee_id = el.ID
LEFT JOIN personal_information pi ON el.ID = pi.personal_id
WHERE c.transaction_date BETWEEN ? AND ?";

// Prepare parameters array
$params = [$start_date, $end_date];
$param_types = "ss";

// Add employee filter
if ($employee_filter === 'specific' && !empty($selected_employees)) {
    $placeholders = str_repeat('?,', count($selected_employees) - 1) . '?';
    $query .= " AND c.employee_id IN ($placeholders)";
    $params = array_merge($params, $selected_employees);
    $param_types .= str_repeat('i', count($selected_employees));
}

// Add status filter
if ($report_status !== 'all') {
    $query .= " AND c.status = ?";
    $params[] = $report_status;
    $param_types .= "s";
}

// Add category filter
if ($report_category !== 'all') {
    $query .= " AND c.category = ?";
    $params[] = $report_category;
    $param_types .= "s";
}

$query .= " ORDER BY c.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Database prepare error: " . $conn->error . "\nQuery: " . $query);
}

// Bind parameters dynamically
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Calculate summary statistics
$total_claims = 0;
$total_amount = 0;
$approved_amount = 0;
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

$claims_data = [];
while ($row = $result->fetch_assoc()) {
    $claims_data[] = $row;
    $total_claims++;
    $total_amount += $row['amount'];
    
    switch (strtolower($row['status'])) {
        case 'pending':
            $pending_count++;
            break;
        case 'approved':
            $approved_count++;
            $approved_amount += $row['amount'];
            break;
        case 'rejected':
            $rejected_count++;
            break;
    }
}

// Generate filename
$employee_text = ($employee_filter === 'all') ? 'All_Employees' : count($selected_employees) . '_Selected_Employees';
$filename = "Claims_Report_{$employee_text}_{$start_date}_to_{$end_date}.xls";

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

// Start Excel output
echo '<html><head><meta charset="UTF-8"></head><body>';
echo '<table border="1">';

// Report Header
echo '<tr><td colspan="13" style="font-size: 16px; font-weight: bold; text-align: center;">CLAIMS SUMMARY REPORT</td></tr>';
echo '<tr><td colspan="13"></td></tr>';

// Report Parameters
echo '<tr><td><strong>Report Parameters:</strong></td><td colspan="12"></td></tr>';
echo '<tr><td>Date Range:</td><td colspan="12">' . htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date) . '</td></tr>';
echo '<tr><td>Employee Filter:</td><td colspan="12">' . htmlspecialchars($employee_filter === 'all' ? 'All Employees' : count($selected_employees) . ' Selected Employees') . '</td></tr>';
echo '<tr><td>Status Filter:</td><td colspan="12">' . htmlspecialchars($report_status === 'all' ? 'All Statuses' : $report_status) . '</td></tr>';
echo '<tr><td>Category Filter:</td><td colspan="12">' . htmlspecialchars($report_category === 'all' ? 'All Categories' : $report_category) . '</td></tr>';
echo '<tr><td>Generated on:</td><td colspan="12">' . date('Y-m-d H:i:s') . '</td></tr>';
echo '<tr><td colspan="13"></td></tr>';

// Summary Statistics
echo '<tr><td colspan="13" style="font-weight: bold; background-color: #f0f0f0;">SUMMARY STATISTICS</td></tr>';
echo '<tr><td>Total Claims:</td><td>' . $total_claims . '</td><td colspan="11"></td></tr>';
echo '<tr><td>Pending Claims:</td><td>' . $pending_count . '</td><td colspan="11"></td></tr>';
echo '<tr><td>Approved Claims:</td><td>' . $approved_count . '</td><td colspan="11"></td></tr>';
echo '<tr><td>Rejected Claims:</td><td>' . $rejected_count . '</td><td colspan="11"></td></tr>';
echo '<tr><td>Total Claimed Amount:</td><td>RM ' . number_format($total_amount, 2) . '</td><td colspan="11"></td></tr>';
echo '<tr><td>Total Approved Amount:</td><td>RM ' . number_format($approved_amount, 2) . '</td><td colspan="11"></td></tr>';
echo '<tr><td colspan="13"></td></tr>';

// Column Headers
echo '<tr style="background-color: #d9d9d9; font-weight: bold;">';
echo '<td>Claim ID</td>';
echo '<td>Employee Username</td>';
echo '<td>Employee Full Name</td>';
echo '<td>Category</td>';
echo '<td>Transaction Date</td>';
echo '<td>Amount (RM)</td>';
echo '<td>Invoice Number</td>';
echo '<td>Notes</td>';
echo '<td>Attachment</td>';
echo '<td>Status</td>';
echo '<td>Created At</td>';
echo '<td>Rejection Reason</td>';
echo '<td>Approved At</td>';
echo '</tr>';

// Data Rows
if (!empty($claims_data)) {
    foreach ($claims_data as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['claim_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['employee_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['category']) . '</td>';
        echo '<td>' . htmlspecialchars($row['transaction_date']) . '</td>';
        echo '<td>' . number_format($row['amount'], 2) . '</td>';
        echo '<td>' . htmlspecialchars($row['invoice_number'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['notes'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['attachment'] ? 'Yes' : 'No') . '</td>';
        echo '<td>' . htmlspecialchars($row['status']) . '</td>';
        echo '<td>' . htmlspecialchars($row['created_at'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['rejection_reason'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['approved_at'] ?? '') . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="13" style="text-align: center; font-style: italic;">No claims found for the selected criteria</td></tr>';
}

// Summary by Category (if data exists)
if (!empty($claims_data)) {
    echo '<tr><td colspan="13"></td></tr>';
    echo '<tr><td colspan="13" style="font-weight: bold; background-color: #f0f0f0;">SUMMARY BY CATEGORY</td></tr>';
    
    $category_summary = [];
    foreach ($claims_data as $row) {
        $category = $row['category'];
        if (!isset($category_summary[$category])) {
            $category_summary[$category] = ['count' => 0, 'amount' => 0, 'approved_amount' => 0];
        }
        $category_summary[$category]['count']++;
        $category_summary[$category]['amount'] += $row['amount'];
        if (strtolower($row['status']) === 'approved') {
            $category_summary[$category]['approved_amount'] += $row['amount'];
        }
    }
    
    echo '<tr style="background-color: #e0e0e0; font-weight: bold;">';
    echo '<td>Category</td><td>Total Claims</td><td>Total Amount (RM)</td><td>Approved Amount (RM)</td><td colspan="9"></td>';
    echo '</tr>';
    
    foreach ($category_summary as $category => $summary) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($category) . '</td>';
        echo '<td>' . $summary['count'] . '</td>';
        echo '<td>' . number_format($summary['amount'], 2) . '</td>';
        echo '<td>' . number_format($summary['approved_amount'], 2) . '</td>';
        echo '<td colspan="9"></td>';
        echo '</tr>';
    }
}

echo '</table>';
echo '</body></html>';

$stmt->close();
$conn->close();
exit();
?>