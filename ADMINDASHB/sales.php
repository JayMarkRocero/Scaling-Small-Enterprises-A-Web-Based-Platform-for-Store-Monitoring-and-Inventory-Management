<?php
// Database Connection
require_once '../DATABASE/db.php';

// stored procedure
$stmt = $conn->prepare("CALL Sales()");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse bg-dark text-white">
            <div class="p-3 fs-5 fw-bold border-bottom">INVENTORY SYSTEM</div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="usermanagement.php"><i class="bi bi-people"></i> User Management</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="product.php"><i class="bi bi-box"></i> Products</a></li>
                <li class="nav-item"><a class="nav-link active text-white bg-secondary" href="sales.php"><i class="bi bi-cart"></i> Sales</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="sales_report.php"><i class="bi bi-graph-up"></i> Sales Report</a></li>
                <li class="nav-item mt-3"><a class="nav-link text-danger" href="../LOGIN/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Sales List</h2>
            </div>

            <table class="table table-hover" id="sales">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>In-Stock</th>
                        <th>Quantity Sold</th>
                        <th>Total Price</th>
                        <th>Date Sold</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo $row['product_name']; ?></td>
                                <td><?php echo $row['category']; ?></td>
                                <td><?php echo $row['stock']; ?></td>
                                <td><?php echo number_format($row['quantity_sold']); ?></td>
                                <td><?php echo number_format($row['total_price'], 2); ?></td>
                                <td><?php echo $row['sale_date']; ?></td>
                                <td>
                                    <a href="edit_sales.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a> |
                                    <a href="delete_sales.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8">No sales record found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        $('#salesTable').DataTable();
    });
</script>
</body>
</html>

<?php $conn->close(); ?>