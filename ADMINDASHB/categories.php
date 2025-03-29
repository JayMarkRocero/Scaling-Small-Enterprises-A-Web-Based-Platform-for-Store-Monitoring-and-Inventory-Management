<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "inventory_database");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Enable error reporting
$conn->set_charset("utf8mb4");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$edit_mode = false;
$category_name = "";

// Handle Add or Update Category
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = $_POST['category_name'];

    if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
        // Update existing category
        $category_id = $_POST['category_id'];
        $sql = "UPDATE categories SET name='$category_name' WHERE id=$category_id";
        $conn->query($sql);
    } else {
        // Add new category
        $sql = "INSERT INTO categories (name) VALUES ('$category_name')";
        $conn->query($sql);
    }

    // Redirect to prevent form resubmission
    header("Location: categories.php");
    exit();
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM categories WHERE id=$id");

    // Redirect to prevent issues with refresh
    header("Location: categories.php");
    exit();
}

// Handle Edit Category (Fetch existing category for editing)
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM categories WHERE id=$id");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $category_name = $row['name'];
    }
}

// Fetch all categories
$result = $conn->query("SELECT * FROM categories");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Category Management</title>
</head>
<body>

<!-- Sidebar Menu -->
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

<!-- Main Content -->
<div style="margin-left: 22%; padding: 20px;">
    <h3><?php echo $edit_mode ? "EDIT CATEGORY" : "ADD NEW CATEGORY"; ?></h3>
    <form method="post">
        <input type="hidden" name="category_id" value="<?php echo isset($_GET['edit']) ? $_GET['edit'] : ''; ?>">
        <input type="text" name="category_name" placeholder="Category Name" value="<?php echo $category_name; ?>" required>
        <button type="submit"><?php echo $edit_mode ? "Update Category" : "Add Category"; ?></button>
        <?php if ($edit_mode): ?>
            <a href="categories.php"><button>Cancel</button></a>
        <?php endif; ?>
    </form>

    <h3>All Categories</h3>
    <table border="1">
        <tr>
            <th>#</th>
            <th>Categories</th>
            <th>Actions</th>
        </tr>
        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $i++; ?></td>
            <td><?php echo $row['name']; ?></td>
            <td>
                <a href="?edit=<?php echo $row['id']; ?>">Edit</a> | 
                <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
