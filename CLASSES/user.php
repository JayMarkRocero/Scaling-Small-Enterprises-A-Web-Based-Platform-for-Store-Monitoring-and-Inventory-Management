<?php
class User {
    private $conn;

    public function __construct($dbConn) {
        $this->conn = $dbConn;
    }

    public function usernameExists($username) {
        $stmt = $this->conn->prepare("SELECT username FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public function addUser($full_name, $username, $password, $user_role) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("CALL AddUser(?, ?, ?, ?)");
        $stmt->bind_param("sssi", $full_name, $username, $hashed_password, $user_role);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getRoles() {
        $result = $this->conn->query("SELECT role_id, role_name FROM roles");
        return $result;
    }
}
?>
