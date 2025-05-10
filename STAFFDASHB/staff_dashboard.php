<?php
session_start();
require_once '../DATABASE/db.php';

$db = new Database();
$conn = $db->getConnection();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

$staffId = $_SESSION['user_id'];

// Get staff info
$stmt = $conn->prepare("CALL getStaffInfo(?)");
$stmt->bind_param("i", $staffId);
$stmt->execute();
$result = $stmt->get_result();
$staffData = $result->fetch_assoc();
$staffUsername = $staffData['username'];
$staffFullName = $staffData['full_name'];
$staffRole = $staffData['role_id'];
$stmt->close();
$conn->next_result();

// Total Products
$result = $conn->query("CALL countProducts()");
$totalProducts = ($row = $result->fetch_assoc()) ? $row['total_products'] : 0;
$conn->next_result();

// Products Sold
$stmt = $conn->prepare("CALL totalSoldByStaff(?)");
$stmt->bind_param("i", $staffId);
$stmt->execute();
$result = $stmt->get_result();
$totalSold = ($row = $result->fetch_assoc()) ? $row['total_sold'] : 0;
$stmt->close();
$conn->next_result();

// Stock on Hand
$result = $conn->query("CALL totalStock()");
$totalStock = ($row = $result->fetch_assoc()) ? $row['total_stock'] : 0;
$conn->next_result();

// Recent Activity
$stmt = $conn->prepare("CALL recentActivity(?)");
$stmt->bind_param("i", $staffId);
$stmt->execute();
$result = $stmt->get_result();
$recentActivity = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->next_result();

// Fetch Profile
$stmt = $conn->prepare("CALL getUserProfile(?)");
$stmt->bind_param("i", $staffId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Helper: Initials
function getInitials($name) {
    return strtoupper(substr(implode('', array_map(fn($word) => $word[0], explode(' ', trim($name)))), 0, 2));
}

$profilePic = $userData['profile_pic'] ?? null;
$profilePicPath = $profilePic ? '../uploads/' . $profilePic : null;
$absolutePath = $profilePic ? __DIR__ . '/../uploads/' . $profilePic : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../STAFFDASHB/staffDashboard.css">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar collapse show">
            <div class="sidebar-header d-flex align-items-center justify-content-between">
                INVENTORY SYSTEM
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link active" href="staff_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="productlist.php"><i class="bi bi-box"></i> Products</a></li>
                <li class="nav-item"><a class="nav-link" href="sales.php"><i class="bi bi-cart"></i> Sales</a></li>
                <li class="nav-item"><a class="nav-link" href="orders_staff.php"><i class="bi bi-bag-check"></i> My Orders</a></li>
                <li class="nav-item mt-3"><a class="nav-link text-danger" href="../LOGIN/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 ms-sm-auto col-lg-10 content">
            <!-- Small Profile Icon Top Right -->
            <div class="d-flex justify-content-end align-items-center mb-3" style="height: 50px;">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if ($profilePic && file_exists($absolutePath)): ?>
                            <img src="<?= htmlspecialchars($profilePicPath) ?>?t=<?= time() ?>" alt="Profile" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <div style="width: 40px; height: 40px; background: #0d6efd; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: bold;">
                                <?= getInitials($staffFullName) ?>
                            </div>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewProfileModal">View Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                    </ul>
                </div>
            </div>

            <!-- Dashboard Cards -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="stat-card">
                        <h6 class="text-muted">Total Products</h6>
                        <h3><?= $totalProducts ?></h3>
                        <i class="bi bi-box text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="stat-card">
                        <h6 class="text-muted">Products Sold</h6>
                        <h3><?= $totalSold ?></h3>
                        <i class="bi bi-cart-check text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="stat-card">
                        <h6 class="text-muted">Stock on Hand</h6>
                        <h3><?= $totalStock ?></h3>
                        <i class="bi bi-box-seam text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="activity-card">
                <h5 class="mb-4">Recent Activity</h5>
                <?php if (empty($recentActivity)): ?>
                    <p class="text-muted">No recent activity</p>
                <?php else: ?>
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item">
                            <h6><?= htmlspecialchars($activity['product_name']) ?></h6>
                            <p class="mb-1">Sold <?= $activity['quantity_sold'] ?> units at ₱<?= number_format($activity['total_price'], 2) ?></p>
                            <small class="text-muted"><?= date('M d, Y h:i A', strtotime($activity['sale_date'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- View Profile Modal -->
<div class="modal fade" id="viewProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Profile Information</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="profile-picture-container" style="width: 150px; height: 150px; margin: 0 auto; position: relative;">
                        <?php if ($profilePic && file_exists($absolutePath)): ?>
                            <img src="<?= htmlspecialchars($profilePicPath) ?>?t=<?= time() ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; background: #0d6efd; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: bold;">
                                <?= getInitials($staffFullName) ?>
                            </div>
                        <?php endif; ?>
                        <label for="profilePicUpload" class="btn btn-sm btn-primary" style="position: absolute; bottom: 0; right: 0; border-radius: 50%;">
                            <i class="bi bi-camera"></i>
                        </label>
                        <input type="file" id="profilePicUpload" accept="image/*" style="display: none;">
                    </div>
                </div>
                <div class="mb-3"><label class="form-label fw-bold">Username</label><p class="form-control-static"><?= htmlspecialchars($userData['username'] ?? '') ?></p></div>
                <div class="mb-3"><label class="form-label fw-bold">Full Name</label><p class="form-control-static"><?= htmlspecialchars($userData['full_name'] ?? '') ?></p></div>
                <div class="mb-3"><label class="form-label fw-bold">Role</label><p class="form-control-static">Staff</p></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="../STAFFDASHB/staffDashboard.js"></script>
</body>
</html>
    