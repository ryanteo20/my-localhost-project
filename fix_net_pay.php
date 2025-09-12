<?php
require('database.php');
require('session.php');

// Check if user is authorized
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employer') {
    header("Location: index2.php");
    exit("Access Denied: Employers only.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = (int)$_POST['employee_id'];
    $month = (int)$_POST['month'];
    $year = (int)$_POST['year'];
    
    // Validate inputs
    if ($employee_id <= 0 || $month < 1 || $month > 12 || $year < 2000) {
        echo "Error: Invalid parameters";
        exit;
    }

    // Get current payroll data for this employee
    $select_query = "
        SELECT basic_salary, allowances, overtime_pay, total_claims,
               epf_amount, socso_amount, tax_amount, eis_amount, net_pay
        FROM payroll_transactions 
        WHERE employee_id = ? 
        AND MONTH(pay_period_start) = ? 
        AND YEAR(pay_period_start) = ?
    ";
    
    $select_stmt = mysqli_prepare($conn, $select_query);
    mysqli_stmt_bind_param($select_stmt, "iii", $employee_id, $month, $year);
    mysqli_stmt_execute($select_stmt);
    $result = mysqli_stmt_get_result($select_stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Calculate correct net pay including allowances
        $correct_net_pay = floatval($row['basic_salary']) + 
                          floatval($row['allowances']) +           // Make sure allowances are included
                          floatval($row['overtime_pay']) + 
                          floatval($row['total_claims']) - 
                          floatval($row['epf_amount']) - 
                          floatval($row['socso_amount']) - 
                          floatval($row['tax_amount']) - 
                          floatval($row['eis_amount']);
        
        $old_net_pay = floatval($row['net_pay']);
        
        // Debug information
        echo "<!-- DEBUG: Employee ID: $employee_id -->";
        echo "<!-- DEBUG: Basic Salary: " . $row['basic_salary'] . " -->";
        echo "<!-- DEBUG: Allowances: " . $row['allowances'] . " -->";
        echo "<!-- DEBUG: Overtime: " . $row['overtime_pay'] . " -->";
        echo "<!-- DEBUG: Claims: " . $row['total_claims'] . " -->";
        echo "<!-- DEBUG: EPF: " . $row['epf_amount'] . " -->";
        echo "<!-- DEBUG: SOCSO: " . $row['socso_amount'] . " -->";
        echo "<!-- DEBUG: Tax: " . $row['tax_amount'] . " -->";
        echo "<!-- DEBUG: EIS: " . $row['eis_amount'] . " -->";
        echo "<!-- DEBUG: Old Net Pay: $old_net_pay -->";
        echo "<!-- DEBUG: Calculated Net Pay: $correct_net_pay -->";
        
        // Update the net pay in the database
        $update_query = "
            UPDATE payroll_transactions 
            SET net_pay = ? 
            WHERE employee_id = ? 
            AND MONTH(pay_period_start) = ? 
            AND YEAR(pay_period_start) = ?
        ";
        
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "diii", $correct_net_pay, $employee_id, $month, $year);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($update_stmt);
            if ($affected_rows > 0) {
                $difference = $correct_net_pay - $old_net_pay;
                echo "Net pay fixed successfully - Updated from RM " . number_format($old_net_pay, 2) . 
                     " to RM " . number_format($correct_net_pay, 2) . 
                     " (Difference: +RM " . number_format($difference, 2) . ")";
            } else {
                echo "No changes needed - Net pay already correct";
            }
        } else {
            echo "Error fixing net pay: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($update_stmt);
    } else {
        echo "Error: Payroll record not found for Employee ID: $employee_id, Month: $month, Year: $year";
    }
    
    mysqli_stmt_close($select_stmt);
} else {
    echo "Error: Invalid request method";
}
?>