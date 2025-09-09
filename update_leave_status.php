<?php
require('database.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['leaveId'], $_POST['status'])) {
    // Sanitize input values
    $leaveId = intval($_POST['leaveId']);  // Make sure it's an integer
    $status = htmlspecialchars($_POST['status']);  // Sanitize status input
    $reason = isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : '';

    // Retrieve the current leave reason
    $query = "SELECT leave_reason FROM leave_apply WHERE leave_id = ?";
    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, "i", $leaveId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $currentReason);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // Use the current leave reason if no new reason is provided
        $finalReason = $reason ? $reason : $currentReason;

        // Update query with the final reason
        $updateQuery = "UPDATE leave_apply SET leave_review = ?, leave_reason = ? WHERE leave_id = ?";
        if ($updateStmt = mysqli_prepare($conn, $updateQuery)) {
            mysqli_stmt_bind_param($updateStmt, "ssi", $status, $finalReason, $leaveId);
            $result = mysqli_stmt_execute($updateStmt);

            if ($result) {
                echo "Success";
            } else {
                echo "Error updating record: " . mysqli_error($conn);
            }

            mysqli_stmt_close($updateStmt);
        } else {
            echo "Error preparing update statement: " . mysqli_error($conn);
        }
    } else {
        echo "Error preparing select statement: " . mysqli_error($conn);
    }

    // Close the database connection
    mysqli_close($conn);
} else {
    echo "Invalid request";
}
?>
