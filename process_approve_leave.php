<?php
include 'database.php';

// Get leave request ID from form (adjust name if needed)
$leave_id = $_POST['leave_id'] ?? null;

if (!$leave_id) {
    die("No leave ID provided.");
}

// 1️⃣ Mark leave as approved
$sql = "UPDATE leave_apply SET status = 'approved' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $leave_id);
$stmt->execute();

// 2️⃣ Get employee ID and leave dates
$sql2 = "SELECT employee_id, leave_datestart, leave_dateend FROM leave_apply WHERE id = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $leave_id);
$stmt2->execute();
$result = $stmt2->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die("Leave request not found.");
}

$emp_id = $row['employee_id'];
$start_date = $row['leave_datestart'];
$end_date = $row['leave_dateend'];

// ✅ Transaction to ensure both succeed
$conn->begin_transaction();

try {
    // 3️⃣ Update existing attendance rows
    $update = "
        UPDATE attendance
        SET status = 'on-leave', clock_in = NULL, clock_out = NULL
        WHERE employee_id = ? 
          AND date BETWEEN ? AND ?
          AND DAYOFWEEK(date) BETWEEN 2 AND 6
    ";
    $stmt3 = $conn->prepare($update);
    $stmt3->bind_param("iss", $emp_id, $start_date, $end_date);
    $stmt3->execute();

    // 4️⃣ Insert missing attendance rows
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
    $stmt4 = $conn->prepare($insert);
    $stmt4->bind_param("isssi", $emp_id, $start_date, $start_date, $end_date, $emp_id);
    $stmt4->execute();

    // ✅ Commit all
    $conn->commit();

    echo "Leave approved & attendance updated successfully!";

} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
