<?php
require('database.php');
require('session.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employer') {
    header("Location: index2.php");
    exit("Access Denied: Employers only.");
}

// Default to current month/year, support GET after redirect
$selected_month = $_POST['month'] ?? $_GET['month'] ?? date('m');
$selected_year  = $_POST['year']  ?? $_GET['year']  ?? date('Y');
$selected_emp   = $_POST['employee_id'] ?? $_GET['employee_id'] ?? '';

$selected_month = str_pad((int)$selected_month, 2, '0', STR_PAD_LEFT);
$selected_year  = (int)$selected_year;
$selected_emp   = $selected_emp !== '' ? (int)$selected_emp : '';

// Fetch employees - only active employees, ordered by employee ID
$employees = mysqli_query($conn, "
    SELECT p.personal_id, p.full_name, e.deleted_at 
    FROM personal_information p 
    LEFT JOIN employeelogin e ON p.personal_id = e.ID 
    WHERE (e.deleted_at IS NULL OR e.deleted_at = '') 
    AND e.ID IS NOT NULL
    ORDER BY p.personal_id ASC
");

// Build employee options - only active employees
$emp_options = '';
while ($emp = mysqli_fetch_assoc($employees)) {
    $selected = ((string)$emp['personal_id'] === (string)$selected_emp) ? 'selected' : '';
    $emp_options .= "<option value='{$emp['personal_id']}' $selected>ID: {$emp['personal_id']} - {$emp['full_name']} (Active)</option>";
}

// Initialize variables
$payroll_data = null;
$employee_name = '';
$error_message = '';

if (!empty($selected_emp)) {
    $emp_id = (int)$selected_emp;

    // Check if employee is still active before processing
    $status_check = "
        SELECT p.full_name, e.deleted_at 
        FROM personal_information p 
        LEFT JOIN employeelogin e ON p.personal_id = e.ID 
        WHERE p.personal_id = $emp_id
    ";
    $status_result = mysqli_query($conn, $status_check);
    $employee_status = mysqli_fetch_assoc($status_result);
    
    if (!$employee_status) {
        $selected_emp = '';
        $error_message = "Employee not found in the system.";
    } elseif (!empty($employee_status['deleted_at'])) {
        $selected_emp = '';
        $payroll_data = null;
        $employee_name = '';
        $error_message = "Employee {$employee_status['full_name']} has been marked as deleted/inactive. Payroll processing is not available for inactive employees.";
    } else {
        // Employee is active, proceed with normal processing
        $employee_name = $employee_status['full_name'];
        
        // Get payroll data
        $query = "SELECT * FROM payroll_detail WHERE payroll_id = $emp_id";
        $res = mysqli_query($conn, $query);

        if (!$res) {
            die("Query Error: " . mysqli_error($conn));
        }

        $payroll_data = mysqli_fetch_assoc($res);
    }
}

// Attendance + Claims Summary
$attendance_summary = [
    'present' => 0,
    'on_leave' => 0,
    'absent' => 0
];
$total_claim = 0.00;

if (!empty($selected_emp)) {
    $month = (int)$selected_month;
    $year = (int)$selected_year;
    $emp_id = (int)$selected_emp;

    // Get attendance data
    $q = "SELECT COUNT(*) as total FROM attendance WHERE employee_id = $emp_id AND MONTH(date) = $month AND YEAR(date) = $year AND status = 'present'";
    $res = mysqli_query($conn, $q);
    if ($res) {
        $attendance_summary['present'] = mysqli_fetch_assoc($res)['total'] ?? 0;
    }

    $q = "SELECT COUNT(*) as total FROM attendance WHERE employee_id = $emp_id AND MONTH(date) = $month AND YEAR(date) = $year AND status = 'on-leave'";
    $res = mysqli_query($conn, $q);
    if ($res) {
        $attendance_summary['on_leave'] = mysqli_fetch_assoc($res)['total'] ?? 0;
    }

    $q = "SELECT COUNT(*) as total FROM attendance WHERE employee_id = $emp_id AND MONTH(date) = $month AND YEAR(date) = $year AND status = 'absent'";
    $res = mysqli_query($conn, $q);
    if ($res) {
        $attendance_summary['absent'] = mysqli_fetch_assoc($res)['total'] ?? 0;
    }

    // Get claims data
    $q = "SELECT SUM(amount) as total FROM claims WHERE employee_id = $emp_id AND MONTH(transaction_date) = $month AND YEAR(transaction_date) = $year AND status ='Approved'";
    $res = mysqli_query($conn, $q);
    if ($res) {
        $total_claim = mysqli_fetch_assoc($res)['total'] ?? 0.00;
    }
}

// Initialize allowance and overtime variables properly
$allowance = 0;
$overtime = 0;

if ($payroll_data) {
    $allowance = isset($_POST['allowance_input']) && is_numeric($_POST['allowance_input']) 
        ? (float)$_POST['allowance_input'] 
        : (float)($payroll_data['allowance'] ?? 0);

    $overtime = isset($_POST['overtime_input']) && is_numeric($_POST['overtime_input']) 
        ? (float)$_POST['overtime_input'] 
        : (float)($payroll_data['overtime_pay'] ?? 0);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_current'])) {
    $employee_id = (int)$_POST['employee_id'];
    
    // Double-check employee is still active before processing payroll
    $active_check = "
        SELECT COUNT(*) as count 
        FROM employeelogin 
        WHERE ID = $employee_id 
        AND (deleted_at IS NULL OR deleted_at = '')
    ";
    $active_result = mysqli_query($conn, $active_check);
    $active_count = mysqli_fetch_assoc($active_result)['count'];
    
    if ($active_count == 0) {
        $_SESSION['payroll_status'] = "Error: Cannot process payroll for inactive employee.";
        header("Location: P_payroll.php?month={$_POST['month']}&year={$_POST['year']}");
        exit;
    }

    $period_start = date("Y-m-01", strtotime($_POST['year']."-".$_POST['month']."-01"));
    $period_end   = date("Y-m-t", strtotime($_POST['year']."-".$_POST['month']."-01"));
    $payment_date = date("Y-m-d");

    // FIXED: Properly capture allowance and overtime values
    $basic_salary = (float)$_POST['basic_salary'];
    
    // Try multiple field names to get the correct values
    $final_allowance = 0;
    $final_overtime = 0;
    
    if (isset($_POST['allowance_input']) && is_numeric($_POST['allowance_input'])) {
        $final_allowance = (float)$_POST['allowance_input'];
    } elseif (isset($_POST['allowance']) && is_numeric($_POST['allowance'])) {
        $final_allowance = (float)$_POST['allowance'];
    }
    
    if (isset($_POST['overtime_input']) && is_numeric($_POST['overtime_input'])) {
        $final_overtime = (float)$_POST['overtime_input'];
    } elseif (isset($_POST['overtime']) && is_numeric($_POST['overtime'])) {
        $final_overtime = (float)$_POST['overtime'];
    }

    // FIXED: Recalculate all values server-side to ensure accuracy
    $total_claims = (float)($_POST['total_claims'] ?? 0);
    
    // Calculate deductions
    $epf_amount = $basic_salary * 0.11;
    $socso_amount = $basic_salary * 0.005;
    $eis_amount = $basic_salary * 0.002;
    
    // Calculate PCB (tax) properly
    $gross_pay = $basic_salary + $final_allowance + $final_overtime + $total_claims;
    $annual_gross = $gross_pay * 12;
    $annual_epf = $basic_salary * 0.11 * 12;
    $annual_chargeable = $annual_gross - $annual_epf;

    function calcAnnualTax($chargeable) {
        $brackets = [
            [0, 5000, 0, 0],
            [5001, 20000, 150, 0.01],
            [20001, 35000, 600, 0.03],
            [35001, 50000, 1500, 0.06],
            [50001, 70000, 3700, 0.11],
            [70001, 100000, 5700, 0.19],
            [100001, 400000, 75000, 0.25],
            [400001, 600000, 84400, 0.26],
            [600001, 2000000, 136400, 0.28],
            [2000001, PHP_INT_MAX, 528400, 0.30],
        ];
        foreach ($brackets as $b) {
            [$min, $max, $base, $rate] = $b;
            if ($chargeable >= $min && $chargeable <= $max) {
                return $base + ($chargeable - $min) * $rate;
            }
        }
        return 0;
    }

    if ($annual_chargeable <= 34000) {
        $annual_tax = 0;
    } else {
        $annual_tax = calcAnnualTax($annual_chargeable);
    }
    $tax_amount = round($annual_tax / 12, 2);
    if ($tax_amount < 10) { $tax_amount = 0; }

    // FIXED: Calculate correct net pay including allowance and overtime
    $total_deductions = $epf_amount + $socso_amount + $eis_amount + $tax_amount;
    $calculated_net_pay = $basic_salary + $final_allowance + $final_overtime + $total_claims - $total_deductions;

    // Debug logging
    error_log("Payroll Calculation Debug:");
    error_log("Basic Salary: $basic_salary");
    error_log("Allowance: $final_allowance");
    error_log("Overtime: $final_overtime");
    error_log("Claims: $total_claims");
    error_log("EPF: $epf_amount");
    error_log("SOCSO: $socso_amount");
    error_log("EIS: $eis_amount");
    error_log("Tax: $tax_amount");
    error_log("Total Deductions: $total_deductions");
    error_log("Calculated Net Pay: $calculated_net_pay");

    $payrollData = [
        'employee_id'     => $employee_id,
        'pay_period_start'=> $period_start,
        'pay_period_end'  => $period_end,
        'payment_date'    => $payment_date,
        'basic_salary'    => $basic_salary,
        'allowances'      => $final_allowance,  // FIXED: Use the captured allowance value
        'deductions'      => $total_deductions,
        'tax_amount'      => $tax_amount,
        'epf_amount'      => $epf_amount,
        'socso_amount'    => $socso_amount,
        'eis_amount'      => $eis_amount,
        'overtime_pay'    => $final_overtime,   // FIXED: Use the captured overtime value
        'total_claims'    => $total_claims,
        'net_pay'         => $calculated_net_pay,  // FIXED: Use calculated net pay
        'status'          => 'processed'
    ];

    require_once 'functions.php';
    $result = savePayrollTransaction($conn, $payrollData);
    
    if ($result) {
        $employee_name_for_msg = '';
        if (!empty($employee_name)) {
            $employee_name_for_msg = " for " . $employee_name;
        }
        $_SESSION['payroll_status'] = "Payroll processed successfully{$employee_name_for_msg}! Net Pay: RM " . number_format($calculated_net_pay, 2) . " (Basic: RM" . number_format($basic_salary, 2) . " + Allowance: RM" . number_format($final_allowance, 2) . " + Overtime: RM" . number_format($final_overtime, 2) . " + Claims: RM" . number_format($total_claims, 2) . " - Deductions: RM" . number_format($total_deductions, 2) . ")";
    } else {
        $_SESSION['payroll_status'] = "Error: Failed to save payroll data. Please try again.";
    }

    // Redirect to next employee if requested
    if (!empty($_POST['goto_next'])) {
        $next_emp = (int)$_POST['goto_next'];
        header("Location: P_payroll.php?month={$_POST['month']}&year={$_POST['year']}&employee_id={$next_emp}");
        exit;
    } else {
        header("Location: P_payroll.php?month={$_POST['month']}&year={$_POST['year']}&employee_id={$_POST['employee_id']}");
        exit;
    }
}
?>

<!-- The HTML and JavaScript sections remain the same, but update the form submission JavaScript -->

<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Head section remains the same -->
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SMEasyHR - Employer</title>
  <!-- All CSS links remain the same -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  
  <!-- CSS styles remain the same -->
  <style>
    body { 
      font-family: Arial, sans-serif; 
      background: #f9f9f9; 
      margin: 0; 
      padding: 20px; 
    }
    
    .payroll-container {
      display: flex;
      gap: 20px;
      background: white;
      padding: 20px;
      border-radius: 8px;
      max-width: 1200px;
      margin: auto;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    
    .column {
      flex: 0 0 23%;
      max-width: 23%;
      min-width: 220px;
      padding: 10px;
    }
    
    .column h3 {
      font-size: 16px;
      border-bottom: 1px solid #ddd;
      margin-bottom: 10px;
      padding-bottom: 5px;
    }
    
    .item {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
    }
    
    .bold { 
      font-weight: bold; 
    }
    
    .highlight { 
      color: teal; 
      font-size: 18px; 
      font-weight: bold; 
    }

    .editable-money {
      color: #007bff;
      font-weight: bold;
    }

    /* Alert improvements */
    .alert {
      margin-bottom: 20px;
      border: none;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .alert-success {
      background: linear-gradient(135deg, #d4edda, #c3e6cb);
      color: #155724;
      border-left: 4px solid #28a745;
    }

    .alert-info {
      background: linear-gradient(135deg, #d1ecf1, #bee5eb);
      color: #0c5460;
      border-left: 4px solid #17a2b8;
    }

    .alert-danger {
      background: linear-gradient(135deg, #f8d7da, #f5c6cb);
      color: #721c24;
      border-left: 4px solid #dc3545;
    }
    
    .alert-warning {
      background: linear-gradient(135deg, #fff3cd, #ffeaa7);
      color: #856404;
      border-left: 4px solid #ffc107;
    }

    /* Employee navigation info */
    .employee-nav-info {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 15px;
      margin: 20px 0;
      text-align: center;
    }

    .employee-nav-info .current-position {
      font-size: 1.1em;
      font-weight: bold;
      color: #495057;
      margin-bottom: 10px;
    }

    .employee-nav-info .progress-bar-custom {
      background: #e9ecef;
      border-radius: 10px;
      height: 20px;
      margin: 10px 0;
      overflow: hidden;
    }

    .employee-nav-info .progress-fill {
      background: linear-gradient(90deg, #28a745, #20c997);
      height: 100%;
      transition: width 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 0.9em;
    }
  </style>
</head>

<body>
  <!-- Header and Sidebar sections remain exactly the same -->
  <!-- Header section... -->
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center">
        <img src="assets/img/logo.png" alt="">
        <span class="d-none d-lg-block">SMEasyHR</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div>

    <div class="search-bar">
      <form class="search-form d-flex align-items-center" method="POST" action="#">
        <input type="text" name="query" placeholder="Search" title="Enter search keyword">
        <button type="submit" title="Search"><i class="bi bi-search"></i></button>
      </form>
    </div>

    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">
        <li class="nav-item d-block d-lg-none">
          <a class="nav-link nav-icon search-bar-toggle " href="#">
            <i class="bi bi-search"></i>
          </a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" id="notificationIcon">
            <i class="bi bi-bell"></i>
            <span class="badge bg-primary badge-number" id="notificationCount" style="display: none;">0</span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications" id="notificationDropdown">
            <li class="dropdown-header">
              You have <span id="notificationHeaderCount">0</span> new notifications
              <a href="#" onclick="markAllAsRead()"><span class="badge rounded-pill bg-primary p-2 ms-2">view all</span></a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <div id="notificationList">
              <!-- Notifications will be loaded here -->
            </div>
          </ul>
        </li>

        <li class="nav-item dropdown pe-3">
          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
            <img src="assets/img/profile-img.jpg" alt="Profile" class="rounded-circle">
            <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $_SESSION['username']; ?></span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6><?php echo $_SESSION['username']; ?></h6>
              <span><?php echo $_SESSION['role']; ?></span>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="pages-faq.html">
                <i class="bi bi-question-circle"></i>
                <span>Need Help?</span>
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>

  <!-- Sidebar section... -->
  <aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
      <li class="nav-item">
        <a class="nav-link collapsed" href="index.php">
          <i class="bi bi-grid"></i>
          <span>Home</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-menu-button-wide"></i><span>Employee Management</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="components-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="add.php">
              <i class="bi bi-circle"></i><span>Add Employee</span>
            </a>
          </li>
          <li>
            <a href="delete.php">
              <i class="bi bi-circle"></i><span>Delete Employee</span>
            </a>
          </li>
          <li>
            <a href="view_all.php">
              <i class="bi bi-circle"></i><span>View All Employee</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="recruitment_process.php">
          <i class="bi bi-journal-text"></i><span>Recruitment Process</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#attendance-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-gem"></i><span>Attendance</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="attendance-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="attendance_employer.php">
              <i class="bi bi-circle"></i><span>Clock in & out</span>
            </a>
          </li>
          <li>
            <a href="v_all_attendance.php">
              <i class="bi bi-circle"></i><span>View All Employee Attendance</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#charts-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-bar-chart"></i><span>Leave Management</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="charts-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="leave_tracking.php">
              <i class="bi bi-circle"></i><span>Leave Tracking</span>
            </a>
          </li>
          <li>
            <a href="AL.php">
              <i class="bi bi-circle"></i><span>Apply Leave</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-gem"></i><span>Payroll</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="icons-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="P_payroll.php">
              <i class="bi bi-circle"></i><span>Process Payroll</span>
            </a>
          </li>
          <li>
            <a href="C_payslip.php">
              <i class="bi bi-circle"></i><span>Check Payslip</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#claim-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-currency-dollar"></i><span>Claim Management</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="claim-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="R_claim.php">
              <i class="bi bi-circle"></i><span>Request Claim</span>
            </a>
          </li>
          <li>
            <a href="AR_claim.php">
              <i class="bi bi-circle"></i><span>Approve/Reject Claim</span>
            </a>
          </li>
        </ul>
      </li>
    </ul>
  </aside>

  <main id="main" class="main">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1>Payroll Summary</h1>
    </div>

    <?php
    // Display error messages
    if (isset($error_message) && !empty($error_message)) {
        echo "<div class='alert alert-warning alert-dismissible fade show' role='alert'>";
        echo "<strong>Warning!</strong> " . htmlspecialchars($error_message);
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
        echo "</div>";
    }
    
    // Display success messages
    if (isset($_SESSION['payroll_status'])) {
        echo "<div class='alert alert-info alert-dismissible fade show' role='alert'>";
        echo "<strong>Status:</strong> " . htmlspecialchars($_SESSION['payroll_status']);
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
        echo "</div>";
        unset($_SESSION['payroll_status']);
    }
    ?>

    <div class="container mt-5">
      <form method="POST" class="row g-3 align-items-end" id="payrollForm">
        <div class="col-md-3">
          <label for="month">Month</label>
          <select name="month" class="form-select">
            <?php
            for ($m = 1; $m <= 12; $m++) {
                $m_val = str_pad($m, 2, '0', STR_PAD_LEFT);
                $selected = ($m_val == $selected_month) ? 'selected' : '';
                echo "<option value='$m_val' $selected>" . date('F', mktime(0, 0, 0, $m, 10)) . "</option>";
            }
            ?>
          </select>
        </div>
        <div class="col-md-2">
          <label for="year">Year</label>
          <select name="year" class="form-select">
            <?php
            for ($y = 2023; $y <= date('Y'); $y++) {
                $selected = ($y == $selected_year) ? 'selected' : '';
                echo "<option value='$y' $selected>$y</option>";
            }
            ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="employee_id">Employee</label>
          <select name="employee_id" class="form-select">
            <option value="">-- Select Employee --</option>
            <?= $emp_options ?>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary">View</button>
        </div>
      </form>

      <?php if (!empty($employee_name)): ?>
        <div class="alert alert-success mt-3">
          <strong>Selected Employee:</strong> ID: <?= $selected_emp ?> - <?= htmlspecialchars($employee_name) ?> 
          <span class="badge bg-success">Active</span>
          <strong>Period:</strong> <?= date('F', mktime(0, 0, 0, $selected_month, 10)) ?> <?= $selected_year ?>
        </div>
      <?php endif; ?>

      <?php 
      // Show employee navigation progress if an employee is selected
      if (!empty($selected_emp)): 
        // Get all ACTIVE employees ordered by ID for progress tracking
        $emp_list = [];
        $res = mysqli_query($conn, "
            SELECT p.personal_id, p.full_name 
            FROM personal_information p 
            LEFT JOIN employeelogin e ON p.personal_id = e.ID 
            WHERE (e.deleted_at IS NULL OR e.deleted_at = '') 
            AND e.ID IS NOT NULL
            ORDER BY p.personal_id ASC
        ");
        while ($row = mysqli_fetch_assoc($res)) {
            $emp_list[] = $row;
        }

        // Find current employee position
        $currentIndex = array_search($selected_emp, array_column($emp_list, 'personal_id'));
        $totalEmployees = count($emp_list);
        
        if ($currentIndex !== false):
          $progressPercentage = (($currentIndex + 1) / $totalEmployees) * 100;
      ?>
        <div class="employee-nav-info">
          <div class="current-position">
            Processing Employee <?= $currentIndex + 1 ?> of <?= $totalEmployees ?> 
            <span class="text-muted">(ID: <?= $selected_emp ?> - <?= htmlspecialchars($employee_name) ?>)</span>
          </div>
          <div class="progress-bar-custom">
            <div class="progress-fill" style="width: <?= $progressPercentage ?>%">
              <?= round($progressPercentage, 1) ?>%
            </div>
          </div>
          <small class="text-muted">Follow employee ID sequence for systematic payroll processing</small>
        </div>
      <?php 
        endif;
      endif; 
      ?>
    </div>

    <?php if (!empty($employee_name) && $payroll_data): 
        // Ensure payroll_data exists and set defaults
        $payroll_data = $payroll_data ?? [];
        $payroll_data += [
            'employee_salary' => 0,
            'allowance'       => 0,
            'overtime_pay'    => 0,
            'epf'             => 0,
            'socso'           => 0,
            'eis'             => 0,
            'pcb'             => 0
        ];

        $salary = (float)($payroll_data['employee_salary'] ?? 0);
        $allowance = isset($_POST['allowance_input']) && is_numeric($_POST['allowance_input']) 
            ? (float)$_POST['allowance_input'] 
            : (float)($payroll_data['allowance'] ?? 0);
        $overtime = isset($_POST['overtime_input']) && is_numeric($_POST['overtime_input']) 
            ? (float)$_POST['overtime_input'] 
            : (float)($payroll_data['overtime_pay'] ?? 0);

        $gross_pay = $salary + $allowance + $overtime + $total_claim;
        $annual_gross = $gross_pay * 12;
        $annual_epf = $salary * 0.11 * 12;
        $annual_chargeable = $annual_gross - $annual_epf;

        function calcAnnualTax($chargeable) {
            $brackets = [
                [0, 5000, 0, 0],
                [5001, 20000, 150, 0.01],
                [20001, 35000, 600, 0.03],
                [35001, 50000, 1500, 0.06],
                [50001, 70000, 3700, 0.11],
                [70001, 100000, 5700, 0.19],
                [100001, 400000, 75000, 0.25],
                [400001, 600000, 84400, 0.26],
                [600001, 2000000, 136400, 0.28],
                [2000001, PHP_INT_MAX, 528400, 0.30],
            ];
            foreach ($brackets as $b) {
                [$min, $max, $base, $rate] = $b;
                if ($chargeable >= $min && $chargeable <= $max) {
                    return $base + ($chargeable - $min) * $rate;
                }
            }
            return 0;
        }

        if ($annual_chargeable <= 34000) {
            $annual_tax = 0;
        } else {
            $annual_tax = calcAnnualTax($annual_chargeable);
        }
        $pcb_monthly = round($annual_tax / 12, 2);
        if ($pcb_monthly < 10) { $pcb_monthly = 0; }

        $payroll_data['epf'] = $salary * 0.11;
        $payroll_data['socso'] = $salary * 0.005;
        $payroll_data['eis'] = $salary * 0.002;
        $payroll_data['pcb'] = $pcb_monthly;
        $payroll_data['allowance'] = $allowance;
        $payroll_data['overtime_pay'] = $overtime;
        $payroll_data['total_deductions'] = $payroll_data['epf'] + $payroll_data['socso'] + $payroll_data['eis'] + $payroll_data['pcb'];
        $payroll_data['net_salary'] = $gross_pay - $payroll_data['total_deductions'];
        $payroll_data['company_epf'] = $salary * 0.13;
        $payroll_data['company_socso'] = $salary * 0.0175;
        $payroll_data['company_eis'] = $salary * 0.002;
    ?>

    <div class="payroll-container">
      <!-- Column 1: Employee Info -->
      <div class="column">
        <h3><?= htmlspecialchars($employee_name) ?></h3>
        <div class="item"><span>Employee ID:</span><span><?= $selected_emp ?></span></div>
        <div class="item"><span>Period:</span><span><?= date('F', mktime(0, 0, 0, $selected_month, 10)) ?> <?= $selected_year ?></span></div>
        <div class="item">
          <span>Employee Salary:</span>
          <span style="display:block; text-align:right;">
            RM <?= number_format((float)str_replace(',', '', $payroll_data['employee_salary']), 2) ?>
          </span>
        </div>
        
        <!-- Allowance -->
        <div class="item">
          <span>Allowance</span>
          <span id="allowanceDisplay" onclick="toggleAllowanceInput()" class="editable-money" style="cursor:pointer;">
            RM <?= number_format($allowance, 2) ?>
          </span>
          <input type="number" step="0.01" min="0" name="allowance_input" id="allowanceInput"
                 value="<?= $allowance ?>"
                 class="editable-money" style="display:none; width: 100px; text-align:right;" />
        </div>

        <!-- Overtime -->
        <div class="item">
          <span>Overtime Pay</span>
          <span id="overtimeDisplay" onclick="toggleOvertimeInput()" class="editable-money" style="cursor:pointer;">
            RM <?= number_format($overtime, 2) ?>
          </span>
          <input type="number" step="0.01" min="0" name="overtime_input" id="overtimeInput"
                 value="<?= $overtime ?>"
                 class="editable-money" style="display:none; width: 100px; text-align:right;" />
        </div>
      </div>

      <!-- Column 2: Monthly Activity -->
      <div class="column">
        <h3>Monthly Activity</h3>
        <div class="item"><span>Days Present</span><span><?= $attendance_summary['present'] ?></span></div>
        <div class="item"><span>Days On Leave</span><span><?= $attendance_summary['on_leave'] ?></span></div>
        <div class="item"><span>Days Absent</span><span><?= $attendance_summary['absent'] ?></span></div>
        <div class="item"><span>Total Claims</span><span>RM <?= number_format($total_claim, 2) ?></span></div>
      </div>

      <!-- Column 3: Deductions -->
      <div class="column">
        <h3>Deductions</h3>
        <div class="item"><span>EPF (11%)</span><span>RM <?= number_format($payroll_data['epf'], 2) ?></span></div>
        <div class="item"><span>SOCSO</span><span>RM <?= number_format($payroll_data['socso'], 2) ?></span></div>
        <div class="item"><span>EIS</span><span>RM <?= number_format($payroll_data['eis'], 2) ?></span></div>
        <div class="item"><span>PCB (Tax)</span><span>RM <?= number_format($payroll_data['pcb'], 2) ?></span></div>
        <div class="item bold"><span>Total</span><span>RM <?= number_format($payroll_data['total_deductions'], 2) ?></span></div>
      </div>

      <!-- Column 4: Pay Summary -->
      <div class="column">
        <h3>Pay Summary</h3>
        <div class="item"><span>Gross Pay</span><span id="grossPayDisplay">RM <?= number_format($salary, 2) ?></span></div>
        <div class="item"><span>+ Allowance</span><span id="allowanceSummary">RM <?= number_format($allowance, 2) ?></span></div>
        <div class="item"><span>+ Overtime</span><span id="overtimeSummary">RM <?= number_format($overtime, 2) ?></span></div>
        <div class="item"><span>+ Claims</span><span id="claimsDisplay">RM <?= number_format($total_claim, 2) ?></span></div>
        <div class="item"><span>- Deductions</span><span id="deductionsDisplay">RM <?= number_format($payroll_data['total_deductions'], 2) ?></span></div>
        <div class="item highlight">
          <span>Net Pay</span>
          <span id="netPayDisplay">RM <?= number_format($payroll_data['net_salary'], 2) ?></span>
        </div>
        
        <h3 style="margin-top:20px;">Company Contribution</h3>
        <div class="item"><span>EPF (13%)</span><span>RM <?= number_format($payroll_data['company_epf'], 2) ?></span></div>
        <div class="item"><span>SOCSO</span><span>RM <?= number_format($payroll_data['company_socso'], 2) ?></span></div>
        <div class="item"><span>EIS</span><span>RM <?= number_format($payroll_data['company_eis'], 2) ?></span></div>
      </div>
    </div>

    <?php if (!empty($selected_emp)): ?>
      <div class="text-center mt-4">
        <?php
        // Get all ACTIVE employees ordered by ID
        $emp_list = [];
        $res = mysqli_query($conn, "
            SELECT p.personal_id, p.full_name 
            FROM personal_information p 
            LEFT JOIN employeelogin e ON p.personal_id = e.ID 
            WHERE (e.deleted_at IS NULL OR e.deleted_at = '') 
            AND e.ID IS NOT NULL
            ORDER BY p.personal_id ASC
        ");
        while ($row = mysqli_fetch_assoc($res)) {
            $emp_list[] = $row;
        }

        // Find current employee position
        $currentIndex = array_search($selected_emp, array_column($emp_list, 'personal_id'));

        if ($currentIndex !== false && $currentIndex < count($emp_list) - 1): 
            // Show "Next Employee" button
            $nextIndex = $currentIndex + 1;
            $next_emp = $emp_list[$nextIndex]['personal_id'];
            $next_name = $emp_list[$nextIndex]['full_name'];
        ?>
        <form method="POST" id="nextEmployeeForm">
          <input type="hidden" name="month" value="<?= htmlspecialchars($selected_month) ?>">
          <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
          <input type="hidden" name="employee_id" value="<?= htmlspecialchars($selected_emp) ?>">
          <input type="hidden" name="save_current" value="1">
          <input type="hidden" name="goto_next" value="<?= htmlspecialchars($next_emp) ?>">

          <!-- FIXED: Payroll fields with current values -->
          <input type="hidden" name="basic_salary" value="<?= htmlspecialchars($payroll_data['employee_salary'] ?? 0) ?>">
          <input type="hidden" name="allowance_input" id="form_allowance_input" value="<?= htmlspecialchars($allowance) ?>">
          <input type="hidden" name="overtime_input" id="form_overtime_input" value="<?= htmlspecialchars($overtime) ?>">
          <input type="hidden" name="total_claims" value="<?= htmlspecialchars($total_claim) ?>">

          <button type="submit" class="btn btn-success">
            Process Current & Go to Next (ID: <?= $next_emp ?> - <?= htmlspecialchars($next_name) ?>) ➡️
          </button>
        </form>
        
        <?php elseif ($currentIndex === count($emp_list) - 1): ?>
          <!-- Last employee: Show "Process Payroll" and "View Report" buttons -->
          <form method="POST" id="processPayrollForm">
            <input type="hidden" name="month" value="<?= htmlspecialchars($selected_month) ?>">
            <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
            <input type="hidden" name="employee_id" value="<?= htmlspecialchars($selected_emp) ?>">
            <input type="hidden" name="save_current" value="1">

            <!-- FIXED: Payroll fields with current values -->
            <input type="hidden" name="basic_salary" value="<?= htmlspecialchars($payroll_data['employee_salary'] ?? 0) ?>">
            <input type="hidden" name="allowance_input" id="form_allowance_input_final" value="<?= htmlspecialchars($allowance) ?>">
            <input type="hidden" name="overtime_input" id="form_overtime_input_final" value="<?= htmlspecialchars($overtime) ?>">
            <input type="hidden" name="total_claims" value="<?= htmlspecialchars($total_claim) ?>">

            <button type="submit" class="btn btn-success">
              ✅ Process Payroll for <?= date('F', mktime(0, 0, 0, $selected_month, 10)) ?> <?= $selected_year ?> (Last Employee)
            </button>
          </form>
          <br>
          
          <!-- View Report Form -->
          <form method="GET" action="view_report.php">
            <input type="hidden" name="month" value="<?= htmlspecialchars($selected_month) ?>">
            <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
            <input type="hidden" name="employee_id" value="<?= htmlspecialchars($selected_emp) ?>">

            <button type="submit" class="btn btn-info">
              📊 View Report for <?= date('F', mktime(0, 0, 0, $selected_month, 10)) ?> <?= $selected_year ?>
            </button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php endif; ?>
  </main>

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>SMEasyHR</span></strong>. All Rights Reserved
    </div>
  </footer>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.min.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>
  
<script>
    // FIXED: Global variables to track current values
    let currentAllowance = <?= $allowance ?>;
    let currentOvertime = <?= $overtime ?>;
    let hasUnsavedChanges = false;

    document.addEventListener('DOMContentLoaded', function() {
        const monthSelect = document.querySelector('select[name="month"]');
        const yearSelect = document.querySelector('select[name="year"]');
        const employeeSelect = document.querySelector('select[name="employee_id"]');
        
        // Initialize current values from page load
        const allowanceInput = document.getElementById('allowanceInput');
        const overtimeInput = document.getElementById('overtimeInput');
        
        if (allowanceInput) currentAllowance = parseFloat(allowanceInput.value || 0);
        if (overtimeInput) currentOvertime = parseFloat(overtimeInput.value || 0);
        
        // Auto-submit when month or year changes (only if employee is already selected)
        monthSelect.addEventListener('change', function() {
            if (employeeSelect.value) {
                document.getElementById('payrollForm').submit();
            }
        });
        
        yearSelect.addEventListener('change', function() {
            if (employeeSelect.value) {
                document.getElementById('payrollForm').submit();
            }
        });
        
        // FIXED: Initialize form interception
        setTimeout(interceptFormSubmission, 500);
    });

    // FIXED: Function to disable/enable process buttons
    function toggleProcessButtons(disable = true) {
        const processButtons = document.querySelectorAll('button[type="submit"]');
        processButtons.forEach(button => {
            if (button.textContent.includes('Process') || button.textContent.includes('Go to Next')) {
                button.disabled = disable;
                if (disable) {
                    button.style.opacity = '0.5';
                    button.style.cursor = 'not-allowed';
                    // Add warning text if not already present
                    if (!button.nextElementSibling || !button.nextElementSibling.classList.contains('recalc-warning')) {
                        const warning = document.createElement('div');
                        warning.className = 'recalc-warning text-warning mt-2';
                        warning.style.fontSize = '0.9em';
                        warning.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Please recalculate before processing payroll';
                        button.parentNode.insertBefore(warning, button.nextSibling);
                    }
                } else {
                    button.style.opacity = '1';
                    button.style.cursor = 'pointer';
                    // Remove warning text
                    const warning = button.nextElementSibling;
                    if (warning && warning.classList.contains('recalc-warning')) {
                        warning.remove();
                    }
                }
            }
        });
    }

    // Allowance editing functions
    function toggleAllowanceInput() {
        const display = document.getElementById('allowanceDisplay');
        const input = document.getElementById('allowanceInput');

        display.style.display = 'none';
        input.style.display = 'inline-block';
        input.focus();
        input.select();
    }

    function toggleOvertimeInput() {
        const display = document.getElementById('overtimeDisplay');
        const input = document.getElementById('overtimeInput');

        display.style.display = 'none';
        input.style.display = 'inline-block';
        input.focus();
        input.select();
    }

    // Event listeners for input fields
    document.addEventListener('DOMContentLoaded', function() {
        const allowanceInput = document.getElementById('allowanceInput');
        const overtimeInput = document.getElementById('overtimeInput');
        
        if (allowanceInput) {
            allowanceInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    finishEditingAllowance();
                }
            });
            
            allowanceInput.addEventListener('blur', function() {
                finishEditingAllowance();
            });
            
            allowanceInput.addEventListener('input', function() {
                currentAllowance = parseFloat(this.value || 0);
                hasUnsavedChanges = true;
                showRecalcButton();
                // FIXED: Disable process buttons when changes are made
                toggleProcessButtons(true);
            });
        }
        
        if (overtimeInput) {
            overtimeInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    finishEditingOvertime();
                }
            });
            
            overtimeInput.addEventListener('blur', function() {
                finishEditingOvertime();
            });
            
            overtimeInput.addEventListener('input', function() {
                currentOvertime = parseFloat(this.value || 0);
                hasUnsavedChanges = true;
                showRecalcButton();
                // FIXED: Disable process buttons when changes are made
                toggleProcessButtons(true);
            });
        }
    });

    function finishEditingAllowance() {
        const input = document.getElementById('allowanceInput');
        const display = document.getElementById('allowanceDisplay');
        const value = parseFloat(input.value || 0);
        
        currentAllowance = value;
        display.textContent = 'RM ' + value.toFixed(2);
        display.style.display = 'inline-block';
        input.style.display = 'none';
    }

    function finishEditingOvertime() {
        const input = document.getElementById('overtimeInput');
        const display = document.getElementById('overtimeDisplay');
        const value = parseFloat(input.value || 0);
        
        currentOvertime = value;
        display.textContent = 'RM ' + value.toFixed(2);
        display.style.display = 'inline-block';
        input.style.display = 'none';
    }

    function showRecalcButton() {
        const netPayElement = document.getElementById("netPayDisplay");
        if (netPayElement && hasUnsavedChanges) {
            netPayElement.innerHTML = 
                '<button type="button" class="btn btn-warning btn-sm pulse-animation" onclick="recalculateNetPay()">🔄 Recalculate Required</button>';
        }
    }

    function recalculateNetPay() {
        // Get PHP values safely
        const salary = <?= isset($payroll_data['employee_salary']) ? (float)$payroll_data['employee_salary'] : 0 ?>;
        const totalClaims = <?= (float)$total_claim ?>;
        const pcb = <?= isset($payroll_data['pcb']) ? (float)$payroll_data['pcb'] : 0 ?>;
        
        // Get current input values
        const allowance = currentAllowance;
        const overtime = currentOvertime;

        const epf = salary * 0.11;
        const socso = salary * 0.005;
        const eis = salary * 0.002;

        const totalDeductions = epf + socso + eis + pcb;
        const gross = salary + allowance + overtime + totalClaims;
        const net = gross - totalDeductions;

        // Update Pay Summary display
        document.getElementById("grossPayDisplay").textContent = "RM " + salary.toFixed(2);
        document.getElementById("allowanceSummary").textContent = "RM " + allowance.toFixed(2);
        document.getElementById("overtimeSummary").textContent = "RM " + overtime.toFixed(2);
        document.getElementById("claimsDisplay").textContent = "RM " + totalClaims.toFixed(2);
        document.getElementById("deductionsDisplay").textContent = "RM " + totalDeductions.toFixed(2);
        document.getElementById("netPayDisplay").textContent = "RM " + net.toFixed(2);

        // FIXED: Mark changes as saved and re-enable process buttons
        hasUnsavedChanges = false;
        toggleProcessButtons(false);
        
        console.log('Recalculated - Allowance:', allowance, 'Overtime:', overtime, 'Net:', net);
        showSuccessMessage('Payroll recalculated successfully! Net Pay: RM ' + net.toFixed(2) + ' - You can now process the payroll.');
    }

    // FIXED: Enhanced form submission prevention when recalculation is needed
    function interceptFormSubmission() {
        // Find all forms that might submit payroll data
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            // Check if this form contains payroll-related hidden fields
            if (form.querySelector('input[name="save_current"]')) {
                form.addEventListener('submit', function(e) {
                    // FIXED: Prevent submission if recalculation is needed
                    if (hasUnsavedChanges) {
                        e.preventDefault();
                        showWarningMessage('Please recalculate the payroll before processing!');
                        
                        // Highlight the recalculate button
                        const recalcButton = document.querySelector('button[onclick="recalculateNetPay()"]');
                        if (recalcButton) {
                            recalcButton.style.animation = 'pulse 0.5s ease-in-out 3';
                            recalcButton.focus();
                        }
                        return false;
                    }
                    
                    console.log('Form submission intercepted');
                    
                    // Update hidden fields with current values
                    addOrUpdateHiddenField(form, 'allowance_input', currentAllowance.toFixed(2));
                    addOrUpdateHiddenField(form, 'overtime_input', currentOvertime.toFixed(2));
                    
                    console.log('Updated form fields - Allowance:', currentAllowance, 'Overtime:', currentOvertime);
                    
                    // Log all form data for debugging
                    const formData = new FormData(form);
                    console.log('Form data being submitted:');
                    for (let [key, value] of formData.entries()) {
                        console.log(key + ': ' + value);
                    }
                });
            }
        });
    }

    function addOrUpdateHiddenField(form, name, value) {
        let field = form.querySelector(`input[name="${name}"]`);
        if (field) {
            field.value = value;
            console.log('Updated field', name, 'to', value);
        } else {
            field = document.createElement('input');
            field.type = 'hidden';
            field.name = name;
            field.value = value;
            form.appendChild(field);
            console.log('Created field', name, 'with value', value);
        }
    }

    function showSuccessMessage(message) {
        // Remove any existing success messages
        const existingMessages = document.querySelectorAll('.dynamic-success-message');
        existingMessages.forEach(msg => msg.remove());
        
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success alert-dismissible fade show dynamic-success-message';
        successDiv.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            left: 20px;
            max-width: 500px;
            margin: 0 auto;
            z-index: 10000;
            animation: slideDown 0.5s ease;
        `;
        
        successDiv.innerHTML = `
            <strong>✅ Success!</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Insert after the main title
        const mainTitle = document.querySelector('main h1');
        if (mainTitle && mainTitle.parentNode) {
            mainTitle.parentNode.insertBefore(successDiv, mainTitle.nextSibling);
        } else {
            document.body.appendChild(successDiv);
        }
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (document.body.contains(successDiv)) {
                successDiv.style.animation = 'slideUp 0.3s ease';
                setTimeout(() => {
                    if (document.body.contains(successDiv)) {
                        successDiv.remove();
                    }
                }, 300);
            }
        }, 5000);
    }

    // FIXED: Function to show warning messages
    function showWarningMessage(message) {
        // Remove any existing warning messages
        const existingMessages = document.querySelectorAll('.dynamic-warning-message');
        existingMessages.forEach(msg => msg.remove());
        
        const warningDiv = document.createElement('div');
        warningDiv.className = 'alert alert-warning alert-dismissible fade show dynamic-warning-message';
        warningDiv.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            left: 20px;
            max-width: 500px;
            margin: 0 auto;
            z-index: 10000;
            animation: slideDown 0.5s ease;
        `;
        
        warningDiv.innerHTML = `
            <strong>⚠️ Warning!</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Insert after the main title
        const mainTitle = document.querySelector('main h1');
        if (mainTitle && mainTitle.parentNode) {
            mainTitle.parentNode.insertBefore(warningDiv, mainTitle.nextSibling);
        } else {
            document.body.appendChild(warningDiv);
        }
        
        // Auto-remove after 4 seconds
        setTimeout(() => {
            if (document.body.contains(warningDiv)) {
                warningDiv.style.animation = 'slideUp 0.3s ease';
                setTimeout(() => {
                    if (document.body.contains(warningDiv)) {
                        warningDiv.remove();
                    }
                }, 300);
            }
        }, 4000);
    }

    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(0); opacity: 1; }
            to { transform: translateY(-20px); opacity: 0; }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse-animation {
            animation: pulse 1s ease-in-out infinite;
        }
        
        .recalc-warning {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    `;
    document.head.appendChild(style);
</script>
</body>
</html>