<?php
require('database.php');
require('session.php');

// FPDF library and PHPMailer
require('fpdf/fpdf.php');
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is authorized
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employer') {
    echo "Error: Access Denied - Employers only";
    exit;
}
// FIXED: Accept both GET and POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get parameters from either POST or GET
    $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : (int)$_GET['employee_id'];
    $month = isset($_POST['month']) ? (int)$_POST['month'] : (int)$_GET['month'];
    $year = isset($_POST['year']) ? (int)$_POST['year'] : (int)$_GET['year'];
    
    // Validate inputs
    if ($employee_id <= 0 || $month < 1 || $month > 12 || $year < 2000) {
        echo "Error: Invalid parameters - Employee ID: $employee_id, Month: $month, Year: $year";
        exit;
    }

    // Test database connection
    if (!$conn) {
        echo "Error: Database connection failed - " . mysqli_connect_error();
        exit;
    }

    // Query for specific employee (including claims)
    $query = "SELECT p.transaction_id, p.employee_id, pi.full_name, pi.email, pi.ic, 
                     p.pay_period_start, p.pay_period_end,
                     p.basic_salary, p.overtime_pay, p.deductions, p.tax_amount, p.epf_amount, p.socso_amount, p.eis_amount,
                     p.net_pay, p.allowances, p.total_claims, ed.employment_position, ed.employment_department, p.status
              FROM payroll_transactions p 
              JOIN personal_information pi ON p.employee_id = pi.personal_id
              LEFT JOIN employment_detail ed ON p.employee_id = ed.employment_id
              WHERE p.employee_id = ? 
              AND MONTH(p.pay_period_start) = ? 
              AND YEAR(p.pay_period_start) = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        echo "Error: Failed to prepare query - " . mysqli_error($conn);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($emp = mysqli_fetch_assoc($result)) {
        $employee_name = $emp['full_name'];
        $employee_email = trim($emp['email']);
        
        // Check if confirmed
        if ($emp['status'] !== 'confirmed') {
            echo "Error: Payroll status is '{$emp['status']}'. Only confirmed payrolls can be sent.";
            mysqli_stmt_close($stmt);
            exit;
        }
        
        if (empty($employee_email)) {
            echo "Error: No email address found for employee ID $employee_id ($employee_name)";
            mysqli_stmt_close($stmt);
            exit;
        }
        
        try {
            // FIXED: Use database values directly and include claims
            $basic_salary = $emp['basic_salary'];
            $allowances = $emp['allowances'] ?? 0;
            $overtime_pay = $emp['overtime_pay'] ?? 0;
            $total_claims = $emp['total_claims'] ?? 0;
            
            // FIXED: Calculate gross pay including claims
            $gross_pay = $basic_salary + $allowances + $overtime_pay + $total_claims;
            
            $employee_epf = $emp['epf_amount'];
            $employee_socso = $emp['socso_amount']; // Use database value directly
            $employee_eis = $emp['eis_amount']; // Use database value directly
            $total_deductions = $employee_epf + $employee_socso + $employee_eis + $emp['tax_amount'];
            
            // Employer contributions (calculated from basic salary)
            $employer_epf = $basic_salary * 0.13;
            $employer_socso = $basic_salary * 0.0175;
            $employer_eis = $basic_salary * 0.002;
            $total_contributions = $employer_epf + $employer_socso + $employer_eis;
            
            $month_name = date("F", mktime(0, 0, 0, $month, 10)); // Full month name
            
            // Generate PDF (same layout as download)
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetMargins(20, 20, 20);
            
            // Company Name
            $pdf->SetFont('Arial', 'B', 18);
            $pdf->Cell(0, 12, 'SMEasyHR', 0, 1, 'L');
            $pdf->Ln(5);
            
            // Employee info and NET PAY section
            $pdf->SetFont('Arial', '', 11);
            
            // Left side - Employee details
            $pdf->SetXY(20, 35);
            $pdf->Cell(100, 6, $emp['full_name'] . ' (Employee No: ' . $emp['employee_id'] . ')', 0, 1, 'L');
            $pdf->Cell(100, 6, "Period: $month_name $year", 0, 1, 'L');
            $pdf->Cell(100, 6, 'Position: ' . ($emp['employment_position'] ?? 'Not specified'), 0, 1, 'L');
            $pdf->Cell(100, 6, 'Dept: ' . ($emp['employment_department'] ?? 'Not specified'), 0, 1, 'L');
            $pdf->Cell(100, 6, 'IC/Passport: ' . ($emp['ic'] ?? 'Not provided'), 0, 1, 'L');
            
            // Right side - NET PAY box
            $pdf->SetXY(130, 35);
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->SetDrawColor(45, 123, 251);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(45, 123, 251);
            $pdf->Cell(60, 20, 'NET PAY', 1, 2, 'C', true);
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->SetX(130);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(60, 15, 'RM ' . number_format($emp['net_pay'], 2), 1, 1, 'C', true);
            $pdf->SetDrawColor(0, 0, 0);
     
            // Reset position for main content
            $pdf->SetY(90);
            $pdf->SetFont('Arial', '', 10);
            
            // Employee Earnings/Reimbursements section
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(95, 8, 'Employee Earnings/Reimbursements', 0, 1, 'L');
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 6, '', 0, 0);
            $pdf->Cell(35, 6, 'Current', 0, 1, 'R');
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(60, 6, 'Basic', 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($basic_salary, 2), 0, 1, 'R');
            
            if ($allowances > 0) {
                $pdf->Cell(60, 6, 'Allowances', 0, 0, 'L');
                $pdf->Cell(35, 6, 'RM ' . number_format($allowances, 2), 0, 1, 'R');
            }
            
            if ($overtime_pay > 0) {
                $pdf->Cell(60, 6, 'Overtime', 0, 0, 'L');
                $pdf->Cell(35, 6, 'RM ' . number_format($overtime_pay, 2), 0, 1, 'R');
            }
            
            // FIXED: Add claims to the payslip
            if ($total_claims > 0) {
                $pdf->Cell(60, 6, 'Claims', 0, 0, 'L');
                $pdf->Cell(35, 6, 'RM ' . number_format($total_claims, 2), 0, 1, 'R');
            }
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 6, 'Gross Pay', 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($gross_pay, 2), 0, 1, 'R');
            
            // Employee Deductions section
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(95, 8, 'Employee Deductions', 0, 1, 'L');
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 6, '', 0, 0);
            $pdf->Cell(35, 6, 'Current', 0, 1, 'R');
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(60, 6, 'Employee EPF', 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($employee_epf, 2), 0, 1, 'R');
            
            $pdf->Cell(60, 6, 'Employee SOCSO', 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($employee_socso, 2), 0, 1, 'R');
            
            $pdf->Cell(60, 6, 'Employee EIS', 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($employee_eis, 2), 0, 1, 'R');
            
            if ($emp['tax_amount'] > 0) {
                $pdf->Cell(60, 6, 'Income Tax', 0, 0, 'L');
                $pdf->Cell(35, 6, 'RM ' . number_format($emp['tax_amount'], 2), 0, 1, 'R');
            }
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 6, 'Total Deductions', 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($total_deductions, 2), 0, 1, 'R');
            $pdf->Ln(3);
            
            // Company Contributions section
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(95, 8, 'Company Contributions', 0, 1, 'L');
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 6, '', 0, 0);
            $pdf->Cell(35, 6, 'Current', 0, 1, 'R');
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(60, 6, "Employer EPF", 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($employer_epf, 2), 0, 1, 'R');
            
            $pdf->Cell(60, 6, "Employer SOCSO", 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($employer_socso, 2), 0, 1, 'R');
            
            $pdf->Cell(60, 6, "Employer EIS", 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($employer_eis, 2), 0, 1, 'R');
        
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 6, 'Total Contributions', 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($total_contributions, 2), 0, 1, 'R');
            
            // Footer
            $pdf->SetY(-40);
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(0, 6, 'This payslip is computer generated. No signature is required.', 0, 1, 'C');
            $pdf->Cell(0, 6, 'Printed on: ' . date('d/m/Y'), 0, 1, 'C');
            
            // Save PDF temporarily
            $filename = "payslip_{$emp['employee_id']}_{$month}_{$year}.pdf";
            $pdf->Output('F', $filename);
            
            // Send Email (same configuration as before)
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'smeasyhr@gmail.com';
            $mail->Password = 'jpkbgcttbogxtleu';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            $mail->setFrom('smeasyhr@gmail.com', 'Payroll Department');
            $mail->addAddress($employee_email, $employee_name);
            $mail->Subject = "Payslip for $month_name $year";
            $mail->Body = "Dear {$employee_name},\n\nPlease find attached your payslip for $month_name $year.\n\nRegards,\nPayroll Department";
            $mail->addAttachment($filename);
            
            // Send the email
            if ($mail->send()) {
                echo "success - Payslip sent successfully to $employee_name at $employee_email";
            } else {
                echo "Error: Failed to send email to $employee_email. Error: " . $mail->ErrorInfo;
            }
            
            // Delete temporary PDF
            if (file_exists($filename)) {
                unlink($filename);
            }
            
        } catch (Exception $e) {
            echo "Error: Failed to process payslip - " . $e->getMessage();
            
            // Clean up any temporary file
            if (isset($filename) && file_exists($filename)) {
                unlink($filename);
            }
        }
    } else {
        echo "Error: No payroll record found for Employee ID $employee_id for " . date('F', mktime(0, 0, 0, $month, 10)) . " $year";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Error: Invalid request method - Expected GET or POST, received " . $_SERVER['REQUEST_METHOD'];
}
?>