<?php
require('database.php');
require('session.php');
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
    const employeeId = <?php echo json_encode($employee_id); ?>;
    const durationElement = document.getElementById("duration");
    if (!durationElement) return;

    // Get clock in/out times from PHP data attributes
    const clockInTime = durationElement.dataset.clockin;
    const clockOutTime = durationElement.dataset.clockout;

    // Store times in localStorage when page loads if they exist
    if (clockInTime) {
        localStorage.setItem(`clockInTime_${employeeId}`, clockInTime);
    }
    if (clockOutTime) {
        localStorage.setItem(`clockOutTime_${employeeId}`, clockOutTime);
    }

    // Add event listeners to the form buttons
    const checkInForm = document.querySelector('form button[name="check_in"]');
    const checkOutForm = document.querySelector('form button[name="check_out"]');

    if (checkInForm) {
        checkInForm.closest('form').addEventListener('submit', function() {
            const currentTime = new Date().toISOString();
            localStorage.setItem(`clockInTime_${employeeId}`, currentTime);
        });
    }

    if (checkOutForm) {
        checkOutForm.closest('form').addEventListener('submit', function() {
            const currentTime = new Date().toISOString();
            localStorage.setItem(`clockOutTime_${employeeId}`, currentTime);
        });
    }

    // Timer update function
    function updateDuration() {
        const clockInTimeStored = localStorage.getItem(`clockInTime_${employeeId}`);
        const clockOutTimeStored = localStorage.getItem(`clockOutTime_${employeeId}`);
        
        if (!clockInTimeStored) return;

        const clockIn = new Date(clockInTimeStored).getTime();
        const now = clockOutTimeStored ? new Date(clockOutTimeStored).getTime() : new Date().getTime();
        const diff = now - clockIn;

        const hours = String(Math.floor(diff / (1000 * 60 * 60))).padStart(2, '0');
        const minutes = String(Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
        const seconds = String(Math.floor((diff % (1000 * 60)) / 1000)).padStart(2, '0');

        durationElement.textContent = `${hours}:${minutes}:${seconds}`;

        if (!clockOutTimeStored) {
            requestAnimationFrame(updateDuration);
        }
    }

    // Start timer if checked in
    if (localStorage.getItem(`clockInTime_${employeeId}`)) {
        updateDuration();
    }
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