<?php
require('database.php');
require('session.php');

function handleEmploymentDetails($con) {
  if (isset($_REQUEST['inputType'])) {
      // Sanitize form data
      $employeeType = stripslashes($_REQUEST['inputType']);
      $employeeType = mysqli_real_escape_string($con, $employeeType);
      $status = stripslashes($_REQUEST['inputStatus']);
      $status = mysqli_real_escape_string($con, $status);
      $position = stripslashes($_REQUEST['inputPosition']);
      $position= mysqli_real_escape_string($con, $position);
      $department = stripslashes($_REQUEST['inputDepartment']);
      $department = mysqli_real_escape_string($con, $department);
      $employmentStart = stripslashes($_REQUEST['inputStart']);
      $employmentStart = mysqli_real_escape_string($con, $employmentStart);
      $employmentEnd = stripslashes($_REQUEST['inputEnd']);
      $employmentEnd = mysqli_real_escape_string($con, $employmentEnd);
      
      // SQL statement to insert data into employment_detail table
      $query = "INSERT INTO `employment_detail` (employment_type, employment_status, employment_position, employment_department, employment_start, employment_end) 
              VALUES ('$employeeType', '$status', '$position', '$department', '$employmentStart', '$employmentEnd')";

      // Execute the query
      $result = mysqli_query($con, $query);

      // Check if the insertion was successful
      if ($result) {
          return "Employment details inserted successfully.";
      } else {
          return "Error: " . mysqli_error($con);
      }
  }
}

$success_message = handleEmploymentDetails($con);
?>

<!DOCTYPE HTML>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

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
        <a class="nav-link collapsed" data-bs-target="#tables-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-layout-text-window-reverse"></i><span>Attendance</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="tables-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="v_all_attandance.php">
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
          <li class="breadcrumb-item active"><a href="add_p.php">Personal Information</a></li>
          <li class="breadcrumb-item active"><a href="add_d.php">Employee Details</a></li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

  <div id="form-container">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">3. Insert Employment Details</h5>
            <!-- Employment Details Form -->
            <form id="employment-info" class="row g-3" method="post">
              <div class="col-md-4">
                  <label for="inputType" class="form-label">Employee Type</label>
                  <select id="inputType" class="form-select" name="inputType">
                    <option value="" disabled selected hidden>Choose...</option>
                    <option value="Full-Time">Full-Time</option>
                    <option value="Monthly_Part-Time">Monthly Part-Time</option>
                    <option value="Hourly_Part-Time">Hourly Part-Time</option>
                    <option value="Intern">Intern</option>
                    <option>Other</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label for="inputStatus" class="form-label">Status</label>
                  <select id="inputStatus" class="form-select" name="inputStatus">
                    <option value="" disabled selected hidden>Choose...</option>
                    <option value="Active-Confirmed">Active - Confirmed</option>
                    <option value="Active-Probation">Active - Probation</option>
                    <option value="Resigned">Resigned</option>
                    <option value="Terminated">Terminated</option>
                    <option value="Suspended">Suspended</option>
                    <option>Other</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label for="inputPosition" class="form-label">Position</label>
                  <select id="inputPosition" class="form-select" name="inputPosition">
                    <option value="" disabled selected hidden>Choose...</option>
                    <option value="General Manager">General Manager</option>
                    <option value="Operation Manager">Operation Manager</option>
                    <option value="Event Executive">Event Executive</option>
                    <option value="Event Decorator">Event Decorator</option>
                    <option value="Finance Manager">Finance Manager</option>
                    <option>Other</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label for="inputDepartment" class="form-label">Department</label>
                  <input type="text" class="form-control" id="inputDepartment" name="inputDepartment">
                </div>
                <div class="col-md-4">
                  <label for="inputStart" class="form-label">Employment Start</label>
                  <input type="date" class="form-control" id="inputStart" name="inputStart">
                </div>
                <div class="col-md-4">
                  <label for="inputEnd" class="form-label">Employment End</label>
                  <input type="date" class="form-control" id="inputEnd" name="inputEnd">
                </div>
                <div class="text-center">
                <button type="reset" class="btn btn-secondary">Reset</button>
                <button type="submit" class="btn btn-primary" id="employment-button">Submit</button>
                <button type="button" class="btn btn-secondary" id="next3-button">Next</button>
                </div>
                </form><!-- End Employment Details Form -->
        </div>
    </div>
</div>
<div class="card">
        <div class="card-body">
            <h5 class="card-title">Latest Employment Details</h5>

            <!-- Default Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Employment Type</th>
                        <th scope="col">Status</th>
                        <th scope="col">Position</th>
                        <th scope="col">Department</th>
                        <th scope="col">Employment Start Date</th>
                        <th scope="col">Employment End Date</th>
                    </tr>
                </thead>
                <tbody id="latest-employment-detail">
                  <?php
                    // Include your database connection
                    require('database.php');

                    // Prepare the SQL query to select data from the employeelogin table
                    $query = "SELECT employment_id, employment_type, employment_status, employment_position, employment_department, employment_start, employment_end FROM employment_detail ORDER BY employment_id DESC LIMIT 1";

                    // Execute the query
                    $result = mysqli_query($con, $query);

                    // Check if there are any rows returned
                    if (mysqli_num_rows($result) > 0) {
                        // Fetch each row of data and display it in the table
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>{$row['employment_id']}</td>";
                            echo "<td>{$row['employment_type']}</td>";
                            echo "<td>{$row['employment_status']}</td>";
                            echo "<td>{$row['employment_position']}</td>";
                            echo "<td>{$row['employment_department']}</td>";
                            echo "<td>{$row['employment_start']}</td>";
                            echo "<td>{$row['employment_end']}</td>";
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
  //add employment detail form
  document.addEventListener('DOMContentLoaded', function() {
      const employmentForm = document.getElementById('employment-info');
      let formSubmitted = false;
      employmentForm.addEventListener('submit', function(event) {
          if (formSubmitted) {
              event.preventDefault();
              return;
          }
          formSubmitted = true;
      });
    document.getElementById('next3-button').addEventListener('click', function() {
      window.location.href = 'add_pr.php';
    });
  });
  </script>

</body>

</html>