<?php
class DatabaseOperations {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function addOrder($staffId, $productId, $quantity) {
        try {
            $this->conn->begin_transaction();

            // Check stock
            $checkStock = "SELECT stock_quantity, price FROM products WHERE id = ?";
            $stmt = $this->conn->prepare($checkStock);
            if ($stmt === false) {
                $error = $this->conn->error;
                error_log("Stock check error: " . $error);
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Error checking stock: ' . $error];
            }

            $stmt->bind_param("i", $productId);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                error_log("Stock check execute error: " . $error);
                $stmt->close();
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Error executing stock check: ' . $error];
            }

            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$product) {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Product not found'];
            }

            if ($product['stock_quantity'] < $quantity) {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Insufficient stock. Available: ' . $product['stock_quantity']];
            }

            // Insert order
            $insertOrder = "INSERT INTO orders (user_id, order_date, status) VALUES (?, NOW(), 'Pending')";
            $stmt = $this->conn->prepare($insertOrder);
            if ($stmt === false) {
                $error = $this->conn->error;
                error_log("Order insert error: " . $error);
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Error creating order: ' . $error];
            }

            $stmt->bind_param("i", $staffId);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                error_log("Order insert execute error: " . $error);
                $stmt->close();
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Error executing order insert: ' . $error];
            }

            $orderId = $this->conn->insert_id;
            $stmt->close();

            // Insert order item
            $totalPrice = $product['price'] * $quantity;
            $insertItem = "INSERT INTO order_items (order_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($insertItem);
            if ($stmt === false) {
                $error = $this->conn->error;
                error_log("Order item insert error: " . $error);
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Error creating order item: ' . $error];
            }

            $stmt->bind_param("iiid", $orderId, $productId, $quantity, $totalPrice);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                error_log("Order item insert execute error: " . $error);
                $stmt->close();
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Error executing order item insert: ' . $error];
            }
            $stmt->close();

            // Update stock
            $updateStock = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $stmt = $this->conn->prepare($updateStock);
            if ($stmt === false) {
                $error = $this->conn->error;
                error_log("Stock update error: " . $error);
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Error updating stock: ' . $error];
            }

            $stmt->bind_param("ii", $quantity, $productId);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                error_log("Stock update execute error: " . $error);
                $stmt->close();
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Error executing stock update: ' . $error];
            }
            $stmt->close();

            $this->conn->commit();
            return [
                'success' => true, 
                'message' => 'Order #' . $orderId . ' has been created successfully',
                'order_id' => $orderId
            ];

        } catch (Exception $e) {
            error_log("Error in addOrder: " . $e->getMessage());
            $this->conn->rollback();
            return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }

    public function cancelOrder($orderId, $staffId) {
        $this->conn->begin_transaction();

        // Check order
        $checkOrder = "SELECT status FROM orders WHERE order_id = ? AND user_id = ? AND status = 'Pending'";
        $stmt = $this->conn->prepare($checkOrder);
        if ($stmt === false) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Database error'];
        }

        $stmt->bind_param("ii", $orderId, $staffId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Order not found or cannot be cancelled'];
        }

        // Restore stock
        $restoreStock = "UPDATE products p 
                        JOIN order_items oi ON p.id = oi.product_id 
                        SET p.stock_quantity = p.stock_quantity + oi.quantity 
                        WHERE oi.order_id = ?";
        $stmt = $this->conn->prepare($restoreStock);
        if ($stmt === false) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Database error'];
        }

        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $stmt->close();

        // Update order status
        $updateOrder = "UPDATE orders SET status = 'Cancelled' WHERE order_id = ?";
        $stmt = $this->conn->prepare($updateOrder);
        if ($stmt === false) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Database error'];
        }

        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $stmt->close();

        $this->conn->commit();
        return ['success' => true, 'message' => 'Order cancelled successfully'];
    }

    public function getOrders($staffId) {
        // Simple query to get orders with their items
        $query = "SELECT o.*, oi.*, p.product_name 
                 FROM orders o 
                 INNER JOIN order_items oi ON o.order_id = oi.order_id 
                 INNER JOIN products p ON oi.product_id = p.id 
                 WHERE o.user_id = ? 
                 ORDER BY o.order_date DESC";
        
        try {
            error_log("Getting orders for staff ID: " . $staffId);
            
            $stmt = $this->conn->prepare($query);
            if ($stmt === false) {
                error_log("Query preparation failed: " . $this->conn->error);
                return $this->getEmptyResult();
            }

            $stmt->bind_param("i", $staffId);
            if (!$stmt->execute()) {
                error_log("Query execution failed: " . $stmt->error);
                $stmt->close();
                return $this->getEmptyResult();
            }

            $result = $stmt->get_result();
            $stmt->close();
            
            if (!$result) {
                error_log("No results found for staff ID: " . $staffId);
                return $this->getEmptyResult();
            }
            
            // Log the number of rows found
            $numRows = $result->num_rows;
            error_log("Found " . $numRows . " orders for staff ID: " . $staffId);
            
            // Log the first row to see what data we're getting
            if ($numRows > 0) {
                $firstRow = $result->fetch_assoc();
                error_log("First order data: " . print_r($firstRow, true));
                // Reset the result pointer
                $result->data_seek(0);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error in getOrders: " . $e->getMessage());
            return $this->getEmptyResult();
        }
    }

    private function getEmptyResult() {
        $emptyQuery = "SELECT 
            NULL as order_id,
            NULL as order_date,
            NULL as status,
            NULL as created_at,
            NULL as quantity,
            NULL as total_price,
            NULL as product_name
            FROM DUAL
            WHERE 1=0";
        return $this->conn->query($emptyQuery);
    }

    public function getAvailableProducts() {
        $query = "SELECT id, product_name, price, stock_quantity 
                 FROM products 
                 WHERE stock_quantity > 0 
                 ORDER BY product_name";
        return $this->conn->query($query);
    }
} 