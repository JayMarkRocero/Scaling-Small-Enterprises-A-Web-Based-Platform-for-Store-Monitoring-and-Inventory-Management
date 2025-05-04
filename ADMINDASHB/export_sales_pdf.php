<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../vendor/dompdf_0-8-6/dompdf/autoload.inc.php';
require_once '../DATABASE/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$db = new Database();
$conn = $db->getConnection();

// Get selected month and year
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get sales data
$stmt = $conn->prepare("CALL GetSalesRecordsByMonth(?, ?)");
$stmt->bind_param("ii", $selectedMonth, $selectedYear);
$stmt->execute();
$salesReport = $stmt->get_result();

// Calculate totals
$totalSales = 0;
$totalTransactions = 0;
while ($row = $salesReport->fetch_assoc()) {
    $totalSales += $row['total_price'];
    $totalTransactions += $row['quantity_sold'];
}

// HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        @page { margin: 40px 30px 60px 30px; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 12px; }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            width: 80px;
            margin-bottom: 10px;
        }
        h1 {
            font-size: 24px;
            margin: 0;
            color: #2c3e50;
        }
        .subtitle {
            font-size: 14px;
            color: #555;
            margin-bottom: 10px;
        }
        .summary {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #bbb;
            padding: 8px 6px;
            text-align: left;
        }
        th {
            background-color: #2c3e50;
            color: #fff;
            font-size: 13px;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            text-align: right;
            font-size: 10px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="http://localhost/ADBMSFinalProject/WEBSITE%20IMAGES/logo.png" class="logo" alt="Logo">
        <h1>Sales Report</h1>
        <div class="subtitle">Month: ' . date('F', mktime(0, 0, 0, $selectedMonth, 1)) . ' ' . $selectedYear . '</div>
    </div>
    <div class="summary">
        <strong>Total Sales:</strong> ₱' . number_format($totalSales, 2) . '<br>
        <strong>Total Transactions:</strong> ' . $totalTransactions . '
    </div>
    <table>
        <tr>
            <th>Product Name</th>
            <th>Quantity Sold</th>
            <th>Total Price</th>
        </tr>';
$salesReport->data_seek(0);
while ($row = $salesReport->fetch_assoc()) {
    $html .= '
        <tr>
            <td>' . htmlspecialchars($row['product_name']) . '</td>
            <td>' . $row['quantity_sold'] . '</td>
            <td>₱' . number_format($row['total_price'], 2) . '</td>
        </tr>';
}
$html .= '
    </table>
    <div class="footer">
        Generated on: ' . date('F j, Y, g:i a') . '
    </div>
</body>
</html>';

// Create PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF
$dompdf->stream("sales_report_{$selectedMonth}_{$selectedYear}.pdf", array("Attachment" => true));
?> 