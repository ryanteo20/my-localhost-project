<?php
require('database.php');
require('session.php');

// Check if user is authorized
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employer') {
    header("Location: index2.php");
    exit("Access Denied: Employers only.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = (int)$_POST['month'];
    $year = (int)$_POST['year'];
    
    // Validate inputs
    if ($month < 1 || $month > 12 || $year < 2000) {
        echo "Error: Invalid month or year";
        exit;
    }

    // Update all payroll statuses to 'confirmed' for the specified month/year
    $query = "
        UPDATE payroll_transactions 
        SET status = 'confirmed' 
        WHERE MONTH(pay_period_start) = ? 
        AND YEAR(pay_period_start) = ? 
        AND status != 'confirmed'
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $month, $year);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        if ($affected_rows > 0) {
            echo "success - $affected_rows employee payments confirmed for " . date("F", mktime(0, 0, 0, $month, 10)) . " $year";
        } else {
            echo "success - All payments were already confirmed";
        }
    } else {
        echo "Error confirming payments: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Error: Invalid request method";
}
?>