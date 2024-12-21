<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>
    <title>कैश बैलेंस सिस्टम</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: bold;
        }

        input[type="number"],
        input[type="date"],
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus {
            border-color: #3498db;
            outline: none;
        }

        .balance-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-size: 18px;
            color: #2c3e50;
            font-weight: bold;
            text-align: center;
            border: 2px solid #3498db;
        }

        button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
        }

        .btn-received {
            background: #2ecc71;
            color: white;
        }

        .btn-expense {
            background: #e74c3c;
            color: white;
        }

        .report-link {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
        }

        .report-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
            display: flex;
        }

        .report-link a:hover {
            text-decoration: underline;
        }

        .message {
            padding: 15px;
            margin: 0 0 20px 0;
            border-radius: 8px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
    <script>
        <?php if (isset($_SESSION['message'])): ?>
            window.onload = function() {
                <?php if ($_SESSION['message_type'] == 'success'): ?>
                    alert('सफलता: <?php echo $_SESSION['message']; ?>');
                <?php else: ?>
                    alert('त्रुटि: <?php echo $_SESSION['message']; ?>');
                <?php endif; ?>
            }
        <?php endif; ?>
    </script>
</head>

<body>
    <div class="container">
        <h2>दैनिक लेनदेन फॉर्म</h2>
        <?php
        if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>
        <?php
        $final_balance = 0;
        $today = date('Y-m-d');
        $is_first_entry = true;

        if (file_exists('data/transactions.xlsx')) {
            require 'vendor/autoload.php';
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load('data/transactions.xlsx');

            // Get the last sheet's balance
            $sheetCount = $spreadsheet->getSheetCount();
            if ($sheetCount > 0) {
                $lastSheet = $spreadsheet->getSheet($sheetCount - 1);
                $lastRow = $lastSheet->getHighestRow();
                if ($lastRow > 1) {
                    $final_balance = $lastSheet->getCell('F' . $lastRow)->getValue();
                    $is_first_entry = false;
                }
            }

            // Check if sheet exists for today's date
            if ($spreadsheet->sheetNameExists($today)) {
                $worksheet = $spreadsheet->getSheetByName($today);
                $lastRow = $worksheet->getHighestRow();
                if ($lastRow > 1) {
                    $final_balance = $worksheet->getCell('F' . $lastRow)->getValue();
                }
            } else {
                // Create new sheet for today
                $worksheet = $spreadsheet->createSheet();
                $worksheet->setTitle($today);
                // Add headers to new sheet
                $worksheet->setCellValue('A1', 'दिनांक');
                $worksheet->setCellValue('B1', 'विवरण');
                $worksheet->setCellValue('C1', 'प्रापंभिक शेष');
                $worksheet->setCellValue('D1', 'प्राप्त');
                $worksheet->setCellValue('E1', 'खर्च');
                $worksheet->setCellValue('F1', 'शेष');
            }
        }

        // Display messages if any
        if (isset($_SESSION['message'])) {
            $messageClass = ($_SESSION['message_type'] === 'error') ? 'error' : 'success';
            echo "<div class='message {$messageClass}'>";
            echo $_SESSION['message'];
            echo "</div>";
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>
        <form action="php/process.php" method="post">
            <div class="form-group">
                <label>प्रारंभिक शेष:</label>
                <?php if ($is_first_entry): ?>
                    <input type="number" name="opening_balance" required>
                <?php else: ?>
                    <div class="balance-display">₹ <?php echo number_format($final_balance, 2); ?></div>
                    <input type="hidden" name="opening_balance" value="<?php echo $final_balance; ?>">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>दिनांक:</label>
                <input type="date" name="date" value="<?php echo $today; ?>" readonly>
            </div>

            <div class="form-group">
                <label>राशि:</label>
                <input type="number" name="amount" step="0.01" required>
            </div>

            <div class="form-group">
                <label>विवरण:</label>
                <input type="text" name="description" required>
            </div>

            <div class="form-group">
                <button type="submit" name="submit_received" class="btn-received">प्राप्त जमा करें</button>
                <button type="submit" name="submit_expense" class="btn-expense">खर्च जमा करें</button>
            </div>
        </form>

        <div class="report-link">
            <a href="includes/report_day.php">दैनिक रिपोर्ट देखें</a>
            <br>
            <a href="includes/report_month.php">मासिक रिपोर्ट देखें</a>
        </div>
    </div>
</body>

</html>