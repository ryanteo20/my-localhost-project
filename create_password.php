<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>SMEasyHR - Create Password</title>
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

  <main>
    <div class="container">

      <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

              <div class="d-flex justify-content-center py-4">
                <a href="index.html" class="logo d-flex align-items-center w-auto">
                  <img src="assets/img/logo.png" alt="">
                  <span class="d-none d-lg-block">SMEasyHR</span>
                </a>
              </div>
              <?php
              // Include the database connection
              require('database.php');
              session_start();
              echo "Username from session: " . $_SESSION['username'];

              // Check if the form is submitted
              if (isset($_POST['password'])) {
                // Debug: Output username from form
                echo "Username from form: " . $_POST['username'];
            
                // Sanitize and escape input data
                $password = mysqli_real_escape_string($con, $_POST['password']);
                $confirmPassword = mysqli_real_escape_string($con, $_POST['confirm_password']);
            
                // Check if passwords match
                if ($password === $confirmPassword) {
                    // Hash the password securely
                    $hashedPassword = md5($password);
            
                    // Update the user's password in the database
                    $query = "UPDATE employeelogin SET password='$hashedPassword', first_login=0 WHERE username='$_SESSION[username]'";
                    mysqli_query($con, $query);
            
                    header("Location: pages-login.php");
                    exit(); // Ensure that no further code is executed after redirection
                } else {
                    // Passwords don't match, display error message
                    echo "<div class='form'>
                            <h3>Passwords don't match. Please try again.</h3>
                            <br/>Click here to <a href='create_password.php'>Create Password</a>
                          </div>";
                }
            }
             else {
                  // If form is not submitted, show the password creation form
                  ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="pt-4 pb-2">
                            <h5 class="card-title text-center pb-0 fs-4">Create Your Password</h5>
                            <p class="text-center small">Enter a new password</p>
                        </div>
                          <form class="row g-3 needs-validation" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="col-12">
                          <label for="password" class="form-label">Password</label>
                          <input type="password" name="password" class="form-control" id="yourPassword" required>
                          <div class="invalid-feedback">Please enter your password!</div>
                        </div>
                        <div class="col-12">
                          <label for="confirm_password" class="form-label">Confirm Password</label>
                          <input type="password" name="confirm_password" class="form-control" id="confirmPassword" required>
                          <div class="invalid-feedback">Please confirm your password!</div>
                        </div>
                        <div class="col-12">
                          <button class="btn btn-primary w-100" type="submit">Create Password</button>
                        </div>
                      </form>
                    </div>
                    </div>
                  <?php
                }
              ?>
            </div>
          </div>
        </div>

      </section>

    </div>
  </main>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.min.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

</body>
