<?php
require('database.php');
require('session.php');

// Default to current month/year
$selected_month = $_POST['month'] ?? date('m');
$selected_year = $_POST['year'] ?? date('Y');
$selected_emp = $_POST['employee_id'] ?? '';

// Fetch employees
$employees = mysqli_query($con, "SELECT personal_id, full_name FROM personal_information");

// Build employee options
$emp_options = '';
while ($emp = mysqli_fetch_assoc($employees)) {
  $selected = ($emp['personal_id'] == $selected_emp) ? 'selected' : '';
  $emp_options .= "<option value='{$emp['personal_id']}' $selected>{$emp['full_name']}</option>";
}

$payroll_data = null;

if (!empty($selected_emp)) {
  $query = "SELECT * FROM payroll WHERE employee_id = $selected_emp AND month = $selected_month AND year = $selected_year";
  $res = mysqli_query($con, $query);

  if (!$res) {
    die("Query Error: " . mysqli_error($con));
  }

  $payroll_data = mysqli_fetch_assoc($res);
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

    <div class="pagetitle">
      <h1>Home</h1>
    </div><!-- End Page Title -->

    <div class="container mt-5">
        <h3>Payroll Summary</h3>
        <form method="POST" class="row g-3 align-items-end">
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

        <?php if ($payroll_data): ?>
            <hr>
            <div class="row mt-4">
            <div class="col-md-4">
                <h5>Basic Earnings</h5>
                <ul class="list-group">
                <li class="list-group-item">Basic Salary: RM <?= number_format($payroll_data['basic_salary'], 2) ?></li>
                <li class="list-group-item">Allowance: RM <?= number_format($payroll_data['allowance'], 2) ?></li>
                <li class="list-group-item">Overtime Pay: RM <?= number_format($payroll_data['overtime_pay'], 2) ?></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Deductions</h5>
                <ul class="list-group">
                <li class="list-group-item">EPF (11%): RM <?= number_format($payroll_data['epf'], 2) ?></li>
                <li class="list-group-item">SOCSO: RM <?= number_format($payroll_data['socso'], 2) ?></li>
                <li class="list-group-item">EIS: RM <?= number_format($payroll_data['eis'], 2) ?></li>
                <li class="list-group-item">PCB (Tax): RM <?= number_format($payroll_data['pcb'], 2) ?></li>
                <li class="list-group-item">Total Deductions: RM <?= number_format($payroll_data['total_deductions'], 2) ?></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Net Pay</h5>
                <ul class="list-group">
                <li class="list-group-item">Net Salary: <strong>RM <?= number_format($payroll_data['net_salary'], 2) ?></strong></li>
                </ul>
                <h6 class="mt-4">Employer Contribution (Est.)</h6>
                <ul class="list-group">
                <li class="list-group-item">EPF (13%): RM <?= number_format($payroll_data['basic_salary'] * 0.13, 2) ?></li>
                <li class="list-group-item">SOCSO (Est.): RM <?= number_format($payroll_data['basic_salary'] * 0.017, 2) ?></li>
                <li class="list-group-item">EIS (Est.): RM <?= number_format($payroll_data['basic_salary'] * 0.002, 2) ?></li>
                </ul>
            </div>
            </div>
        <?php elseif ($selected_emp): ?>
            <div class="alert alert-warning mt-4">No payroll data found for the selected period.</div>
        <?php endif; ?>
        </div>

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

</body>

</html>