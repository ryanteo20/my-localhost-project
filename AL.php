<?php
require('database.php');
require('session.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for leave application
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['leave_type'])) {
    // Get form data
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $leave_length = $_POST['leave_length'];
    $reason = $_POST['reason'];
    $user_id = $_SESSION['ID'];
    
    // Validate dates
    if (strtotime($start_date) > strtotime($end_date)) {
        $_SESSION['error_message'] = 'Start date cannot be after end date.';
    } else {
        // Insert leave application (without file_path for now to avoid the column error)
        $query = "INSERT INTO leave_apply (fk_leaveapply_id, leave_type, leave_datestart, leave_dateend, leave_length, leave_reason, apply_date, leave_review) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Pending for review')";
        
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            $_SESSION['error_message'] = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("isssds", $user_id, $leave_type, $start_date, $end_date, $leave_length, $reason);
            
            if ($stmt->execute()) {
                $leave_id = $conn->insert_id;
                
                // Create notification for employers (if notification service exists)
                if (file_exists('includes/notification_service.php')) {
                    require_once('includes/notification_service.php');
                    $notification_service = new NotificationService($conn);
                    
                    // Send notification to all employers
                    $notification_service->notifyLeaveApplication($_SESSION['ID'], $leave_type, $leave_id);
                }
                
                // Set success message and redirect to prevent form resubmission
                $_SESSION['success_message'] = 'Leave application submitted successfully! Employers have been notified.';
                $_SESSION['show_notification'] = true; // Add this flag
                header("Location: AL.php");
                exit();
            } else {
                $_SESSION['error_message'] = 'Error submitting leave application: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Check for messages to display
$success_message = '';
$error_message = '';
$show_success_notification = false;

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['show_notification'])) {
    $show_success_notification = true;
    unset($_SESSION['show_notification']);
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
  <link
    href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
    rel="stylesheet">

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
  
  <!-- Add notification styles -->
  <style>
    .success-notification {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #28a745;
      color: white;
      padding: 15px 20px;
      border-radius: 5px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
      z-index: 9999;
      display: none;
      max-width: 300px;
    }
    
    .error-notification {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #dc3545;
      color: white;
      padding: 15px 20px;
      border-radius: 5px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
      z-index: 9999;
      display: none;
      max-width: 300px;
    }
  </style>
  
  <?php include 'includes/chatbot-includes.php'; ?>
</head>

<body>

<!-- Success Notification -->
<?php if ($success_message): ?>
<div id="successNotification" class="success-notification">
    <i class="bi bi-check-circle me-2"></i>
    <?php echo htmlspecialchars($success_message); ?>
</div>
<?php endif; ?>

<!-- Error Notification -->
<?php if ($error_message): ?>
<div id="errorNotification" class="error-notification">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

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

<!-- ... rest of your existing HTML content ... -->

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Apply Leave</h1>
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
                        exit("User session ID is not set");
                      }

                      // Get the user session ID
                      $userSessionID = $_SESSION['ID'];

                      $query = "SELECT l.annual_leave, l.annual_leavetaken, 
                                    SUM(CASE WHEN a.leave_type = 'annual' AND a.leave_review = 'Approved' THEN a.leave_length ELSE 0 END) AS total_approved_leave
                                    FROM leave_info l
                                    LEFT JOIN leave_apply a ON l.leaveinfo_id = a.fk_leaveapply_id
                                    WHERE l.leaveinfo_id = ?";

                      // Use prepared statement to prevent SQL injection
                      $stmt = mysqli_prepare($conn, $query);

                      if (!$stmt) {
                        echo "<h6>Error: " . mysqli_error($conn) . "</h6>";
                      } else {
                        // Bind the session ID parameter
                        mysqli_stmt_bind_param($stmt, "s", $userSessionID);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_bind_result($stmt, $annualLeave, $annualLeaveTaken, $totalApprovedLeave);
                        mysqli_stmt_fetch($stmt);
                        mysqli_stmt_close($stmt);

                        // Calculate the remaining annual leave
                        $remainingAnnualLeave = $annualLeave - $totalApprovedLeave;
                        echo "<h6>$remainingAnnualLeave</h6>";
                      }
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
                      $userSessionID = $_SESSION['ID'];

                      $query = "SELECT l.sick_leave, l.sick_leavetaken, 
                                    SUM(CASE WHEN a.leave_type = 'sick' AND a.leave_review = 'Approved' THEN a.leave_length ELSE 0 END) AS total_approved_leave
                                    FROM leave_info l
                                    LEFT JOIN leave_apply a ON l.leaveinfo_id = a.fk_leaveapply_id
                                    WHERE l.leaveinfo_id = ?";

                      $stmt = mysqli_prepare($conn, $query);

                      if (!$stmt) {
                        echo "<h6>Error: " . mysqli_error($conn) . "</h6>";
                      } else {
                        mysqli_stmt_bind_param($stmt, "s", $userSessionID);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_bind_result($stmt, $sickLeave, $sickLeaveTaken, $totalApprovedLeave);
                        mysqli_stmt_fetch($stmt);
                        mysqli_stmt_close($stmt);

                        $remainingSickLeave = $sickLeave - $totalApprovedLeave;
                        echo "<h6>$remainingSickLeave</h6>";
                      }
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
                      $userSessionID = $_SESSION['ID'];

                      $query = "SELECT l.hospitalization_leave, l.hospitalization_leavetaken, 
                                    SUM(CASE WHEN a.leave_type = 'hospitalization' AND a.leave_review = 'Approved' THEN a.leave_length ELSE 0 END) AS total_approved_leave
                                    FROM leave_info l
                                    LEFT JOIN leave_apply a ON l.leaveinfo_id = a.fk_leaveapply_id
                                    WHERE l.leaveinfo_id = ?";

                      $stmt = mysqli_prepare($conn, $query);

                      if (!$stmt) {
                        echo "<h6>Error: " . mysqli_error($conn) . "</h6>";
                      } else {
                        mysqli_stmt_bind_param($stmt, "s", $userSessionID);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_bind_result($stmt, $hospitalizationLeave, $hospitalizationLeaveTaken, $totalApprovedLeave);
                        mysqli_stmt_fetch($stmt);
                        mysqli_stmt_close($stmt);

                        // Handle null values and calculate remaining leave
                        $hospitalizationLeave = $hospitalizationLeave ?? 60; // Default to 60 if null
                        $totalApprovedLeave = $totalApprovedLeave ?? 0; // Default to 0 if null
                        
                        $remainingHospitalizationLeave = $hospitalizationLeave - $totalApprovedLeave;
                        
                        // Debug information - remove this after fixing
                        echo "<!-- Debug: hospitalization_leave = $hospitalizationLeave, approved = $totalApprovedLeave -->";
                        
                        echo "<h6>$remainingHospitalizationLeave</h6>";
                      }
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

    <div class="col-lg-12">
      <div class="card">
        <div class="card-body">
          <form id="leaveForm" method="post" enctype="multipart/form-data">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="card-title">View Leave Application History</h5>
              <!-- Large Modal Button -->
              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#largeModal">
                New Leave
              </button>
            </div>
            <div class="modal fade" id="largeModal" tabindex="-1" data-bs-backdrop="false">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">New Leave</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="col-12">
                      <label for="inputLeave" class="col-sm-3 col-form-label">Select Leave Type</label>
                      <div class="col-sm-12">
                        <select class="form-select" name="leave_type" id="inputLeave"
                          aria-label="Default select example" required>
                          <option value="annual" selected>Annual</option>
                          <option value="sick">Sick</option>
                          <option value="hospitalization">Hospitalization</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  <div class="modal-body">
                    <div class="row">
                      <!-- Start Date Input -->
                      <div class="col-sm-6">
                        <label for="inputStartDate" class="col-sm-3 col-form-label">Start Date</label>
                        <div class="col-sm-12">
                          <input type="date" class="form-control" name="start_date" id="inputStartDate" required>
                        </div>
                      </div>

                      <!-- End Date Input -->
                      <div class="col-sm-6">
                        <label for="inputEndDate" class="col-sm-3 col-form-label">End Date</label>
                        <div class="col-sm-12">
                          <input type="date" class="form-control" name="end_date" id="inputEndDate" required>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="modal-body">
                    <fieldset class="mb-3">
                      <legend class="col-form-label pt-0">Leave Length</legend>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="leave_length" id="gridRadios1" value="1"
                          checked>
                        <label class="form-check-label" for="gridRadios1">Full Day</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="leave_length" id="gridRadios2" value="0.5">
                        <label class="form-check-label" for="gridRadios2">AM (9am-1pm)</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="leave_length" id="gridRadios3" value="0.5">
                        <label class="form-check-label" for="gridRadios3">PM (2pm-6pm)</label>
                      </div>
                    </fieldset>
                    <div class="col-sm-12">
                      <label for="inputPassword" class="col-sm-12 col-form-label">Reason</label>
                      <div class="col-sm-12">
                        <textarea class="form-control" id="inputPassword" name="reason"
                          style="height: 100px" required></textarea>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                  </div>
                </div>
              </div>
            </div><!-- End Large Modal-->
          </form>

          <!-- Table with stripped rows -->
          <table class="table datatable table-striped">
            <thead>
              <tr>
                <th scope="col">Employee</th>
                <th scope="col">Leave Type</th>
                <th scope="col">From</th>
                <th scope="col">To</th>
                <th scope="col">Days</th>
                <th scope="col">Reason</th>
                <th scope="col">Applied</th>
                <th scope="col">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Get user session ID for the table
              $userSessionID = $_SESSION['ID'];

              // Query to join the employeelogin and leave_apply tables and filter by user session ID
              $query = "SELECT el.username, la.leave_type, la.leave_datestart, la.leave_dateend, la.leave_reason, la.apply_date, la.leave_length, la.leave_review
                      FROM employeelogin el
                      INNER JOIN leave_apply la ON el.ID = la.fk_leaveapply_id
                      WHERE el.ID = ?";

              // Prepare the statement
              $stmt = mysqli_prepare($conn, $query);

              if ($stmt) {
                // Bind the user session ID parameter
                mysqli_stmt_bind_param($stmt, "s", $userSessionID);
                mysqli_stmt_execute($stmt);
                $data = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($data) > 0) {
                  while ($row = mysqli_fetch_assoc($data)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['leave_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['leave_datestart']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['leave_dateend']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['leave_length']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['leave_reason']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['apply_date']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['leave_review']) . "</td>";
                    echo "</tr>";
                  }
                } else {
                  echo "<tr><td colspan='8'>No records found.</td></tr>";
                }
                mysqli_stmt_close($stmt);
              } else {
                echo "<tr><td colspan='8'>Error loading leave history.</td></tr>";
              }
              ?>
            </tbody>
          </table>
          <!-- End Table with stripped rows -->
        </div>
      </div>
    </div>
  </main>

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>SMEasyHR</span></strong>. All Rights Reserved
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
      class="bi bi-arrow-up-short"></i></a>

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
  
  <!-- Add notification system JavaScript -->
  <script>
    // Notification Manager
    class NotificationManager {
        constructor() {
            this.updateInterval = 30000; // 30 seconds
            this.init();
        }
        
        init() {
            this.loadNotifications();
            this.startPeriodicUpdate();
        }
        
        async loadNotifications() {
            try {
                const response = await fetch('api/notifications.php?action=get_unread');
                const data = await response.json();
                
                if (data.success) {
                    this.updateUI(data.notifications, data.count);
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        }
        
        updateUI(notifications, count) {
            const countElement = document.getElementById('notificationCount');
            const headerCountElement = document.getElementById('notificationHeaderCount');
            const listElement = document.getElementById('notificationList');
            
            if (countElement && headerCountElement) {
                if (count > 0) {
                    countElement.textContent = count > 99 ? '99+' : count;
                    countElement.style.display = 'block';
                    headerCountElement.textContent = count;
                } else {
                    countElement.style.display = 'none';
                    headerCountElement.textContent = '0';
                }
            }
            
            if (listElement) {
                listElement.innerHTML = notifications.length === 0 ? 
                    '<li class="text-center py-3 text-muted">No new notifications</li>' :
                    notifications.map(n => this.createNotificationHTML(n)).join('');
            }
        }
        
        createNotificationHTML(notification) {
            const timeAgo = this.getTimeAgo(notification.created_at);
            return `
                <li class="notification-item">
                    <div class="d-flex p-3 border-bottom" onclick="markNotificationAsRead(${notification.id})">
                        <div class="me-3">
                            <i class="bi bi-bell text-primary fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${notification.title}</h6>
                            <p class="mb-1 text-muted small">${notification.message}</p>
                            <small class="text-muted">${timeAgo}</small>
                        </div>
                    </div>
                </li>
            `;
        }
        
        getTimeAgo(dateString) {
            const now = new Date();
            const notificationDate = new Date(dateString);
            const diffInSeconds = Math.floor((now - notificationDate) / 1000);
            
            if (diffInSeconds < 60) return 'Just now';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' min ago';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hr ago';
            return Math.floor(diffInSeconds / 86400) + ' day ago';
        }
        
        startPeriodicUpdate() {
            setInterval(() => this.loadNotifications(), this.updateInterval);
        }
    }

    async function markNotificationAsRead(notificationId) {
        const formData = new FormData();
        formData.append('notification_id', notificationId);
        
        const response = await fetch('api/notifications.php?action=mark_read', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            window.notificationManager.loadNotifications();
        }
    }

    async function markAllAsRead() {
        const response = await fetch('api/notifications.php?action=mark_all_read', {
            method: 'POST'
        });
        
        if (response.ok) {
            window.notificationManager.loadNotifications();
        }
    }

    // Show notifications function
    function showNotification(elementId) {
        const notification = document.getElementById(elementId);
        if (notification) {
            notification.style.display = 'block';
            notification.style.opacity = 0;
            
            let opacity = 0;
            const fadeIn = setInterval(() => {
                if (opacity < 1) {
                    opacity += 0.05;
                    notification.style.opacity = opacity;
                } else {
                    clearInterval(fadeIn);
                    
                    // Auto hide after 5 seconds
                    setTimeout(() => {
                        const fadeOut = setInterval(() => {
                            if (opacity > 0) {
                                opacity -= 0.05;
                                notification.style.opacity = opacity;
                            } else {
                                clearInterval(fadeOut);
                                notification.style.display = 'none';
                            }
                        }, 50);
                    }, 5000);
                }
            }, 50);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
      // Initialize notification manager
      window.notificationManager = new NotificationManager();
      
      // Show success notification if it exists
      <?php if ($success_message): ?>
      showNotification('successNotification');
      <?php endif; ?>
      
      // Show error notification if it exists
      <?php if ($error_message): ?>
      showNotification('errorNotification');
      <?php endif; ?>
      
      // Close modal after successful form submission
      $('#leaveForm').on('submit', function() {
          setTimeout(function() {
              $('#largeModal').modal('hide');
          }, 1000);
      });
    });
  </script>

</body>

</html>