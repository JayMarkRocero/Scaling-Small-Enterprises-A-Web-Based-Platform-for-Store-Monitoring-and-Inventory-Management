<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'inventory_database';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$staffId = $_SESSION['user_id'];
$staffFullName = '';
$staffUsername = '';
$staffRole = '';

$stmt = $conn->prepare("SELECT username, full_name, role_id FROM users WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    $stmt->bind_result($staffUsername, $staffFullName, $staffRole);
    $stmt->fetch();
    $stmt->close();
} else {
    die("Error in fetching staff info: " . $conn->error);
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../LOGIN/login.php");
    exit;
}

// Total Products
$queryProducts = "SELECT COUNT(*) AS total_products FROM products";
$resultProducts = $conn->query($queryProducts);
$rowProducts = $resultProducts->fetch_assoc();

$totalProducts = $rowProducts['total_products'] ?? 0;


// Total Sales
$query = "SELECT SUM(total_price) AS total_sales FROM sales";
$result = $conn->query($query);
$row = $result->fetch_assoc();

$totalSales = $row['total_sales'] ?? 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
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

        .card-custom {
            border-radius: 12px;
            padding: 20px;
            color: white;
        }

        .bg-primary-custom {
            background-color: #2563eb;
        }

        .bg-success-custom {
            background-color: #10b981;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar collapse show">
            <div class="sidebar-header">
                INVENTORY SYSTEM
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="staff_dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="product_list.php">
                        <i class="bi bi-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="sales.php">
                        <i class="bi bi-cart"></i> Sales
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="sales_rep.php">
                        <i class="bi bi-graph-up"></i> Sales Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="orders_staff.php">
                        <i class="bi bi-bag-check"></i> My Orders</a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-danger" href="../LOGIN/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 content">
            <h1 class="mt-4">Welcome, <?= htmlspecialchars($staffFullName) ?>!</h1>


            <div class="row mt-5">
                <div class="col-md-6 mb-4">
                    <div class="card-custom bg-primary-custom">
                        <h4>Total Products</h4>
                        <h2><?= $totalProducts ?></h2>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card-custom bg-success-custom">
                        <h4>Total Sales</h4>
                        <h2> ₱<?= number_format($totalSales, 2); ?></h2>
                    </div>
                </div>
            </div>

        </div> <!-- End of Main Content -->
    </div> <!-- End of Row -->
</div> <!-- End of Container -->

        </body>
    </html>
</div>

<!-- Bootstrap JS Bundle (optional for some components like collapse sidebar if needed) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
