<?php
require('database.php');
require('session.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $claim_id = $_POST['claim_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if ($claim_id && in_array($status, ['Approved', 'Rejected'])) {
        $query = "UPDATE claims SET status = ?, rejection_reason = ? WHERE claim_id = ?";
        $stmt = mysqli_prepare($con, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssi", $status, $reason, $claim_id);
            if (mysqli_stmt_execute($stmt)) {
                echo "Success";
            } else {
                echo "Error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "Prepare failed: " . mysqli_error($con);
        }
    } else {
        echo "Invalid data.";
    }

    mysqli_close($con);
} else {
    echo "Invalid request.";
}
?>
