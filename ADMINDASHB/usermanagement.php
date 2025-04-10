<?php
include '../DATABASE/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_role = $_POST['user_role']; 

    // Check if the username already exists
    $checkStmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        echo "<p style='color: red;'>Error: Username already exists. Please choose a different username.</p>";
    } else {
        // Hash the password before storing it
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Call the stored procedure with the hashed password
        $stmt = $conn->prepare("CALL AddUser(?, ?, ?, ?)");
        $stmt->bind_param("sssi", $full_name, $username, $hashed_password, $user_role); // Fixed: Use hashed password

        if ($stmt->execute()) {
            echo "<p style='color: green;'>User added successfully.</p>";
        } else {
            echo "<p style='color: red;'>Error adding user: " . $stmt->error . "</p>";
        }

        $stmt->close();
    }

    $checkStmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add New User</title>
</head>
<body>

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

<div style="margin-left: 22%; padding: 20px;">
    <h3>ADD NEW USER</h3>

    <form method="POST" action="">
        <table cellpadding="5" cellspacing="0" border="0">
            <tr>
                <td>Name:</td>
                <td><input type="text" name="full_name" placeholder="Full Name" required></td>
            </tr>
            <tr>
                <td>Username:</td>
                <td><input type="text" name="username" placeholder="Username" required></td>
            </tr>
            <tr>
                <td>Password:</td>
                <td><input type="password" name="password" placeholder="Password" required></td>
            </tr>
            <tr>
                <td>User Role:</td>
                <td>
                    <select name="user_role">
                        <?php
                        include '../DATABASE/db.php';
                        $roles = $conn->query("SELECT role_id, role_name FROM roles");
                        while ($row = $roles->fetch_assoc()) {
                            echo "<option value='{$row['role_id']}'>{$row['role_name']}</option>";
                        }
                        $conn->close();
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="2" align="left">
                    <input type="submit" value="Add User">
                </td>
            </tr>
        </table>
    </form>
</div>

</body>
</html>
