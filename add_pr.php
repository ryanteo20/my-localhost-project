<?php
require('database.php');
require('session.php');

function handlePayrollDetails($con) {
  if (isset($_REQUEST['inputS_Type'])) {
      // Retrieve form data
      $salaryType = stripslashes($_REQUEST['inputS_Type']);
      $salaryType = mysqli_real_escape_string($con, $salaryType);
      $account_name = stripslashes($_REQUEST['inputA_Name']);
      $account_name = mysqli_real_escape_string($con, $account_name);
      $account_no = stripslashes($_REQUEST['inputA_No']);
      $account_no = mysqli_real_escape_string($con, $account_no);
      $salary = stripslashes($_REQUEST['inputSalary']);
      $salary = mysqli_real_escape_string($con, $salary);
      $bank_name = stripslashes($_REQUEST['inputB_Name']);
      $bank_name = mysqli_real_escape_string($con, $bank_name);

      // SQL statement
      $query = "INSERT INTO `payroll_detail` (salary_type, bank_account_name, bank_account_no, employee_salary, bank_name) 
              VALUES ('$salaryType', '$account_name', '$account_no', '$salary', '$bank_name')";
      $result = mysqli_query($con, $query);

      // Check if the insertion was successful
      if ($result) {
          return "Payroll details inserted successfully.";
      } else {
          return "Error: " . mysqli_error($con);
      }
  }
}

$success_message = handlePayrollDetails($con);

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

  </aside><!-- End Sidebar-->

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Add Employee</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">Employee Management</li>
          <li class="breadcrumb-item active"><a href="add_d.php">Employee Details</a></li>
          <li class="breadcrumb-item active"><a href="add_pr.php">Payroll Details</a></li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

  <div id="form-container">
    <div class="card" id="payroll_detail">
        <div class="card-body">
            <h5 class="card-title">4. Insert Payroll Details</h5>
            <!-- Payroll Form -->
            <form id="payroll-detail" class="row g-3">
              <div class="col-md-4">
                    <label for="inputS_Type" class="form-label">Salary Type</label>
                    <select id="inputS_Type" class="form-select" name="inputS_Type">
                      <option value="" disabled selected hidden>Choose...</option>
                      <option value="Monthly">Monthly</option>
                      <option value="Daily">Daily</option>
                      <option value="Hourly">Hourly</option>
                    </select>
              </div>
              <div class="col-md-4">
                    <label for="inputA_Name" class="form-label">Bank Account Name</label>
                    <input type="text" class="form-control" id="inputA_Name" name="inputA_Name">
              </div>
              <div class="col-md-4">
                    <label for="inputA_No" class="form-label">Bank Account No</label>
                    <input type="text" class="form-control" id="inputA_No" name="inputA_No">
              </div>
              <div class="col-md-4">
                    <label for="inputSalary" class="form-label">Employee Salary</label>
                    <input type="text" class="form-control" id="inputSalary" name="inputSalary">
              </div>
              <div class="col-md-8">
                    <label for="inputB_Name" class="form-label">Bank Name</label>
                    <input type="text" class="form-control" id="inputB_Name" name="inputB_Name">
              </div>
              <div class="text-center">
                  <button type="reset" class="btn btn-secondary">Reset</button>
                  <button type="submit" class="btn btn-primary" id="payroll-button">Submit</button>
                  <button type="button" class="btn btn-secondary" id="next4-button">Next</button>
              </div>
            </form><!-- End Payroll Form -->
        </div>
    </div>
</div>

<div class="card">
        <div class="card-body">
            <h5 class="card-title">Latest Payroll Details</h5>

            <!-- Default Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Salary Type</th>
                        <th scope="col">Bank Account Name</th>
                        <th scope="col">Bank Account No</th>
                        <th scope="col">Employee Salary</th>
                        <th scope="col">Bank Name</th>
                    </tr>
                </thead>
                <tbody id="latest-payroll-detail">
                  <?php
                    // Include your database connection
                    require('database.php');

                    // Prepare the SQL query to select data from the employeelogin table
                    $query = "SELECT payroll_id, salary_type, bank_account_name, bank_account_no, employee_salary, bank_name FROM payroll_detail ORDER BY payroll_id DESC LIMIT 1";

                    // Execute the query
                    $result = mysqli_query($con, $query);

                    // Check if there are any rows returned
                    if (mysqli_num_rows($result) > 0) {
                        // Fetch each row of data and display it in the table
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>{$row['payroll_id']}</td>";
                            echo "<td>{$row['salary_type']}</td>";
                            echo "<td>{$row['bank_account_name']}</td>";
                            echo "<td>{$row['bank_account_no']}</td>";
                            echo "<td>{$row['employee_salary']}</td>";
                            echo "<td>{$row['bank_name']}</td>";
                            echo "</tr>";
                        }
                    } else {
                        // If no data is found, display a message
                        echo "<tr><td colspan='4'>No data found</td></tr>";
                    }

                    error_reporting(E_ALL);
                    ini_set('display_errors', 1);
                    
                    // Close the connection
                    mysqli_close($con);
                    ?>
                </tbody>
            </table>
            <!-- End Default Table -->
        </div>
    </div>
  </main><!-- End #main -->
  <?php
  if (isset($success_message)) {
      // If a success message is set, output JavaScript to display an alert
      echo "<script>alert('$success_message');</script>";
  }
  ?>
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
    document.getElementById('next4-button').addEventListener('click', function() {
      window.location.href = 'add_ed.php';
    });
  </script>

</body>

</html>