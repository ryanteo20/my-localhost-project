<?php
// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the leaveId and reason parameters are set
    if (isset($_POST['leaveId']) && isset($_POST['reason'])) {
        // Sanitize and validate input data
        $leaveId = $_POST['leaveId'];
        $reason = $_POST['reason'];

        // Perform database update
        require('database.php'); // Include database connection

        $query = "UPDATE leave_apply SET leave_reason = ? WHERE leave_id = ?";
        $stmt = mysqli_prepare($con, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $reason, $leaveId);
            $result = mysqli_stmt_execute($stmt);

            if ($result) {
                // Success
                echo "Leave reason updated successfully.";
            } else {
                // Error executing query
                echo "Error: " . mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);
        } else {
            // Error preparing statement
            echo "Error preparing statement: " . mysqli_error($con);
        }

        mysqli_close($con);
    } else {
        // Parameters not set
        echo "Error: leaveId and reason parameters are required.";
    }
} else {
    // Invalid request method
    echo "Error: Invalid request method.";
}
?>
