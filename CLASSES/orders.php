<?php
class Order {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function updateOrderStatus($orderId, $newStatus) {
        $stmt = $this->conn->prepare("CALL UpdateOrderStatus(?, ?)");
        $stmt->bind_param("is", $orderId, $newStatus);
        $stmt->execute();
        $stmt->close();
    }

    public function fetchAllOrders() {
        $stmt = $this->conn->prepare("CALL GetAllOrders()");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }      
}
