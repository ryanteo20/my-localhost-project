<?php
require('database.php');
require('session.php');

// For employer-specific page
if ($_SESSION['role'] != 'Employer') {
    header("Location: pages-login.php");
    exit();
}

// Get current month data and 5 months before
$current_month = date('m');
$current_year = date('Y');

// Initialize arrays to store 6 months of data
$months_data = [];
$months_labels = [];

// Generate data for current month and 5 months before
for ($i = 0; $i < 6; $i++) {
    $month = $current_month - $i;
    $year = $current_year;
    
    // Handle year transition
    if ($month <= 0) {
        $month += 12;
        $year--;
    }
    
    // Store month info
    $months_data[$i] = [
        'month' => $month,
        'year' => $year,
        'label' => date('M Y', mktime(0, 0, 0, $month, 1, $year))
    ];
    $months_labels[] = $months_data[$i]['label'];
}

// Reverse arrays to show oldest to newest
$months_data = array_reverse($months_data);
$months_labels = array_reverse($months_labels);

// Initialize data arrays
$approved_leave_data = [];
$approved_claims_data = [];
$approved_claims_amount = [];

// Get data for each month
foreach ($months_data as $month_info) {
    // Query for approved leave count
    $query_leave = "SELECT COUNT(*) AS approved_leave FROM leave_apply 
                    WHERE leave_review = 'Approved' 
                    AND MONTH(leave_datestart) = ? 
                    AND YEAR(leave_datestart) = ?";
    $stmt = $conn->prepare($query_leave);
    $stmt->bind_param("ii", $month_info['month'], $month_info['year']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $approved_leave_data[] = (int)$result['approved_leave'];

    // Query for approved claims count
    $query_claims_count = "SELECT COUNT(*) AS approved_claims FROM claims 
                          WHERE status = 'Approved' 
                          AND MONTH(transaction_date) = ? 
                          AND YEAR(transaction_date) = ?";
    $stmt = $conn->prepare($query_claims_count);
    $stmt->bind_param("ii", $month_info['month'], $month_info['year']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $approved_claims_data[] = (int)$result['approved_claims'];

    // Query for approved claims amount
    $query_claims_amount = "SELECT COALESCE(SUM(amount), 0) AS approved_amount FROM claims 
                           WHERE status = 'Approved' 
                           AND MONTH(transaction_date) = ? 
                           AND YEAR(transaction_date) = ?";
    $stmt = $conn->prepare($query_claims_amount);
    $stmt->bind_param("ii", $month_info['month'], $month_info['year']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $approved_claims_amount[] = (float)$result['approved_amount'];
}

// Correctly handle the previous month for existing cards
if ($current_month == 1) {
    $previous_month = 12;
    $previous_year = $current_year - 1;
} else {
    $previous_month = $current_month - 1;
    $previous_year = $current_year;
}

// Existing queries for cards (keep as is for backward compatibility)
$query_current_approved_leave = "SELECT COUNT(*) AS current_approved_leave FROM leave_apply WHERE leave_review = 'Approved' AND MONTH(leave_datestart) = ? AND YEAR(leave_datestart) = ?";
$stmt = $conn->prepare($query_current_approved_leave);
$stmt->bind_param("ii", $current_month, $current_year);
$stmt->execute();
$current_approved_leave_result = $stmt->get_result()->fetch_assoc();
$current_approved_leave = $current_approved_leave_result['current_approved_leave'];

$query_previous_approved_leave = "SELECT COUNT(*) AS previous_approved_leave FROM leave_apply WHERE leave_review = 'Approved' AND MONTH(leave_datestart) = ? AND YEAR(leave_datestart) = ?";
$stmt = $conn->prepare($query_previous_approved_leave);
$stmt->bind_param("ii", $previous_month, $previous_year);
$stmt->execute();
$previous_approved_leave_result = $stmt->get_result()->fetch_assoc();
$previous_approved_leave = $previous_approved_leave_result['previous_approved_leave'];

$query_current_approved_claims = "SELECT COUNT(*) AS current_approved_claims FROM claims WHERE status = 'Approved' AND MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?";
$stmt = $conn->prepare($query_current_approved_claims);
$stmt->bind_param("ii", $current_month, $current_year);
$stmt->execute();
$current_approved_claims_result = $stmt->get_result()->fetch_assoc();
$current_approved_claims = $current_approved_claims_result['current_approved_claims'];

$query_previous_approved_claims = "SELECT COUNT(*) AS previous_approved_claims FROM claims WHERE status = 'Approved' AND MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?";
$stmt = $conn->prepare($query_previous_approved_claims);
$stmt->bind_param("ii", $previous_month, $previous_year);
$stmt->execute();
$previous_approved_claims_result = $stmt->get_result()->fetch_assoc();
$previous_approved_claims = $previous_approved_claims_result['previous_approved_claims'];
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
  
  <!-- Custom CSS for chart sizing -->
  <style>
    .chart-container {
      position: relative;
      height: 250px;
      width: 100%;
    }
    
    .chart-card {
      height: 350px;
    }
    
    .chart-card .card-body {
      padding: 1rem;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    
    .chart-card .card-title {
      margin-bottom: 0.5rem;
      font-size: 0.95rem;
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

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Home</h1>
    </div><!-- End Page Title -->

    <section class="section dashboard">
  <div class="row">
    <h3>Employee Management</h3>
    
    <!-- Left side columns -->
    <div class="col-lg-12">
      <div class="row">

        <!-- Sales Card: Total Employee -->
        <div class="col-xxl-3 col-md-6 col-sm-12 mb-4">
          <div class="card info-card sales-card">
            <div class="filter">
              <a class="icon" href="#" data-bs-toggle="dropdown"></a>
            </div>
            <div class="card-body">
              <h5 class="card-title">Total Employee</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-group-line"></i>
                </div>
                <div class="ps-3">
                  <?php
                  require('database.php');
                  $query = "SELECT COUNT(*) AS total_employees FROM employeelogin";
                  $result = mysqli_query($conn, $query);
                  if ($result) {
                      $row = mysqli_fetch_assoc($result);
                      $totalEmployees = $row['total_employees'];
                  }
                  echo "<h6>$totalEmployees</h6>";
                  ?>
                </div>
              </div>
            </div>
          </div>
        </div><!-- End Total Employee Card -->

        <!-- Sales Card: Total Leave Pending -->
        <div class="col-xxl-3 col-md-6 col-sm-12 mb-4">
          <div class="card info-card sales-card">
            <div class="filter">
              <a class="icon" href="#" data-bs-toggle="dropdown"></a>
            </div>
            <div class="card-body">
              <h5 class="card-title">Total Leave Pending</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-group-line"></i>
                </div>
                <div class="ps-3">
                  <?php
                  require('database.php');
                  $query_pending_review = "SELECT COUNT(*) AS pending_review FROM leave_apply WHERE leave_review = 'Pending for review'";
                  $result_pending_review = mysqli_query($conn, $query_pending_review);
                  if ($result_pending_review) {
                      $row_pending_review = mysqli_fetch_assoc($result_pending_review);
                      $pendingReview = $row_pending_review['pending_review'];
                  } else {
                      $pendingReview = "Error fetching pending leave for review";
                  }
                  echo "<h6>$pendingReview</h6>";
                  ?>
                </div>
              </div>
            </div>
          </div>
        </div><!-- End Total Leave Pending Card -->

        <!-- Sales Card: Total Claim Requested -->
        <div class="col-xxl-3 col-md-6 col-sm-12 mb-4">
          <div class="card info-card sales-card">
            <div class="filter">
              <a class="icon" href="#" data-bs-toggle="dropdown"></a>
            </div>
            <div class="card-body">
              <h5 class="card-title">Total Claim Pending</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-money-dollar-box-line"></i>
                </div>
                <div class="ps-3">
                  <?php
                  require('database.php');
                  $query_pending_claims = "SELECT COUNT(*) AS pending_claims FROM claims WHERE status = 'Pending'";
                  $result_pending_claims = mysqli_query($conn, $query_pending_claims);
                  if ($result_pending_claims) {
                      $row_pending_claims = mysqli_fetch_assoc($result_pending_claims);
                      $pendingClaims = $row_pending_claims['pending_claims'];
                  } else {
                      $pendingClaims = "Error fetching pending claims";
                  }
                  echo "<h6>$pendingClaims</h6>";
                  ?>
                </div>
              </div>
            </div>
          </div>
        </div><!-- End Total Claim Requested Card -->

      </div><!-- End Row -->
    </div><!-- End Left side columns -->
  </div><!-- End Section -->
</section>

    <!-- Charts Section -->
    <section class="section dashboard">
      <div class="row">
        <h3>Analytics Dashboard - Last 6 Months</h3>
        
        <!-- Approved Leave Chart -->
        <div class="col-xxl-4 col-md-6 col-sm-12 mb-3">
          <div class="card chart-card">
            <div class="card-body">
              <h5 class="card-title">Approved Leave Applications</h5>
              <div class="chart-container">
                <canvas id="approvedLeaveChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Approved Claims Count Chart -->
        <div class="col-xxl-4 col-md-6 col-sm-12 mb-3">
          <div class="card chart-card">
            <div class="card-body">
              <h5 class="card-title">Approved Claims Count</h5>
              <div class="chart-container">
                <canvas id="approvedClaimsChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Approved Claims Amount Chart -->
        <div class="col-xxl-4 col-md-12 col-sm-12 mb-3">
          <div class="card chart-card">
            <div class="card-body">
              <h5 class="card-title">Approved Claims Amount (RM)</h5>
              <div class="chart-container">
                <canvas id="approvedClaimsAmountChart"></canvas>
              </div>
            </div>
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

<script>
// Data from PHP
const monthsLabels = <?php echo json_encode($months_labels); ?>;
const approvedLeaveData = <?php echo json_encode($approved_leave_data); ?>;
const approvedClaimsData = <?php echo json_encode($approved_claims_data); ?>;
const approvedClaimsAmountData = <?php echo json_encode($approved_claims_amount); ?>;

// Common chart options - optimized for smaller containers
const commonOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      titleFont: {
        size: 12
      },
      bodyFont: {
        size: 11
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1,
        font: {
          size: 10
        },
        callback: function(value) {
          return Number.isInteger(value) ? value : '';
        }
      },
      grid: {
        color: 'rgba(0,0,0,0.1)'
      }
    },
    x: {
      ticks: {
        maxRotation: 45,
        minRotation: 45,
        font: {
          size: 10
        }
      },
      grid: {
        display: false
      }
    }
  },
  elements: {
    point: {
      radius: 3,
      hoverRadius: 5
    }
  }
};

// Chart 1: Approved Leave Applications
const ctx1 = document.getElementById('approvedLeaveChart').getContext('2d');
const approvedLeaveChart = new Chart(ctx1, {
  type: 'line',
  data: {
    labels: monthsLabels,
    datasets: [{
      label: 'Approved Leave',
      data: approvedLeaveData,
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      borderColor: 'rgba(75, 192, 192, 1)',
      borderWidth: 2,
      fill: true,
      tension: 0.3,
      pointBackgroundColor: 'rgba(75, 192, 192, 1)',
      pointBorderColor: '#fff',
      pointBorderWidth: 1,
      pointRadius: 3
    }]
  },
  options: commonOptions
});

// Chart 2: Approved Claims Count
const ctx2 = document.getElementById('approvedClaimsChart').getContext('2d');
const approvedClaimsChart = new Chart(ctx2, {
  type: 'bar',
  data: {
    labels: monthsLabels,
    datasets: [{
      label: 'Approved Claims',
      data: approvedClaimsData,
      backgroundColor: 'rgba(255, 99, 132, 0.6)',
      borderColor: 'rgba(255, 99, 132, 1)',
      borderWidth: 1,
      borderRadius: 2,
      borderSkipped: false
    }]
  },
  options: commonOptions
});

// Chart 3: Approved Claims Amount
const ctx3 = document.getElementById('approvedClaimsAmountChart').getContext('2d');
const approvedClaimsAmountChart = new Chart(ctx3, {
  type: 'line',
  data: {
    labels: monthsLabels,
    datasets: [{
      label: 'Claims Amount (RM)',
      data: approvedClaimsAmountData,
      backgroundColor: 'rgba(54, 162, 235, 0.2)',
      borderColor: 'rgba(54, 162, 235, 1)',
      borderWidth: 2,
      fill: true,
      tension: 0.3,
      pointBackgroundColor: 'rgba(54, 162, 235, 1)',
      pointBorderColor: '#fff',
      pointBorderWidth: 1,
      pointRadius: 3
    }]
  },
  options: {
    ...commonOptions,
    scales: {
      ...commonOptions.scales,
      y: {
        beginAtZero: true,
        ticks: {
          font: {
            size: 10
          },
          callback: function(value, index, values) {
            return 'RM ' + value.toLocaleString('en-MY', {minimumFractionDigits: 0, maximumFractionDigits: 0});
          }
        },
        grid: {
          color: 'rgba(0,0,0,0.1)'
        }
      }
    }
  }
});
</script>

</body>

</html>