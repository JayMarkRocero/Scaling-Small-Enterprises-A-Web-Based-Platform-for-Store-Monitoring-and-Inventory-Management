<?php
session_start();
require_once '../DATABASE/db.php';

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Fetch product options for dropdown
$productOptions = $conn->query("SELECT id, product_name, price FROM products");

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity_sold = $_POST['quantity_sold'];

    $product = $conn->query("SELECT product_name, price FROM products WHERE id = $product_id")->fetch_assoc();

    $_SESSION['cart'][] = [
        'id' => $product_id,
        'name' => $product['product_name'],
        'price' => $product['price'],
        'quantity' => $quantity_sold
    ];

    header("Location: sales.php");
    exit();
}

// Handle direct sale (Add Sale)
if (isset($_POST['add_sale'])) {
    $product_id = $_POST['product_id'];
    $quantity_sold = $_POST['quantity_sold'];

    $product = $conn->query("SELECT price FROM products WHERE id = $product_id")->fetch_assoc();
    $total_price = $product['price'] * $quantity_sold;

    $conn->query("INSERT INTO sales (product_id, quantity_sold, total_price, sale_date) 
                  VALUES ($product_id, $quantity_sold, $total_price, NOW())");

    header("Location: sales.php?added_sale=1");
    exit();
}

// Fetch sales records
$salesList = $conn->query("SELECT sales.id, products.product_name, sales.quantity_sold, sales.total_price, sales.sale_date 
                           FROM sales 
                           JOIN products ON sales.product_id = products.id 
                           ORDER BY sales.sale_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="sidebar-header">INVENTORY SYSTEM</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="staff_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="product_list.php"><i class="bi bi-box"></i> Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="sales.php"><i class="bi bi-cart"></i> Sales</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="sales_rep.php"><i class="bi bi-graph-up"></i> Sales Report</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="orders_staff.php"><i class="bi bi-bag-check"></i> My Orders</a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-danger" href="../LOGIN/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
            <h2 class="mb-4">Sales Records</h2>

            <?php if (isset($_GET['added_sale'])): ?>
                <div class="alert alert-success">Sale recorded successfully!</div>
            <?php endif; ?>

            <form method="POST" class="mb-3 d-flex gap-2">
                <select name="product_id" class="form-select w-auto" required>
                    <?php while ($row = $productOptions->fetch_assoc()): ?>
                        <option value="<?= $row['id']; ?>">
                            <?= htmlspecialchars($row['product_name']); ?> - ₱<?= number_format($row['price'], 2); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="number" name="quantity_sold" placeholder="Qty" class="form-control w-auto" min="1" required>
                <button type="submit" name="add_sale" class="btn btn-success">Add Sale</button>
                <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Order</button>
            </form>

            <?php if (!empty($_SESSION['cart'])): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">Items to Order</div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <div>
                                    <?= htmlspecialchars($item['name']); ?> (x<?= $item['quantity']; ?>)
                                </div>
                                <div>₱<?= number_format($item['price'] * $item['quantity'], 2); ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-success">
                        <tr>
                            <th>Sale ID</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Total Price</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sale = $salesList->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($sale['id']); ?></td>
                            <td><?= htmlspecialchars($sale['product_name']); ?></td>
                            <td><?= htmlspecialchars($sale['quantity_sold']); ?></td>
                            <td>₱<?= number_format($sale['total_price'], 2); ?></td>
                            <td><?= htmlspecialchars($sale['sale_date']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
</body>
</html>
