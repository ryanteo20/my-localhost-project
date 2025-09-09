<?php
require('database.php');
require('session.php');

// Check if user is logged in and has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employer') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = (int)$_POST['employee_id'];
    $pay_period_start = $_POST['pay_period_start'];
    $pay_period_end = $_POST['pay_period_end'];
    $payment_date = $_POST['payment_date'];
    $basic_salary = (float)$_POST['basic_salary'];
    $allowances = (float)$_POST['allowances'];
    $deductions = (float)$_POST['deductions'];
    $tax_amount = (float)$_POST['tax_amount'];
    $epf_amount = (float)$_POST['epf_amount'];
    $socso_amount = (float)$_POST['socso_amount'];
    $eis_amount = (float)$_POST['eis_amount'];
    $overtime_pay = (float)$_POST['overtime_pay'];
    $total_claims = (float)$_POST['total_claims'];
    $net_pay = (float)$_POST['net_pay'];
    $status = $_POST['status'];
    $created_at = $_POST['created_at'];
    $updated_at = $_POST['updated_at'];

    // Insert payroll data into the database
    $query = "INSERT INTO payroll_transactions 
                (employee_id, pay_period_start, pay_period_end, payment_date, basic_salary, allowances, deductions, 
                 tax_amount, epf_amount, socso_amount, eis_amount, overtime_pay, total_claims, net_pay, status, 
                 created_at, updated_at) 
              VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'isssddddddddddsss', $employee_id, $pay_period_start, $pay_period_end, $payment_date, 
                           $basic_salary, $allowances, $deductions, $tax_amount, $epf_amount, $socso_amount, $eis_amount, 
                           $overtime_pay, $total_claims, $net_pay, $status, $created_at, $updated_at);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Payroll data saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error saving payroll data: ' . mysqli_error($conn)]);
    }

    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
