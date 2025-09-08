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

// Fetch employees
$employees = mysqli_query($con, "SELECT personal_id, full_name FROM personal_information");

// Build employee options
$emp_options = '';
while ($emp = mysqli_fetch_assoc($employees)) {
    $selected = ((string)$emp['personal_id'] === (string)$selected_emp) ? 'selected' : '';
    $emp_options .= "<option value='{$emp['personal_id']}' $selected>{$emp['full_name']}</option>";
}

$payroll_data = null;
$employee_name = '';

if (!empty($selected_emp)) {
    // Define emp_id before using it
    $emp_id = (int)$selected_emp;

    // Get employee name for display
    $emp_query = "SELECT full_name FROM personal_information WHERE personal_id = $emp_id";
    $emp_result = mysqli_query($con, $emp_query);
    if ($emp_row = mysqli_fetch_assoc($emp_result)) {
        $employee_name = $emp_row['full_name'];
    }

    if (isset($_POST['allowance_input']) && is_numeric($_POST['allowance_input'])) {
        $payroll_data['allowance'] = (float)$_POST['allowance_input'];
    } else {
        $payroll_data['allowance'] = $payroll_data['allowance'] ?? 0;
    }

    // Get payroll data using emp_id
    $query = "SELECT * FROM payroll_detail WHERE payroll_id = $emp_id";  // Use emp_id here
    $res = mysqli_query($con, $query);

    if (!$res) {
        die("Query Error: " . mysqli_error($con));
    }

    $payroll_data = mysqli_fetch_assoc($res);
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

    $q = "SELECT COUNT(*) as total FROM attendance WHERE employee_id = $emp_id AND MONTH(date) = $month AND YEAR(date) = $year AND status = 'present'";
    $res = mysqli_query($con, $q);
    if ($res) {
        $attendance_summary['present'] = mysqli_fetch_assoc($res)['total'] ?? 0;
    } else {
        echo "<div class='alert alert-danger'>Error in present query: " . mysqli_error($con) . "</div>";
    }

    $q = "SELECT COUNT(*) as total FROM attendance WHERE employee_id = $emp_id AND MONTH(date) = $month AND YEAR(date) = $year AND status = 'on-leave'";
    $res = mysqli_query($con, $q);
    if ($res) {
        $attendance_summary['on-leave'] = mysqli_fetch_assoc($res)['total'] ?? 0;
    } else {
        echo "<div class='alert alert-danger'>Error in present query: " . mysqli_error($con) . "</div>";
    }

    $q = "SELECT COUNT(*) as total FROM attendance WHERE employee_id = $emp_id AND MONTH(date) = $month AND YEAR(date) = $year AND status = 'absent'";
    $res = mysqli_query($con, $q);
    if ($res) {
        $attendance_summary['absent'] = mysqli_fetch_assoc($res)['total'] ?? 0;
    } else {
        echo "<div class='alert alert-danger'>Error in present query: " . mysqli_error($con) . "</div>";
    }

    $q = "SELECT SUM(amount) as total FROM claims WHERE employee_id = $emp_id AND MONTH(transaction_date) = $month AND YEAR(transaction_date) = $year AND status ='Approved'";
    $res = mysqli_query($con, $q);
    if ($res) {
        $total_claim = mysqli_fetch_assoc($res)['total'] ?? 0.00;
    } else {
        echo "<div class='alert alert-danger'>Error in claim query: " . mysqli_error($con) . "</div>";
    }
}
// Initialize allowance and overtime variables properly
$allowance = 0;
$overtime = 0;

if ($payroll_data) {
    // Use POST inputs if they exist (after form submission), otherwise use database values
    $allowance = isset($_POST['allowance_input']) && is_numeric($_POST['allowance_input']) 
        ? (float)$_POST['allowance_input'] 
        : (float)($payroll_data['allowance'] ?? 0);

    $overtime = isset($_POST['overtime_input']) && is_numeric($_POST['overtime_input']) 
        ? (float)$_POST['overtime_input'] 
        : (float)($payroll_data['overtime_pay'] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_current'])) {
    $period_start = date("Y-m-01", strtotime($_POST['year']."-".$_POST['month']."-01"));
    $period_end   = date("Y-m-t", strtotime($_POST['year']."-".$_POST['month']."-01"));
    $payment_date = date("Y-m-d");

    // Get the values, prioritizing the recalculated values
    $final_allowance = 0;
    $final_overtime = 0;

    // Check multiple possible field names
    if (isset($_POST['allowance'])) {
        $final_allowance = (float)$_POST['allowance'];
    } elseif (isset($_POST['allowance_input'])) {
        $final_allowance = (float)$_POST['allowance_input'];
    }

    if (isset($_POST['overtime'])) {
        $final_overtime = (float)$_POST['overtime'];
    } elseif (isset($_POST['overtime_input'])) {
        $final_overtime = (float)$_POST['overtime_input'];
    }

    // Debug output - remove after fixing
    echo "<div class='alert alert-info'>Debug: Allowance = $final_allowance, Overtime = $final_overtime</div>";

    $payrollData = [
        'employee_id'     => (int)$_POST['employee_id'],
        'pay_period_start'=> $period_start,
        'pay_period_end'  => $period_end,
        'payment_date'    => $payment_date,
        'basic_salary'    => (float)$_POST['basic_salary'],
        'allowances'      => $final_allowance,        // This should now have the correct value
        'deductions'      => (float)$_POST['epf'] + (float)$_POST['socso'] + (float)$_POST['eis'] + (float)$_POST['pcb'],
        'tax_amount'      => (float)$_POST['pcb'],
        'epf_amount'      => (float)$_POST['epf'],
        'socso_amount'    => (float)$_POST['socso'],
        'eis_amount'      => (float)$_POST['eis'],
        'overtime_pay'    => $final_overtime,         // This should now have the correct value
        'total_claims'    => (float)$_POST['total_claims'],
        'net_pay'         => (float)$_POST['net_pay'],
        'status'          => 'processed'
    ];

    require_once 'functions.php';
    $result = savePayrollTransaction($con, $payrollData);
    
    if ($result) {
        echo "<div class='alert alert-success'>Payroll saved successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error saving payroll data.</div>";
    }

    // Redirect to next employee if requested
    if (!empty($_POST['goto_next'])) {
        $next_emp = (int)$_POST['goto_next'];
        header("Location: P_payroll.php?month={$_POST['month']}&year={$_POST['year']}&employee_id={$next_emp}");
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>SMEasyHR - Employer</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">
      <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 20px; }
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
        .bold { font-weight: bold; }
        .highlight { color: teal; font-size: 18px; font-weight: bold; }

        .editable-money {
        color: #007bff; /* Bootstrap primary blue */
        font-weight: bold;
        }

    </style>
</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center">
        <img src="assets/img/logo.png" alt="">
        <span class="d-none d-lg-block">SMEasyHR</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div><!-- End Logo -->

    <div class="search-bar">
      <form class="search-form d-flex align-items-center" method="POST" action="#">
        <input type="text" name="query" placeholder="Search" title="Enter search keyword">
        <button type="submit" title="Search"><i class="bi bi-search"></i></button>
      </form>
    </div><!-- End Search Bar -->

    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">

        <li class="nav-item d-block d-lg-none">
          <a class="nav-link nav-icon search-bar-toggle " href="#">
            <i class="bi bi-search"></i>
          </a>
        </li><!-- End Search Icon-->

        
                <!-- Notification Icon -->
        <li class="nav-item dropdown">
          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" id="notificationIcon">
            <i class="bi bi-bell"></i>
            <span class="badge bg-primary badge-number" id="notificationCount" style="display: none;">0</span>
          </a><!-- End Notification Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications" id="notificationDropdown">
            <li class="dropdown-header">
              You have <span id="notificationHeaderCount">0</span> new notifications
              <a href="#" onclick="markAllAsRead()"><span class="badge rounded-pill bg-primary p-2 ms-2">view all</span></a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <div id="notificationList">
              <!-- Notifications will be loaded here -->
            </div>
          </ul><!-- End Notification Dropdown Items -->
        </li><!-- End Notification Nav -->

        <li class="nav-item dropdown pe-3">

          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
            <img src="assets/img/profile-img.jpg" alt="Profile" class="rounded-circle">
            <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $_SESSION['username']; ?></span>
          </a><!-- End Profile Iamge Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
            <h6><?php echo $_SESSION['username']; ?></h6>
              <span><?php echo $_SESSION['role']; ?></span>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="pages-faq.html">
                <i class="bi bi-question-circle"></i>
                <span>Need Help?</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </a>
            </li>

          </ul><!-- End Profile Dropdown Items -->
        </li><!-- End Profile Nav -->

      </ul>
    </nav><!-- End Icons Navigation -->

  </header><!-- End Header -->

<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

  <ul class="sidebar-nav" id="sidebar-nav">

    <li class="nav-item">
      <a class="nav-link collapsed" href="index.php">
        <i class="bi bi-grid"></i>
        <span>Home</span>
      </a>
    </li><!-- End Dashboard Nav -->

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
    </li><!-- End Employee Management Nav -->

    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="recruiment_process.php">
        <i class="bi bi-journal-text"></i><span>Recruiment Process</span>
      </a>
    </li><!-- End Recruiment Process Nav -->

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
    </li><!-- End Attandance Nav -->

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
    </li><!-- End Leave Management Nav -->

    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-gem"></i><span>Payroll</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="icons-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Employer'): ?>
          <li>
            <a href="P_payroll.php">
              <i class="bi bi-circle"></i><span>Process Payroll</span>
            </a>
          </li>
        <?php endif; ?>
        <li>
          <a href="C_payslip.php">
            <i class="bi bi-circle"></i><span>Check Payslip</span>
          </a>
        </li>
      </ul>
    </li>
    <!-- End Payroll Nav -->

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
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Employer'): ?>
          <li>
            <a href="AR_claim.php">
              <i class="bi bi-circle"></i><span>Approve/Reject Claim</span>
            </a>
          </li>
        <?php endif; ?>
        <li>
          <a href="VR_claim.php">
            <i class="bi bi-circle"></i><span>View All Claim</span>
          </a>
        </li>
      </ul>
    </li><!-- End Claim Management Nav -->
  </ul>

</aside>

  <main id="main" class="main">

    <h1>Payroll Summary</h1>

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

    <!-- Display selected employee info -->
    <?php if (!empty($employee_name)): ?>
        <div class="alert alert-info mt-3">
            <strong>Selected Employee:</strong> <?= htmlspecialchars($employee_name) ?> 
            <strong>Period:</strong> <?= date('F', mktime(0, 0, 0, $selected_month, 10)) ?> <?= $selected_year ?>
        </div>
    <?php endif; ?>

    <!-- Your existing payroll data display section -->
    <?php if ($payroll_data): ?>
        <hr>
        <div class="row mt-4">
            <!-- Your existing payroll display code -->
        </div>
    <?php elseif ($selected_emp): ?>
        <div class="alert alert-warning mt-4">No payroll data found for the selected period.</div>
    <?php endif; ?>
</div>

<!-- Updated payroll container section -->
<?php if (!empty($employee_name)): ?>
<div class="payroll-container">
    <!-- Column 1: Employee Info -->
    <div class="column">
        <h3><?= htmlspecialchars($employee_name) ?></h3>
        <div class="item"><span>Employee ID:</span><span><?= $selected_emp ?></span></div>
        <div class="item"><span>Period:</span><span><?= date('F', mktime(0, 0, 0, $selected_month, 10)) ?> <?= $selected_year ?></span></div>
        <?php if ($payroll_data): ?>
            <div class="item">
                <span>Employee Salary:</span>
                <span style="display:block; text-align:right;">
                    RM <?= number_format((float)str_replace(',', '', $payroll_data['employee_salary']), 2) ?>
                </span>
            </div>
        <?php endif; ?>
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
    
    <!-- Rest of your columns... -->
    <?php if ($payroll_data): 
    // Ensure payroll_data exists
    $payroll_data = $payroll_data ?? [];

    // Pre-fill defaults to avoid undefined index warnings
    $payroll_data += [
        'employee_salary' => 0,
        'allowance'       => 0,
        'overtime_pay'    => 0,
        'epf'             => 0,
        'socso'           => 0,
        'eis'             => 0,
        'pcb'             => 0
    ];

    //Fetch values safely, prioritizing POST inputs over database values
    $salary = (float)($payroll_data['employee_salary'] ?? 0);

    // Use POST inputs for allowance and overtime if they exist, otherwise use database values
    $allowance = isset($_POST['allowance_input']) && is_numeric($_POST['allowance_input']) 
        ? (float)$_POST['allowance_input'] 
        : (float)($payroll_data['allowance'] ?? 0);

    $overtime = isset($_POST['overtime_input']) && is_numeric($_POST['overtime_input']) 
        ? (float)$_POST['overtime_input'] 
        : (float)($payroll_data['overtime_pay'] ?? 0);

    // Calculate gross pay with updated values
    $gross_pay = $salary + $allowance + $overtime + $total_claim;

    // Annual calculations for tax
    $annual_gross = $gross_pay * 12;
    $annual_epf = $salary * 0.11 * 12;
    $annual_chargeable = $annual_gross - $annual_epf;


    // Tax bracket function
    function calcAnnualTax($chargeable) {
        $brackets = [
            [0, 5000, 0,     0],
            [5001, 20000, 150,    0.01],
            [20001, 35000, 600,   0.03],
            [35001, 50000, 1500,  0.06],
            [50001, 70000, 3700,  0.11],
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

    // Calculate deductions (based on base salary only)
    $payroll_data['epf'] = $salary * 0.11;
    $payroll_data['socso'] = $salary * 0.005;
    $payroll_data['eis'] = $salary * 0.002;
    $payroll_data['pcb'] = $pcb_monthly;

    // Update payroll_data with current values for display
    $payroll_data['allowance'] = $allowance;
    $payroll_data['overtime_pay'] = $overtime;

    $payroll_data['total_deductions'] = $payroll_data['epf'] + $payroll_data['socso'] + $payroll_data['eis'] + $payroll_data['pcb'];
    $payroll_data['net_salary'] = $gross_pay - $payroll_data['total_deductions'];


    // Employer contributions
    $payroll_data['company_epf']   = $salary * 0.13;
    $payroll_data['company_socso'] = $salary * 0.0175;
    $payroll_data['company_eis']   = $salary * 0.002;
    ?>
        <div class="column">
            <h3>Monthly Activity</h3>
            <div class="item"><span>Days Present</span><span><?= $attendance_summary['present'] ?></span></div>
            <div class="item"><span>Days On Leave</span><span><?= $attendance_summary['on-leave'] ?></span></div>
            <div class="item"><span>Days Absent</span><span><?= $attendance_summary['absent'] ?></span></div>
            <div class="item"><span>Total Claims</span><span>RM <?= number_format($total_claim, 2) ?></span></div>
        </div>
        <!-- Column 2: Earnings -->
        <div class="column">
            <!-- Column 3: Deductions -->
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
            <div class="item"><span>EPF (13%)</span><span>RM <?= number_format($payroll_data['employee_salary'] * 0.13, 2) ?></span></div>
            <div class="item"><span>SOCSO</span><span>RM <?= number_format($payroll_data['employee_salary'] * 0.017, 2) ?></span></div>
            <div class="item"><span>EIS</span><span>RM <?= number_format($payroll_data['employee_salary'] * 0.002, 2) ?></span></div>
        </div>

    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($selected_emp)): ?>
    <div class="text-center mt-4">
        <?php
        // Get all employees ordered by ID
        $emp_list = [];
        $res = mysqli_query($con, "SELECT personal_id, full_name FROM personal_information ORDER BY personal_id ASC");
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

            <!-- Payroll fields -->
            <input type="hidden" name="basic_salary" value="<?= htmlspecialchars($payroll_data['employee_salary'] ?? 0) ?>">
            <input type="hidden" name="allowance" value="<?= htmlspecialchars($allowance) ?>">
            <input type="hidden" name="overtime" value="<?= htmlspecialchars($overtime) ?>">
            <input type="hidden" name="total_claims" value="<?= htmlspecialchars($total_claim) ?>">
            <input type="hidden" name="epf" value="<?= htmlspecialchars($payroll_data['epf'] ?? 0) ?>">
            <input type="hidden" name="socso" value="<?= htmlspecialchars($payroll_data['socso'] ?? 0) ?>">
            <input type="hidden" name="eis" value="<?= htmlspecialchars($payroll_data['eis'] ?? 0) ?>">
            <input type="hidden" name="pcb" value="<?= htmlspecialchars($payroll_data['pcb'] ?? 0) ?>">
            <input type="hidden" name="net_pay" value="<?= htmlspecialchars($payroll_data['net_salary'] ?? 0) ?>">

            <button type="submit" class="btn btn-success">
                Process Current & Go to Next (<?= htmlspecialchars($next_name) ?>) ➡️
            </button>
        </form>
        <?php else: ?>
            <!-- Last employee: Show "View Report" button -->
          <?php if ($currentIndex === count($emp_list) - 1): ?>
              <!-- Last employee: Show "View Report" button -->
<?php if ($currentIndex === count($emp_list) - 1): ?>
    <!-- Last employee: Show "Process Payroll" and "View Report" buttons -->

    <!-- Process Payroll Form -->
    <form method="POST" id="processPayrollForm">
        <input type="hidden" name="month" value="<?= htmlspecialchars($selected_month) ?>">
        <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
        <input type="hidden" name="employee_id" value="<?= htmlspecialchars($selected_emp) ?>">
        <input type="hidden" name="save_current" value="1">

        <!-- Payroll fields -->
        <input type="hidden" name="basic_salary" value="<?= htmlspecialchars($payroll_data['employee_salary'] ?? 0) ?>">
        <input type="hidden" name="allowance" value="<?= htmlspecialchars($allowance) ?>">
        <input type="hidden" name="overtime" value="<?= htmlspecialchars($overtime) ?>">
        <input type="hidden" name="total_claims" value="<?= htmlspecialchars($total_claim) ?>">
        <input type="hidden" name="epf" value="<?= htmlspecialchars($payroll_data['epf'] ?? 0) ?>">
        <input type="hidden" name="socso" value="<?= htmlspecialchars($payroll_data['socso'] ?? 0) ?>">
        <input type="hidden" name="eis" value="<?= htmlspecialchars($payroll_data['eis'] ?? 0) ?>">
        <input type="hidden" name="pcb" value="<?= htmlspecialchars($payroll_data['pcb'] ?? 0) ?>">
        <input type="hidden" name="net_pay" value="<?= htmlspecialchars($payroll_data['net_salary'] ?? 0) ?>">

        <button type="submit" class="btn btn-success">
            ✅ Process Payroll for <?= date('F', mktime(0, 0, 0, $selected_month, 10)) ?> <?= $selected_year ?>
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

          <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>


  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>SMEasyHR</span></strong>. All Rights Reserved
    </div>
  </footer><!-- End Footer -->

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
// Global variables to track current values
let currentAllowance = 0;
let currentOvertime = 0;
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
});

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

// Event listeners for allowance input
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
            hasUnsavedChanges = true;
            showRecalcButton();
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
            hasUnsavedChanges = true;
            showRecalcButton();
        });
    }
});

function finishEditingAllowance() {
    const input = document.getElementById('allowanceInput');
    const display = document.getElementById('allowanceDisplay');
    const value = parseFloat(input.value || 0).toFixed(2);

    display.textContent = 'RM ' + value;
    display.style.display = 'inline-block';
    input.style.display = 'none';
}

function finishEditingOvertime() {
    const input = document.getElementById('overtimeInput');
    const display = document.getElementById('overtimeDisplay');
    const value = parseFloat(input.value || 0).toFixed(2);

    display.textContent = 'RM ' + value;
    display.style.display = 'inline-block';
    input.style.display = 'none';
}

function showRecalcButton() {
    const netPayElement = document.getElementById("netPayDisplay");
    if (netPayElement && hasUnsavedChanges) {
        netPayElement.innerHTML = 
            '<button type="button" class="btn btn-warning btn-sm" onclick="recalculateNetPay()">Recalculate</button>';
    }
}

function recalculateNetPay() {
    // Get PHP values safely
    const salary = <?= isset($payroll_data['employee_salary']) ? (float)$payroll_data['employee_salary'] : 0 ?>;
    const totalClaims = <?= (float)$total_claim ?>;
    const pcb = <?= isset($payroll_data['pcb']) ? (float)$payroll_data['pcb'] : 0 ?>;
    
    // Get current input values
    const allowance = parseFloat(document.getElementById('allowanceInput').value || 0);
    const overtime = parseFloat(document.getElementById('overtimeInput').value || 0);

    // Update global variables
    currentAllowance = allowance;
    currentOvertime = overtime;

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

    // Mark changes as saved (recalculated)
    hasUnsavedChanges = false;
    
    console.log('Recalculated - Allowance:', allowance, 'Overtime:', overtime, 'Net:', net);
    showSuccessMessage('Payroll recalculated successfully!');
}

// Intercept form submissions to add current values
function interceptFormSubmission() {
    // Find all forms that might submit payroll data
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Check if this form contains payroll-related hidden fields
        if (form.querySelector('input[name="save_current"]')) {
            form.addEventListener('submit', function(e) {
                console.log('Form submission intercepted');
                
                // Add or update hidden fields with current values
                addOrUpdateHiddenField(form, 'allowance', currentAllowance.toFixed(2));
                addOrUpdateHiddenField(form, 'overtime', currentOvertime.toFixed(2));
                
                // Also update the original input field names as backup
                addOrUpdateHiddenField(form, 'allowance_input', currentAllowance.toFixed(2));
                addOrUpdateHiddenField(form, 'overtime_input', currentOvertime.toFixed(2));
                
                console.log('Added fields - Allowance:', currentAllowance, 'Overtime:', currentOvertime);
                
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

// Initialize form interception when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure all forms are loaded
    setTimeout(interceptFormSubmission, 500);
});

function showSuccessMessage(message) {
    const successDiv = document.createElement('div');
    successDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    successDiv.textContent = message;
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (document.body.contains(successDiv)) {
                document.body.removeChild(successDiv);
            }
        }, 300);
    }, 2000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>
</body>
</html>