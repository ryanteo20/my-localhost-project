<?php
require('database.php');
require('session.php');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $employee_filter = $_POST['employee_filter'] ?? 'all';
    $report_status = $_POST['report_status'] ?? 'all';
    $report_category = $_POST['report_category'] ?? 'all';
    
    // Build the query with correct column names from your claims table
    $query = "SELECT 
                c.claim_id,
                c.employee_id,
                c.category,
                c.transaction_date,
                c.amount,
                c.invoice_number,
                c.notes,
                c.attachment,
                c.status,
                c.created_at,
                c.rejection_reason,
                c.approved_at,
                COALESCE(pi.full_name, el.username) as employee_name,
                el.role as position
              FROM claims c 
              LEFT JOIN employeelogin el ON c.employee_id = el.ID
              LEFT JOIN personal_information pi ON c.employee_id = pi.personal_id
              WHERE c.transaction_date BETWEEN ? AND ?";
    
    $params = [$start_date, $end_date];
    $types = "ss";
    
    // Add employee filter
    if ($employee_filter === 'specific' && isset($_POST['selected_employees']) && !empty($_POST['selected_employees'])) {
        $selected_employees = $_POST['selected_employees'];
        
        if (is_array($selected_employees) && count($selected_employees) > 0) {
            $placeholders = str_repeat('?,', count($selected_employees) - 1) . '?';
            $query .= " AND c.employee_id IN ($placeholders)";
            
            foreach ($selected_employees as $emp_id) {
                $params[] = (int)$emp_id;
                $types .= "i";
            }
        } else {
            $_SESSION['error_message'] = 'No employees selected for the report.';
            header('Location: R_claim.php');
            exit();
        }
    }
    
    // Add status filter
    if ($report_status !== 'all') {
        $query .= " AND c.status = ?";
        $params[] = $report_status;
        $types .= "s";
    }
    
    // Add category filter
    if ($report_category !== 'all') {
        $query .= " AND c.category = ?";
        $params[] = $report_category;
        $types .= "s";
    }
    
    $query .= " ORDER BY c.transaction_date DESC, employee_name ASC";
    
    // Prepare and execute query
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        $_SESSION['error_message'] = 'Database prepare error: ' . $conn->error;
        header('Location: R_claim.php');
        exit();
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        $_SESSION['error_message'] = 'Database execution error: ' . $stmt->error;
        header('Location: R_claim.php');
        exit();
    }
    
    $result = $stmt->get_result();
    
    // Check if we have any results
    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = 'No claims found matching your criteria.';
        header('Location: R_claim.php');
        exit();
    }
    
    try {
        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("SMEasyHR")
            ->setTitle("Claim Summary Report")
            ->setSubject("Claims Report")
            ->setDescription("Detailed claims report generated from SMEasyHR system");
        
        // Report Header
        $sheet->setCellValue('A1', 'SMEasyHR - Claim Summary Report');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
        
        // Report Info
        $sheet->setCellValue('A3', 'Report Generated: ' . date('Y-m-d H:i:s'));
        $sheet->setCellValue('A4', 'Date Range: ' . $start_date . ' to ' . $end_date);
        
        // Employee selection info
        if ($employee_filter === 'all') {
            $sheet->setCellValue('A5', 'Employees: All Employees');
        } else {
            $selected_count = isset($_POST['selected_employees']) ? count($_POST['selected_employees']) : 0;
            $sheet->setCellValue('A5', 'Employees: ' . $selected_count . ' Selected Employee(s)');
        }
        
        // Status and category info
        $sheet->setCellValue('A6', 'Status Filter: ' . ($report_status === 'all' ? 'All Statuses' : $report_status));
        $sheet->setCellValue('A7', 'Category Filter: ' . ($report_category === 'all' ? 'All Categories' : $report_category));
        
        // Headers
        $headers = [
            'A9' => 'Claim ID',
            'B9' => 'Employee Name',
            'C9' => 'Role',
            'D9' => 'Category',
            'E9' => 'Transaction Date',
            'F9' => 'Amount (MYR)',
            'G9' => 'Invoice Number',
            'H9' => 'Status',
            'I9' => 'Created Date',
            'J9' => 'Approved Date',
            'K9' => 'Has Attachment',
            'L9' => 'Notes'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Style headers
        $headerRange = 'A9:L9';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Data rows
        $row = 10;
        $totalAmount = 0;
        $statusCounts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
        $categoryCounts = [];
        $employeeCounts = [];
        
        while ($claim = $result->fetch_assoc()) {
            $sheet->setCellValue('A' . $row, $claim['claim_id']);
            $sheet->setCellValue('B' . $row, $claim['employee_name'] ?? 'Unknown Employee');
            $sheet->setCellValue('C' . $row, $claim['position'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, $claim['category']);
            $sheet->setCellValue('E' . $row, date('Y-m-d', strtotime($claim['transaction_date'])));
            $sheet->setCellValue('F' . $row, number_format($claim['amount'], 2));
            $sheet->setCellValue('G' . $row, $claim['invoice_number'] ?? 'N/A');
            $sheet->setCellValue('H' . $row, $claim['status']);
            $sheet->setCellValue('I' . $row, $claim['created_at'] ? date('Y-m-d H:i', strtotime($claim['created_at'])) : 'N/A');
            $sheet->setCellValue('J' . $row, $claim['approved_at'] ? date('Y-m-d H:i', strtotime($claim['approved_at'])) : 'N/A');
            $sheet->setCellValue('K' . $row, !empty($claim['attachment']) ? 'Yes' : 'No');
            $sheet->setCellValue('L' . $row, $claim['notes'] ?? '');
            
            // Color code status
            $statusColor = '';
            switch ($claim['status']) {
                case 'Approved':
                    $statusColor = '70AD47'; // Green
                    break;
                case 'Rejected':
                    $statusColor = 'C5504B'; // Red
                    break;
                case 'Pending':
                    $statusColor = 'FFC000'; // Orange
                    break;
            }
            
            if ($statusColor) {
                $sheet->getStyle('H' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($statusColor);
                $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            
            // Update counters
            $totalAmount += $claim['amount'];
            
            if (!isset($statusCounts[$claim['status']])) {
                $statusCounts[$claim['status']] = 0;
            }
            $statusCounts[$claim['status']]++;
            
            // Count by category
            if (!isset($categoryCounts[$claim['category']])) {
                $categoryCounts[$claim['category']] = 0;
            }
            $categoryCounts[$claim['category']]++;
            
            // Count by employee
            $empName = $claim['employee_name'] ?? 'Unknown Employee';
            if (!isset($employeeCounts[$empName])) {
                $employeeCounts[$empName] = ['count' => 0, 'total' => 0];
            }
            $employeeCounts[$empName]['count']++;
            $employeeCounts[$empName]['total'] += $claim['amount'];
            
            $row++;
        }
        
        // Summary section
        if ($row > 10) {
            $summaryRow = $row + 2;
            $sheet->setCellValue('A' . $summaryRow, 'REPORT SUMMARY');
            $sheet->mergeCells('A' . $summaryRow . ':D' . $summaryRow);
            $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A' . $summaryRow)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E7E6E6');
            
            $summaryRow += 2;
            
            // Basic stats
            $sheet->setCellValue('A' . $summaryRow, 'Total Claims:');
            $sheet->setCellValue('B' . $summaryRow, ($row - 10));
            $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
            
            $summaryRow++;
            $sheet->setCellValue('A' . $summaryRow, 'Total Amount:');
            $sheet->setCellValue('B' . $summaryRow, 'MYR ' . number_format($totalAmount, 2));
            $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
            
            $summaryRow++;
            $sheet->setCellValue('A' . $summaryRow, 'Average Claim:');
            $sheet->setCellValue('B' . $summaryRow, 'MYR ' . number_format($totalAmount / ($row - 10), 2));
            $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
            
            $summaryRow += 2;
            
            // Status breakdown
            $sheet->setCellValue('A' . $summaryRow, 'STATUS BREAKDOWN:');
            $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
            $summaryRow++;
            
            foreach ($statusCounts as $status => $count) {
                $percentage = round(($count / ($row - 10)) * 100, 1);
                $sheet->setCellValue('A' . $summaryRow, $status . ':');
                $sheet->setCellValue('B' . $summaryRow, $count);
                $sheet->setCellValue('C' . $summaryRow, "({$percentage}%)");
                $summaryRow++;
            }
            
            $summaryRow++;
            
            // Category breakdown
            $sheet->setCellValue('A' . $summaryRow, 'CATEGORY BREAKDOWN:');
            $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
            $summaryRow++;
            
            foreach ($categoryCounts as $category => $count) {
                $percentage = round(($count / ($row - 10)) * 100, 1);
                $sheet->setCellValue('A' . $summaryRow, $category . ':');
                $sheet->setCellValue('B' . $summaryRow, $count);
                $sheet->setCellValue('C' . $summaryRow, "({$percentage}%)");
                $summaryRow++;
            }
            
            // If specific employees selected, show employee breakdown
            if ($employee_filter === 'specific' && count($employeeCounts) <= 15) {
                $summaryRow++;
                $sheet->setCellValue('A' . $summaryRow, 'EMPLOYEE BREAKDOWN:');
                $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
                $summaryRow++;
                
                foreach ($employeeCounts as $employee => $data) {
                    $sheet->setCellValue('A' . $summaryRow, $employee . ':');
                    $sheet->setCellValue('B' . $summaryRow, $data['count'] . ' claims');
                    $sheet->setCellValue('C' . $summaryRow, 'MYR ' . number_format($data['total'], 2));
                    $summaryRow++;
                }
            }
        }
        
        // Auto-size columns
        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Add borders to data
        if ($row > 10) {
            $dataRange = 'A9:L' . ($row - 1);
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }
        
        // Generate filename with timestamp and filters
        $filename = 'Claims_Report_' . $start_date . '_to_' . $end_date;
        if ($employee_filter === 'specific') {
            $filename .= '_SelectedEmployees';
        }
        if ($report_status !== 'all') {
            $filename .= '_' . $report_status;
        }
        if ($report_category !== 'all') {
            $filename .= '_' . str_replace(' ', '_', $report_category);
        }
        $filename .= '_' . date('YmdHis') . '.xlsx';
        
        // Download file
        $writer = new Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        
        $writer->save('php://output');
        exit();
        
    } catch (Exception $e) {
        error_log("Excel generation error: " . $e->getMessage());
        $_SESSION['error_message'] = 'Error generating Excel file: ' . $e->getMessage();
        header('Location: R_claim.php');
        exit();
    }
    
} else {
    $_SESSION['error_message'] = 'Invalid request method.';
    header('Location: R_claim.php');
    exit();
}
?>