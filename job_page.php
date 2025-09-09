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
                                        <div class="candidate-name"><?php echo htmlspecialchars($application['candidate_name']); ?></div>
                                        <div class="candidate-meta">
                                            <span class="position"><?php echo htmlspecialchars($application['job_title']); ?></span>
                                        </div>
                                        <div class="candidate-actions mt-2">
                                            <button class="btn btn-sm btn-primary view-candidate" data-id="<?php echo $application['id']; ?>">View</button>
                                            <button class="btn btn-sm btn-secondary message-candidate" data-id="<?php echo $application['id']; ?>">Message</button>
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
                        </div>
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
        <h5 class="modal-title">New Candidate</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="addCandidateForm">
        <div class="modal-body">
          <div class="row">
            <!-- Left Column - Form Fields (Now Larger) -->
            <div class="col-md-8">
              <div class="card">
                <div class="card-body">
                  <!-- Name Field -->
                  <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" id="candidateName" placeholder="e.g. John Doe" required>
                  </div>

                  <!-- Email Field -->
                  <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="candidateEmail" placeholder="e.g. john.doe@example.com" required>
                  </div>

                  <!-- Phone Field -->
                  <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="candidatePhone" required>
                  </div>

                  <!-- Job Position Field with PHP -->
                  <div class="mb-3">
                    <label class="form-label">Job Position</label>
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

                  <!-- Tags -->
                  <div class="mb-3">
                    <label class="form-label">Tags</label>
                    <input type="text" class="form-control" id="tags" placeholder="e.g. Trainee">
                  </div>

                  <!-- Recruiter Info -->
                  <div class="mb-3">
                    <label class="form-label">Recruiter</label>
                    <div class="d-flex align-items-center">
                      <img src="assets/img/profile-img.jpg" class="rounded-circle me-2" width="32" height="32">
                      <span><?php echo $_SESSION['username']; ?></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column - Files Section (Now Smaller) -->
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Files</h6>
                  <div class="border rounded p-3 text-center">
                    <i class="bi bi-cloud-arrow-up fs-3"></i>
                    <p class="mt-2">Drag & drop or click to attach files</p>
                    <input type="file" class="form-control" id="resume" multiple>
                  </div>
                  <div class="mt-3">
                    <small class="text-muted">Supported formats: PDF, DOC, DOCX</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes Tabs (Now with Larger Textbox) -->
          <div class="row mt-3">
            <div class="col-12">
              <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" data-bs-toggle="tab" href="#notesTab">Note</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" data-bs-toggle="tab" href="#detailsTab">Details</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" data-bs-toggle="tab" href="#skillsTab">Skills</a>
                </li>
              </ul>
              <div class="tab-content p-3 border border-top-0">
                <div class="tab-pane fade show active" id="notesTab">
                  <textarea class="form-control border-0" id="candidateNotes" rows="6" 
                    style="min-height: 150px; resize: vertical;" 
                    placeholder="Add private notes about this applicant..."></textarea>
                </div>
                <div class="tab-pane fade" id="detailsTab">
                  <textarea class="form-control border-0" rows="6" 
                    style="min-height: 150px; resize: vertical;"
                    placeholder="Add additional candidate details here..."></textarea>
                </div>
                <div class="tab-pane fade" id="skillsTab">
                  <textarea class="form-control border-0" rows="6" 
                    style="min-height: 150px; resize: vertical;"
                    placeholder="List candidate skills and qualifications here..."></textarea>
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

<!-- Add this just before the closing </main> tag in job_page.php -->

<!-- View/Edit Candidate Modal -->
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
                  <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" id="editCandidateName" required>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="editCandidateEmail" required>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="editCandidatePhone" required>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Job Position</label>
                    <select class="form-control" id="editJobPosition" required>
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
                    <label class="form-label">Tags</label>
                    <input type="text" class="form-control" id="editTags">
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Current Stage</label>
                    <input type="text" class="form-control" id="editStage" readonly>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column - Files Section -->
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Files</h6>
                  <div class="border rounded p-3 text-center">
                    <i class="bi bi-cloud-arrow-up fs-3"></i>
                    <p class="mt-2">Drag & drop or click to attach files</p>
                    <input type="file" class="form-control" id="editResume" multiple>
                  </div>
                  <div class="mt-3">
                    <small class="text-muted">Supported formats: PDF, DOC, DOCX</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes Tabs -->
          <div class="row mt-3">
            <div class="col-12">
              <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" data-bs-toggle="tab" href="#editNotesTab">Notes</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" data-bs-toggle="tab" href="#editDetailsTab">Details</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" data-bs-toggle="tab" href="#editSkillsTab">Skills</a>
                </li>
              </ul>
              <div class="tab-content p-3 border border-top-0">
                <div class="tab-pane fade show active" id="editNotesTab">
                  <textarea class="form-control border-0" id="editCandidateNotes" rows="6" 
                    style="min-height: 150px; resize: vertical;"></textarea>
                </div>
                <div class="tab-pane fade" id="editDetailsTab">
                  <textarea class="form-control border-0" id="editDetailsNotes" rows="6" 
                    style="min-height: 150px; resize: vertical;"></textarea>
                </div>
                <div class="tab-pane fade" id="editSkillsTab">
                  <textarea class="form-control border-0" id="editSkillsNotes" rows="6" 
                    style="min-height: 150px; resize: vertical;"></textarea>
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

    document.getElementById('addCandidateBtn').addEventListener('click', function() {
    const modal = new bootstrap.Modal(document.getElementById('addCandidateModal'));
    modal.show();
});
// Define the function globally first, before DOMContentLoaded
function showAddCandidateForm() {
    console.log('Opening modal...');
    const modal = new bootstrap.Modal(document.getElementById('addCandidateModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    
    // Initialize sidebar state
    document.body.classList.add('toggle-sidebar');

    // Rest of your existing code...
    initializeKanban();
    
    // Form submission handler
    const addCandidateForm = document.getElementById('addCandidateForm');
    if (addCandidateForm) {
        addCandidateForm.addEventListener('submit', handleCandidateFormSubmit);
    } else {
        console.log('Form not found!');
    }
});

    function initializeKanban() {
        console.log('Initializing kanban...');
        const cards = document.querySelectorAll('.candidate-card');
        const columns = document.querySelectorAll('.kanban-column');
        
        console.log('Found cards:', cards.length);
        console.log('Found columns:', columns.length);

        // Add drag listeners to existing cards
        cards.forEach((card, index) => {
            addDragListeners(card);
            console.log('Added drag listeners to card', index);
        });

        // Add drop listeners to columns
        columns.forEach((column, index) => {
            column.addEventListener('dragenter', handleDragEnter);
            column.addEventListener('dragover', handleDragOver);
            column.addEventListener('dragleave', handleDragLeave);
            column.addEventListener('drop', handleDrop);
            console.log('Added drop listeners to column', index);
        });

        // Update initial counts
        updateColumnCounts();
    }

    function addDragListeners(card) {
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    }

    function handleDragStart(e) {
        e.target.classList.add('dragging');
        e.dataTransfer.setData('text/plain', e.target.dataset.id);
        console.log('Drag started for card:', e.target.dataset.id);
    }

    function handleDragEnd(e) {
        e.target.classList.remove('dragging');
        document.querySelectorAll('.kanban-column').forEach(col => {
            col.classList.remove('drag-over');
        });
        console.log('Drag ended');
    }

    function handleDragEnter(e) {
        e.preventDefault();
        const column = e.target.closest('.kanban-column');
        if (column) {
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
        if (column) {
            column.classList.remove('drag-over');
            
            const cardId = e.dataTransfer.getData('text/plain');
            const card = document.querySelector(`[data-id="${cardId}"]`);
            
            if (card && column) {
                const items = column.querySelector('.kanban-items');
                items.appendChild(card);
                updateColumnCounts();
                handleHiredStatus(card, column.dataset.stage);
                console.log('Card dropped in column:', column.dataset.stage);
            }
        }
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

    function handleCandidateFormSubmit(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        const jobSelect = document.getElementById('jobPosition');
        const selectedOption = jobSelect.options[jobSelect.selectedIndex];
        
        const candidateData = {
            name: document.getElementById('candidateName').value,
            email: document.getElementById('candidateEmail').value,
            phone: document.getElementById('candidatePhone').value,
            position: selectedOption ? selectedOption.text : 'No Position Selected',
            tags: document.getElementById('tags').value,
            notes: document.getElementById('candidateNotes').value
        };

        console.log('Candidate data:', candidateData);

        // Create and add new candidate card
        const newCard = createCandidateCard(candidateData);
        const newColumn = document.querySelector('.kanban-column[data-stage="New"] .kanban-items');
        if (newColumn) {
            newColumn.appendChild(newCard);
            updateColumnCounts();
            console.log('New candidate card added');
        } else {
            console.log('New column not found!');
        }
        
        // Close modal and reset form
        const modal = bootstrap.Modal.getInstance(document.getElementById('addCandidateModal'));
        if (modal) {
            modal.hide();
        }
        
        e.target.reset();
    }

    function createCandidateCard(data) {
        const card = document.createElement('div');
        card.className = 'candidate-card';
        card.draggable = true;
        card.dataset.id = Date.now();
        
        card.innerHTML = `
            <div class="candidate-name">${data.name}</div>
            <div class="candidate-meta">
                <span class="position">${data.position}</span>
            </div>
            <div class="candidate-actions mt-2">
                <button class="btn btn-sm btn-primary">View</button>
                <button class="btn btn-sm btn-secondary">Message</button>
            </div>
        `;
        
        // Add drag event listeners
        addDragListeners(card);
        
        return card;
    }

    // Add this JavaScript code after the existing scripts

function handleCandidateFormSubmit(e) {
    e.preventDefault();
    
    const formData = {
        position_id: document.getElementById('jobPosition').value,
        candidate_name: document.getElementById('candidateName').value,
        email: document.getElementById('candidateEmail').value,
        phone: document.getElementById('candidatePhone').value,
        notes: document.getElementById('candidateNotes').value,
        tags: document.getElementById('tags').value
    };

    // Send AJAX request to save candidate
    fetch('save_candidate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Create and add new card to the "New" column
            const newCard = createCandidateCard(data.candidate);
            const newColumn = document.querySelector('.kanban-column[data-stage="New"] .kanban-items');
            if (newColumn) {
                newColumn.appendChild(newCard);
                updateColumnCounts();
            }

            // Close modal and reset form
            const modal = bootstrap.Modal.getInstance(document.getElementById('addCandidateModal'));
            modal.hide();
            e.target.reset();
        } else {
            alert('Error saving candidate: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving candidate');
    });
}

function handleDrop(e) {
    e.preventDefault();
    const column = e.target.closest('.kanban-column');
    if (column) {
        column.classList.remove('drag-over');
        
        const cardId = e.dataTransfer.getData('text/plain');
        const card = document.querySelector(`[data-id="${cardId}"]`);
        
        if (card && column) {
            const items = column.querySelector('.kanban-items');
            items.appendChild(card);
            updateColumnCounts();
            
            // Update stage in database
            const newStage = column.dataset.stage;
            fetch('update_candidate_stage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    candidate_id: cardId,
                    stage: newStage
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Error updating stage:', data.message);
                }
                handleHiredStatus(card, newStage);
            })
            .catch(error => console.error('Error:', error));
        }
    }
}


document.addEventListener('click', function(e) {
    if (e.target.classList.contains('view-candidate')) {
        const candidateId = e.target.dataset.id;
        openViewCandidateModal(candidateId);
    }
});

function openViewCandidateModal(candidateId) {
    // Fetch candidate details
    fetch('get_candidate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ candidate_id: candidateId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Populate the modal with candidate data
            document.getElementById('editCandidateId').value = data.candidate.id;
            document.getElementById('editCandidateName').value = data.candidate.candidate_name;
            document.getElementById('editCandidateEmail').value = data.candidate.email;
            document.getElementById('editCandidatePhone').value = data.candidate.phone;
            document.getElementById('editJobPosition').value = data.candidate.position_id;
            document.getElementById('editTags').value = data.candidate.tags;
            document.getElementById('editStage').value = data.candidate.stage;
            document.getElementById('editCandidateNotes').value = data.candidate.notes;
            
            // Show the modal
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

// Handle form submission for editing
document.getElementById('editCandidateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        candidate_id: document.getElementById('editCandidateId').value,
        candidate_name: document.getElementById('editCandidateName').value,
        email: document.getElementById('editCandidateEmail').value,
        phone: document.getElementById('editCandidatePhone').value,
        position_id: document.getElementById('editJobPosition').value,
        notes: document.getElementById('editCandidateNotes').value,
        tags: document.getElementById('editTags').value
    };

    // Send update request
    fetch('update_candidate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the card in the kanban board
            updateCandidateCard(formData);
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('viewCandidateModal'));
            modal.hide();
        } else {
            alert('Error updating candidate: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating candidate');
    });
});

function updateCandidateCard(candidateData) {
    const card = document.querySelector(`.candidate-card[data-id="${candidateData.candidate_id}"]`);
    if (card) {
        card.querySelector('.candidate-name').textContent = candidateData.candidate_name;
        // Update other visible card details as needed
    }
}
</script>
</body>

</html>