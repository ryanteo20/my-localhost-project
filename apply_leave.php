<?php
require('database.php');
require('session.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add form processing logic here
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['leave_type'])) {
    // Get form data
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $leave_length = $_POST['leave_length'];
    $reason = $_POST['reason'];
    $user_id = $_SESSION['ID'];
    
    // Handle file upload
    $file_path = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $upload_dir = 'uploads/leave_documents/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . $_FILES['file']['name'];
        $file_path = $upload_dir . $file_name;
        move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
    }
    
    // Insert leave application
    $query = "INSERT INTO leave_apply (fk_leaveapply_id, leave_type, leave_datestart, leave_dateend, leave_length, leave_reason, apply_date, leave_review, file_path) 
              VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Pending for review', ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssdss", $user_id, $leave_type, $start_date, $end_date, $leave_length, $reason, $file_path);
    
    if ($stmt->execute()) {
        $leave_id = $conn->insert_id;
        
        // Create notification for employers
        require_once('includes/notification_service.php');
        $notification_service = new NotificationService($conn);
        
        // Get employee name
        $emp_query = "SELECT pi.full_name FROM personal_information pi 
                      JOIN employeelogin el ON pi.personal_id = el.ID 
                      WHERE el.ID = ?";
        $emp_stmt = $conn->prepare($emp_query);
        $emp_stmt->bind_param("i", $_SESSION['ID']);
        $emp_stmt->execute();
        $emp_result = $emp_stmt->get_result()->fetch_assoc();
        $employee_name = $emp_result['full_name'] ?? $_SESSION['username'];
        
        // Send notification to all employers
        $notification_service->notifyLeaveApplication($_SESSION['ID'], $leave_type, $leave_id);
        
        $_SESSION['success_message'] = 'Leave application submitted successfully! Employers have been notified.';
        header("Location: apply_leave.php");
        exit();
    }
}
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
  
  <!-- Add notification styles -->
  <style>
<style>
    .notification-container {
      position: fixed;
      top: 80px;
      right: 20px;
      background: #28a745;
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 9999;
      display: none;
      max-width: 350px;
      min-width: 280px;
      font-size: 14px;
      font-weight: 500;
      border-left: 4px solid #1e7e34;
    }
    
    .notification-container i {
      margin-right: 8px;
      font-size: 16px;
    }
    
    .notification-container.show {
      display: block;
      animation: slideInRight 0.3s ease-out;
    }
    
    @keyframes slideInRight {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    @keyframes slideOutRight {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(100%);
        opacity: 0;
      }
    }
  </style>
  
  <?php include 'includes/chatbot-includes.php'; ?>
</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
      <a href="index2.php" class="logo d-flex align-items-center">
        <img src="assets/img/logo.png" alt="">
        <span class="d-none d-lg-block">SMEasyHR - Employee</span>
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
        <a class="nav-link collapsed" data-bs-target="#attendance-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-gem"></i><span>Attendance</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="attendance-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
        <li>
            <a href="attendance_employee.php">
              <i class="bi bi-circle"></i><span>Clock in & out</span>
            </a>
          </li>
          <li>
            <a href="v_attendance.php">
              <i class="bi bi-circle"></i><span>View Attendance</span>
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
            <a href="EC_payslip.php">
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
            <a href="ER_claim.php">
              <i class="bi bi-circle"></i><span>Request Claim</span>
            </a>
          </li>
          <li>
            <a href="EVR_claim.php">
              <i class="bi bi-circle"></i><span>View All Claim</span>
            </a>
          </li>
        </ul>
      </li><!-- End Claim Management Nav -->
    </ul>

  </aside>
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
                            $stmt = mysqli_prepare($conn, $query);

                            if (!$stmt) {
                                // Handle the error if the statement preparation fails
                                exit("Error: " . mysqli_error($conn));
                            }

                            // Bind the session ID parameter
                            $success = mysqli_stmt_bind_param($stmt, "s", $userSessionID);

                            if (!$success) {
                                // Handle the error if binding parameters fails
                                exit("Error binding parameters: " . mysqli_error($conn));
                            }

                            // Execute the query
                            $success = mysqli_stmt_execute($stmt);

                            if (!$success) {
                                // Handle the error if execution fails
                                exit("Error executing query: " . mysqli_error($conn));
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
                            mysqli_close($conn);
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
                            $stmt = mysqli_prepare($conn, $query);

                            if (!$stmt) {
                                // Handle the error if the statement preparation fails
                                exit("Error: " . mysqli_error($conn));
                            }

                            // Bind the session ID parameter
                            $success = mysqli_stmt_bind_param($stmt, "s", $userSessionID);

                            if (!$success) {
                                // Handle the error if binding parameters fails
                                exit("Error binding parameters: " . mysqli_error($conn));
                            }

                            // Execute the query
                            $success = mysqli_stmt_execute($stmt);

                            if (!$success) {
                                // Handle the error if execution fails
                                exit("Error executing query: " . mysqli_error($conn));
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
                            mysqli_close($conn);
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
                            $stmt = mysqli_prepare($conn, $query);

                            if (!$stmt) {
                                // Handle the error if the statement preparation fails
                                exit("Error: " . mysqli_error($conn));
                            }

                            // Bind the session ID parameter
                            $success = mysqli_stmt_bind_param($stmt, "s", $userSessionID);

                            if (!$success) {
                                // Handle the error if binding parameters fails
                                exit("Error binding parameters: " . mysqli_error($conn));
                            }

                            // Execute the query
                            $success = mysqli_stmt_execute($stmt);

                            if (!$success) {
                                // Handle the error if execution fails
                                exit("Error executing query: " . mysqli_error($conn));
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
                            mysqli_close($conn);
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

    
    <div class="col-lg-14">
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
          <div class="modal fade" id="largeModal" tabindex="-1"  data-bs-backdrop="false">
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
                      <select class="form-select" name="leave_type" id="inputLeave" aria-label="Default select example">
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
                      <label for="inputStartDate" class="col-sm-4 col-form-label">Start Date</label>
                      <div class="col-sm-12">
                        <input type="date" class="form-control" name="start_date" id="inputStartDate">
                      </div>
                    </div>

                    <!-- End Date Input -->
                    <div class="col-sm-6">
                      <label for="inputEndDate" class="col-sm-3 col-form-label">End Date</label>
                      <div class="col-sm-12">
                        <input type="date" class="form-control" name="end_date" id="inputEndDate">
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal-body">
                  <fieldset class="mb-3">
                    <legend class="col-form-label pt-0">Leave Length</legend>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="leave_length" id="gridRadios1" value="1" checked>
                      <label class="form-check-label" id="gridRadios1" for="gridRadios1">Full Day</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="leave_length" id="gridRadios2" value="0.5">
                      <label class="form-check-label" id="gridRadios2" for="gridRadios2">AM (9am-1pm)</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="leave_length" id="gridRadios3" value="0.5">
                      <label class="form-check-label" id="gridRadios3" for="gridRadios3">PM (2pm-6pm)</label>
                    </div>
                  </fieldset>
                  <div class="col-sm-12">
                    <label for="inputPassword" class="col-sm-12 col-form-label">Reason</label>
                    <div class="col-sm-12">
                      <textarea class="form-control" id="inputPassword" name="reason" style="height: 100px"></textarea>
                    </div>
                  </div>
                </div>
                <div class="modal-body">
                  <div class="col-sm-12">
                      <label for="inputNumber" class="col-sm-2 col-form-label">File Upload</label>
                      <div class="col-sm-12">
                          <input type="file" name="file" id="formFile" class="form-control">
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
              <th scope="col">Reason</th>
              <th scope="col">Applied</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Connect to the database
            require('database.php');

            $userSessionID = $_SESSION['ID'];

            // Query to join the employeelogin and leave_apply tables and filter by user session ID
            $query = "SELECT el.username, la.leave_type, la.leave_datestart, la.leave_dateend, la.leave_reason, la.apply_date
                      FROM employeelogin el
                      INNER JOIN leave_apply la ON el.ID = la.fk_leaveapply_id
                      WHERE el.ID = ?";

            // Prepare the statement
            $stmt = mysqli_prepare($conn, $query);

            // Bind the user session ID parameter
            mysqli_stmt_bind_param($stmt, "s", $userSessionID);

            // Execute the prepared statement
            $result = mysqli_stmt_execute($stmt);

            // Check if the query was successful
            if ($result) {
              $data = mysqli_stmt_get_result($stmt);
              if (mysqli_num_rows($data) > 0) {
                $row_count = 1;
                while ($row = mysqli_fetch_assoc($data)) {
                  echo "<tr>";
                  echo "<td>" . $row['username'] . "</td>";
                  echo "<td>" . $row['leave_type'] . "</td>";
                  echo "<td>" . $row['leave_datestart'] . "</td>";
                  echo "<td>" . $row['leave_dateend'] . "</td>";
                  echo "<td>" . $row['leave_reason'] . "</td>";
                  echo "<td>" . $row['apply_date'] . "</td>";
                  echo "</tr>";
                  $row_count++;
                }
              } else {
                echo "<tr><td colspan='6'>No records found.</td></tr>";
              }
            } else {
              echo "Error executing query: " . mysqli_stmt_error($stmt);
            }

            // Close the prepared statement
            mysqli_stmt_close($stmt);

            // Close the database connection
            mysqli_close($conn);
            ?>
          </tbody>
        </table>
      <!-- End Table with stripped rows -->
        </div>
      </div>
    </div>
</main>


      <!-- Notification Container - Fixed -->
      <div id="notification" class="notification-container">
          <i class="bi bi-check-circle-fill"></i>
          Successfully applied for leave!
      </div>

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
        // Improved notification function
        function showNotification() {
            const notification = document.getElementById('notification');
            
            // Show with slide-in animation
            notification.classList.add('show');
            notification.style.display = 'block';
            
            // Auto-hide after 4 seconds with slide-out animation
            setTimeout(function() {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                
                // Remove from DOM after animation completes
                setTimeout(function() {
                    notification.style.display = 'none';
                    notification.classList.remove('show');
                    notification.style.animation = '';
                }, 300);
            }, 4000);
        }

        // Check if there's a message to display
        <?php if (isset($_SESSION['success_message'])): ?>
            showNotification();
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        // Close modal after successful form submission
        const form = document.getElementById('leaveForm');
        if (form) {
            form.addEventListener('submit', function() {
                // Close modal after a brief delay
                setTimeout(function() {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('largeModal'));
                    if (modal) {
                        modal.hide();
                    }
                }, 500);
            });
        }
    });
</script>

</body>
</html>