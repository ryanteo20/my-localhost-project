<?php
require('database.php');
require('session.php');
require('send_notification.php');

date_default_timezone_set('Asia/Kuala_Lumpur');
$date = date('Y-m-d');
$time_now = date('Y-m-d H:i:s');

$employee_id = $_SESSION['ID'] ?? null;

$status = 'present';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
$location_coordinates = null;

// Function to insert notification into the database
function insertNotification($employee_id, $employer_id, $message) {
    global $con;
    $query = "INSERT INTO notifications (employee_id, employer_id, message, status, created_at) VALUES (?, ?, ?, 'unread', NOW())";
    $stmt = $con->prepare($query);
    $stmt->bind_param("iis", $employee_id, $employer_id, $message);
    return $stmt->execute();
}

// Function to get employee name
function getEmployeeName($employee_id) {
    global $con;
    $query = "SELECT username FROM employeelogin WHERE ID = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['username'] : "Employee #$employee_id";
}

// Function to get employer ID for an employee
function getEmployerID($employee_id) {
    global $con;
    $query = "SELECT employer_id FROM employeelogin WHERE ID = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['employer_id'] : null;
}

// Function to get employer email
function getEmployerEmail($employer_id) {
    global $con;
    $query = "SELECT email FROM employers WHERE ID = ?"; // Adjust table name as needed
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $employer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['email'] : null;
}

// Handle Clock In
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_in'])) {
    $query = "INSERT INTO attendance (employee_id, date, clock_in, status, ip_address, location_coordinates)
              VALUES (?, ?, ?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE clock_in = VALUES(clock_in), status = VALUES(status), ip_address = VALUES(ip_address), location_coordinates = VALUES(location_coordinates)";

    $stmt = $con->prepare($query);
    $stmt->bind_param("isssss", $employee_id, $date, $time_now, $status, $ip_address, $location_coordinates);
    
    if ($stmt->execute()) {
        // Check if clocked in after 9 AM
        $clock_in_cutoff = strtotime($date . ' 09:00:00');
        $clock_in_time = strtotime($time_now);
        
        if ($clock_in_time > $clock_in_cutoff) {
            $employee_name = getEmployeeName($employee_id);
            $employer_id = getEmployerID($employee_id);
            $employer_email = getEmployerEmail($employer_id);
            
            $late_minutes = round(($clock_in_time - $clock_in_cutoff) / 60);
            $notification_message = "$employee_name clocked in late at " . date('g:i A', $clock_in_time) . " (${late_minutes} minutes after 9:00 AM)";
            
            if ($employer_id && $employer_email) {
                // Insert system notification
                insertNotification($employee_id, $employer_id, $notification_message);
                
                // Send email notification
                sendNotification($employer_email, $employee_name, $notification_message);
            }
        }
    } else {
        die("Error clocking in: " . $stmt->error);
    }
}

// Handle Clock Out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_out'])) {
    $checkQuery = "SELECT clock_out FROM attendance WHERE employee_id = ? AND date = ?";
    $checkStmt = $con->prepare($checkQuery);
    $checkStmt->bind_param("is", $employee_id, $date);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        if (empty($row['clock_out'])) {
            // Update clock-out time
            $updateQuery = "UPDATE attendance SET clock_out = ?, ip_address = ? WHERE employee_id = ? AND date = ?";
            $updateStmt = $con->prepare($updateQuery);
            $updateStmt->bind_param("ssis", $time_now, $ip_address, $employee_id, $date);
            
            if ($updateStmt->execute()) {
                // Check if clocked out before 6 PM
                $clock_out_cutoff = strtotime($date . ' 18:00:00');
                $clock_out_time = strtotime($time_now);
                
                if ($clock_out_time < $clock_out_cutoff) {
                    $employee_name = getEmployeeName($employee_id);
                    $employer_id = getEmployerID($employee_id);
                    $employer_email = getEmployerEmail($employer_id);
                    
                    $early_minutes = round(($clock_out_cutoff - $clock_out_time) / 60);
                    $notification_message = "$employee_name clocked out early at " . date('g:i A', $clock_out_time) . " (${early_minutes} minutes before 6:00 PM)";
                    
                    if ($employer_id && $employer_email) {
                        // Insert system notification
                        insertNotification($employee_id, $employer_id, $notification_message);
                        
                        // Send email notification
                        sendNotification($employer_email, $employee_name, $notification_message);
                    }
                }
            }
        }
    }
}

// ✅ Fetch today's attendance
$query = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("is", $employee_id, $date);
$stmt->execute();
$result = $stmt->get_result();
$attendance = $result->fetch_assoc();

// ✅ Define flags to control UI logic
$hasClockedIn = false;
$hasClockedOut = false;

if ($attendance) {
    $hasClockedIn = !empty($attendance['clock_in']);
    $hasClockedOut = !empty($attendance['clock_out']);
}

// Add a scheduled task check for employees who haven't clocked in by 9:15 AM
$current_time = time();
$late_check_cutoff = strtotime($date . ' 09:15:00');

if ($current_time > $late_check_cutoff) {
    // Check for employees who should have clocked in but haven't
    $missedClockInQuery = "
        SELECT el.ID, el.username, el.employer_id, e.email as employer_email
        FROM employeelogin el 
        LEFT JOIN employers e ON el.employer_id = e.ID
        LEFT JOIN attendance a ON el.ID = a.employee_id AND a.date = ?
        WHERE a.clock_in IS NULL AND el.status = 'active'
    ";
    
    $stmt = $con->prepare($missedClockInQuery);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $notification_message = $row['username'] . " has not clocked in today (as of " . date('g:i A') . ")";
        
        // Check if notification already sent today
        $checkNotificationQuery = "SELECT ID FROM notifications WHERE employee_id = ? AND employer_id = ? AND DATE(created_at) = ? AND message LIKE '%has not clocked in%'";
        $checkStmt = $con->prepare($checkNotificationQuery);
        $checkStmt->bind_param("iis", $row['ID'], $row['employer_id'], $date);
        $checkStmt->execute();
        $existingNotification = $checkStmt->get_result();
        
        if ($existingNotification->num_rows == 0) {
            // Insert system notification
            insertNotification($row['ID'], $row['employer_id'], $notification_message);
            
            // Send email notification
            if ($row['employer_email']) {
                sendNotification($row['employer_email'], $row['username'], $notification_message);
            }
        }
    }
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
  
  <style>
    /* Notification styles */
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
    
    .notification-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #dc3545;
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      font-size: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
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
          </a><!-- End Profile Image Icon -->

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
        <a class="nav-link collapsed" data-bs-target="#tables-nav" data-bs-toggle="collapse" href="attendance_employer.php">
          <i class="bi bi-layout-text-window-reverse"></i><span>Attendance</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="tables-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
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
      </li><!-- End Attendance Nav -->

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

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Attendance</h1>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="card p-4" style="max-width: 500px; margin: auto;">
        <h4 class="mb-3">Clock In / Clock Out</h4>

        <div class="d-flex align-items-center justify-content-between border rounded p-3">
          <div class="d-flex align-items-center">
            <span class="me-2" style="font-size: 20px; color: <?= $hasClockedIn ? 'green' : 'red' ?>">●</span>
            <div>
              <?php if ($hasClockedIn): ?>
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
            <?php if (!$hasClockedIn): ?>
              <button type="submit" name="check_in" class="btn btn-success">Check In</button>
            <?php elseif (!$hasClockedOut): ?>
              <button type="submit" name="check_out" class="btn btn-warning">Check Out</button>
            <?php else: ?>
              <span class="badge bg-secondary">Checked Out</span>
            <?php endif; ?>
          </form>
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
  
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const durationElement = document.getElementById("duration");
      if (!durationElement) return;

      const clockIn = new Date(durationElement.dataset.clockin).getTime();
      const clockOutAttr = durationElement.dataset.clockout;
      const clockOut = clockOutAttr ? new Date(clockOutAttr).getTime() : null;

      function updateDuration() {
        const now = clockOut || new Date().getTime();
        const diff = now - clockIn;

        const hours = String(Math.floor(diff / (1000 * 60 * 60))).padStart(2, '0');
        const minutes = String(Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
        const seconds = String(Math.floor((diff % (1000 * 60)) / 1000)).padStart(2, '0');

        durationElement.textContent = `${hours}:${minutes}:${seconds}`;

        if (clockOut) clearInterval(timer);
      }

      updateDuration();
      const timer = setInterval(updateDuration, 1000);
    });

    // Notification system
    function fetchNotifications() {
      fetch('fetch_notifications.php')
        .then(response => response.json())
        .then(data => {
          updateNotificationUI(data);
          
          // Display new notifications as toast
          if (data.length > 0) {
            data.forEach((notification, index) => {
              setTimeout(() => {
                displayNotificationToast(notification.message);
              }, index * 500); // Stagger notifications
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
        
        // Clear existing notifications
        listElement.innerHTML = '';
        
        // Add new notifications
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
        
        // Add divider and footer
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

      // Auto remove after 8 seconds
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
          // Update UI to show no notifications
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
  </script>

</body>
</html>