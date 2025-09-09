<?php
require('database.php');
require('session.php');

// For employer-specific page
if ($_SESSION['role'] != 'Employer') {
    header("Location: pages-login.php");
    exit();
}

// Create job_positions table if it doesn't exist
$create_table_query = "CREATE TABLE IF NOT EXISTS job_positions (
    id int(11) NOT NULL AUTO_INCREMENT,
    job_title varchar(255) NOT NULL,
    application_email varchar(255) NOT NULL,
    recruiter varchar(255) NOT NULL,
    open_slots int(11) DEFAULT 1,
    applications int(11) DEFAULT 0,
    status enum('active','inactive') DEFAULT 'active',
    created_date datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

mysqli_query($conn, $create_table_query);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_position':
                $job_title = mysqli_real_escape_string($conn, $_POST['job_title']);
                $application_email = mysqli_real_escape_string($conn, $_POST['application_email']);
                $recruiter = $_SESSION['username'];
                
                $query = "INSERT INTO job_positions (job_title, application_email, recruiter, open_slots, applications, status, created_date) 
                         VALUES ('$job_title', '$application_email', '$recruiter', 1, 0, 'active', NOW())";
                
                if (mysqli_query($conn, $query)) {
                    $success_message = "Job position created successfully!";
                } else {
                    $error_message = "Error creating job position: " . mysqli_error($conn);
                }
                break;
                
            case 'update_position':
                $position_id = (int)$_POST['position_id'];
                $open_slots = (int)$_POST['open_slots'];
                
                $query = "UPDATE job_positions SET open_slots = $open_slots WHERE id = $position_id";
                if (mysqli_query($conn, $query)) {
                    $success_message = "Position updated successfully!";
                }
                break;

            case 'toggle_status':
                $position_id = (int)$_POST['position_id'];
                $result = togglePositionStatus($position_id);
                if ($result['success']) {
                    echo json_encode($result);
                    exit;
                }
                break;
                
            case 'delete_position':
                $position_id = (int)$_GET['id'];
                $query = "DELETE FROM job_positions WHERE id = $position_id";
                if (mysqli_query($conn, $query)) {
                    $success_message = "Position deleted successfully!";
                } else {
                    $error_message = "Error deleting position: " . mysqli_error($conn);
                }
                break;
        }
    }
}

// Handle delete action from GET request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $position_id = (int)$_GET['id'];
    $query = "DELETE FROM job_positions WHERE id = $position_id";
    if (mysqli_query($conn, $query)) {
        $success_message = "Position deleted successfully!";
    } else {
        $error_message = "Error deleting position: " . mysqli_error($conn);
    }
}

// Fetch job positions with error handling
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = $search ? "WHERE job_title LIKE '%$search%' OR application_email LIKE '%$search%'" : '';

$query = "SELECT * FROM job_positions $search_condition ORDER BY created_date DESC";
$result = mysqli_query($conn, $query);
$job_positions = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $job_positions[] = $row;
    }
} else {
    $error_message = "Error fetching job positions: " . mysqli_error($conn);
}

function togglePositionStatus($position_id) {
    global $conn;
    
    // First get current status
    $query = "SELECT status FROM job_positions WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $position_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Toggle the status
    $new_status = ($row['status'] == 'active') ? 'inactive' : 'active';
    
    // Update the status
    $update_query = "UPDATE job_positions SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $new_status, $position_id);
    
    if ($update_stmt->execute()) {
        return ["success" => true, "new_status" => $new_status];
    }
    return ["success" => false];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>SMEasyHR - Recruitment Process</title>
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
    .job-card {
      background: white;
      border-radius: 8px;
      border: 1px solid #e0e6ed;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      margin-bottom: 20px;
    }
    
    .job-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      transform: translateY(-2px);
    }
    
    .recruiter-avatar {
      width: 40px;
      height: 40px;
      background: #6c5ce7;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      font-size: 14px;
    }
    
    .job-stats {
      background: #f8f9fa;
      border-radius: 6px;
      padding: 15px;
      text-align: center;
    }
    
    .job-stats .stat-number {
      font-size: 24px;
      font-weight: bold;
      color: #2d3436;
      margin-bottom: 4px;
    }
    
    .job-stats .stat-label {
      font-size: 12px;
      color: #636e72;
    }
    
    .status-badge {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
    }
    
    .status-active {
      background: #d4edda;
      color: #155724;
    }
    
    .status-inactive {
      background: #f8d7da;
      color: #721c24;
    }
    
    .btn-job-page {
      background: #6c5ce7;
      border: none;
      color: white;
      padding: 8px 16px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 500;
    }
    
    .btn-job-page:hover {
      background: #5f4fcf;
      color: white;
    }
    
    .btn-new-position {
      background: #6c5ce7;
      border: none;
      color: white;
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    
    .btn-new-position:hover {
      background: #5f4fcf;
      color: white;
    }
    
    .search-box {
      position: relative;
      max-width: 300px;
    }
    
    .search-box input {
      padding-left: 40px;
      border: 1px solid #ddd;
      border-radius: 6px;
    }
    
    .search-box .search-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #999;
    }
    
    .page-header {
      background: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 30px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .modal-header {
      background: #6c5ce7;
      color: white;
    }
    
    .modal-header .btn-close {
      filter: invert(1);
    }
    
    .form-label.required::after {
      content: " *";
      color: #dc3545;
    }
    
    .email-suffix {
      background: #f8f9fa;
      border: 1px solid #ced4da;
      border-left: none;
      padding: 0.375rem 0.75rem;
      border-radius: 0 0.375rem 0.375rem 0;
      color: #6c757d;
      font-size: 0.9em;
    }
    
    .no-positions {
      text-align: center;
      padding: 60px 20px;
      color: #6c757d;
    }
    
    .no-positions i {
      font-size: 4rem;
      margin-bottom: 20px;
      color: #dee2e6;
    }

    // Add this to the existing <style> section in recruitment_process.php

.kanban-board {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  overflow-x: auto;
}

.kanban-column {
  min-width: 300px;
  background: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
}

.kanban-column-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.kanban-count {
  background: #e9ecef;
  padding: 0.2rem 0.6rem;
  border-radius: 12px;
  font-size: 0.875rem;
}

.candidate-card {
  background: white;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 0.8rem;
  cursor: move;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  position: relative;
}

.candidate-card.hired {
  border: 1px solid #28a745;
}

.hired-ribbon {
  position: absolute;
  top: 10px;
  right: -5px;
  padding: 0.25rem 1rem;
  background: #28a745;
  color: white;
  font-size: 0.75rem;
  border-radius: 3px;
  transform: rotate(3deg);
}

.candidate-rating {
  color: #ffc107;
  font-size: 0.875rem;
}

.candidate-actions {
  display: flex;
  gap: 0.5rem;
}

.stage-column {
  border-right: 1px solid #dee2e6;
  padding-right: 1rem;
  margin-right: 1rem;
}

// Add to the existing <style> section

.btn-job-page.disabled {
    pointer-events: none;
    background-color: #b8b5c9 !important;
    opacity: 0.6;
}

.btn-job-page.disabled:hover {
    background-color: #b8b5c9 !important;
}

/* Add tooltip for disabled button */
.btn-job-page.disabled::after {
    content: "Position is inactive";
    position: absolute;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    white-space: nowrap;
    display: none;
}

.btn-job-page.disabled:hover::after {
    display: block;
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
      <form class="search-form d-flex align-items-center" method="GET" action="#">
        <input type="text" name="search" placeholder="Search positions..." title="Enter search keyword" value="<?php echo htmlspecialchars($search); ?>">
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
        <a class="nav-link" href="recruitment_process.php">
          <i class="bi bi-journal-text"></i><span>Recruitment Process</span>
        </a>
      </li><!-- End Recruitment Process Nav -->

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
      <h1>Recruitment Process</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Recruitment Process</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-1"></i>
      <?php echo $success_message; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-1"></i>
      <?php echo $error_message; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <button type="button" class="btn btn-new-position me-3" data-bs-toggle="modal" data-bs-target="#createPositionModal">
            <i class="bi bi-plus-lg"></i>
            New
          </button>
          <h2 class="mb-0 me-2">
            <i class="bi bi-briefcase me-2"></i>
            Job Positions
          </h2>
          <span class="badge bg-secondary"><?php echo count($job_positions); ?></span>
        </div>

        <div class="d-flex align-items-center">
          <div class="search-box me-3">
            <form method="GET" class="d-flex">
              <div class="position-relative">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control" name="search" placeholder="Search..." 
                       value="<?php echo htmlspecialchars($search); ?>">
              </div>
            </form>
          </div>
          
          <div class="d-flex align-items-center text-muted me-3">
            <small>1-<?php echo count($job_positions); ?> / <?php echo count($job_positions); ?></small>
            <button class="btn btn-sm btn-outline-secondary ms-2">
              <i class="bi bi-chevron-left"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>

          <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-secondary active">
              <i class="bi bi-grid-3x3-gap"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary">
              <i class="bi bi-list"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Job Positions Grid -->
    <?php if (count($job_positions) > 0): ?>
    <div class="row">
      <?php foreach ($job_positions as $position): ?>
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="job-card">
          <div class="card-body p-4">
            <!-- Position Header -->
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div class="d-flex align-items-center">
                <div class="recruiter-avatar me-3">
                  <?php 
                    $names = explode(' ', $position['recruiter']);
                    $initials = '';
                    foreach ($names as $name) {
                      $initials .= substr($name, 0, 1);
                    }
                    echo strtoupper($initials);
                  ?>
                </div>
                <div>
                  <h5 class="card-title mb-1"><?php echo htmlspecialchars($position['job_title']); ?></h5>
                  <p class="text-muted mb-0 small"><?php echo htmlspecialchars($position['application_email']); ?></p>
                  <p class="text-primary mb-0 small"><?php echo htmlspecialchars($position['recruiter']); ?></p>
                </div>
              </div>
                <a href="job_page.php?id=<?php echo $position['id']; ?>" 
                  class="btn btn-job-page <?php echo ($position['status'] === 'inactive') ? 'disabled' : ''; ?>"
                  <?php echo ($position['status'] === 'inactive') ? 'onclick="return false;"' : ''; ?>
                  style="<?php echo ($position['status'] === 'inactive') ? 'opacity: 0.6; cursor: not-allowed;' : ''; ?>">
                    Job Page
                </a>
            </div>

            <!-- Stats -->
            <div class="row mb-3">
              <div class="col-6">
                <div class="job-stats">
                  <div class="stat-number"><?php echo $position['open_slots']; ?></div>
                  <div class="stat-label">Open Slot<?php echo $position['open_slots'] != 1 ? 's' : ''; ?></div>
                </div>
              </div>
              <div class="col-6">
                <div class="job-stats">
                  <div class="stat-number"><?php echo $position['applications']; ?></div>
                  <div class="stat-label">Application<?php echo $position['applications'] != 1 ? 's' : ''; ?></div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
              <small class="text-muted">
                <i class="bi bi-calendar me-1"></i>
                Created <?php echo date('M d, Y', strtotime($position['created_date'])); ?>
              </small>
              <span class="status-badge status-<?php echo $position['status']; ?>">
                <?php echo ucfirst($position['status']); ?>
              </span>
            </div>

            <!-- Quick Actions -->
            <div class="mt-3 pt-3 border-top">
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" onclick="toggleStatus(<?php echo $position['id']; ?>, this)">
                    <i class="bi bi-toggle-on"></i> <?php echo ucfirst($position['status']); ?>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deletePosition(<?php echo $position['id']; ?>)">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="no-positions">
      <i class="bi bi-briefcase"></i>
      <h3>No job positions found</h3>
      <p class="mb-4">
        <?php echo $search ? 'No positions match your search criteria.' : 'You haven\'t created any job positions yet.'; ?>
      </p>
      <?php if (!$search): ?>
      <button type="button" class="btn btn-new-position" data-bs-toggle="modal" data-bs-target="#createPositionModal">
        <i class="bi bi-plus-lg"></i>
        Create Your First Job Position
      </button>
      <?php else: ?>
      <a href="recruitment_process.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
        View All Positions
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </main><!-- End #main -->

  <!-- Create Position Modal -->
  <div class="modal fade" id="createPositionModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create a Job Position</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
<form method="POST" action="">
    <div class="modal-body">
        <input type="hidden" name="action" value="create_position"> <!-- Action field for identification -->
        <div class="mb-3">
            <label for="job_title" class="form-label required">Job Position</label>
            <input type="text" class="form-control" name="job_title" id="job_title" placeholder="e.g. Sales Manager" required>
        </div>
        <div class="mb-3">
            <label for="application_email" class="form-label required">Application email</label>
            <div class="input-group">
                <input type="email" class="form-control" name="application_email" id="application_email" placeholder="e.g. sales-manager@smeasybr.od" required>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Discard</button>
        <button type="submit" class="btn btn-primary">Create</button> <!-- Submit button -->
    </div>
</form>

      </div>
    </div>
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
    function editPosition(positionId) {
      // Implement edit position functionality
      alert('Edit position functionality - Position ID: ' + positionId);
    }

    function viewApplications(positionId) {
      // Implement view applications functionality
      alert('View applications functionality - Position ID: ' + positionId);
    }

    function deletePosition(positionId) {
      if (confirm('Are you sure you want to delete this job position?')) {
        // Implement delete position functionality
        window.location.href = 'recruitment_process.php?action=delete&id=' + positionId;
      }
    }

    // Clear form when modal is closed
    document.getElementById('createPositionModal').addEventListener('hidden.bs.modal', function () {
      document.querySelector('#createPositionModal form').reset();
    });

    // Add this to your existing <script> section

function toggleStatus(positionId, button) {
    if (confirm('Are you sure you want to change the status of this position?')) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('position_id', positionId);

        // Send AJAX request
        fetch('recruitment_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the status badge
                const statusBadge = button.closest('.job-card').querySelector('.status-badge');
                statusBadge.textContent = data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1);
                statusBadge.className = `status-badge status-${data.new_status}`;
                
                // Update the button text
                button.innerHTML = `<i class="bi bi-toggle-on"></i> ${data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1)}`;
                
                // Show success message
                alert('Status updated successfully!');
            } else {
                alert('Failed to update status. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the status.');
        });
    }
}

// Add to the existing <script> section

function toggleStatus(positionId, button) {
    if (confirm('Are you sure you want to change the status of this position?')) {
        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('position_id', positionId);

        fetch('recruitment_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the status badge
                const jobCard = button.closest('.job-card');
                const statusBadge = jobCard.querySelector('.status-badge');
                statusBadge.textContent = data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1);
                statusBadge.className = `status-badge status-${data.new_status}`;
                
                // Update the button text
                button.innerHTML = `<i class="bi bi-toggle-on"></i> ${data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1)}`;
                
                // Update the Job Page button state
                const jobPageButton = jobCard.querySelector('.btn-job-page');
                if (data.new_status === 'inactive') {
                    jobPageButton.classList.add('disabled');
                    jobPageButton.style.opacity = '0.6';
                    jobPageButton.style.cursor = 'not-allowed';
                    jobPageButton.onclick = function(e) { return false; };
                } else {
                    jobPageButton.classList.remove('disabled');
                    jobPageButton.style.opacity = '';
                    jobPageButton.style.cursor = '';
                    jobPageButton.onclick = null;
                }
                
                // Show success message
                alert('Status updated successfully!');
            } else {
                alert('Failed to update status. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the status.');
        });
    }
}
  </script>

</body>

</html>