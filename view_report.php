<?php
require('database.php');  // Include your database connection script
require('session.php');   // Include session management script

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get month and year from previous page (required parameters)
$month = isset($_GET['month']) ? (int)trim($_GET['month']) : null;
$year = isset($_GET['year']) ? (int)trim($_GET['year']) : null;

if (!$month || !$year) {
    die("Error: Month and year must be specified.");
}

// Check if there are any unconfirmed statuses for the month
$query = "
    SELECT COUNT(*) AS unconfirmed_count
    FROM payroll_transactions
    WHERE MONTH(pay_period_start) = ? AND YEAR(pay_period_start) = ? AND status != 'confirmed'
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $month, $year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

$allConfirmed = $row['unconfirmed_count'] == 0; // true if all are confirmed
mysqli_stmt_close($stmt);

// Validate month range
if ($month < 1 || $month > 12) {
    die('Error: Invalid month selected. Please go back and select a valid month.');
}

// Convert month number to month name for display
$monthName = date("F", mktime(0, 0, 0, $month, 10));

// Use prepared statement to prevent SQL injection - CORRECTED QUERY
$payroll_query = "
SELECT el.ID AS employee_id, el.username as employee_name, 
       pt.basic_salary, pt.allowances, pt.overtime_pay, 
       pt.epf_amount, pt.socso_amount, pt.tax_amount, pt.eis_amount, pt.net_pay, 
       pt.deductions, pt.total_claims, pt.status
FROM payroll_transactions pt
INNER JOIN employeelogin el ON pt.employee_id = el.ID
WHERE MONTH(pt.pay_period_start) = ? AND YEAR(pt.pay_period_start) = ?
ORDER BY el.ID ASC
";

$stmt = mysqli_prepare($conn, $payroll_query);
mysqli_stmt_bind_param($stmt, "ii", $month, $year);
mysqli_stmt_execute($stmt);
$payroll_result = mysqli_stmt_get_result($stmt);

// Check if query executed successfully
if ($payroll_result === false) {
    die('Error executing query: ' . mysqli_error($conn));
}

// Check if the success or error message session variable is set
if (isset($_SESSION['message'])) {
    // Display message inside a Bootstrap modal
    echo '
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ' . $_SESSION['message'] . '
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>';

    // Unset the session message after displaying it
    unset($_SESSION['message']);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>SMEasyHR - Employer</title>

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">
  
  <style>
    /* Green color for the More Actions dropdown button */
    [id^="more-actions-btn-"] {
        background-color: #28a745 !important;  /* Green background */
        color: white !important;               /* White text */
        border: none !important;
        padding: 5px 15px !important;
        cursor: pointer !important;
        font-size: 12px !important;
        text-align: center !important;
        border-radius: 4px !important;         /* Rounded corners for consistency */
    }

    /* Hover effect for the More Actions dropdown button */
    [id^="more-actions-btn-"]:hover {
        background-color: #218838 !important;  /* Darker green on hover */
    }

    /* Styling for the dropdown menu */
    .dropdown-menu {
        min-width: auto !important;            /* Prevent extra width */
    }

    /* Dropdown item styling */
    .dropdown-item {
        padding: 10px !important;
        font-size: 12px !important;
    }

    /* Hover effect for dropdown items */
    .dropdown-item:hover {
        background-color: #218838 !important;  /* Darker green on hover */
        color: white !important;
    }

    .table-container {
      margin-top: 30px;
    }
    .confirm-btn {
      background-color: #17a2b8;
      color: white;
      border: none;
      padding: 5px 15px;
      cursor: pointer;
      font-size: 12px;
    }
    .confirm-btn:hover {
      background-color: #138496;
    }
    .table th, .table td {
      vertical-align: middle;
      text-align: center;
    }
    .table th {
      text-align: center;
    }
    .table-striped tbody tr:nth-of-type(odd) {
      background-color: #f9f9f9;
    }
    .totals {
      margin-top: 20px;
    }
    .totals h5 {
      margin-bottom: 10px;
    }
    .month-year-selector {
      margin-bottom: 20px;
    }
    .no-print {
      /* Hide navigation buttons when printing */
    }
    @media print {
      .no-print {
        display: none !important;
      }
      .sidebar {
        display: none !important;
      }
      #main {
        margin-left: 0 !important;
      }
    }
    
    /* Highlight calculated net pay */
    .calculated-net-pay {
      background-color: #e8f5e8;
      font-weight: bold;
    }
    
    /* Warning for incorrect net pay */
    .incorrect-net-pay {
      background-color: #ffe6e6;
      color: #dc3545;
      font-weight: bold;
    }
  
  </style>
    <?php include 'includes/chatbot-includes.php'; ?>
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
        <a class="nav-link collapsed" href="recruitment_process.php">
          <i class="bi bi-journal-text"></i><span>Recruitment Process</span>
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
      </li><!-- End Payroll Nav -->

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
      </li><!-- End Claim Management Nav -->
    </ul>

  </aside>

  <main id="main" class="main">
    <div class="container mt-5">
      <h1>Payroll Report - <?php echo $monthName . ' ' . $year; ?></h1>
      
      <!-- Navigation buttons -->
      <div class="row mb-3 no-print">
        <div class="col-md-6">
          <button onclick="history.back()" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Selection
          </button>
        </div>
        <div class="col-md-6 text-end">
          <button onclick="window.print()" class="btn btn-outline-primary">
            <i class="bi bi-printer"></i> Print Report
          </button>
        </div>
      </div>
      
      <div class="table-container">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Employee ID</th>
              <th>Employee</th>
              <th>Basic Salary</th>
              <th>Allowance</th>
              <th>Overtime</th>
              <th>Claim</th>
              <th>EPF</th>
              <th>SOCSO</th>
              <th>Tax</th>
              <th>EIS</th>
              <th>Net Pay</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php
                $total_net_pay = 0;
                $total_calculated_net_pay = 0;
                $epf_total = 0;
                $socso_total = 0;
                $tax_total = 0;
                $eis_total = 0;
                $claims_total = 0;
                $allowance_total = 0;
                $overtime_total = 0;
                $basic_salary_total = 0;

                if (mysqli_num_rows($payroll_result) > 0) {
                    while ($employee = mysqli_fetch_assoc($payroll_result)) {
                        
                        $basic_salary_total += floatval($employee['basic_salary'] ?? 0);
                        $allowance_total += floatval($employee['allowances'] ?? 0);
                        $overtime_total += floatval($employee['overtime_pay'] ?? 0);
                        $claims_total += floatval($employee['total_claims'] ?? 0);
                        $epf_total += floatval($employee['epf_amount'] ?? 0);
                        $socso_total += floatval($employee['socso_amount'] ?? 0);
                        $tax_total += floatval($employee['tax_amount'] ?? 0);
                        $eis_total += floatval($employee['eis_amount'] ?? 0);
                        $total_net_pay += floatval($employee['net_pay'] ?? 0);

                        // Calculate correct net pay (without deductions field)
                        $calculated_net_pay = floatval($employee['basic_salary'] ?? 0) + 
                                            floatval($employee['allowances'] ?? 0) + 
                                            floatval($employee['overtime_pay'] ?? 0) + 
                                            floatval($employee['total_claims'] ?? 0) - 
                                            floatval($employee['epf_amount'] ?? 0) - 
                                            floatval($employee['socso_amount'] ?? 0) - 
                                            floatval($employee['tax_amount'] ?? 0) - 
                                            floatval($employee['eis_amount'] ?? 0);
                        
                        $total_calculated_net_pay += $calculated_net_pay;

                        // Use employee_name instead of full_name based on the corrected query
                        $employee_display_name = isset($employee['employee_name']) ? $employee['employee_name'] : 
                                                (isset($employee['full_name']) ? $employee['full_name'] : 'Unknown Employee');

                        // Check if the status is confirmed
                        $status = isset($employee['status']) ? $employee['status'] : 'unconfirmed';

                        // Check if stored net pay matches calculated net pay
                        $net_pay_difference = abs(floatval($employee['net_pay'] ?? 0) - $calculated_net_pay);
                        $is_net_pay_correct = $net_pay_difference < 0.01; // Allow for small rounding differences

                        echo "<tr>
                                <td>{$employee['employee_id']}</td>
                                <td>{$employee_display_name}</td>
                                <td>RM " . number_format($employee['basic_salary'] ?? 0, 2) . "</td>
                                <td>RM " . number_format($employee['allowances'] ?? 0, 2) . "</td>
                                <td>RM " . number_format($employee['overtime_pay'] ?? 0, 2) . "</td>
                                <td>RM " . number_format($employee['total_claims'] ?? 0, 2) . "</td>
                                <td>RM " . number_format($employee['epf_amount'] ?? 0, 2) . "</td>
                                <td>RM " . number_format($employee['socso_amount'] ?? 0, 2) . "</td>
                                <td>RM " . number_format($employee['tax_amount'] ?? 0, 2) . "</td>
                                <td>RM " . number_format($employee['eis_amount'] ?? 0, 2) . "</td>";

                        // Display net pay with highlighting if incorrect
                        if ($is_net_pay_correct) {
                            echo "<td class='calculated-net-pay'>RM " . number_format($calculated_net_pay, 2) . "</td>";
                        } else {
                            echo "<td class='incorrect-net-pay' title='Stored: RM " . number_format($employee['net_pay'] ?? 0, 2) . " | Calculated: RM " . number_format($calculated_net_pay, 2) . "'>
                                    RM " . number_format($calculated_net_pay, 2) . "
                                    <br><small>(⚠️ Mismatch: Stored RM " . number_format($employee['net_pay'] ?? 0, 2) . ")</small>
                                  </td>";
                        }

                        // If status is confirmed, show "Send payslip" or dropdown, otherwise show "Confirm amount"
                        if ($status == 'confirmed') {
                            // Use dynamic IDs for the More Actions button
                            echo "<td>
                                    <div class='dropdown'>
                                        <button class='btn confirm-btn dropdown-toggle' id='more-actions-btn-" . $employee['employee_id'] . "' type='button' data-bs-toggle='dropdown' aria-expanded='false'>
                                            More actions
                                        </button>
                                        <ul class='dropdown-menu' aria-labelledby='dropdownMenuButton'>
                                            <li><a class='dropdown-item' href='#' onclick='sendPayslip({$employee['employee_id']})'>Send payslip</a></li>
                                            <li><a class='dropdown-item' href='#' onclick='downloadPayslip({$employee['employee_id']})'>Download payslip</a></li>
                                            <li><a class='dropdown-item' href='#' onclick='unconfirmPayment({$employee['employee_id']}, \"{$employee_display_name}\")'>Unconfirm</a></li>";
                            
                            // Add fix net pay option if there's a mismatch
                            if (!$is_net_pay_correct) {
                                echo "<li><hr class='dropdown-divider'></li>
                                      <li><a class='dropdown-item text-warning' href='#' onclick='fixNetPay({$employee['employee_id']}, {$calculated_net_pay})'>🔧 Fix Net Pay</a></li>";
                            }
                            
                            echo "        </ul>
                                    </div>
                                </td>";
                        } else {
                            echo "<td><button class='confirm-btn' data-employee-id='{$employee['employee_id']}' onclick='confirmPayment({$employee['employee_id']}, \"{$employee_display_name}\")'>Confirm amount</button></td>";
                        }

                        echo "</tr>";
                        
                    }
                } else {
                    echo "<tr><td colspan='12' class='text-center'>No payroll data found for " . $monthName . " " . $year . "</td></tr>";
                }
                ?>
          </tbody>
        </table>
      </div>

      <div class="totals">
        <div class="row">
          <div class="col-md-3">
            <h5>Total Net Pay</h5>
            <p class="text-success">RM <?php echo number_format($total_calculated_net_pay, 2); ?></p>
          </div>
          <div class="col-md-3">
            <h5>Total Allowances</h5>
            <p>RM <?php echo number_format($allowance_total, 2); ?></p>
          </div>
          <div class="col-md-3">
            <h5>Total Overtime</h5>
            <p>RM <?php echo number_format($overtime_total, 2); ?></p>
          </div>
          <div class="col-md-3">
            <h5>Total Claims</h5>
            <p>RM <?php echo number_format($claims_total, 2); ?></p>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3">
            <h5>Total payable to EPF</h5>
            <p>RM <?php echo number_format($epf_total, 2); ?></p>
          </div>
          <div class="col-md-3">
            <h5>Total payable to SOCSO</h5>
            <p>RM <?php echo number_format($socso_total, 2); ?></p>
          </div>
          <div class="col-md-3">
            <h5>Total payable to income tax</h5>
            <p>RM <?php echo number_format($tax_total, 2); ?></p>
          </div>
          <div class="col-md-3">
            <h5>Total payable to EIS</h5>
            <p>RM <?php echo number_format($eis_total, 2); ?></p>
          </div>
        </div>
        <div class="row mt-3">
          <?php if (abs($total_net_pay - $total_calculated_net_pay) > 0.01): ?>
          <div class="col-md-6">
            <h4>Total Net Pay (Stored)</h4>
            <p class="h5 text-warning">RM <?php echo number_format($total_net_pay, 2); ?></p>
            <small class="text-muted">Difference: RM <?php echo number_format($total_calculated_net_pay - $total_net_pay, 2); ?></small>
          </div>
          <?php endif; ?>
        </div>
      </div>

        <!-- Auto-Fix Net Pay Button -->
        <?php if (abs($total_net_pay - $total_calculated_net_pay) > 0.01): ?>
        <div class="text-center mt-3">
            <div class="alert alert-warning">
                <strong>⚠️ Net Pay Calculation Mismatches Detected!</strong><br>
                Total difference: RM <?php echo number_format($total_calculated_net_pay - $total_net_pay, 2); ?>
            </div>
            <a href="auto_fix_all_net_pay.php?month=<?= $month ?>&year=<?= $year ?>" 
               class="btn btn-warning btn-lg">
                🔧 Auto-Fix All Net Pay Calculations
            </a>
        </div>
        <?php endif; ?>

        <!-- Send Everyone Payslip Button -->
        <div class="text-center mt-3">
                    <button class="btn btn-success" onclick="confirmEveryone()">
                        ✅ Confirm Everyone
                    </button>
            <?php if ($allConfirmed): ?>
                <button class="btn btn-primary" onclick="sendAllPayslips()" onsubmit="showLoading()">Send Everyone Payslip</button>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>
                    Send Everyone Payslip (Some statuses unconfirmed)
                </button>
            <?php endif; ?>
        </div>
    </div>

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">&copy; Copyright <strong><span>SMEasyHR</span></strong>. All Rights Reserved</div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

  <script>

function sendAllPayslips() {
    if (confirm("Are you sure you want to send payslips to all employees?")) {
        window.location.href = "send_all_payslips.php?month=<?= $month ?>&year=<?= $year ?>";
    }
}

function confirmPayment(employeeId, employeeName) {
  if (confirm('Confirm payment for ' + employeeName + '?')) {
    // Get month and year from PHP
    let month = <?php echo $month; ?>;
    let year = <?php echo $year; ?>;

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "update_payroll_status.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    // Pass the employee ID, month, and year to update the status
    xhr.onreadystatechange = function () {
      if (xhr.readyState == 4 && xhr.status == 200) {
        alert('Payment confirmed for ' + employeeName);
        location.reload(); // Reload the page to show updated status
      }
    };

    xhr.send("employee_id=" + employeeId + "&month=" + month + "&year=" + year);
  }
}

function downloadPayslip(employeeId) {
    // Logic to download payslip (e.g., initiate file download or send a request)
    alert('Downloading payslip for employee ID ' + employeeId);
    location.reload(); // Reload to reflect the changes
}

function unconfirmPayment(employeeId, employeeName) {
    if (confirm('Are you sure you want to unconfirm the payment for ' + employeeName + '?')) {
        let month = <?php echo $month; ?>;
        let year = <?php echo $year; ?>;

        let xhr = new XMLHttpRequest();
        xhr.open("POST", "update_payroll_status_action.php", true); // Pointing to the correct PHP file
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // Send employee ID, month, year to update the status to "processed"
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                if(xhr.responseText == 'Status updated to processed successfully') {
                    alert('Payment status changed to processed for ' + employeeName);
                    location.reload(); // Reload the page to show updated status
                } else {
                    alert('Error updating status: ' + xhr.responseText);
                }
            }
        };

        // Sending employee ID, month, and year to the backend to update the status to "processed"
        xhr.send("employee_id=" + employeeId + "&month=" + month + "&year=" + year);
    }
}

function fixNetPay(employeeId, correctNetPay) {
    if (confirm('Fix the net pay calculation for this employee?\\nThis will recalculate and update the stored net pay to the correct amount.')) {
        let month = <?php echo $month; ?>;
        let year = <?php echo $year; ?>;

        // Show loading state
        let button = event.target.closest('.dropdown').querySelector('button');
        let originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Fixing...';
        button.disabled = true;

        let xhr = new XMLHttpRequest();
        xhr.open("POST", "fix_net_pay.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                if(xhr.responseText.includes('success')) {
                    alert('Net pay has been recalculated and updated successfully!\\n' + xhr.responseText);
                    location.reload();
                } else {
                    alert('Error fixing net pay: ' + xhr.responseText);
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            }
        };

        xhr.send("employee_id=" + employeeId + "&month=" + month + "&year=" + year);
    }
}

// Show the modal if the message is set
window.onload = function() {
    if (document.getElementById("messageModal")) {
        const modal = new bootstrap.Modal(document.getElementById("messageModal"));
        modal.show();  // Show the modal
    }
};

function showLoading() {
    document.getElementById('loading-spinner').classList.remove('d-none');
    event.target.disabled = true;
    event.target.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';
}

function sendAllPayslips() {
    if (confirm("Are you sure you want to send payslips to all employees?")) {
        // Show loading state
        event.target.disabled = true;
        event.target.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';
        
        window.location.href = "send_all_payslips.php?month=<?= $month ?>&year=<?= $year ?>";
    }
}

function confirmEveryone() {
    if (confirm("Are you sure you want to confirm payment for ALL employees for <?= date('F', mktime(0, 0, 0, $month, 10)) . ' ' . $year ?>?")) {
        let month = <?php echo $month; ?>;
        let year = <?php echo $year; ?>;

        // Show loading state
        event.target.disabled = true;
        event.target.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Confirming...';

        let xhr = new XMLHttpRequest();
        xhr.open("POST", "confirm_all_payroll.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                if(xhr.responseText.includes('success')) {
                    alert('All payments have been confirmed successfully!');
                    location.reload();
                } else {
                    alert('Error confirming payments: ' + xhr.responseText);
                    event.target.disabled = false;
                    event.target.innerHTML = '✅ Confirm Everyone';
                }
            }
        };

        xhr.send("month=" + month + "&year=" + year);
    }
}

function downloadAllPayslips() {
    if (confirm("Download all payslips for <?= date('F', mktime(0, 0, 0, $month, 10)) . ' ' . $year ?>?")) {
        // Show loading state
        event.target.disabled = true;
        event.target.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Preparing...';
        
        window.open("download_all_payslips.php?month=<?= $month ?>&year=<?= $year ?>", '_blank');
        
        // Reset button after 3 seconds
        setTimeout(() => {
            event.target.disabled = false;
            event.target.innerHTML = '📥 Download All Payslips';
        }, 3000);
    }
}

function confirmPayment(employeeId, employeeName) {
  if (confirm('Confirm payment for ' + employeeName + '?')) {
    // Get month and year from PHP
    let month = <?php echo $month; ?>;
    let year = <?php echo $year; ?>;

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "update_payroll_status.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    // Pass the employee ID, month, and year to update the status
    xhr.onreadystatechange = function () {
      if (xhr.readyState == 4 && xhr.status == 200) {
        alert('Payment confirmed for ' + employeeName);
        location.reload(); // Reload the page to show updated status
      }
    };

    xhr.send("employee_id=" + employeeId + "&month=" + month + "&year=" + year);
  }
}

function sendPayslip(employeeId) {
    if (confirm('Send payslip to this employee?')) {
        let month = <?php echo $month; ?>;
        let year = <?php echo $year; ?>;

        // Show loading state
        let button = event.target.closest('.dropdown').querySelector('button');
        let originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';
        button.disabled = true;

        let xhr = new XMLHttpRequest();
        xhr.open("POST", "send_payslip.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                if(xhr.responseText.includes('success')) {
                    alert('Payslip sent successfully!\\n' + xhr.responseText);
                    location.reload();
                } else {
                    alert('Error sending payslip: ' + xhr.responseText);
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            }
        };

        xhr.send("employee_id=" + employeeId + "&month=" + month + "&year=" + year);
    }
}

function downloadPayslip(employeeId) {
    let month = <?php echo $month; ?>;
    let year = <?php echo $year; ?>;
    
    // Show loading state
    let button = event.target.closest('.dropdown').querySelector('button');
    let originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Preparing...';
    button.disabled = true;

    // Open download in new window/tab
    let downloadUrl = `download_payslip.php?employee_id=${employeeId}&month=${month}&year=${year}`;
    window.open(downloadUrl, '_blank');

    // Reset button after 2 seconds
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 2000);
}

// Show the modal if the message is set
window.onload = function() {
    if (document.getElementById("messageModal")) {
        const modal = new bootstrap.Modal(document.getElementById("messageModal"));
        modal.show();
    }
};

function sendPayslip(employeeId) {
    if (confirm('Send payslip to this employee?')) {
        let month = <?php echo $month; ?>;
        let year = <?php echo $year; ?>;

        // Show loading state
        let button = event.target.closest('.dropdown').querySelector('button');
        let originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';
        button.disabled = true;

        let xhr = new XMLHttpRequest();
        xhr.open("POST", "send_individual_payslip.php", true);  // Updated filename
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                if(xhr.responseText.includes('success')) {
                    alert('Payslip sent successfully!\n' + xhr.responseText);
                    location.reload();
                } else {
                    alert('Error sending payslip: ' + xhr.responseText);
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            }
        };

        xhr.send("employee_id=" + employeeId + "&month=" + month + "&year=" + year);
    }
}
 </script>

</body>
</html>

<?php
// Clean up
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>