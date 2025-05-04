<?php
session_start();
require_once '../DATABASE/db.php';

$db = new Database();
$conn = $db->getConnection();

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

    // Get product details and current stock
    $productQuery = "SELECT product_name, price, stock_quantity FROM products WHERE id = ?";
    $stmt = $conn->prepare($productQuery);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($product) {
        // Check if there's enough stock
        if ($product['stock_quantity'] >= $quantity_sold) {
            $_SESSION['cart'][] = [
                'id' => $product_id,
                'name' => $product['product_name'],
                'price' => $product['price'],
                'quantity' => $quantity_sold
            ];
            header("Location: sales.php?added_cart=1");
        } else {
            header("Location: sales.php?error=insufficient_stock");
        }
    } else {
        header("Location: sales.php?error=product_not_found");
    }
    exit();
}

// Handle direct sale (Add Sale)
if (isset($_POST['add_sale'])) {
    $product_id = $_POST['product_id'];
    $quantity_sold = $_POST['quantity_sold'];

<<<<<<< Updated upstream
    $product = $conn->query("SELECT price FROM products WHERE id = $product_id")->fetch_assoc();
    $total_price = $product['price'] * $quantity_sold;
    $conn->query("INSERT INTO sales (product_id, quantity_sold, total_price, sale_date)  
    VALUES ('$product_id', '$quantity_sold', '$total_price', NOW())");
    header("Location: sales.php?added_sale=1");
    exit();
=======
    // Get product details and current stock
    $productQuery = "SELECT price, stock_quantity FROM products WHERE id = ?";
    $stmt = $conn->prepare($productQuery);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($product) {
        // Check if there's enough stock
        if ($product['stock_quantity'] >= $quantity_sold) {
            $total_price = $product['price'] * $quantity_sold;

            // Start transaction
            $conn->begin_transaction();

            try {
                // Update product stock
                $updateStock = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                $stmt = $conn->prepare($updateStock);
                $stmt->bind_param("ii", $quantity_sold, $product_id);
                $stmt->execute();
                $stmt->close();

                // Insert into sales
                $insertSale = "INSERT INTO sales (product_id, quantity_sold, total_price, sale_date) VALUES (?, ?, ?, NOW())";
                $stmt = $conn->prepare($insertSale);
                $stmt->bind_param("iid", $product_id, $quantity_sold, $total_price);
                $stmt->execute();
                $stmt->close();

                // Commit transaction
                $conn->commit();
                header("Location: sales.php?added_sale=1");
                exit();
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                header("Location: sales.php?error=1");
                exit();
            }
        } else {
            header("Location: sales.php?error=insufficient_stock");
            exit();
        }
    } else {
        header("Location: sales.php?error=product_not_found");
        exit();
    }
>>>>>>> Stashed changes
}

// Fetch sales records with category
$salesList = $conn->query("
    SELECT s.id, p.product_name, c.category_name AS category, s.quantity_sold, s.total_price, s.sale_date 
    FROM sales s
    JOIN products p ON s.product_id = p.id 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY s.sale_date DESC
");

// Display error messages
if (isset($_GET['error'])) {
    $error_message = '';
    switch($_GET['error']) {
        case 'insufficient_stock':
            $error_message = 'Insufficient stock for this product';
            break;
        case 'product_not_found':
            $error_message = 'Product not found';
            break;
        default:
            $error_message = 'An error occurred while processing your request';
    }
    echo '<div class="alert alert-danger">' . $error_message . '</div>';
}
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
                    <a class="nav-link" href="productlist.php"><i class="bi bi-box"></i> Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="sales.php"><i class="bi bi-cart"></i> Sales</a>
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

            <div class="mb-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSaleModal">
                    <i class="bi bi-plus-circle"></i> Add New Sale
                </button>
            </div>

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

            <!-- Add Sale Modal -->
            <div class="modal fade" id="addSaleModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Sale</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Product</label>
                                    <select name="product_id" class="form-select" required>
                                        <option value="">Select a product</option>
                                        <?php while ($row = $productOptions->fetch_assoc()): ?>
                                            <option value="<?= $row['id']; ?>">
                                                <?= htmlspecialchars($row['product_name']); ?> - ₱<?= number_format($row['price'], 2); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" name="quantity_sold" class="form-control" min="1" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="add_sale" class="btn btn-success">Record Sale</button>
                                <a href="orders_staff.php" class="btn btn-primary">Go to Orders</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-success">
                        <tr>
                            <th>Sale ID</th>
                            <th>Product</th>
                            <th>Category</th>
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
                            <td><?= htmlspecialchars($sale['category']); ?></td>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Reset the product options dropdown when modal is closed
        $('#addSaleModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
        });
    });
</script>
</body>
</html>
