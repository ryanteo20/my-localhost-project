<?php
require('database.php');
require('session.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// For employer-specific page
if ($_SESSION['role'] != 'Employer') {
    header("Location: pages-login.php");
    exit();
}

// Fetch all job applications
$applications_query = "SELECT ja.*, jp.job_title 
    FROM job_applications ja 
    LEFT JOIN job_positions jp ON ja.position_id = jp.id 
    ORDER BY ja.created_at DESC";
$applications_result = mysqli_query($conn, $applications_query);

// Group applications by stage
$applications_by_stage = [];
while ($row = mysqli_fetch_assoc($applications_result)) {
    $stage = $row['stage'];
    if (!isset($applications_by_stage[$stage])) {
        $applications_by_stage[$stage] = [];
    }
    $applications_by_stage[$stage][] = $row;
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
.kanban-board {
    display: flex;
    gap: 20px;
    padding: 20px;
    overflow-x: auto;
    background: #f8f9fa;
    min-height: calc(100vh - 300px);
}

.kanban-column {
    min-width: 300px;
    background: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.kanban-column-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 15px;
    border-bottom: 2px solid #f8f9fa;
}

.kanban-column-header h6 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.kanban-count {
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.85rem;
    color: #666;
}

.candidate-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    cursor: move;
    position: relative;
    transition: all 0.3s ease;
}

.candidate-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.candidate-card.hired {
    border: 2px solid #28a745;
}

.hired-ribbon {
    position: absolute;
    top: -10px;
    right: -10px;
    background: #28a745;
    color: white;
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 0.75rem;
    transform: rotate(5deg);
}

.sidebar {
    transition: all 0.3s ease;
}

#main {
    transition: margin-left 0.3s ease;
}

.footer {
    transition: margin-left 0.3s ease;
}

.toggle-sidebar .sidebar {
    left: -300px;
}

.toggle-sidebar #main {
    margin-left: 0;
}

.toggle-sidebar .footer {
    margin-left: 0;
}

.add-candidate-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #4154f1;
    color: white;
    border: none;
    border-radius: 50px;
    padding: 12px 24px;
    font-size: 14px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 8px;
    z-index: 1000;
    transition: all 0.3s ease;
}

.add-candidate-btn:hover {
    background: #364ed9;
    transform: translateY(-2px);
}

.kanban-column.drag-over {
    background: #e8f5e9;
    border: 0.5px solid #4caf50;
}

.candidate-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    cursor: move;
    position: relative;
}

.sidebar {
    position: fixed;
    top: 60px;
    left: 0;
    bottom: 0;
    width: 300px;
    z-index: 996;
    transition: all 0.3s;
    padding: 20px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #aab7cf transparent;
    box-shadow: 0px 0px 20px rgba(1, 41, 112, 0.1);
    background-color: #fff;
}

#main {
    margin-left: 300px;
    padding: 20px 30px;
    transition: all 0.3s;
}

.toggle-sidebar-btn {
    font-size: 32px;
    padding-left: 10px;
    cursor: pointer;
    color: #012970;
}

/* Sidebar toggle state */
body.toggle-sidebar .sidebar {
    left: -300px;
}

body.toggle-sidebar #main {
    margin-left: 0;
}

body.toggle-sidebar .footer {
    margin-left: 0;
}

.modal-content {
    border-radius: 10px;
}

.modal-header {
    background: #f8f9fa;
    border-radius: 10px 10px 0 0;
}

.modal-footer {
    background: #f8f9fa;
    border-radius: 0 0 10px 10px;
}

.form-label {
    font-weight: 600;
    color: #012970;
}

.form-control:focus {
    border-color: #4154f1;
    box-shadow: 0 0 0 0.2rem rgba(65, 84, 241, 0.25);
}



.modal-lg {
    max-width: 1000px;
}

.nav-tabs .nav-link {
    color: #012970;
    font-weight: 600;
}

.nav-tabs .nav-link.active {
    color: #4154f1;
    border-bottom: 2px solid #4154f1;
}

.tab-content {
    background: #fff;
    border-radius: 0 0 5px 5px;
}

.card {
    border: 1px solid #eee;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.card-title {
    color: #012970;
    font-weight: 600;
    margin-bottom: 15px;
}

.text-muted {
    color: #6c757d !important;
}

.border {
    border-color: #eee !important;
}

textarea.form-control.border-0:focus {
    box-shadow: none;
}

.file-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
}

.file-upload-area:hover {
    border-color: #4154f1;
}

.candidate-form-field {
    width: 100%;
    margin-bottom: 20px;
}

.recruiter-select {
    width: 100%;
    padding: 8px 12px;
    border-radius: 4px;
    border: 1px solid #ced4da;
}

.job-position-wrapper {
    display: flex;
    gap: 10px;
    align-items: start;
}

.job-position-select {
    flex: 1;
}

.add-position-btn {
    padding: 6px 12px;
    background: #4154f1;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.add-position-btn:hover {
    background: #364ed9;
}

.candidate-actions {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 10px;
}

.rating-stars {
    display: inline-flex;
    align-items: center;
    gap: 2px;
}

.rating-stars i {
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
}

.rating-stars i:hover {
    transform: scale(1.2);
}

.text-warning {
    color: #ffc107 !important;
}

.text-muted {
    color: #6c757d !important;
}

.rating-stars-edit {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    margin-bottom: 10px;
}

.rating-stars-edit i {
    cursor: pointer;
    font-size: 18px;
    transition: all 0.2s ease;
}

.rating-stars-edit i:hover {
    transform: scale(1.2);
}

.rating-stars-edit:hover i.bi-star-fill {
    color: #dee2e6 !important;
}

.rating-stars-edit i:hover ~ i {
    color: #dee2e6 !important;
}

.rating-stars-edit:hover i:hover,
.rating-stars-edit:hover i:hover ~ i {
    color: #ffc107 !important;
}

.resume-item {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 10px;
}

.resume-item:hover {
    background: #e9ecef;
}

.btn-group .btn {
    font-size: 0.8rem;
    padding: 4px 8px;
}

.text-truncate {
    max-width: 150px;
}

#currentResumeSection .form-label {
    font-size: 0.9rem;
    margin-bottom: 8px;
}
#currentResume .bg-light {
    background-color: #f8f9fa !important;
    border: 1px solid #e9ecef !important;
}

#resumeActions {
    gap: 8px;
}

#resumeActions .btn {
    font-size: 0.9rem;
    padding: 8px 16px;
    border-radius: 6px;
}

#resumeActions .btn i {
    font-size: 0.85rem;
}

.resume-file-info {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.file-upload-area {
    background: #fafafa;
    border: 2px dashed #d1d5db;
    transition: all 0.3s ease;
}

.file-upload-area:hover {
    border-color: #4154f1;
    background: #f0f8ff;
}

.min-w-0 {
    min-width: 0;
}

.flex-shrink-0 {
    flex-shrink: 0;
}

#currentResume .text-truncate {
    max-width: 200px; /* Adjust this value as needed */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

#currentResume .d-flex {
    overflow: hidden;
}

/* Ensure the resume section doesn't overflow */
#currentResumeSection {
    max-width: 100%;
    overflow: hidden;
}

#currentResumeSection .card-body {
    padding: 15px;
}

/* Better button spacing */
#resumeActions {
    margin-top: 10px;
}

#resumeActions .btn {
    white-space: nowrap;
    font-size: 0.85rem;
}

#currentResume .bg-light {
    background-color: #f8f9fa !important;
    border: 1px solid #e9ecef !important;
}

#currentResume .d-grid {
    gap: 8px !important;
}

#currentResume .btn {
    white-space: nowrap;
    font-size: 0.85rem;
    padding: 6px 12px;
}

#currentResume .fw-semibold {
    font-size: 0.9rem;
    color: #495057;
    max-width: 100%;
    overflow-wrap: break-word;
    word-wrap: break-word;
    hyphens: auto;
}

/* Ensure the resume section container is properly sized */
#currentResumeSection {
    max-width: 100%;
    overflow: hidden;
}

#currentResumeSection .card-body {
    padding: 15px;
}

/* Remove conflicting text-truncate rules */
#currentResume .text-truncate {
    /* Remove or comment out this rule */
}

/* Better responsive behavior */
@media (max-width: 768px) {
    #currentResume .fw-semibold {
        font-size: 0.8rem;
    }
    
    #currentResume .btn {
        font-size: 0.8rem;
        padding: 5px 10px;
    }
}

.file-upload-area {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #fafafa;
}

.file-upload-area:hover {
    border-color: #4154f1;
    background: #f0f8ff;
}

.file-upload-area.drag-over {
    border-color: #4154f1;
    background: #e8f4fd;
    transform: scale(1.02);
}

.file-upload-area input[type="file"] {
    position: absolute;
    left: -9999px;
}
.candidate-menu {
    position: relative;
    display: inline-block;
}

.menu-dots {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    color: #6c757d;
    transition: all 0.2s ease;
}

.menu-dots:hover {
    background: #f8f9fa;
    color: #495057;
}

.dropdown-menu-custom {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 150px;
    z-index: 1000;
    display: none;
    padding: 4px 0;
}

.dropdown-menu-custom.show {
    display: block;
}

.dropdown-item-custom {
    display: block;
    width: 100%;
    padding: 8px 16px;
    clear: both;
    font-weight: 400;
    color: #212529;
    text-align: inherit;
    text-decoration: none;
    white-space: nowrap;
    background: transparent;
    border: 0;
    cursor: pointer;
    font-size: 14px;
    line-height: 1.5;
    transition: all 0.15s ease-in-out;
}

.dropdown-item-custom:hover {
    color: #16181b;
    background-color: #f8f9fa;
}

.dropdown-item-custom.text-danger:hover {
    color: #dc3545;
    background-color: #f8d7da;
}

.dropdown-item-custom i {
    margin-right: 8px;
    width: 16px;
    text-align: center;
}

/* Refuse stage toggle button */
.refuse-toggle-btn {
    position: fixed;
    top: 50%;
    right: 20px;
    transform: translateY(-50%);
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 25px;
    padding: 10px 15px;
    font-size: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    z-index: 999;
    transition: all 0.3s ease;
    cursor: pointer;
}

.refuse-toggle-btn:hover {
    background: #5a6268;
    transform: translateY(-50%) scale(1.05);
}

.refuse-toggle-btn.active {
    background: #dc3545;
}

/* Refuse stage styling */
.kanban-column[data-stage="Refuse"] {
    border: 2px solid #dc3545;
    background: #fff5f5;
}

.kanban-column[data-stage="Refuse"] .kanban-column-header {
    color: #dc3545;
    border-bottom-color: #dc3545;
}

/* Hide refuse stage by default */
.refuse-stage-hidden {
    display: none !important;
}

/* Modal styles for refuse reason */
.refuse-modal .modal-content {
    border-radius: 12px;
}

.refuse-modal .modal-header {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border-radius: 12px 12px 0 0;
}

.refuse-modal .btn-refuse {
    background: #dc3545;
    border-color: #dc3545;
}

.refuse-modal .btn-refuse:hover {
    background: #c82333;
    border-color: #bd2130;
}

.refuse-reason-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
}

.reason-tag {
    padding: 6px 12px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.reason-tag:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.reason-tag.selected {
    background: #dc3545;
    color: white;
    border-color: #dc3545;
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
        <h1>Job Applications</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="recruitment_process.php">Recruitment Process</a></li>
                <li class="breadcrumb-item active">Job Applications</li>
                <button class="add-candidate-btn" id="addCandidateBtn">                    
                    <i class="bi bi-plus-lg"></i>
                    New Candidate
                </button>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                      <div class="kanban-board">
                        <?php
                        $stages = ['New', 'Qualification', 'Interview', 'Contract Proposal', 'Contract Signed'];
                        foreach ($stages as $stage):
                        ?>
                        <div class="kanban-column" data-stage="<?php echo $stage; ?>">
                            <div class="kanban-column-header">
                                <h6><?php echo $stage; ?></h6>
                                <span class="kanban-count">0</span>
                            </div>
                            <div class="kanban-items">
                                <?php 
                                if ($stage === 'New' || isset($applications_by_stage[$stage])):
                                    if (isset($applications_by_stage[$stage])):
                                        foreach ($applications_by_stage[$stage] as $application):
                                ?>
                                <div class="candidate-card" draggable="true" data-id="<?php echo $application['id']; ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="candidate-name"><strong><?php echo htmlspecialchars($application['candidate_name']); ?></strong></div>
                                        <div class="candidate-menu">
                                            <button class="menu-dots" type="button" data-candidate-id="<?php echo $application['id']; ?>">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu-custom">
                                                <button class="dropdown-item-custom" onclick="editCandidate('<?php echo $application['id']; ?>')">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button class="dropdown-item-custom" onclick="refuseCandidate('<?php echo $application['id']; ?>', '<?php echo htmlspecialchars($application['candidate_name']); ?>')">
                                                    <i class="bi bi-x-circle"></i> Refuse
                                                </button>
                                                <button class="dropdown-item-custom text-danger" onclick="deleteCandidate('<?php echo $application['id']; ?>', '<?php echo htmlspecialchars($application['candidate_name']); ?>')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="candidate-meta">
                                        <span class="position"><?php echo htmlspecialchars($application['job_title']); ?></span>
                                    </div>
                                    <div class="candidate-actions mt-2 d-flex align-items-center justify-content-between">
                                        <button class="btn btn-sm btn-primary view-candidate" data-id="<?php echo $application['id']; ?>">View</button>
                                        <div class="rating-stars ms-2" data-candidate-id="<?php echo $application['id']; ?>">
                                            <?php
                                            $rating = isset($application['rating']) ? (int)$application['rating'] : 0;
                                            for ($i = 1; $i <= 3; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="bi bi-star-fill text-warning" data-rating="' . $i . '"></i>';
                                                } else {
                                                    echo '<i class="bi bi-star text-muted" data-rating="' . $i . '"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <?php 
                                        endforeach;
                                    endif;
                                endif;
                                ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Hidden Refuse Stage -->
                        <div class="kanban-column refuse-stage-hidden" data-stage="Refuse" id="refuseStage">
                            <div class="kanban-column-header">
                                <h6>Refused</h6>
                                <span class="kanban-count">0</span>
                            </div>
                            <div class="kanban-items">
                                <?php 
                                if (isset($applications_by_stage['Refuse'])):
                                    foreach ($applications_by_stage['Refuse'] as $application):
                                ?>
                                <div class="candidate-card" draggable="true" data-id="<?php echo $application['id']; ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="candidate-name"><strong><?php echo htmlspecialchars($application['candidate_name']); ?></strong></div>
                                        <div class="candidate-menu">
                                            <button class="menu-dots" type="button" data-candidate-id="<?php echo $application['id']; ?>">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu-custom">
                                                <button class="dropdown-item-custom" onclick="editCandidate('<?php echo $application['id']; ?>')">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button class="dropdown-item-custom text-danger" onclick="deleteCandidate('<?php echo $application['id']; ?>', '<?php echo htmlspecialchars($application['candidate_name']); ?>')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="candidate-meta">
                                        <span class="position"><?php echo htmlspecialchars($application['job_title']); ?></span>
                                        <?php if (isset($application['refuse_reason']) && $application['refuse_reason']): ?>
                                        <div class="mt-1">
                                            <small class="text-danger">Reason: <?php echo htmlspecialchars($application['refuse_reason']); ?></small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="candidate-actions mt-2 d-flex align-items-center justify-content-between">
                                        <button class="btn btn-sm btn-primary view-candidate" data-id="<?php echo $application['id']; ?>">View</button>
                                        <div class="rating-stars ms-2" data-candidate-id="<?php echo $application['id']; ?>">
                                            <?php
                                            $rating = isset($application['rating']) ? (int)$application['rating'] : 0;
                                            for ($i = 1; $i <= 3; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="bi bi-star-fill text-warning" data-rating="' . $i . '"></i>';
                                                } else {
                                                    echo '<i class="bi bi-star text-muted" data-rating="' . $i . '"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <?php 
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Refuse Toggle Button -->
                    <button class="refuse-toggle-btn" id="refuseToggleBtn" onclick="toggleRefuseStage()">
                        <i class="bi bi-eye-slash me-1"></i> Show Refused
                    </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
<!-- Replace your existing modal section with this -->
<div class="modal fade" id="addCandidateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Candidate</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="candidateForm">
        <div class="modal-body">
          <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
              <div class="mb-3">
                <label class="form-label">Job Position <span class="text-danger">*</span></label>
                <select class="form-control" id="jobPosition" required>
                  <option value="" disabled selected>Select Position</option>
                  <?php
                  $query = "SELECT id, job_title FROM job_positions WHERE status = 'active' ORDER BY job_title";
                  $result = mysqli_query($conn, $query);
                  if ($result && mysqli_num_rows($result) > 0) {
                      while($row = mysqli_fetch_assoc($result)) {
                          echo "<option value='".$row['id']."'>".$row['job_title']."</option>";
                      }
                  }
                  ?>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label">Candidate Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="candidateName" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="candidateEmail" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Rating</label>
                <div class="rating-stars-edit" data-candidate-id="">
                    <i class="bi bi-star text-muted" data-rating="1"></i>
                    <i class="bi bi-star text-muted" data-rating="2"></i>
                    <i class="bi bi-star text-muted" data-rating="3"></i>
                </div>
                <input type="hidden" id="candidateRating" value="0">
              </div>

              <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" id="candidateNotes" rows="3"></textarea>
              </div>
            </div>

            <!-- Right Column - Simple Resume Upload Section -->
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Resume</h6>
                  
                  <!-- Simple Upload Section -->
                  <div class="file-upload-area" id="resumeUploadArea">
                    <i class="bi bi-cloud-arrow-up fs-3"></i>
                    <p class="mt-2">Drag & drop or click to upload resume</p>
                    <input type="file" class="form-control" id="resume" accept=".pdf,.doc,.docx">
                  </div>
                  
                  <div class="mt-3">
                    <small class="text-muted">Supported formats: PDF, DOC, DOCX</small>
                    <small class="text-muted d-block">Max file size: 5MB</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Candidate</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Replace the Edit Candidate Modal resume section -->
<div class="modal fade" id="viewCandidateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Candidate</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editCandidateForm">
        <input type="hidden" id="editCandidateId">
        <div class="modal-body">
          <div class="row">
            <!-- Left Column - Form Fields -->
            <div class="col-md-8">
              <div class="card">
                <div class="card-body">
                  <!-- Job Position Field First -->
                  <div class="mb-3">
                    <label class="form-label">Job Position</label>
                    <select class="form-control" id="editJobPosition" required>
                      <option value="" disabled>Select Position</option>
                      <?php
                      $query = "SELECT id, job_title FROM job_positions WHERE status = 'active' ORDER BY job_title";
                      $result = mysqli_query($conn, $query);
                      
                      if ($result && mysqli_num_rows($result) > 0) {
                          while($row = mysqli_fetch_assoc($result)) {
                              echo "<option value='".$row['id']."'>".$row['job_title']."</option>";
                          }
                      }
                      ?>
                    </select>
                  </div>

                  <!-- Name Field -->
                  <div class="mb-3">
                    <label class="form-label">Candidate Name</label>
                    <input type="text" class="form-control" id="editCandidateName" required>
                  </div>

                  <!-- Email Field -->
                  <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="editCandidateEmail" required>
                  </div>

                  <!-- Rating Field -->
                  <div class="mb-3">
                    <label class="form-label">Rating</label>
                    <div class="rating-stars-edit" data-candidate-id="">
                        <i class="bi bi-star text-muted" data-rating="1"></i>
                        <i class="bi bi-star text-muted" data-rating="2"></i>
                        <i class="bi bi-star text-muted" data-rating="3"></i>
                    </div>
                    <input type="hidden" id="editCandidateRating" value="0">
                  </div>

                  <!-- Current Stage -->
                  <div class="mb-3">
                    <label class="form-label">Current Stage</label>
                    <input type="text" class="form-control" id="editStage" readonly>
                  </div>
                </div>
              </div>
            </div>
            <!-- END Left Column -->

            <!-- Right Column - Advanced Resume Section -->
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Resume</h6>
                  
                  <!-- Current Resume Section -->
                  <div id="currentResumeSection" class="mb-3" style="display: none;">
                    <label class="form-label text-muted">Current Resume</label>
                    <div id="currentResume" class="mb-2">
                      <!-- Will be populated dynamically -->
                    </div>
                    <!-- Buttons section -->
                    <div id="resumeActions" class="d-flex gap-2 mb-3">
                      <!-- Buttons will be populated dynamically -->
                    </div>
                  </div>
                  
                  <!-- Upload New Resume Section -->
                  <div class="file-upload-area" id="editResumeUploadArea">
                    <i class="bi bi-cloud-arrow-up fs-3"></i>
                    <p class="mt-2">Drag & drop or click to upload new resume</p>
                    <input type="file" class="form-control" id="editResume" accept=".pdf,.doc,.docx">
                  </div>
                  
                  <div class="mt-3">
                    <small class="text-muted">Supported formats: PDF, DOC, DOCX</small>
                    <small class="text-muted d-block">Max file size: 5MB</small>
                    <small class="text-muted d-block">Uploading a new file will replace the existing resume</small>
                  </div>
                </div>
              </div>
            </div>
            <!-- END Right Column -->
          </div>
          <!-- END Row -->

          <!-- Notes Section -->
          <div class="row mt-3">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Additional Information</h6>
                  <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" id="editCandidateNotes" rows="4" 
                      style="min-height: 120px; resize: vertical;" 
                      placeholder="Add any additional notes about the candidate..."></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Refuse Candidate Modal -->
<div class="modal fade refuse-modal" id="refuseCandidateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-x-circle me-2"></i>
                    Refuse Candidate
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="refuseCandidateForm">
                <div class="modal-body">
                    <input type="hidden" id="refuseCandidateId">
                    
                    <div class="mb-3">
                        <p>Are you sure you want to refuse <strong id="refuseCandidateName"></strong>?</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for refusal:</label>
                        <div class="refuse-reason-tags">
                            <span class="reason-tag" data-reason="Does not fit the job requirements">Does not fit requirements</span>
                            <span class="reason-tag" data-reason="Refused by applicant: salary">Salary expectations</span>
                            <span class="reason-tag" data-reason="Refused by applicant: job fit">Job fit issues</span>
                            <span class="reason-tag" data-reason="Job already fulfilled">Position filled</span>
                            <span class="reason-tag" data-reason="Duplicate application">Duplicate</span>
                            <span class="reason-tag" data-reason="Spam application">Spam</span>
                        </div>
                        
                        <textarea class="form-control" id="refuseReason" rows="3" 
                                  placeholder="Enter custom reason or select from above..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sendRefuseEmail" checked>
                            <label class="form-check-label" for="sendRefuseEmail">
                                Send notification email to candidate
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-refuse">
                        <i class="bi bi-x-circle me-1"></i>
                        Refuse Candidate
                    </button>
                </div>
            </form>
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
// Global variables
let draggedCard = null;

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing application...');
    
    // Initialize sidebar state
    document.body.classList.add('toggle-sidebar');
    
    // Initialize all components
    initializeKanban();
    initializeModals();
    initializeFormHandlers();
    initializeDragAndDrop();
    initializeRatingSystem();
    
    console.log('Application initialized successfully');
});

// ===== KANBAN BOARD FUNCTIONALITY =====
function initializeKanban() {
    console.log('Initializing kanban board...');
    
    const cards = document.querySelectorAll('.candidate-card');
    const columns = document.querySelectorAll('.kanban-column');
    
    console.log(`Found ${cards.length} cards and ${columns.length} columns`);

    // Add drag listeners to existing cards
    cards.forEach((card, index) => {
        addDragListeners(card);
        console.log(`Added drag listeners to card ${index + 1}`);
    });

    // Add drop listeners to columns
    columns.forEach((column, index) => {
        addDropListeners(column);
        console.log(`Added drop listeners to column ${index + 1}`);
    });

    // Update initial counts
    updateColumnCounts();
    
    // Add click handlers for existing buttons
    addButtonHandlers();
}

function addDragListeners(card) {
    card.addEventListener('dragstart', handleDragStart);
    card.addEventListener('dragend', handleDragEnd);
}

function addDropListeners(column) {
    column.addEventListener('dragenter', handleDragEnter);
    column.addEventListener('dragover', handleDragOver);
    column.addEventListener('dragleave', handleDragLeave);
    column.addEventListener('drop', handleDrop);
}

function addButtonHandlers() {
    // Add candidate button - Fixed event listener
    const addCandidateBtn = document.getElementById('addCandidateBtn');
    if (addCandidateBtn) {
        console.log('Adding click listener to add candidate button');
        addCandidateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Add candidate button clicked');
            
            // Reset form first
            resetAddCandidateForm();
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('addCandidateModal'));
            modal.show();
        });
    } else {
        console.error('Add candidate button not found!');
    }

    // Edit candidate buttons - Use event delegation
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-candidate') || e.target.closest('.view-candidate')) {
            e.preventDefault();
            e.stopPropagation();
            const button = e.target.classList.contains('view-candidate') ? e.target : e.target.closest('.view-candidate');
            const candidateId = button.dataset.id;
            console.log('Edit candidate button clicked for ID:', candidateId);
            openViewCandidateModal(candidateId);
        }
    });
}

// ===== DRAG AND DROP HANDLERS =====
function handleDragStart(e) {
    draggedCard = e.target;
    e.target.classList.add('dragging');
    e.dataTransfer.setData('text/plain', e.target.dataset.id);
    console.log('Drag started for card:', e.target.dataset.id);
}

function handleDragEnd(e) {
    draggedCard = null;
    e.target.classList.remove('dragging');
    document.querySelectorAll('.kanban-column').forEach(col => {
        col.classList.remove('drag-over');
    });
    console.log('Drag ended');
}

function handleDragEnter(e) {
    e.preventDefault();
    const column = e.target.closest('.kanban-column');
    if (column && draggedCard) {
        column.classList.add('drag-over');
    }
}

function handleDragOver(e) {
    e.preventDefault();
}

function handleDragLeave(e) {
    const column = e.target.closest('.kanban-column');
    if (column && !column.contains(e.relatedTarget)) {
        column.classList.remove('drag-over');
    }
}

function handleDrop(e) {
    e.preventDefault();
    const column = e.target.closest('.kanban-column');
    
    if (column && draggedCard) {
        column.classList.remove('drag-over');
        
        const cardId = e.dataTransfer.getData('text/plain');
        const newStage = column.dataset.stage;
        
        // Move card to new column
        const items = column.querySelector('.kanban-items');
        items.appendChild(draggedCard);
        
        // Update counts and visual effects
        updateColumnCounts();
        handleHiredStatus(draggedCard, newStage);
        
        // Update stage in database
        updateCandidateStage(cardId, newStage);
        
        console.log(`Card ${cardId} moved to ${newStage}`);
    }
}

function updateCandidateStage(candidateId, stage) {
    fetch('update_candidate_stage.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            candidate_id: candidateId,
            stage: stage
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Error updating stage:', data.message);
            alert('Error updating candidate stage');
        } else {
            console.log('Stage updated successfully');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating candidate stage');
    });
}

function handleHiredStatus(card, stage) {
    if (stage === 'Contract Signed') {
        if (!card.querySelector('.hired-ribbon')) {
            const ribbon = document.createElement('div');
            ribbon.className = 'hired-ribbon';
            ribbon.textContent = 'HIRED';
            card.appendChild(ribbon);
        }
        card.classList.add('hired');
    } else {
        const ribbon = card.querySelector('.hired-ribbon');
        if (ribbon) ribbon.remove();
        card.classList.remove('hired');
    }
}

function updateColumnCounts() {
    document.querySelectorAll('.kanban-column').forEach(column => {
        const count = column.querySelectorAll('.candidate-card').length;
        const countElement = column.querySelector('.kanban-count');
        if (countElement) {
            countElement.textContent = count;
        }
    });
}

// ===== MODAL FUNCTIONALITY =====
function initializeModals() {
    console.log('Initializing modals...');
    
    const editModal = document.getElementById('viewCandidateModal');
    const addModal = document.getElementById('addCandidateModal');
    
    // Clean up modals when they close
    [editModal, addModal].forEach(modal => {
        if (modal) {
            modal.addEventListener('hidden.bs.modal', cleanupModal);
            modal.addEventListener('hide.bs.modal', function() {
                setTimeout(cleanupModal, 100);
            });
        }
    });
}

function cleanupModal() {
    // Remove any remaining backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Clean up body classes and styles
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

// Force clean modals function
function forceCleanModals() {
    cleanupModal();
    
    // Hide all modals
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    });
}

// ===== FORM HANDLERS =====
function initializeFormHandlers() {
    console.log('Initializing form handlers...');
    
    // Add candidate form
    const addForm = document.getElementById('candidateForm');
    if (addForm) {
        addForm.addEventListener('submit', handleAddCandidateSubmit);
        console.log('Add candidate form handler attached');
    } else {
        console.error('Add candidate form not found!');
    }
    
    // Edit candidate form
    const editForm = document.getElementById('editCandidateForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditCandidateSubmit);
        console.log('Edit candidate form handler attached');
    } else {
        console.error('Edit candidate form not found!');
    }
}

function resetAddCandidateForm() {
    console.log('Resetting add candidate form...');
    
    // Reset form fields
    document.getElementById('candidateName').value = '';
    document.getElementById('candidateEmail').value = '';
    document.getElementById('jobPosition').value = '';
    document.getElementById('candidateNotes').value = '';
    document.getElementById('candidateRating').value = '0';
    
    // Reset rating stars
    const stars = document.querySelectorAll('#addCandidateModal .rating-stars-edit i');
    stars.forEach(star => {
        star.classList.remove('bi-star-fill', 'text-warning');
        star.classList.add('bi-star', 'text-muted');
    });
    
    // Reset file upload area
    const uploadArea = document.getElementById('resumeUploadArea');
    if (uploadArea) {
        uploadArea.innerHTML = `
            <i class="bi bi-cloud-arrow-up fs-3"></i>
            <p class="mt-2">Drag & drop or click to upload resume</p>
            <input type="file" class="form-control" id="resume" accept=".pdf,.doc,.docx">
        `;
        setupFileUpload('resumeUploadArea', 'resume');
    }
}

function handleAddCandidateSubmit(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('Adding new candidate...');
    
    // Create FormData for file uploads
    const formData = new FormData();
    formData.append('candidate_name', document.getElementById('candidateName').value);
    formData.append('email', document.getElementById('candidateEmail').value);
    formData.append('position_id', document.getElementById('jobPosition').value);
    formData.append('rating', document.getElementById('candidateRating').value);
    formData.append('stage', 'New');
    formData.append('notes', document.getElementById('candidateNotes').value);
    
    // Add resume file if selected
    const resumeFile = document.getElementById('resume').files[0];
    if (resumeFile) {
        formData.append('resume', resumeFile);
        console.log('Resume file attached:', resumeFile.name);
    }

    // Validate required fields
    if (!formData.get('candidate_name') || !formData.get('email') || !formData.get('position_id')) {
        alert('Please fill in all required fields');
        return;
    }

    // Submit form
    fetch('save_candidate.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addCandidateModal'));
            if (modal) {
                modal.hide();
            }
            
            // Show success message
            let message = 'Candidate added successfully!';
            if (data.candidate && data.candidate.resume_uploaded) {
                message += ' Resume has been uploaded.';
            }
            alert(message);
            
            // Refresh page to show new candidate
            location.reload();
        } else {
            alert('Error saving candidate: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving candidate: ' + error.message);
    });
}

function handleEditCandidateSubmit(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('Updating candidate...');
    
    // Create FormData for file uploads
    const formData = new FormData();
    formData.append('candidate_id', document.getElementById('editCandidateId').value);
    formData.append('candidate_name', document.getElementById('editCandidateName').value);
    formData.append('email', document.getElementById('editCandidateEmail').value);
    formData.append('position_id', document.getElementById('editJobPosition').value);
    formData.append('notes', document.getElementById('editCandidateNotes').value);
    formData.append('rating', document.getElementById('editCandidateRating').value);
    
    // Add resume file if selected
    const resumeFile = document.getElementById('editResume').files[0];
    if (resumeFile) {
        formData.append('resume', resumeFile);
        console.log('Resume file attached:', resumeFile.name);
    }

    // Validate required fields
    if (!formData.get('candidate_id') || !formData.get('candidate_name') || !formData.get('email') || !formData.get('position_id')) {
        alert('Please fill in all required fields');
        return;
    }

    console.log('Submitting form data...');
    
    // Submit form
    fetch('update_candidate.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('viewCandidateModal'));
            if (modal) {
                modal.hide();
            }
            
            // Clean up modal
            setTimeout(cleanupModal, 300);
            
            // Show success message
            let message = 'Candidate updated successfully!';
            if (data.resume_uploaded) {
                message += ' Resume has been uploaded.';
            }
            alert(message);
            
            // Refresh page to show updates
            location.reload();
        } else {
            alert('Error updating candidate: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating candidate: ' + error.message);
    });
}

// ===== DRAG AND DROP FILE UPLOAD =====
function initializeDragAndDrop() {
    console.log('Initializing drag and drop file upload...');
    
    // Initialize for both modals
    setupFileUpload('resumeUploadArea', 'resume'); // For add candidate modal
    setupFileUpload('editResumeUploadArea', 'editResume'); // For edit candidate modal
}

function setupFileUpload(uploadAreaId, inputId) {
    const uploadArea = document.getElementById(uploadAreaId);
    const fileInput = document.getElementById(inputId);
    
    if (!uploadArea || !fileInput) {
        console.log(`Upload area ${uploadAreaId} or input ${inputId} not found`);
        return;
    }

    console.log(`Setting up file upload for ${uploadAreaId}`);

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    // Highlight drop area when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => highlight(uploadArea), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => unhighlight(uploadArea), false);
    });

    // Handle dropped files
    uploadArea.addEventListener('drop', (e) => handleFileDrop(e, uploadArea, fileInput), false);

    // Handle click to upload
    uploadArea.addEventListener('click', (e) => {
        // Prevent clicking on the hidden file input
        if (e.target.type !== 'file') {
            fileInput.click();
        }
    });

    // Handle file input change
    fileInput.addEventListener('change', function(e) {
        console.log('File input changed:', e.target.files);
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files, uploadArea);
        }
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight(area) {
        area.classList.add('drag-over');
    }

    function unhighlight(area) {
        area.classList.remove('drag-over');
    }

    function handleFileDrop(e, area, input) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        // Create a new FileList and assign it to the input
        const fileArray = Array.from(files);
        const dataTransfer = new DataTransfer();
        fileArray.forEach(file => dataTransfer.items.add(file));
        input.files = dataTransfer.files;
        
        handleFileSelect(files, area);
    }

    function handleFileSelect(files, area) {
        console.log('Handling file selection:', files);
        if (files.length > 0) {
            const file = files[0];
            const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            
            console.log('File type:', file.type);
            console.log('File name:', file.name);
            
            if (allowedTypes.includes(file.type)) {
                // Update UI to show file is selected
                area.innerHTML = `
                    <i class="bi bi-file-earmark-check text-success fs-3"></i>
                    <p class="mt-2 text-success">File selected: ${file.name}</p>
                    <small class="text-muted">Click to change file</small>
                    <input type="file" class="form-control" id="${inputId}" accept=".pdf,.doc,.docx" style="position: absolute; left: -9999px;">
                `;
                
                // Re-setup the file input after updating the UI
                const newInput = area.querySelector('input[type="file"]');
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                newInput.files = dataTransfer.files;
                
                // Re-add event listeners
                newInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        handleFileSelect(e.target.files, area);
                    }
                });
                
                area.addEventListener('click', (e) => {
                    if (e.target.type !== 'file') {
                        newInput.click();
                    }
                });
                
                console.log('Resume file selected:', file.name);
            } else {
                alert('Invalid file type. Please upload PDF, DOC, or DOCX files only.');
                const input = document.getElementById(inputId);
                if (input) input.value = '';
                
                // Reset UI
                area.innerHTML = `
                    <i class="bi bi-cloud-arrow-up fs-3"></i>
                    <p class="mt-2">Drag & drop or click to upload resume</p>
                    <input type="file" class="form-control" id="${inputId}" accept=".pdf,.doc,.docx" style="position: absolute; left: -9999px;">
                `;
                
                // Re-setup file upload
                setupFileUpload(uploadAreaId, inputId);
            }
        }
    }
}

// Reinitialize file upload for edit modal when opened
function reinitializeEditFileUpload() {
    const editUploadArea = document.getElementById('editResumeUploadArea');
    if (editUploadArea) {
        editUploadArea.innerHTML = `
            <i class="bi bi-cloud-arrow-up fs-3"></i>
            <p class="mt-2">Drag & drop or click to upload new resume</p>
            <input type="file" class="form-control" id="editResume" accept=".pdf,.doc,.docx" style="position: absolute; left: -9999px;">
        `;
        setupFileUpload('editResumeUploadArea', 'editResume');
    }
}

// ===== RATING SYSTEM =====
function initializeRatingSystem() {
    console.log('Initializing rating system...');
    
    // Single event listener for all star ratings
    document.addEventListener('click', function(e) {
        // Handle kanban board star rating
        if (e.target.closest('.rating-stars i') && !e.target.closest('.rating-stars-edit')) {
            const star = e.target;
            const ratingContainer = star.closest('.rating-stars');
            const candidateId = ratingContainer.dataset.candidateId;
            const newRating = parseInt(star.dataset.rating);
            
            // Update visual display
            updateKanbanStars(ratingContainer, newRating);
            
            // Update database
            updateCandidateRating(candidateId, newRating);
        }
        
        // Handle edit modal star rating (both add and edit modals)
        if (e.target.closest('.rating-stars-edit i')) {
            const star = e.target;
            const newRating = parseInt(star.dataset.rating);
            const modal = star.closest('.modal');
            
            // Determine which modal we're in
            if (modal && modal.id === 'addCandidateModal') {
                // Add candidate modal
                document.getElementById('candidateRating').value = newRating;
                updateAddModalStars(newRating);
            } else if (modal && modal.id === 'viewCandidateModal') {
                // Edit candidate modal
                document.getElementById('editCandidateRating').value = newRating;
                updateEditModalStars(newRating);
            }
            
            console.log('Rating updated to:', newRating);
        }
    });
}

function updateKanbanStars(container, rating) {
    const stars = container.querySelectorAll('i');
    stars.forEach(star => {
        const starRating = parseInt(star.dataset.rating);
        if (starRating <= rating) {
            star.classList.remove('bi-star', 'text-muted');
            star.classList.add('bi-star-fill', 'text-warning');
        } else {
            star.classList.remove('bi-star-fill', 'text-warning');
            star.classList.add('bi-star', 'text-muted');
        }
    });
}

function updateAddModalStars(rating) {
    const stars = document.querySelectorAll('#addCandidateModal .rating-stars-edit i');
    stars.forEach(star => {
        const starRating = parseInt(star.dataset.rating);
        if (starRating <= rating) {
            star.classList.remove('bi-star', 'text-muted');
            star.classList.add('bi-star-fill', 'text-warning');
        } else {
            star.classList.remove('bi-star-fill', 'text-warning');
            star.classList.add('bi-star', 'text-muted');
        }
    });
}

function updateEditModalStars(rating) {
    const stars = document.querySelectorAll('#viewCandidateModal .rating-stars-edit i');
    stars.forEach(star => {
        const starRating = parseInt(star.dataset.rating);
        if (starRating <= rating) {
            star.classList.remove('bi-star', 'text-muted');
            star.classList.add('bi-star-fill', 'text-warning');
        } else {
            star.classList.remove('bi-star-fill', 'text-warning');
            star.classList.add('bi-star', 'text-muted');
        }
    });
}

function updateCandidateRating(candidateId, rating) {
    fetch('update_candidate_rating.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            candidate_id: candidateId,
            rating: rating
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Error updating rating:', data.message);
            alert('Error updating rating');
        } else {
            console.log('Rating updated successfully');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating rating');
    });
}

// ===== CANDIDATE MODAL FUNCTIONS =====
function openViewCandidateModal(candidateId) {
    console.log('Opening modal for candidate:', candidateId);
    
    fetch('get_candidate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ candidate_id: candidateId })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Candidate data received:', data);
        
        if (data.success) {
            populateEditModal(data);
            
            const modal = new bootstrap.Modal(document.getElementById('viewCandidateModal'));
            modal.show();
        } else {
            alert('Error loading candidate details: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading candidate details');
    });
}

function populateEditModal(data) {
    // Populate form fields
    document.getElementById('editCandidateId').value = data.candidate.id;
    document.getElementById('editCandidateName').value = data.candidate.candidate_name;
    document.getElementById('editCandidateEmail').value = data.candidate.email;
    document.getElementById('editJobPosition').value = data.candidate.position_id;
    document.getElementById('editStage').value = data.candidate.stage;
    document.getElementById('editCandidateNotes').value = data.candidate.notes || '';
    
    // Set rating
    const rating = parseInt(data.candidate.rating) || 0;
    document.getElementById('editCandidateRating').value = rating;
    updateEditModalStars(rating);
    
    // Set candidate ID for rating stars
    const ratingContainer = document.querySelector('#viewCandidateModal .rating-stars-edit');
    if (ratingContainer) {
        ratingContainer.dataset.candidateId = data.candidate.id;
    }
    
    // Handle resume display
    displayCurrentResume(data.resume);
    
    // Reinitialize file upload for edit modal
    reinitializeEditFileUpload();
}

function displayCurrentResume(resumeData) {
    const currentResumeSection = document.getElementById('currentResumeSection');
    const currentResumeDiv = document.getElementById('currentResume');
    const resumeActionsDiv = document.getElementById('resumeActions');
    
    if (resumeData && resumeData.exists && resumeData.filename) {
        console.log('Displaying current resume:', resumeData.filename);
        
        // Show current resume section
        currentResumeSection.style.display = 'block';
        
        // Determine file icon
        const fileExtension = resumeData.filename.toLowerCase().split('.').pop();
        let fileIcon = 'bi-file-earmark';
        let iconColor = 'text-secondary';
        
        switch(fileExtension) {
            case 'pdf':
                fileIcon = 'bi-file-earmark-pdf';
                iconColor = 'text-danger';
                break;
            case 'doc':
            case 'docx':
                fileIcon = 'bi-file-earmark-word';
                iconColor = 'text-primary';
                break;
        }
        
        // Create resume display
        currentResumeDiv.innerHTML = `
            <div class="border rounded p-3 bg-light">
                <div class="d-flex align-items-start mb-3">
                    <i class="bi ${fileIcon} ${iconColor} me-2 fs-4 flex-shrink-0"></i>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold mb-1" style="word-break: break-all; line-height: 1.3;">
                            ${resumeData.filename}
                        </div>
                        <small class="text-muted">Current resume file</small>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary btn-sm" onclick="viewResume('${resumeData.url}')" title="View Resume">
                        <i class="bi bi-eye me-1"></i> View Resume
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="downloadResume('${resumeData.url}', '${resumeData.filename}')" title="Download Resume">
                        <i class="bi bi-download me-1"></i> Download Resume
                    </button>
                </div>
            </div>
        `;
        
        // Clear separate actions div
        resumeActionsDiv.innerHTML = '';
    } else {
        console.log('No resume found');
        // Hide current resume section
        currentResumeSection.style.display = 'none';
        currentResumeDiv.innerHTML = '';
        resumeActionsDiv.innerHTML = '';
    }
}

// ===== RESUME FUNCTIONS =====
function viewResume(resumeUrl) {
    console.log('Viewing resume:', resumeUrl);
    window.open(resumeUrl, '_blank');
}

function downloadResume(resumeUrl, filename) {
    console.log('Downloading resume:', resumeUrl, filename);
    const link = document.createElement('a');
    link.href = resumeUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// ===== UTILITY FUNCTIONS =====
function createCandidateCard(candidate) {
    const card = document.createElement('div');
    card.className = 'candidate-card';
    card.draggable = true;
    card.dataset.id = candidate.id;
    
    card.innerHTML = `
        <div class="candidate-name"><strong>${candidate.candidate_name}</strong></div>
        <div class="candidate-meta">
            <span class="position">${candidate.job_title}</span>
        </div>
        <div class="candidate-actions mt-2 d-flex align-items-center justify-content-between">
            <button class="btn btn-sm btn-primary view-candidate" data-id="${candidate.id}">Edit</button>
            <div class="rating-stars ms-2" data-candidate-id="${candidate.id}">
                ${generateStarRating(candidate.rating || 0)}
            </div>
        </div>
    `;
    
    // Add drag event listeners
    addDragListeners(card);
    
    return card;
}

function generateStarRating(rating) {
    let starsHtml = '';
    for (let i = 1; i <= 3; i++) {
        if (i <= rating) {
            starsHtml += `<i class="bi bi-star-fill text-warning" data-rating="${i}"></i>`;
        } else {
            starsHtml += `<i class="bi bi-star text-muted" data-rating="${i}"></i>`;
        }
    }
    return starsHtml;
}

// ===== EVENT LISTENERS =====
// Escape key handler
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        setTimeout(forceCleanModals, 100);
    }
});

// Close button handlers
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-close') || 
        e.target.closest('.btn-close') ||
        (e.target.classList.contains('btn') && e.target.textContent.trim() === 'Cancel')) {
        setTimeout(forceCleanModals, 100);
    }
});

function initializeDropdownMenus() {
    console.log('Initializing dropdown menus...');
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.candidate-menu')) {
            closeAllDropdowns();
        }
    });
    
    // Handle menu button clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.menu-dots')) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = e.target.closest('.menu-dots');
            const menu = button.nextElementSibling;
            
            // Close other dropdowns
            closeAllDropdowns();
            
            // Toggle current dropdown
            menu.classList.toggle('show');
        }
    });
}

function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-menu-custom').forEach(menu => {
        menu.classList.remove('show');
    });
}

// Edit candidate function
function editCandidate(candidateId) {
    closeAllDropdowns();
    openViewCandidateModal(candidateId);
}

// Refuse candidate function
function refuseCandidate(candidateId, candidateName) {
    closeAllDropdowns();
    
    document.getElementById('refuseCandidateId').value = candidateId;
    document.getElementById('refuseCandidateName').textContent = candidateName;
    document.getElementById('refuseReason').value = '';
    
    // Reset reason tags
    document.querySelectorAll('.reason-tag').forEach(tag => {
        tag.classList.remove('selected');
    });
    
    const modal = new bootstrap.Modal(document.getElementById('refuseCandidateModal'));
    modal.show();
}

// Delete candidate function
function deleteCandidate(candidateId, candidateName) {
    closeAllDropdowns();
    
    if (confirm(`Are you sure you want to permanently delete ${candidateName}? This action cannot be undone.`)) {
        fetch('delete_candidate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                candidate_id: candidateId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Candidate deleted successfully');
                location.reload();
            } else {
                alert('Error deleting candidate: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting candidate');
        });
    }
}

// Toggle refuse stage visibility
function toggleRefuseStage() {
    const refuseStage = document.getElementById('refuseStage');
    const toggleBtn = document.getElementById('refuseToggleBtn');
    
    if (refuseStage.classList.contains('refuse-stage-hidden')) {
        // Show refuse stage
        refuseStage.classList.remove('refuse-stage-hidden');
        toggleBtn.innerHTML = '<i class="bi bi-eye me-1"></i> Hide Refused';
        toggleBtn.classList.add('active');
    } else {
        // Hide refuse stage
        refuseStage.classList.add('refuse-stage-hidden');
        toggleBtn.innerHTML = '<i class="bi bi-eye-slash me-1"></i> Show Refused';
        toggleBtn.classList.remove('active');
    }
    
    // Update column counts
    updateColumnCounts();
}

// Initialize refuse reason tags
function initializeRefuseReasonTags() {
    document.querySelectorAll('.reason-tag').forEach(tag => {
        tag.addEventListener('click', function() {
            // Toggle selection
            this.classList.toggle('selected');
            
            // Update textarea
            const selectedReasons = Array.from(document.querySelectorAll('.reason-tag.selected'))
                .map(tag => tag.dataset.reason);
            
            document.getElementById('refuseReason').value = selectedReasons.join('; ');
        });
    });
}

// Handle refuse form submission
function initializeRefuseForm() {
    const refuseForm = document.getElementById('refuseCandidateForm');
    if (refuseForm) {
        refuseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const candidateId = document.getElementById('refuseCandidateId').value;
            const reason = document.getElementById('refuseReason').value;
            const sendEmail = document.getElementById('sendRefuseEmail').checked;
            
            if (!reason.trim()) {
                alert('Please provide a reason for refusal');
                return;
            }
            
            const formData = new FormData();
            formData.append('candidate_id', candidateId);
            formData.append('refuse_reason', reason);
            formData.append('send_email', sendEmail ? '1' : '0');
            
            fetch('refuse_candidate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('refuseCandidateModal'));
                    modal.hide();
                    
                    let message = 'Candidate refused successfully';
                    if (sendEmail) {
                        message += ' and notification email sent';
                    }
                    alert(message);
                    
                    location.reload();
                } else {
                    alert('Error refusing candidate: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error refusing candidate');
            });
        });
    }
}

// Update the main initialization function
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing application...');
    
    // Initialize sidebar state
    document.body.classList.add('toggle-sidebar');
    
    // Initialize all components
    initializeKanban();
    initializeModals();
    initializeFormHandlers();
    initializeDragAndDrop();
    initializeRatingSystem();
    initializeDropdownMenus(); // Add this
    initializeRefuseReasonTags(); // Add this
    initializeRefuseForm(); // Add this
    
    console.log('Application initialized successfully');
});

</script>
</body>

</html>