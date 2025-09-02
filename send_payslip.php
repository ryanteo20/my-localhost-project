<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection and session
require('database.php');
require('session.php');

// Check if the user is logged in
if (!isset($_SESSION['ID'])) {
    die("You must be logged in to view your payslip.");
}

// Get the logged-in user's ID
$employee_id = $_SESSION['ID'];

// Get month and year from GET parameters (default to current month and year)
$month = isset($_GET['month']) ? (int)trim($_GET['month']) : date('m');
$year  = isset($_GET['year']) ? (int)trim($_GET['year']) : date('Y');

// Validate month/year
if ($month < 1 || $month > 12) {
    die("Error: Invalid month.");
}

// Query to fetch payroll transaction for the logged-in user for the given month and year
$query = "SELECT p.transaction_id, p.employee_id, pi.full_name, pi.email, pi.ic, 
                  p.pay_period_start, p.pay_period_end,
                  p.basic_salary, p.overtime_pay, p.deductions, p.tax_amount, p.epf_amount, p.socso_amount, p.eis_amount,
                  p.net_pay, p.allowances, ed.employment_position, ed.employment_department
           FROM payroll_transactions p 
           JOIN personal_information pi ON p.employee_id = pi.personal_id
           LEFT JOIN employment_detail ed ON p.employee_id = ed.employment_id
           WHERE p.employee_id = ? AND MONTH(p.pay_period_start) = ? AND YEAR(p.pay_period_start) = ? AND p.status = 'confirmed'";

$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Check if results are found
if (mysqli_num_rows($result) == 0) {
    die("No payroll records found for this month.");
}

$emp = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Calculate totals
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

// Prepare data to display on the webpage
$payroll_data = [
    'full_name' => $emp['full_name'],
    'employee_id' => $emp['employee_id'],
    'position' => $emp['employment_position'] ?? 'Not specified',
    'department' => $emp['employment_department'] ?? 'Not specified',
    'ic' => $emp['ic'] ?? 'Not provided',
    'pay_period_start' => $emp['pay_period_start'],
    'pay_period_end' => $emp['pay_period_end'],
    'net_pay' => number_format($emp['net_pay'], 2),
    'basic_salary' => number_format($emp['basic_salary'], 2),
    'overtime_pay' => number_format($emp['overtime_pay'], 2),
    'allowances' => number_format($emp['allowances'], 2),
    'gross_pay' => number_format($gross_pay, 2),
    'total_deductions' => number_format($total_deductions, 2),
    'employee_epf' => number_format($employee_epf, 2),
    'employee_socso' => number_format($employee_socso, 2),
    'employee_eis' => number_format($employee_eis, 2),
    'tax_amount' => number_format($emp['tax_amount'], 2),
    'total_contributions' => number_format($total_contributions, 2),
];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?=$payroll_data['full_name']?></title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .payslip {
            max-width: 100%;
            margin: auto;
            border: 1px solid #ccc;
            padding: 20px;
        }

        .payslip h2,
        .payslip h4 {
            text-align: center;
        }

        .table td,
        .table th {
            padding: 8px;
        }

        .table {
            width: 100%;
            margin-top: 20px;
        }

        .print-btn {
            margin-top: 20px;
            text-align: center;
        }

        .no-print {
            display: block;
        }

        /* Layout for side-by-side Earnings and Deductions */
        @media print {
            .no-print {
                display: none;
            }

            /* Set landscape orientation */
            @page {
                size: landscape;
                margin: 10mm;
            }

            /* Ensure body fits within the print layout */
            body {
                width: 100%;
                height: 100%;
            }

            .payslip {
                max-width: 100%;
                padding: 20px;
                margin: 0;
            }

            .table .earnings,
            .table .deductions {
                width: 48%;
                display: inline-block;
                vertical-align: top;
            }

            /* Employer Contributions in One Row */
            .table .employer-contributions {
                display: flex;
                justify-content: space-between;
                width: 100%;
            }
            .table .employer-contributions .contribution {
                width: 30%;
            }

            /* IC and Employee on Same Row */
            .info-row {
                display: flex;
                justify-content: space-between;
            }

            .info-row div {
                width: 48%;
            }
        }
    </style>
</head>

<body>

<div class="payslip">
    <h2>Blush Events</h2>
    <h4>Payslip for <?=date("M", mktime(0, 0, 0, $month, 10)) . " $year"?></h4>

    <!-- Employee Information in Same Row (IC and Employee) -->
    <div class="info-row">
        <div><strong>Employee:</strong> <?= htmlspecialchars($payroll_data['full_name']) ?></div>
        <div><strong>IC/Passport:</strong> <?= $payroll_data['ic'] ?></div>
    </div>

    <!-- Position and Department in Same Row -->
    <div class="info-row">
        <div><strong>Position:</strong> <?= $payroll_data['position'] ?></div>
        <div><strong>Department:</strong> <?= $payroll_data['department'] ?></div>
    </div>

    <!-- Earnings and Deductions Section Side-by-Side -->
    <div class="table">
        <div class="earnings">
            <h5><strong>EARNINGS</strong></h5>
            <table class="table table-bordered">
                <tbody>
                <tr>
                    <th>DESCRIPTION</th>
                    <th>AMOUNT (RM)</th>
                </tr>
                <tr>
                    <td>BASIC SALARY</td>
                    <td>RM <?= $payroll_data['basic_salary'] ?></td>
                </tr>
                <tr>
                    <td>OVERTIME</td>
                    <td>RM <?= $payroll_data['overtime_pay'] ?></td>
                </tr>
                <tr>
                    <td>ALLOWANCES</td>
                    <td>RM <?= $payroll_data['allowances'] ?></td>
                </tr>
                <tr>
                    <th>TOTAL EARNINGS</th>
                    <th>RM <?= $payroll_data['gross_pay'] ?></th>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="deductions">
            <h5><strong>DEDUCTIONS</strong></h5>
            <table class="table table-bordered">
                <tbody>
                <tr>
                    <th>DESCRIPTION</th>
                    <th>AMOUNT (RM)</th>
                </tr>
                <tr>
                    <td>EIS DEDUCTION</td>
                    <td>RM <?= $payroll_data['employee_eis'] ?></td>
                </tr>
                <tr>
                    <td>EPF DEDUCTION</td>
                    <td>RM <?= $payroll_data['employee_epf'] ?></td>
                </tr>
                <tr>
                    <td>SOCSO DEDUCTION</td>
                    <td>RM <?= $payroll_data['employee_socso'] ?></td>
                </tr>
                <tr>
                    <td>TAX DEDUCTION</td>
                    <td>RM <?= $payroll_data['tax_amount'] ?></td>
                </tr>
                <tr>
                    <th>TOTAL DEDUCTIONS</th>
                    <th>RM <?= $payroll_data['total_deductions'] ?></th>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Employer's Contributions Section in One Row -->
    <h5><strong>EMPLOYER'S CONTRIBUTIONS</strong></h5>
    <div class="table">
        <div class="employer-contributions">
            <div class="contribution">
                <strong>EIS</strong><br>
                RM <?= $payroll_data['employee_eis'] ?>
            </div>
            <div class="contribution">
                <strong>EPF</strong><br>
                RM <?= $payroll_data['employee_epf'] * 1.3 ?>
            </div>
            <div class="contribution">
                <strong>SOCSO</strong><br>
                RM <?= $payroll_data['employee_socso'] ?>
            </div>
        </div>
    </div>

    <!-- Net Pay -->
    <table class="table table-bordered">
        <tbody>
        <tr>
            <th>TOTAL NET PAY</th>
            <th>RM <?= $payroll_data['net_pay'] ?></th>
        </tr>
        </tbody>
    </table>

    <!-- Print Button -->
    <div class="print-btn no-print">
        <button class="btn btn-primary" onclick="window.print()">Print Payslip</button>
    </div>
</div>

</body>
</html>
