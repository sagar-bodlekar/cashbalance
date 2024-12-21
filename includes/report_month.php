<?php
session_start();
require '../vendor/autoload.php';
require_once 'error_handler.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

// एक्सेल फाइल चेक करें
if (!file_exists('../data/transactions.xlsx')) {
    CustomError::show404();
}

try {
    $reader = new Xlsx();
    $spreadsheet = $reader->load('../data/transactions.xlsx');
    
    // अगर कोई डेटा नहीं है
    if ($spreadsheet->getActiveSheet()->getHighestRow() <= 1) {
        CustomError::show404();
    }
    
    // बाकी का कोड...
    
} catch (Exception $e) {
    CustomError::show404();
}
