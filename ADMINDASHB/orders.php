<?php
session_start();

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'inventory_database';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process status update request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id'], $_POST['action'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = $_POST['action'] === 'approve' ? 'Approved' : 'Declined';

    $stmt = $conn->prepare("CALL UpdateOrderStatus(?, ?)");
    $stmt->bind_param("is", $orderId, $newStatus);
    $stmt->execute();
    $stmt->close();
    header("Location: orders.php"); // ✅ TAMANG FILE NAME
    exit;
}

// Fetch all orders
$sql = "SELECT o.order_id, u.full_name, o.order_date, o.status
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        ORDER BY o.order_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
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

        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }

        .nav-link i {
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
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse show">
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
        <div class="col-md-9 col-lg-10 content">
            <h2>Manage Orders</h2>
            <table class="table table-bordered mt-4">
                <thead class="table-dark">
                    <tr>
                        <th>Order ID</th>
                        <th>Ordered By</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['order_id'] ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= $row['order_date'] ?></td>
                        <td>
                            <span class="badge 
                                <?= $row['status'] == 'Pending' ? 'bg-warning' : 
                                    ($row['status'] == 'Approved' ? 'bg-success' : 'bg-danger') ?>">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'Pending'): ?>
                                <form method="post" style="display:inline-block">
                                    <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                    <button name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form method="post" style="display:inline-block">
                                    <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                    <button name="action" value="decline" class="btn btn-danger btn-sm">Decline</button>
                                </form>
                            <?php else: ?>
                                <em>No actions available</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
