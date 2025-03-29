<?php
session_start();
if (isset($_SESSION['username'])) {
    header("Location: " . ($_SESSION['role'] == 'admin' ? 'dashboardpanel.php' : 'staff_dashboard.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'inventory_database');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // wala to ha need dapat lahat ng admin makapag login sa umpisa 
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        header("Location: ../ADMINDASHB/dashboardpanel.php");
        exit();
    }

    // Fetch user from database
    $stmt = $conn->prepare("SELECT password_hash, user_role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($hashed_password, $role);
    $stmt->fetch();

    if (password_verify($password, $hashed_password)) {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        header("Location: " . ($role == 'admin' ? '../ADMINDASHB/dashboardpanel.php' : 'staff_dashboard.php'));
        exit();
    } else {
        $error = "Invalid username or password.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<body>
<table width="100%" height="100%">
    <tr>
        <td align="center" valign="middle">
            <table border="1" cellpadding="10" cellspacing="0">
                <tr>
                    <td align="center">
                        <h1>Login Panel</h1>
                        <p>Inventory Management System</p>
                        <form method="POST" action="">
                            <input type="text" name="username" placeholder="Username" required>
                            <br><br>
                            <input type="password" name="password" placeholder="Password" required>
                            <br><br>
                            <button type="submit">Login</button>
                        </form>
                        <?php if (isset($error)) echo "<p>$error</p>"; ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>