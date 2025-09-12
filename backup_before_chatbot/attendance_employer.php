<?php
require('database.php');
require('session.php');
require('send_notification.php');

date_default_timezone_set('Asia/Kuala_Lumpur');
$date = date('Y-m-d');
$time_now = date('Y-m-d H:i:s');
$current_hour = (int)date('H');

$employee_id = $_SESSION['ID'] ?? null;

$status = 'present';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
$location_coordinates = null;

// Function to insert notification into the database
function insertNotification($employee_id, $employer_id, $message) {
    global $conn;
    $query = "INSERT INTO notifications (employee_id, employer_id, message, status, created_at) VALUES (?, ?, ?, 'unread', NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $employee_id, $employer_id, $message);
    return $stmt->execute();
}

// Function to get employee name
function getEmployeeName($employee_id) {
    global $conn;
    $query = "SELECT username FROM employeelogin WHERE ID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['username'] : "Employee #$employee_id";
}

// Function to get employer ID and email for an employee
// Function to get employer ID and email for an employee
function getEmployerInfo($employee_id) {
    global $conn;
    
    // SQL query to get employer info
    $query = "SELECT el.employer_id, e.email as employer_email 
              FROM employeelogin el 
              LEFT JOIN employeelogin e ON el.employer_id = e.ID 
              WHERE el.ID = ?";
    
    // Prepare the query
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        // If the prepare statement fails, output the specific MySQL error
        echo "✗ Error preparing query: " . $conn->error . "\n";
        return null;
    }

    // Bind parameters
    $stmt->bind_param("i", $employee_id);
    
    // Execute the query
    if (!$stmt->execute()) {
        echo "✗ Error executing query: " . $stmt->error . "\n";
        return null;
    }

    // Get the result
    $result = $stmt->get_result();
    
    // Check if any result was returned
    if ($result->num_rows > 0) {
        return $result->fetch_assoc(); // Return the first row
    } else {
        echo "✗ No employer information found for employee ID: $employee_id\n";
        return null;
    }
}


// Function to check if notification already exists today
function notificationExistsToday($employee_id, $employer_id, $message_pattern) {
    global $conn, $date;
    $query = "SELECT ID FROM notifications 
              WHERE employee_id = ? AND employer_id = ? 
              AND DATE(created_at) = ? 
              AND message LIKE ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiss", $employee_id, $employer_id, $date, $message_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to create absent records and send notifications
function processAbsentEmployees() {
    global $conn, $date;
    
    // SQL query to get the absent employees
    $query = "SELECT a.employee_id, el.username, el.employer_id, pi.email as employer_email
              FROM attendance a
              JOIN employeelogin el ON a.employee_id = el.ID
              LEFT JOIN employeelogin emp ON el.employer_id = emp.ID
              LEFT JOIN personal_information pi ON emp.ID = pi.personal_id
              WHERE a.date = ? AND a.status = 'absent'
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        // Output the specific error message from MySQL
        echo "✗ Error preparing query: " . $conn->error . "\n";
        return;
    }

    $stmt->bind_param("s", $date);  // Bind date parameter
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Proceed with creating the notification
        $message = "TEST NOTIFICATION: {$row['username']} is marked as absent for testing purposes";
        
        // Insert system notification
        $notifyQuery = "INSERT INTO notifications (employee_id, employer_id, message, status, created_at) 
                       VALUES (?, ?, ?, 'unread', NOW())";
        $notifyStmt = $conn->prepare($notifyQuery);
        
        if (!$notifyStmt) {
            echo "✗ Error preparing notification query: " . $conn->error . "\n";
            return;
        }
        
        $notifyStmt->bind_param("iis", $row['employee_id'], $row['employer_id'], $message);
        
        if ($notifyStmt->execute()) {
            echo "✓ Notification created successfully\n";
            
            // Send email notification if employer email exists
            if ($row['employer_email']) {
                sendNotification($row['employer_email'], $row['username'], $message);
                echo "✓ Email notification sent to: {$row['employer_email']}\n";
            } else {
                echo "! No employer email found\n";
            }
        } else {
            echo "✗ Error creating notification: " . $notifyStmt->error . "\n";
        }

        $notifyStmt->close();
    }
    $stmt->close();
}


// Function to send late clock-in notifications
function checkLateClockIn($employee_id, $clock_in_time) {
    global $date;
    
    $clock_in_cutoff = strtotime($date . ' 09:00:00');
    $actual_clock_in = strtotime($clock_in_time);
    
    if ($actual_clock_in > $clock_in_cutoff) {
        $employee_name = getEmployeeName($employee_id);
        $employer_info = getEmployerInfo($employee_id);
        
        if ($employer_info && $employer_info['employer_id'] && $employer_info['employer_email']) {
            $late_minutes = round(($actual_clock_in - $clock_in_cutoff) / 60);
            $notification_message = "$employee_name clocked in late at " . 
                                  date('g:i A', $actual_clock_in) . 
                                  " (${late_minutes} minutes after 9:00 AM)";
            
            $message_pattern = "%clocked in late%";
            if (!notificationExistsToday($employee_id, $employer_info['employer_id'], $message_pattern)) {
                // Insert system notification
                insertNotification($employee_id, $employer_info['employer_id'], $notification_message);
                
                // Send email notification
                sendNotification($employer_info['employer_email'], $employee_name, $notification_message);
            }
        }
    }
}


// Function to send early clock-out notifications
function checkEarlyClockOut($employee_id, $clock_out_time) {
    global $date;
    
    $clock_out_cutoff = strtotime($date . ' 18:00:00');
    $actual_clock_out = strtotime($clock_out_time);
    
    if ($actual_clock_out < $clock_out_cutoff) {
        $employee_name = getEmployeeName($employee_id);
        $employer_info = getEmployerInfo($employee_id);
        
        if ($employer_info && $employer_info['employer_id'] && $employer_info['employer_email']) {
            $early_minutes = round(($clock_out_cutoff - $actual_clock_out) / 60);
            $notification_message = "$employee_name clocked out early at " . 
                                  date('g:i A', $actual_clock_out) . 
                                  " (${early_minutes} minutes before 6:00 PM)";
            
            $message_pattern = "%clocked out early%";
            if (!notificationExistsToday($employee_id, $employer_info['employer_id'], $message_pattern)) {
                // Insert system notification
                insertNotification($employee_id, $employer_info['employer_id'], $notification_message);
                
                // Send email notification
                sendNotification($employer_info['employer_email'], $employee_name, $notification_message);
            }
        }
    }
}


// Run the absent employee check every time the page loads (after 9:15 AM)
processAbsentEmployees();

// Handle Clock In
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_in'])) {
    // First, check if there's an absent record for today and update it
    $checkAbsentQuery = "SELECT * FROM attendance WHERE employee_id = ? AND date = ? AND status = 'absent'";
    $checkAbsentStmt = $conn->prepare($checkAbsentQuery);
    $checkAbsentStmt->bind_param("is", $employee_id, $date);
    $checkAbsentStmt->execute();
    $absentResult = $checkAbsentStmt->get_result();
    
    if ($absentResult->num_rows > 0) {
        // Update the absent record to present with clock in time
        $query = "UPDATE attendance SET clock_in = ?, status = ?, ip_address = ?, location_coordinates = ? 
                  WHERE employee_id = ? AND date = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssi", $time_now, $status, $ip_address, $location_coordinates, $employee_id, $date);
    } else {
        // Insert new record
        $query = "INSERT INTO attendance (employee_id, date, clock_in, status, ip_address, location_coordinates)
                  VALUES (?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE clock_in = VALUES(clock_in), status = VALUES(status), 
                  ip_address = VALUES(ip_address), location_coordinates = VALUES(location_coordinates)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssss", $employee_id, $date, $time_now, $status, $ip_address, $location_coordinates);
    }
    
    if ($stmt->execute()) {
        // Check for late clock-in and send notification
        checkLateClockIn($employee_id, $time_now);
    } else {
        die("Error clocking in: " . $stmt->error);
    }
}

// Handle Clock Out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_out'])) {
    $checkQuery = "SELECT clock_out FROM attendance WHERE employee_id = ? AND date = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("is", $employee_id, $date);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        if (empty($row['clock_out'])) {
            // Update clock-out time
            $updateQuery = "UPDATE attendance SET clock_out = ?, ip_address = ? WHERE employee_id = ? AND date = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ssis", $time_now, $ip_address, $employee_id, $date);
            
            if ($updateStmt->execute()) {
                // Check for early clock-out and send notification
                checkEarlyClockOut($employee_id, $time_now);
            }
        }
    }
}

// ✅ Fetch today's attendance
$query = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $employee_id, $date);
$stmt->execute();
$result = $stmt->get_result();
$attendance = $result->fetch_assoc();

// ✅ Define flags to control UI logic
$hasClockedIn = false;
$hasClockedOut = false;
$isAbsent = false;

if ($attendance) {
    $hasClockedIn = !empty($attendance['clock_in']);
    $hasClockedOut = !empty($attendance['clock_out']);
    $isAbsent = ($attendance['status'] === 'absent' && empty($attendance['clock_in']));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>SMEasyHR - Attendance</title>
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
  
  <style>
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
      border-radius: 5px;
      padding: 15px;
      margin-bottom: 10px;
      z-index: 9999;
      max-width: 400px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      animation: slideIn 0.3s ease-out;
    }
    
    .notification.success {
      background-color: #d4edda;
      color: #155724;
      border-color: #c3e6cb;
    }
    
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    
    .absent-status {
      background-color: #f8d7da;
      color: #721c24;
      border: 2px solid #f5c6cb;
    }
  </style>
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
    </div>

    <div class="search-bar">
      <form class="search-form d-flex align-items-center" method="POST" action="#">
        <input type="text" name="query" placeholder="Search" title="Enter search keyword">
        <button type="submit" title="Search"><i class="bi bi-search"></i></button>
      </form>
    </div>

    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">
        <li class="nav-item d-block d-lg-none">
          <a class="nav-link nav-icon search-bar-toggle " href="#">
            <i class="bi bi-search"></i>
          </a>
        </li>

        <!-- Notification Icon -->
        <li class="nav-item dropdown">
          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" id="notificationIcon">
            <i class="bi bi-bell"></i>
            <span class="badge bg-primary badge-number" id="notificationCount" style="display: none;">0</span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications" id="notificationDropdown">
            <li class="dropdown-header">
              You have <span id="notificationHeaderCount">0</span> new notifications
              <a href="#" onclick="markAllAsRead()"><span class="badge rounded-pill bg-primary p-2 ms-2">view all</span></a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <div id="notificationList">
              <!-- Notifications will be loaded here -->
            </div>
          </ul>
        </li>

        <li class="nav-item dropdown pe-3">
          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
            <img src="assets/img/profile-img.jpg" alt="Profile" class="rounded-circle">
            <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $_SESSION['username']; ?></span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6><?php echo $_SESSION['username']; ?></h6>
              <span><?php echo $_SESSION['role']; ?></span>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="pages-faq.html">
                <i class="bi bi-question-circle"></i>
                <span>Need Help?</span>
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </a>
            </li>
          </ul>
        </li>

      </ul>
    </nav>

  </header>

  <!-- ======= Sidebar ======= -->
  <aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
      <li class="nav-item">
        <a class="nav-link collapsed" href="index.php">
          <i class="bi bi-grid"></i>
          <span>Home</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-menu-button-wide"></i><span>Employee Management</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="components-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li><a href="add.php"><i class="bi bi-circle"></i><span>Add Employee</span></a></li>
          <li><a href="delete.php"><i class="bi bi-circle"></i><span>Delete Employee</span></a></li>
          <li><a href="view_all.php"><i class="bi bi-circle"></i><span>View All Employee</span></a></li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="recruitment_process.php">
          <i class="bi bi-journal-text"></i><span>Recruitment Process</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#tables-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-layout-text-window-reverse"></i><span>Attendance</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="tables-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li><a href="attendance_employer.php"><i class="bi bi-circle"></i><span>Clock in & out</span></a></li>
          <li><a href="v_all_attendance.php"><i class="bi bi-circle"></i><span>View All Employee Attendance</span></a></li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#charts-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-bar-chart"></i><span>Leave Management</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="charts-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li><a href="leave_tracking.php"><i class="bi bi-circle"></i><span>Leave Tracking</span></a></li>
          <li><a href="AL.php"><i class="bi bi-circle"></i><span>Apply Leave</span></a></li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-gem"></i><span>Payroll</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="icons-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li><a href="P_payroll.php"><i class="bi bi-circle"></i><span>Process Payroll</span></a></li>
          <li><a href="C_payslip.php"><i class="bi bi-circle"></i><span>Check Payslip</span></a></li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#claim-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-currency-dollar"></i><span>Claim Management</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="claim-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li><a href="R_claim.php"><i class="bi bi-circle"></i><span>Request Claim</span></a></li>
          <li><a href="AR_claim.php"><i class="bi bi-circle"></i><span>Approve/Reject Claim</span></a></li>
        </ul>
      </li>
    </ul>
  </aside>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Attendance</h1>
    </div>

    <section class="section">
      <div class="card p-4" style="max-width: 500px; margin: auto;">
        <h4 class="mb-3">Clock In / Clock Out</h4>

        <div class="d-flex align-items-center justify-content-between border rounded p-3 <?= $isAbsent ? 'absent-status' : '' ?>">
          <div class="d-flex align-items-center">
            <span class="me-2" style="font-size: 20px; color: <?= $isAbsent ? 'red' : ($hasClockedIn ? 'green' : 'red') ?>">●</span>
            <div>
              <?php if ($isAbsent): ?>
                <p class="mb-0"><strong>Status: ABSENT</strong></p>
                <small>You were marked absent. Clock in now to change status.</small>
              <?php elseif ($hasClockedIn): ?>
                <p class="mb-0">Since <?= date('g:i A', strtotime($attendance['clock_in'])) ?></p>
                <strong 
                    id="duration" 
                    data-clockin="<?= $attendance['clock_in'] ?>"
                    <?php if ($hasClockedOut): ?>data-clockout="<?= $attendance['clock_out'] ?>"<?php endif; ?>
                  >
                    00:00:00
                  </strong>
              <?php else: ?>
                <p class="mb-0">Not yet checked in</p>
                <small>Click to start your shift</small>
              <?php endif; ?>
            </div>
          </div>

          <form method="post">
            <?php if (!$hasClockedIn || $isAbsent): ?>
              <button id="checkInButton" type="submit" name="check_in" class="btn btn-success">
                <?= $isAbsent ? 'Clock In (Change from Absent)' : 'Check In' ?>
              </button>
            <?php elseif (!$hasClockedOut): ?>
              <button id="checkOutButton" type="submit" name="check_out" class="btn btn-warning">Check Out</button>
            <?php else: ?>
              <span class="badge bg-secondary">Checked Out</span>
            <?php endif; ?>
          </form>
        </div>

        <?php if ($isAbsent): ?>
          <div class="alert alert-warning mt-3">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Important:</strong> You have been marked as absent for today. Your employer has been notified. 
            Please clock in as soon as possible to change your status.
          </div>
        <?php endif; ?>

        <?php if ($hasClockedIn && !$hasClockedOut): ?>
          <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle"></i>
            Remember to clock out before leaving (6:00 PM)
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>SMEasyHR</span></strong>. All Rights Reserved
    </div>
  </footer>

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
    document.addEventListener("DOMContentLoaded", function () {
        const durationElement = document.getElementById("duration");
        if (!durationElement) return;

        const clockInTimeStored = localStorage.getItem('clockInTime');
        const clockOutTimeStored = localStorage.getItem('clockOutTime');
        
        // If there's no clock-in time, return early (e.g., user hasn't clocked in yet)
        if (!clockInTimeStored) return;

        const clockIn = new Date(clockInTimeStored).getTime();
        const clockOut = clockOutTimeStored ? new Date(clockOutTimeStored).getTime() : null;

        function updateDuration() {
            const now = clockOut || new Date().getTime();
            const diff = now - clockIn;

            const hours = String(Math.floor(diff / (1000 * 60 * 60))).padStart(2, '0');
            const minutes = String(Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
            const seconds = String(Math.floor((diff % (1000 * 60)) / 1000)).padStart(2, '0');

            durationElement.textContent = `${hours}:${minutes}:${seconds}`;

            if (clockOut) clearInterval(timer);
        }

        // Initial update
        updateDuration();
        
        // Update every second
        const timer = setInterval(updateDuration, 1000);
    });

    // Example: When the user checks in
      document.querySelector("#checkInButton").addEventListener("click", function() {
          const currentTime = new Date().toISOString();
          localStorage.setItem('clockInTime', currentTime);

          // Optionally, update the duration display
          updateDuration();
      });

      // Example: When the user checks out
      document.querySelector("#checkOutButton").addEventListener("click", function() {
          const currentTime = new Date().toISOString();
          localStorage.setItem('clockOutTime', currentTime);

          // Update the timer
          updateDuration();
      });




function fetchNotifications() {
  fetch('fetch_notifications.php')
    .then(response => response.json())
    .then(data => {
      updateNotificationUI(data);

      if (data.length > 0) {
        data.forEach((notification, index) => {
          setTimeout(() => {
            displayNotificationToast(notification.message);
          }, index * 500);
        });
      }
    })
    .catch(error => console.error('Error fetching notifications:', error));
}

function updateNotificationUI(notifications) {
  const countElement = document.getElementById('notificationCount');
  const headerCountElement = document.getElementById('notificationHeaderCount');
  const listElement = document.getElementById('notificationList');
  
  const count = notifications.length;
  
  if (count > 0) {
    countElement.textContent = count;
    countElement.style.display = 'block';
    headerCountElement.textContent = count;
    
    listElement.innerHTML = '';
    
    notifications.forEach(notification => {
      const notificationItem = document.createElement('li');
      notificationItem.innerHTML = `
        <a class="dropdown-item">
          <i class="bi bi-exclamation-circle text-warning"></i>
          <div>
            <h4>Attendance Alert</h4>
            <p>${notification.message}</p>
            <p>${new Date(notification.created_at).toLocaleString()}</p>
          </div>
        </a>
      `;
      listElement.appendChild(notificationItem);
    });
    
    const divider = document.createElement('li');
    divider.innerHTML = '<hr class="dropdown-divider">';
    listElement.appendChild(divider);
    
    const footer = document.createElement('li');
    footer.innerHTML = '<a class="dropdown-item dropdown-footer" href="#" onclick="markAllAsRead()">Mark all as read</a>';
    listElement.appendChild(footer);
  } else {
    countElement.style.display = 'none';
    headerCountElement.textContent = '0';
    listElement.innerHTML = '<li><a class="dropdown-item">No new notifications</a></li>';
  }
}

    function displayNotificationToast(message) {
      const notificationDiv = document.createElement('div');
      notificationDiv.classList.add('notification');
      notificationDiv.innerHTML = `
        <strong>Attendance Alert</strong><br>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()" style="float: right; margin-top: -5px;"></button>
      `;

      document.body.appendChild(notificationDiv);

      setTimeout(() => {
        if (notificationDiv.parentElement) {
          notificationDiv.remove();
        }
      }, 8000);
    }

    function markAllAsRead() {
      fetch('mark_notifications_read.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          document.getElementById('notificationCount').style.display = 'none';
          document.getElementById('notificationHeaderCount').textContent = '0';
          document.getElementById('notificationList').innerHTML = '<li><a class="dropdown-item">No new notifications</a></li>';
        }
      })
      .catch(error => console.error('Error marking notifications as read:', error));
    }

    // Fetch notifications every 30 seconds
    setInterval(fetchNotifications, 30000);

    // Initial fetch when page loads
    fetchNotifications();

    // Refresh page every 5 minutes to check for absent status updates
    setInterval(() => {
      window.location.reload();
    }, 300000);
  </script>

</body>
</html>