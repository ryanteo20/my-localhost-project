<?php
require('database.php');

function handleLeave($con) {
    if (isset($_POST['inputAnnual'], $_POST['inputSick'], $_POST['inputHospitalization'])) {
        $annualLeave = mysqli_real_escape_string($con, $_POST['inputAnnual']);
        $sickLeave = mysqli_real_escape_string($con, $_POST['inputSick']);
        $hospitalizationLeave = mysqli_real_escape_string($con, $_POST['inputHospitalization']);

        // Validate input (Example: Ensure values are numeric)
        if (!is_numeric($annualLeave) || !is_numeric($sickLeave) || !is_numeric($hospitalizationLeave)) {
            echo "<script>alert('Error: Leave values must be numeric.');</script>";
            return;
        }

        // SQL statement (use prepared statement)
        $query = "INSERT INTO `leave_info` (annual_leave, sick_leave, hospitalization_leave, annual_leavetaken, sick_leavetaken, hospitalization_leavetaken) VALUES (?, ?, ?, 0, 0, 0)";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "sss", $annualLeave, $sickLeave, $hospitalizationLeave);
        
        // Execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            // Display success message in an alert box using JavaScript
            echo "<script>alert('Leave information inserted successfully.');</script>";
            // Redirect back to add_l.php after a short delay
            echo "<script>window.location.href = 'add_l.php';</script>";
        } else {
            echo "<script>alert('Error inserting leave info: " . mysqli_error($con) . "');</script>";
        }
    } else {
        echo "<script>alert('Error: Required fields are missing');</script>";
    }
}

handleLeave($con);
mysqli_close($con);
?>
