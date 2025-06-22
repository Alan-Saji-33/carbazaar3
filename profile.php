<?php
session_start();
require 'db-config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
    
    // Handle profile image upload
    $profile_image = $_SESSION['profile_image'];
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "uploads/profiles/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            // Delete old profile image if it exists
            if (!empty($profile_image) {
                @unlink($profile_image);
            }
            $profile_image = $target_file;
        }
    }
    
    try {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, location = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $username, $email, $phone, $location, $profile_image, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['phone'] = $phone;
            $_SESSION['profile_image'] = $profile_image;
            $_SESSION['message'] = "Profile updated successfully!";
            header("Location: profile.php");
            exit();
        } else {
            throw new Exception("Profile update failed: " . $conn->error);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: profile.php");
        exit();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match.";
        header("Location: profile.php");
        exit();
    }
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($db_password);
    $stmt->fetch();
    $stmt->close();
    
    if (!password_verify($current_password, $db_password)) {
        $_SESSION['error'] = "Current password is incorrect.";
        header("Location: profile.php");
        exit();
    }
    
    // Update password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $new_password_hash, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Password changed successfully!";
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to change password. Please try again.";
        header("Location: profile.php");
        exit();
    }
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get user's cars if seller
$cars = [];
if ($_SESSION['user_type'] == 'seller') {
    $stmt = $conn->prepare("SELECT * FROM cars WHERE seller_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cars = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get user's favorites
$favorites = [];
$stmt = $conn->prepare("SELECT c.* FROM favorites f JOIN cars c ON f.car_id = c.id WHERE f.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$favorites = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CarBazaar</title>
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

        <div class="profile-container">
            <!-- Profile Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <img src="<?php echo !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'images/default-avatar.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($user['username']); ?>">
                        <?php if ($user['is_verified']): ?>
                            <div class="profile-verified">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                        <p><?php echo ucfirst($user['user_type']); ?></p>
                        <?php if (!empty($user['location'])): ?>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['location']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="profile-stats">
                    <div class="profile-stat">
                        <h3><?php echo count($favorites); ?></h3>
                        <p>Favorites</p>
                    </div>
                    <?php if ($_SESSION['user_type'] == 'seller'): ?>
                        <div class="profile-stat">
                            <h3><?php echo count($cars); ?></h3>
                            <p>Cars Listed</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <ul class="profile-menu">
                    <li><a href="#profile-info" class="active"><i class="fas fa-user"></i> Profile Information</a></li>
                    <?php if ($_SESSION['user_type'] == 'seller'): ?>
                        <li><a href="#my-cars"><i class="fas fa-car"></i> My Cars</a></li>
                    <?php endif; ?>
                    <li><a href="#favorites"><i class="fas fa-heart"></i> Favorites</a></li>
                    <li><a href="#change-password"><i class="fas fa-lock"></i> Change Password</a></li>
                </ul>
            </div>
            
            <!-- Profile Content -->
            <div class="profile-content">
                <!-- Profile Information Section -->
                <section id="profile-info" class="profile-section">
                    <h3>Profile Information</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" id="location" name="location" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['location']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Profile Image</label>
                            <div class="file-upload">
                                <input type="file" id="profile_image" name="profile_image" class="file-upload-input" 
                                       accept="image/*" onchange="previewImage(this, 'profile-preview')">
                                <label for="profile_image" class="
