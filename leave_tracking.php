<?php
require('database.php');
require('session.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
      <a href="index2.php" class="logo d-flex align-items-center">
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
        <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="recruiment_process.php">
          <i class="bi bi-journal-text"></i><span>Recruiment Process</span>
        </a>
      </li><!-- End Recruiment Process Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#tables-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-layout-text-window-reverse"></i><span>Attendance</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="tables-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="v_all_attandance.php">
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
            <a href="AR_claim.php">
              <i class="bi bi-circle"></i><span>Approve/Reject Claim</span>
            </a>
          </li>
          <li>
            <a href="VR_claim.php">
              <i class="bi bi-circle"></i><span>View All Claim</span>
            </a>
          </li>
        </ul>
      </li><!-- End Claim Management Nav -->
    </ul>

  </aside>

  <main id="main" class="main">
  <div class="card">
            <div class="card-body">
              <h5 class="card-title">View All Leave Request</h5>

              <!-- Default Tabs -->
              <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">Pending for review</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">Approved</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab" aria-controls="contact" aria-selected="false">Rejected</button>
                </li>
              </ul>
              <div class="tab-content pt-2" id="myTabContent">
                <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
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
                    <th scope="col">Attachment</th>
                    <th scope="col">Applied</th>
                    <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    require('database.php');

                    $query = "SELECT el.username, la.leave_id, la.leave_type, la.leave_datestart, la.leave_dateend, la.leave_length, la.leave_reason, la.leave_document, la.apply_date, la.leave_review
                            FROM employeelogin el
                            INNER JOIN leave_apply la ON el.ID = la.fk_leaveapply_id
                            WHERE la.leave_review = 'Pending for review'";

                    $stmt = mysqli_prepare($con, $query);
                    $result = mysqli_stmt_execute($stmt);
                    $modalsContent = '';

                    if ($result) {
                        $data = mysqli_stmt_get_result($stmt);
                        if (mysqli_num_rows($data) > 0) {
                            while ($row = mysqli_fetch_assoc($data)) {
                                $rowId = htmlspecialchars($row['leave_id']);
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['leave_type']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['leave_datestart']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['leave_dateend']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['leave_length']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['leave_reason']) . "</td>";
                                $fullPath = htmlspecialchars($row['leave_document']);
                                if ($fullPath != null) {
                                    echo "<td><a href='" . $fullPath . "' download='" . pathinfo($fullPath, PATHINFO_FILENAME) . ".pdf'>Download Document</a></td>";
                                } else {
                                    echo "<td>No document is uploaded.</td>"; // Leave the cell empty if leave_document is null
                                }
                                echo "<td>" . htmlspecialchars($row['apply_date']) . "</td>";
                                echo "<td><button type='button' class='btn btn-primary review-button' onclick='showReviewModal($rowId)'>Review</button></td>";
                                echo "</tr>";

                                echo <<<HTML
                                <!-- Generic Modal for Leave Review -->
                                <div class="modal fade" id="reviewModal" tabindex="-1" role="dialog" aria-labelledby="reviewModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="reviewModalLabel">Leave Request Review</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <!-- Buttons to approve or reject -->
                                                <button class="btn btn-success" onclick="approveReview(currentLeaveId)">Approve</button>
                                                <button class="btn btn-danger" onclick="showRejectionReason()">Reject</button>
                                                <textarea id="rejectionReason" style="display: none;" class="form-control mt-2" placeholder="Enter rejection reason"></textarea>
                                                <button class="btn btn-secondary mt-2" style="display: none;" id="submitRejection" onclick="rejectReview(currentLeaveId)">Submit Rejection</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                HTML;                        
                            }
                        } else {
                            echo "<tr><td colspan='9'>No pending reviews found.</td></tr>";
                        }
                    } else {
                        echo "Error executing query: " . mysqli_stmt_error($stmt);
                    }

                    mysqli_stmt_close($stmt);
                    mysqli_close($con);
                ?>
                </tbody>
                <?= $modalsContent ?>
                </table>           
                </div>
                <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                <table class="table datatable table-striped">
                <thead>
                    <tr>
                    <th scope="col">Employee</th>
                    <th scope="col">Leave Type</th>
                    <th scope="col">From</th>
                    <th scope="col">To</th>
                    <th scope="col">Days</th>
                    <th scope="col">Reason</th>
                    <th scope="col">Attachment</th>
                    <th scope="col">Applied</th>
                    <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    require('database.php');

                    $query = "SELECT el.username, la.leave_id, la.leave_type, la.leave_datestart, la.leave_dateend, la.leave_length, la.leave_reason, la.leave_document, la.apply_date, la.leave_review
                                FROM employeelogin el
                                INNER JOIN leave_apply la ON el.ID = la.fk_leaveapply_id
                                WHERE la.leave_review = 'Approve'";

                    $stmt = mysqli_prepare($con, $query);

                    if ($stmt) {
                        $result = mysqli_stmt_execute($stmt);
                        $modalsContent = '';

                        if ($result) {
                            $data = mysqli_stmt_get_result($stmt);
                            if (mysqli_num_rows($data) > 0) {
                                while ($row = mysqli_fetch_assoc($data)) {
                                    $rowId = htmlspecialchars($row['leave_id']);
                                    echo "<tr class='clickable-row' data-id='$rowId'>";
                                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['leave_type']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['leave_datestart']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['leave_dateend']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['leave_length']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['leave_reason']) . "</td>";
                                    $fullPath = htmlspecialchars($row['leave_document']);
                                    echo "<td><a href='" . $fullPath . "' download='" . pathinfo($fullPath, PATHINFO_FILENAME) . ".pdf'>Download Document</a></td>";
                                    echo "<td>" . htmlspecialchars($row['apply_date']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['leave_review']);
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9'>No pending reviews found.</td></tr>";
                            }
                        } else {
                            echo "Error executing query: " . mysqli_stmt_error($stmt);
                        }

                        mysqli_stmt_close($stmt);
                    } else {
                        echo "Error preparing statement: " . mysqli_error($con);
                    }

                    mysqli_close($con);
                    ?>
                        </tbody>
                    </table> 
                </div>
                <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                <table class="table datatable table-striped">
                <thead>
                    <tr>
                    <th scope="col">Employee</th>
                    <th scope="col">Leave Type</th>
                    <th scope="col">From</th>
                    <th scope="col">To</th>
                    <th scope="col">Days</th>
                    <th scope="col">Reason</th>
                    <th scope="col">Attachment</th>
                    <th scope="col">Applied</th>
                    <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                require('database.php');

                $query = "SELECT el.username, la.leave_id, la.leave_type, la.leave_datestart, la.leave_dateend, la.leave_length, la.leave_reason, la.leave_document, la.apply_date, la.leave_review
                            FROM employeelogin el
                            INNER JOIN leave_apply la ON el.ID = la.fk_leaveapply_id
                            WHERE la.leave_review = 'Reject'";

                $stmt = mysqli_prepare($con, $query);

                if ($stmt) {
                    $result = mysqli_stmt_execute($stmt);
                    $modalsContent = '';

                    if ($result) {
                        $data = mysqli_stmt_get_result($stmt);
                        if (mysqli_num_rows($data) > 0) {
                            while ($row = mysqli_fetch_assoc($data)) {
                                $rowId = htmlspecialchars($row['leave_id']);
                                echo "<tr class='reject-row' data-id='$rowId'>";
                                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['leave_type']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['leave_datestart']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['leave_dateend']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['leave_length']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['leave_reason']) . "</td>";
                                $fullPath = htmlspecialchars($row['leave_document']);
                                echo "<td><a href='" . $fullPath . "' download='" . pathinfo($fullPath, PATHINFO_FILENAME) . ".pdf'>Download Document</a></td>";
                                echo "<td>" . htmlspecialchars($row['apply_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['leave_review']);
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9'>No approved leave requests found.</td></tr>";
                        }
                    } else {
                        echo "Error executing query: " . mysqli_stmt_error($stmt);
                    }

                    mysqli_stmt_close($stmt);
                } else {
                    echo "Error preparing statement: " . mysqli_error($con);
                }

                mysqli_close($con);
                ?>
                        </tbody>
                    </table>                 
                  </div>
              </div><!-- End Default Tabs -->
            </div>
          </div>
    </main>

      <!-- ======= Footer ======= -->
      <footer id="footer" class="footer text-center">
        <div class="copyright">
          &copy; Copyright <strong><span>SMEasyHR</span></strong>. All Rights Reserved
        </div>
      </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.min.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
let currentLeaveId = null;  // Global variable to keep track of the current leave ID

function showReviewModal(leaveId) {
    currentLeaveId = leaveId;
    $('#rejectionReason').hide();  // Make sure to hide the rejection reason textarea initially
    $('#submitRejection').hide();  // Hide the submit rejection button initially
    $('#reviewModal').modal('show');  // Show the modal
}

function approveReview() {
    if (currentLeaveId) {
        updateLeaveStatus(currentLeaveId, 'Approve', '');
    }
}

function showRejectionReason() {
    $('#rejectionReason').show();
    $('#submitRejection').show();
}

function rejectReview() {
    var reason = $('#rejectionReason').val();
    if (!reason) {
        alert('Please provide a reason for rejection.');
        return;
    }
    updateLeaveStatus(currentLeaveId, 'Reject', reason);
}

function updateLeaveStatus(leaveId, status, reason) {
    $.ajax({
        url: 'update_leave_status.php',
        type: 'POST',
        data: {
            leaveId: leaveId,
            status: status,
            reason: reason
        },
        success: function(response) {
            if (response.trim() === "Success") {
                alert(`Leave request ${status.toLowerCase()} successfully!`);
                $('#reviewModal').modal('hide');  // Close the modal
                location.reload();  // Refresh the page to show updated status
            } else {
                alert(`Failed to ${status.toLowerCase()} leave. Server response: ` + response);
            }
        },
        error: function(xhr, status, error) {
            console.error(`An error occurred: ${xhr.responseText}`);
            alert(`An error occurred while processing the leave. Please try again.`);
        }
    });
}
</script>


</body>

</html>