<?php
session_start();
require 'db-config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'seller' && $_SESSION['user_type'] != 'admin')) {
    header("Location: login.php");
    exit();
}

// Check if seller needs verification
if ($_SESSION['user_type'] == 'seller' && $_SESSION['cars_posted'] >= 3 && !$_SESSION['is_verified']) {
    $_SESSION['error'] = "You need to verify your account to post more cars.";
    header("Location: verify-seller.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_car'])) {
    $seller_id = $_SESSION['user_id'];
    $model = filter_input(INPUT_POST, 'model', FILTER_SANITIZE_STRING);
    $brand = filter_input(INPUT_POST, 'brand', FILTER_SANITIZE_STRING);
    $year = filter_input(INPUT_POST, 'year', FILTER_SANITIZE_NUMBER_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $km_driven = filter_input(INPUT_POST, 'km_driven', FILTER_SANITIZE_NUMBER_INT);
    $fuel_type = filter_input(INPUT_POST, 'fuel_type', FILTER_SANITIZE_STRING);
    $transmission = filter_input(INPUT_POST, 'transmission', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    
    // Handle file uploads
    $target_dir = "uploads/cars/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $uploaded_images = [];
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    for ($i = 1; $i <= 3; $i++) {
        if (!empty($_FILES["image$i"]['name'])) {
            $file_extension = strtolower(pathinfo($_FILES["image$i"]['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, $allowed_types)) {
                $_SESSION['error'] = "Only JPG, JPEG, PNG & GIF files are allowed.";
                header("Location: add-car.php");
                exit();
            }
            
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["image$i"]['tmp_name'], $target_file)) {
                $uploaded_images[] = $target_file;
            } else {
                $_SESSION['error'] = "Error uploading image file.";
                header("Location: add-car.php");
                exit();
            }
        }
    }
    
    if (count($uploaded_images) == 0) {
        $_SESSION['error'] = "At least one image is required.";
        header("Location: add-car.php");
        exit();
    }
    
    // Insert car into database
    $stmt = $conn->prepare("INSERT INTO cars (seller_id, model, brand, year, price, km_driven, fuel_type, transmission, image_path, image_path2, image_path3, description) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Pad the array with nulls if less than 3 images were uploaded
    while (count($uploaded_images) < 3) {
        $uploaded_images[] = null;
    }
    
    $stmt->bind_param("issiisssssss", $seller_id, $model, $brand, $year, $price, $km_driven, $fuel_type, $transmission, 
                      $uploaded_images[0], $uploaded_images[1], $uploaded_images[2], $description);
    
    if ($stmt->execute()) {
        // Update seller's cars posted count
        $conn->query("UPDATE users SET cars_posted = cars_posted + 1 WHERE id = $seller_id");
        $_SESSION['cars_posted']++;
        
        $_SESSION['message'] = "Car added successfully!";
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['error'] = "Error adding car: " . $conn->error;
        header("Location: add-car.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Car - CarBazaar</title>
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

        <div class="section-header">
            <h2 class="section-title">Add New Car</h2>
            <a href="profile.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Profile
            </a>
        </div>
        
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" id="brand" name="brand" class="form-control" placeholder="e.g. Toyota" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="model">Model</label>
                        <input type="text" id="model" name="model" class="form-control" placeholder="e.g. Corolla" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="year">Year</label>
                        <input type="number" id="year" name="year" class="form-control" min="1900" max="<?php echo date('Y'); ?>" placeholder="e.g. 2020" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (₹)</label>
                        <input type="number" id="price" name="price" class="form-control" min="0" step="1" placeholder="e.g. 500000" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="km_driven">Kilometers Driven</label>
                        <input type="number" id="km_driven" name="km_driven" class="form-control" min="0" placeholder="e.g. 25000" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fuel_type">Fuel Type</label>
                        <select id="fuel_type" name="fuel_type" class="form-control" required>
                            <option value="">Select Fuel Type</option>
                            <option value="Petrol">Petrol</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Electric">Electric</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="CNG">CNG</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="transmission">Transmission</label>
                        <select id="transmission" name="transmission" class="form-control" required>
                            <option value="">Select Transmission</option>
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Car Images (Upload at least one)</label>
                    
                    <div class="file-upload-group">
                        <div class="file-upload">
                            <input type="file" id="image1" name="image1" class="file-upload-input" accept="image/*" onchange="previewImage(this, 'preview1')">
                            <label for="image1" class="file-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                Main Image (Required)
                            </label>
                        </div>
                        <img id="preview1" src="#" alt="Preview" style="max-width: 150px; display: none; margin-top: 10px; border-radius: 8px;">
                    </div>
                    
                    <div class="file-upload-group">
                        <div class="file-upload">
                            <input type="file" id="image2" name="image2" class="file-upload-input" accept="image/*" onchange="previewImage(this, 'preview2')">
                            <label for="image2" class="file-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                Additional Image (Optional)
                            </label>
                        </div>
                        <img id="preview2" src="#" alt="Preview" style="max-width: 150px; display: none; margin-top: 10px; border-radius: 8px;">
                    </div>
                    
                    <div class="file-upload-group">
                        <div class="file-upload">
                            <input type="file" id="image3" name="image3" class="file-upload-input" accept="image/*" onchange="previewImage(this, 'preview3')">
                            <label for="image3" class="file-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                Additional Image (Optional)
                            </label>
                        </div>
                        <img id="preview3" src="#" alt="Preview" style="max-width: 150px; display: none; margin-top: 10px; border-radius: 8px;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="5" placeholder="Add details about the car's condition, features, etc." required></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="add_car" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Car
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>
