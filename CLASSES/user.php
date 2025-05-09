<?php
class User {
    private $conn;

    public function __construct($dbConn) {
        $this->conn = $dbConn;
    }

    public function usernameExists($username) {
        // Prepare and bind
        $stmt = $this->conn->prepare("CALL usernameExist(?, @exists_flag)");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->close();

        // Fetch the OUT parameter
        $result = $this->conn->query("SELECT @exists_flag AS exists_flag");
        $row = $result->fetch_assoc();
        return (bool)$row['exists_flag'];
    }

    public function addUser($full_name, $username, $password, $user_role) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("CALL AddUser(?, ?, ?, ?)");
        $stmt->bind_param("sssi", $full_name, $username, $hashed_password, $user_role);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function GetAllRoles() {
        $stmt = $this->conn->prepare("CALL GetAllRoles()");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }
}
?>
