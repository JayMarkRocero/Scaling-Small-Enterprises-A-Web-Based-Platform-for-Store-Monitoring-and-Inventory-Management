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
$orderId = $_GET['id'] ?? 0;

// Fetch order details
$orderQuery = "SELECT o.order_id, o.order_date, o.status, 
               u.full_name as staff_name
               FROM orders o
               JOIN users u ON o.user_id = u.user_id
               WHERE o.order_id = ? AND o.user_id = ?";

$stmt = $conn->prepare($orderQuery);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("ii", $orderId, $staffId);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: orders_staff.php?error=not_found");
    exit;
}

// Fetch order items
$itemsQuery = "SELECT oi.order_item_id, oi.quantity, oi.total_price,
               p.product_name, p.price as unit_price
               FROM order_items oi
               JOIN products p ON oi.product_id = p.id
               WHERE oi.order_id = ?
               ORDER BY oi.order_item_id";

$stmt = $conn->prepare($itemsQuery);
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
        .order-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .table-custom {
            border-radius: 10px;
            overflow: hidden;
        }
        .table-custom thead {
            background-color: #f8f9fa;
        }
        .table-custom th {
            border-bottom: 2px solid #dee2e6;
            padding: 15px;
        }
        .table-custom td {
            padding: 12px;
            vertical-align: middle;
        }
        .back-button {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .back-button:hover {
            background-color: #5a6268;
            color: white;
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