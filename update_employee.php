<?php
// Include your database connection
require('database.php');

// Check if the employee ID, field to update, and new value are provided
if (isset($_POST['employeeId'], $_POST['selectedField'], $_POST['newValue'])) {
    // Sanitize the inputs to prevent SQL injection
    $employeeId = mysqli_real_escape_string($con, $_POST['employeeId']);
    $selectedField = mysqli_real_escape_string($con, $_POST['selectedField']);
    $newValue = mysqli_real_escape_string($con, $_POST['newValue']);

    if ($selectedField === 'password') {
        $hashedPassword = md5($newValue);
        $newValue = $hashedPassword;
    }

    // Prepare the SQL query to update the specified field
    $query = "UPDATE employeelogin SET $selectedField = '$newValue' WHERE ID = '$employeeId'";

    // Execute the query
    if (mysqli_query($con, $query)) {
        // Update successful
        echo "Employee information updated successfully";
    } else {
        // Update failed
        echo "Error updating employee information: " . mysqli_error($con);
    }
} else {
    // If any required data is missing
    echo "Employee ID, field to update, or new value not provided";
}

// Close the connection
mysqli_close($con);
?>
