<?php
session_start();
require_once '../DATABASE/db.php';
require_once '../CLASSES/orders.php';

$db = new Database();
$conn = $db->getConnection();

// Variable Holders
$salesReport = [];
$selectedYear = date('Y');
$selectedMonth = date('1');
$totalSales = 0;
$totalTransactions = 0; 
$totalApproved = 0;
$totalPending = 0;
$totalDeclined = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['selected_year'])) {
        $selectedYear = $_POST['selected_year'];
    }
    if (isset($_POST['selected_month'])) {
        $selectedMonth = $_POST['selected_month'];
    }
}

// Stored Procedure Call to get sales records for the selected month and year
$stmt = $conn->prepare("CALL GetSalesRecordsByMonth(?, ?)");
$stmt->bind_param("ii", $selectedMonth, $selectedYear);
$stmt->execute();
$salesReport = $stmt->get_result();

// Prepare data for graph: total sales for the selected month
$graphLabels = [];
$graphData = [];

while ($row = $salesReport->fetch_assoc()) {
    $graphLabels[] = $row['product_name'];
    $graphData[] = (float)$row['total_price'];
    $totalSales += (float)$row['total_price'];
    $totalTransactions += $row['quantity_sold']; 
}

// Calculate totals by status for the selected month and year
$statusTotals = $conn->query("
    SELECT status, SUM(total_price) as total
    FROM sales
    WHERE MONTH(sale_date) = $selectedMonth AND YEAR(sale_date) = $selectedYear
    GROUP BY status
");

if ($statusTotals) {
    while ($row = $statusTotals->fetch_assoc()) {
        if ($row['status'] == 'Approved') $totalApproved = $row['total'];
        if ($row['status'] == 'Pending') $totalPending = $row['total'];
        if ($row['status'] == 'Declined') $totalDeclined = $row['total'];
    }
}

$salesReport->free();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #212529;
            color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .sidebar-header {
            padding: 20px 15px;
            background-color: #111418;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .content {
            padding: 30px;
        }
        .card {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        canvas {
            background: white;
            border-radius: 8px;
            padding: 10px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse show">
            <div class="sidebar-header">
                INVENTORY SYSTEM
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="../ADMINDASHB/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../ADMINDASHB/usermanagement.php">
                        <i class="bi bi-people"></i> User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../ADMINDASHB/product.php">
                        <i class="bi bi-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="sales_rep.php">
                        <i class="bi bi-graph-up"></i> Sales Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../ADMINDASHB/orders.php">
                        <i class="bi bi-bag-check"></i> Ordering
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-danger" href="../LOGIN/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Sales Report</h2>
            </div>

            <!-- Total Sales -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <h2 class="card-text">₱<?= number_format($totalSales, 2) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Approved Sales</h5>
                            <h2 class="card-text">₱<?= number_format($totalApproved, 2) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <h5 class="card-title">Pending Sales</h5>
                            <h2 class="card-text">₱<?= number_format($totalPending, 2) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h5 class="card-title">Declined Sales</h5>
                            <h2 class="card-text">₱<?= number_format($totalDeclined, 2) ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Year and Month selection -->
            <form method="POST" class="mb-3">
                <div class="row">
                    <div class="col-md-6">
                        <label for="selected_year" class="form-label">Select Year:</label>
                        <select name="selected_year" id="selected_year" class="form-select" onchange="this.form.submit()">
                            <?php for ($year = date('Y'); $year >= date('Y') - 5; $year--): ?>
                                <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>><?= $year ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="selected_month" class="form-label">Select Month:</label>
                        <select name="selected_month" id="selected_month" class="form-select" onchange="this.form.submit()">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $selectedMonth == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </form>

            <!-- Graph Section -->
            <div class="card mt-5 p-3">
                <h5>Total Sales for <?= htmlspecialchars($selectedMonth) ?>, <?= htmlspecialchars($selectedYear) ?></h5>
                <canvas id="salesChart" height="150"></canvas>
            </div>

            <!-- Sales Records Table -->
            <h2 class="mt-5">Sales Records</h2>
            <table class="table table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Quantity Sold</th>
                        <th>Total Price</th>
                        <th>Date Sold</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Re-execute the stored procedure to fetch sales records for the table
                $stmt = $conn->prepare("CALL GetSalesRecordsByMonth(?, ?)");
                $stmt->bind_param("ii", $selectedMonth, $selectedYear);
                $stmt->execute();
                $salesRecords = $stmt->get_result();

                while ($row = $salesRecords->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td><?= htmlspecialchars($row['quantity_sold']) ?></td>
                        <td>₱<?= number_format($row['total_price'], 2) ?></td>
                        <td><?= htmlspecialchars($row['sale_date']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const labels = <?= json_encode($graphLabels); ?>;
    const data = <?= json_encode($graphData); ?>;

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Sales (₱)',
                data: data,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>
</body>
</html>