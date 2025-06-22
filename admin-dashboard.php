<?php
session_start();
require 'db-config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get stats for dashboard
$users_count = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$sellers_count = $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'seller'")->fetch_row()[0];
$buyers_count = $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'buyer'")->fetch_row()[0];
$cars_count = $conn->query("SELECT COUNT(*) FROM cars")->fetch_row()[0];
$sold_cars_count = $conn->query("SELECT COUNT(*) FROM cars WHERE is_sold = TRUE")->fetch_row()[0];
$pending_verifications = $conn->query("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'")->fetch_row()[0];

// Get recent users
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Get recent cars
$recent_cars = $conn->query("SELECT c.*, u.username AS seller_name FROM cars c JOIN users u ON c.seller_id = u.id ORDER BY c.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Get verification requests
$verification_requests = $conn->query("SELECT vr.*, u.username, u.email, u.phone 
                                      FROM verification_requests vr 
                                      JOIN users u ON vr.user_id = u.id 
                                      ORDER BY vr.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Handle verification actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verification_action'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_SANITIZE_NUMBER_INT);
    $action = filter_input(INPUT_POST, 'verification_action', FILTER_SANITIZE_STRING);
    $admin_id = $_SESSION['user_id'];
    
    if ($action == 'approve') {
        $stmt = $conn->prepare("UPDATE verification_requests SET status = 'approved', admin_id = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $admin_id, $request_id);
        
        if ($stmt->execute()) {
            // Update user as verified
            $user_id = $conn->query("SELECT user_id FROM verification_requests WHERE id = $request_id")->fetch_row()[0];
            $conn->query("UPDATE users SET is_verified = TRUE WHERE id = $user_id");
            
            $_SESSION['message'] = "Verification request approved successfully!";
            header("Location: admin-dashboard.php");
            exit();
        }
    } elseif ($action == 'reject') {
        $stmt = $conn->prepare("UPDATE verification_requests SET status = 'rejected', admin_id = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $admin_id, $request_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Verification request rejected.";
            header("Location: admin-dashboard.php");
            exit();
        }
    }
    
    $_SESSION['error'] = "Failed to process verification request.";
    header("Location: admin-dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-container">
            <!-- Sidebar -->
            <div class="dashboard-sidebar">
                <h3>Admin Panel</h3>
                
                <ul class="dashboard-menu">
                    <li><a href="#overview" class="active"><i class="fas fa-tachometer-alt"></i> Overview</a></li>
                    <li><a href="#users"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="#cars"><i class="fas fa-car"></i> Cars</a></li>
                    <li><a href="#verifications"><i class="fas fa-id-card"></i> Verifications</a></li>
                    <li><a href="index.php"><i class="fas fa-arrow-left"></i> Back to Site</a></li>
                </ul>
            </div>
            
            <!-- Content -->
            <div class="dashboard-content">
                <!-- Overview Section -->
                <section id="overview" class="profile-section">
                    <h3>Dashboard Overview</h3>
                    
                    <div class="dashboard-stats">
                        <div class="dashboard-stat">
                            <h3><?php echo $users_count; ?></h3>
                            <p>Total Users</p>
                        </div>
                        <div class="dashboard-stat">
                            <h3><?php echo $sellers_count; ?></h3>
                            <p>Sellers</p>
                        </div>
                        <div class="dashboard-stat">
                            <h3><?php echo $buyers_count; ?></h3>
                            <p>Buyers</p>
                        </div>
                        <div class="dashboard-stat">
                            <h3><?php echo $cars_count; ?></h3>
                            <p>Cars Listed</p>
                        </div>
                        <div class="dashboard-stat">
                            <h3><?php echo $sold_cars_count; ?></h3>
                            <p>Cars Sold</p>
                        </div>
                        <div class="dashboard-stat">
                            <h3><?php echo $pending_verifications; ?></h3>
                            <p>Pending Verifications</p>
                        </div>
                    </div>
                </section>
                
                <!-- Recent Users Section -->
                <section id="users" class="profile-section">
                    <h3>Recent Users</h3>
                    
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo ucfirst($user['user_type']); ?></td>
                                    <td>
                                        <?php if ($user['is_verified']): ?>
                                            <span class="status-badge status-approved">Verified</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">Unverified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <a href="#" class="btn btn-outline" style="margin-top: 20px;">
                        <i class="fas fa-users"></i> View All Users
                    </a>
                </section>
                
                <!-- Recent Cars Section -->
                <section id="cars" class="profile-section">
                    <h3>Recent Cars</h3>
                    
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Car</th>
                                <th>Seller</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Listed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_cars as $car): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></td>
                                    <td><?php echo htmlspecialchars($car['seller_name']); ?></td>
                                    <td>₹<?php echo number_format($car['price']); ?></td>
                                    <td>
                                        <?php if ($car['is_sold']): ?>
                                            <span class="status-badge status-rejected">Sold</span>
                                        <?php else: ?>
                                            <span class="status-badge status-approved">Available</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($car['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <a href="#" class="btn btn-outline" style="margin-top: 20px;">
                        <i class="fas fa-car"></i> View All Cars
                    </a>
                </section>
                
                <!-- Verification Requests Section -->
                <section id="verifications" class="profile-section">
                    <h3>Verification Requests</h3>
                    
                    <?php if (count($verification_requests) > 0): ?>
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Aadhar Number</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($verification_requests as $request): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($request['username']); ?><br>
                                            <small><?php echo htmlspecialchars($request['email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($request['aadhar_number']); ?></td>
                                        <td>
                                            <?php if ($request['status'] == 'pending'): ?>
                                                <span class="status-badge status-pending">Pending</span>
                                            <?php elseif ($request['status'] == 'approved'): ?>
                                                <span class="status-badge status-approved">Approved</span>
                                            <?php else: ?>
                                                <span class="status-badge status-rejected">Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                        <td>
                                            <?php if ($request['status'] == 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <button type="submit" name="verification_action" value="approve" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <button type="submit" name="verification_action" value="reject" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span>Processed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> No pending verification requests.
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>
