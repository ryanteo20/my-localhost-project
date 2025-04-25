<?php
require('database.php');
require('session.php');

function handleEmployeeDocument($con) {
  if (isset($_REQUEST['inputEducation']) && isset($_FILES['inputIC']) && $_FILES['inputIC']['error'] === UPLOAD_ERR_OK) {
      
      // Retrieve form data
      $inputEducation = mysqli_real_escape_string($con, $_REQUEST['inputEducation']);

      // Handle file upload
      $file = $_FILES['inputIC'];
      $fileName = $file['name'];
      $fileTmpName = $file['tmp_name'];

      // Set the upload directory
      $uploadDir = '/Applications/XAMPP/xamppfiles/htdocs/Image/';

      // Create the target file path
      $targetFilePath = $uploadDir . basename($fileName);

      // Move the uploaded file to the target directory
      if (move_uploaded_file($fileTmpName, $targetFilePath)) {
          // SQL statement (use prepared statement)
          $query = "INSERT INTO `employee_document` (education_level, ic_picture) VALUES (?, ?)";
          $stmt = mysqli_prepare($con, $query);
          mysqli_stmt_bind_param($stmt, "ss", $inputEducation, $targetFilePath);
          
          // Execute the statement
          if (mysqli_stmt_execute($stmt)) {
              return "Employee document inserted successfully.";
          } else {
              return "Error: " . mysqli_stmt_error($stmt);
          }
      } else {
          return "Error uploading file.";
      }
  }
}

$success_message = handleEmployeeDocument($con);

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
          <li class="breadcrumb-item active"><a href="add_pr.php">Payroll Details</a></li>
          <li class="breadcrumb-item active"><a href="add_ed.php">Employee Documentation</a></li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <div id="form-container">
    <div class="card" id="employee-info-form">
        <div class="card-body">
            <h5 class="card-title">5. Insert Employee Documentation</h5>
            <!-- Employee Documentation Form -->
            <form id="employee-document" class="row g-3" method="POST" enctype="multipart/form-data" onsubmit="return handlePayrollDetails(event)">
                <div class="col-md-8">
                    <label for="inputEducation" class="form-label">Education Level</label>
                    <select id="inputEducation" class="form-select" name="inputEducation">
                        <option value="" disabled selected hidden>Choose...</option>
                        <option value="UPSR">UPSR</option>
                        <option value="SRP/PT3/PMR">SRP/PT3/PMR</option>
                        <option value="SPM">SPM</option>
                        <option value="STPM/STPVM">STPM/STPVM</option>
                        <option value="Diploma">Diploma</option>
                        <option value="Bachelors Degree">Bachelors Degree</option>
                        <option value="Masters Degree">Masters Degree</option>
                        <option value="Doctorate Degree">Doctorate Degree</option>
                        <option value="Post-Graduate">Post-Graduate</option>
                        <option value="Professional Degree">Professional Degree</option>
                        <option value="Certificate">Doctorate Certificate</option>
                        <option value="Other">Other Qualification</option>
                        <option value="No Qualification">No Qualification</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="inputIC" class="form-label">IC</label>
                    <div class="col-sm-10">
                        <input class="form-control" type="file" id="inputIC" name="inputIC"> <!-- Ensure name attribute is set -->
                    </div>
                </div>
                <div class="text-center">
                <button type="reset" class="btn btn-secondary">Reset</button>
                <button type="submit" class="btn btn-primary" id="employee_documentation-button">Submit</button>
                <button type="button" class="btn btn-secondary" id="next5-button">Next</button>
                </div>
            </form><!-- End Employee Documentation Form -->
        </div>
    </div>
</div>

<div class="card">
        <div class="card-body">
            <h5 class="card-title">Latest Employee Documentation</h5>

            <!-- Default Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Education Level</th>
                        <th scope="col">IC</th>
                    </tr>
                </thead>
                <tbody id="latest-employee-document">
                  <?php
                    // Include your database connection
                    require('database.php');

                    // Prepare the SQL query to select data from the employeelogin table
                    $query = "SELECT document_id, education_level, ic_picture FROM employee_document ORDER BY document_id DESC LIMIT 1";

                    // Execute the query
                    $result = mysqli_query($con, $query);

                    // Check if there are any rows returned
                    if (mysqli_num_rows($result) > 0) {
                        // Fetch each row of data and display it in the table
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>{$row['document_id']}</td>";
                            echo "<td>{$row['education_level']}</td>";
                            echo "<td><a href='file://{$row['ic_picture']}' target='_blank'>View Picture</a></td>";                            
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
  <script src="https://www.gstatic.com/firebasejs/9.0.2/firebase-app.js"></script>
  <script src="https://www.gstatic.com/firebasejs/9.0.2/firebase-analytics.js"></script>
  <script src="assets/js/main.js"></script>
  <script>
      document.addEventListener('DOMContentLoaded', function() {
      const employeeForm = document.getElementById('leave-info');
      let formSubmitted = false;

      employeeForm.addEventListener('submit', function(event) {
          if (formSubmitted) {
              event.preventDefault();
              return;
          }

          formSubmitted = true;
      });
  });

  document.getElementById('next5-button').addEventListener('click', function() {
      window.location.href = 'add_l.php';
    });
  </script>

</body>

</html>