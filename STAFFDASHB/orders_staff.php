<?php
session_start();
require_once '../DATABASE/db.php';

$db = new Database();
$conn = $db->getConnection();
$staffId = $_SESSION['user_id'];

if (!isset($_SESSION['user_id'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_order':
                    if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
                        throw new Exception('Missing required fields');
                    }

                    $productId = intval($_POST['product_id']);
                    $quantity = intval($_POST['quantity']);

                    if ($productId <= 0 || $quantity <= 0) {
                        throw new Exception('Invalid product or quantity');
                    }

                    // Start transaction
                    $conn->begin_transaction();

                    // Check product availability
                    $checkStockQuery = "SELECT stock_quantity, price, product_name FROM products WHERE id = ? FOR UPDATE";
                    $stmt = $conn->prepare($checkStockQuery);
                    if (!$stmt) {
                        throw new Exception('Error preparing stock check statement: ' . $conn->error);
                    }

                    $stmt->bind_param("i", $productId);
                    if (!$stmt->execute()) {
                        throw new Exception('Error executing stock check: ' . $stmt->error);
                    }

                    $result = $stmt->get_result();
                    $product = $result->fetch_assoc();
                    $stmt->close();

                    if (!$product) {
                        throw new Exception('Product not found');
                    }

                    if ($product['stock_quantity'] < $quantity) {
                        throw new Exception('Insufficient stock available for ' . $product['product_name'] . '. Only ' . $product['stock_quantity'] . ' items left.');
                    }

                    // Calculate total price
                    $totalPrice = $product['price'] * $quantity;

                    // Insert order
                    $orderQuery = "INSERT INTO orders (user_id, order_date, status) VALUES (?, NOW(), 'Pending')";
                    $stmt = $conn->prepare($orderQuery);
                    if (!$stmt) {
                        throw new Exception('Error preparing order statement: ' . $conn->error);
                    }

                    $stmt->bind_param("i", $staffId);
                    if (!$stmt->execute()) {
                        throw new Exception('Error executing order statement: ' . $stmt->error);
                    }

                    $orderId = $conn->insert_id;
                    $stmt->close();

                    // Insert order item
                    $itemQuery = "INSERT INTO order_items (order_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($itemQuery);
                    if (!$stmt) {
                        throw new Exception('Error preparing item statement: ' . $conn->error);
                    }

                    $stmt->bind_param("iiid", $orderId, $productId, $quantity, $totalPrice);
                    if (!$stmt->execute()) {
                        throw new Exception('Error executing item statement: ' . $stmt->error);
                    }
                    $stmt->close();

                    // Update product stock
                    $updateStockQuery = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                    $stmt = $conn->prepare($updateStockQuery);
                    if (!$stmt) {
                        throw new Exception('Error preparing stock update statement: ' . $conn->error);
                    }

                    $stmt->bind_param("ii", $quantity, $productId);
                    if (!$stmt->execute()) {
                        throw new Exception('Error executing stock update: ' . $stmt->error);
                    }
                    $stmt->close();

                    // Commit transaction
                    $conn->commit();

                    $response = [
                        'success' => true,
                        'message' => 'Order #' . $orderId . ' has been created successfully',
                        'order_id' => $orderId
                    ];
                    break;

                case 'cancel_order':
                    if (!isset($_POST['order_id'])) {
                        throw new Exception('Order ID is required');
                    }

                    $orderId = intval($_POST['order_id']);

                    if ($orderId <= 0) {
                        throw new Exception('Invalid order ID');
                    }

                    // Start transaction
                    $conn->begin_transaction();

                    // Check if order exists and belongs to the user
                    $checkOrderQuery = "SELECT status FROM orders WHERE order_id = ? AND user_id = ? FOR UPDATE";
                    $stmt = $conn->prepare($checkOrderQuery);
                    if (!$stmt) {
                        throw new Exception('Error preparing order check statement: ' . $conn->error);
                    }

                    $stmt->bind_param("ii", $orderId, $staffId);
                    if (!$stmt->execute()) {
                        throw new Exception('Error executing order check: ' . $stmt->error);
                    }

                    $result = $stmt->get_result();
                    $order = $result->fetch_assoc();
                    $stmt->close();

                    if (!$order) {
                        throw new Exception('Order not found or you do not have permission to cancel it');
                    }

                    if ($order['status'] !== 'Pending') {
                        throw new Exception('Only pending orders can be cancelled');
                    }

                    // Get order items to restore stock
                    $getItemsQuery = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
                    $stmt = $conn->prepare($getItemsQuery);
                    if (!$stmt) {
                        throw new Exception('Error preparing items statement: ' . $conn->error);
                    }

                    $stmt->bind_param("i", $orderId);
                    if (!$stmt->execute()) {
                        throw new Exception('Error executing items statement: ' . $stmt->error);
                    }

                    $items = $stmt->get_result();
                    $stmt->close();

                    // Restore stock for each item
                    while ($item = $items->fetch_assoc()) {
                        $updateStockQuery = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
                        $stmt = $conn->prepare($updateStockQuery);
                        if (!$stmt) {
                            throw new Exception('Error preparing stock update statement: ' . $conn->error);
                        }

                        $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                        if (!$stmt->execute()) {
                            throw new Exception('Error executing stock update: ' . $stmt->error);
                        }
                        $stmt->close();
                    }

                    // Update order status to cancelled
                    $updateOrderQuery = "UPDATE orders SET status = 'Cancelled' WHERE order_id = ?";
                    $stmt = $conn->prepare($updateOrderQuery);
                    if (!$stmt) {
                        throw new Exception('Error preparing order update statement: ' . $conn->error);
                    }

                    $stmt->bind_param("i", $orderId);
                    if (!$stmt->execute()) {
                        throw new Exception('Error executing order update: ' . $stmt->error);
                    }
                    $stmt->close();

                    // Commit transaction
                    $conn->commit();

                    $response = [
                        'success' => true,
                        'message' => 'Order #' . $orderId . ' has been cancelled successfully'
                    ];
                    break;

                default:
                    throw new Exception('Invalid action');
            }
        } else {
            throw new Exception('No action specified');
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn->in_transaction) {
            $conn->rollback();
        }
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }

    echo json_encode($response);
    exit;
}

// Fetch staff's orders
$ordersQuery = "SELECT o.order_id, o.order_date, o.status, 
                COUNT(oi.order_item_id) as item_count, 
                COALESCE(SUM(oi.total_price), 0) as total_amount
                FROM orders o
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                WHERE o.user_id = ? AND o.status != 'Cancelled'
                GROUP BY o.order_id, o.order_date, o.status
                ORDER BY o.order_date DESC";

$stmt = $conn->prepare($ordersQuery);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $staffId);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$orders = $stmt->get_result();
if (!$orders) {
    die("Error getting result: " . $stmt->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../STAFFDASHB/staff.css">
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
            <div class="modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addOrderModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addOrderModalLabel">Add New Order</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addOrderForm">
                                <input type="hidden" name="action" value="add_order">
                                <div class="mb-3">
                                    <label for="productSelect" class="form-label">Select Product</label>
                                    <select class="form-select" id="productSelect" name="product_id" required>
                                        <option value="">Choose a product...</option>
                                        <?php
                                        $productsQuery = "SELECT id, product_name, price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY product_name";
                                        $productsResult = $conn->query($productsQuery);
                                        while ($product = $productsResult->fetch_assoc()):
                                        ?>
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
                                <th>Items</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders->num_rows > 0): ?>
                                <?php while ($order = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $order['order_id'] ?></td>
                                        <td><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></td>
                                        <td><?= $order['item_count'] ?></td>
                                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="status-badge 
                                                <?= $order['status'] == 'Pending' ? 'status-pending' : 
                                                    ($order['status'] == 'Approved' ? 'status-approved' : 'status-cancelled') ?>">
                                                <?= $order['status'] ?>
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
        
        // Show loading state
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
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addOrderModal'));
                modal.hide();
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    // Reload the page to show the new order
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
                    // Show loading state
                    Swal.fire({
                        title: 'Cancelling Order',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Submit the form
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
                                // Reload the page to show the updated order list
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
