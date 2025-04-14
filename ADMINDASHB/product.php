<?php
require_once '../DATABASE/db.php';

$low_stock_threshold = 5;
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $stmt = $conn->prepare("INSERT INTO products (product_name, category_id, stock_quantity, price, expiration_date, manufacturer) VALUES (?, ?, ?, ?, ?, ?)");
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
    <title>Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="bg-dark text-white position-fixed vh-100 p-3" style="width: 240px;">
    <div class="fs-5 fw-bold border-bottom pb-2 mb-3">INVENTORY SYSTEM</div>
    <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="usermanagement.php"><i class="bi bi-people"></i> User Management</a></li>
        <li class="nav-item"><a class="nav-link active text-white bg-secondary" href="#"><i class="bi bi-box"></i> Products</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="#"><i class="bi bi-cart"></i> Sales</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="#"><i class="bi bi-graph-up"></i> Sales Report</a></li>
        <li class="nav-item mt-3"><a class="nav-link text-danger" href="../LOGIN/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
    </ul>
</div>

<main style="margin-left: 240px; padding: 20px;">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Product List</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal"><i class="bi bi-plus-lg"></i> Add Product</button>
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
                <td>
                    <?= $row['stock_quantity'] ?>
                    <?php if ($row['stock_quantity'] <= $low_stock_threshold): ?>
                        <span class="badge bg-danger ms-2">Low Stock</span>
                    <?php endif; ?>
                </td>
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
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        $('#productTable').DataTable({
            order: [[0, 'desc']], // Sort by hidden ID
            columnDefs: [{ targets: 0, visible: false }]
        });

        <?php if ($alert): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: 'Product successfully <?= $alert ?>.',
            confirmButtonColor: '#3085d6'
        });
        <?php endif; ?>
    });
</script>
</body>
</html>

<?php $conn->close(); ?>
