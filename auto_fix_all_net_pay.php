<?php
require('database.php');
require('session.php');

// Check if user is authorized
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employer') {
    header("Location: index2.php");
    exit("Access Denied: Employers only.");
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_fix'])) {
    // Update all net pay calculations for the specified month/year
    $query = "
        UPDATE payroll_transactions 
        SET net_pay = (
            COALESCE(basic_salary, 0) + 
            COALESCE(allowances, 0) + 
            COALESCE(overtime_pay, 0) + 
            COALESCE(total_claims, 0) - 
            COALESCE(epf_amount, 0) - 
            COALESCE(socso_amount, 0) - 
            COALESCE(tax_amount, 0) - 
            COALESCE(eis_amount, 0)
        )
        WHERE MONTH(pay_period_start) = ? AND YEAR(pay_period_start) = ?
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $month, $year);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        $_SESSION['message'] = "Success! $affected_rows payroll records have been automatically recalculated and updated.";
        header("Location: view_report.php?month=$month&year=$year");
        exit;
    } else {
        $error_message = "Error updating records: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Auto-Fix All Net Pay</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .warning { background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info { background-color: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Auto-Fix All Net Pay Calculations</h2>
        <h3><?= date("F", mktime(0, 0, 0, $month, 10)) . " $year" ?></h3>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>
        
        <div class="info">
            <h4>ℹ️ What This Will Do</h4>
            <p><strong>Formula:</strong> Net Pay = Basic Salary + Allowances + Overtime + Claims - EPF - SOCSO - Tax - EIS</p>
            <p>This will automatically recalculate and update ALL net pay values for this month using the correct formula.</p>
        </div>
        
        <div class="warning">
            <h4>⚠️ Important</h4>
            <ul>
                <li>This will recalculate ALL employees' net pay for <?= date("F", mktime(0, 0, 0, $month, 10)) . " $year" ?></li>
                <li>Any existing net pay values will be overwritten</li>
                <li>This action cannot be undone</li>
                <li>Make sure you have a database backup</li>
            </ul>
        </div>
        
        <form method="POST" class="mt-4">
            <button type="submit" name="auto_fix" class="btn btn-danger btn-lg" 
                    onclick="return confirm('Are you absolutely sure you want to auto-fix all net pay calculations? This cannot be undone!')">
                🔧 Auto-Fix All Net Pay Values
            </button>
            <a href="view_report.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-secondary btn-lg ms-3">Cancel</a>
        </form>
        
        <div class="mt-4">
            <a href="payroll_diagnostic.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-info">
                📊 View Diagnostic Report First
            </a>
        </div>
    </div>
</body>
</html>