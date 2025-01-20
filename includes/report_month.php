<?php
session_start();
require '../vendor/autoload.php';
require_once 'error_handler.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

// excel file check 
if (!file_exists('../data/transactions.xlsx')) {
    CustomError::show404();
}

try {
    $reader = new Xlsx();
    $spreadsheet = $reader->load('../data/transactions.xlsx');
    
    // if are not match 
    if ($spreadsheet->getActiveSheet()->getHighestRow() <= 1) {
        CustomError::show404();
    }
    
// blank for now!!
    
} catch (Exception $e) {
    CustomError::show404();
}
