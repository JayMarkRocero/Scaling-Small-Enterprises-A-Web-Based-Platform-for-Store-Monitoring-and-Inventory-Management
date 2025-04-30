<?php
require_once '../DATABASE/db.php';

$db = new Database();
$conn = $db->getConnection();

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $stmt = $conn->prepare ("INSERT INTO products (product_name, category_id, stock_quantity, price, expiration_date, manufacturer) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siidss", $_POST['product_name'], $_POST['category_id'], $_POST['stock_quantity'], $_POST['price'], $_POST['expiration_date'], $_POST['manufacturer']);
        $stmt->execute();
        header("Location: ".$_SERVER['PHP_SELF']."?alert=added");
        exit;
    } elseif (isset($_POST['edit_product'])) {
        $stmt = $conn->prepare("UPDATE products SET product_name=?, stock_quantity=?, price=?, expiration_date=?, manufacturer=? WHERE id=?");
        $stmt->bind_param("sidssi", $_POST['product_name'], $_POST['stock_quantity'], $_POST['price'], $_POST['expiration_date'], $_POST['manufacturer'], $_POST['product_id']);
        $stmt->execute();
        header("Location: ".$_SERVER['PHP_SELF']."?alert=edited");
        exit;
    } elseif (isset($_POST['delete_product'])) {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $_POST['product_id']);
        $stmt->execute();
        header("Location: ".$_SERVER['PHP_SELF']."?alert=deleted");
        exit;
    }
}

if (isset($_GET['alert'])) {
    $alert = $_GET['alert'];
}

$products = $conn->query("SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");

$categoryResult = $conn->query("SELECT id, category_name FROM categories");
$categories = [];
while ($cat = $categoryResult->fetch_assoc()) {
    $categories[] = $cat;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
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
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
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
                <li class="nav-item">
                    <a class="nav-link" href="../ADMINDASHB/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link " href="../ADMINDASHB/usermanagement.php">
                        <i class="bi bi-people"></i> User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active " href="../ADMINDASHB/product.php">
                        <i class="bi bi-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-cart"></i> Sales
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-graph-up"></i> Sales Report
                    </a>
                </li>
                <li class="nav-item">
                     <a class="nav-link" href="../ADMINDASHB/orders.php">
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
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Product List</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="bi bi-plus-lg"></i> Add Product
                </button>
            </div>

            <table class="table table-hover" id="productTable">
                <thead>
                    <tr>
                        <th style="display:none;">ID</th>
                        <th>Product</th><th>Category</th><th>Qty</th><th>Price</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $products->fetch_assoc()): ?>
                        <tr>
                            <td style="display:none;"><?= $row['id'] ?></td>
                            <td><?= $row['product_name'] ?></td>
                            <td><?= $row['category_name'] ?></td>
                            <td><?= $row['stock_quantity'] ?></td>
                            <td><?= $row['price'] ?></td>
                            <td>
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#viewProductModal<?= $row['id'] ?>">View</button>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editProductModal<?= $row['id'] ?>">Edit</button>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="delete_product" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>

                        <!-- View Modal -->
                        <div class="modal fade" id="viewProductModal<?= $row['id'] ?>" tabindex="-1">
                            <div class="modal-dialog"><div class="modal-content">
                                <div class="modal-header"><h5 class="modal-title">Product Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <p><strong>Product Name:</strong> <?= $row['product_name'] ?></p>
                                    <p><strong>Category:</strong> <?= $row['category_name'] ?></p>
                                    <p><strong>Quantity:</strong> <?= $row['stock_quantity'] ?></p>
                                    <p><strong>Price:</strong> <?= $row['price'] ?></p>
                                    <p><strong>Expiration Date:</strong> <?= $row['expiration_date'] ?? 'N/A' ?></p>
                                    <p><strong>Manufacturer:</strong> <?= $row['manufacturer'] ?? 'N/A' ?></p>
                                </div>
                            </div></div>
                        </div>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editProductModal<?= $row['id'] ?>" tabindex="-1">
                            <div class="modal-dialog"><div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header"><h5>Edit Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                        <div class="mb-3"><label>Product Name</label><input type="text" name="product_name" class="form-control" value="<?= $row['product_name'] ?>"></div>
                                        <div class="mb-3"><label>Category</label>
                                            <input type="text" class="form-control" value="<?= $row['category_name'] ?>" disabled>
                                            <input type="hidden" name="category_id" value="<?= $row['category_id'] ?>">
                                        </div>
                                        <div class="mb-3"><label>Quantity</label><input type="number" name="stock_quantity" class="form-control" value="<?= $row['stock_quantity'] ?>"></div>
                                        <div class="mb-3"><label>Price</label><input type="number" name="price" class="form-control" step="0.01" value="<?= $row['price'] ?>"></div>
                                        <div class="mb-3"><label>Expiration Date</label><input type="date" name="expiration_date" class="form-control" value="<?= $row['expiration_date'] ?>"></div>
                                        <div class="mb-3"><label>Manufacturer</label><input type="text" name="manufacturer" class="form-control" value="<?= $row['manufacturer'] ?>"></div>
                                    </div>
                                    <div class="modal-footer"><button type="submit" name="edit_product" class="btn btn-primary">Save Changes</button></div>
                                </form>
                            </div></div>
                        </div>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Add Modal -->
            <div class="modal fade" id="addProductModal" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                    <form method="POST">
                        <div class="modal-header"><h5>Add Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="mb-3"><label>Product Name</label><input type="text" name="product_name" class="form-control" required></div>
                            <div class="mb-3"><label>Category</label>
                                <select name="category_id" class="form-control" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= $cat['category_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3"><label>Quantity</label><input type="number" name="stock_quantity" class="form-control" required></div>
                            <div class="mb-3"><label>Price</label><input type="number" step="0.01" name="price" class="form-control" required></div>
                            <div class="mb-3"><label>Expiration Date</label><input type="date" name="expiration_date" class="form-control"></div>
                            <div class="mb-3"><label>Manufacturer</label><input type="text" name="manufacturer" class="form-control"></div>
                        </div>
                        <div class="modal-footer"><button type="submit" name="add_product" class="btn btn-success">Add Product</button></div>
                    </form>
                </div></div>
            </div>
        </div>
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

        <?php if ($alert === 'added'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Product Added',
                text: 'The product was successfully added.',
            });
        <?php elseif ($alert === 'edited'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Product Edited',
                text: 'The product was successfully updated.',
            });
        <?php elseif ($alert === 'deleted'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Product Deleted',
                text: 'The product was successfully deleted.',
            });
        <?php endif; ?>
    });
</script>

</body>
</html>
