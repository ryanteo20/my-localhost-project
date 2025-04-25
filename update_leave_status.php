<?php
require('database.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['leaveId'], $_POST['status'])) {
    $leaveId = $_POST['leaveId'];
    $status = $_POST['status'];
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';

    // Retrieve the current leave reason
    $query = "SELECT leave_reason FROM leave_apply WHERE leave_id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $leaveId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $currentReason);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Use the current leave reason if no new reason is provided
    $finalReason = $reason ? $reason : $currentReason;

    // Update query with the final reason
    $updateQuery = "UPDATE leave_apply SET leave_review = ?, leave_reason = ? WHERE leave_id = ?";
    $updateStmt = mysqli_prepare($con, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "ssi", $status, $finalReason, $leaveId);
    $result = mysqli_stmt_execute($updateStmt);

    if ($result) {
        echo "Success";
    } else {
        echo "Error updating record: " . mysqli_error($con);
    }

    mysqli_stmt_close($updateStmt);
    mysqli_close($con);
} else {
    echo "Invalid request";
}
?>
