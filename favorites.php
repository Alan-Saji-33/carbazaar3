<?php
session_start();
require 'db-config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle toggle favorite
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_favorite'])) {
    $car_id = filter_input(INPUT_POST, 'car_id', FILTER_SANITIZE_NUMBER_INT);
    
    // Check if already favorited
    $stmt = $conn->prepare("SELECT * FROM favorites WHERE user_id = ? AND car_id = ?");
    $stmt->bind_param("ii", $user_id, $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Remove from favorites
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND car_id = ?");
        $stmt->bind_param("ii", $user_id, $car_id);
        $stmt->execute();
        $_SESSION['message'] = "Car removed from favorites!";
    } else {
        // Add to favorites
        $stmt = $conn->prepare("INSERT INTO favorites (user_id, car_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $car_id);
        $stmt->execute();
        $_SESSION['message'] = "Car added to favorites!";
    }
    
    header("Location: favorites.php");
    exit();
}

// Get favorite cars
$stmt = $conn->prepare("SELECT c.*, u.username AS seller_name 
                       FROM favorites f 
                       JOIN cars c ON f.car_id = c.id 
                       JOIN users u ON c.seller_id = u.id
                       WHERE f.user_id = ?
                       ORDER BY f.created_at DESC");
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
    <title>Favorites - CarBazaar</title>
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

        <div class="section-header">
            <h2 class="section-title">My Favorite Cars</h2>
            <a href="index.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Browse
            </a>
        </div>
        
        <div class="cars-grid">
            <?php if (count($favorites) > 0): ?>
                <?php foreach ($favorites as $car): ?>
                    <div class="car-card">
                        <div class="car-badge">FAVORITE</div>
                        
                        <div class="car-image">
                            <img src="<?php echo htmlspecialchars($car['image_path']); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                        </div>
                        
                        <div class="car-details">
                            <h3 class="car-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h3>
                            <div class="car-price">₹<?php echo number_format($car['price']); ?></div>
                            
                            <div class="car-specs">
                                <span class="car-spec"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($car['year']); ?></span>
                                <span class="car-spec"><i class="fas fa-tachometer-alt"></i> <?php echo number_format($car['km_driven']); ?> km</span>
                                <span class="car-spec"><i class="fas fa-gas-pump"></i> <?php echo htmlspecialchars($car['fuel_type']); ?></span>
                                <span class="car-spec"><i class="fas fa-cog"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                            </div>
                            
                            <div class="seller-info">
                                <div class="seller-avatar">
                                    <img src="<?php echo !empty($car['seller_image']) ? htmlspecialchars($car['seller_image']) : 'images/default-avatar.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($car['seller_name']); ?>">
                                </div>
                                <div class="seller-details">
                                    <h4><?php echo htmlspecialchars($car['seller_name']); ?></h4>
                                </div>
                            </div>
                            
                            <div class="car-actions">
                                <form method="POST" action="favorites.php" style="display: inline;">
                                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                    <button type="submit" name="toggle_favorite" class="favorite-btn active">
                                        <i class="fas fa-heart"></i> Remove
                                    </button>
                                </form>
                                <a href="car-details.php?id=<?php echo $car['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <i class="fas fa-heart" style="font-size: 60px; color: var(--light-gray); margin-bottom: 20px;"></i>
                    <h3 style="color: var(--gray);">You haven't added any favorites yet</h3>
                    <p>Browse cars and click the heart icon to add them to your favorites</p>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-car"></i> Browse Cars
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>
