<?php
require('database.php');
require('session.php');

// Process payroll form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = (int)$_POST['employee_id'];
    $allowance = (float)$_POST['allowance_input'];
    $overtime = (float)$_POST['overtime_input'];

    // Other payroll data fields
    $basic_salary = (float)$_POST['basic_salary'];
    $epf = (float)$_POST['epf'];
    $socso = (float)$_POST['socso'];
    $eis = (float)$_POST['eis'];
    $pcb = (float)$_POST['pcb'];

    // Calculate total deductions and net pay
    $total_deductions = $epf + $socso + $eis + $pcb;
    $net_pay = $basic_salary + $allowance + $overtime - $total_deductions;

    // Prepare the SQL query
    $query = "INSERT INTO payroll_transactions (employee_id, pay_period_start, pay_period_end, payment_date, basic_salary, allowances, deductions, tax_amount, epf_amount, socso_amount, eis_amount, overtime_pay, total_claims, net_pay, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($con, $query);
    
    $pay_period_start = date("Y-m-01", strtotime($_POST['year']."-".$_POST['month']."-01"));
    $pay_period_end = date("Y-m-t", strtotime($_POST['year']."-".$_POST['month']."-01"));
    $payment_date = date("Y-m-d");

    mysqli_stmt_bind_param($stmt, 'isssddddddddds', $employee_id, $pay_period_start, $pay_period_end, $payment_date, 
                           $basic_salary, $allowance, $total_deductions, $pcb, $epf, $socso, $eis, $overtime, 0.00, $net_pay, 'processed');

    if (mysqli_stmt_execute($stmt)) {
        echo "Payroll saved successfully.";
    } else {
        echo "Error saving payroll: " . mysqli_error($con);
    }

    mysqli_stmt_close($stmt);
}
?>
