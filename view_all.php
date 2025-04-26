<?php
require('database.php');
require('session.php');
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
                    <table class="table datatable">
                      <thead>
                          <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th data-type="datetime">Registration Date</th>
                            <th>Role</th>
                            <th>First Login</th>
                          </tr>
                      </thead>
                      <tbody id="latest-employee-info">
                        <?php
                          // Include your database connection
                          require('database.php');

                          // Prepare the SQL query to select data from the employeelogin table
                          $query = "SELECT ID, username, password, reg_date, role, first_login FROM employeelogin";

                          // Execute the query
                          $result = mysqli_query($con, $query);

                          // Check if there are any rows returned
                          if ($result && mysqli_num_rows($result) > 0) {
                              // Fetch each row of data and display it in the table
                              while ($row = mysqli_fetch_assoc($result)) {
                                  echo "<tr>";
                                  echo "<td>{$row['ID']}</td>";
                                  echo "<td>{$row['username']}</td>";
                                  echo "<td>{$row['password']}</td>";
                                  echo "<td>{$row['reg_date']}</td>";
                                  echo "<td>{$row['role']}</td>";
                                  echo "<td>{$row['first_login']}</td>";
                                  echo "</tr>";
                              }
                          } else {
                              // If no data is found, display a message
                              echo "<tr><td colspan='4'>No data found</td></tr>";
                          }

                          // Close the connection
                          mysqli_close($con);
                          ?>
                      </tbody>
                  </table>
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#disablebackdrop">
                    Edit Registration Information
                  </button>
                  <div class="modal fade" id="disablebackdrop" tabindex="-1" data-bs-backdrop="false">
                  <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content">
                          <div class="modal-header">
                              <h5 class="modal-title">Edit Employee</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                              <div class="mb-3">
                                  <label for="employeeIdInput" class="form-label">Please Enter Employee ID:</label>
                                  <input type="text" class="form-control" id="employeeIdInput" placeholder="Enter Employee ID">
                              </div>
                              <div class="mb-3">
                                  <label for="updateField" class="form-label">Select Field to Update:</label>
                                  <select class="form-select" id="updateField">
                                      <option value="username">Username</option>
                                      <option value="password">Password</option>
                                      <option value="role">Role</option>
                                      <option value="first_login">First Login</option>
                                  </select>
                              </div>
                              <div class="mb-3" id="newRoleDropdown" style="display: none;">
                                  <label for="newRole" class="form-label">Select New Role:</label>
                                  <select class="form-select" id="newRole">
                                      <option value="Employer">Employer</option>
                                      <option value="Employee">Employee</option>
                                  </select>
                              </div>
                              <div class="mb-3" id="firstLoginOptions" style="display: none;">
                                <label for="firstLoginSelect" class="form-label">First Login:</label>
                                <select class="form-select" id="firstLoginSelect">
                                    <option value="0">0 - Old User</option>
                                    <option value="1">1 - New User Reset Password</option>
                                </select>
                            </div>
                              <div class="mb-3" id="newValueField">
                                  <label for="newValue" class="form-label">New Value:</label>
                                  <input type="text" class="form-control" id="newValue" placeholder="Enter new value">
                              </div>
                          </div>
                          <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                              <button type="button" class="btn btn-primary" id="saveChangesBtn">Edit Employee</button>
                          </div>
                          <div id="successMessage" style="display: none;" class="alert alert-success" role="alert">
                              Employee edited successfully.
                          </div>
                      </div>
                  </div>
              </div><!-- End Disabled Backdrop Modal-->

            </div>
              <div class="tab-pane fade" id="bordered-personal" role="tabpanel" aria-labelledby="personal-tab">
                  <div class="table-responsive">
                      <table class="table datatable">
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
                              require('database.php');

                              $query = "SELECT personal_id, full_name, email, phone_number, ic, address, address2, zip, city, state, race, religion, marital, gender FROM personal_information";

                              $result = mysqli_query($con, $query);

                              if ($result) {
                                  if (mysqli_num_rows($result) > 0) {
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
                                      echo "<tr><td colspan='4'>No data found</td></tr>";
                                  }
                              } else {
                                  echo "Error: " . mysqli_error($con);
                              }

                              mysqli_close($con);
                              ?>
                          </tbody>
                      </table>

                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editPersonalInformation">
                          Edit Personal Information
                      </button>

                      <!-- Modal for editing data -->
                      <div class="modal fade" id="editPersonalInformation" tabindex="-1"  data-bs-backdrop="false">
                          <div class="modal-dialog modal-dialog-centered">
                              <div class="modal-content">
                                  <div class="modal-header">
                                      <h5 class="modal-title">Edit Data</h5>
                                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                  </div>
                                  <div class="modal-body">
                                      <form id="editDataForm">
                                          <div class="mb-3">
                                              <label for="editIdInput" class="form-label">Enter ID:</label>
                                              <input type="text" class="form-control" id="editpersonalid" placeholder="Enter ID">
                                          </div>
                                          <div class="mb-3">
                                              <label for="editFieldSelect" class="form-label">Select Field to Edit:</label>
                                              <select class="form-select" id="editpersonalfile">
                                                  <option value="" disabled selected hidden>Choose...</option>
                                                  <option value="full_name">Full Name</option>
                                                  <option value="email">Email</option>
                                                  <option value="phone_number">Phone Number</option>
                                                  <option value="ic">NRIC No</option>
                                                  <option value="address">Permanent Address</option>
                                                  <option value="address2">Permanent Address 2</option>
                                                  <option value="zip">Zip</option>
                                                  <option value="city">City</option>
                                                  <option value="state">State</option>
                                                  <option value="race">Race</option>
                                                  <option value="religion">Religion</option>
                                                  <option value="marital">Marital Status</option>
                                                  <option value="gender">Gender</option>
                                              </select>
                                          </div>
                                          <div class="mb-3" id="newStateDropdown" style="display: none;">
                                              <label for="newState" class="form-label">Select State:</label>
                                              <select class="form-select" id="newState">
                                              <option value="" disabled selected hidden>Choose...</option>
                                                <option value="Johor">Johor</option>
                                                <option value="Kedah">Kedah</option>
                                                <option value="Kelantan">Kelantan</option>
                                                <option value="Malacca">Malacca</option>
                                                <option value="Negeri Sembilan">Negeri Sembilan</option>
                                                <option value="Pahang">Pahang</option>
                                                <option value="Penang">Penang</option>
                                                <option value="Perak">Perak</option>
                                                <option value="Perlis">Perlis</option>
                                                <option value="Sabah">Sabah</option>
                                                <option value="Sarawak">Sarawak</option>
                                                <option value="Selangor">Selangor</option>
                                                <option value="Terengganu">Terengganu</option>
                                              </select>
                                          </div>
                                          <div class="mb-3" id="newRaceDropdown" style="display: none;">
                                            <label for="newRace" class="form-label">Select Race:</label>
                                            <select class="form-select" id="newRace">
                                              <option value="" disabled selected hidden>Choose...</option>
                                              <option value="Chinese">Chinese</option>
                                              <option value="Malay">Malay</option>
                                              <option value="Indian">Indian</option>
                                              <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="mb-3" id="newReligionDropdown" style="display: none;">
                                            <label for="newReligion" class="form-label">Select Religion:</label>
                                            <select class="form-select" id="newReligion">
                                              <option value="" disabled selected hidden>Choose...</option>
                                              <option value="Christian">Christian</option>
                                              <option value="Islam">Islam</option>
                                              <option value="Buddhist">Buddhist</option>
                                              <option>Other</option>
                                            </select>
                                        </div> 
                                        <div class="mb-3" id="newMaritalDropdown" style="display: none;">
                                            <label for="newMarital" class="form-label">Select Marital:</label>
                                            <select class="form-select" id="newMarital">
                                              <option value="" disabled selected hidden>Choose...</option>
                                              <option value="Single">Single</option>
                                              <option value="Married">Married</option>
                                              <option value="Divorced">Divorced</option>
                                              <option>Other</option>
                                            </select>
                                        </div>                                        
                                        <div class="mb-3" id="newGenderDropdown" style="display: none;">
                                            <label for="newGender" class="form-label">Select Gender:</label>
                                            <select class="form-select" id="newGender">
                                              <option value="" disabled selected hidden>Choose...</option>
                                              <option value="Male">Male</option>
                                              <option value="Female">Female</option>
                                              <option value="Other">Other</option>
                                            </select>
                                        </div>
                                          <div class="mb-3">
                                              <label for="editNewValueInput" class="form-label">Enter New Value:</label>
                                              <input type="text" class="form-control" id="editpersonalvalue" placeholder="Enter New Value">
                                          </div>
                                      </form>
                                  </div>
                                  <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                      <button type="button" class="btn btn-primary" id="savePersonalBtn">Save Changes</button>
                                  </div>
                              </div>
                          </div>
                        </div><!-- End Edit Data Modal-->
                    </div>
                <div class="tab-pane fade" id="bordered-employement" role="tabpanel" aria-labelledby="employement-tab">
                  <table class="table datatable">
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

                          // Prepare the SQL query to select data from the employeelogin table
                          $query = "SELECT employment_id, employment_type, employment_status, employment_position, employment_department, employment_start, employment_end FROM employment_detail";

                          // Execute the query
                          $result = mysqli_query($con, $query);

                          // Check if there are any rows returned
                          if (mysqli_num_rows($result) > 0) {
                              // Fetch each row of data and display it in the table
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
                          // Close the connection
                          mysqli_close($con);
                          ?>
                      </tbody>
                  </table>   
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editEmployment">
                          Edit Employment Detail
                      </button>

                  <!-- Modal for editing employment data -->
                  <div class="modal fade" id="editEmployment" tabindex="-1" data-bs-backdrop="false">
                      <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content">
                              <div class="modal-header">
                                  <h5 class="modal-title">Edit Employment Data</h5>
                                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                  <form id="editEmploymentForm">
                                      <div class="mb-3">
                                          <label for="editemploymentid" class="form-label">Enter Employee ID:</label>
                                          <input type="text" class="form-control" id="editemploymentid" placeholder="Enter Employee ID">
                                      </div>
                                      <div class="mb-3">
                                          <label for="editemploymentfile" class="form-label">Select Field to Edit:</label>
                                          <select class="form-select" id="editemploymentfile">
                                              <option value="" disabled selected hidden>Choose...</option>
                                              <option value="employment_type">Employment Type</option>
                                              <option value="employment_status">Status</option>
                                              <option value="employment_position">Position</option>
                                              <option value="employment_department">Department</option>
                                              <option value="employment_start">Employment Start Date</option>
                                              <option value="employment_end">Employment End Date</option>
                                          </select>
                                      </div>
                                      <div class="mb-3" id="employmentValueField">
                                          <!-- This div will be dynamically populated based on the selected field -->
                                      </div>
                                  </form>
                              </div>
                              <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                  <button type="button" class="btn btn-primary" id="saveEmploymentBtn">Save Changes</button>
                              </div>
                          </div>
                      </div>
                  </div>
                  <!-- End Edit Employment Data Modal-->
        
                </div>
                <div class="tab-pane fade" id="bordered-payroll" role="tabpanel" aria-labelledby="payroll-tab">
                  <table class="table datatable">
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

                          // Prepare the SQL query to select data from the employeelogin table
                          $query = "SELECT payroll_id, salary_type, bank_account_name, bank_account_no, employee_salary, bank_name FROM payroll_detail";

                          // Execute the query
                          $result = mysqli_query($con, $query);

                          // Check if there are any rows returned
                          if (mysqli_num_rows($result) > 0) {
                              // Fetch each row of data and display it in the table
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

                          error_reporting(E_ALL);
                          ini_set('display_errors', 1);
                          
                          // Close the connection
                          mysqli_close($con);
                          ?> 
                      </tbody>
                  </table>
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editDataModal">
                      Edit Payroll
                  </button>

                  <!-- Modal for editing data -->
                  <div class="modal fade" id="editDataModal" tabindex="-1" data-bs-backdrop="false">
                      <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content">
                              <div class="modal-header">
                                  <h5 class="modal-title">Edit Data</h5>
                                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                  <!-- Form for entering ID, selecting field, and entering new value -->
                                  <form id="editDataForm">
                                      <div class="mb-3">
                                          <label for="editPayrollId" class="form-label">Enter ID:</label>
                                          <input type="text" class="form-control" id="editPayrollId" placeholder="Enter ID">
                                      </div>
                                      <div class="mb-3">
                                          <label for="editPayrollSelect" class="form-label">Select Field to Edit:</label>
                                          <select class="form-select" id="editPayrollSelect">
                                              <option value="" disabled selected hidden>Choose...</option>
                                              <option value="salary_type">Salary Type</option>
                                              <option value="bank_account_name">Bank Account Name</option>
                                              <option value="bank_account_no">Bank Account No</option>
                                              <option value="employee_salary">Employee Salary</option>
                                              <option value="bank_name">Bank Name</option>
                                          </select>
                                      </div>
                                      <div class="mb-3" id="payrollValueField">
                                          <!-- This div will be dynamically populated based on the selected field -->
                                      </div>
                                  </form>
                              </div>
                              <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                  <button type="button" class="btn btn-primary" id="savePayrollBtn">Save Changes</button>
                              </div>
                          </div>
                      </div>
                  </div><!-- End Edit Data Modal-->
                </div>
                <div class="tab-pane fade" id="bordered-document" role="tabpanel" aria-labelledby="document-tab">
                  <table class="table datatable">
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

                        // Prepare the SQL query to select data from the employeelogin table
                        $query = "SELECT document_id, education_level, ic_picture FROM employee_document";

                        // Execute the query
                        $result = mysqli_query($con, $query);

                        // Check if there are any rows returned
                        if (mysqli_num_rows($result) > 0) {
                            // Fetch each row of data and display it in the table
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

                        // Close the connection
                        mysqli_close($con);
                        ?>
                    </tbody>
                </table>  
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editDocumentModal">
                      Edit Employee Document
                  </button>

                  <!-- Modal for editing data -->
                  <div class="modal fade" id="editDocumentModal" tabindex="-1" data-bs-backdrop="false">
                      <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content">
                              <div class="modal-header">
                                  <h5 class="modal-title">Edit Data</h5>
                                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                  <!-- Form for entering ID, selecting field, and entering new value -->
                                  <form id="editDataForm">
                                      <div class="mb-3">
                                          <label for="editIdInput" class="form-label">Enter ID:</label>
                                          <input type="text" class="form-control" id="editdocumentid" placeholder="Enter ID">
                                      </div>
                                      <div class="mb-3">
                                          <label for="editFieldSelect" class="form-label">Select Field to Edit:</label>
                                          <select class="form-select" id="editdocumentfile">
                                              <option value="" disabled selected hidden>Choose...</option>
                                              <option value="education_level">Education Level</option>
                                              <option value="ic_picture">IC</option>
                                          </select>
                                      </div>
                                      <div class="mb-3" id="documentValueField">
                                          <!-- This div will be dynamically populated based on the selected field -->
                                      </div>
                                  </form>
                              </div>
                              <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                  <button type="button" class="btn btn-primary" id="saveDocumentBtn">Save Changes</button>
                              </div>
                          </div>
                      </div>
                  </div><!-- End Edit Data Modal-->              
              </div>

              <div class="tab-pane fade" id="bordered-leave" role="tabpanel" aria-labelledby="leave-tab">
                  <table class="table datatable">
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

                        // Prepare the SQL query to select data from the employeelogin table
                        $query = "SELECT * FROM leave_info";

                        // Execute the query
                        $result = mysqli_query($con, $query);

                        // Check if there are any rows returned
                        if (mysqli_num_rows($result) > 0) {
                            // Fetch each row of data and display it in the table
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

                        // Close the connection
                        mysqli_close($con);
                        ?>
                    </tbody>
                </table>  
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editLeaveModal">
                      Edit Leave Information
                  </button>

                  <!-- Modal for editing data -->
                  <div class="modal fade" id="editLeaveModal" tabindex="-1" data-bs-backdrop="false">
                      <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content">
                              <div class="modal-header">
                                  <h5 class="modal-title">Edit Data</h5>
                                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                  <!-- Form for entering ID, selecting field, and entering new value -->
                                  <form id="editDataForm">
                                      <div class="mb-3">
                                          <label for="editIdInput" class="form-label">Enter ID:</label>
                                          <input type="text" class="form-control" id="editleaveid" placeholder="Enter ID">
                                      </div>
                                      <div class="mb-3">
                                          <label for="editFieldSelect" class="form-label">Select Field to Edit:</label>
                                          <select class="form-select" id="editleavefile">
                                              <option value="" disabled selected hidden>Choose...</option>
                                              <option value="annual_leave">Annual Leave</option>
                                              <option value="sick_leave">Sick Leave</option>
                                              <option value="hospitalization_leave">Hospitalization Leave</option>
                                          </select>
                                      </div>
                                      <div class="mb-3">
                                          <label for="editNewValueInput" class="form-label">Enter New Value:</label>
                                          <input type="text" class="form-control" id="editleavevalue" placeholder="Enter New Value">
                                      </div>
                                  </form>
                              </div>
                              <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                  <button type="button" class="btn btn-primary" id="saveLeaveBtn">Save Changes</button>
                              </div>
                          </div>
                      </div>
                  </div><!-- End Edit Data Modal-->              
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
  <script>
    document.getElementById('saveChangesBtn').addEventListener('click', function() {
        // Show the success message
        document.getElementById('successMessage').style.display = 'block';
        // Hide the modal after a short delay (for better user experience)
        setTimeout(function() {
            $('#disablebackdrop').modal('hide');
        }, 2000); // Adjust the delay time as needed (2000 milliseconds = 2 seconds)
    });
  </script>
</body>

</html>