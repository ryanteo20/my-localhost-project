<?php
require('database.php');
require('session.php');

// FPDF library
require('fpdf/fpdf.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the logged-in user's ID
$employee_id = $_SESSION['ID'];

// Initialize variables
$payslip_data = null;
$month = isset($_POST['month']) ? (int)$_POST['month'] : date('m');
$year = isset($_POST['year']) ? (int)$_POST['year'] : date('Y');
$no_data_found = false;

// Handle PDF download for specific month/year
if (isset($_GET['download']) && $_GET['download'] == 'pdf') {
    $download_month = (int)$_GET['month'];
    $download_year = (int)$_GET['year'];
    
    // Fetch specific payroll data for download
    $query = "SELECT pt.*, el.username AS employee_name, pi.full_name, pi.ic, 
                     ed.employment_position, ed.employment_department
              FROM payroll_transactions pt
              JOIN employeelogin el ON pt.employee_id = el.ID
              LEFT JOIN personal_information pi ON pt.employee_id = pi.personal_id
              LEFT JOIN employment_detail ed ON pt.employee_id = ed.employment_id
              WHERE pt.employee_id = ? 
              AND MONTH(pt.pay_period_start) = ? 
              AND YEAR(pt.pay_period_start) = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iii", $employee_id, $download_month, $download_year);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($emp = mysqli_fetch_assoc($result)) {
        // Calculate values including claims
        $basic_salary = $emp['basic_salary'];
        $allowances = $emp['allowances'] ?? 0;
        $overtime_pay = $emp['overtime_pay'] ?? 0;
        $total_claims = $emp['total_claims'] ?? 0;
        
        // Calculate gross pay including claims
        $gross_pay = $basic_salary + $allowances + $overtime_pay + $total_claims;
        
        $employee_epf = $emp['epf_amount'];
        $employee_socso = $emp['socso_amount'];
        $employee_eis = $emp['eis_amount'];
        $total_deductions = $employee_epf + $employee_socso + $employee_eis + $emp['tax_amount'];
        
        // Employer contributions
        $employer_epf = $basic_salary * 0.13;
        $employer_socso = $basic_salary * 0.0175;
        $employer_eis = $basic_salary * 0.002;
        $total_contributions = $employer_epf + $employer_socso + $employer_eis;
        
        $month_name = date("F", mktime(0, 0, 0, $download_month, 10));
        
        // Generate PDF using same format as individual payslip
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetMargins(20, 20, 20);
        
        // Company Name
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 12, 'SMEasyHR', 0, 1, 'L');
        $pdf->Ln(5);
        
        // Employee info and NET PAY section
        $pdf->SetFont('Arial', '', 11);
        
        // Left side - Employee details
        $pdf->SetXY(20, 35);
        $employee_name = $emp['full_name'] ?? $emp['employee_name'] ?? 'Unknown Employee';
        $pdf->Cell(100, 6, $employee_name . ' (Employee No: ' . $emp['employee_id'] . ')', 0, 1, 'L');
        $pdf->Cell(100, 6, "Period: $month_name $download_year", 0, 1, 'L');
        $pdf->Cell(100, 6, 'Position: ' . ($emp['employment_position'] ?? 'Not specified'), 0, 1, 'L');
        $pdf->Cell(100, 6, 'Dept: ' . ($emp['employment_department'] ?? 'Not specified'), 0, 1, 'L');
        $pdf->Cell(100, 6, 'IC/Passport: ' . ($emp['ic'] ?? 'Not provided'), 0, 1, 'L');
        
        // Right side - NET PAY box
        $pdf->SetXY(130, 35);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetDrawColor(45, 123, 251);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(45, 123, 251);
        $pdf->Cell(60, 20, 'NET PAY', 1, 2, 'C', true);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetX(130);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(60, 15, 'RM ' . number_format($emp['net_pay'], 2), 1, 1, 'C', true);
        $pdf->SetDrawColor(0, 0, 0);

        // Reset position for main content
        $pdf->SetY(90);
        $pdf->SetFont('Arial', '', 10);
        
        // Employee Earnings/Reimbursements section
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(95, 8, 'Employee Earnings/Reimbursements', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 6, '', 0, 0);
        $pdf->Cell(35, 6, 'Current', 0, 1, 'R');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(60, 6, 'Basic', 0, 0, 'L');
        $pdf->Cell(35, 6, 'RM ' . number_format($basic_salary, 2), 0, 1, 'R');
        
        if ($allowances > 0) {
            $pdf->Cell(60, 6, 'Allowances', 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($allowances, 2), 0, 1, 'R');
        }
        
        if ($overtime_pay > 0) {
            $pdf->Cell(60, 6, 'Overtime', 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($overtime_pay, 2), 0, 1, 'R');
        }
        
        // Add claims to the payslip
        if ($total_claims > 0) {
            $pdf->Cell(60, 6, 'Claims', 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($total_claims, 2), 0, 1, 'R');
        }
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 6, 'Gross Pay', 0, 0, 'L');
        $pdf->Cell(35, 6, 'RM ' . number_format($gross_pay, 2), 0, 1, 'R');
        
        // Employee Deductions section
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(95, 8, 'Employee Deductions', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 6, '', 0, 0);
        $pdf->Cell(35, 6, 'Current', 0, 1, 'R');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(60, 6, 'Employee EPF', 0, 0, 'L');
        $pdf->Cell(35, 6, 'RM ' . number_format($employee_epf, 2), 0, 1, 'R');
        
        $pdf->Cell(60, 6, 'Employee SOCSO', 0, 0, 'L');
        $pdf->Cell(35, 6, 'RM ' . number_format($employee_socso, 2), 0, 1, 'R');
        
        $pdf->Cell(60, 6, 'Employee EIS', 0, 0, 'L');
        $pdf->Cell(35, 6, 'RM ' . number_format($employee_eis, 2), 0, 1, 'R');
        
        if ($emp['tax_amount'] > 0) {
            $pdf->Cell(60, 6, 'Income Tax', 0, 0, 'L');
            $pdf->Cell(35, 6, 'RM ' . number_format($emp['tax_amount'], 2), 0, 1, 'R');
        }
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 6, 'Total Deductions', 0, 0, 'L');
        $pdf->Cell(35, 6, 'RM ' . number_format($total_deductions, 2), 0, 1, 'R');
        $pdf->Ln(3);
        
        // Company Contributions section
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(95, 8, 'Company Contributions', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 6, '', 0, 0);
        $pdf->Cell(35, 6, 'Current', 0, 1, 'R');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(60, 6, "Employer EPF", 0, 0, 'L');
        $pdf->Cell(35, 6, 'RM ' . number_format($employer_epf, 2), 0, 1, 'R');
        
        $pdf->Cell(60, 6, "Employer SOCSO", 0, 0, 'L');
        $pdf->Cell(35, 6, 'RM ' . number_format($employer_socso, 2), 0, 1, 'R');
        
        $pdf->Cell(60, 6, "Employer EIS", 0, 0, 'L');
        $pdf->Cell(35, 6, 'RM ' . number_format($employer_eis, 2), 0, 1, 'R');

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 6, 'Total Contributions', 0, 0, 'L');
        $pdf->Cell(35, 6, 'RM ' . number_format($total_contributions, 2), 0, 1, 'R');
        
        // Footer
        $pdf->SetY(-40);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 6, 'This payslip is computer generated. No signature is required.', 0, 1, 'C');
        $pdf->Cell(0, 6, 'Printed on: ' . date('d/m/Y'), 0, 1, 'C');
        
        // Generate filename for download
        $safe_employee_name = preg_replace('/[^A-Za-z0-9_\-\s]/', '', $employee_name);
        $safe_employee_name = str_replace(' ', '_', $safe_employee_name);
        $filename = "Payslip_{$safe_employee_name}_{$month_name}_{$download_year}.pdf";
        
        // Clear any output buffers and set proper headers
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        // Set correct headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        header('Cache-Control: private, no-transform, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output PDF directly
        $pdf->Output('D', $filename);
        exit();
    }
    mysqli_stmt_close($stmt);
}

// Fetch all payroll data for the employee for the selected year
if ($employee_id && $year) {
    $query = "SELECT pt.*, 
                     MONTH(pt.pay_period_start) as payroll_month,
                     YEAR(pt.pay_period_start) as payroll_year
              FROM payroll_transactions pt
              WHERE pt.employee_id = ? 
              AND YEAR(pt.pay_period_start) = ?
              ORDER BY pt.pay_period_start DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $employee_id, $year);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $payslip_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        $no_data_found = true;
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SMEasyHR - Employee</title>
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <?php include 'includes/chatbot-includes.php'; ?>
</head>

<body>
  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center">
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
              <i class="bi bi-circle"></i><span>View All Claim Request</span>
            </a>
          </li>
        </ul>
      </li><!-- End Claim Management Nav -->
    </ul>

  </aside>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>My Payslips</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index2.php">Home</a></li>
          <li class="breadcrumb-item">Payroll</li>
          <li class="breadcrumb-item active">Check Payslip</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">View My Payslips</h5>

              <!-- Filter Form -->
              <form method="POST" class="row g-3 align-items-end mb-4">
                <div class="col-md-4">
                  <label for="year" class="form-label">Select Year</label>
                  <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                    <?php
                    for ($y = 2023; $y <= date('Y'); $y++) {
                        $selected = ($y == $year) ? 'selected' : '';
                        echo "<option value='$y' $selected>$y</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="col-md-8">
                  <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> Showing payslips for <strong><?php echo $year; ?></strong>
                  </div>
                </div>
              </form>

              <!-- Payslips Table -->
              <?php if ($payslip_data): ?>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th scope="col">Month</th>
                        <th scope="col">Basic Salary</th>
                        <th scope="col">Allowances</th>
                        <th scope="col">Overtime</th>
                        <th scope="col">Claims</th>
                        <th scope="col">Total Contributions</th>
                        <th scope="col" class="text-end">Net Pay</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($payslip_data as $payroll): ?>
                        <?php
                        $monthName = date("F", mktime(0, 0, 0, $payroll['payroll_month'], 10));
                        $basic_salary = $payroll['basic_salary'];
                        $allowances = $payroll['allowances'] ?? 0;
                        $overtime_pay = $payroll['overtime_pay'] ?? 0;
                        $total_claims = $payroll['total_claims'] ?? 0;
                        $gross_pay = $basic_salary + $allowances + $overtime_pay + $total_claims;
                        $total_deductions = $payroll['epf_amount'] + $payroll['socso_amount'] + $payroll['eis_amount'] + $payroll['tax_amount'];
                        
                        // Status styling
                        $statusClass = '';
                        switch(strtolower($payroll['status'])) {
                            case 'confirmed':
                                $statusClass = 'badge bg-success';
                                break;
                            case 'pending':
                                $statusClass = 'badge bg-warning text-dark';
                                break;
                            default:
                                $statusClass = 'badge bg-secondary';
                        }
                        ?>
                        <tr>
                          <td><strong><?php echo $monthName; ?></strong></td>
                          <td>RM <?php echo number_format($basic_salary, 2); ?></td>
                          <td>RM <?php echo number_format($allowances, 2); ?></td>
                          <td>RM <?php echo number_format($overtime_pay, 2); ?></td>
                          <td>RM <?php echo number_format($total_claims, 2); ?></td>
                          <td>RM <?php echo number_format($total_deductions, 2); ?></td>
                          <td class="text-end"><strong class="text-primary">RM <?php echo number_format($payroll['net_pay'], 2); ?></strong></td>
                          <td><span class="<?php echo $statusClass; ?>"><?php echo ucfirst($payroll['status']); ?></span></td>
                          <td>
                            <?php if (strtolower($payroll['status']) === 'confirmed'): ?>
                              <a href="?download=pdf&month=<?php echo $payroll['payroll_month']; ?>&year=<?php echo $payroll['payroll_year']; ?>" 
                                 class="btn btn-sm btn-outline-success" 
                                 title="Download Payslip">
                                <i class="bi bi-download"></i> Download
                              </a>
                            <?php else: ?>
                              <span class="text-muted small">Not available</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <!-- Summary Card -->
                <div class="row mt-4">
                  <div class="col-md-12">
                    <div class="card bg-light">
                      <div class="card-body">
                        <h6 class="card-title">Year <?php echo $year; ?> Summary</h6>
                        <div class="row">
                          <div class="col-md-3">
                            <div class="text-center">
                              <div class="fs-4 fw-bold text-primary">
                                RM <?php echo number_format(array_sum(array_column($payslip_data, 'net_pay')), 2); ?>
                              </div>
                              <div class="text-muted">Total Net Pay</div>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="text-center">
                              <div class="fs-4 fw-bold text-success">
                                <?php echo count($payslip_data); ?>
                              </div>
                              <div class="text-muted">Payslips Generated</div>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="text-center">
                              <div class="fs-4 fw-bold text-warning">
                                RM <?php echo number_format(array_sum(array_map(function($p) { 
                                  return ($p['epf_amount'] + $p['socso_amount'] + $p['eis_amount'] + $p['tax_amount']); 
                                }, $payslip_data)), 2); ?>
                              </div>
                              <div class="text-muted">Total Contributions</div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              <?php elseif ($no_data_found): ?>
                <div class="alert alert-warning">
                  <i class="bi bi-exclamation-triangle"></i> No payroll data found for year <?php echo $year; ?>.
                </div>
              <?php else: ?>
                <div class="alert alert-info">
                  <i class="bi bi-info-circle"></i> Select a year to view your payslips.
                </div>
              <?php endif; ?>

            </div>
          </div>

        </div>
      </div>
    </section>

  </main><!-- End #main -->

  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>SMEasyHR</span></strong>. All Rights Reserved
    </div>
  </footer>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.min.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/js/main.js"></script>

</body>

</html>