<?php
require_once '../DATABASE/db.php';

// Connect to database
$db = new Database();
$conn = $db->getConnection();

// Fetch product options for dropdown
$productOptions = $conn->query("SELECT id, product_name FROM products");

// Handle form submission
$showSuccess = false;
if (isset($_POST['add_sale'])) {
    $product_id = $_POST['product_id'];
    $quantity_sold = $_POST['quantity_sold'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Prepare statement to add sale
        $stmt = $conn->prepare("CALL AddSale(?, ?)");
        $stmt->bind_param("ii", $product_id, $quantity_sold);
        
        if ($stmt->execute()) {
            // Call the stored procedure to update product quantity
            $updateStockStmt = $conn->prepare("CALL UpdateProductQuantity(?, ?)");
            $updateStockStmt->bind_param("ii", $product_id, $quantity_sold);
            if (!$updateStockStmt->execute()) {
                throw new Exception("Failed to update stock: " . $updateStockStmt->error);
            }
            $updateStockStmt->close();

            $showSuccess = true;
        } else {
            throw new Exception("Failed to add sale: " . $stmt->error);
        }

        $stmt->close();
        $conn->commit(); 
    } catch (Exception $e) {
        $conn->rollback(); 
        echo "Error: " . $e->getMessage(); 
    }
}

// Fetch sales records using stored procedure
$salesList = $conn->query("CALL GetSalesList()");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .sidebar { min-height: 100vh; background-color: #212529; color: white; }
        .sidebar-header { padding: 20px 15px; background-color: #111418; font-weight: bold; font-size: 1.2rem; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link i { margin-right: 10px; }
        .content { padding: 30px; }
        .table th, .table td { vertical-align: middle; }
    </style>
</head>
<body>

<?php if ($showSuccess): ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        Swal.fire({
            icon: 'success',
            title: 'Sale added successfully!',
            showConfirmButton: false,
            timer: 2000
        });
    });
</script>
<?php endif; ?>

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

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Sales Records</h2>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSaleModal">
                    <i class="bi bi-plus-circle"></i> Add Sale
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
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
                            <td><?= $sale['id']; ?></td>
                            <td><?= htmlspecialchars($sale['product_name']); ?></td>
                            <td><?= $sale['quantity_sold']; ?></td>
                            <td>₱<?= number_format($sale['total_price'], 2); ?></td>
                            <td><?= $sale['sale_date']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Modal for Add Sale -->
<div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSaleModalLabel"><i class="bi bi-cart-plus"></i> Add Sale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="product_id" class="form-label">Product</label>
                    <select name="product_id" id="product_id" class="form-select" required>
                        <option value="">Select Product</option>
                        <?php $productOptions->data_seek(0); while ($row = $productOptions->fetch_assoc()): ?>
                            <option value="<?= $row['id']; ?>"><?= htmlspecialchars($row['product_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="quantity_sold" class="form-label">Quantity</label>
                    <input type="number" name="quantity_sold" id="quantity_sold" class="form-control" min="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="add_sale" class="btn btn-primary">Submit Sale</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>