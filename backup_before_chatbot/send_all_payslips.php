<?php
// database connection
require('database.php');
require('session.php');

// FPDF library
require('fpdf/fpdf.php');
require 'vendor/autoload.php';  // this loads PHPMailer automatically
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is authorized
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employer') {
    $_SESSION['message'] = "Error: Access Denied - Employers only";
    $_SESSION['message_type'] = 'error';
    header("Location: view_report.php?month=" . ($_GET['month'] ?? date('m')) . "&year=" . ($_GET['year'] ?? date('Y')));
    exit;
}

// Get month and year from GET
$month = isset($_GET['month']) ? (int)trim($_GET['month']) : date('m');
$year  = isset($_GET['year']) ? (int)trim($_GET['year']) : date('Y');

// Validate month/year
if ($month < 1 || $month > 12) {
    $_SESSION['message'] = "Error: Invalid month.";
    $_SESSION['message_type'] = 'error';
    header("Location: view_report.php?month=" . date('m') . "&year=" . date('Y'));
    exit;
}

// Test database connection
if (!$conn) {
    $_SESSION['message'] = "Database connection failed: " . mysqli_connect_error();
    $_SESSION['message_type'] = 'error';
    header("Location: view_report.php?month=$month&year=$year");
    exit;
}

// Query to check if all employees are confirmed
$query = "SELECT COUNT(*) AS unconfirmed_count 
          FROM payroll_transactions
          WHERE MONTH(pay_period_start) = ? AND YEAR(pay_period_start) = ? AND status != 'confirmed'";

$stmt = mysqli_prepare($conn, $query);
if ($stmt === false) {
    $_SESSION['message'] = "Error preparing the query: " . mysqli_error($conn);
    $_SESSION['message_type'] = 'error';
    header("Location: view_report.php?month=$month&year=$year");
    exit;
}

mysqli_stmt_bind_param($stmt, "ii", $month, $year);
if (!mysqli_stmt_execute($stmt)) {
    $_SESSION['message'] = "Error executing query: " . mysqli_stmt_error($stmt);
    $_SESSION['message_type'] = 'error';
    header("Location: view_report.php?month=$month&year=$year");
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($row['unconfirmed_count'] > 0) {
    $_SESSION['message'] = "Cannot send payslips. Not all employees are confirmed for this month.";
    $_SESSION['message_type'] = 'error';
    header("Location: view_report.php?month=$month&year=$year");
    exit;
}

// Fetch ALL employees for this month with their details INCLUDING CLAIMS
$query2 = "SELECT p.transaction_id, p.employee_id, pi.full_name, pi.email, pi.ic, 
                  p.pay_period_start, p.pay_period_end,
                  p.basic_salary, p.overtime_pay, p.deductions, p.tax_amount, p.epf_amount, p.socso_amount, p.eis_amount,
                  p.net_pay, p.allowances, p.total_claims, ed.employment_position, ed.employment_department
           FROM payroll_transactions p 
           JOIN personal_information pi ON p.employee_id = pi.personal_id
           LEFT JOIN employment_detail ed ON p.employee_id = ed.employment_id
           WHERE MONTH(p.pay_period_start) = ? AND YEAR(p.pay_period_start) = ? AND p.status = 'confirmed'";

$stmt2 = mysqli_prepare($conn, $query2);
if ($stmt2 === false) {
    $_SESSION['message'] = "Error preparing the second query: " . mysqli_error($conn);
    $_SESSION['message_type'] = 'error';
    header("Location: view_report.php?month=$month&year=$year");
    exit;
}

mysqli_stmt_bind_param($stmt2, "ii", $month, $year);
if (!mysqli_stmt_execute($stmt2)) {
    $_SESSION['message'] = "Error executing second query: " . mysqli_stmt_error($stmt2);
    $_SESSION['message_type'] = 'error';
    header("Location: view_report.php?month=$month&year=$year");
    exit;
}

$result2 = mysqli_stmt_get_result($stmt2);

if (mysqli_num_rows($result2) == 0) {
    mysqli_stmt_close($stmt2);
    $_SESSION['message'] = "No payroll records found for this month.";
    $_SESSION['message_type'] = 'error';
    header("Location: view_report.php?month=$month&year=$year");
    exit;
}

$email_results = [];
$success_count = 0;
$error_count = 0;

// Loop through ALL employees
while ($emp = mysqli_fetch_assoc($result2)) {
    try {
        // Skip employees without email addresses
        $employee_email = trim($emp['email']);
        if (empty($employee_email)) {
            $error_count++;
            $email_results[] = "✗ Skipped {$emp['full_name']} - No email address";
            continue;
        }

        // Calculate values including claims
        $basic_salary = $emp['basic_salary'];
        $allowances = $emp['allowances'] ?? 0;
        $overtime_pay = $emp['overtime_pay'] ?? 0;
        $total_claims = $emp['total_claims'] ?? 0;
        
        // Calculate gross pay including claims
        $gross_pay = $basic_salary + $allowances + $overtime_pay + $total_claims;
        
        $employee_epf = $emp['epf_amount'];
        $employee_socso = $emp['socso_amount'];
        $employee_eis = $emp['eis_amount'];
        $total_deductions = $employee_epf + $employee_socso + $employee_eis + $emp['tax_amount'];
        
        // Employer contributions
        $employer_epf = $basic_salary * 0.13;
        $employer_socso = $basic_salary * 0.0175;
        $employer_eis = $basic_salary * 0.002;
        $total_contributions = $employer_epf + $employer_socso + $employer_eis;
        
        $month_name = date("F", mktime(0, 0, 0, $month, 10));
        
        // Generate PDF
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
        
        // Add claims to the payslip
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
        
        // Send Email
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'smeasyhr@gmail.com';
        $mail->Password = 'jpkbgcttbogxtleu';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('smeasyhr@gmail.com', 'Payroll Department');
        $mail->addAddress($employee_email, $emp['full_name']);
        $mail->Subject = "Payslip for $month_name $year";
        $mail->Body = "Dear {$emp['full_name']},\n\nPlease find attached your payslip for $month_name $year.\n\nRegards,\nPayroll Department";
        $mail->addAttachment($filename);
        
        // Send the email
        if ($mail->send()) {
            $success_count++;
            $email_results[] = "✓ Sent to {$emp['full_name']} ({$employee_email})";
        } else {
            $error_count++;
            $email_results[] = "✗ Failed to send to {$emp['full_name']} ({$employee_email}): " . $mail->ErrorInfo;
            error_log("Failed to send payslip to {$employee_email}: " . $mail->ErrorInfo);
        }
        
        // Delete temporary PDF
        if (file_exists($filename)) {
            unlink($filename);
        }
        
        // Clear PHPMailer for next iteration
        $mail->clearAddresses();
        $mail->clearAttachments();
        
    } catch (Exception $e) {
        $error_count++;
        $email_results[] = "✗ Error processing {$emp['full_name']}: " . $e->getMessage();
        error_log("Error processing payslip for employee {$emp['employee_id']}: " . $e->getMessage());
        
        // Clean up any temporary file
        if (isset($filename) && file_exists($filename)) {
            unlink($filename);
        }
    }
}

mysqli_stmt_close($stmt2);

// Set session messages and redirect back to view_report.php
if ($success_count > 0 && $error_count == 0) {
    $_SESSION['message'] = "All payslips sent successfully! ($success_count emails sent)";
    $_SESSION['message_type'] = 'success';
} elseif ($success_count > 0 && $error_count > 0) {
    $_SESSION['message'] = "Partially completed: $success_count emails sent, $error_count failed.";
    $_SESSION['message_type'] = 'warning';
} else {
    $_SESSION['message'] = "Failed to send payslips. All $error_count attempts failed.";
    $_SESSION['message_type'] = 'error';
}

// Store detailed results for potential display
$_SESSION['email_results'] = $email_results;

// Redirect back to view_report.php
header("Location: view_report.php?month=$month&year=$year&sent=1");
exit();
?>