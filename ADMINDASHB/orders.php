<?php
session_start();
require_once '../DATABASE/db.php';
require_once '../CLASSES/orders.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../LOGIN/login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$order = new Order($conn);

// Handle order status update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $order->updateOrderStatus($order_id, $status);
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$orders = $search ? $order->searchOrders($search, $limit, $offset) : $order->getOrders($limit, $offset);
$totalOrders = $search ? $order->getTotalSearchedOrders($search) : $order->getTotalOrders();
$totalPages = ceil($totalOrders / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .card {
            margin-bottom: 20px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-declined {
            background-color: #f8d7da;
            color: #721c24;
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
                <li class="nav-item"><a class="nav-link text-white" href="sales_rep.php"><i class="bi bi-graph-up"></i> Sales Report</a></li>
                <li class="nav-item"><a class="nav-link active text-white bg-secondary" href="orders.php"><i class="bi bi-bag-check"></i> Ordering</a></li>
                <li class="nav-item mt-3"><a class="nav-link text-danger" href="../LOGIN/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Orders Management</h2>
            </div>

            <!-- Search Bar -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="search" placeholder="Search orders..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if ($search): ?>
                                <a href="orders.php" class="btn btn-secondary">Clear</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Username</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Order Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($orders)): ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?= $order['order_id'] ?></td>
                                            <td><?= htmlspecialchars($order['username']) ?></td>
                                            <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                            <td>
                                                <?php if (strtolower($order['status']) === 'pending'): ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                        <button type="submit" name="status" value="approved" class="btn btn-success btn-sm me-1">
                                                            <i class="bi bi-check-circle"></i> Accept
                                                        </button>
                                                        <button type="submit" name="status" value="declined" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-x-circle"></i> Decline
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                                        <?= ucfirst($order['status']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="approved" <?= $order['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                                                        <option value="declined" <?= $order['status'] === 'declined' ? 'selected' : '' ?>>Declined</option>
                                                    </select>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No orders found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
