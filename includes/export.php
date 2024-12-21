<?php
// Clear any previous output
ob_clean();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set correct path for includes
define('ROOT_PATH', dirname(dirname(__FILE__)));
require ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/vendor/tecnickcom/tcpdf/tcpdf.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    $export_type = $_GET['type'] ?? 'excel';
    $date = $_GET['date'] ?? date('Y-m-d');

    // Get data from session
    if (!isset($_SESSION['export_data']) || empty($_SESSION['export_data'])) {
        throw new Exception('No data available for export');
    }

    $all_data = $_SESSION['export_data'];

    if ($export_type == 'excel') {
        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $sheet->setCellValue('A1', 'Date');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('C1', 'Opening Balance');
        $sheet->setCellValue('D1', 'Received');
        $sheet->setCellValue('E1', 'Expense');
        $sheet->setCellValue('F1', 'Balance');
        
        // Style header row
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '337AB7']],
            'alignment' => ['horizontal' => 'center'],
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        
        // Add data
        $row = 2;
        foreach ($all_data as $data) {
            $sheet->setCellValue('A' . $row, $data['date']);
            $sheet->setCellValue('B' . $row, $data['description']);
            $sheet->setCellValue('C' . $row, $data['opening_balance']);
            $sheet->setCellValue('D' . $row, $data['received']);
            $sheet->setCellValue('E' . $row, $data['expense']);
            $sheet->setCellValue('F' . $row, $data['balance']);
            $row++;
        }
        
        // Auto-size columns
        foreach(range('A','F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Download Excel file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Transactions_'.date('d-m-Y', strtotime($date)).'.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
    } elseif ($export_type == 'pdf') {
        class MYPDF extends TCPDF {
            public function Header() {
                $image_file = dirname(__FILE__) . '/../assets/logo.png';
                if(file_exists($image_file)) {
                    $this->Image($image_file, 10, 5, 40);
                }
                
                $this->SetFont('helvetica', 'B', 16);
                $this->Cell(0, 30, 'Daily Transaction Report', 0, false, 'C');
                
                $this->SetFont('helvetica', '', 12);
                $this->Cell(0, 45, 'Date: ' . date('d-m-Y'), 0, false, 'R');
                
                $this->Line(10, 40, 200, 40);
            }
        }

        // Create PDF
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Your System');
        $pdf->SetTitle('Transaction Report - ' . date('d-m-Y', strtotime($date)));
        $pdf->SetMargins(10, 45, 10);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();

        // Table header
        $header = array('Description', 'Opening Bal.', 'Received', 'Expense', 'Balance');
        $w = array(85, 25, 25, 25, 25);

        $pdf->SetFillColor(51, 122, 183);
        $pdf->SetTextColor(255);
        $pdf->SetFont('helvetica', 'B', 11);

        foreach($header as $i => $h) {
            $pdf->Cell($w[$i], 7, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Reset colors and font
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetTextColor(0);
        $pdf->SetFont('helvetica', '', 10);

        // Add data
        $fill = false;
        foreach ($all_data as $data) {
            $pdf->Cell($w[0], 6, $data['description'], 1, 0, 'L', $fill);
            $pdf->Cell($w[1], 6, 'Rs. '.number_format($data['opening_balance'], 2), 1, 0, 'R', $fill);
            $pdf->Cell($w[2], 6, $data['received'] ? 'Rs. '.number_format($data['received'], 2) : '-', 1, 0, 'R', $fill);
            $pdf->Cell($w[3], 6, $data['expense'] ? 'Rs. '.number_format($data['expense'], 2) : '-', 1, 0, 'R', $fill);
            $pdf->Cell($w[4], 6, 'Rs. '.number_format($data['balance'], 2), 1, 0, 'R', $fill);
            $pdf->Ln();
            $fill = !$fill;
        }

        // Output PDF
        ob_clean();
        $pdf->Output('Transaction_Report_'.date('d-m-Y', strtotime($date)).'.pdf', 'D');
        exit;
    }

} catch (Exception $e) {
    error_log('Export Error: ' . $e->getMessage());
    die('Error generating export: ' . $e->getMessage());
}
?>