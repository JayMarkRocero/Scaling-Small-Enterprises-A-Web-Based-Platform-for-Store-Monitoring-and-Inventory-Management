<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../LOGIN/login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'inventory_database');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch users
//$result = $conn->query("SELECT * FROM users");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h1>Welcome Admin</h1>
    <h2>User Management</h2>
    <h2>Categories</h2>
    <h2>Products</h2>
    <h2>Sales</h2>
    <h2>Sales Report</h2>
    <a href="logout.php">Logout</a>
</body>
</html>

<?php
$conn->close();
?>