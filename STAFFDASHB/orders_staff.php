<?php
session_start();
require_once '../DATABASE/db.php';
require_once '../CLASSES/order_staff.php';

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

$db = new DatabaseOperations($conn);
$staffId = $_SESSION['user_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!isset($_POST['action'])) {
        echo json_encode(['success' => false, 'message' => 'No action specified']);
        exit;
    }

    switch ($_POST['action']) {
        case 'add_order':
            if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            $result = $db->addOrder($staffId, intval($_POST['product_id']), intval($_POST['quantity']));
            echo json_encode($result);
            break;

        case 'cancel_order':
            if (!isset($_POST['order_id'])) {
                echo json_encode(['success' => false, 'message' => 'Order ID is required']);
                exit;
            }
            $result = $db->cancelOrder(intval($_POST['order_id']), $staffId);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Get orders and products for display
$orders = $db->getOrders($staffId);
$products = $db->getAvailableProducts();

// Debug information
error_log("Staff ID: " . $staffId);
error_log("Orders object: " . print_r($orders, true));
error_log("Number of orders: " . ($orders ? $orders->num_rows : 0));

// Check if orders exist in database directly
$checkQuery = "SELECT * FROM orders WHERE user_id = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("i", $staffId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
error_log("Direct DB check - Number of orders: " . $checkResult->num_rows);
$checkStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../STAFFDASHB/staff.css">
    <style>
        .product-details { font-size: 0.9em; color: #666; }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .table-custom th { background-color: #f8f9fa; font-weight: 600; }
        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar collapse show">
            <div class="sidebar-header">INVENTORY SYSTEM</div>
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
            <h2 class="mb-4">My Orders</h2>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOrderModal">
                    <i class="bi bi-plus-circle"></i> Add New Order
                </button>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Order submitted successfully!</div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">An error occurred while processing your order.</div>
            <?php endif; ?>

            <?php if (isset($_GET['cancelled'])): ?>
                <div class="alert alert-info">Order has been cancelled.</div>
            <?php endif; ?>

            <!-- Add Order Modal -->
            <div class="modal fade" id="addOrderModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Order</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addOrderForm">
                                <input type="hidden" name="action" value="add_order">
                                <div class="mb-3">
                                    <label for="productSelect" class="form-label">Select Product</label>
                                    <select class="form-select" id="productSelect" name="product_id" required>
                                        <option value="">Choose a product...</option>
                                        <?php while ($product = $products->fetch_assoc()): ?>
                                            <option value="<?= $product['id'] ?>" 
                                                    data-price="<?= $product['price'] ?>"
                                                    data-stock="<?= $product['stock_quantity'] ?>">
                                                <?= htmlspecialchars($product['product_name']) ?> 
                                                (₱<?= number_format($product['price'], 2) ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                    <small class="text-muted">Available stock: <span id="availableStock">0</span></small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Total Price</label>
                                    <div class="form-control-plaintext" id="totalPrice">₱0.00</div>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Add to Order</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="order-card">
                <h4>Order History</h4>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Products</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders && $orders->num_rows > 0): ?>
                                <?php while ($order = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $order['order_id'] ?></td>
                                        <td><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></td>
                                        <td>
                                            <div class="product-details">
                                                <?= $order['product_name'] ?> (<?= $order['quantity'] ?> x ₱<?= number_format($order['total_price'], 2) ?>)
                                            </div>
                                        </td>
                                        <td>₱<?= number_format($order['total_price'], 2) ?></td>
                                        <td>
                                            <span class="status-badge 
                                                <?= $order['status'] == 'Pending' ? 'status-pending' : 
                                                    ($order['status'] == 'Approved' ? 'status-approved' : 'status-cancelled') ?>">
                                                <?php
                                                    if (strtolower($order['status']) === 'cancelled') {
                                                        echo '<span style="color:#dc3545;font-weight:bold;">CANCELLED</span>';
                                                    } else {
                                                        echo strtoupper($order['status']);
                                                    }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($order['status'] == 'Pending'): ?>
                                                <form method="POST" class="cancel-order-form" style="display: inline;">
                                                    <input type="hidden" name="action" value="cancel_order">
                                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                    <button type="submit" name="cancel_order" class="btn btn-danger btn-sm">
                                                        <i class="bi bi-x-circle"></i> Cancel
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="order_details.php?id=<?= $order['order_id'] ?>" class="btn btn-primary btn-sm">
                                                <i class="bi bi-eye"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                            <p class="mt-2">No orders found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('productSelect');
    const quantityInput = document.getElementById('quantity');
    const availableStockSpan = document.getElementById('availableStock');
    const totalPriceDiv = document.getElementById('totalPrice');
    const addOrderForm = document.getElementById('addOrderForm');

    function updateTotalPrice() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const price = selectedOption ? parseFloat(selectedOption.dataset.price) : 0;
        const quantity = parseInt(quantityInput.value) || 0;
        const total = price * quantity;
        totalPriceDiv.textContent = `₱${total.toFixed(2)}`;
    }

    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption) {
            const stock = parseInt(selectedOption.dataset.stock);
            availableStockSpan.textContent = stock;
            quantityInput.max = stock;
            quantityInput.value = 1;
            updateTotalPrice();
        } else {
            availableStockSpan.textContent = '0';
            quantityInput.value = '';
            totalPriceDiv.textContent = '₱0.00';
        }
    });

    quantityInput.addEventListener('input', function() {
        const max = parseInt(this.max);
        if (this.value > max) {
            this.value = max;
        }
        updateTotalPrice();
    });

    // Handle form submission
    addOrderForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        Swal.fire({
            title: 'Processing Order',
            text: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch('orders_staff.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('addOrderModal'));
                modal.hide();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to add order'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while processing your request'
            });
        });
    });

    // Handle order cancellation
    document.querySelectorAll('.cancel-order-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No, keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Cancelling Order',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('orders_staff.php', {
                        method: 'POST',
                        body: new FormData(this)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Cancelled!',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message || 'Failed to cancel order'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while processing your request'
                        });
                    });
                }
            });
        });
    });

    // Reset form when modal is closed
    document.getElementById('addOrderModal').addEventListener('hidden.bs.modal', function () {
        addOrderForm.reset();
        availableStockSpan.textContent = '0';
        totalPriceDiv.textContent = '₱0.00';
    });
});
</script>
</body>
</html>
