<?php
// Connect to the database
require_once '../DATABASE/db.php';

$db = new Database();
$conn = $db->getConnection();
 
// Total user 
function getUserCount() {
    global $conn;
    $conn->query("CALL GetUserCount(@userCount)");
    $result = $conn->query("SELECT @userCount AS userCount");
    if ($result && $row = $result->fetch_assoc()) 
        $userCount = $row['userCount']; {
    return $userCount;
}
}
// Total product
function getTotalProducts() {
    global $conn;
    
    $conn->query("CALL GetTotalProducts(@totalProducts)");
    $result = $conn->query("SELECT @totalProducts AS totalProducts");
    if ($result && $row = $result->fetch_assoc()) {
        $totalProducts = $row['totalProducts'];
    }
    return $totalProducts;
}
// Total sales 
function getTotalSales() {
  global $conn;
  $conn->query("CALL GetTotalSales(@totalSales)");
  $result = $conn->query("SELECT @totalSales AS totalSales");
  if ($result && $row = $result->fetch_assoc()) {
      $totalSales = $row['totalSales'];
  }
  return $totalSales;
}
// Fetch monthly sales data for the line chart
$monthlySalesData = [];
$stmtMonthly = $conn->prepare("CALL GetMonthlySalesData()");
$stmtMonthly->execute();
$monthlySalesResult = $stmtMonthly->get_result();

// Free result sets and advance
$conn->next_result();
if ($monthlySalesResult->num_rows > 0) {
    while ($row = $monthlySalesResult->fetch_assoc()) {
        $monthlySalesData[$row['month']] = $row['total_sales'];
    }
}

// Prepare data for the line chart
$lineChartLabels = [];
$lineChartData = [];
for ($i = 1; $i <= 12; $i++) {
    $lineChartLabels[] = date ("F", mktime(0, 0, 0, $i, 1));
    $lineChartData[] = isset($monthlySalesData[$i]) ? $monthlySalesData[$i] : 0;
}

$totalUsers = getUserCount();
$totalProducts = getTotalProducts();
$totalSales = getTotalSales();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../ADMINDASHB/bootstrap.css">
</head>
<body>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 d-md-block sidebar">
      <div class="sidebar-header">
        INVENTORY SYSTEM
      </div>
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="../ADMINDASHB/dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'usermanagement.php' ? 'active' : '' ?>" href="../ADMINDASHB/usermanagement.php">
            <i class="bi bi-people"></i> User Management
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'product.php' ? 'active' : '' ?>" href="../ADMINDASHB/product.php">
            <i class="bi bi-box"></i> Products
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'sales_rep.php' ? 'active' : '' ?>" href="../ADMINDASHB/sales_rep.php">
            <i class="bi bi-graph-up"></i> Sales Report
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>" href="../ADMINDASHB/orders.php">
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
    <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
      <h1 class="mb-3">Dashboard</h1>
      <div class="row g-3">
      <div class="row g-3">
    <div class="col-md-4">
        <div class="card bg-primary total-users-card" style="height: 130px; display: flex; justify-content: center; align-items: center;">
            <div class="text-center">
                <i class="bi bi-people-fill fs-4 d-block mb-1"></i>
                <div class="card-title mb-0" style="font-size: 0.95rem;">Total Users</div>
                <div class="card-text" style="font-size: 1.05rem;"><?php echo $totalUsers; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success total-products-card" style="height: 130px; display: flex; justify-content: center; align-items: center;">
            <div class="text-center">
                <i class="bi bi-box-seam fs-4 d-block mb-1"></i>
                <div class="card-title mb-0" style="font-size: 0.95rem;">Total Products</div>
                <div class="card-text" style="font-size: 1.05rem;"><?php echo $totalProducts; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning total-sales-card" style="height: 130px; display: flex; justify-content: center; align-items: center;">
            <div class="text-center">
                <i class="bi bi-cash fs-4 d-block mb-1"></i>
                <div class="card-title mb-0" style="font-size: 0.95rem;">Total Sales</div>
                <div class="card-text" style="font-size: 1.05rem;">₱<?php echo number_format($totalSales, 2); ?></div>
            </div>
        </div>
    </div>
</div>
<!-- Monthly Sales Line Chart -->
<div class="mt-5">
        <h4>Monthly Sales (<?= date('Y') ?>)</h4>
        <canvas id="monthlySalesChart" height="150"></canvas>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('monthlySalesChart').getContext('2d');
  const monthlySalesChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($lineChartLabels); ?>,
      datasets: [{
        label: 'Total Sales (₱)',
        data: <?= json_encode($lineChartData); ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 2,
        fill: true,
        tension: 0.3,
        pointRadius: 4,
        pointHoverRadius: 6
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {

            // Include currency symbol in ticks
            callback: function(value) {
              return '₱' + value.toLocaleString();
            }
          }
        }
      },
      plugins: {
        legend: {
          display: true,
          position: 'top'
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

<?php
$stmtMonthly->close();
$conn->close();
?>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>