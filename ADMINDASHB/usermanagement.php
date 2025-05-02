<?php
require_once '../DATABASE/db.php';
require_once '../CLASSES/user.php';

$db = new Database();
$conn = $db->getConnection();
$user = new User($conn);

// Debugging the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Add User
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_role = $_POST['user_role'];

    if ($user->usernameExists($username)) {
        $error_message = "Username already exists. Please choose a different username.";
    } else {
        if ($user->addUser($full_name, $username, $password, $user_role)) {
            $success_message = "User added successfully.";
        } else {
            $error_message = "Error adding user.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../ADMINDASHB/bootstrap.css">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="sidebar-header">
                INVENTORY SYSTEM
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="../ADMINDASHB/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="../ADMINDASHB/usermanagement.php">
                        <i class="bi bi-people"></i> User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../ADMINDASHB/product.php">
                        <i class="bi bi-box"></i> Products
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="sales_rep.php">
                        <i class="bi bi-graph-up"></i> Sales Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../ADMINDASHB/orders.php">
                         <i class="bi bi-bag-check"></i> Ordering
                    </a>
                 </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-danger" href="../LOGIN/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>User Management</h2>
                <!-- Button to trigger modal -->
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#userModal">Add New User</button>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- User List Table -->
            <div class="card">
                <div class="card-header">List of Users</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("CALL GetAllUsersWithRoles()");

                            if (!$result) {
                                echo "<tr><td colspan='4'>Error fetching users: " . $conn->error . "</td></tr>";
                            } else {
                                $i = 1;
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>" . $i++ . "</td>
                                            <td>" . htmlspecialchars($row['full_name']) . "</td>
                                            <td>" . htmlspecialchars($row['username']) . "</td>
                                            <td>" . htmlspecialchars($row['role_name']) . "</td>
                                          </tr>";
                                }
                                $result->close();
                                $conn->next_result();
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal for Adding User -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalLabel">Add User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label for="full_name" class="form-label">Name:</label>
                <input type="text" class="form-control" id="full_name" name="full_name" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="user_role" class="form-label">User Role:</label>
                <select class="form-select" id="user_role" name="user_role" required>
                    <option value="">Select Role</option>
                    <?php
                    $roles = $conn->query("CALL GetAllRoles()");

                    if ($roles) {
                        if ($roles->num_rows > 0) {
                            while ($row = $roles->fetch_assoc()) {
                                echo "<option value='{$row['role_id']}'>{$row['role_name']}</option>";
                            }
                            $roles->close();
                            $conn->next_result();
                        } else {
                            echo "<option disabled>No roles found</option>";
                        }
                    } else {
                        echo "<option disabled>Error loading roles: " . $conn->error . "</option>";
                    }
                    
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add User</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>