<?php
session_start();
require_once '../DATABASE/db.php';
require_once '../CLASSES/orders.php';

$db = new Database();
$conn = $db->getConnection();

$order = new Order($conn);

// Handle POST for approval/decline
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['order_id'], $_POST['action'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = $_POST['action'] === 'approve' ? 'Approved' : 'Declined';
    $order->updateOrderStatus($orderId, $newStatus);
    header("Location: ../ADMINDASHB/orders.php");
    exit;
}

// Get orders
$result = $order->fetchAllOrders();
?>


<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../ADMINDASHB/bootstrap.css">
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
                    <th>Total Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= isset($row['order_id']) ? $row['order_id'] : 'N/A' ?></td>
                        <td><?= isset($row['full_name']) ? htmlspecialchars($row['full_name']) : 'N/A' ?></td>
                        <td><?= isset($row['order_date']) ? $row['order_date'] : 'N/A' ?></td>
                        <td>
                            <span class="badge 
                                <?= isset($row['status']) && $row['status'] == 'Pending' ? 'bg-warning' : 
                                    (isset($row['status']) && $row['status'] == 'Approved' ? 'bg-success' : 'bg-danger') ?>">
                                <?= isset($row['status']) ? $row['status'] : 'N/A' ?>
                            </span>
                        </td>
                        <td>₱<?= isset($row['total_price']) ? number_format($row['total_price'], 2) : '0.00' ?></td>
                        <td>
                            <?php if (isset($row['status']) && $row['status'] == 'Pending'): ?>
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
