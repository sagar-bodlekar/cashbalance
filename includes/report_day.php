<?php
session_start();
require '../vendor/autoload.php';
require 'error_handler.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

// pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($page - 1) * $records_per_page;


// check excel file
if (!file_exists('../data/transactions.xlsx')) {
    CustomError::show404();
}

// read excel file
$reader = new Xlsx();
$spreadsheet = $reader->load('../data/transactions.xlsx');
$all_data = [];



try {
    $reader = new Xlsx();
    $spreadsheet = $reader->load('../data/transactions.xlsx');

    // check if data exists
    if ($spreadsheet->getActiveSheet()->getHighestRow() <= 1) {
        CustomError::show404();
    }

    // collect all data from all sheets
    foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
        $date = $worksheet->getTitle();
        $transactions = [];

        $highestRow = $worksheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $transactions[] = [
                'date' => $worksheet->getCell('A' . $row)->getValue(),
                'description' => $worksheet->getCell('B' . $row)->getValue(),
                'opening_balance' => $worksheet->getCell('C' . $row)->getValue(),
                'received' => $worksheet->getCell('D' . $row)->getValue(),
                'expense' => $worksheet->getCell('E' . $row)->getValue(),
                'balance' => $worksheet->getCell('F' . $row)->getValue()
            ];
        }

        if (!empty($transactions)) {
            $all_data[$date] = $transactions;
        }
    }
} catch (Exception $e) {
    CustomError::show404();
}

// arrage date wise
krsort($all_data);

// search funtion
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $filtered_data = [];
    foreach ($all_data as $date => $transactions) {
        $date_match = false;
        $description_match = false;
        $amount_match = false;

        // date wise search
        if (stripos(date('d-m-Y', strtotime($date)), $search) !== false) {
            $date_match = true;
        }

        foreach ($transactions as $transaction) {
            // description wise search
            if (stripos($transaction['description'], $search) !== false) {
                $description_match = true;
            }

            // amount wise search
            $amount_search = str_replace(['rs', 'rs.', '₹', ',', ' '], '', strtolower($search));
            if (is_numeric($amount_search)) {
                if (
                    stripos($transaction['received'], $amount_search) !== false ||
                    stripos($transaction['expense'], $amount_search) !== false ||
                    stripos($transaction['balance'], $amount_search) !== false
                ) {
                    $amount_match = true;
                }
            }
        }

        // if data not found than this
        if ($date_match || $description_match || $amount_match) {
            $filtered_data[$date] = $transactions;
        }
    }
    $all_data = $filtered_data;
}

// filter and shorting format
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : ''; // received/expense
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc'; // date_asc, date_desc, amount_asc, amount_desc

// filter logic
if (!empty($filter_date_from) || !empty($filter_date_to) || !empty($filter_type)) {
    $filtered_data = [];
    foreach ($all_data as $date => $transactions) {
        // date range filter
        if (!empty($filter_date_from) && strtotime($date) < strtotime($filter_date_from)) {
            continue;
        }
        if (!empty($filter_date_to) && strtotime($date) > strtotime($filter_date_to)) {
            continue;
        }

        // transaction type filter
        if (!empty($filter_type)) {
            $keep_transactions = [];
            foreach ($transactions as $transaction) {
                if ($filter_type == 'received' && !empty($transaction['received'])) {
                    $keep_transactions[] = $transaction;
                } elseif ($filter_type == 'expense' && !empty($transaction['expense'])) {
                    $keep_transactions[] = $transaction;
                }
            }
            if (!empty($keep_transactions)) {
                $filtered_data[$date] = $keep_transactions;
            }
        } else {
            $filtered_data[$date] = $transactions;
        }
    }
    $all_data = $filtered_data;
}

// shorting logic
switch ($sort_by) {
    case 'date_asc':
        ksort($all_data);
        break;
    case 'date_desc':
        krsort($all_data);
        break;
    case 'amount_asc':
        uasort($all_data, function ($a, $b) {
            return end($a)['balance'] <=> end($b)['balance'];
        });
        break;
    case 'amount_desc':
        uasort($all_data, function ($a, $b) {
            return end($b)['balance'] <=> end($a)['balance'];
        });
        break;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>

<!DOCTYPE html>
<html>

<head>
    <title>दैनिक लेनदेन रिपोर्ट</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .transaction-row {
            cursor: pointer;
            flex-grow: 1;
            margin-right: 15px;
        }

        .transaction-details {
            display: none;
        }

        .search-box {
            margin: 20px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .card-header {
            background-color: #f8f9fa;
        }

        .btn-outline-primary:hover {
            color: #fff;
        }

        /* reponsive for mobile view */
        @media (max-width: 576px) {
            .d-flex {
                flex-direction: column;
                gap: 10px;
            }

            .btn-sm {
                width: 100%;
            }
        }

        .filter-export-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .filter-export-box label {
            font-weight: 500;
        }

        .filter-export-box .btn {
            height: 38px;
        }

        @media (max-width: 768px) {
            .filter-export-box .col-md-2 {
                margin-bottom: 10px;
            }

            .filter-export-box .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-4">दैनिक लेनदेन रिपोर्ट</h2>

        <!-- search box -->
        <div class="search-box">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control"
                            placeholder="तारीख, विवरण या राशि से खोजें..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> खोजें
                        </button>
                        <?php if ($search): ?>
                            <a href="?" class="btn btn-secondary">
                                <i class="fas fa-times"></i> रीसेट
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- delter and export ui -->
        <div class="filter-export-box mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $filter_date_from; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $filter_date_to; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Transaction Type</label>
                    <select name="type" class="form-select">
                        <option value="">All</option>
                        <option value="received" <?php echo $filter_type == 'received' ? 'selected' : ''; ?>>Received</option>
                        <option value="expense" <?php echo $filter_type == 'expense' ? 'selected' : ''; ?>>Expense</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort By</label>
                    <select name="sort" class="form-select">
                        <option value="date_desc" <?php echo $sort_by == 'date_desc' ? 'selected' : ''; ?>>Date (Newest)</option>
                        <option value="date_asc" <?php echo $sort_by == 'date_asc' ? 'selected' : ''; ?>>Date (Oldest)</option>
                        <option value="amount_desc" <?php echo $sort_by == 'amount_desc' ? 'selected' : ''; ?>>Amount (High)</option>
                        <option value="amount_asc" <?php echo $sort_by == 'amount_asc' ? 'selected' : ''; ?>>Amount (Low)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
                <div class="col-md-2">
                    <div class="dropdown">
                        <button class="btn btn-success w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="export.php?type=excel&date=<?php echo date('Y-m-d'); ?>">
                                    <i class="fas fa-file-excel"></i> Excel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="export.php?type=pdf&date=<?php echo date('Y-m-d'); ?>">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>

        <!-- data table -->
        <div class="table-responsive">
            <?php foreach ($all_data as $date => $transactions): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="transaction-row" onclick="toggleTransactions('<?php echo $date; ?>')">
                                <strong><?php echo date('d-m-Y', strtotime($date)); ?></strong>
                                <span class="ms-3">
                                    लेनदेन: <?php echo count($transactions); ?> |
                                    शेष: ₹<?php echo number_format(end($transactions)['balance'], 2); ?>
                                </span>
                            </div>
                            <a href="generate_pdf.php?date=<?php echo urlencode($date); ?>"
                                class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-file-pdf"></i> PDF डाउनलोड
                            </a>
                        </div>
                    </div>
                    <div id="<?php echo $date; ?>" class="transaction-details card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>विवरण</th>
                                    <th>प्रारंभिक शेष</th>
                                    <th>प्राप्त</th>
                                    <th>खर्च</th>
                                    <th>शेष</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                        <td>₹<?php echo number_format($transaction['opening_balance'], 2); ?></td>
                                        <td><?php echo $transaction['received'] ? '₹' . number_format($transaction['received'], 2) : '-'; ?></td>
                                        <td><?php echo $transaction['expense'] ? '₹' . number_format($transaction['expense'], 2) : '-'; ?></td>
                                        <td>₹<?php echo number_format($transaction['balance'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php
                $total_pages = ceil(count($all_data) / $records_per_page);
                for ($i = 1; $i <= $total_pages; $i++):
                ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . $search : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleTransactions(date) {
            $('#' + date).slideToggle();
        }
    </script>
</body>

</html>