<?php
require('database.php');  // Include your database connection script

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the data from the POST request
$employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;
$month = isset($_POST['month']) ? (int)$_POST['month'] : null;
$year = isset($_POST['year']) ? (int)$_POST['year'] : null;

// Log the received data
error_log("Received employee_id: $employee_id, month: $month, year: $year");

if ($employee_id && $month && $year) {
    // Prepare the SQL update query
    $update_query = "
    UPDATE payroll_transactions
    SET status = 'confirmed'
    WHERE employee_id = ? AND MONTH(pay_period_start) = ? AND YEAR(pay_period_start) = ?
    ";

    // Prepare and bind the query parameters
    if ($stmt = mysqli_prepare($conn, $update_query)) {
        mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);

        // Execute the query
        if (mysqli_stmt_execute($stmt)) {
            echo 'Status updated successfully';
        } else {
            // Log any errors to troubleshoot
            error_log('Error executing query: ' . mysqli_error($conn));
            echo 'Error updating status';
        }

        mysqli_stmt_close($stmt);
    } else {
        // Log errors if the query preparation fails
        error_log('Error preparing the query: ' . mysqli_error($conn));
        echo 'Error preparing query';
    }
} else {
    echo 'Invalid data received';
}

// Close the database connection
mysqli_close($conn);
?>
