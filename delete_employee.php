<?php
// Include your database connection
require('database.php');

// Check if the employee ID is provided
if (isset($_POST['employeeId'])) {
    // Sanitize the input to prevent SQL injection
    $employeeId = mysqli_real_escape_string($con, $_POST['employeeId']);
    
    // Prepare the SQL query to delete the employee
    $query = "DELETE FROM employeelogin WHERE ID = '$employeeId'";
    
    // Execute the query
    if (mysqli_query($con, $query)) {
        // Deletion successful
        mysqli_close($con);
        // Return success response
        echo json_encode(array("status" => "success"));
    } else {
        // Deletion failed
        echo json_encode(array("status" => "error", "message" => "Error deleting employee: " . mysqli_error($con)));
    }
} else {
    // No employee ID provided
    echo json_encode(array("status" => "error", "message" => "Employee ID not provided"));
}
?>
