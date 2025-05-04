<?php
require_once '../DATABASE/db.php';
require_once '../CLASSES/Product.php';

$db = new Database();
$conn = $db->getConnection();
$productObj = new Product($conn);

// Get all categories for filter dropdown
$categories = $productObj->getCategories();

// Handle search, filter, and low stock
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$lowStockOnly = isset($_GET['lowstock']) && $_GET['lowstock'] == '1';

if ($lowStockOnly) {
    $products = $productObj->getLowStockProducts();
} elseif ($search) {
    $products = $productObj->searchProducts($search);
    // If category filter is also set, filter further
    if ($categoryFilter) {
        $products = array_filter($products, function($p) use ($categoryFilter) {
            return $p['category_id'] == $categoryFilter;
        });
    }
} elseif ($categoryFilter) {
    // No search, but filter by category
    $all = $productObj->getAllProducts();
    $products = array_filter($all, function($p) use ($categoryFilter) {
        return $p['category_id'] == $categoryFilter;
    });
} else {
    $products = $productObj->getAllProducts();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
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
                    <a class="nav-link" href="staff_dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="productlist.php">
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
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
            <h2 class="mb-4">Product List</h2>
            <form method="get" class="row g-3 mb-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search product or category..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="lowstock" value="1" id="lowstock" <?= $lowStockOnly ? 'checked' : '' ?>>
                        <label class="form-check-label" for="lowstock">Low Stock Only</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-custom" id="productTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total Sold</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr<?= $product['stock_quantity'] <= 10 ? ' class="table-warning"' : '' ?>>
                                <td><?= $product['id'] ?></td>
                                <td><?= htmlspecialchars($product['product_name']) ?></td>
                                <td><?= htmlspecialchars($product['category_name']) ?></td>
                                <td>
                                    <?= $product['stock_quantity'] ?>
                                    <?php if ($product['stock_quantity'] <= 10): ?>
                                        <span class="badge bg-danger ms-2">Low Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>₱<?= number_format($product['price'], 2) ?></td>
                                <td><?= $product['total_sold'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        $('#productTable').DataTable({
            order: [[0, 'desc']]
        });
        // Check for low stock products
        const lowStockProducts = document.querySelectorAll('tr.table-warning');
        if (lowStockProducts.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Low Stock Alert',
                html: `There are ${lowStockProducts.length} products with low stock levels. Please inform the administrator to restock these items.`,
                confirmButtonText: 'OK'
            });
        }
    });
</script>

</body>
</html>
