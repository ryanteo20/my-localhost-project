<?php
// Include your database connection
require('database.php');

// Check if the necessary POST data is provided
if (isset($_POST['editpersonalid'], $_POST['editpersonalfile'], $_POST['editpersonalvalue'])) {
    // Sanitize the inputs to prevent SQL injection
    $personalid = mysqli_real_escape_string($con, $_POST['editpersonalid']);
    $personalField = mysqli_real_escape_string($con, $_POST['editpersonalfile']);
    $personalValue = mysqli_real_escape_string($con, $_POST['editpersonalvalue']);

    // Prepare and execute the SQL query to update the specified field
    $query = "UPDATE personal_information SET $personalField = '$personalValue' WHERE personal_id = '$personalid'";

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
