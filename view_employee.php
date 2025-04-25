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
            <h5 class="card-title">View All Employees</h5>

              <!-- Bordered Tabs -->
              <ul class="nav nav-tabs nav-tabs-bordered" id="borderedTab" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="registration-tab" data-bs-toggle="tab" data-bs-target="#bordered-registration" type="button" role="tab" aria-controls="regiatration" aria-selected="true">Registration</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="personal-tab" data-bs-toggle="tab" data-bs-target="#bordered-personal" type="button" role="tab" aria-controls="personal" aria-selected="false">Personal Information</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="employement-tab" data-bs-toggle="tab" data-bs-target="#bordered-employement" type="button" role="tab" aria-controls="employement" aria-selected="false">Employment Detail</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="payroll-tab" data-bs-toggle="tab" data-bs-target="#bordered-payroll" type="button" role="tab" aria-controls="payroll" aria-selected="false">Payroll Detail</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="document-tab" data-bs-toggle="tab" data-bs-target="#bordered-document" type="button" role="tab" aria-controls="document" aria-selected="false">Employee Document</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="leave-tab" data-bs-toggle="tab" data-bs-target="#bordered-leave" type="button" role="tab" aria-controls="leave" aria-selected="false">Leave Information</button>
                </li>
              </ul>
              <div class="tab-content pt-2" id="borderedTabContent">
                <div class="tab-pane fade show active" id="bordered-registration" role="tabpanel" aria-labelledby="regiatration-tab">
                    <table class="table">
                      <thead>
                          <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th data-type="datetime">Registration Date</th>
                          </tr>
                      </thead>
                      <tbody id="latest-employee-info">
                      <?php
                        // Include your database connection
                        require('database.php');

                        // Check if the session variable is set
                        if(isset($_SESSION['ID'])) {
                            // Get the ID value from the session
                            $sessionID = $_SESSION['ID'];

                            // Prepare the SQL query to select data from the employeelogin table based on the session ID
                            $query = "SELECT * FROM employeelogin WHERE ID = ?";

                            // Prepare a statement
                            $stmt = mysqli_prepare($con, $query);

                            // Bind the session ID value to the query parameter
                            mysqli_stmt_bind_param($stmt, "i", $sessionID);

                            // Execute the query
                            mysqli_stmt_execute($stmt);

                            // Get the result set
                            $result = mysqli_stmt_get_result($stmt);

                            // Check if there are any rows returned
                            if ($result && mysqli_num_rows($result) > 0) {
                                // Process the result set
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>{$row['ID']}</td>";
                                    echo "<td>{$row['username']}</td>";
                                    echo "<td>{$row['password']}</td>";
                                    echo "<td>{$row['reg_date']}</td>";
                                    echo "</tr>";
                                }
                            } else {
                                // If no data is found, display a message
                                echo "<tr><td colspan='4'>No data found</td></tr>";
                            }

                            // Close the statement
                            mysqli_stmt_close($stmt);
                        } else {
                            echo "Session ID is not set.";
                        }

                        // Close the connection
                        mysqli_close($con);
                        ?>
                      </tbody>
                  </table>
            </div>
              <div class="tab-pane fade" id="bordered-personal" role="tabpanel" aria-labelledby="personal-tab">
                  <div class="table-responsive">
                      <table class="table">
                          <thead>
                              <tr>
                                  <th scope="col">ID</th>
                                  <th scope="col">Fullname</th>
                                  <th scope="col">Email</th>
                                  <th scope="col">Phone Numeber</th>
                                  <th scope="col">NRIC No</th>
                                  <th scope="col">Permanent Address</th>
                                  <th scope="col">Permanent Address 2</th>
                                  <th scope="col">Zip</th>
                                  <th scope="col">City</th>
                                  <th scope="col">State</th>
                                  <th scope="col">Race</th>
                                  <th scope="col">Religion</th>
                                  <th scope="col">Marital Status</th>
                                  <th scope="col">Gender</th>
                              </tr>
                          </thead>
                          <tbody id="latest-employee-info">
                          <?php
                        // Include your database connection
                        require('database.php');

                        // Check if the session variable is set
                        if(isset($_SESSION['ID'])) {
                            // Get the ID value from the session
                            $sessionID = $_SESSION['ID'];
                            
                            // Prepare the SQL query to select data from the employeelogin table based on the session ID
                            $query = "SELECT pi.personal_id, pi.full_name, pi.email, pi.phone_number, pi.ic, pi.address, pi.address2, pi.zip, pi.city, pi.state, pi.race, pi.religion, pi.marital, pi.gender
                            FROM personal_information AS pi
                            JOIN employeelogin AS el ON pi.personal_id = el.ID
                            WHERE el.ID = ?";
                            // Prepare a statement
                            $stmt = mysqli_prepare($con, $query);

                            // Bind the session ID value to the query parameter
                            mysqli_stmt_bind_param($stmt, "i", $sessionID);

                            // Execute the query
                            mysqli_stmt_execute($stmt);

                            // Get the result set
                            $result = mysqli_stmt_get_result($stmt);

                            // Check if there are any rows returned
                            if ($result && mysqli_num_rows($result) > 0) {
                                // Process the result set
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>{$row['personal_id']}</td>";
                                    echo "<td>{$row['full_name']}</td>";
                                    echo "<td>{$row['email']}</td>";
                                    echo "<td>{$row['phone_number']}</td>";
                                    echo "<td>{$row['ic']}</td>";
                                    echo "<td>{$row['address']}</td>";
                                    echo "<td>{$row['address2']}</td>";
                                    echo "<td>{$row['zip']}</td>";
                                    echo "<td>{$row['city']}</td>";
                                    echo "<td>{$row['state']}</td>";
                                    echo "<td>{$row['race']}</td>";
                                    echo "<td>{$row['religion']}</td>";
                                    echo "<td>{$row['marital']}</td>";
                                    echo "<td>{$row['gender']}</td>";
                                    echo "</tr>";
                                }
                            } else {
                                // If no data is found, display a message
                                echo "<tr><td colspan='4'>No data found</td></tr>";
                            }

                            // Close the statement
                            mysqli_stmt_close($stmt);
                        } else {
                            echo "Session ID is not set.";
                        }

                        // Close the connection
                        mysqli_close($con);
                        ?>
                          </tbody>
                      </table>

                    </div>
                </div>
                <div class="tab-pane fade" id="bordered-employement" role="tabpanel" aria-labelledby="employement-tab">
                  <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Employment Type</th>
                            <th scope="col">Status</th>
                            <th scope="col">Position</th>
                            <th scope="col">Department</th>
                            <th scope="col">Employment Start Date</th>
                            <th scope="col">Employment End Date</th>
                        </tr>
                    </thead>
                      <tbody id="latest-employment-detail">
                      <?php
                        // Include your database connection
                        require('database.php');

                        // Check if the session variable is set
                        if(isset($_SESSION['ID'])) {
                            // Get the ID value from the session
                            $sessionID = $_SESSION['ID'];
                            
                            // Prepare the SQL query to select data from the employeelogin table based on the session ID
                            $query = "SELECT *
                            FROM employment_detail AS pi
                            JOIN employeelogin AS el ON pi.employment_id = el.ID
                            WHERE el.ID = ?";
                            // Prepare a statement
                            $stmt = mysqli_prepare($con, $query);

                            // Bind the session ID value to the query parameter
                            mysqli_stmt_bind_param($stmt, "i", $sessionID);

                            // Execute the query
                            mysqli_stmt_execute($stmt);

                            // Get the result set
                            $result = mysqli_stmt_get_result($stmt);

                            // Check if there are any rows returned
                            if ($result && mysqli_num_rows($result) > 0) {
                                // Process the result set
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>{$row['employment_id']}</td>";
                                    echo "<td>{$row['employment_type']}</td>";
                                    echo "<td>{$row['employment_status']}</td>";
                                    echo "<td>{$row['employment_position']}</td>";
                                    echo "<td>{$row['employment_department']}</td>";
                                    echo "<td>{$row['employment_start']}</td>";
                                    echo "<td>{$row['employment_end']}</td>";
                                    echo "</tr>";
                                }
                            } else {
                                // If no data is found, display a message
                                echo "<tr><td colspan='4'>No data found</td></tr>";
                            }

                            // Close the statement
                            mysqli_stmt_close($stmt);
                        } else {
                            echo "Session ID is not set.";
                        }

                        // Close the connection
                        mysqli_close($con);
                        ?>
                      </tbody>
                  </table>          
                </div>
                <div class="tab-pane fade" id="bordered-payroll" role="tabpanel" aria-labelledby="payroll-tab">
                  <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Salary Type</th>
                            <th scope="col">Bank Account Name</th>
                            <th scope="col">Bank Account No</th>
                            <th scope="col">Employee Salary</th>
                            <th scope="col">Bank Name</th>
                        </tr>
                    </thead>
                      <tbody id="latest-payroll-detail">
                      <?php
                        // Include your database connection
                        require('database.php');

                        // Check if the session variable is set
                        if(isset($_SESSION['ID'])) {
                            // Get the ID value from the session
                            $sessionID = $_SESSION['ID'];
                            
                            // Prepare the SQL query to select data from the employeelogin table based on the session ID
                            $query = "SELECT *
                            FROM payroll_detail AS pi
                            JOIN employeelogin AS el ON pi.payroll_id = el.ID
                            WHERE el.ID = ?";
                            // Prepare a statement
                            $stmt = mysqli_prepare($con, $query);

                            // Bind the session ID value to the query parameter
                            mysqli_stmt_bind_param($stmt, "i", $sessionID);

                            // Execute the query
                            mysqli_stmt_execute($stmt);

                            // Get the result set
                            $result = mysqli_stmt_get_result($stmt);

                            // Check if there are any rows returned
                            if ($result && mysqli_num_rows($result) > 0) {
                                // Process the result set
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>{$row['payroll_id']}</td>";
                                    echo "<td>{$row['salary_type']}</td>";
                                    echo "<td>{$row['bank_account_name']}</td>";
                                    echo "<td>{$row['bank_account_no']}</td>";
                                    echo "<td>{$row['employee_salary']}</td>";
                                    echo "<td>{$row['bank_name']}</td>";
                                    echo "</tr>";
                                }
                            } else {
                                // If no data is found, display a message
                                echo "<tr><td colspan='4'>No data found</td></tr>";
                            }

                            // Close the statement
                            mysqli_stmt_close($stmt);
                        } else {
                            echo "Session ID is not set.";
                        }

                        // Close the connection
                        mysqli_close($con);
                        ?>
                      </tbody>
                  </table>
                </div>
                <div class="tab-pane fade" id="bordered-document" role="tabpanel" aria-labelledby="document-tab">
                  <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Education Level</th>
                            <th scope="col">IC</th>
                        </tr>
                    </thead>
                    <tbody id="latest-employee-document">
                    <?php
                        // Include your database connection
                        require('database.php');

                        // Check if the session variable is set
                        if(isset($_SESSION['ID'])) {
                            // Get the ID value from the session
                            $sessionID = $_SESSION['ID'];
                            
                            // Prepare the SQL query to select data from the employeelogin table based on the session ID
                            $query = "SELECT *
                            FROM employee_document AS pi
                            JOIN employeelogin AS el ON pi.document_id = el.ID
                            WHERE el.ID = ?";
                            // Prepare a statement
                            $stmt = mysqli_prepare($con, $query);

                            // Bind the session ID value to the query parameter
                            mysqli_stmt_bind_param($stmt, "i", $sessionID);

                            // Execute the query
                            mysqli_stmt_execute($stmt);

                            // Get the result set
                            $result = mysqli_stmt_get_result($stmt);

                            // Check if there are any rows returned
                            if ($result && mysqli_num_rows($result) > 0) {
                                // Process the result set
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>{$row['document_id']}</td>";
                                    echo "<td>{$row['education_level']}</td>";
                                    echo "<td><a href='file://{$row['ic_picture']}' target='_blank'>View Picture</a></td>";                            
    
                                    echo "</tr>";
                                }
                            } else {
                                // If no data is found, display a message
                                echo "<tr><td colspan='4'>No data found</td></tr>";
                            }

                            // Close the statement
                            mysqli_stmt_close($stmt);
                        } else {
                            echo "Session ID is not set.";
                        }

                        // Close the connection
                        mysqli_close($con);
                        ?>
                    </tbody>
                </table>              
              </div>
              <div class="tab-pane fade" id="bordered-leave" role="tabpanel" aria-labelledby="leave-tab">
                  <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Annual Leave</th>
                            <th scope="col">Sick Leave</th>
                            <th scope="col">Hospitalization Leave</th>
                        </tr>
                    </thead>
                    <tbody id="latest-leave-info">
                      <?php
                        // Include your database connection
                        require('database.php');

                              // Check if the session variable is set
                              if(isset($_SESSION['ID'])) {
                              // Get the ID value from the session
                              $sessionID = $_SESSION['ID'];
                              
                              // Prepare the SQL query to select data from the employeelogin table based on the session ID
                              $query = "SELECT *
                              FROM leave_info AS pi
                              JOIN employeelogin AS el ON pi.leaveinfo_id = el.ID
                              WHERE el.ID = ?";
                              // Prepare a statement
                              $stmt = mysqli_prepare($con, $query);

                              // Bind the session ID value to the query parameter
                              mysqli_stmt_bind_param($stmt, "i", $sessionID);

                              // Execute the query
                              mysqli_stmt_execute($stmt);

                              // Get the result set
                              $result = mysqli_stmt_get_result($stmt);

                              // Check if there are any rows returned
                              if ($result && mysqli_num_rows($result) > 0) {
                                  // Process the result set
                                  while ($row = mysqli_fetch_assoc($result)) {
                                      echo "<tr>";
                                      echo "<td>{$row['leaveinfo_id']}</td>";
                                      echo "<td>{$row['annual_leave']}</td>";
                                      echo "<td>{$row['sick_leave']}</td>";
                                      echo "<td>{$row['hospitalization_leave']}</td>";            
                                      echo "</tr>";
                                  }
                              } else {
                                  // If no data is found, display a message
                                  echo "<tr><td colspan='4'>No data found</td></tr>";
                              }

                              // Close the statement
                              mysqli_stmt_close($stmt);
                          } else {
                              echo "Session ID is not set.";
                          }

                        // Close the connection
                        mysqli_close($con);
                        ?>
                    </tbody>
                </table>  
                      </div>
          </div><!-- End Bordered Tabs -->
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
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>




  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>
  <script src="assets/js/view_all.js"></script>


</body>

</html>