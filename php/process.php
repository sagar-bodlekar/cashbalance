<?php
session_start();
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if(isset($_POST['submit_received']) || isset($_POST['submit_expense'])) {
    try {
        // Validate inputs
        if(empty($_POST['date']) || empty($_POST['description']) || empty($_POST['amount']) || !isset($_POST['opening_balance'])) {
            throw new Exception('सभी फ़ील्ड भरना आवश्यक हैं');
        }

        $date = $_POST['date'];
        $description = $_POST['description']; 
        $amount = floatval($_POST['amount']);
        $opening_balance = floatval($_POST['opening_balance']);
        
        if($amount <= 0) {
            throw new Exception('राशि 0 से अधिक होनी चाहिए');
        }
        
        $is_received = isset($_POST['submit_received']);
        
        // check if data folder and
        if (!file_exists('../data')) {
            mkdir('../data', 0777, true);
        }
        
        $file_path = '../data/transactions.xlsx';
        
        if(file_exists($file_path)) {
            // check file exists or not
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($file_path);
            
            // check date wise sheet
            if($spreadsheet->sheetNameExists($date)) {
                $worksheet = $spreadsheet->getSheetByName($date);
            } else {
                // create new sheet with current date
                $worksheet = $spreadsheet->createSheet();
                $worksheet->setTitle($date);
                
                // join header
                $worksheet->setCellValue('A1', 'दिनांक');
                $worksheet->setCellValue('B1', 'विवरण');
                $worksheet->setCellValue('C1', 'प्रापंभिक शेष');
                $worksheet->setCellValue('D1', 'प्राप्त');
                $worksheet->setCellValue('E1', 'खर्च');
                $worksheet->setCellValue('F1', 'शेष');
            }
        } else {
            // create new file
            $spreadsheet = new Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setTitle($date);
            
            // join header
            $worksheet->setCellValue('A1', 'दिनांक');
            $worksheet->setCellValue('B1', 'विवरण');
            $worksheet->setCellValue('C1', 'प्रापंभिक शेष');
            $worksheet->setCellValue('D1', 'प्राप्त');
            $worksheet->setCellValue('E1', 'खर्च');
            $worksheet->setCellValue('F1', 'शेष');
        }
        
        // last entry comes from last sheet
        $worksheet = $spreadsheet->getSheetByName($date);
        $lastRow = $worksheet->getHighestRow();
        $newRow = $lastRow + 1;
        
        // new entry
        $worksheet->setCellValue('A' . $newRow, $date);
        $worksheet->setCellValue('B' . $newRow, $description);
        $worksheet->setCellValue('C' . $newRow, $opening_balance);
        
        if($is_received) {
            $worksheet->setCellValue('D' . $newRow, $amount);
            $worksheet->setCellValue('E' . $newRow, '');
            $final_balance = $opening_balance + $amount;
        } else {
            $worksheet->setCellValue('D' . $newRow, '');
            $worksheet->setCellValue('E' . $newRow, $amount);
            $final_balance = $opening_balance - $amount;
        }
        
        $worksheet->setCellValue('F' . $newRow, $final_balance);
        
        // check permission before entry
        if (file_exists($file_path)) {
            if (!is_writable($file_path)) {
                throw new Exception('फ़ाइल में लिखने की अनुमति नहीं है');
            }
        } else if (!is_writable(dirname($file_path))) {
            throw new Exception('डायरेक्टरी में लिखने की अनुमति नहीं है');
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($file_path);
        
        $_SESSION['message'] = $is_received ? 
            "₹{$amount} की राशि सफलतापूर्वक जमा की गई" : 
            "₹{$amount} का खर्च सफलतापूर्वक दर्ज किया गया";
        $_SESSION['message_type'] = 'success';
        
    } catch(Exception $e) {
        $_SESSION['message'] = 'त्रुटि: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: ../index.php');
    exit();
}
?>
