<?php
// Function to calculate overtime details (you'll need to implement this)
function calculateOvertimeDetails($overtime_pay) {
    // Replace this with your actual overtime calculation logic
    if ($overtime_pay > 0) {
        return "RM15.00/hour × 2 hours = RM" . number_format($overtime_pay, 2);
    }
    return "No overtime";
}

// database connection
require('database.php');
require('session.php');

// FPDF library
require('fpdf/fpdf.php');
require 'vendor/autoload.php';  // this loads PHPMailer automatically
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get month and year from GET (or default to current)
$month = isset($_GET['month']) ? (int)trim($_GET['month']) : date('m');
$year  = isset($_GET['year']) ? (int)trim($_GET['year']) : date('Y');

// Validate month/year
if ($month < 1 || $month > 12) {
    die("Error: Invalid month.");
}

// Test database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Query to check if all employees are confirmed
$query = "SELECT COUNT(*) AS unconfirmed_count 
          FROM payroll_transactions
          WHERE MONTH(pay_period_start) = ? AND YEAR(pay_period_start) = ? AND status != 'confirmed'";

$stmt = mysqli_prepare($conn, $query);
if ($stmt === false) {
    die("Error preparing the query: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "ii", $month, $year);
if (!mysqli_stmt_execute($stmt)) {
    die("Error executing query: " . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($row['unconfirmed_count'] > 0) {
    die("Cannot send payslips. Not all employees are confirmed for this month.");
}

// Fetch all employees for this month with their details
$query2 = "SELECT p.transaction_id, p.employee_id, pi.full_name, pi.email, pi.ic, 
                  p.pay_period_start, p.pay_period_end,
                  p.basic_salary, p.overtime_pay, p.deductions, p.tax_amount, p.epf_amount, p.socso_amount, p.eis_amount,
                  p.net_pay, p.allowances, ed.employment_position, ed.employment_department
           FROM payroll_transactions p 
           JOIN personal_information pi ON p.employee_id = pi.personal_id
           LEFT JOIN employment_detail ed ON p.employee_id = ed.employment_id
           WHERE MONTH(p.pay_period_start) = ? AND YEAR(p.pay_period_start) = ? AND p.status = 'confirmed'";

$stmt2 = mysqli_prepare($conn, $query2);
if ($stmt2 === false) {
    die("Error preparing the second query: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt2, "ii", $month, $year);
if (!mysqli_stmt_execute($stmt2)) {
    die("Error executing second query: " . mysqli_stmt_error($stmt2));
}

$result2 = mysqli_stmt_get_result($stmt2);

if (mysqli_num_rows($result2) == 0) {
    mysqli_stmt_close($stmt2);
    die("No payroll records found for this month.");
}

$email_results = [];
$success_count = 0;
$error_count = 0;

while ($emp = mysqli_fetch_assoc($result2)) {
    try {
        // Calculate values
        $gross_pay = $emp['basic_salary'] + $emp['overtime_pay'] + ($emp['allowances'] ?? 0);
        $employee_epf = $emp['epf_amount'];
        $employee_socso = $emp['socso_amount'] * 0.5; // Employee pays half of SOCSO
        $employee_eis = $emp['eis_amount'] * 0.5; // Employee pays half of EIS
        $total_deductions = $employee_epf + $employee_socso + $employee_eis + $emp['tax_amount'];
        
        // Employer contributions
        $employer_epf = $employee_epf * 1.3; // Employer contributes more to EPF
        $employer_socso = $emp['socso_amount'] * 0.5; // Employer pays half
        $employer_eis = $emp['eis_amount'] * 0.5; // Employer pays half
        $total_contributions = $employer_epf + $employer_socso + $employer_eis;
        
        $overtime_details = calculateOvertimeDetails($emp['overtime_pay']);
        $month_name = date("M", mktime(0, 0, 0, $month, 10));
        
        // Generate PDF
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetMargins(20, 20, 20);
        
        // Company Name - Left aligned, larger font
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 12, 'Blush Events', 0, 1, 'L');
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
        $pdf->SetDrawColor(45, 123, 251); // Blue border
        $pdf->SetFillColor(255, 255, 255); // White background
        $pdf->SetTextColor(45, 123, 251); // Blue text
        $pdf->Cell(60, 20, 'NET PAY', 1, 2, 'C', true);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetX(130);
        $pdf->SetTextColor(0, 0, 0); // Reset text color to black for rest of document
        $pdf->Cell(60, 15, 'RM ' . number_format($emp['net_pay'], 2), 1, 1, 'C', true);
        $pdf->SetDrawColor(0, 0, 0); // Reset border color to black
 
        
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
        $pdf->Cell(35, 6, 'RM ' . number_format($emp['basic_salary'], 2), 0, 1, 'R');
        
        $pdf->Cell(60, 6, 'Overtime', 0, 0, 'L');
        $pdf->Cell(35, 6, 'RM ' . number_format($emp['overtime_pay'], 2), 0, 1, 'R');
        
        if (($emp['allowances'] ?? 0) > 0) {
            $pdf->Cell(60, 6, 'Allowances', 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($emp['allowances'], 2), 0, 1, 'R');
        }
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 6, 'Gross Pay', 0, 0, 'L');
        $pdf->Cell(35, 6, 'RM ' . number_format($gross_pay, 2), 0, 1, 'R');
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(95, 6, "Overtime Normal (rate/hours/amount): $overtime_details", 0, 1, 'L');
        $pdf->Ln(3);
        
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
        $mail->addAddress($emp['email'], $emp['full_name']);
        $mail->Subject = "Payslip for $month_name $year";
        $mail->Body = "Dear {$emp['full_name']},\n\nPlease find attached your payslip for $month_name $year.\n\nRegards,\nPayroll Department";
        $mail->addAttachment($filename);
        
        // Send the email
        if ($mail->send()) {
            $success_count++;
            $email_results[] = "✓ Sent to {$emp['full_name']} ({$emp['email']})";
        } else {
            $error_count++;
            $email_results[] = "✗ Failed to send to {$emp['full_name']} ({$emp['email']}): " . $mail->ErrorInfo;
            error_log("Failed to send payslip to {$emp['email']}: " . $mail->ErrorInfo);
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

// Start the session for message handling
session_start();

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

// Store detailed results for debugging if needed
$_SESSION['email_results'] = $email_results;

$redirect_url = "view_report.php?month=$month&year=$year&sent=1";
header("Location: $redirect_url");
exit();
exit();
?>