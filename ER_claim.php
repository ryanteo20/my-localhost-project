<?php
require('database.php');
require('session.php');

// Make sure user ID is set
if (!isset($_SESSION['ID'])) {
    die("User not logged in.");
}

$user_id = $_SESSION['ID'];

// Safe: Fetch user's full name from `personal_information`
$fullname = "Unknown";
$query = "SELECT full_name FROM personal_information WHERE personal_id = ?";
$stmt = mysqli_prepare($con, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $fullname);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
} else {
    $fullname = "Unknown"; // fallback
}

if (isset($_SESSION['success_message'])) {
    echo '<div id="autoDismissAlert" class="alert alert-success alert-dismissible fade show text-center" role="alert">'
        . htmlspecialchars($_SESSION['success_message']) .
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div id="autoDismissAlert" class="alert alert-danger alert-dismissible fade show text-center" role="alert">'
        . htmlspecialchars($_SESSION['error_message']) .
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION['error_message']);
}

require 'vendor/autoload.php';
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
      <h1>Request Claim</h1>
    </div><!-- End Page Title -->

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">All Claims</h5>

              <!-- Bordered Tabs -->
              <ul class="nav nav-tabs nav-tabs-bordered" id="borderedTab" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#bordered-home" type="button" role="tab" aria-controls="home" aria-selected="true">Claims List</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#bordered-profile" type="button" role="tab" aria-controls="profile" aria-selected="false">Summary Report</button>
              </ul>
              <div class="tab-content pt-2" id="borderedTabContent">
                <div class="tab-pane fade show active" id="bordered-home" role="tabpanel" aria-labelledby="home-tab">
                <!-- Sales Card -->
                 <div class="row">
                    <div class="col-xxl-4 col-md-6">
                    <div class="card info-card sales-card">

                        <div class="filter">
                        <a class="icon" href="#" data-bs-toggle="dropdown"></a>
                        </div>
                        <div class="card-body">
                        <h5 class="card-title">Total Claim Requested</h5>

                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="ri-money-dollar-box-line"></i>
                            </div>
                            <div class="ps-3">
                            <?php
                                // Include your database connection
                                require('database.php');

                                // Get the current employee's ID from the session
                                $employee_id = $_SESSION['ID'];

                                // Query the total claims requested by the current employee
                                $query_employee_claims = "SELECT COUNT(*) AS total_claims FROM claims WHERE employee_id = ?";

                                // Prepare and bind the query
                                $stmt = $con->prepare($query_employee_claims);
                                $stmt->bind_param("i", $employee_id); // "i" for integer type

                                // Execute the query
                                $stmt->execute();
                                $result = $stmt->get_result();

                                // Check if the query was successful
                                if ($result) {
                                    // Fetch the result as an associative array
                                    $row = $result->fetch_assoc();
                                    
                                    // Get the total number of claims for the current employee
                                    $totalClaims = $row['total_claims'];
                                } else {
                                    // Error handling if the query fails
                                    $totalClaims = "Error fetching claims";
                                }

                                // Output the total number of pending leave applications for review within the card
                                echo "<h6>$totalClaims</h6>";
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
                            <h5 class="card-title">Total Claim Approve</h5>

                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="ri-money-dollar-box-line"></i>
                                </div>
                                <div class="ps-3">
                                <?php
                                    // Include your database connection
                                    require('database.php');

                                    // Query pending leave applications for review
                                    $query_pending_review = "SELECT COUNT(*) AS Approve FROM claims WHERE status = 'Approved' AND employee_id = $user_id";

                                    // Execute the query
                                    $result_pending_review = mysqli_query($con, $query_pending_review);

                                    // Check if the query executed successfully
                                    if ($result_pending_review) {
                                        // Fetch the result as an associative array
                                        $row_pending_review = mysqli_fetch_assoc($result_pending_review);
                                        
                                        // Get the total number of pending leave applications for review
                                        $pendingReview = $row_pending_review['Approve'];
                                        
                                    } else {
                                        // Error handling if the query fails
                                        $pendingReview = "Error fetching pending leave for review";
                                    }

                                    // Output the total number of pending leave applications for review within the card
                                    echo "<h6>$pendingReview</h6>";
                                    ?>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div><!-- End Sales Card -->
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#verticalycentered">                            
                            New Claim
                        </button>
                    </div>
                    <div class="modal fade" id="verticalycentered" tabindex="-1" data-bs-backdrop="false">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">New Claim</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="newClaimForm" method="POST" action="insert_claim.php">
                                    <!-- Employee -->
                                    <div class="mb-3">
                                        <label for="employeeName" class="form-label">Employee</label>
                                        <select class="form-select" id="employeeName" name="employee">
                                            <option value="<?php echo htmlspecialchars($user_id); ?>" selected>
                                            <?php echo htmlspecialchars($fullname); ?>
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Category -->
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Category</label>
                                        <select class="form-select" id="category" name="category">
                                        <option selected disabled>Choose a category below</option>
                                        <option value="Travel">Travel</option>
                                        <option value="Meal">Meal</option>
                                        <option value="Office Supplies">Office Supplies</option>
                                        <!-- Add more if needed -->
                                        </select>
                                    </div>

                                    <!-- Date of Transaction -->
                                    <div class="mb-3">
                                        <label for="transactionDate" class="form-label">Date of Transaction</label>
                                        <input type="date" class="form-control" id="transactionDate" name="transaction_date">
                                    </div>

                                    <!-- Total Claim Amount + Tax Invoice -->
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                        <label for="claimAmount" class="form-label">Total Claim Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">MYR</span>
                                            <input type="number" step="0.01" class="form-control" id="claimAmount" name="amount">
                                        </div>
                                        </div>
                                        <div class="col-md-6">
                                        <label for="invoiceNumber" class="form-label">Receipt No</label>
                                        <input type="text" class="form-control" id="invoiceNumber" name="invoice_number">
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div class="mb-3">
                                        <label for="claimNotes" class="form-label">Notes about this claim</label>
                                        <textarea class="form-control" id="claimNotes" name="notes" rows="3" placeholder='For e.g. "Met ABC Sdn. Bhd. CEO for dinner"'></textarea>
                                    </div>

                                    <!-- Attachments -->
                                    <div class="mb-3">
                                        <label for="claimAttachment" class="form-label">Attachments</label>
                                        <input class="form-control" type="file" id="claimAttachment" name="attachment">
                                        <div class="form-text">Max file size 1MB.</div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Submit claim</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Row -->
              </div><!-- End Bordered Tabs -->
            </div>
            <div class="tab-pane fade" id="bordered-profile" role="tabpanel" aria-labelledby="profile-tab">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Claim Summary Report</h5>
                        <p>You can now download a claim summary report in xlsx (an Excel spreadsheet). Choose the start date and end date, a report will be generated immediately.</p>
                        <form action="download_xlsx.php" method="POST" class="row g-3 align-items-center">                    <div class="col-auto">
                            <input type="date" name="start_date" class="form-control" placeholder="Start date" required>
                        </div>
                        <div class="col-auto">
                            <span> ~ </span>
                        </div>
                        <div class="col-auto">
                            <input type="date" name="end_date" class="form-control" placeholder="End date" required>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Download XLSX</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.getElementById('autoDismissAlert');
        if (alert) {
        setTimeout(() => {
            // Remove `show` to start fade out
            alert.classList.remove('show');
            // Wait for fade transition to complete (Bootstrap default is ~150ms)
            setTimeout(() => {
            // Remove the element from DOM
            alert.remove();
            }, 500); // wait slightly longer than Bootstrap's fade transition
        }, 10000); // 10 sec before starting fade
        }
    });
    </script>
</body>

</html>