<?php
class Product {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Fetch all products with category name and total sold
    public function getAllProducts() {
    $query = "CALL GetAllProductsWithDetails()";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

    // Get low stock products (e.g., stock_quantity <= 10)
    public function getLowStockProducts($threshold = 10) {
        $stmt = $this->conn->prepare("CALL GetLowStockProducts(?)");
        $stmt->bind_param("i", $threshold);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $data;
    }

    // Search products by name or category
    public function searchProducts($search) {
    $stmt = $this->conn->prepare("CALL SearchProducts(?)");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

    // Add a new product
    public function addProduct($name, $category_id, $stock, $price, $expiration, $manufacturer) {
        $stmt = $this->conn->prepare("CALL AddProduct(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siidss", $name, $category_id, $stock, $price, $expiration, $manufacturer);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // Edit a product
    public function editProduct($id, $name, $category_id, $stock, $price, $expiration, $manufacturer) {
        $stmt = $this->conn->prepare("CALL EditProduct(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isiidss", $id, $name, $category_id, $stock, $price, $expiration, $manufacturer);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // Delete a product
    public function deleteProduct($id) {
        $stmt = $this->conn->prepare("CALL DeleteProduct(?)");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // Get all categories (for dropdowns)
    public function getCategories() {
        $result = $this->conn->query("CALL GetAllCategories()");
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $result->free(); while ($this->conn->more_results() && $this->conn->next_result()) { $this->conn->use_result(); }
        return $data;
    }
}
?> 