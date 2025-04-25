<?php
// Include your database connection
require('database.php');

// Check if the necessary POST data is provided
if (isset($_POST['editPayrollId'], $_POST['editFieldSelect'], $_POST['editNewValueInput'])) {
    // Sanitize the inputs to prevent SQL injection
    $payrollid = mysqli_real_escape_string($con, $_POST['editPayrollId']);
    $payrollField = mysqli_real_escape_string($con, $_POST['editFieldSelect']);
    $payrollValue = mysqli_real_escape_string($con, $_POST['editNewValueInput']);

    // Prepare the SQL query to update the specified field
    $query = "UPDATE payroll_detail SET $payrollField = '$payrollValue' WHERE payroll_id = '$payrollid'";

    // Execute the SQL query
    if (mysqli_query($con, $query)) {
        // Update successful
        echo "Data updated successfully";
    } else {
        // Update failed
        $error_message = "Error updating data: " . mysqli_error($con);
    }
} else {
    // If any required data is missing
    echo "Employee ID, field to update, or new value not provided";
}

// Close the connection
mysqli_close($con);
?>
