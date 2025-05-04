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
if ($resultProducts) {
    $rowProducts = $resultProducts->fetch_assoc();
    $totalProducts = $rowProducts['total_products'] ?? 0;
} else {
    $totalProducts = 0;
}

// Products Sold
$querySold = "SELECT COALESCE(SUM(quantity_sold), 0) AS total_sold
              FROM sales
              WHERE staff_id = ?";
$stmtSold = $conn->prepare($querySold);
if ($stmtSold) {
    $stmtSold->bind_param("i", $staffId);
    $stmtSold->execute();
    $resultSold = $stmtSold->get_result();
    $rowSold = $resultSold->fetch_assoc();
    $totalSold = $rowSold['total_sold'] ?? 0;
    $stmtSold->close();
} else {
    $totalSold = 0;
}

// Stock on Hand
$queryStock = "SELECT COALESCE(SUM(stock_quantity), 0) AS total_stock FROM products";
$resultStock = $conn->query($queryStock);
if ($resultStock) {
    $rowStock = $resultStock->fetch_assoc();
    $totalStock = $rowStock['total_stock'] ?? 0;
} else {
    $totalStock = 0;
}

// Recent Activity (last 5 sales)
$queryActivity = "SELECT s.sale_date, p.product_name, s.quantity_sold, s.total_price
                  FROM sales s
                  JOIN products p ON s.product_id = p.id
                  WHERE s.staff_id = ?
                  ORDER BY s.sale_date DESC LIMIT 5";
$stmtActivity = $conn->prepare($queryActivity);
$recentActivity = [];
if ($stmtActivity) {
    $stmtActivity->bind_param("i", $staffId);
    $stmtActivity->execute();
    $resultActivity = $stmtActivity->get_result();
    while ($row = $resultActivity->fetch_assoc()) {
        $recentActivity[] = $row;
    }
    $stmtActivity->close();
}

// Fetch current user data for the modal
$userQuery = "SELECT username, full_name FROM users WHERE user_id = ?";
$userStmt = $conn->prepare($userQuery);
if (!$userStmt) {
    die("Error preparing statement: " . $conn->error);
}
$userStmt->bind_param("i", $staffId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userData = $userResult->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        .profile-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .activity-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
        }
        .activity-item {
            border-left: 3px solid #0d6efd;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .modal-content {
            border-radius: 10px;
        }
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
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
                    <a class="nav-link" href="productlist.php">
                        <i class="bi bi-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="sales.php">
                        <i class="bi bi-cart"></i> Sales
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
        <div class="col-md-9 ms-sm-auto col-lg-10 content">
            <div class="row">
                <!-- Profile Section -->
                <div class="col-md-4 mb-4">
                    <div class="profile-card">
                        <div class="text-center mb-3">
                            <i class="bi bi-person-circle" style="font-size: 4rem; color: #0d6efd;"></i>
                            <h4 class="mt-2"><?= htmlspecialchars($staffFullName) ?></h4>
                            <p class="text-muted"><?= htmlspecialchars($staffRole) ?></p>
                        </div>
                        <div class="d-grid">
                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewProfileModal">View Profile</button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="stat-card">
                                <h6 class="text-muted">Total Products</h6>
                                <h3><?= $totalProducts ?></h3>
                                <i class="bi bi-box text-primary" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="stat-card">
                                <h6 class="text-muted">Products Sold</h6>
                                <h3><?= $totalSold ?></h3>
                                <i class="bi bi-cart-check text-success" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="stat-card">
                                <h6 class="text-muted">Stock on Hand</h6>
                                <h3><?= $totalStock ?></h3>
                                <i class="bi bi-box-seam text-warning" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="col-12">
                    <div class="activity-card">
                        <h5 class="mb-4">Recent Activity</h5>
                        <?php if (empty($recentActivity)): ?>
                            <p class="text-muted">No recent activity</p>
                        <?php else: ?>
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="activity-item">
                                    <h6><?= htmlspecialchars($activity['product_name']) ?></h6>
                                    <p class="mb-1">Sold <?= $activity['quantity_sold'] ?> units at ₱<?= number_format($activity['total_price'], 2) ?></p>
                                    <small class="text-muted"><?= date('M d, Y h:i A', strtotime($activity['sale_date'])) ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Profile Modal -->
<div class="modal fade" id="viewProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Profile Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Username</label>
                    <p class="form-control-static"><?= htmlspecialchars($userData['username'] ?? '') ?></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Full Name</label>
                    <p class="form-control-static"><?= htmlspecialchars($userData['full_name'] ?? '') ?></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Role</label>
                    <p class="form-control-static">Staff</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Check for low stock products
        const lowStockProducts = document.querySelectorAll('tr.table-warning');
        if (lowStockProducts.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Low Stock Alert',
                html: There are ${lowStockProducts.length} products with low stock levels. Please inform the administrator to restock these items.,
                confirmButtonText: 'View Products'
            });
        }
    });
</script>
</body>
</html>