<?php
require_once '../DATABASE/db.php';

// Variable Holders
$salesReport = [];
$reportType = 'monthly';
$Month = date('m');
$Year = date('Y');

// Check if the form is submitted
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse bg-dark text-white">
            <div class="p-3 fs-5 fw-bold border-bottom">INVENTORY SYSTEM</div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="usermanagement.php"><i class="bi bi-people"></i> User Management</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="product.php"><i class="bi bi-box"></i> Products</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="sales.php"><i class="bi bi-cart"></i> Sales</a></li>
                <li class="nav-item"><a class="nav-link active text-white bg-secondary" href="#"><i class="bi bi-graph-up"></i> Sales Report</a></li>
                <li class="nav-item mt-3"><a class="nav-link text-danger" href="../LOGIN/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Sales Report</h2>
            </div>

            <!-- Dropdown for Report Type -->
            <form method="POST" class="mb-3">
                <label for="report_type" class="form-label">Select Report Type:</label>
                <select name="report_type" id="report_type" class="form-select" onchange="this.form.submit()">
                    <option value="monthly" <?php echo $reportType === 'monthly' ? 'selected' : ''; ?>>Monthly Sales</option>
                    <option value="daily" <?php echo $reportType === 'daily' ? 'selected' : ''; ?>>Daily Sales</option>
                </select>
            </form>

            <!-- Month and Year for Monthly Sales -->
            <?php if ($reportType === 'monthly'): ?>
                <form method="POST" class="mb-3">
                    <input type="hidden" name="report_type" value="monthly"> <!-- Keep the report type -->
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
                    <label for="selected_year" class="form-label">Select Year:</label>
                    <select name="selected_year" id="selected_year" class="form-select" onchange="this.form.submit()">
                        <?php for ($year = date('Y'); $year >= date('Y') - 5; $year--): ?>
                            <option value="<?php echo $year; ?>" <?php echo $selectedYear === $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            <?php endif; ?>

            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Quantity Sold</th>
                        <th>Total Price</th>
                        <th>Date Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($salesReport->num_rows > 0): ?>
                        <?php $i = 1; while ($row = $salesReport->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo $row['product_name']; ?></td>
                                <td><?php echo $row['category']; ?></td>
                                <td><?php echo number_format($row['quantity_sold']); ?></td>
                                <td><?php echo number_format($row['total_price'], 2); ?></td>
                                <td><?php echo $row['sale_date']; ?></td>
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

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 
$stmt->close(); 
$conn->close(); 
?>