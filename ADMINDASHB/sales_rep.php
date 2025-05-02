<?php
require_once '../DATABASE/db.php';

// Create an instance of the Database class
$database = new Database();
$conn = $database->getConnection(); // Get the connection


if (!$conn) {
    die("Database connection not established.");
}

// Variable Holders
$salesReport = [];
$reportType = 'monthly';
$selectedMonth = date('m');
$selectedYear = date('Y');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['report_type'];
    if (isset($_POST['selected_month'])) {
        $selectedMonth = $_POST['selected_month'];
    }
    if (isset($_POST['selected_year'])) {
        $selectedYear = $_POST['selected_year'];
    }
}

// Stored Procedure
$stmt = $conn->prepare("CALL SalesReport(?, ?, ?)");
$stmt->bind_param("sii", $reportType, $selectedMonth, $selectedYear);
$stmt->execute();
$salesReport = $stmt->get_result();

// Calculate total sales and total transactions
$totalSales = 0;
$totalTransactions = 0;

if ($salesReport->num_rows > 0) {
    while ($row = $salesReport->fetch_assoc()) {
        $totalSales += $row['total_price']; 
        $totalTransactions += $row['quantity_sold'];
    }
}

// Prepare data for the chart
$chartLabels = [];
$chartData = [];

if ($salesReport->num_rows > 0) {
    while ($row = $salesReport->fetch_assoc()) {
        $chartLabels[] = $row['product_name']; 
        $chartData[] = $row['total_price']; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sales Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->
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
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="sidebar-header">
                INVENTORY SYSTEM
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="../ADMINDASHB/usermanagement.php">
                        <i class="bi bi-people"></i> User Management
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link text-white" href="product.php"><i class="bi bi-box"></i> Products</a></li>
                <li class="nav-item"><a class="nav-link active text-white bg-secondary" href="#"><i class="bi bi-graph-up"></i> Sales Report</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="orders.php"><i class="bi bi-bag-check"></i> Ordering</a></li>
                <li class="nav-item mt-3"><a class="nav-link text-danger" href="../LOGIN/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Sales Report</h2>
            </div>

            <!-- Total Sales and Transactions -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <p class="card-text">₱<?= number_format($totalSales, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Transactions</h5>
                            <p class="card-text"><?= $totalTransactions; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dropdown for Report Type -->
            <form method="POST" class="mb-3">
                <label for="report_type" class="form-label">Select Report Type:</label>
                <select name="report_type" id="report_type" class="form-select" onchange="this.form.submit()">
                    <option value="monthly" <?php echo $reportType === 'monthly' ? 'selected' : ''; ?>>Monthly Sales</option>
                    <option value="daily" <?php echo $reportType === 'daily' ? 'selected' : ''; ?>>Daily Sales</option>
                </select>
            </form>

            <!-- Month and Year selection for both report types -->
            <form method="POST" class="mb-3">
                <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($reportType); ?>">
                <label for="selected_month" class="form-label">Select Month:</label>
                <select name="selected_month" id="selected_month" class="form-select" onchange="this.form.submit()">
                    <option value="1" <?php echo $selectedMonth === '1' ? 'selected' : ''; ?>>January</option>
                    <option value="2" <?php echo $selectedMonth === '2' ? 'selected' : ''; ?>>February</option>
                    <option value="3" <?php echo $selectedMonth === '3' ? 'selected' : ''; ?>>March</option>
                    <option value="4" <?php echo $selectedMonth === '4' ? 'selected' : ''; ?>>April</option>
                    <option value="5" <?php echo $selectedMonth === '5' ? 'selected' : ''; ?>>May</option>
                    <option value="6" <?php echo $selectedMonth === '6' ? 'selected' : ''; ?>>June</option>
                    <option value="7" <?php echo $selectedMonth === '7' ? 'selected' : ''; ?>>July</option>
                    <option value="8" <?php echo $selectedMonth === '8' ? 'selected' : ''; ?>>August</option>
                    <option value="9" <?php echo $selectedMonth === '9' ? 'selected' : ''; ?>>September</option>
                    <option value="10" <?php echo $selectedMonth === '10' ? 'selected' : ''; ?>>October</option>
                    <option value="11" <?php echo $selectedMonth === '11' ? 'selected' : ''; ?>>November</option>
                    <option value="12" <?php echo $selectedMonth === '12' ? 'selected' : ''; ?>>December</option>
                </select>
                <label for="selected_year" class="form-label mt-3">Select Year:</label>
                <select name="selected_year" id="selected_year" class="form-select" onchange="this.form.submit()">
                    <?php for ($year = date('Y'); $year >= date('Y') - 5; $year--): ?>
                        <option value="<?php echo $year; ?>" <?php echo $selectedYear == $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                    <?php endfor; ?>
                </select>
            </form>

            <canvas id="salesChart" width="400" height="200"></canvas> <!-- Canvas for the chart -->

            <table class="table table-hover mt-4">
                <thead>
                    <tr>
                        <?php if ($reportType === 'monthly'): ?>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Quantity Sold</th>
                            <th>Total Price</th>
                            <th>Date Sold</th>
                        <?php elseif ($reportType === 'daily'): ?>
                            <th>Date Sold</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Total Quantity Sold</th>
                            <th>Total Price</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($salesReport->num_rows > 0): ?>
                        <?php
                        $i = 1;
                        while ($row = $salesReport->fetch_assoc()):
                        ?>
                            <tr>
                                <?php if ($reportType === 'monthly'): ?>
                                    <td><?= $i++; ?></td>
                                    <td><?= htmlspecialchars($row['product_name']); ?></td>
                                    <td><?= htmlspecialchars($row['category']); ?></td>
                                    <td><?= number_format($row['quantity_sold']); ?></td>
                                    <td>₱<?= number_format($row['total_price'], 2); ?></td>
                                    <td><?= htmlspecialchars($row['sale_date']); ?></td>
                                <?php elseif ($reportType === 'daily'): ?>
                                    <td><?= htmlspecialchars($row['sale_date']); ?></td>
                                    <td><?= htmlspecialchars($row['product_name']); ?></td>
                                    <td><?= htmlspecialchars($row['category']); ?></td>
                                    <td><?= number_format($row['total_quantity_sold']); ?></td>
                                    <td>₱<?= number_format($row['total_price'], 2); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No sales record found for the selected period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>

<script>
    // Prepare data for the chart
    const salesData = {
        labels: <?php echo json_encode($chartLabels); ?>, // Product names
        datasets: [{
            label: 'Total Sales',
            data: <?php echo json_encode($chartData); ?>, // Total sales data
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    };

    // Create the chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'bar', // Change to 'line' for a line chart
        data: salesData,
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 
$stmt->close(); 
$conn->close(); 
?>