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
        if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
            $this->redirectUser($_SESSION['role']);
        }
    }

    public function handleLogin($username, $password) {
        $stmt = $this->conn->prepare("CALL CheckUserLogin(?)");
    
        if (!$stmt) {
            $this->error = "Database error: " . $this->conn->error;
            return;
        }
    
        $stmt->bind_param("s", $username);
        $stmt->execute();
    
        $result = $stmt->get_result();
    
        if ($result && $row = $result->fetch_assoc()) {
            $user_id = $row['user_id'];
            $hashed_password = $row['password_hash'];
            $role_id = $row['role_id'];
    
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $user_id;
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
        if ($role == 'admin') {
            header("Location: ../ADMINDASHB/dashboard.php");
        } else {
            header("Location: ../STAFFDASHB/staff_dashboard.php");
        }
        exit();
    }
    

    public function getError() {
        return $this->error;
    }
}

$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $auth->handleLogin($username, $password);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../ADMINDASHB/bootstrap.css">
</head>
<body>

    <div class="login-container">
        <h1>Login Panel</h1>
        <p>Inventory Management System</p>
        <form method="POST" action="">
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <?php
        if ($auth->getError()) {
            echo "<p class='error-message'>" . $auth->getError() . "</p>";
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="preload" as="image" href="../WEBSITE IMAGES/LOGIN.png">
</body>
</html>
