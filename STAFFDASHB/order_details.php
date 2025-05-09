<?php
session_start();
// Connect to the database
require_once '../DATABASE/db.php';

$db = new Database();
$conn = $db->getConnection();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

$stmt = $conn->prepare("CALL GetOrderDetailsByUser(?, ?)");
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("ii", $orderId, $staffId);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conn->next_result();
$stmt = $conn->prepare("CALL GetOrderItemsByOrderId(?)");
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $orderId);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}
$items = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../ADMINDASHB/bootstrap.css">
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
                    <a class="nav-link" href="staff_dashboard.php">
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
                    <a class="nav-link active" href="orders_staff.php">
                        <i class="bi bi-bag-check"></i> My Orders
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
        <div class="col-md-9 ms-sm-auto col-lg-10 content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Order Details</h2>
                <a href="orders_staff.php" class="back-button">
                    <i class="bi bi-arrow-left"></i> Back to Orders
                </a>
            </div>

            <div class="order-card">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-muted">Order Information</h5>
                        <p class="mb-1"><strong>Order ID:</strong> #<?= $order['order_id'] ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></p>
                        <p class="mb-1"><strong>Staff:</strong> <?= htmlspecialchars($order['staff_name']) ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h5 class="text-muted">Status</h5>
                        <span class="status-badge 
                            <?= $order['status'] == 'Pending' ? 'status-pending' : 
                                ($order['status'] == 'Approved' ? 'status-approved' : 'status-cancelled') ?>">
                            <?= $order['status'] ?>
                        </span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grandTotal = 0;
                            while ($item = $items->fetch_assoc()): 
                                $grandTotal += $item['total_price'];
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td>₱<?= number_format($item['total_price'], 2) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Grand Total:</th>
                                <th>₱<?= number_format($grandTotal, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 