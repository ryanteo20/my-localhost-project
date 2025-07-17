<?php
require('database.php');
require('session.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the leave ID from the POST request
$leave_id = $_POST['leave_id'] ?? null;

if (!$leave_id) {
    die("❌ No leave ID provided.");
}

// ✅ Update the leave_apply table: set leave_review to 'approved'
$sql = "UPDATE leave_apply SET leave_review = 'approved' WHERE leave_id = ?";
$stmt = $con->prepare($sql);

if (!$stmt) {
    die("❌ Prepare failed: (" . $con->errno . ") " . $con->error);
}

$stmt->bind_param("i", $leave_id);
$stmt->execute();
echo "✅ Leave marked as approved.\n";

// ✅ Get the leave details to update attendance
$sql2 = "SELECT fk_leaveapply_id AS employee_id, leave_datestart, leave_dateend FROM leave_apply WHERE leave_id = ?";
$stmt2 = $con->prepare($sql2);
$stmt2->bind_param("i", $leave_id);
$stmt2->execute();
$result = $stmt2->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die("❌ Leave request not found.");
}

$emp_id = $row['employee_id'];
$start_date = $row['leave_datestart'];
$end_date = $row['leave_dateend'];

echo "✅ Employee ID: $emp_id, Start: $start_date, End: $end_date\n";

// ✅ Start transaction
$con->begin_transaction();

try {
    // 1️⃣ Update existing attendance rows to 'on-leave'
    $update = "
        UPDATE attendance
        SET status = 'on-leave', clock_in = NULL, clock_out = NULL
        WHERE employee_id = ? 
          AND date BETWEEN ? AND ?
          AND DAYOFWEEK(date) BETWEEN 2 AND 6
    ";
    $stmt3 = $con->prepare($update);
    $stmt3->bind_param("iss", $emp_id, $start_date, $end_date);
    $stmt3->execute();
    echo "✅ Updated existing attendance.\n";

    // 2️⃣ Insert missing attendance rows
    $insert = "
        INSERT INTO attendance (employee_id, date, status)
        SELECT ?, date_seq, 'on-leave'
        FROM (
            SELECT DATE_ADD(?, INTERVAL @i := @i + 1 DAY) AS date_seq
            FROM 
                (SELECT 0 i UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                 UNION ALL SELECT 8 UNION ALL SELECT 9) a,
                (SELECT 0 i UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                 UNION ALL SELECT 8 UNION ALL SELECT 9) b,
                (SELECT @i:=-1) vars
            WHERE DATE_ADD(?, INTERVAL @i + 1 DAY) <= ?
        ) AS dates
        WHERE DAYOFWEEK(date_seq) BETWEEN 2 AND 6
          AND NOT EXISTS (
              SELECT 1 FROM attendance 
              WHERE employee_id = ? AND date = date_seq
          )
    ";
    $stmt4 = $con->prepare($insert);
    $stmt4->bind_param("isssi", $emp_id, $start_date, $start_date, $end_date, $emp_id);
    $stmt4->execute();
    echo "✅ Inserted missing attendance rows.\n";

    $con->commit();
    echo "✅ ✅ ✅ Leave approval & attendance sync complete!";

} catch (Exception $e) {
    $con->rollback();
    die("❌ Transaction failed: " . $e->getMessage());
}

$con->close();
?>
