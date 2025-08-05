<?php
require('database.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $claim_id = $_POST['claim_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $reason = $_POST['reason'] ?? '';

    // Sanity check
    if ($status === 'Approve') {
        $query = "UPDATE claims SET status = ? WHERE claim_id = ?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "si", $status, $claim_id);
    } else {
        $query = "UPDATE claims SET status = ?, rejection_reason = ? WHERE claim_id = ?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "ssi", $status, $reason, $claim_id);
    }

    if ($stmt) {
        if (mysqli_stmt_execute($stmt)) {
            $affected = mysqli_stmt_affected_rows($stmt);
            if ($affected > 0) {
                echo "Success";
            } else {
                echo "No rows updated. Is claim_id correct?";
            }
        } else {
            echo "Execute failed: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Prepare failed: " . mysqli_error($con);
    }

    mysqli_close($con);
}
?>
