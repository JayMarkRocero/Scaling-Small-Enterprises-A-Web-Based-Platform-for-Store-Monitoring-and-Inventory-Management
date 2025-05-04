<?php
class Product {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Fetch all products with category name and total sold
    public function getAllProducts() {
        $sql = "SELECT 
                    p.*, 
                    c.category_name, 
                    COALESCE(SUM(s.quantity_sold), 0) AS total_sold
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN sales s ON s.product_id = p.id
                GROUP BY p.id";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Get low stock products (e.g., stock_quantity <= 10)
    public function getLowStockProducts($threshold = 10) {
        $sql = "SELECT p.*, c.category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.stock_quantity <= ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $threshold);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Search products by name or category
    public function searchProducts($search) {
        $sql = "SELECT p.*, c.category_name, COALESCE(SUM(s.quantity_sold), 0) AS total_sold
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN sales s ON s.product_id = p.id
                WHERE p.product_name LIKE ? OR c.category_name LIKE ?
                GROUP BY p.id";
        $like = "%$search%";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Add a new product
    public function addProduct($name, $category_id, $stock, $price, $expiration, $manufacturer) {
        $sql = "INSERT INTO products (product_name, category_id, stock_quantity, price, expiration_date, manufacturer)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("siidss", $name, $category_id, $stock, $price, $expiration, $manufacturer);
        return $stmt->execute();
    }

    // Edit a product
    public function editProduct($id, $name, $category_id, $stock, $price, $expiration, $manufacturer) {
        $sql = "UPDATE products SET product_name=?, category_id=?, stock_quantity=?, price=?, expiration_date=?, manufacturer=?
                WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("siidssi", $name, $category_id, $stock, $price, $expiration, $manufacturer, $id);
        return $stmt->execute();
    }

    // Delete a product
    public function deleteProduct($id) {
        $sql = "DELETE FROM products WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Get all categories (for dropdowns)
    public function getCategories() {
        $sql = "SELECT id, category_name FROM categories";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
?> 