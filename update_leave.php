<?php
// Include your database connection
require('database.php');

// Check if the necessary POST data is provided
if (isset($_POST['editleaveid'], $_POST['editleavefile'], $_POST['editleavevalue'])) {
    // Sanitize the inputs to prevent SQL injection
    $leaveid = mysqli_real_escape_string($con, $_POST['editleaveid']);
    $leaveField = mysqli_real_escape_string($con, $_POST['editleavefile']);
    $leaveValue = mysqli_real_escape_string($con, $_POST['editleavevalue']);

    // Prepare and execute the SQL query to update the specified field
    $query = "UPDATE leave_info SET $leaveField = '$leaveValue' WHERE leaveinfo_id = '$leaveid'";

    if (mysqli_query($con, $query)) {
        // Update successful
        echo "Data updated successfully";
    } else {
        // Update failed
        echo "Error updating data: " . mysqli_error($con);
    }
} else {
    // If any required data is missing
    echo "Employee ID, field to update, or new value not provided";
}

// Close the connection
mysqli_close($con);
?>
