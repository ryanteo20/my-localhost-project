<?php
require('database.php');

// Check if the necessary POST data is provided
if (isset($_POST['editemploymentid'], $_POST['editemploymentfile'], $_POST['editemploymentvalue'])) {
    // Sanitize inputs to prevent SQL injection
    $id = mysqli_real_escape_string($con, $_POST['editemploymentid']);
    $field = mysqli_real_escape_string($con, $_POST['editemploymentfile']);
    $value = mysqli_real_escape_string($con, $_POST['editemploymentvalue']);

    // Build the SQL statement with dynamic column name
    $query = "UPDATE employment_detail SET $field = ? WHERE employment_id = ?";

    // Prepare the statement
    if ($stmt = mysqli_prepare($con, $query)) {
        // Bind parameters
        mysqli_stmt_bind_param($stmt, 'si', $value, $id);
        
        // Execute the query
        if (mysqli_stmt_execute($stmt)) {
            echo "Update successful!";
        } else {
            // If the query failed, show why
            echo "Update failed: " . mysqli_error($con);
        }

        // Close the statement
        mysqli_stmt_close($stmt);
    } else {
        // If the statement couldn't be prepared, show why
        echo "Prepare failed: " . mysqli_error($con);
    }

    // Close the connection
    mysqli_close($con);
} else {
    // If any required data is missing
    echo "Employee ID, field to update, or new value not provided";
}
?>
