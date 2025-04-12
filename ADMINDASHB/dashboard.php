<?php
// Connect to the database
include '../DATABASE/db.php'; // Make sure this file correctly initializes $conn

function getUserCount() {
    global $conn;

    $userCount = 0;

    // Call the stored procedure
    $conn->query("CALL GetUserCount(@userCount)");
    $result = $conn->query("SELECT @userCount AS userCount");

    if ($result && $row = $result->fetch_assoc()) {
        $userCount = $row['userCount'];
    }

    return $userCount;
}

// Call the function to get the user count
$totalUsers = getUserCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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

<!-- Main Content Area -->
<div style="margin-left: 21%; padding: 20px;">
    <h1>Dashboard</h1>
    <p>Total Users: <?php echo $totalUsers; ?></p>
</div>

</body>
</html>



