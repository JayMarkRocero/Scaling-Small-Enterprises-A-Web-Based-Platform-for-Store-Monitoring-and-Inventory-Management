<?php
// Database Connection
$servername = "localhost";
$username = "root";  // Change if necessary
$password = "";      // Change if necessary
$dbname = "inventory_database";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Products
$sql = "SELECT * FROM products";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<table border="1" width="20%" cellspacing="0" cellpadding="5" style="float: left; height: 100vh;">
    <tr>
        <td><b>INVENTORY SYSTEM</b></td>
    </tr>
    <tr><td><a href="../ADMINDASHB/dashboard.php">Dashboard</a></td></tr>
    <tr><td><a href="../ADMINDASHB/usermanagement.php">User Management</a></td></tr>
    <tr><td><a href="../ADMINDASHB/categories.php">Categories</a></td></tr>
    <tr><td><a href="../ADMINDASHB/product.php">Products</a></td></tr>
    <tr><td><a href="#">Media Files</a></td></tr>
    <tr><td><a href="#">Sales</a></td></tr>
    <tr><td><a href="#">Sales Report</a></td></tr>
    <tr><td><a href="../LOGIN/logout.php">Logout</a></td></tr>
</table>
<html>
<head>
    <title>Product List</title>
</head>
<body>
    <h2>All Products</h2>
    <a href="add_product.php">Add New Product</a>
    <table border="1">
        <thead>
            <tr>
                <th>#</th>
                <th>Product Title</th>
                <th>Category</th>
                <th>In-Stock</th>
                <th>Buying Price</th>
                <th>Selling Price</th>
                <th>Product Added</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo $row['title']; ?></td>
                        <td><?php echo $row['category']; ?></td>
                        <td><?php echo $row['stock']; ?></td>
                        <td><?php echo number_format($row['buying_price'], 2); ?></td>
                        <td><?php echo number_format($row['selling_price'], 2); ?></td>
                        <td><?php echo $row['product_added']; ?></td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $row['id']; ?>">Edit</a> |
                            <a href="delete_product.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9">No products found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

<?php $conn->close(); ?>
