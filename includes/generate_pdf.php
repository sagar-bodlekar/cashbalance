<?php
session_start();
require '../vendor/autoload.php';
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_GET['date'])) {
        throw new Exception('Date not found');
    }

    $date = $_GET['date'];

    // Read Excel file
    $reader = new Xlsx();
    $spreadsheet = $reader->load('../data/transactions.xlsx');
    $worksheet = $spreadsheet->getSheetByName($date);

    if (!$worksheet) {
        throw new Exception('Data not found for the selected date');
    }

    // Extend TCPDF with custom header
    class MYPDF extends TCPDF
    {
        public function Header()
        {
            // Add logo - using absolute path
            $image_file = dirname(__FILE__) . '/../assets/logo.png';  // Changed path

            // Debug line to check if file exists
            if (!file_exists($image_file)) {
                error_log('Logo file not found at: ' . $image_file);
            } else {
                // Add logo with error handling
                try {
                    $this->Image($image_file, 10, 5, 25); // x, y, width
                } catch (Exception $e) {
                    error_log('Error loading logo: ' . $e->getMessage());
                }
            }

            // Set font
            $this->SetFont('helvetica', 'B', 16);

            // Title
            $this->Cell(0, 30, 'Daily Transaction Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');

            // Draw a line
            $this->Line(10, 40, 200, 40);
        }
    }

    // Create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Your System');
    $pdf->SetTitle('Transaction Report - ' . date('d-m-Y', strtotime($date)));

    // Set margins
    $pdf->SetMargins(10, 45, 10); // Adjusted top margin to accommodate header
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);

    // Add a page
    $pdf->AddPage();

    // Add date below header
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Date: ' . date('d-m-Y', strtotime($date)), 0, 1, 'R');
    $pdf->Ln(5);

    // Table header
    $header = array('Description', 'Opening Bal.', 'Received', 'Expense', 'Balance');
    $w = array(85, 25, 25, 25, 25); // Adjusted widths (total = 185mm to fit on A4)

    // Colors for header
    $pdf->SetFillColor(51, 122, 183);
    $pdf->SetTextColor(255);
    $pdf->SetFont('helvetica', 'B', 11);

    // Print header with adjusted height and word wrap
    foreach ($header as $i => $h) {
        $pdf->Cell($w[$i], 10, $h, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Color and font restoration
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', '', 10); // Reduced font size

    // Data
    $total_received = 0;
    $total_expense = 0;
    $fill = false;

    $highestRow = $worksheet->getHighestRow();
    for ($row = 2; $row <= $highestRow; $row++) {
        $description = $worksheet->getCell('B' . $row)->getValue();
        $opening = $worksheet->getCell('C' . $row)->getValue();
        $received = $worksheet->getCell('D' . $row)->getValue();
        $expense = $worksheet->getCell('E' . $row)->getValue();
        $balance = $worksheet->getCell('F' . $row)->getValue();

        $total_received += floatval($received);
        $total_expense += floatval($expense);

        // Get the height needed for description
        $pdf->startTransaction();
        $start_y = $pdf->GetY();
        $start_page = $pdf->getPage();
        $pdf->MultiCell($w[0], 6, $description, 1, 'L', $fill, 0);
        $end_y = $pdf->GetY();
        $end_page = $pdf->getPage();
        $height = $end_y - $start_y;
        $pdf->rollbackTransaction(true);

        // Print row with consistent height
        $pdf->MultiCell($w[0], $height, $description, 1, 'L', $fill, 0);
        $pdf->Cell($w[1], $height, 'Rs. ' . number_format($opening, 2), 1, 0, 'R', $fill);
        $pdf->Cell($w[2], $height, $received ? 'Rs. ' . number_format($received, 2) : '-', 1, 0, 'R', $fill);
        $pdf->Cell($w[3], $height, $expense ? 'Rs. ' . number_format($expense, 2) : '-', 1, 0, 'R', $fill);
        $pdf->Cell($w[4], $height, 'Rs. ' . number_format($balance, 2), 1, 0, 'R', $fill);
        $pdf->Ln();
        $fill = !$fill;
    }

    // Total row with adjusted width
    $pdf->SetFillColor(51, 122, 183);
    $pdf->SetTextColor(255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(85, 7, 'Total', 1, 0, 'C', true);
    $pdf->Cell(25, 7, '', 1, 0, 'R', true);
    $pdf->Cell(25, 7, 'Rs. ' . number_format($total_received, 2), 1, 0, 'R', true);
    $pdf->Cell(25, 7, 'Rs. ' . number_format($total_expense, 2), 1, 0, 'R', true);
    $pdf->Cell(25, 7, 'Rs. ' . number_format($balance, 2), 1, 0, 'R', true);

    // Summary with adjusted width
    $pdf->Ln(15);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetTextColor(0);
    $pdf->Cell(0, 7, 'Daily Summary', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);

    $summary_data = array(
        array('Total Transactions', ($highestRow - 1)),
        array('Total Received', 'Rs. ' . number_format($total_received, 2)),
        array('Total Expense', 'Rs. ' . number_format($total_expense, 2)),
        array('Closing Balance', 'Rs. ' . number_format($balance, 2))
    );

    foreach ($summary_data as $row) {
        $pdf->Cell(60, 6, $row[0], 1, 0, 'L', true);
        $pdf->Cell(60, 6, $row[1], 1, 1, 'R', true);
    }

    // Clear any output that might have been sent
    if (ob_get_length()) ob_clean();

    // Send appropriate headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Transaction_Report_' . $date . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output PDF
    $pdf->Output('Transaction_Report_' . $date . '.pdf', 'D');
    exit;
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
