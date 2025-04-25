<?php
require('database.php');
require('session.php');

function handlePersonalInformation($con) {
  if (isset($_REQUEST['inputName5'])) {
      // Retrieve form data
      $fullName = stripslashes($_REQUEST['inputName5']);
      $fullName = mysqli_real_escape_string($con, $fullName);
      $email = stripslashes($_REQUEST['inputEmail5']);
      $email = mysqli_real_escape_string($con, $email);
      $phoneNumber = stripslashes($_REQUEST['inputNumber5']);
      $phoneNumber= mysqli_real_escape_string($con, $phoneNumber);
      $ic = stripslashes($_REQUEST['inputIC5']);
      $ic= mysqli_real_escape_string($con, $ic);
      $address = stripslashes($_REQUEST['inputAddress5']);
      $address = mysqli_real_escape_string($con, $address);
      $address2 = stripslashes($_REQUEST['inputAddress2']);
      $address2 = mysqli_real_escape_string($con, $address2);
      $zip = stripslashes($_REQUEST['inputZip']);
      $zip = mysqli_real_escape_string($con, $zip);
      $city = stripslashes($_REQUEST['inputCity']);
      $city = mysqli_real_escape_string($con, $city);
      $state = stripslashes($_REQUEST['inputState']);
      $state = mysqli_real_escape_string($con, $state);
      $race = stripslashes($_REQUEST['inputRace']);
      $race = mysqli_real_escape_string($con, $race);
      $religion = stripslashes($_REQUEST['inputReligion']);
      $religion = mysqli_real_escape_string($con, $religion);
      $marital = stripslashes($_REQUEST['inputMarital']);
      $marital = mysqli_real_escape_string($con, $marital);
      $gender = stripslashes($_REQUEST['inputGender']);
      $gender = mysqli_real_escape_string($con, $gender);

      // SQL statement
      $query = "INSERT INTO `personal_information` (full_name, email, phone_number, ic, address, address2, zip, city, state, race, religion, marital, gender) 
      VALUES ('$fullName', '$email', '$phoneNumber', '$ic', '$address', '$address2', '$zip', '$city', '$state', '$race', '$religion', '$marital', '$gender')";
      $result = mysqli_query($con, $query);

      if ($result) {
          return "Personal information saved successfully.";
      } else {
          return "Error: " . mysqli_error($con);
      }
  }
}

$success_message = handlePersonalInformation($con);
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

  </aside><!-- End Sidebar-->

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Add Employee</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">Employee Management</li>
          <li class="breadcrumb-item active"><a href="add.php">Add Employee</a></li>
          <li class="breadcrumb-item active"><a href="add_p.php">Personal Information</a></li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <div id="form-container">
    <div class="card" id="employee-info-form">
        <div class="card-body">
            <h5 class="card-title">2. Register New Employee Personal Information</h5>
            <!-- Personal Information Form -->
            <form id="employee-info" class="row g-3">
                <div class="col-md-6">
                  <label for="inputName5" class="form-label">Full Name</label>
                  <input type="text" class="form-control" id="inputName5" name="inputName5">                
                </div>
                <div class="col-md-6">
                  <label for="inputEmail5" class="form-label">Email</label>
                  <input type="email" class="form-control" id="inputEmail5" name="inputEmail5">
                </div>
                <div class="col-md-6">
                  <label for="inputNumber5" class="form-label">Phone Number</label>
                  <input type="text" class="form-control" id="inputNumber5" name="inputNumber5">
                </div>
                <div class="col-md-6">
                  <label for="inputIC5" class="form-label">NRIC No</label>
                  <input type="text" class="form-control" id="inputIC5" name="inputIC5">
                </div>
                <div class="col-12">
                  <label for="inputAddress5" class="form-label">Permanent Address</label>
                  <input type="text" class="form-control" id="inputAddress5" name="inputAddress5">
                </div>
                <div class="col-12">
                  <label for="inputAddress2" class="form-label">Permanent Address 2</label>
                  <input type="text" class="form-control" id="inputAddress2" name="inputAddress2">
                </div>
                <div class="col-md-2">
                  <label for="inputZip" class="form-label">Zip</label>
                  <input type="text" class="form-control" id="inputZip" name="inputZip">
                </div>
                <div class="col-md-6">
                  <label for="inputCity" class="form-label">City</label>
                  <input type="text" class="form-control" id="inputCity" name="inputCity">
                </div>
                <div class="col-md-4">
                  <label for="inputState" class="form-label">State</label>
                  <select id="inputState" class="form-select" name="inputState">
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
                <div class="col-md-3">
                  <label for="inputRace" class="form-label">Race</label>
                  <select id="inputRace" class="form-select" name="inputRace">
                    <option value="" disabled selected hidden>Choose...</option>
                    <option value="Chinese">Chinese</option>
                    <option value="Malay">Malay</option>
                    <option value="Indian">Indian</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label for="inputReligion" class="form-label">Religion</label>
                  <select id="inputReligion" class="form-select" name="inputReligion">
                    <option value="" disabled selected hidden>Choose...</option>
                    <option value="Christian">Christian</option>
                    <option value="Islam">Islam</option>
                    <option value="Buddhist">Buddhist</option>
                    <option>Other</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label for="inputMarital" class="form-label">Marital Status</label>
                  <select id="inputMarital" class="form-select" name="inputMarital">
                    <option value="" disabled selected hidden>Choose...</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Divorced">Divorced</option>
                    <option>Other</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label for="inputGender" class="form-label">Gender</label>
                  <select id="inputGender" class="form-select" name="inputGender">
                    <option value="" disabled selected hidden>Choose...</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div class="text-center">
                <button type="reset" class="btn btn-secondary">Reset</button>
                <button type="submit" class="btn btn-primary" id="personalinformation-button">Submit</button>
                <button type="button" class="btn btn-secondary" id="next2-button">Next</button>
                </div>
            </form><!-- End Personal Information Form -->
        </div>
    </div>
</div>

<div class="card">
        <div class="card-body">
            <h5 class="card-title">Latest Personal Information Details</h5>

            <!-- Default Table -->
            <div style="overflow-y: auto;">
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

                      // Prepare the SQL query to select data from the personal_information table
                      $query = "SELECT personal_id, full_name, email, phone_number, ic, address, address2, zip, city, state, race, religion, marital, gender FROM personal_information ORDER BY personal_id DESC LIMIT 1";

                      // Execute the query
                      $result = mysqli_query($con, $query);

                      // Check if there are any rows returned
                      if ($result) {
                          // Check if there is at least one row returned
                          if (mysqli_num_rows($result) > 0) {
                              // Fetch each row of data and display it in the table
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
                      } else {
                          // If there's an error in the query execution, display the error message
                          echo "Error: " . mysqli_error($con);
                      }

                      // Close the connection
                      mysqli_close($con);
                      ?>
                  </tbody>
              </table>
          </div>
            <!-- End Default Table -->
        </div>
    </div>

  </main><!-- End #main -->
  <?php
  if (isset($success_message)) {
      // If a success message is set, output JavaScript to display an alert
      echo "<script>alert('$success_message');</script>";
  }
  ?>
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
//add personal information form
document.addEventListener('DOMContentLoaded', function() {
  const employeeForm = document.getElementById('employee-info');
  let formSubmitted = false;

  employeeForm.addEventListener('submit', function(event) {
      if (formSubmitted) {
          event.preventDefault();
          return;
      }

      formSubmitted = true;
  });

document.getElementById('next2-button').addEventListener('click', function() {
  window.location.href = 'add_d.php';
});
});
</script>

</body>

</html>