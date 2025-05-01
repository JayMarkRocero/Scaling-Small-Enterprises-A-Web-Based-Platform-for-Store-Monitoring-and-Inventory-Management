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
        // Default admin
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['user_id'] = 0;
            $_SESSION['username'] = 'admin';
            $_SESSION['role'] = 'admin';
            $this->redirectUser('admin');
        }

        $query = "SELECT user_id, password_hash, role_id FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            $this->error = "Database error: " . $this->conn->error;
            return;
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result(); // required to use num_rows
        $stmt->bind_result($user_id, $hashed_password, $role_id);

        if ($stmt->num_rows > 0) {
            $stmt->fetch();
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
    <style>
        body {
            background-image: url('../WEBSITE IMAGES/LOGIN.png');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            font-family: 'Arial', sans-serif;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            max-width: 400px;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent background */
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .login-container h1 {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 20px;
        }
        .login-container p {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-container .form-control {
            border-radius: 8px;
        }
        .login-container button {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            font-size: 1.1rem;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        .login-container button:hover {
            background-color: #0056b3;
        }
        .login-container .error-message {
            color: red;
            text-align: center;
        }
    </style>
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
