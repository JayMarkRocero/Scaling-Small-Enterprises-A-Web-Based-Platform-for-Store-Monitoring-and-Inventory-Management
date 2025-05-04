<?php
class Order {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getOrders($limit, $offset) {
        try {
            // Check if the stored procedure exists
            $checkProc = $this->conn->query("SHOW PROCEDURE STATUS WHERE Name = 'GetOrdersWithPagination'");
            if ($checkProc->num_rows === 0) {
                error_log("Stored procedure GetOrdersWithPagination does not exist");
                return [];
            }

            $stmt = $this->conn->prepare("CALL GetOrdersWithPagination(?, ?)");
            if (!$stmt) {
                error_log("Error preparing statement: " . $this->conn->error);
                return [];
            }

            $stmt->bind_param("ii", $limit, $offset);
            
            if (!$stmt->execute()) {
                error_log("Error executing statement: " . $stmt->error);
                return [];
            }

            $result = $stmt->get_result();
            if (!$result) {
                error_log("Error getting result: " . $stmt->error);
                return [];
            }

            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        } catch (Exception $e) {
            error_log("Error in getOrders: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalOrders() {
        try {
            // Check if the stored procedure exists
            $checkProc = $this->conn->query("SHOW PROCEDURE STATUS WHERE Name = 'GetTotalOrders'");
            if ($checkProc->num_rows === 0) {
                error_log("Stored procedure GetTotalOrders does not exist");
                return 0;
            }

            $result = $this->conn->query("CALL GetTotalOrders()");
            if (!$result) {
                error_log("Error executing GetTotalOrders: " . $this->conn->error);
                return 0;
            }

            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error in getTotalOrders: " . $e->getMessage());
            return 0;
        }
    }

    public function searchOrders($search, $limit, $offset) {
        try {
            // Check if the stored procedure exists
            $checkProc = $this->conn->query("SHOW PROCEDURE STATUS WHERE Name = 'SearchOrders'");
            if ($checkProc->num_rows === 0) {
                error_log("Stored procedure SearchOrders does not exist");
                return [];
            }

            $stmt = $this->conn->prepare("CALL SearchOrders(?, ?, ?)");
            if (!$stmt) {
                error_log("Error preparing statement: " . $this->conn->error);
                return [];
            }

            $stmt->bind_param("sii", $search, $limit, $offset);
            
            if (!$stmt->execute()) {
                error_log("Error executing statement: " . $stmt->error);
                return [];
            }

            $result = $stmt->get_result();
            if (!$result) {
                error_log("Error getting result: " . $stmt->error);
                return [];
            }

            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        } catch (Exception $e) {
            error_log("Error in searchOrders: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalSearchedOrders($search) {
        try {
            // Check if the stored procedure exists
            $checkProc = $this->conn->query("SHOW PROCEDURE STATUS WHERE Name = 'GetTotalSearchedOrders'");
            if ($checkProc->num_rows === 0) {
                error_log("Stored procedure GetTotalSearchedOrders does not exist");
                return 0;
            }

            $stmt = $this->conn->prepare("CALL GetTotalSearchedOrders(?)");
            if (!$stmt) {
                error_log("Error preparing statement: " . $this->conn->error);
                return 0;
            }

            $stmt->bind_param("s", $search);
            
            if (!$stmt->execute()) {
                error_log("Error executing statement: " . $stmt->error);
                return 0;
            }

            $result = $stmt->get_result();
            if (!$result) {
                error_log("Error getting result: " . $stmt->error);
                return 0;
            }

            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error in getTotalSearchedOrders: " . $e->getMessage());
            return 0;
        }
    }

    public function updateOrderStatus($order_id, $status) {
        try {
            // Check if the stored procedure exists
            $checkProc = $this->conn->query("SHOW PROCEDURE STATUS WHERE Name = 'UpdateOrderStatus'");
            if ($checkProc->num_rows === 0) {
                error_log("Stored procedure UpdateOrderStatus does not exist");
                return false;
            }

            $stmt = $this->conn->prepare("CALL UpdateOrderStatus(?, ?)");
            if (!$stmt) {
                error_log("Error preparing statement: " . $this->conn->error);
                return false;
            }

            $stmt->bind_param("is", $order_id, $status);
            
            if (!$stmt->execute()) {
                error_log("Error executing statement: " . $stmt->error);
                return false;
            }

            $stmt->close();
            return true;
        } catch (Exception $e) {
            error_log("Error in updateOrderStatus: " . $e->getMessage());
            return false;
        }
    }

    public function getSalesReport($start_date, $end_date) {
        try {
            // Check if the stored procedure exists
            $checkProc = $this->conn->query("SHOW PROCEDURE STATUS WHERE Name = 'GetSalesReport'");
            if ($checkProc->num_rows === 0) {
                error_log("Stored procedure GetSalesReport does not exist");
                return [];
            }

            $stmt = $this->conn->prepare("CALL GetSalesReport(?, ?)");
            if (!$stmt) {
                error_log("Error preparing statement: " . $this->conn->error);
                return [];
            }

            $stmt->bind_param("ss", $start_date, $end_date);
            
            if (!$stmt->execute()) {
                error_log("Error executing statement: " . $stmt->error);
                return [];
            }

            $result = $stmt->get_result();
            if (!$result) {
                error_log("Error getting result: " . $stmt->error);
                return [];
            }

            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        } catch (Exception $e) {
            error_log("Error in getSalesReport: " . $e->getMessage());
            return [];
        }
    }
}
