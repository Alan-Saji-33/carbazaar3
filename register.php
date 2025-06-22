<?php
session_start();
require 'db-config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = filter_input(INPUT_POST, 'user_type', FILTER_SANITIZE_STRING);
    
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, phone, user_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $password, $email, $phone, $user_type);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } else {
            throw new Exception("Registration failed: " . $conn->error);
        }
    } catch (Exception $e) {
        if ($conn->errno == 1062) {
            // Duplicate entry error
            if (strpos($conn->error, 'username') !== false) {
                $_SESSION['error'] = "Username already exists. Please choose another.";
            } elseif (strpos($conn->error, 'email') !== false) {
                $_SESSION['error'] = "Email already exists. Please use another email.";
            } else {
                $_SESSION['error'] = "Registration failed. Please try again.";
            }
        } else {
            $_SESSION['error'] = $e->getMessage();
        }
        header("Location: register.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <h2>Create Your Account</h2>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_type">I want to:</label>
                        <select id="user_type" name="user_type" class="form-control" required>
                            <option value="">Select Account Type</option>
                            <option value="buyer">Buy Cars</option>
                            <option value="seller">Sell Cars</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="register" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </div>
                    
                    <div class="form-footer">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>
