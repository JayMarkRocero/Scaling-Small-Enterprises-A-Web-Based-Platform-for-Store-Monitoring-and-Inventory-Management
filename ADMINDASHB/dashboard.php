<?php
// Connect to the database
require_once '../DATABASE/db.php';

function getUserCount() {
    global $conn;
    $userCount = 0;
    $conn->query("CALL GetUserCount(@userCount)");
    $result = $conn->query("SELECT @userCount AS userCount");
    if ($result && $row = $result->fetch_assoc()) {
        $userCount = $row['userCount'];
    }
    return $userCount;
}

function getTotalProducts() {
    global $conn;
    $totalProducts = 0;
    $conn->query("CALL GetTotalProducts(@totalProducts)");
    $result = $conn->query("SELECT @totalProducts AS totalProducts");
    if ($result && $row = $result->fetch_assoc()) {
        $totalProducts = $row['totalProducts'];
    }
    return $totalProducts;
}

function getLowStockProducts() {
    global $conn;
    // (You can add implementation here later if needed)
    return [];
}

$totalUsers = getUserCount();
$totalProducts = getTotalProducts();
$lowStockProducts = getLowStockProducts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #212529;
            color: white;
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
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
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
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }
        .card-header {
            background-color: #fff;
            font-weight: 600;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
    </style>
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
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : '' ?>" href="../ADMINDASHB/sales.php">
            <i class="bi bi-cart"></i> Sales
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'sales_report.php' ? 'active' : '' ?>" href="../ADMINDASHB/sales_report.php">
            <i class="bi bi-graph-up"></i> Sales Report
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>" href="../ADMINDASHB/order.php">
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
        <div class="col-md-6">
          <div class="card bg-primary text-white" style="height: 130px; display: flex; justify-content: center; align-items: center;">
            <div class="text-center">
              <i class="bi bi-people-fill fs-4 d-block mb-1"></i>
              <div class="card-title mb-0" style="font-size: 0.95rem;">Total Users</div>
              <div class="card-text" style="font-size: 1.05rem;"><?php echo $totalUsers; ?></div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card bg-success text-white" style="height: 130px; display: flex; justify-content: center; align-items: center;">
            <div class="text-center">
              <i class="bi bi-box-seam fs-4 d-block mb-1"></i>
              <div class="card-title mb-0" style="font-size: 0.95rem;">Total Products</div>
              <div class="card-text" style="font-size: 1.05rem;"><?php echo $totalProducts; ?></div>
            </div>
          </div>
        </div>
      </div>
    </div> <!-- End of content -->
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>