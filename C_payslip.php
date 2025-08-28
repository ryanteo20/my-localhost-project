<?php
require('database.php');
require('session.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the logged-in user's ID
$employee_id = $_SESSION['ID'];  // Assume the user_id is stored in session when logged in

// Initialize empty payroll data
$payslip_data = null;
$month = null;
$year = null;
$no_data_found = false; // Flag for no data found

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $month = isset($_POST['month']) ? (int)trim($_POST['month']) : null;
    $year = isset($_POST['year']) ? (int)trim($_POST['year']) : null;

    // Convert month number to month name for display
    $monthName = date("F", mktime(0, 0, 0, $month, 10));

    // Retrieve the payroll data for the selected month and year
    if ($employee_id && $month && $year) {
        // Query to fetch payroll data for the selected month and year
        $query = "SELECT * FROM payroll_transactions WHERE employee_id = ? AND MONTH(pay_period_start) = ? AND YEAR(pay_period_start) = ? ORDER BY payment_date DESC";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $payslip_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            $no_data_found = true; // Set flag to true if no data is found
        }

        mysqli_stmt_close($stmt);
    }
}

$query = "
SELECT pt.*, el.username AS employee_name
FROM payroll_transactions pt
JOIN employeelogin el ON pt.employee_id = el.ID
WHERE pt.employee_id = ? 
AND MONTH(pt.pay_period_start) = ? 
AND YEAR(pt.pay_period_start) = ?
ORDER BY pt.payment_date DESC
";

$stmt = mysqli_prepare($con, $query);
if (!$stmt) {
    die("MySQL prepare failed: " . mysqli_error($con));
}

mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $payslip_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $no_data_found = true;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SMEasyHR - Employer</title>
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
    <div class="container mt-5">
      <h1>Payroll Report</h1>

      <!-- Form to select month and year -->
      <form method="POST" class="row g-3 align-items-end">
        <div class="col-md-3">
          <label for="month">Month</label>
          <select name="month" class="form-select" required>
            <option value="">-- Select Month --</option>
            <?php
            for ($m = 1; $m <= 12; $m++) {
                $selected = ($m == $month) ? 'selected' : '';
                echo "<option value='$m' $selected>" . date('F', mktime(0, 0, 0, $m, 10)) . "</option>";
            }
            ?>
          </select>
        </div>
        <div class="col-md-2">
          <label for="year">Year</label>
          <select name="year" class="form-select" required>
            <option value="">-- Select Year --</option>
            <?php
            for ($y = 2023; $y <= date('Y'); $y++) {
                $selected = ($y == $year) ? 'selected' : '';
                echo "<option value='$y' $selected>$y</option>";
            }
            ?>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary">View</button>
        </div>
      </form>
<br><Br>
      <!-- If data is available, display payroll report -->
      <?php if ($payslip_data): ?>
        <h3>Payroll Report for <?php echo $monthName . ' ' . $year; ?></h3>

              <!-- Navigation buttons -->
        <div class="table-container">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Employee</th>
                <th>Basic Salary</th>
                <th>Allowance</th>
                <th>Overtime</th>
                <th>Claim</th>
                <th>EPF</th>
                <th>SOCSO</th>
                <th>EIS</th>
                <th>Tax</th>
                <th>Net Pay</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $total_net_pay = 0;
              $epf_total = 0;
              $socso_total = 0;
              $tax_total = 0;
              $eis_total = 0;
              $claims_total = 0;
              $allowance_total = 0;
              $overtime_total = 0;
              $basic_salary_total = 0;

              foreach ($payslip_data as $employee) {
                  $basic_salary_total += floatval($employee['basic_salary']);
                  $allowance_total += floatval($employee['allowances']);
                  $overtime_total += floatval($employee['overtime_pay']);
                  $claims_total += floatval($employee['total_claims']);
                  $epf_total += floatval($employee['epf_amount']);
                  $socso_total += floatval($employee['socso_amount']);
                  $eis_total += floatval($employee['eis_amount']);
                  $tax_total += floatval($employee['tax_amount']);
                  $total_net_pay += floatval($employee['net_pay']);

                $employee_display_name = isset($employee['employee_name']) ? $employee['employee_name'] : 'Unknown Employee';


                  echo "<tr>
                          <td>{$employee_display_name}</td>
                          <td>RM " . number_format($employee['basic_salary'], 2) . "</td>
                          <td>RM " . number_format($employee['allowances'], 2) . "</td>
                          <td>RM " . number_format($employee['overtime_pay'], 2) . "</td>
                          <td>RM " . number_format($employee['total_claims'], 2) . "</td>
                          <td>RM " . number_format($employee['epf_amount'], 2) . "</td>
                          <td>RM " . number_format($employee['socso_amount'], 2) . "</td>
                          <td>RM " . number_format($employee['eis_amount'], 2) . "</td>
                          <td>RM " . number_format($employee['tax_amount'], 2) . "</td>
                          <td>RM " . number_format($employee['net_pay'], 2) . "</td>
                        </tr>";
              }
              ?>
            </tbody>
          </table>
        </div>

        <div class="totals">
          <div class="row">
            <div class="col-md-3">
              <h5>Total Basic Salary</h5>
              <p>RM <?php echo number_format($basic_salary_total, 2); ?></p>
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
              <h5>Total Net Pay</h5>
              <p>RM <?php echo number_format($total_net_pay, 2); ?></p>
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
        </div>
                <!-- Send Everyone Payslip Button -->
        <div class="text-center mt-3">
                <button class="btn btn-primary" onclick="sendAllPayslips()" onsubmit="showLoading()">Print Payslip</button>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>SMEasyHR</span></strong>. All Rights Reserved
    </div>
  </footer>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.min.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/js/main.js"></script>
  <script>
<?php if ($no_data_found): ?>
  alert('No payroll data found for this month and year.');
<?php endif; ?>

function sendAllPayslips() {
        window.location.href = "send_payslip.php?month=<?= $month ?>&year=<?= $year ?>";
}
</script>



</body>

</html>
