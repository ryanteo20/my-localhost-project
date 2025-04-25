<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $leave_length = $_POST['leave_length'];
    $reason = $_POST['reason'];
    $apply_date = date("Y-m-d"); // Get current date
    $fk_leaveapply_id = $_SESSION["ID"]; // Assuming session variable ID holds the ID needed

    // Check if a file is uploaded
    if (isset($_FILES["file"]) && $_FILES["file"]["error"] !== UPLOAD_ERR_NO_FILE) {
        // Handle file upload
        $targetDir = "/Applications/XAMPP/xamppfiles/htdocs/Image/leave/";
        $fileName = basename($_FILES["file"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
            // If file uploaded successfully, include it in the database query
            $sql = "INSERT INTO leave_apply (leave_type, leave_datestart, leave_dateend, leave_length, leave_reason, leave_document, apply_date, fk_leaveapply_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $con->prepare($sql);
            if ($stmt === false) {
                echo 'Prepare error: ' . $con->error;
                exit;
            }

            $stmt->bind_param("sssssssi", $leave_type, $start_date, $end_date, $leave_length, $reason, $targetFilePath, $apply_date, $fk_leaveapply_id);
            $stmt->execute();
            // Inside process_leave.php
            if ($stmt->affected_rows > 0) {
                $stmt->close();
                $con->close();
                $_SESSION['success_message'] = "Successfully applied for leave!";
                header("Location: apply_leave.php"); // Redirect to apply_leave.php to show the message
                exit();
            } else {
                echo "Error in insertion: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error uploading file";
        }
    } else {
        // If no file uploaded, exclude leave_document from the database query
        $sql = "INSERT INTO leave_apply (leave_type, leave_datestart, leave_dateend, leave_length, leave_reason, apply_date, fk_leaveapply_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        if ($stmt === false) {
            echo 'Prepare error: ' . $con->error;
            exit;
        }

        $stmt->bind_param("ssssssi", $leave_type, $start_date, $end_date, $leave_length, $reason, $apply_date, $fk_leaveapply_id);
        $stmt->execute();
        // Inside process_leave.php
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            $con->close();
            $_SESSION['success_message'] = "Successfully applied for leave!";
            header("Location: AL.php"); // Redirect to apply_leave.php to show the message
            exit();
        } else {
            echo "Error in insertion: " . $stmt->error;
        }
        $stmt->close();
    }

    $con->close();
}
?>
