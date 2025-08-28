<?php
require('database.php');
require('session.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is logged in
if (!isset($_SESSION['ID'])) {
    die("You must be logged in to view your payslip.");
}

$employee_id = $_SESSION['ID'];

// Get month and year from URL
$month = isset($_GET['month']) ? (int)$_GET['month'] : null;
$year = isset($_GET['year']) ? (int)$_GET['year'] : null;

if (!$month || !$year) {
    die("Invalid month or year.");
}

// Convert month number to name
$monthName = date("F", mktime(0,0,0,$month,10));

// Get payroll data for this user
$query = "
SELECT pt.*, el.username AS employee_name
FROM payroll_transactions pt
JOIN employeelogin el ON pt.employee_id = el.ID
WHERE pt.employee_id = ?
AND MONTH(pt.pay_period_start) = ?
AND YEAR(pt.pay_period_start) = ?
LIMIT 1
";

$stmt = mysqli_prepare($con, $query);
if (!$stmt) {
    die("MySQL prepare failed: " . mysqli_error($con));
}

mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $payroll = mysqli_fetch_assoc($result);
} else {
    echo "<script>alert('No payroll data found for this month/year.'); window.location.href='C_payslip.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payslip - <?= htmlspecialchars($payroll['employee_name']) ?></title>
<link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { font-family: Arial, sans-serif; margin: 20px; }
  .payslip { max-width: 700px; margin: auto; border: 1px solid #ccc; padding: 20px; }
  .payslip h2, .payslip h4 { text-align: center; }
  .table td, .table th { padding: 8px; }
  @media print {
    .no-print { display: none; }
  }
</style>
</head>
<body>

<div class="payslip">
  <h2>Company Name</h2>
  <h4>Payslip for <?= $monthName . ' ' . $year ?></h4>
  <p><strong>Employee:</strong> <?= htmlspecialchars($payroll['employee_name']) ?> (ID: <?= $payroll['employee_id'] ?>)</p>
  <p><strong>Pay Period:</strong> <?= date('d M Y', strtotime($payroll['pay_period_start'])) ?> - <?= date('d M Y', strtotime($payroll['pay_period_end'])) ?></p>

  <table class="table table-bordered">
    <tbody>
      <tr><th>Basic Salary</th><td>RM <?= number_format($payroll['basic_salary'], 2) ?></td></tr>
      <tr><th>Allowance</th><td>RM <?= number_format($payroll['allowances'], 2) ?></td></tr>
      <tr><th>Overtime</th><td>RM <?= number_format($payroll['overtime_pay'], 2) ?></td></tr>
      <tr><th>Total Claims</th><td>RM <?= number_format($payroll['total_claims'], 2) ?></td></tr>
      <tr><th>EPF</th><td>RM <?= number_format($payroll['epf_amount'], 2) ?></td></tr>
      <tr><th>SOCSO</th><td>RM <?= number_format($payroll['socso_amount'], 2) ?></td></tr>
      <tr><th>Tax</th><td>RM <?= number_format($payroll['tax_amount'], 2) ?></td></tr>
      <tr><th>EIS</th><td>RM <?= number_format($payroll['eis_amount'], 2) ?></td></tr>
      <tr><th>Net Pay</th><td><strong>RM <?= number_format($payroll['net_pay'], 2) ?></strong></td></tr>
    </tbody>
  </table>

  <div class="text-center no-print mt-3">
    <button class="btn btn-primary" onclick="window.print()">Print Payslip</button>
    <a href="C_payslip.php" class="btn btn-secondary">Back</a>
  </div>
</div>

</body>
</html>
