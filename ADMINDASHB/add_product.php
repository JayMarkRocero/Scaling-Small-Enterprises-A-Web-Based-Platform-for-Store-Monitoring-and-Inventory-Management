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

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $category = $_POST["category"];
    $stock = $_POST["stock"];
    $buying_price = $_POST["buying_price"];
    $selling_price = $_POST["selling_price"];


    $sql = "INSERT INTO products (title, category, stock, buying_price, selling_price)
            VALUES ('$title', '$category', '$stock', '$buying_price', '$selling_price')";

    if ($conn->query($sql) === TRUE) {
        echo "Product added successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Product</title>
</head>
<body>

    <h2>Add New Product</h2>
    <form method="post" action="" enctype="multipart/form-data">
        <label>Product Title:</label>
        <input type="text" name="title" required><br><br>

        <label>Select Product Category:</label>
        <select name="category">
            <option value="Demo Category">Demo Category</option>
            <option value="Packing Materials">Packing Materials</option>
            <option value="Raw Materials">Raw Materials</option>
            <option value="Machinery">Machinery</option>
            <option value="Finished Goods">Finished Goods</option>
        </select><br><br>


        <label>Product Quantity:</label>
        <input type="number" name="stock" required><br><br>

        <label>Buying Price:</label>
        <input type="number" name="buying_price" step="0.01" required><br><br>

        <label>Selling Price:</label>
        <input type="number" name="selling_price" step="0.01" required><br><br>

        <button type="submit">Add Product</button>
    </form>

</body>
</html>

<?php $conn->close(); ?>
