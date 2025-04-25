<?php
require('database.php');
require('session.php');
date_default_timezone_set("Asia/Kuala_Lumpur");

error_reporting(E_ALL);
ini_set('display_errors', 1);

function handleRegistration($con) {
  if (isset($_REQUEST['username'], $_REQUEST['password'], $_REQUEST['role'])) {
      $username = mysqli_real_escape_string($con, $_REQUEST['username']);
      $password = mysqli_real_escape_string($con, $_REQUEST['password']);
      $hashed_password = md5($password);
      $reg_date = date("Y-m-d H:i:s");
      $role = mysqli_real_escape_string($con, $_REQUEST['role']);
      $first_login = 1;

      $check_query = "SELECT * FROM `employeelogin` WHERE username = ?";
      $check_stmt = mysqli_prepare($con, $check_query);
      mysqli_stmt_bind_param($check_stmt, "s", $username);
      mysqli_stmt_execute($check_stmt);
      $check_result = mysqli_stmt_get_result($check_stmt);

      if(mysqli_num_rows($check_result) > 0) {
          $success_message = "Employee already exists.";
      } else {
          $query = "INSERT INTO `employeelogin` (username, password, reg_date, role, first_login) VALUES (?, ?, ?, ?, ?)";
          $stmt = mysqli_prepare($con, $query);
          mysqli_stmt_bind_param($stmt, "ssssi", $username, $hashed_password, $reg_date, $role, $first_login);
          mysqli_stmt_execute($stmt);

          $success_message = "Employee registered successfully.";
      }

      return $success_message;
  }
}

$success_message = handleRegistration($con);
?>
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

  </aside>

  <main id="main" class="main">

  <div class="pagetitle">
    <h1>Add Employee</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item">Employee Management</li>
        <li class="breadcrumb-item active"><a href="add.php">Add Employee</a></li>
        <li class="breadcrumb-item active"><a href="add.php">Register New Account</a></li>
      </ol>
    </nav>
  </div>

  <div id="form-container">
    <div class="card" id="registration-form-container">
      <div class="card-body">
        <h5 class="card-title">1. Register New Account For New Employee</h5>
        <div class="card">
          <div class="card-body">
            <form id="registerForm" class="row g-3" method="post">
              <div class="col-12">
                <label for="inputName4" class="form-label">Username</label>
                <input type="text" class="form-control" id="inputName4" name="username">
              </div>
              <div class="col-12">
                <label for="inputPassword4" class="form-label">Password</label>
                <input type="password" class="form-control" id="inputPassword4" name="password">
              </div>
              <div class="col-12">
                <label for="inputRole" class="form-label">Role</label>
                  <select id="inputRole" class="form-select" name="role">
                    <option value="" disabled selected hidden>Choose...</option>
                    <option value="Employer">Employer</option>
                    <option value="Employee">Employee</option>
                  </select>
              </div>
              <div class="text-center">
                <button type="reset" class="btn btn-secondary">Reset</button>
                <button type="submit" class="btn btn-primary" id="register-button">Register</button>
                <button type="button" class="btn btn-secondary" id="next-button">Next</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

 
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Latest Register Employee Account</h5>

            <!-- Default Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Username</th>
                        <th scope="col">Password</th>
                        <th scope="col">Registration Date</th>
                        <th scope="col">Role</th>
                    </tr>
                </thead>
                <tbody id="latest-employee-info">
                  <?php
                    // Include your database connection
                    require('database.php');

                    // Prepare the SQL query to select data from the employeelogin table
                    $query = "SELECT ID, username, password, reg_date, role FROM employeelogin ORDER BY ID DESC LIMIT 1";

                    // Execute the query
                    $result = mysqli_query($con, $query);

                    // Check if there are any rows returned
                    if (mysqli_num_rows($result) > 0) {
                        // Fetch each row of data and display it in the table
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>{$row['ID']}</td>";
                            echo "<td>{$row['username']}</td>";
                            echo "<td>{$row['password']}</td>";
                            echo "<td>{$row['reg_date']}</td>";
                            echo "<td>{$row['role']}</td>";
                            echo "</tr>";
                        }
                    } else {
                        // If no data is found, display a message
                        echo "<tr><td colspan='4'>No data found</td></tr>";
                    }

                    // Close the connection
                    mysqli_close($con);
                    ?>
                </tbody>
            </table>
            <!-- End Default Table -->
        </div>
    </div>
    
  </main>
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
    document.addEventListener('DOMContentLoaded', function() {
      const registerForm = document.getElementById('registerForm');
      let formSubmitted = false;

      registerForm.addEventListener('submit', function(event) {
          if (formSubmitted) {
              event.preventDefault();
              return;
          }

          formSubmitted = true;
      });

    document.getElementById('next-button').addEventListener('click', function() {
      window.location.href = 'add_p.php';
    });
});
</script>

</body>

</html>