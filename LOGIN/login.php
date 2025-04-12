<?php
session_start();

class Auth {
    private $conn;
    private $error;

    public function __construct() {
        $this->conn = new mysqli('localhost', 'root', '', 'inventory_database');
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        // Redirect if already logged in
        if (isset($_SESSION['username'])) {
            $this->redirectUser($_SESSION['role']);
        }
    }

    public function handleLogin($username, $password) {
        // Check for default admin login
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['username'] = 'admin';
            $_SESSION['role'] = 'admin';
            $this->redirectUser('admin');
        }

        // Check user in database
        $stmt = $this->conn->prepare("SELECT password_hash, role_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($hashed_password, $role_id);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = ($role_id == 1) ? 'admin' : 'staff';
                $this->redirectUser($_SESSION['role']);
            }
        }

        $this->error = "Invalid username or password.";
        $stmt->close();
        $this->conn->close();
    }

    private function redirectUser($role) {
        $location = ($role == 'admin') ? '../ADMINDASHB/dashboard.php' : 'staff_dashboard.php';
        header("Location: $location");
        exit();
    }

    public function getError() {
        return $this->error;
    }
}

$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->handleLogin($_POST['username'], $_POST['password']);
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