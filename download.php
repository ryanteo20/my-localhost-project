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
    
    // Build the query based on selections
    $query = "SELECT c.*, pi.full_name, pi.position 
              FROM claims c 
              JOIN personal_information pi ON c.employee_id = pi.personal_id 
              WHERE c.transaction_date BETWEEN ? AND ?";
    
    $params = [$start_date, $end_date];
    $types = "ss";
    
    // Add employee filter
    if ($employee_filter === 'specific' && isset($_POST['selected_employees']) && !empty($_POST['selected_employees'])) {
        $selected_employees = $_POST['selected_employees'];
        $placeholders = str_repeat('?,', count($selected_employees) - 1) . '?';
        $query .= " AND c.employee_id IN ($placeholders)";
        $params = array_merge($params, $selected_employees);
        $types .= str_repeat('i', count($selected_employees));
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
    
    $query .= " ORDER BY c.transaction_date DESC, pi.full_name ASC";
    
    // Prepare and execute query
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
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
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Report Info
        $sheet->setCellValue('A2', 'Report Generated: ' . date('Y-m-d H:i:s'));
        $sheet->setCellValue('A3', 'Date Range: ' . $start_date . ' to ' . $end_date);
        
        // Employee selection info
        if ($employee_filter === 'all') {
            $sheet->setCellValue('A4', 'Employees: All Employees');
        } else {
            $selected_count = isset($_POST['selected_employees']) ? count($_POST['selected_employees']) : 0;
            $sheet->setCellValue('A4', 'Employees: ' . $selected_count . ' Selected Employee(s)');
        }
        
        // Status and category info
        $sheet->setCellValue('A5', 'Status Filter: ' . ($report_status === 'all' ? 'All Statuses' : $report_status));
        $sheet->setCellValue('A6', 'Category Filter: ' . ($report_category === 'all' ? 'All Categories' : $report_category));
        
        // Headers
        $headers = [
            'A8' => 'Claim ID',
            'B8' => 'Employee Name',
            'C8' => 'Position',
            'D8' => 'Category',
            'E8' => 'Transaction Date',
            'F8' => 'Amount (MYR)',
            'G8' => 'Receipt No',
            'H8' => 'Status',
            'I8' => 'Submitted Date',
            'J8' => 'Notes'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Style headers
        $headerRange = 'A8:J8';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Data rows
        $row = 9;
        $totalAmount = 0;
        $statusCounts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
        
        while ($claim = $result->fetch_assoc()) {
            $sheet->setCellValue('A' . $row, $claim['id']);
            $sheet->setCellValue('B' . $row, $claim['full_name']);
            $sheet->setCellValue('C' . $row, $claim['position'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, $claim['category']);
            $sheet->setCellValue('E' . $row, $claim['transaction_date']);
            $sheet->setCellValue('F' . $row, number_format($claim['amount'], 2));
            $sheet->setCellValue('G' . $row, $claim['invoice_number'] ?? 'N/A');
            $sheet->setCellValue('H' . $row, $claim['status']);
            $sheet->setCellValue('I' . $row, date('Y-m-d', strtotime($claim['submitted_at'])));
            $sheet->setCellValue('J' . $row, $claim['notes'] ?? '');
            
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
            }
            
            $totalAmount += $claim['amount'];
            $statusCounts[$claim['status']]++;
            $row++;
        }
        
        // Summary section
        if ($row > 9) {
            $summaryRow = $row + 1;
            $sheet->setCellValue('A' . $summaryRow, 'SUMMARY');
            $sheet->mergeCells('A' . $summaryRow . ':B' . $summaryRow);
            $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true)->setSize(12);
            
            $summaryRow++;
            $sheet->setCellValue('A' . $summaryRow, 'Total Claims:');
            $sheet->setCellValue('B' . $summaryRow, ($row - 9));
            
            $summaryRow++;
            $sheet->setCellValue('A' . $summaryRow, 'Total Amount:');
            $sheet->setCellValue('B' . $summaryRow, 'MYR ' . number_format($totalAmount, 2));
            
            $summaryRow++;
            $sheet->setCellValue('A' . $summaryRow, 'Pending:');
            $sheet->setCellValue('B' . $summaryRow, $statusCounts['Pending']);
            
            $summaryRow++;
            $sheet->setCellValue('A' . $summaryRow, 'Approved:');
            $sheet->setCellValue('B' . $summaryRow, $statusCounts['Approved']);
            
            $summaryRow++;
            $sheet->setCellValue('A' . $summaryRow, 'Rejected:');
            $sheet->setCellValue('B' . $summaryRow, $statusCounts['Rejected']);
        }
        
        // Auto-size columns
        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Add borders to data
        if ($row > 9) {
            $dataRange = 'A8:J' . ($row - 1);
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
            $filename .= '_' . $report_category;
        }
        $filename .= '_' . date('YmdHis') . '.xlsx';
        
        // Download file
        $writer = new Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit();
        
    } else {
        $_SESSION['error_message'] = 'Error generating report: ' . $conn->error;
        header('Location: R_claim.php');
        exit();
    }
} else {
    header('Location: R_claim.php');
    exit();
}
?>