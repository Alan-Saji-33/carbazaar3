<?php
session_start();
require 'db-config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'seller') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if already verified
if ($_SESSION['is_verified']) {
    header("Location: profile.php");
    exit();
}

// Check if already has a pending request
$stmt = $conn->prepare("SELECT * FROM verification_requests WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$has_pending_request = $stmt->get_result()->num_rows > 0;
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_verification'])) {
    $aadhar_number = filter_input(INPUT_POST, 'aadhar_number', FILTER_SANITIZE_STRING);
    
    // Handle Aadhar image upload
    $target_dir = "uploads/verifications/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $file_extension = pathinfo($_FILES["aadhar_image"]["name"], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($_FILES["aadhar_image"]["tmp_name"], $target_file)) {
        // Create verification request
        $stmt = $conn->prepare("INSERT INTO verification_requests (user_id, aadhar_number, aadhar_image) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $aadhar_number, $target_file);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Verification request submitted successfully! Our team will review your details shortly.";
            header("Location: profile.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to submit verification request. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Error uploading Aadhar image. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Verification - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="verification-container">
            <div class="verification-steps">
                <div class="verification-step <?php echo !$has_pending_request ? 'active' : ''; ?>">
                    Submit Documents
                </div>
                <div class="verification-step <?php echo $has_pending_request ? 'active' : ''; ?>">
                    <?php echo $has_pending_request ? 'Under Review' : 'Verification'; ?>
                </div>
            </div>
            
            <div class="verification-content">
                <?php if ($has_pending_request): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i> Your verification request is under review. We'll notify you once it's processed.
                    </div>
                    
                    <div class="text-center">
                        <a href="profile.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Profile
                        </a>
                    </div>
                <?php else: ?>
                    <h3>Seller Verification</h3>
                    <p>To become a verified seller and post more than 3 cars, please submit your Aadhar card details for verification.</p>
                    
                    <form method="POST" enctype="multipart/form-data" class="verification-form">
                        <div class="form-group">
                            <label for="aadhar_number">Aadhar Number</label>
                            <input type="text" id="aadhar_number" name="aadhar_number" class="form-control" 
                                   placeholder="Enter 12-digit Aadhar number" required pattern="[0-9]{12}">
                        </div>
                        
                        <div class="form-group">
                            <label for="aadhar_image">Aadhar Card Image</label>
                            <div class="file-upload">
                                <input type="file" id="aadhar_image" name="aadhar_image" class="file-upload-input" 
                                       accept="image/*" required onchange="previewImage(this, 'aadhar-preview')">
                                <label for="aadhar_image" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    Upload Aadhar Card (Front)
                                </label>
                            </div>
                            <img id="aadhar-preview" src="#" alt="Aadhar Preview" class="aadhar-preview">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="submit_verification" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit for Verification
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>
