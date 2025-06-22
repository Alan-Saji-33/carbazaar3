<?php
session_start();
require 'db-config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

$car_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Get car details
$stmt = $conn->prepare("SELECT * FROM cars WHERE id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: profile.php");
    exit();
}

$car = $result->fetch_assoc();
$stmt->close();

// Verify ownership or admin status
if ($_SESSION['user_id'] != $car['seller_id'] && $_SESSION['user_type'] != 'admin') {
    header("Location: profile.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_car'])) {
    $model = filter_input(INPUT_POST, 'model', FILTER_SANITIZE_STRING);
    $brand = filter_input(INPUT_POST, 'brand', FILTER_SANITIZE_STRING);
    $year = filter_input(INPUT_POST, 'year', FILTER_SANITIZE_NUMBER_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $km_driven = filter_input(INPUT_POST, 'km_driven', FILTER_SANITIZE_NUMBER_INT);
    $fuel_type = filter_input(INPUT_POST, 'fuel_type', FILTER_SANITIZE_STRING);
    $transmission = filter_input(INPUT_POST, 'transmission', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    
    // Handle image updates
    $image_path = $car['image_path'];
    $image_path2 = $car['image_path2'];
    $image_path3 = $car['image_path3'];
    
    $target_dir = "uploads/cars/";
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    for ($i = 1; $i <= 3; $i++) {
        if (!empty($_FILES["image$i"]['name'])) {
            $file_extension = strtolower(pathinfo($_FILES["image$i"]['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, $allowed_types)) {
                $_SESSION['error'] = "Only JPG, JPEG, PNG & GIF files are allowed.";
                header("Location: edit-car.php?id=$car_id");
                exit();
            }
            
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["image$i"]['tmp_name'], $target_file)) {
                // Delete old image if it exists
                if ($i == 1 && !empty($image_path)) {
                    @unlink($image_path);
                } elseif ($i == 2 && !empty($image_path2)) {
                    @unlink($image_path2);
                } elseif ($i == 3 && !empty($image_path3)) {
                    @unlink($image_path3);
                }
                
                // Update the corresponding image path
                if ($i == 1) {
                    $image_path = $target_file;
                } elseif ($i == 2) {
                    $image_path2 = $target_file;
                } elseif ($i == 3) {
                    $image_path3 = $target_file;
                }
            } else {
                $_SESSION['error'] = "Error uploading image file.";
                header("Location: edit-car.php?id=$car_id");
                exit();
            }
        }
    }
    
    // Update car in database
    $stmt = $conn->prepare("UPDATE cars SET model = ?, brand = ?, year = ?, price = ?, km_driven = ?, fuel_type = ?, transmission = ?, image_path = ?, image_path2 = ?, image_path3 = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssiisssssssi", $model, $brand, $year, $price, $km_driven, $fuel_type, $transmission, $image_path, $image_path2, $image_path3, $description, $car_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Car updated successfully!";
        header("Location: car-details.php?id=$car_id");
        exit();
    } else {
        $_SESSION['error'] = "Error updating car: " . $conn->error;
        header("Location: edit-car.php?id=$car_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Car - CarBazaar</title>
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
            <h2 class="section-title">Edit Car Details</h2>
            <a href="car-details.php?id=<?php echo $car_id; ?>" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Car
            </a>
        </div>
        
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" id="brand" name="brand" class="form-control" 
                               value="<?php echo htmlspecialchars($car['brand']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="model">Model</label>
                        <input type="text" id="model" name="model" class="form-control" 
                               value="<?php echo htmlspecialchars($car['model']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="year">Year</label>
                        <input type="number" id="year" name="year" class="form-control" 
                               min="1900" max="<?php echo date('Y'); ?>" 
                               value="<?php echo htmlspecialchars($car['year']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (₹)</label>
                        <input type="number" id="price" name="price" class="form-control" 
                               min="0" step="1" 
                               value="<?php echo htmlspecialchars($car['price']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="km_driven">Kilometers Driven</label>
                        <input type="number" id="km_driven" name="km_driven" class="form-control" 
                               min="0" value="<?php echo htmlspecialchars($car['km_driven']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fuel_type">Fuel Type</label>
                        <select id="fuel_type" name="fuel_type" class="form-control" required>
                            <option value="Petrol" <?php echo $car['fuel_type'] == 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                            <option value="Diesel" <?php echo $car['fuel_type'] == 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                            <option value="Electric" <?php echo $car['fuel_type'] == 'Electric' ? 'selected' : ''; ?>>Electric</option>
                            <option value="Hybrid" <?php echo $car['fuel_type'] == 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                            <option value="CNG" <?php echo $car['fuel_type'] == 'CNG' ? 'selected' : ''; ?>>CNG</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="transmission">Transmission</label>
                        <select id="transmission" name="transmission" class="form-control" required>
                            <option value="Automatic" <?php echo $car['transmission'] == 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                            <option value="Manual" <?php echo $car['transmission'] == 'Manual' ? 'selected' : ''; ?>>Manual</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Car Images</label>
                    
                    <div class="file-upload-group">
                        <div class="file-upload">
                            <input type="file" id="image1" name="image1" class="file-upload-input" accept="image/*" onchange="previewImage(this, 'preview1')">
                            <label for="image1" class="file-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                Main Image (Current: <?php echo basename($car['image_path']); ?>)
                            </label>
                        </div>
                        <img id="preview1" src="<?php echo $car['image_path']; ?>" alt="Current Image" style="max-width: 150px; margin-top: 10px; border-radius: 8px;">
                    </div>
                    
                    <?php if (!empty($car['image_path2'])): ?>
                        <div class="file-upload-group">
                            <div class="file-upload">
                                <input type="file" id="image2" name="image2" class="file-upload-input" accept="image/*" onchange="previewImage(this, 'preview2')">
                                <label for="image2" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    Additional Image (Current: <?php echo basename($car['image_path2']); ?>)
                                </label>
                            </div>
                            <img id="preview2" src="<?php echo $car['image_path2']; ?>" alt="Current Image" style="max-width: 150px; margin-top: 10px; border-radius: 8px;">
                        </div>
                    <?php else: ?>
                        <div class="file-upload-group">
                            <div class="file-upload">
                                <input type="file" id="image2" name="image2" class="file-upload-input" accept="image/*" onchange="previewImage(this, 'preview2')">
                                <label for="image2" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    Add Additional Image (Optional)
                                </label>
                            </div>
                            <img id="preview2" src="#" alt="Preview" style="max-width: 150px; display: none; margin-top: 10px; border-radius: 8px;">
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($car['image_path3'])): ?>
                        <div class="file-upload-group">
                            <div class="file-upload">
                                <input type="file" id="image3" name="image3" class="file-upload-input" accept="image/*" onchange="previewImage(this, 'preview3')">
                                <label for="image3" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    Additional Image (Current: <?php echo basename($car['image_path3']); ?>)
                                </label>
                            </div>
                            <img id="preview3" src="<?php echo $car['image_path3']; ?>" alt="Current Image" style="max-width: 150px; margin-top: 10px; border-radius: 8px;">
                        </div>
                    <?php else: ?>
                        <div class="file-upload-group">
                            <div class="file-upload">
                                <input type="file" id="image3" name="image3" class="file-upload-input" accept="image/*" onchange="previewImage(this, 'preview3')">
                                <label for="image3" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    Add Additional Image (Optional)
                                </label>
                            </div>
                            <img id="preview3" src="#" alt="Preview" style="max-width: 150px; display: none; margin-top: 10px; border-radius: 8px;">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($car['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="update_car" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
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
