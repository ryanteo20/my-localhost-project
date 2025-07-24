<?php
require 'vendor/autoload.php'; // adjust path if needed

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Format if needed
    $start_formatted = date('d/m/Y', strtotime($start_date));
    $end_formatted = date('d/m/Y', strtotime($end_date));

    // 🔹 Create Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Claim Summary Report');

    // 🔹 Example headers
    $sheet->setCellValue('A1', 'Employee');
    $sheet->setCellValue('B1', 'Claim Date');
    $sheet->setCellValue('C1', 'Amount');
    $sheet->setCellValue('D1', 'Status');

    // 🔹 Example data: Replace with real DB query!
    $sheet->setCellValue('A2', 'Ryan Maximillian');
    $sheet->setCellValue('B2', $start_formatted . ' - ' . $end_formatted);
    $sheet->setCellValue('C2', 'MYR 150.70');
    $sheet->setCellValue('D2', 'Approved');

    // 🔹 Send XLSX file to browser
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Claim_Summary.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
