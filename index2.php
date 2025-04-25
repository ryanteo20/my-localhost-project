<?php
require('database.php');
require('session.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>SMEasyHR - Employee</title>
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
      <a href="index2.php" class="logo d-flex align-items-center">
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
        <a class="nav-link collapsed" href="index2.php">
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
            <a href="view_employee.php">
              <i class="bi bi-circle"></i><span>View All Employee</span>
            </a>
          </li>
        </ul>
      </li><!-- End Employee Management Nav -->

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
            <a href="apply_leave.php">
              <i class="bi bi-circle"></i><span>Apply Leave</span>
            </a>
          </li>
          <li>
            <a href="leave_status.php">
              <i class="bi bi-circle"></i><span>Leave Status</span>
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
      <h1>Home</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index2.php">Home</a></li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <div class="row">

        <!-- Left side columns -->
        <div class="col-lg-12">
          <div class="row">

            <!-- Sales Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card sales-card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"></a>
                </div>
                <div class="card-body">
                  <h5 class="card-title">Remaining Annual Leave</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ri-task-line"></i>
                        </div>
                          <div class="ps-3">
                          <?php
                            // Check if the user session ID is set
                            if (!isset($_SESSION['ID'])) {
                                // Redirect the user to the login page or handle the authentication process
                                // Example: header("Location: login.php");
                                exit("User session ID is not set");
                            }

                            // Include database connection
                            require('database.php');

                            // Get the user session ID
                            $userSessionID = $_SESSION['ID'];

                            $query = "SELECT l.annual_leave, l.annual_leavetaken, 
                                    SUM(CASE WHEN a.leave_type = 'annual' AND a.leave_review = 'Approve' THEN a.leave_length ELSE 0 END) AS total_approved_leave
                                    FROM leave_info l
                                    LEFT JOIN leave_apply a ON l.leaveinfo_id = a.fk_leaveapply_id
                                    WHERE l.leaveinfo_id = ?";
                                            

                            // Use prepared statement to prevent SQL injection
                            $stmt = mysqli_prepare($con, $query);

                            if (!$stmt) {
                                // Handle the error if the statement preparation fails
                                exit("Error: " . mysqli_error($con));
                            }

                            // Bind the session ID parameter
                            $success = mysqli_stmt_bind_param($stmt, "s", $userSessionID);

                            if (!$success) {
                                // Handle the error if binding parameters fails
                                exit("Error binding parameters: " . mysqli_error($con));
                            }

                            // Execute the query
                            $success = mysqli_stmt_execute($stmt);

                            if (!$success) {
                                // Handle the error if execution fails
                                exit("Error executing query: " . mysqli_error($con));
                            }

                            // Bind the result variables
                            mysqli_stmt_bind_result($stmt, $annualLeave, $annualLeaveTaken, $totalApprovedLeave);

                            // Fetch the result
                            mysqli_stmt_fetch($stmt);

                            // Close the statement
                            mysqli_stmt_close($stmt);

                            // Calculate the remaining hospitalization leave by subtracting total approved leave from total hospitalization leave taken
                            $remainingAnnualLeave = $annualLeave - $totalApprovedLeave;

                            // Display the remaining hospitalization leave
                            echo "<h6>$remainingAnnualLeave</h6>";

                            // Close the database connection
                            mysqli_close($con);
                            ?>
                          </div>
                      </div>


                  </div>
                </div>
            </div><!-- End Sales Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card sales-card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"></a>
                </div>
                <div class="card-body">
                  <h5 class="card-title">Remaining Sick Leave</h5>

                  <div class="d-flex align-items-center">
                      <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                          <i class="ri-stethoscope-line"></i>
                      </div>
                        <div class="ps-3">
                        <?php
                            // Check if the user session ID is set
                            if (!isset($_SESSION['ID'])) {
                                // Redirect the user to the login page or handle the authentication process
                                // Example: header("Location: login.php");
                                exit("User session ID is not set");
                            }

                            // Include database connection
                            require('database.php');

                            // Get the user session ID
                            $userSessionID = $_SESSION['ID'];

                            $query = "SELECT l.sick_leave, l.sick_leavetaken, 
                                    SUM(CASE WHEN a.leave_type = 'sick' AND a.leave_review = 'Approve' THEN a.leave_length ELSE 0 END) AS total_approved_leave
                                    FROM leave_info l
                                    LEFT JOIN leave_apply a ON l.leaveinfo_id = a.fk_leaveapply_id
                                    WHERE l.leaveinfo_id = ?";
                                            

                            // Use prepared statement to prevent SQL injection
                            $stmt = mysqli_prepare($con, $query);

                            if (!$stmt) {
                                // Handle the error if the statement preparation fails
                                exit("Error: " . mysqli_error($con));
                            }

                            // Bind the session ID parameter
                            $success = mysqli_stmt_bind_param($stmt, "s", $userSessionID);

                            if (!$success) {
                                // Handle the error if binding parameters fails
                                exit("Error binding parameters: " . mysqli_error($con));
                            }

                            // Execute the query
                            $success = mysqli_stmt_execute($stmt);

                            if (!$success) {
                                // Handle the error if execution fails
                                exit("Error executing query: " . mysqli_error($con));
                            }

                            // Bind the result variables
                            mysqli_stmt_bind_result($stmt, $sickLeave, $sickLeaveTaken, $totalApprovedLeave);

                            // Fetch the result
                            mysqli_stmt_fetch($stmt);

                            // Close the statement
                            mysqli_stmt_close($stmt);

                            // Calculate the remaining hospitalization leave by subtracting total approved leave from total hospitalization leave taken
                            $remainingSickLeave = $sickLeave - $totalApprovedLeave;

                            // Display the remaining hospitalization leave
                            echo "<h6>$remainingSickLeave</h6>";

                            // Close the database connection
                            mysqli_close($con);
                            ?>
                          </div>
                        </div>
                    </div>
                </div>
            </div><!-- End Sales Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card sales-card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"></a>
                </div>
                <div class="card-body">
                  <h5 class="card-title">Remaining Hospitalization Leave</h5>

                  <div class="d-flex align-items-center">
                      <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                          <i class="ri-hospital-line"></i>
                      </div>
                        <div class="ps-3">
                        <?php
                            // Check if the user session ID is set
                            if (!isset($_SESSION['ID'])) {
                                // Redirect the user to the login page or handle the authentication process
                                // Example: header("Location: login.php");
                                exit("User session ID is not set");
                            }

                            // Include database connection
                            require('database.php');

                            // Get the user session ID
                            $userSessionID = $_SESSION['ID'];

                            $query = "SELECT l.hospitalization_leave, l.hospitalization_leavetaken, 
                                    SUM(CASE WHEN a.leave_type = 'hospitalization' AND a.leave_review = 'Approve' THEN a.leave_length ELSE 0 END) AS total_approved_leave
                                    FROM leave_info l
                                    LEFT JOIN leave_apply a ON l.leaveinfo_id = a.fk_leaveapply_id
                                    WHERE l.leaveinfo_id = ?";
                                            

                            // Use prepared statement to prevent SQL injection
                            $stmt = mysqli_prepare($con, $query);

                            if (!$stmt) {
                                // Handle the error if the statement preparation fails
                                exit("Error: " . mysqli_error($con));
                            }

                            // Bind the session ID parameter
                            $success = mysqli_stmt_bind_param($stmt, "s", $userSessionID);

                            if (!$success) {
                                // Handle the error if binding parameters fails
                                exit("Error binding parameters: " . mysqli_error($con));
                            }

                            // Execute the query
                            $success = mysqli_stmt_execute($stmt);

                            if (!$success) {
                                // Handle the error if execution fails
                                exit("Error executing query: " . mysqli_error($con));
                            }

                            // Bind the result variables
                            mysqli_stmt_bind_result($stmt, $hospitalizationLeave, $hospitalizationLeaveTaken, $totalApprovedLeave);

                            // Fetch the result
                            mysqli_stmt_fetch($stmt);

                            // Close the statement
                            mysqli_stmt_close($stmt);

                            // Calculate the remaining hospitalization leave by subtracting total approved leave from total hospitalization leave taken
                            $remainingHospitalizationLeave = $hospitalizationLeave - $totalApprovedLeave;

                            // Display the remaining hospitalization leave
                            echo "<h6>$remainingHospitalizationLeave</h6>";

                            // Close the database connection
                            mysqli_close($con);
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- End Sales Card -->
          </div>
        </div>
      </div>
    </section>

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