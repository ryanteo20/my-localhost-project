<?php
function savePayrollTransaction($conn, $data) {
    // Check if record already exists for employee + pay period
    $check = $conn->prepare("
        SELECT transaction_id 
        FROM payroll_transactions 
        WHERE employee_id = ? 
          AND pay_period_start = ? 
          AND pay_period_end = ?
    ");
    $check->bind_param("iss", $data['employee_id'], $data['pay_period_start'], $data['pay_period_end']);
    $check->execute();
    $result = $check->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        // Record exists → UPDATE
        $transaction_id = $row['transaction_id'];
        $stmt = $conn->prepare("
            UPDATE payroll_transactions 
            SET payment_date=?, basic_salary=?, allowances=?, deductions=?, tax_amount=?, epf_amount=?, socso_amount=?, eis_amount=?, overtime_pay=?, total_claims=?, net_pay=?, status=? 
            WHERE transaction_id=?
        ");
        $stmt->bind_param(
            "sddddddddddsi",
            $data['payment_date'],
            $data['basic_salary'],
            $data['allowances'],
            $data['deductions'],
            $data['tax_amount'],
            $data['epf_amount'],
            $data['socso_amount'],
            $data['eis_amount'],
            $data['overtime_pay'],
            $data['total_claims'],
            $data['net_pay'],
            $data['status'],
            $transaction_id
        );
    } else {
        // No record → INSERT new
        $stmt = $conn->prepare("
            INSERT INTO payroll_transactions 
            (employee_id, pay_period_start, pay_period_end, payment_date, basic_salary, allowances, deductions, tax_amount, epf_amount, socso_amount, eis_amount, overtime_pay, total_claims, net_pay, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isssdddddddddds", 
            $data['employee_id'],
            $data['pay_period_start'],
            $data['pay_period_end'],
            $data['payment_date'],
            $data['basic_salary'],
            $data['allowances'],
            $data['deductions'],
            $data['tax_amount'],
            $data['epf_amount'],
            $data['socso_amount'],
            $data['eis_amount'],
            $data['overtime_pay'],
            $data['total_claims'],
            $data['net_pay'],
            $data['status']
        );
    }

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    $stmt->close();
    return true;
}
?>
